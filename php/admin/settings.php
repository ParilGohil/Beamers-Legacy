<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

admin_ensure_schema($conn);
admin_require_login();
$sidebarCounts = [];
foreach (['customers', 'test-drives', 'car-services', 'sales', 'feedback', 'cars'] as $sidebarEntity) {
    $sidebarCounts[$sidebarEntity] = admin_count($conn, $sidebarEntity);
}
$definitions = admin_settings_definitions();
$defaults = [
    'showroom_name' => 'Beamers Legacy', 'legal_business_name' => 'Beamers Legacy',
    'tagline' => 'Sheer Driving Pleasure', 'dealer_code' => 'BMW-AHD-001', 'dealership_type' => 'Authorized BMW Dealer',
    'city' => 'Ahmedabad', 'state' => 'Gujarat', 'country' => 'India', 'pincode' => '380054',
    'latitude' => '23.0225', 'longitude' => '72.5714', 'business_currency' => 'INR ₹', 'business_country' => 'India', 'business_state' => 'Gujarat',
    'default_gst_rate' => '18', 'website_name' => 'Beamers Legacy', 'website_title' => 'Beamers Legacy | BMW Dealership',
    'website_description' => 'Explore BMW cars, book test drives and connect with our showroom.', 'language' => 'English',
    'system_currency' => 'INR ₹', 'timezone' => 'Asia/Kolkata', 'date_format' => 'DD/MM/YYYY', 'time_format' => '12 Hour',
];
$values = array_merge($defaults, admin_settings($conn));
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!admin_verify_csrf()) {
            throw new RuntimeException('Your session token expired. Refresh the page and try again.');
        }
        $settingsInput = $_POST;
        foreach (['logo_path', 'favicon_path'] as $imageKey) {
            if (isset($_FILES[$imageKey]) && ($_FILES[$imageKey]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $settingsInput[$imageKey] = admin_store_upload($_FILES[$imageKey]);
            } else {
                $settingsInput[$imageKey] = (string)($values[$imageKey] ?? '');
            }
        }
        admin_settings_save($conn, $settingsInput);
        $notice = ['type' => 'success', 'message' => 'Settings updated successfully.'];
        $values = array_merge($defaults, admin_settings($conn));
    } catch (Throwable $error) {
        $notice = ['type' => 'error', 'message' => $error->getMessage()];
        $values = array_merge($values, $_POST);
    }
}

function settings_field(string $key, array $field, array $values): string
{
    $id = 'setting-' . $key;
    $value = admin_escape((string)($values[$key] ?? ''));
    $label = admin_escape($field['label']);
    $html = '<div class="field"><label for="' . $id . '">' . $label . '</label>';
    if ($field['type'] === 'file') {
        $html .= '<input id="' . $id . '" name="' . admin_escape($key) . '" type="file" accept="image/jpeg,image/png,image/webp,image/gif">';
        $html .= '<small>Choose a file from your computer.</small>';
    } elseif ($field['type'] === 'textarea') {
        $html .= '<textarea id="' . $id . '" name="' . admin_escape($key) . '" rows="4">' . $value . '</textarea>';
    } elseif ($field['type'] === 'select') {
        $html .= '<select id="' . $id . '" name="' . admin_escape($key) . '"><option value="">Select</option>';
        foreach ($field['options'] as $optionValue => $optionLabel) {
            if (is_int($optionValue)) $optionValue = (string)$optionLabel;
            $selected = (string)$optionValue === (string)($values[$key] ?? '') ? ' selected' : '';
            $html .= '<option value="' . admin_escape((string)$optionValue) . '"' . $selected . '>' . admin_escape((string)$optionLabel) . '</option>';
        }
        $html .= '</select>';
    } else {
        $html .= '<input id="' . $id . '" name="' . admin_escape($key) . '" type="' . admin_escape($field['type']) . '" value="' . $value . '">';
    }
    return $html . '</div>';
}

$csrf = admin_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Settings | Beamers Legacy Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="../../css/admin.css?v=20260926-notifications-1">
  <style>
    .settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-top:30px}.settings-card{padding:25px}
    .settings-card.wide{grid-column:1/-1}.settings-card .panel-heading{margin-bottom:18px}.settings-card .form-grid{margin:0}
    .settings-card .field input,.settings-card .field select,.settings-card .field textarea{width:100%}.settings-card .form-actions{margin-top:20px}
    .settings-preview{display:flex;align-items:center;gap:14px;margin-bottom:18px;color:#7c838d;font-size:11px;font-weight:700}.settings-preview img{width:54px;height:54px;object-fit:contain;padding:8px;border:1px solid var(--line);border-radius:10px;background:#171a1f}
    .hours-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}.hours-grid .field:first-child{grid-column:1/-1}
    .map-link{display:inline-flex;margin-top:12px;color:var(--blue);font-size:12px;font-weight:800}
    @media(max-width:850px){.settings-grid{grid-template-columns:1fr}.settings-card.wide{grid-column:auto}.hours-grid{grid-template-columns:1fr}.hours-grid .field:first-child{grid-column:auto}}
  </style>
</head>
<body>
<div class="admin-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="admin_dashboard.php"><img src="../../images/BMW logo.svg" alt="BMW logo"><span><strong>BEAMERS</strong><small>LEGACY / ADMIN</small></span></a>
    <nav class="sidebar-nav" aria-label="Dashboard sections">
      <a href="admin_dashboard.php"><i class="fa-solid fa-grid-2"></i>Overview</a>
      <a href="admin_dashboard.php?section=customers"><i class="fa-solid fa-users"></i>Customers <b><?= $sidebarCounts['customers'] ?></b></a>
      <a href="admin_dashboard.php?section=test-drives"><i class="fa-solid fa-car-side"></i>Test drives <b><?= $sidebarCounts['test-drives'] ?></b></a>
      <a href="admin_dashboard.php?section=car-services"><i class="fa-solid fa-screwdriver-wrench"></i>Car service <b><?= $sidebarCounts['car-services'] ?></b></a>
      <a href="admin_dashboard.php?section=sales"><i class="fa-solid fa-handshake"></i>Sales bookings <b><?= $sidebarCounts['sales'] ?></b></a>
      <a href="admin_dashboard.php?section=feedback"><i class="fa-solid fa-message"></i>Feedback <b><?= $sidebarCounts['feedback'] ?></b></a>
      <a href="admin_dashboard.php?section=cars"><i class="fa-solid fa-car"></i>BMW Cars <b><?= $sidebarCounts['cars'] ?></b></a>
      <a href="notifications.php"><i class="fa-regular fa-bell"></i>Notifications</a>
      <a href="store.php"><i class="fa-solid fa-bag-shopping"></i>BMW Store</a>
    </nav>
    <div class="sidebar-footer">
      <a href="../home.php"><i class="fa-solid fa-arrow-up-right-from-square"></i>View website</a>
      <a class="active" href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
      <a href="logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Sign out</a>
    </div>
  </aside>
  <main class="main-content">
    <header class="topbar"><button class="menu-toggle" type="button" aria-label="Open navigation" aria-controls="sidebar"><i class="fa-solid fa-bars"></i></button><div class="breadcrumb"><span>Control centre</span><i class="fa-solid fa-chevron-right"></i><strong>Settings</strong></div><div class="admin-topbar-actions"><?php require __DIR__ . '/notification_widget.php'; ?><div class="admin-profile"><span class="profile-mark"><?= admin_escape(strtoupper(substr((string) $_SESSION['admin_name'], 0, 1))) ?></span><span><strong><?= admin_escape((string) $_SESSION['admin_name']) ?></strong><small>Administrator</small></span></div></div></header>
    <section class="page-content">
      <?php if ($notice): ?><div class="notice <?= admin_escape($notice['type']) ?>"><i class="fa-solid <?= $notice['type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i><?= admin_escape($notice['message']) ?></div><?php endif; ?>
      <div class="hero-row"><div><p class="eyebrow">CONFIGURATION</p><h1>Settings</h1><p class="intro">Manage your showroom information, contact details and website configuration.</p></div></div>
      <form method="post" enctype="multipart/form-data" id="settings-form">
        <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>">
        <div class="settings-grid">
          <?php foreach ($definitions as $sectionKey => $definition): ?>
            <section class="panel settings-card <?= $sectionKey === 'hours' || $sectionKey === 'website' ? 'wide' : '' ?>">
              <div class="panel-heading"><h2><?= admin_escape($definition['title']) ?></h2></div>
              <?php if ($sectionKey === 'website'): ?>
                <div class="settings-preview"><span>Current logo preview</span><img src="../../<?= admin_escape((string)($values['logo_path'] ?? 'images/BMW logo.svg')) ?>" alt="Current showroom logo"></div>
              <?php endif; ?>
              <div class="<?= $sectionKey === 'hours' ? 'hours-grid' : 'form-grid' ?>">
                <?php foreach ($definition['fields'] as $key => $field): ?><?= settings_field($key, $field, $values) ?><?php endforeach; ?>
              </div>
              <?php if ($sectionKey === 'hours'): ?><button type="button" class="secondary-button" id="apply-monday-hours"><i class="fa-solid fa-copy"></i>Apply Monday Hours to All</button><?php endif; ?>
              <?php if ($sectionKey === 'coordinates'): ?><a class="map-link" href="<?= admin_escape((string)($values['google_maps_url'] ?? '#')) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-location-dot"></i>&nbsp; Open in Google Maps</a><?php endif; ?>
              <div class="form-actions"><button class="primary-button settings-save" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save Changes</button></div>
            </section>
          <?php endforeach; ?>
        </div>
      </form>
    </section>
  </main>
</div>
<script src="../../js/admin.js"></script>
<script src="../../js/notifications.js?v=20260926-1" defer></script>
<script>
document.getElementById('settings-form')?.addEventListener('submit', function () {
  this.dataset.saved = 'true';
  this.querySelectorAll('.settings-save').forEach((button) => { button.disabled = true; button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...'; });
});
const settingsForm = document.getElementById('settings-form');
const settingsSnapshot = settingsForm ? Array.from(settingsForm.elements)
  .filter((element) => element.name && element.name !== 'csrf' && element.name !== 'action' && element.type !== 'file')
  .map((element) => element.name + '=' + element.value)
  .join('&') : '';
document.getElementById('apply-monday-hours')?.addEventListener('click', function () {
  const form = document.getElementById('settings-form');
  const from = form.querySelector('[name="hours_monday_from"]').value;
  const to = form.querySelector('[name="hours_monday_to"]').value;
  ['tuesday','wednesday','thursday','friday','saturday','sunday'].forEach((day) => {
    form.querySelector(`[name="hours_${day}_from"]`).value = from;
    form.querySelector(`[name="hours_${day}_to"]`).value = to;
    form.querySelector(`[name="hours_${day}_open"]`).value = form.querySelector('[name="hours_monday_open"]').value;
  });
});
window.addEventListener('beforeunload', function (event) {
  const form = document.getElementById('settings-form');
  const currentSnapshot = form ? Array.from(form.elements)
    .filter((element) => element.name && element.name !== 'csrf' && element.name !== 'action' && element.type !== 'file')
    .map((element) => element.name + '=' + element.value)
    .join('&') : '';
  if (form && form.dataset.saved !== 'true' && currentSnapshot !== settingsSnapshot) {
    event.preventDefault();
    event.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
  }
});
</script>
</body>
</html>
