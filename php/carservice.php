<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

start_app_session();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !app_verify_csrf()) {
    http_response_code(419);
    exit('Your session expired. Please go back and submit the form again.');
}

$allowedCities = ['Ahmedabad', 'Mumbai', 'Delhi', 'Bangalore', 'Chennai'];
$serviceTypes = ['Periodic maintenance', 'Repair / diagnosis', 'Tyres / brakes', 'Bodywork', 'Other'];
$statusInput = $_GET['status'] ?? '';
$status = is_string($statusInput) ? $statusInput : '';
$formError = '';
$prefillFirst = (string)($_SESSION['first_name'] ?? '');
$prefillLast = (string)($_SESSION['last_name'] ?? '');
$prefillEmail = (string)($_SESSION['email'] ?? '');

function car_service_form_value(string $key, string $default = ''): string
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$key]) && is_scalar($_POST[$key])) {
        return trim((string)$_POST[$key]);
    }
    return $default;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleModel = mb_substr(trim(strip_tags((string)($_POST['Vehicle_Model'] ?? ''))), 0, 120);
    $registration = strtoupper(preg_replace('/\s+/', '', trim((string)($_POST['Registration_Number'] ?? ''))) ?? '');
    $serviceType = trim((string)($_POST['Service_Type'] ?? ''));
    $firstName = mb_substr(trim(strip_tags((string)($_POST['First_Name'] ?? ''))), 0, 100);
    $lastName = mb_substr(trim(strip_tags((string)($_POST['Last_Name'] ?? ''))), 0, 100);
    $email = strtolower(trim((string)($_POST['E-Mail'] ?? '')));
    $mobile = trim((string)($_POST['Mobile_No'] ?? ''));
    $city = trim((string)($_POST['City'] ?? ''));
    $preferredDate = trim((string)($_POST['Preferred_Date'] ?? ''));
    $preferredTime = trim((string)($_POST['Preferred_Time'] ?? ''));
    $notes = mb_substr(trim(strip_tags((string)($_POST['Notes'] ?? ''))), 0, 1000);
    $privacyConsent = (string)($_POST['Privacy_Consent'] ?? '');

    if ($vehicleModel === '' || !preg_match('/^[A-Z0-9-]{4,20}$/', $registration) || !in_array($serviceType, $serviceTypes, true)) {
        $formError = 'Enter your BMW model, a valid registration number, and the service you need.';
    } elseif ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[0-9]{10}$/', $mobile) || !in_array($city, $allowedCities, true) || $privacyConsent !== 'yes') {
        $formError = 'Please complete all contact fields with a valid email address and 10-digit mobile number, and accept the privacy terms.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $preferredDate) || $preferredDate < date('Y-m-d') || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $preferredTime)) {
        $formError = 'Choose a valid preferred service date and time.';
    } else {
        try {
            $conn = db();
            app_ensure_car_catalog_schema($conn);
            app_ensure_car_service_schema($conn);
            app_ensure_notification_schema($conn);
            $customerId = 0;
            $customerLookup = $conn->prepare('SELECT `id` FROM `register` WHERE LOWER(`E-mail`) = LOWER(?) LIMIT 1');
            $customerLookup->bind_param('s', $email);
            $customerLookup->execute();
            $customer = $customerLookup->get_result()->fetch_assoc();
            $customerLookup->close();
            if ($customer) {
                $customerId = (int)$customer['id'];
            }

            $conn->begin_transaction();
            $statement = $conn->prepare('INSERT INTO `car_service_requests` (`Vehicle_Model`,`Registration_Number`,`Service_Type`,`First_Name`,`Last_Name`,`E-Mail`,`Mobile_No`,`City`,`Preferred_Date`,`Preferred_Time`,`Notes`,`Customer_ID`) VALUES (?,?,?,?,?,?,?,?,?,?,?,NULLIF(?,0))');
            $statement->bind_param('sssssssssssi', $vehicleModel, $registration, $serviceType, $firstName, $lastName, $email, $mobile, $city, $preferredDate, $preferredTime, $notes, $customerId);
            $statement->execute();
            $requestId = (int)$statement->insert_id;
            $statement->close();

            app_notify_admins(
                $conn,
                'service_requests',
                'NEW_CAR_SERVICE_REQUEST',
                'New Car Service Request',
                "{$firstName} {$lastName} requested {$serviceType} for {$vehicleModel} ({$registration}).",
                $requestId,
                'car_service',
                'new-car-service:' . $requestId
            );
            $conn->commit();
            header('Location: carservice.php?status=success');
            exit;
        } catch (Throwable $error) {
            if (isset($conn) && $conn instanceof mysqli) {
                try {
                    $conn->rollback();
                } catch (Throwable $rollbackError) {
                    error_log('BMW car-service request transaction rollback failed: ' . $rollbackError->getMessage());
                }
            }
            error_log('BMW car-service request could not be saved: ' . $error->getMessage());
            $formError = 'We could not save your service request. Please check that MySQL is running and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request Car Service | Beamers Legacy</title>
  <meta name="description" content="Request BMW maintenance, repair or vehicle service at Beamers Legacy.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1">
  <style>
    .service-page{min-height:100vh;padding:160px 0 100px;background:var(--bg-primary)}
    .service-holder{width:min(90%,820px);margin:auto}.service-eyebrow{margin:0 0 16px;color:var(--bmw-blue);font-size:.75rem;font-weight:700;letter-spacing:.3em;text-transform:uppercase}.service-holder h1{margin:0 0 12px;font-size:clamp(2rem,4vw,3rem);letter-spacing:-.02em}.service-holder>p{max-width:62ch;margin:0 0 40px;color:var(--text-muted)}
    .service-card{padding:clamp(22px,4vw,38px);border:1px solid var(--border-color);background:var(--bg-secondary);box-shadow:0 18px 50px rgba(0,0,0,.12)}.section-title{margin:34px 0 18px;padding-top:26px;border-top:1px solid var(--border-color);color:var(--text-primary);font-size:.78rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase}.section-title:first-of-type{margin-top:0;padding-top:0;border-top:0}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 24px}.service-holder select{color-scheme:dark}.service-holder select option{background:#111;color:#fff}.service-holder textarea{min-height:110px;resize:vertical}.notice{margin:0 0 24px;padding:14px 18px;border:1px solid var(--border-color);background:var(--bg-secondary);color:var(--text-secondary);font-size:.9rem}.notice.success{border-color:#2d7a4d}.notice.error{border-color:#8a3d3d;color:#ffb4b4}.checkbox-line{display:flex;align-items:flex-start;gap:10px;margin:10px 0 26px;color:var(--text-muted);font-size:.85rem}.checkbox-line input{margin-top:3px;accent-color:var(--bmw-blue)}.checkbox-line a{color:var(--bmw-blue)}.service-footer{margin-top:26px;text-align:center;color:var(--text-dark);font-size:.78rem}.service-footer a{color:var(--bmw-blue)}
    @media(max-width:700px){.service-page{padding:130px 0 70px}.form-grid{grid-template-columns:1fr;gap:0}.service-card{padding:22px}}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="service-page">
  <div class="service-holder">
    <p class="service-eyebrow">Beamers Legacy / After-sales care</p>
    <h1>Request car service</h1>
    <p>Tell us what your BMW needs and choose a convenient preferred time. Our service team will confirm your appointment shortly.</p>
    <?php if ($status === 'success'): ?><div class="notice success" role="status">Your car-service request was submitted successfully. We will contact you shortly to confirm the appointment.</div><?php endif; ?>
    <?php if ($formError !== ''): ?><div class="notice error" role="alert"><?= e($formError) ?></div><?php endif; ?>
    <form class="service-card" method="post" action="carservice.php">
      <input type="hidden" name="csrf" value="<?= e(app_csrf_token()) ?>">
      <div class="section-title">Your BMW</div>
      <div class="form-grid">
        <div class="form-group-premium"><label for="vehicle-model">BMW model*</label><input id="vehicle-model" name="Vehicle_Model" type="text" maxlength="120" value="<?= e(car_service_form_value('Vehicle_Model')) ?>" placeholder="e.g. X5 xDrive40i" required></div>
        <div class="form-group-premium"><label for="registration">Registration number*</label><input id="registration" name="Registration_Number" type="text" maxlength="20" value="<?= e(car_service_form_value('Registration_Number')) ?>" pattern="[A-Za-z0-9-]{4,20}" placeholder="e.g. GJ01AB1234" required></div>
      </div>
      <div class="form-grid">
        <div class="form-group-premium"><label for="service-type">Service needed*</label><select id="service-type" name="Service_Type" required><option value="">Select service</option><?php foreach ($serviceTypes as $type): ?><option value="<?= e($type) ?>" <?= car_service_form_value('Service_Type') === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></div>
        <div class="form-group-premium"><label for="city">Preferred service city*</label><select id="city" name="City" required><option value="">Select city</option><?php foreach ($allowedCities as $city): ?><option value="<?= e($city) ?>" <?= car_service_form_value('City') === $city ? 'selected' : '' ?>><?= e($city) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="form-grid">
        <div class="form-group-premium"><label for="preferred-date">Preferred date*</label><input id="preferred-date" name="Preferred_Date" type="date" min="<?= e(date('Y-m-d')) ?>" value="<?= e(car_service_form_value('Preferred_Date')) ?>" required></div>
        <div class="form-group-premium"><label for="preferred-time">Preferred time*</label><input id="preferred-time" name="Preferred_Time" type="time" value="<?= e(car_service_form_value('Preferred_Time')) ?>" required></div>
      </div>
      <div class="form-group-premium"><label for="notes">Tell us about the issue <small>(optional)</small></label><textarea id="notes" name="Notes" maxlength="1000" placeholder="For example: warning light, unusual noise, or service due date."><?= e(car_service_form_value('Notes')) ?></textarea></div>
      <div class="section-title">Contact details</div>
      <div class="form-grid">
        <div class="form-group-premium"><label for="first-name">First name*</label><input id="first-name" name="First_Name" type="text" maxlength="100" value="<?= e(car_service_form_value('First_Name', $prefillFirst)) ?>" required></div>
        <div class="form-group-premium"><label for="last-name">Last name*</label><input id="last-name" name="Last_Name" type="text" maxlength="100" value="<?= e(car_service_form_value('Last_Name', $prefillLast)) ?>" required></div>
      </div>
      <div class="form-grid">
        <div class="form-group-premium"><label for="email">Email*</label><input id="email" name="E-Mail" type="email" value="<?= e(car_service_form_value('E-Mail', $prefillEmail)) ?>" required></div>
        <div class="form-group-premium"><label for="mobile">Mobile number*</label><input id="mobile" name="Mobile_No" type="tel" pattern="[0-9]{10}" value="<?= e(car_service_form_value('Mobile_No')) ?>" placeholder="10-digit number" required></div>
      </div>
      <label class="checkbox-line"><input type="checkbox" name="Privacy_Consent" value="yes" <?= car_service_form_value('Privacy_Consent') === 'yes' ? 'checked' : '' ?> required><span>I agree that Beamers Legacy may use these details to arrange my service appointment.</span></label>
      <button class="btn-primary" type="submit">Request service appointment <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
    </form>
    <p class="service-footer">Looking to experience a new BMW? <a href="testdrive.php">Book a test drive</a>.</p>
  </div>
</main>
<script src="../js/premium.js"></script>
</body>
</html>
