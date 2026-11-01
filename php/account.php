<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/backend/firebase.php';
start_app_session();

if (empty($_SESSION['user_id'])) {
    header('Location: loginform.php?return_to=account.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$message = '';
$messageType = 'success';
$formName = '';
$formAbout = '';
$formWebsite = '';
$formUsername = '';

try {
    $conn = db();
    app_ensure_customer_profile_schema($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $formName = trim((string)($_POST['name'] ?? ''));
        $formAbout = trim((string)($_POST['about'] ?? ''));
        $formWebsite = trim((string)($_POST['website'] ?? ''));
        $formUsername = trim((string)($_POST['username'] ?? ''));
        if (!app_verify_csrf()) {
            http_response_code(400);
            $message = 'Your session expired. Please refresh the page and try again.';
            $messageType = 'error';
        } else {
            $fullName = $formName;
            $about = $formAbout;
            $website = $formWebsite;
            $username = $formUsername;
            $nameParts = preg_split('/\s+/', $fullName, 2, PREG_SPLIT_NO_EMPTY) ?: [];
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            if ($fullName === '' || mb_strlen($firstName) > 100 || mb_strlen($lastName) > 100) {
                $message = 'Enter a name with a first and last part of no more than 100 characters each.';
                $messageType = 'error';
            } elseif (mb_strlen($about) > 500) {
                $message = 'Your About section must be 500 characters or fewer.';
                $messageType = 'error';
            } elseif ($username !== '' && !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
                $message = 'Username must be 3–50 characters and use letters, numbers, dots, underscores or hyphens.';
                $messageType = 'error';
            } else {
                if ($website !== '' && !preg_match('#^https?://#i', $website)) {
                    $website = 'https://' . $website;
                }
                if ($website !== '' && (mb_strlen($website) > 255 || filter_var($website, FILTER_VALIDATE_URL) === false || !in_array(strtolower((string)parse_url($website, PHP_URL_SCHEME)), ['http', 'https'], true))) {
                    $message = 'Enter a valid website URL.';
                    $messageType = 'error';
                } else {
                    $duplicate = $conn->prepare('SELECT id FROM `register` WHERE `Username` = ? AND id <> ? LIMIT 1');
                    $duplicate->bind_param('si', $username, $userId);
                    $duplicate->execute();
                    $usernameExists = $username !== '' && (bool)$duplicate->get_result()->fetch_assoc();
                    $duplicate->close();

                    if ($usernameExists) {
                        $message = 'That username is already in use.';
                        $messageType = 'error';
                    } else {
                        try {
                            $firebaseUser = firebase_lookup_id_token(trim((string)($_POST['idToken'] ?? '')));
                            $sessionUid = (string)($_SESSION['firebase_uid'] ?? '');
                            if ($sessionUid === '' || !hash_equals($sessionUid, (string)($firebaseUser['localId'] ?? ''))) {
                                throw new FirebaseSyncException('Your Firebase sign-in no longer matches this session. Sign in again before saving your profile.', 401);
                            }
                            $syncedUser = firebase_sync_customer($firebaseUser, [
                                'first_name' => $firstName,
                                'last_name' => $lastName,
                            ]);
                            $update = $conn->prepare('UPDATE `register` SET `About` = ?, `Website` = ?, `Username` = ? WHERE id = ?');
                            $update->bind_param('sssi', $about, $website, $username, $userId);
                            $update->execute();
                            $update->close();
                            app_set_authenticated_user($syncedUser);
                            header('Location: account.php?updated=1');
                            exit;
                        } catch (Throwable $error) {
                            error_log('Customer profile Firebase synchronization failed: ' . $error->getMessage());
                            $message = $error->getMessage();
                            $messageType = 'error';
                        }
                    }
                }
            }
        }
    }

    $stmt = $conn->prepare('SELECT id, First_Name, Last_Name, `E-mail`, About, Website, Username, Firebase_UID, Photo_URL, created_at FROM register WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        app_destroy_session();
        header('Location: loginform.php?status=session-expired');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $formName = trim((string)$user['First_Name'] . ' ' . (string)$user['Last_Name']);
        $formAbout = (string)($user['About'] ?? '');
        $formWebsite = (string)($user['Website'] ?? '');
        $formUsername = (string)($user['Username'] ?? '');
    }

    app_ensure_car_catalog_schema($conn);
    app_ensure_car_service_schema($conn);
    app_ensure_sales_booking_schema($conn);
    app_ensure_notification_schema($conn);
    $driveStmt = $conn->prepare('SELECT id, Series, Model, City, Finance, Status, Appointment_Date, Appointment_Time, created_at FROM `test drive` WHERE Customer_ID = ? OR `E-Mail` = ? ORDER BY id DESC LIMIT 10');
    $driveStmt->bind_param('is', $userId, $user['E-mail']);
    $driveStmt->execute();
    $testDrives = $driveStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $driveStmt->close();

    $serviceStmt = $conn->prepare('SELECT id, Vehicle_Model, Registration_Number, Service_Type, City, Status, Preferred_Date, Preferred_Time, Appointment_Date, Appointment_Time, created_at FROM `car_service_requests` WHERE Customer_ID = ? OR `E-Mail` = ? ORDER BY id DESC LIMIT 10');
    $serviceStmt->bind_param('is', $userId, $user['E-mail']);
    $serviceStmt->execute();
    $serviceRequests = $serviceStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $serviceStmt->close();

    $bookingStmt = $conn->prepare('SELECT id, Vehicle_Name, Vehicle_Price, City, Payment_Method, Trade_In, Status, created_at FROM `car_bookings` WHERE Customer_ID = ? OR `E-Mail` = ? ORDER BY id DESC LIMIT 10');
    $bookingStmt->bind_param('is', $userId, $user['E-mail']);
    $bookingStmt->execute();
    $salesBookings = $bookingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $bookingStmt->close();

    $feedbackStmt = $conn->prepare('SELECT model, rating, message, created_at FROM feedback WHERE email = ? ORDER BY id DESC LIMIT 10');
    $feedbackStmt->bind_param('s', $user['E-mail']);
    $feedbackStmt->execute();
    $feedback = $feedbackStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $feedbackStmt->close();
} catch (Throwable $error) {
    error_log('Customer account could not be loaded: ' . $error->getMessage());
    http_response_code(500);
    exit('Unable to load your account right now. Please try again later.');
}

$name = trim((string)$user['First_Name'] . ' ' . (string)$user['Last_Name']);
$initial = strtoupper(mb_substr($name !== '' ? $name : 'U', 0, 1));
$photoUrl = trim((string)($user['Photo_URL'] ?? ''));
if (strtolower((string)parse_url($photoUrl, PHP_URL_SCHEME)) !== 'https') {
    $photoUrl = '';
}
if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $message = 'Your profile has been saved.';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Account | Beamers Legacy</title>
<link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1">
<style>
:root{--profile-ink:#171717;--profile-muted:#777;--profile-border:#c9c9c6;--profile-surface:#fff;--profile-soft:#f4f4f1}
body{background:var(--profile-surface);color:var(--profile-ink);font-family:var(--font-sans);min-height:100vh;margin:0;padding:36px 20px 110px}
.account-shell{width:min(640px,100%);margin:0 auto}
.account-header{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;flex-wrap:wrap;margin-bottom:32px}
.eyebrow{color:#646464;letter-spacing:.14em;font-size:11px;font-weight:700;text-transform:uppercase}
.muted,.profile-hint{color:var(--profile-muted)}
.account-header h1{margin:8px 0;font-size:clamp(28px,5vw,40px);letter-spacing:-.04em}
.actions{display:flex;gap:10px;flex-wrap:wrap}
a.action{display:inline-block;padding:11px 16px;text-decoration:none;font-weight:600;font-size:.8rem;transition:background .2s,color .2s}
.primary{background:var(--profile-ink);color:#fff}.secondary{border:1px solid var(--profile-border);color:var(--profile-ink)}
a.action:hover{background:#e8e8e5;color:#111}
.profile-card,.history-card{padding:26px 0 30px;border-bottom:1px solid #e5e5e2}
.profile-heading{margin:0 0 18px;font-size:1.12rem;font-weight:600}
.photo-row{display:flex;align-items:center;gap:18px;margin-bottom:26px}
.profile-avatar{width:92px;height:92px;border-radius:50%;display:grid;place-items:center;background:#dfe5ff;color:#111;font-size:2.1rem;font-weight:500}
.profile-hint{font-size:.85rem}
.profile-form{display:grid;gap:18px}
.profile-field{position:relative;display:grid}
.profile-field label{position:absolute;z-index:1;top:13px;left:19px;font-size:.85rem;font-weight:500}
.profile-field input,.profile-field textarea{width:100%;box-sizing:border-box;border:1.5px solid var(--profile-border);border-radius:18px;background:#fff;color:var(--profile-ink);padding:14px 18px;font:inherit;font-size:1rem;outline:none;transition:border-color .15s,box-shadow .15s}
.profile-field input{height:96px;padding:37px 18px 12px}
.profile-field textarea{min-height:118px;padding:39px 18px 12px;resize:vertical}
.profile-field input:focus,.profile-field textarea:focus{border-color:#555;box-shadow:0 0 0 3px rgba(0,0,0,.06)}
.profile-field input[readonly]{color:#777;background:#fafaf9}
.field-note{margin:2px 0 0 18px;color:var(--profile-muted);font-size:.83rem}
.profile-message{margin:0 0 20px;padding:12px 16px;border-radius:10px;background:#eef5ee;color:#24572b}
.profile-message.error{background:#fff0ef;color:#8c2922}
.table-wrap{overflow:auto}.account-table{width:100%;border-collapse:collapse;min-width:620px}.account-table th,.account-table td{padding:12px;border-bottom:1px solid #e5e5e2;text-align:left;font-size:13px}.account-table th{color:var(--profile-muted);text-transform:uppercase;font-size:10px;letter-spacing:.08em}.empty{color:var(--profile-muted);padding:14px 0}
.save-bar{position:fixed;z-index:20;bottom:0;left:0;right:0;display:flex;justify-content:flex-end;gap:12px;padding:14px max(24px,calc((100vw - 640px)/2));background:rgba(255,255,255,.96);border-top:1px solid #ececea;box-shadow:0 -5px 18px rgba(0,0,0,.04);backdrop-filter:blur(10px)}
.save-bar button{min-width:98px;padding:14px 20px;border:0;border-radius:18px;background:#e8e8e5;color:#777;font:inherit;font-weight:600;cursor:pointer}
.save-bar button[type=submit]{background:#171717;color:#fff}.save-bar button[type=submit]:hover{background:#333}
@media(max-width:600px){body{padding:24px 18px 100px}.profile-card,.history-card{padding-top:20px}.profile-field input{height:60px}.save-bar{padding:12px 18px}.save-bar button{flex:1}}
</style>
</head>
<body>
<main class="account-shell">
  <header class="account-header">
    <div><p class="eyebrow">BEAMERS LEGACY / CUSTOMER PORTAL</p><h1>Profile settings</h1><p class="muted">Manage your personal details and showroom activity.</p></div>
    <div class="actions"><a class="action secondary" href="home.php">Back to showroom</a><a class="action primary" href="logout.php">Sign out</a></div>
  </header>

  <?php if ($message !== ''): ?><p class="profile-message<?= $messageType === 'error' ? ' error' : '' ?>" role="status"><?= e($message) ?></p><?php endif; ?>

  <section class="profile-card" aria-labelledby="profile-title">
    <h2 class="profile-heading" id="profile-title">Your profile</h2>
    <div class="photo-row"><div class="profile-avatar"><?php if ($photoUrl !== ''): ?><img src="<?= e($photoUrl) ?>" alt="<?= e($name) ?> profile photo"><?php else: ?><span aria-hidden="true"><?= e($initial) ?></span><?php endif; ?></div><div><strong>Profile photo</strong><p class="profile-hint"><?= $photoUrl !== '' ? 'Synchronized from your Firebase profile.' : 'Your initials are used as your account photo.' ?></p></div></div>
    <form class="profile-form" id="profile-form" method="post" action="account.php">
      <input type="hidden" name="csrf" value="<?= e(app_csrf_token()) ?>">
      <input type="hidden" name="idToken" id="profile-id-token" value="">
      <div class="profile-field"><label for="profile-name">Name</label><input id="profile-name" name="name" type="text" maxlength="201" value="<?= e($formName) ?>" autocomplete="name" required></div>
      <div class="profile-field"><label for="profile-about">About</label><textarea id="profile-about" name="about" maxlength="500" placeholder="Tell us about yourself"><?= e($formAbout) ?></textarea></div>
      <div class="profile-field"><label for="profile-website">Website</label><input id="profile-website" name="website" type="url" maxlength="255" value="<?= e($formWebsite) ?>" placeholder="https://"><p class="field-note">Add a link to your website.</p></div>
      <div class="profile-field"><label for="profile-username">Username</label><input id="profile-username" name="username" type="text" maxlength="50" value="<?= e($formUsername) ?>" autocomplete="username" pattern="[A-Za-z0-9._-]{3,50}" placeholder="Choose a username"><p class="field-note">Letters, numbers, dots, underscores and hyphens; 3–50 characters.</p></div>
      <div class="profile-field"><label for="profile-email">Email</label><input id="profile-email" type="email" value="<?= e((string)$user['E-mail']) ?>" readonly></div>
    </form>
  </section>

  <section class="history-card">
    <p class="eyebrow">TEST DRIVE HISTORY</p>
    <p class="profile-hint"><a href="notifications.php">View your notifications</a></p>
    <?php if ($testDrives): ?>
      <div class="table-wrap"><table class="account-table"><thead><tr><th>Vehicle</th><th>Status / Appointment</th><th>City</th><th>Finance</th><th>Requested</th></tr></thead><tbody>
      <?php foreach ($testDrives as $drive): ?><tr id="test-drive-<?= (int)$drive['id'] ?>"><td><?= e((string)$drive['Series'] . ' · ' . (string)$drive['Model']) ?></td><td><?= e(ucfirst((string)($drive['Status'] ?? 'pending'))) ?><?php if (!empty($drive['Appointment_Date'])): ?><small><?= e(date('d M Y', strtotime((string)$drive['Appointment_Date']))) ?><?= !empty($drive['Appointment_Time']) ? ' · ' . e(date('g:i A', strtotime((string)$drive['Appointment_Time']))) : '' ?></small><?php endif; ?></td><td><?= e((string)$drive['City']) ?></td><td><?= e((string)$drive['Finance'] === 'yes' ? 'Interested' : 'Not interested') ?></td><td><?= e(date('d M Y', strtotime((string)$drive['created_at']))) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php else: ?><p class="empty">No test-drive requests yet. <a href="testdrive.php">Book your first drive.</a></p><?php endif; ?>
  </section>

  <section class="history-card">
    <p class="eyebrow">CAR SERVICE HISTORY</p>
    <?php if ($serviceRequests): ?>
      <div class="table-wrap"><table class="account-table"><thead><tr><th>Vehicle</th><th>Service</th><th>Status / Appointment</th><th>City</th><th>Requested</th></tr></thead><tbody>
      <?php foreach ($serviceRequests as $service): ?><tr id="service-request-<?= (int)$service['id'] ?>"><td><?= e((string)$service['Vehicle_Model']) ?><small><?= e((string)$service['Registration_Number']) ?></small></td><td><?= e((string)$service['Service_Type']) ?></td><td><?= e(ucfirst((string)($service['Status'] ?? 'pending'))) ?><?php $serviceDate = $service['Appointment_Date'] ?: $service['Preferred_Date']; $serviceTime = $service['Appointment_Time'] ?: $service['Preferred_Time']; if (!empty($serviceDate)): ?><small><?= e(date('d M Y', strtotime((string)$serviceDate))) ?><?= !empty($serviceTime) ? ' · ' . e(date('g:i A', strtotime((string)$serviceTime))) : '' ?></small><?php endif; ?></td><td><?= e((string)$service['City']) ?></td><td><?= e(date('d M Y', strtotime((string)$service['created_at']))) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php else: ?><p class="empty">No car-service requests yet. <a href="carservice.php">Request a service appointment.</a></p><?php endif; ?>
  </section>

  <section class="history-card">
    <p class="eyebrow">SALES BOOKINGS</p>
    <?php if ($salesBookings): ?>
      <div class="table-wrap"><table class="account-table"><thead><tr><th>Vehicle</th><th>Purchase preference</th><th>Status</th><th>City</th><th>Requested</th></tr></thead><tbody>
      <?php foreach ($salesBookings as $booking): ?><tr id="sales-booking-<?= (int)$booking['id'] ?>"><td><?= e((string)$booking['Vehicle_Name']) ?><?php if (!empty($booking['Vehicle_Price'])): ?><small><?= e((string)$booking['Vehicle_Price']) ?></small><?php endif; ?></td><td><?= e((string)$booking['Payment_Method'] === 'cash' ? 'Full payment' : 'Finance / EMI') ?><?= (string)$booking['Trade_In'] === 'yes' ? ' · Trade-in' : '' ?></td><td><?= e(ucfirst((string)($booking['Status'] ?? 'pending'))) ?></td><td><?= e((string)$booking['City']) ?></td><td><?= e(date('d M Y', strtotime((string)$booking['created_at']))) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php else: ?><p class="empty">No BMW booking requests yet. <a href="models.php">Explore BMW models.</a></p><?php endif; ?>
  </section>

  <section class="history-card">
    <p class="eyebrow">YOUR FEEDBACK</p>
    <?php if ($feedback): ?>
      <div class="table-wrap"><table class="account-table"><thead><tr><th>Model</th><th>Rating</th><th>Message</th><th>Submitted</th></tr></thead><tbody>
      <?php foreach ($feedback as $item): ?><tr><td><?= e((string)($item['model'] ?: 'General')) ?></td><td><?= $item['rating'] !== null ? e(str_repeat('★', (int)$item['rating'])) : '—' ?></td><td><?= e((string)$item['message']) ?></td><td><?= e(date('d M Y', strtotime((string)$item['created_at']))) ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    <?php else: ?><p class="empty">You have not submitted feedback yet. <a href="feedback.php">Share your experience.</a></p><?php endif; ?>
  </section>
</main>
<div class="save-bar"><button type="reset" form="profile-form">Reset</button><button type="submit" form="profile-form">Save</button></div>
<script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.12.5/firebase-auth-compat.js"></script>
<script src="../js/firebase-config.js"></script>
<script>
document.getElementById('profile-form').addEventListener('submit', async function (event) {
  event.preventDefault();
  const form = event.currentTarget;
  if (!form.reportValidity()) return;
  const message = document.querySelector('.profile-message');
  const firebaseUser = typeof firebase !== 'undefined' && firebase.apps.length
    ? firebase.auth().currentUser
    : null;
  if (!firebaseUser) {
    message.textContent = 'Your Firebase sign-in has expired. Sign in again before saving your profile.';
    message.classList.add('error');
    return;
  }
  try {
    const nameParts = document.getElementById('profile-name').value.trim().split(/\s+/);
    await firebaseUser.updateProfile({displayName: nameParts.join(' ')});
    document.getElementById('profile-id-token').value = await firebaseUser.getIdToken(true);
    form.submit();
  } catch (error) {
    message.textContent = error && error.message
      ? error.message
      : 'Firebase could not update your profile. Please try again.';
    message.classList.add('error');
  }
});
</script>
</body>
</html>
