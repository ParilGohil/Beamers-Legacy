<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !app_verify_csrf()) {
    http_response_code(419);
    exit('Your session expired. Please go back and submit the form again.');
}
$prefillFirst = (string)($_SESSION['first_name'] ?? '');
$prefillLast = (string)($_SESSION['last_name'] ?? '');
$prefillEmail = (string)($_SESSION['email'] ?? '');
$statusInput = $_GET['status'] ?? '';
$status = is_string($statusInput) ? $statusInput : '';
$formError = '';

// The full BMW lineup, matching the canonical series list already used by
// the admin "cars" inventory (php/admin/bootstrap.php) so this form never
// falls out of sync with the rest of the site.
$allowedSeries = ['1 Series', '2 Series', '3 Series', '4 Series', '5 Series', '7 Series', 'X1', 'X3', 'X5', 'X7', 'XM', 'i4', 'i5', 'i7', 'iX', 'i Series', 'X Series', 'Z4', 'M Series'];
$allowedCities = ['Ahmedabad', 'Mumbai', 'Delhi', 'Bangalore', 'Chennai'];
$requestTypeInput = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['Request_Type'] ?? 'test_drive') : ($_GET['inquiry'] ?? 'test_drive');
$requestType = is_string($requestTypeInput) ? $requestTypeInput : 'test_drive';
if (!in_array($requestType, ['test_drive', 'offer'], true)) {
    $requestType = 'test_drive';
}
$selectedCarId = 0;
$selectedCar = null;
$invalidCarSelection = false;
$rawCarId = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['Car_ID'] ?? '') : ($_GET['car_id'] ?? '');
if (is_scalar($rawCarId) && (string)$rawCarId !== '' && ctype_digit((string)$rawCarId) && (int)$rawCarId > 0) {
    $selectedCarId = (int)$rawCarId;
    try {
        $carConn = db();
        app_ensure_car_catalog_schema($carConn);
        $carLookup = $carConn->prepare("SELECT `id`, `name`, `series` FROM `cars` WHERE `id` = ? AND `status` = 'active' LIMIT 1");
        $carLookup->bind_param('i', $selectedCarId);
        $carLookup->execute();
        $selectedCar = $carLookup->get_result()->fetch_assoc() ?: null;
        $carLookup->close();
        if ($selectedCar) {
            $carSeries = preg_replace('/^BMW\s+/i', '', trim((string)$selectedCar['series'])) ?? '';
            if ($carSeries !== '' && !in_array($carSeries, $allowedSeries, true)) {
                $allowedSeries[] = $carSeries;
            }
        }
    } catch (Throwable $error) {
        error_log('BMW test-drive vehicle could not be loaded: ' . $error->getMessage());
        $formError = 'The selected vehicle could not be loaded. Please try again later.';
        $selectedCarId = 0;
        $invalidCarSelection = true;
    }
} elseif ($rawCarId !== '' && $rawCarId !== null && $rawCarId !== '0' && $rawCarId !== 0) {
    $formError = 'The selected vehicle is invalid.';
    $invalidCarSelection = true;
}

// A visitor arriving from the configurator (?preset_config=Model:+BMW+M4...)
// should not have to re-enter what they already chose. We sanitize and
// carry the raw string through as "Configuration" for the dealer/admin,
// and try to pre-select a sensible Series/Model from it.
$presetInput = $_GET['preset_config'] ?? '';
$presetConfig = is_string($presetInput) ? trim($presetInput) : '';
$presetConfig = $presetConfig !== '' ? mb_substr(strip_tags($presetConfig), 0, 255) : '';
$prefillSeries = '';
$prefillModel = '';
if ($presetConfig !== '' && preg_match('/Model:\s*([^,]+)/i', $presetConfig, $modelMatch)) {
    $prefillModel = trim($modelMatch[1]);
    $modelUpper = strtoupper($prefillModel);
    foreach (['M8', 'M4', 'M2', 'M3', 'M5'] as $mCode) {
        if (str_contains($modelUpper, $mCode)) { $prefillSeries = 'M Series'; break; }
    }
    if ($prefillSeries === '') {
        foreach ($allowedSeries as $seriesOption) {
            if (str_contains($modelUpper, strtoupper($seriesOption))) { $prefillSeries = $seriesOption; break; }
        }
    }
}
if ($selectedCar) {
    $prefillSeries = preg_replace('/^BMW\s+/i', '', trim((string)$selectedCar['series'])) ?? '';
    $prefillModel = (string)$selectedCar['name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $selectedCar ? $prefillSeries : trim((string)($_POST['Series'] ?? ''));
    $b = $selectedCar ? $prefillModel : trim((string)($_POST['Model'] ?? ''));
    $c = trim((string)($_POST['First_Name'] ?? ''));
    $d = trim((string)($_POST['Last_Name'] ?? ''));
    $eMail = strtolower(trim((string)($_POST['E-Mail'] ?? '')));
    $f = trim((string)($_POST['Mobile_No'] ?? ''));
    $g = trim((string)($_POST['City'] ?? ''));
    $h = trim((string)($_POST['Finance'] ?? 'no'));
    $configuration = trim((string)($_POST['Configuration'] ?? ''));
    $configuration = $configuration !== '' ? mb_substr(strip_tags($configuration), 0, 255) : null;
    $privacyConsent = (string)($_POST['Privacy_Consent'] ?? '');
    $appointmentDate = trim((string)($_POST['Appointment_Date'] ?? ''));
    $appointmentTime = trim((string)($_POST['Appointment_Time'] ?? ''));

    if ($invalidCarSelection || ($selectedCarId > 0 && !$selectedCar)) {
        $formError = 'The selected vehicle is no longer available.';
    } elseif (!in_array($a, $allowedSeries, true) || $b === '' || mb_strlen($b) > 100 || !in_array($g, $allowedCities, true) || !in_array($h, ['yes', 'no'], true)) {
        $formError = 'Please select a valid series, enter the model you want to drive, and choose a city.';
    } elseif ($c === '' || $d === '' || !filter_var($eMail, FILTER_VALIDATE_EMAIL) || !preg_match('/^[0-9]{10}$/', $f) || $privacyConsent !== 'yes') {
        $formError = 'Please complete all fields with a valid email address and 10-digit mobile number, and accept the privacy terms.';
    } elseif ($requestType === 'test_drive' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointmentDate) || $appointmentDate < date('Y-m-d') || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $appointmentTime))) {
        $formError = 'Choose a valid future date and time for your test drive.';
    } else {
        try {
            $conn = db();
            app_ensure_car_catalog_schema($conn);
            app_ensure_notification_schema($conn);
            $customerId = 0;
            $customerLookup = $conn->prepare('SELECT `id` FROM `register` WHERE LOWER(`E-mail`) = LOWER(?) LIMIT 1');
            $customerLookup->bind_param('s', $eMail);
            $customerLookup->execute();
            $customer = $customerLookup->get_result()->fetch_assoc();
            $customerLookup->close();
            if ($customer) {
                $customerId = (int)$customer['id'];
            }

            $conn->begin_transaction();
            $stmt = $conn->prepare("INSERT INTO `test drive` (`Series`, `Model`, `First_Name`, `Last_Name`, `E-Mail`, `Mobile_No`, `City`, `Finance`, `Configuration`, `Car_ID`, `Request_Type`, `Customer_ID`, `Appointment_Date`, `Appointment_Time`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, 0), ?, NULLIF(?, 0), NULLIF(?, ''), NULLIF(?, ''))");
            $stmt->bind_param('sssssssssisiss', $a, $b, $c, $d, $eMail, $f, $g, $h, $configuration, $selectedCarId, $requestType, $customerId, $appointmentDate, $appointmentTime);
            $stmt->execute();
            $requestId = (int)$stmt->insert_id;
            $stmt->close();

            app_notify_admins(
                $conn,
                'test_drives',
                'NEW_TEST_DRIVE',
                'New Test Drive Request',
                "{$c} {$d} requested a test drive for {$b}.",
                $requestId,
                'test_drive',
                'new-test-drive:' . $requestId
            );
            $conn->commit();
            header('Location: testdrive.php?status=success');
            exit;
        } catch (Throwable $error) {
            if (isset($conn) && $conn instanceof mysqli) {
                try {
                    $conn->rollback();
                } catch (Throwable $rollbackError) {
                    error_log('BMW enquiry transaction rollback failed: ' . $rollbackError->getMessage());
                }
            }
            error_log('BMW enquiry could not be saved: ' . $error->getMessage());
            $formError = 'We could not save your request. Please check that MySQL is running and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $requestType === 'offer' ? 'Request an Offer' : 'Book a Test Drive' ?> | Beamers Legacy</title>
  <meta name="description" content="Book a personal BMW test drive at Beamers Legacy. Choose your series, share your details, and our team will arrange your drive." />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1" />
  <style>
    /* Test Drive page — built entirely on the shared premium design system
       (var(--bg-*), var(--text-*), var(--bmw-blue), .form-group-premium,
       .glass-card, .btn-primary) rather than a bespoke theme, so it reads
       as part of the same product as the rest of the showroom. */
    .testdrive-page {
      padding: 160px 0 100px;
      min-height: 100vh;
      background-color: var(--bg-primary);
    }

    .testdrive-holder {
      width: 90%;
      max-width: 820px;
      margin: 0 auto;
    }

    .testdrive-eyebrow {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.3em;
      color: var(--bmw-blue);
      margin-bottom: 16px;
    }

    .testdrive-holder h1 {
      font-size: clamp(2rem, 4vw, 3rem);
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 12px;
    }

    .testdrive-holder > p {
      color: var(--text-muted);
      font-size: 1rem;
      margin-bottom: 40px;
      max-width: 60ch;
    }

    .config-summary-card {
      display: flex;
      align-items: center;
      gap: 14px;
      border: 1px solid var(--border-color);
      border-left: 3px solid var(--bmw-blue);
      background: rgba(28, 105, 212, 0.08);
      padding: 16px 20px;
      margin-bottom: 35px;
      font-size: 0.85rem;
      color: var(--text-secondary);
    }

    .config-summary-card i { color: var(--bmw-blue); font-size: 1.1rem; }

    .form-notice {
      max-width: 100%;
      margin: 0 0 30px;
      padding: 14px 18px;
      border: 1px solid var(--border-color);
      background: var(--bg-secondary);
      color: var(--text-secondary);
      font-size: 0.9rem;
    }
    .form-notice.success { border-color: #2d7a4d; }
    .form-notice.error { border-color: #8a3d3d; color: #ffb4b4; }

    .testdrive-section-title {
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.2em;
      color: var(--text-primary);
      margin: 40px 0 20px;
      padding-top: 30px;
      border-top: 1px solid var(--border-color);
    }
    .testdrive-holder form > .testdrive-section-title:first-of-type {
      margin-top: 0;
      padding-top: 0;
      border-top: none;
    }

    .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px 25px;
    }

    .testdrive-holder select {
      color-scheme: dark;
    }
    .testdrive-holder select option {
      color: var(--text-primary);
      background-color: #111;
    }

    .radio-group-premium {
      display: flex;
      gap: 30px;
      margin-bottom: 25px;
    }
    .radio-group-premium label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.9rem;
      color: var(--text-secondary);
      cursor: pointer;
    }
    .radio-group-premium input { accent-color: var(--bmw-blue); width: 16px; height: 16px; }

    .checkbox-line {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      font-size: 0.85rem;
      color: var(--text-muted);
      margin: 10px 0 30px;
    }
    .checkbox-line input { margin-top: 3px; accent-color: var(--bmw-blue); }
    .checkbox-line a { color: var(--bmw-blue); }

    .testdrive-holder footer {
      text-align: center;
      font-size: 0.75rem;
      color: var(--text-dark);
      margin-top: 30px;
    }

    @media (max-width: 700px) {
      .testdrive-page { padding: 130px 0 70px; }
      .form-grid-2 { grid-template-columns: 1fr; gap: 0; }
    }
  </style>
</head>
<body>

  <?php require_once __DIR__ . '/navbar.php'; ?>

  <main class="testdrive-page">
    <div class="testdrive-holder">
      <p class="testdrive-eyebrow">Beamers Legacy / <?= $requestType === 'offer' ? 'Vehicle Offer' : 'Test Drive' ?></p>
      <h1><?= $requestType === 'offer' ? 'Request an offer' : 'Which BMW would you like to drive?' ?></h1>
      <p><?= $requestType === 'offer' ? 'Share your details and our team will contact you with an offer for your selected BMW.' : 'Complete your details below and your BMW Dealer will be in touch shortly to arrange a test drive.' ?></p>

      <?php if ($status === 'success'): ?><div class="form-notice success">Your request was submitted successfully. Our team will contact you shortly.</div><?php endif; ?>
      <?php if ($formError !== ''): ?><div class="form-notice error"><?= e($formError) ?></div><?php endif; ?>

      <?php if ($presetConfig !== ''): ?>
        <div class="config-summary-card">
          <i class="fa-solid fa-sliders" aria-hidden="true"></i>
          <span>Carried over from the configurator: <strong><?= e($presetConfig) ?></strong></span>
        </div>
      <?php endif; ?>
      <?php if ($selectedCar): ?>
        <div class="config-summary-card">
          <i class="fa-solid fa-car-side" aria-hidden="true"></i>
          <span>Selected BMW: <strong><?= e((string)$selectedCar['name']) ?></strong></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="testdrive.php">
        <input type="hidden" name="csrf" value="<?= e(app_csrf_token()) ?>">
        <input type="hidden" name="Configuration" value="<?= e($presetConfig) ?>">
        <input type="hidden" name="Car_ID" value="<?= $selectedCar ? (int)$selectedCar['id'] : 0 ?>">
        <input type="hidden" name="Request_Type" value="<?= e($requestType) ?>">

        <div class="testdrive-section-title">Vehicle</div>
        <div class="form-grid-2">
          <div class="form-group-premium">
            <label for="series">Series*</label>
            <select id="series" name="Series" required>
              <option value="">Select Series</option>
              <?php foreach ($allowedSeries as $seriesOption): ?>
                <option value="<?= e($seriesOption) ?>" <?= $prefillSeries === $seriesOption ? 'selected' : '' ?>>BMW <?= e($seriesOption) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group-premium">
            <label for="model">Model*</label>
            <input id="model" type="text" name="Model" value="<?= e($prefillModel) ?>" placeholder="e.g. M4 Competition, 530i, X5 xDrive50" maxlength="100" required>
          </div>
        </div>
        <?php if ($requestType === 'test_drive'): ?>
          <div class="form-grid-2">
            <div class="form-group-premium">
              <label for="appointment-date">Preferred Date*</label>
              <input id="appointment-date" type="date" name="Appointment_Date" min="<?= e(date('Y-m-d')) ?>" required>
            </div>
            <div class="form-group-premium">
              <label for="appointment-time">Preferred Time*</label>
              <input id="appointment-time" type="time" name="Appointment_Time" required>
            </div>
          </div>
        <?php endif; ?>

        <div class="testdrive-section-title">Contact details</div>
        <div class="form-grid-2">
          <div class="form-group-premium">
            <label for="fname">First Name*</label>
            <input id="fname" type="text" name="First_Name" value="<?= e($prefillFirst) ?>" required>
          </div>
          <div class="form-group-premium">
            <label for="lname">Last Name*</label>
            <input id="lname" type="text" name="Last_Name" value="<?= e($prefillLast) ?>" required>
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group-premium">
            <label for="email">Email*</label>
            <input id="email" type="email" name="E-Mail" value="<?= e($prefillEmail) ?>" required>
          </div>
          <div class="form-group-premium">
            <label for="mobile">Mobile Number*</label>
            <input id="mobile" type="tel" name="Mobile_No" required pattern="[0-9]{10}" placeholder="10-digit number">
          </div>
        </div>
        <div class="form-group-premium">
          <label for="city">Current City*</label>
          <select id="city" name="City" required>
            <option value="">Select City</option>
            <?php foreach ($allowedCities as $cityOption): ?>
              <option><?= e($cityOption) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group-premium">
          <label>Are you interested in Financing?</label>
          <div class="radio-group-premium">
            <label><input type="radio" name="Finance" value="yes"> Yes</label>
            <label><input type="radio" name="Finance" value="no" checked> Not Interested</label>
          </div>
        </div>

        <div class="checkbox-line">
          <input type="checkbox" id="terms" name="Privacy_Consent" value="yes" required>
          <label for="terms">I accept the <a href="terms.php">Terms and Conditions</a>.</label>
        </div>

        <button type="submit" class="btn-primary"><?= $requestType === 'offer' ? 'Request Offer' : 'Submit Request' ?></button>
      </form>

      <footer>
        <p>The data provided will be used for the purpose of fulfilling your request. Further information can be found in our Data Privacy Policy.</p>
        <p>&copy; Beamers Legacy 2025</p>
      </footer>
    </div>
  </main>

<script src="../js/premium.js"></script>
</body>
</html>
