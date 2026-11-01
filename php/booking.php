<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

start_app_session();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !app_verify_csrf()) {
    http_response_code(419);
    exit('Your session expired. Please go back and submit the form again.');
}

$allowedCities = ['Ahmedabad', 'Mumbai', 'Delhi', 'Bangalore', 'Chennai'];
$paymentMethods = ['finance' => 'Finance / EMI', 'cash' => 'Full payment'];
$statusInput = $_GET['status'] ?? '';
$status = is_string($statusInput) ? $statusInput : '';
$formError = '';
$availableCars = [];
$selectedCarIdInput = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['Car_ID'] ?? '') : ($_GET['car_id'] ?? '');
$selectedCarId = is_scalar($selectedCarIdInput) && ctype_digit((string)$selectedCarIdInput) ? (int)$selectedCarIdInput : 0;
$selectedCar = null;
$prefillFirst = (string)($_SESSION['first_name'] ?? '');
$prefillLast = (string)($_SESSION['last_name'] ?? '');
$prefillEmail = (string)($_SESSION['email'] ?? '');

function booking_form_value(string $key, string $default = ''): string
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$key]) && is_scalar($_POST[$key])) {
        return trim((string)$_POST[$key]);
    }
    return $default;
}

try {
    $conn = db();
    app_ensure_car_catalog_schema($conn);
    app_ensure_sales_booking_schema($conn);
    app_ensure_notification_schema($conn);
    $availableCars = $conn->query("SELECT `id`,`name`,`series`,`price` FROM `cars` WHERE `status` = 'active' ORDER BY `series`,`name`")->fetch_all(MYSQLI_ASSOC);
    if ($selectedCarId > 0) {
        $carLookup = $conn->prepare("SELECT `id`,`name`,`series`,`price` FROM `cars` WHERE `id` = ? AND `status` = 'active' LIMIT 1");
        $carLookup->bind_param('i', $selectedCarId);
        $carLookup->execute();
        $selectedCar = $carLookup->get_result()->fetch_assoc() ?: null;
        $carLookup->close();
    }
} catch (Throwable $error) {
    error_log('BMW booking page could not load the vehicle catalogue: ' . $error->getMessage());
    $formError = 'The vehicle catalogue could not be loaded. Please try again later.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = mb_substr(trim(strip_tags((string)($_POST['First_Name'] ?? ''))), 0, 100);
    $lastName = mb_substr(trim(strip_tags((string)($_POST['Last_Name'] ?? ''))), 0, 100);
    $email = strtolower(trim((string)($_POST['E-Mail'] ?? '')));
    $mobile = trim((string)($_POST['Mobile_No'] ?? ''));
    $city = trim((string)($_POST['City'] ?? ''));
    $paymentMethod = trim((string)($_POST['Payment_Method'] ?? ''));
    $tradeIn = trim((string)($_POST['Trade_In'] ?? 'no'));
    $notes = mb_substr(trim(strip_tags((string)($_POST['Notes'] ?? ''))), 0, 1000);
    $privacyConsent = (string)($_POST['Privacy_Consent'] ?? '');

    if (!$selectedCar) {
        $formError = 'Choose an available BMW before submitting your booking request.';
    } elseif ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[0-9]{10}$/', $mobile) || !in_array($city, $allowedCities, true)) {
        $formError = 'Please complete all contact fields with a valid email address and 10-digit mobile number.';
    } elseif (!array_key_exists($paymentMethod, $paymentMethods) || !in_array($tradeIn, ['yes', 'no'], true) || $privacyConsent !== 'yes') {
        $formError = 'Choose your payment preference and accept the privacy terms.';
    } elseif ($formError === '') {
        try {
            $customerId = 0;
            $customerLookup = $conn->prepare('SELECT `id` FROM `register` WHERE LOWER(`E-mail`) = LOWER(?) LIMIT 1');
            $customerLookup->bind_param('s', $email);
            $customerLookup->execute();
            $customer = $customerLookup->get_result()->fetch_assoc();
            $customerLookup->close();
            if ($customer) {
                $customerId = (int)$customer['id'];
            }

            $vehicleName = (string)$selectedCar['name'];
            $vehiclePrice = trim((string)($selectedCar['price'] ?? ''));
            $conn->begin_transaction();
            $statement = $conn->prepare('INSERT INTO `car_bookings` (`Car_ID`,`Vehicle_Name`,`Vehicle_Price`,`First_Name`,`Last_Name`,`E-Mail`,`Mobile_No`,`City`,`Payment_Method`,`Trade_In`,`Notes`,`Customer_ID`) VALUES (?,?,?,?,?,?,?,?,?,?,?,NULLIF(?,0))');
            $types = 'i' . str_repeat('s', 10) . 'i';
            $statement->bind_param($types, $selectedCarId, $vehicleName, $vehiclePrice, $firstName, $lastName, $email, $mobile, $city, $paymentMethod, $tradeIn, $notes, $customerId);
            $statement->execute();
            $bookingId = (int)$statement->insert_id;
            $statement->close();
            app_notify_admins($conn, 'sales', 'NEW_CAR_BOOKING', 'New BMW Booking Request', "{$firstName} {$lastName} wants to reserve {$vehicleName}.", $bookingId, 'sales_booking', 'new-sales-booking:' . $bookingId);
            $conn->commit();
            header('Location: booking.php?car_id=' . $selectedCarId . '&status=success');
            exit;
        } catch (Throwable $error) {
            if (isset($conn) && $conn instanceof mysqli) {
                try { $conn->rollback(); } catch (Throwable $rollbackError) { error_log('BMW booking rollback failed: ' . $rollbackError->getMessage()); }
            }
            error_log('BMW booking could not be saved: ' . $error->getMessage());
            $formError = 'We could not save your booking request. Please check that MySQL is running and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reserve Your BMW | Beamers Legacy</title>
  <meta name="description" content="Send a BMW purchase and reservation request to the Beamers Legacy sales team.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1">
  <style>
    .booking-page{min-height:100vh;padding:160px 0 100px;background:var(--bg-primary)}.booking-holder{width:min(90%,820px);margin:auto}.booking-eyebrow{margin:0 0 16px;color:var(--bmw-blue);font-size:.75rem;font-weight:700;letter-spacing:.3em;text-transform:uppercase}.booking-holder h1{margin:0 0 12px;font-size:clamp(2rem,4vw,3rem);letter-spacing:-.02em}.booking-holder>p{max-width:64ch;margin:0 0 40px;color:var(--text-muted)}.booking-card{padding:clamp(22px,4vw,38px);border:1px solid var(--border-color);background:var(--bg-secondary);box-shadow:0 18px 50px rgba(0,0,0,.12)}.selected-vehicle{display:flex;align-items:center;gap:12px;margin:0 0 28px;padding:16px 18px;border-left:3px solid var(--bmw-blue);background:rgba(28,105,212,.08);color:var(--text-secondary)}.selected-vehicle i{color:var(--bmw-blue)}.section-title{margin:34px 0 18px;padding-top:26px;border-top:1px solid var(--border-color);color:var(--text-primary);font-size:.78rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase}.section-title:first-of-type{margin-top:0;padding-top:0;border-top:0}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 24px}.booking-holder select{color-scheme:dark}.booking-holder select option{background:#111;color:#fff}.booking-holder textarea{min-height:105px;resize:vertical}.notice{margin:0 0 24px;padding:14px 18px;border:1px solid var(--border-color);background:var(--bg-secondary);color:var(--text-secondary);font-size:.9rem}.notice.success{border-color:#2d7a4d}.notice.error{border-color:#8a3d3d;color:#ffb4b4}.radio-row{display:flex;gap:22px;flex-wrap:wrap;margin:4px 0 24px}.radio-row label,.checkbox-line{display:flex;align-items:flex-start;gap:9px;color:var(--text-muted);font-size:.88rem}.radio-row input,.checkbox-line input{margin-top:3px;accent-color:var(--bmw-blue)}.checkbox-line{margin:8px 0 25px}.booking-footer{margin-top:26px;text-align:center;color:var(--text-dark);font-size:.78rem}@media(max-width:700px){.booking-page{padding:130px 0 70px}.form-grid{grid-template-columns:1fr;gap:0}.booking-card{padding:22px}}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="booking-page"><div class="booking-holder">
  <p class="booking-eyebrow">Beamers Legacy / Sales</p>
  <h1>Reserve your BMW</h1>
  <p>Submit a booking request and a sales advisor will confirm availability, explain the next steps, and help you complete your purchase.</p>
  <?php if ($status === 'success'): ?><div class="notice success" role="status">Your BMW booking request was received. A sales advisor will contact you shortly.</div><?php endif; ?>
  <?php if ($formError !== ''): ?><div class="notice error" role="alert"><?= e($formError) ?></div><?php endif; ?>
  <form class="booking-card" method="post" action="booking.php">
    <input type="hidden" name="csrf" value="<?= e(app_csrf_token()) ?>">
    <?php if ($selectedCar): ?><div class="selected-vehicle"><i class="fa-solid fa-car-side" aria-hidden="true"></i><span>Booking request for <strong><?= e((string)$selectedCar['name']) ?></strong><?= !empty($selectedCar['price']) ? ' · ' . e((string)$selectedCar['price']) : '' ?></span></div><?php endif; ?>
    <div class="section-title">Choose your BMW</div>
    <div class="form-group-premium"><label for="car">BMW model*</label><select id="car" name="Car_ID" required><option value="">Select an available BMW</option><?php foreach ($availableCars as $car): ?><option value="<?= (int)$car['id'] ?>" <?= $selectedCarId === (int)$car['id'] ? 'selected' : '' ?>><?= e((string)$car['name']) ?><?= !empty($car['price']) ? ' — ' . e((string)$car['price']) : '' ?></option><?php endforeach; ?></select></div>
    <div class="section-title">Purchase details</div>
    <div class="form-group-premium"><label>How would you like to purchase?*</label><div class="radio-row"><?php foreach ($paymentMethods as $value => $label): ?><label><input type="radio" name="Payment_Method" value="<?= e($value) ?>" <?= booking_form_value('Payment_Method', 'finance') === $value ? 'checked' : '' ?> required> <?= e($label) ?></label><?php endforeach; ?></div></div>
    <div class="form-group-premium"><label>Do you have a car to trade in?</label><div class="radio-row"><label><input type="radio" name="Trade_In" value="yes" <?= booking_form_value('Trade_In', 'no') === 'yes' ? 'checked' : '' ?>> Yes</label><label><input type="radio" name="Trade_In" value="no" <?= booking_form_value('Trade_In', 'no') === 'no' ? 'checked' : '' ?>> No</label></div></div>
    <div class="form-group-premium"><label for="notes">Notes for your sales advisor <small>(optional)</small></label><textarea id="notes" name="Notes" maxlength="1000" placeholder="Preferred colour, variant, or any question about the booking."><?= e(booking_form_value('Notes')) ?></textarea></div>
    <div class="section-title">Contact details</div>
    <div class="form-grid"><div class="form-group-premium"><label for="first-name">First name*</label><input id="first-name" name="First_Name" maxlength="100" value="<?= e(booking_form_value('First_Name', $prefillFirst)) ?>" required></div><div class="form-group-premium"><label for="last-name">Last name*</label><input id="last-name" name="Last_Name" maxlength="100" value="<?= e(booking_form_value('Last_Name', $prefillLast)) ?>" required></div></div>
    <div class="form-grid"><div class="form-group-premium"><label for="email">Email*</label><input id="email" name="E-Mail" type="email" value="<?= e(booking_form_value('E-Mail', $prefillEmail)) ?>" required></div><div class="form-group-premium"><label for="mobile">Mobile number*</label><input id="mobile" name="Mobile_No" type="tel" pattern="[0-9]{10}" value="<?= e(booking_form_value('Mobile_No')) ?>" placeholder="10-digit number" required></div></div>
    <div class="form-group-premium"><label for="city">City*</label><select id="city" name="City" required><option value="">Select city</option><?php foreach ($allowedCities as $city): ?><option value="<?= e($city) ?>" <?= booking_form_value('City') === $city ? 'selected' : '' ?>><?= e($city) ?></option><?php endforeach; ?></select></div>
    <label class="checkbox-line"><input type="checkbox" name="Privacy_Consent" value="yes" <?= booking_form_value('Privacy_Consent') === 'yes' ? 'checked' : '' ?> required><span>I agree that Beamers Legacy may contact me about this BMW purchase and booking request.</span></label>
    <button class="btn-primary" type="submit">Submit booking request <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
  </form>
  <p class="booking-footer">A booking request does not create a purchase contract or confirm vehicle availability until the sales team contacts you.</p>
</div></main>
<script src="../js/premium.js"></script>
</body>
</html>
