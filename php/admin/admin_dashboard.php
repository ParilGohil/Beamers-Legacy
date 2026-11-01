<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

try {
    admin_ensure_schema($conn);
} catch (Throwable $error) {
    http_response_code(500);
    exit('The dashboard could not prepare its database tables. Please check the MySQL connection and permissions.');
}

admin_require_login();
$entities = admin_entities();
$section = (string) ($_GET['section'] ?? 'overview');
if ($section !== 'overview' && !isset($entities[$section])) {
    $section = 'overview';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entityKey = (string) ($_POST['entity'] ?? '');
    $returnSection = isset($entities[$entityKey]) ? $entityKey : 'overview';

    try {
        if (!admin_verify_csrf()) {
            throw new RuntimeException('Your session token expired. Refresh the page and try again.');
        }

        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'testdrive_status') {
            app_update_testdrive_status(
                $conn,
                (int)($_POST['id'] ?? 0),
                (string)($_POST['status'] ?? ''),
                trim((string)($_POST['appointment_date'] ?? '')),
                trim((string)($_POST['appointment_time'] ?? ''))
            );
            admin_flash('success', 'Test-drive status updated and the customer was notified.');
            header('Location: admin_dashboard.php?section=test-drives');
            exit;
        } elseif ($action === 'carservice_status') {
            app_update_car_service_status(
                $conn,
                (int)($_POST['id'] ?? 0),
                (string)($_POST['status'] ?? ''),
                trim((string)($_POST['appointment_date'] ?? '')),
                trim((string)($_POST['appointment_time'] ?? ''))
            );
            admin_flash('success', 'Car-service status updated and the customer was notified.');
            header('Location: admin_dashboard.php?section=car-services');
            exit;
        } elseif ($action === 'sales_status') {
            app_update_sales_booking_status(
                $conn,
                (int)($_POST['id'] ?? 0),
                (string)($_POST['status'] ?? '')
            );
            admin_flash('success', 'Sales-booking status updated and the customer was notified.');
            header('Location: admin_dashboard.php?section=sales');
            exit;
        } elseif ($action === 'create') {
            admin_create_entity($conn, $entityKey, $_POST, $_FILES);
            admin_flash('success', ucfirst($entities[$entityKey]['singular']) . ' created successfully.');
        } elseif ($action === 'update') {
            admin_update_entity($conn, $entityKey, (int) ($_POST['id'] ?? 0), $_POST, $_FILES);
            admin_flash('success', ucfirst($entities[$entityKey]['singular']) . ' updated successfully.');
        } elseif ($action === 'delete') {
            admin_delete_entity($conn, $entityKey, (int) ($_POST['id'] ?? 0));
            admin_flash('success', ucfirst($entities[$entityKey]['singular']) . ' deleted successfully.');
        } else {
            throw new InvalidArgumentException('Unsupported dashboard action.');
        }
    } catch (Throwable $error) {
        admin_flash('error', $error->getMessage());
    }

    header('Location: admin_dashboard.php?section=' . rawurlencode($returnSection));
    exit;
}

function admin_field_html(string $prefix, string $key, array $field, bool $editing = false): string
{
    $id = $prefix . '-' . $key;
    $required = !empty($field['required']) ? ' required' : '';
    $label = admin_escape($field['label']);
    $name = admin_escape($key);
    $type = $field['type'];

    $html = '';
    if (!empty($field['group'])) {
        $html .= '<div class="form-section-banner"><strong><i class="fa-solid fa-layer-group"></i> ' . admin_escape((string)$field['group']) . '</strong></div>';
    }

    $html .= '<div class="field field-' . admin_escape($type) . '">';
    $html .= '<label for="' . admin_escape($id) . '">' . $label;
    if (!empty($field['required'])) {
        $html .= ' <span aria-hidden="true">*</span>';
    }
    $html .= '</label>';

    if ($type === 'textarea') {
        $html .= '<textarea id="' . admin_escape($id) . '" name="' . $name . '" rows="4"' . $required . '></textarea>';
        if ($key === 'gallery') {
            $html .= '<label for="' . admin_escape($prefix . '-gallery-uploads') . '">Upload gallery images</label>';
            $html .= '<input id="' . admin_escape($prefix . '-gallery-uploads') . '" name="gallery_uploads[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple>';
            $html .= '<small>Choose up to 10 JPG, PNG, WEBP or GIF images (5 MB each). Uploaded images are added to the paths above.</small>';
        }
    } elseif ($type === 'file') {
        $accept = !empty($field['accept']) ? ' accept="' . admin_escape($field['accept']) . '"' : '';
        $html .= '<input id="' . admin_escape($id) . '" name="' . $name . '" type="file"' . $accept . $required . '>';
        if (!empty($field['help'])) {
            $html .= '<small>' . admin_escape($field['help']) . '</small>';
        }
    } elseif ($type === 'select') {
        $html .= '<select id="' . admin_escape($id) . '" name="' . $name . '"' . $required . '>';
        $html .= '<option value="">Select ' . $label . '</option>';
        foreach ($field['options'] as $value => $optionLabel) {
            if (is_int($value)) {
                $value = (string) $optionLabel;
            }
            $html .= '<option value="' . admin_escape((string) $value) . '">' . admin_escape((string) $optionLabel) . '</option>';
        }
        $html .= '</select>';
    } else {
        $placeholder = !empty($field['placeholder']) ? ' placeholder="' . admin_escape($field['placeholder']) . '"' : '';
        $html .= '<input id="' . admin_escape($id) . '" name="' . $name . '" type="' . admin_escape($type) . '"' . $placeholder . $required . '>';
    }

    $html .= '</div>';
    return $html;
}

function admin_record_payload(array $entity, array $record): string
{
    $payload = ['id' => (int) $record['id']];
    foreach ($entity['fields'] as $key => $field) {
        $payload[$key] = $record[$field['column']] ?? '';
    }
    return admin_escape((string) json_encode($payload, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
}

function admin_table_value(array $field, array $record): string
{
    $value = (string) ($record[$field['column']] ?? '');
    if ($field['type'] === 'textarea' && strlen($value) > 95) {
        $value = substr($value, 0, 95) . '…';
    }
    if ($field['column'] === 'rating' && $value !== '') {
        $rating = max(0, min(5, (int) $value));
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }
    if ($field['type'] === 'file') {
        if ($value === '') {
            return '<span class="muted-value">No image</span>';
        }
        return admin_escape(basename(str_replace('\\', '/', $value)));
    }
    return admin_escape($value === '' ? '—' : $value);
}

$flash = admin_get_flash();
$counts = [];
foreach (array_keys($entities) as $entityKey) {
    $counts[$entityKey] = admin_count($conn, $entityKey);
}

$feedbackAverage = 0;
$ratingResult = $conn->query('SELECT AVG(`rating`) AS average_rating FROM `feedback` WHERE `rating` IS NOT NULL');
if ($rating = $ratingResult->fetch_assoc()) {
    $feedbackAverage = $rating['average_rating'] !== null ? (float) $rating['average_rating'] : 0;
}

$activeEntity = $section === 'overview' ? null : $entities[$section];
$records = $activeEntity ? admin_records($conn, $section) : [];
$isAppointmentRequestSection = in_array($section, ['test-drives', 'car-services'], true);
$isSalesSection = $section === 'sales';
$recentDrives = admin_records($conn, 'test-drives', 5);
$recentSales = admin_records($conn, 'sales', 5);
$csrf = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin Dashboard | Beamers Legacy</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="../../css/admin.css?v=20260926-futuristic-6">
</head>
<body>
  <div class="admin-shell">
    <aside class="sidebar" id="sidebar">
      <a class="brand" href="admin_dashboard.php" aria-label="Beamers Legacy admin home">
        <img src="../../images/BMW logo.svg" alt="BMW logo">
        <span><strong>BEAMERS</strong><small>LEGACY / ADMIN</small></span>
      </a>

      <nav class="sidebar-nav" aria-label="Dashboard sections">
        <a class="<?= $section === 'overview' ? 'active' : '' ?>" href="admin_dashboard.php"><i class="fa-solid fa-grid-2"></i>Overview</a>
        <a class="<?= $section === 'customers' ? 'active' : '' ?>" href="admin_dashboard.php?section=customers"><i class="fa-solid fa-users"></i>Customers <b><?= $counts['customers'] ?></b></a>
        <a class="<?= $section === 'test-drives' ? 'active' : '' ?>" href="admin_dashboard.php?section=test-drives"><i class="fa-solid fa-car-side"></i>Test drives <b><?= $counts['test-drives'] ?></b></a>
        <a class="<?= $section === 'car-services' ? 'active' : '' ?>" href="admin_dashboard.php?section=car-services"><i class="fa-solid fa-screwdriver-wrench"></i>Car service <b><?= $counts['car-services'] ?></b></a>
        <a class="<?= $section === 'sales' ? 'active' : '' ?>" href="admin_dashboard.php?section=sales"><i class="fa-solid fa-handshake"></i>Sales bookings <b><?= $counts['sales'] ?></b></a>
        <a class="<?= $section === 'feedback' ? 'active' : '' ?>" href="admin_dashboard.php?section=feedback"><i class="fa-solid fa-message"></i>Feedback <b><?= $counts['feedback'] ?></b></a>
        <a class="<?= $section === 'cars' ? 'active' : '' ?>" href="admin_dashboard.php?section=cars"><i class="fa-solid fa-car"></i>BMW Cars <b><?= $counts['cars'] ?></b></a>
        <a class="<?= $section === 'notifications' ? 'active' : '' ?>" href="notifications.php"><i class="fa-regular fa-bell"></i>Notifications</a>
        <a href="store.php"><i class="fa-solid fa-bag-shopping"></i>BMW Store</a>
      </nav>

      <div class="sidebar-footer">
        <a href="../home.php"><i class="fa-solid fa-arrow-up-right-from-square"></i>View website</a>
        <a href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
        <a href="logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Sign out</a>
      </div>
    </aside>

    <main class="main-content">
      <header class="topbar">
        <button class="menu-toggle" type="button" aria-label="Open navigation" aria-controls="sidebar"><i class="fa-solid fa-bars"></i></button>
        <div class="breadcrumb"><span>Control centre</span><i class="fa-solid fa-chevron-right"></i><strong><?= $activeEntity ? admin_escape($activeEntity['label']) : 'Overview' ?></strong></div>
        <div class="admin-topbar-actions">
          <?php require __DIR__ . '/notification_widget.php'; ?>
          <div class="admin-profile"><span class="profile-mark"><?= admin_escape(strtoupper(substr((string) $_SESSION['admin_name'], 0, 1))) ?></span><span><strong><?= admin_escape((string) $_SESSION['admin_name']) ?></strong><small>Administrator</small></span></div>
        </div>
      </header>

      <section class="page-content">
        <?php if ($flash): ?>
          <div class="notice <?= admin_escape($flash['type']) ?>" role="status"><i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i><?= admin_escape($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($section === 'overview'): ?>
          <div class="hero-row">
            <div>
              <p class="eyebrow">SHOWROOM OPERATIONS</p>
              <h1>Good to see you,<br><em><?= admin_escape(explode(' ', (string) $_SESSION['admin_name'])[0]) ?>.</em></h1>
              <p class="intro">Keep customers, BMW cars, sales bookings, test drives, service requests and visitor feedback organised from one place.</p>
            </div>
            <div class="hero-date"><i class="fa-regular fa-calendar"></i><span><?= date('l') ?><strong><?= date('d M Y') ?></strong></span></div>
          </div>

          <div class="stats-grid">
            <a class="stat-card blue" href="admin_dashboard.php?section=cars"><span class="stat-icon"><i class="fa-solid fa-car"></i></span><span><small>BMW CARS</small><strong><?= $counts['cars'] ?></strong><em>Manage catalogue <i class="fa-solid fa-arrow-right"></i></em></span></a>
            <a class="stat-card blue" href="admin_dashboard.php?section=customers"><span class="stat-icon"><i class="fa-solid fa-users"></i></span><span><small>REGISTERED CUSTOMERS</small><strong><?= $counts['customers'] ?></strong><em>Manage accounts <i class="fa-solid fa-arrow-right"></i></em></span></a>
            <a class="stat-card amber" href="admin_dashboard.php?section=test-drives"><span class="stat-icon"><i class="fa-solid fa-key"></i></span><span><small>TEST-DRIVE REQUESTS</small><strong><?= $counts['test-drives'] ?></strong><em>Manage requests <i class="fa-solid fa-arrow-right"></i></em></span></a>
            <a class="stat-card amber" href="admin_dashboard.php?section=car-services"><span class="stat-icon"><i class="fa-solid fa-screwdriver-wrench"></i></span><span><small>CAR-SERVICE REQUESTS</small><strong><?= $counts['car-services'] ?></strong><em>Manage appointments <i class="fa-solid fa-arrow-right"></i></em></span></a>
            <a class="stat-card blue" href="admin_dashboard.php?section=sales"><span class="stat-icon"><i class="fa-solid fa-handshake"></i></span><span><small>SALES BOOKINGS</small><strong><?= $counts['sales'] ?></strong><em>Manage reservations <i class="fa-solid fa-arrow-right"></i></em></span></a>
            <a class="stat-card red" href="admin_dashboard.php?section=feedback"><span class="stat-icon"><i class="fa-solid fa-star"></i></span><span><small>AVERAGE EXPERIENCE</small><strong><?= $feedbackAverage ? number_format($feedbackAverage, 1) . '<small>/5</small>' : '—' ?></strong><em><?= $counts['feedback'] ?> feedback entries <i class="fa-solid fa-arrow-right"></i></em></span></a>
          </div>

          <div class="dashboard-grid">
            <section class="panel recent-panel">
              <div class="panel-heading"><div><p class="eyebrow">LATEST ACTIVITY</p><h2>Recent test-drive requests</h2></div><a href="admin_dashboard.php?section=test-drives">View all <i class="fa-solid fa-arrow-right"></i></a></div>
              <?php if ($recentDrives): ?>
                <div class="compact-table-wrap"><table class="compact-table"><thead><tr><th>Customer</th><th>Vehicle</th><th>City</th><th>Finance</th></tr></thead><tbody>
                <?php foreach ($recentDrives as $drive): ?>
                  <tr><td><strong><?= admin_escape(($drive['First_Name'] ?? '') . ' ' . ($drive['Last_Name'] ?? '')) ?></strong><small><?= admin_escape($drive['E-Mail'] ?? '') ?></small></td><td><?= admin_escape(($drive['Series'] ?? '') . ' · ' . ($drive['Model'] ?? '')) ?></td><td><?= admin_escape($drive['City'] ?? '') ?></td><td><span class="status <?= ($drive['Finance'] ?? '') === 'yes' ? 'yes' : 'no' ?>"><?= ($drive['Finance'] ?? '') === 'yes' ? 'Interested' : 'Not interested' ?></span></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
              <?php else: ?>
                <div class="empty-state small"><i class="fa-solid fa-car-side"></i><p>No test-drive requests yet.</p><a href="admin_dashboard.php?section=test-drives">Add the first request</a></div>
              <?php endif; ?>
            </section>

            <section class="panel quick-panel">
              <p class="eyebrow">QUICK ACTIONS</p><h2>Start a task</h2>
              <a href="admin_dashboard.php?section=customers#new-record"><span><i class="fa-solid fa-user-plus"></i><strong>Add customer</strong><small>Create a showroom account</small></span><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
              <a href="admin_dashboard.php?section=test-drives#new-record"><span><i class="fa-solid fa-calendar-plus"></i><strong>Log a test drive</strong><small>Create a request manually</small></span><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
              <a href="admin_dashboard.php?section=car-services#new-record"><span><i class="fa-solid fa-screwdriver-wrench"></i><strong>Log a service request</strong><small>Create an appointment request</small></span><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
              <a href="admin_dashboard.php?section=feedback#new-record"><span><i class="fa-solid fa-pen-to-square"></i><strong>Add feedback</strong><small>Record a visitor response</small></span><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
              <a href="admin_dashboard.php?section=cars#new-record"><span><i class="fa-solid fa-car"></i><strong>Add BMW car</strong><small>Manage catalogue data</small></span><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
              <a href="admin_dashboard.php?section=sales"><span><i class="fa-solid fa-handshake"></i><strong>Review sales bookings</strong><small><?= $counts['sales'] ?> purchase requests received</small></span><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            </section>
          </div>
          <section class="panel recent-panel">
            <div class="panel-heading"><div><p class="eyebrow">LATEST ACTIVITY</p><h2>Recent sales bookings</h2></div><a href="admin_dashboard.php?section=sales">View all <i class="fa-solid fa-arrow-right"></i></a></div>
            <?php if ($recentSales): ?>
              <div class="compact-table-wrap"><table class="compact-table"><thead><tr><th>Customer</th><th>Vehicle</th><th>City</th><th>Status</th></tr></thead><tbody>
              <?php foreach ($recentSales as $booking): ?>
                <tr id="sales-booking-<?= (int)$booking['id'] ?>"><td><strong><?= admin_escape(($booking['First_Name'] ?? '') . ' ' . ($booking['Last_Name'] ?? '')) ?></strong><small><?= admin_escape($booking['E-Mail'] ?? '') ?></small></td><td><?= admin_escape((string)($booking['Vehicle_Name'] ?? '')) ?></td><td><?= admin_escape((string)($booking['City'] ?? '')) ?></td><td><span class="status <?= in_array(($booking['Status'] ?? 'pending'), ['contacted','reserved','completed'], true) ? 'yes' : (($booking['Status'] ?? '') === 'cancelled' ? 'no' : 'pending') ?>"><?= admin_escape(ucfirst((string)($booking['Status'] ?? 'pending'))) ?></span></td></tr>
              <?php endforeach; ?>
              </tbody></table></div>
            <?php else: ?>
              <div class="empty-state small"><i class="fa-solid fa-handshake"></i><p>No sales bookings yet.</p><a href="admin_dashboard.php?section=sales">Open sales bookings</a></div>
            <?php endif; ?>
          </section>
        <?php else: ?>
          <div class="management-heading">
            <div><p class="eyebrow">DATA MANAGEMENT</p><h1><?= admin_escape($activeEntity['label']) ?></h1><p><?= admin_escape($activeEntity['description']) ?></p></div>
            <a class="primary-button" href="#new-record"><i class="fa-solid fa-plus"></i>Add <?= admin_escape($activeEntity['singular']) ?></a>
          </div>

          <details class="record-editor" id="new-record" open>
            <summary><span><i class="fa-solid fa-circle-plus"></i>New <?= admin_escape($activeEntity['singular']) ?></span><i class="fa-solid fa-chevron-down"></i></summary>
            <form method="post" class="data-form"<?= $section === 'cars' ? ' enctype="multipart/form-data"' : '' ?>>
              <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="create"><input type="hidden" name="entity" value="<?= admin_escape($section) ?>">
              <div class="form-grid">
                <?php foreach ($activeEntity['fields'] as $key => $field): ?><?= admin_field_html('create', $key, $field) ?><?php endforeach; ?>
                <?php if ($section === 'customers'): ?><div class="field"><label for="create-password">Temporary password <span aria-hidden="true">*</span></label><input id="create-password" name="password" type="password" minlength="8" required autocomplete="new-password"><small>The customer can use this to sign in.</small></div><?php endif; ?>
              </div>
              <div class="form-actions"><button class="secondary-button" type="reset">Clear</button><button class="primary-button" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save <?= admin_escape($activeEntity['singular']) ?></button></div>
            </form>
          </details>

          <section class="panel records-panel">
            <div class="records-toolbar"><div><strong><?= count($records) ?> record<?= count($records) === 1 ? '' : 's' ?></strong><span>All <?= strtolower(admin_escape($activeEntity['label'])) ?></span></div><label class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input id="record-search" type="search" placeholder="Search records" autocomplete="off"></label></div>
            <?php if ($records): ?>
              <?php
              $tableFields = !empty($activeEntity['table_fields'])
                  ? array_intersect_key($activeEntity['fields'], array_flip($activeEntity['table_fields']))
                  : $activeEntity['fields'];
              ?>
              <div class="records-table-wrap"><table class="records-table"><thead><tr><th>ID</th><?php foreach ($tableFields as $field): ?><th><?= admin_escape($field['label']) ?></th><?php endforeach; ?><?php if ($isAppointmentRequestSection || $isSalesSection): ?><th>Status</th><?php endif; ?><th><span class="sr-only">Actions</span></th></tr></thead><tbody id="records-body">
              <?php foreach ($records as $record): ?>
                <tr<?= $section === 'test-drives' ? ' id="request-' . (int)$record['id'] . '"' : ($section === 'car-services' ? ' id="service-request-' . (int)$record['id'] . '"' : ($isSalesSection ? ' id="sales-booking-' . (int)$record['id'] . '"' : ($section === 'feedback' ? ' id="feedback-' . (int)$record['id'] . '"' : ''))) ?>>
                  <td><span class="id-pill">#<?= (int) $record['id'] ?></span></td>
                  <?php foreach ($tableFields as $field): ?><td class="<?= $field['type'] === 'textarea' ? 'message-cell' : '' ?>"><?= admin_table_value($field, $record) ?></td><?php endforeach; ?>
                  <?php if ($isAppointmentRequestSection || $isSalesSection): ?><td><span class="status <?= in_array(($record['Status'] ?? 'pending'), ['accepted','rescheduled','contacted','reserved','completed'] , true) ? 'yes' : (in_array(($record['Status'] ?? ''), ['rejected','cancelled'], true) ? 'no' : 'pending') ?>"><?= admin_escape(ucfirst((string)($record['Status'] ?? 'pending'))) ?></span></td><?php endif; ?>
                  <td class="row-actions">
                    <?php if ($section === 'cars' && !empty($record['slug'])): ?>
                      <a class="icon-button" href="../car-details.php?slug=<?= rawurlencode((string)$record['slug']) ?>" target="_blank" aria-label="View on website" title="View live details page"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                    <?php endif; ?>
                    <button type="button" class="icon-button edit-record" data-record="<?= admin_record_payload($activeEntity, $record) ?>" aria-label="Edit record" title="Edit car details"><i class="fa-solid fa-pen"></i></button>
                    <?php if ($isAppointmentRequestSection): ?>
                      <?php $isCarService = $section === 'car-services'; ?>
                      <details class="testdrive-status-editor"><summary aria-label="Update request status"><i class="fa-solid fa-calendar-check"></i></summary><form method="post" class="testdrive-status-form"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="<?= $isCarService ? 'carservice_status' : 'testdrive_status' ?>"><input type="hidden" name="id" value="<?= (int)$record['id'] ?>"><label>Status<select name="status" required><option value="accepted">Accept</option><option value="rejected">Reject</option><option value="rescheduled">Reschedule</option></select></label><label>Appointment date<input type="date" name="appointment_date" value="<?= admin_escape((string)($record['Appointment_Date'] ?? ($isCarService ? $record['Preferred_Date'] ?? '' : ''))) ?>"></label><label>Appointment time<input type="time" name="appointment_time" value="<?= admin_escape(substr((string)($record['Appointment_Time'] ?? ($isCarService ? $record['Preferred_Time'] ?? '' : '')), 0, 5)) ?>"></label><button class="primary-button" type="submit">Update &amp; notify</button></form></details>
                    <?php endif; ?>
                    <?php if ($isSalesSection): ?>
                      <details class="testdrive-status-editor"><summary aria-label="Update sales booking status"><i class="fa-solid fa-clipboard-check"></i></summary><form method="post" class="testdrive-status-form"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="sales_status"><input type="hidden" name="entity" value="sales"><input type="hidden" name="id" value="<?= (int)$record['id'] ?>"><label>Status<select name="status" required><option value="pending"<?= ($record['Status'] ?? '') === 'pending' ? ' selected' : '' ?>>Pending</option><option value="contacted"<?= ($record['Status'] ?? '') === 'contacted' ? ' selected' : '' ?>>Contacted</option><option value="reserved"<?= ($record['Status'] ?? '') === 'reserved' ? ' selected' : '' ?>>Reserved</option><option value="completed"<?= ($record['Status'] ?? '') === 'completed' ? ' selected' : '' ?>>Completed</option><option value="cancelled"<?= ($record['Status'] ?? '') === 'cancelled' ? ' selected' : '' ?>>Cancelled</option></select></label><button class="primary-button" type="submit">Update &amp; notify</button></form></details>
                    <?php endif; ?>
                    <form method="post" class="delete-form"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="entity" value="<?= admin_escape($section) ?>"><input type="hidden" name="id" value="<?= (int) $record['id'] ?>"><button class="icon-button danger" type="submit" aria-label="Delete record"><i class="fa-solid fa-trash-can"></i></button></form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody></table></div>
            <?php else: ?>
              <div class="empty-state"><i class="fa-solid fa-folder-open"></i><h3>No <?= strtolower(admin_escape($activeEntity['label'])) ?> yet</h3><p>Create the first <?= admin_escape($activeEntity['singular']) ?> to start managing this section.</p><a class="primary-button" href="#new-record">Add <?= admin_escape($activeEntity['singular']) ?></a></div>
            <?php endif; ?>
          </section>

          <dialog class="edit-dialog" id="edit-dialog" aria-labelledby="edit-title">
            <div class="dialog-heading"><div><p class="eyebrow">UPDATE RECORD</p><h2 id="edit-title">Edit <?= admin_escape($activeEntity['singular']) ?></h2></div><button class="close-dialog" type="button" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
            <form method="post" class="data-form" id="edit-form"<?= $section === 'cars' ? ' enctype="multipart/form-data"' : '' ?>>
              <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="entity" value="<?= admin_escape($section) ?>"><input type="hidden" name="id" value="">
              <div class="form-grid">
                <?php foreach ($activeEntity['fields'] as $key => $field): ?><?= admin_field_html('edit', $key, $field, true) ?><?php endforeach; ?>
                <?php if ($section === 'cars'): ?><div class="field field-file-info"><small data-current-image>Current image will be shown here.</small></div><?php endif; ?>
                <?php if ($section === 'cars'): ?><div class="field"><label><input type="checkbox" name="remove_image" value="1"> Remove current image</label><small>Leave unchecked to keep the current image when you are not uploading a replacement.</small></div><?php endif; ?>
                <?php if ($section === 'customers'): ?><div class="field"><label for="edit-password">Change password</label><input id="edit-password" name="password" type="password" minlength="8" autocomplete="new-password"><small>Leave empty to retain the current password.</small></div><?php endif; ?>
              </div>
              <div class="form-actions"><button class="secondary-button close-dialog" type="button">Cancel</button><button class="primary-button" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save changes</button></div>
            </form>
          </dialog>
        <?php endif; ?>
      </section>
    </main>
  </div>
  <script src="../../js/admin.js"></script>
  <script src="../../js/notifications.js?v=20260926-1" defer></script>
</body>
</html>
