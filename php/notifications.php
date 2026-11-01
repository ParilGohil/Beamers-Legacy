<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();
if (empty($_SESSION['user_id'])) {
    header('Location: loginform.php?return_to=notifications.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Notifications | Beamers Legacy</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1">
  <style>
    .notifications-page{min-height:100vh;padding:170px 24px 80px;background:#08090b;color:#f5f6f7}
    .notifications-main{width:min(1000px,100%);margin:0 auto}
    .notifications-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:24px;margin-bottom:28px}
    .notifications-heading h1{font-size:clamp(2rem,5vw,3.5rem);letter-spacing:-.05em}
    .notifications-heading p{margin-top:8px;color:#aeb4bb}
    .notifications-filters{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:18px;padding:16px;border:1px solid rgba(255,255,255,.12);background:#101215}
    .notifications-filters input[type=search]{flex:1 1 240px;min-width:0;padding:11px 13px;border:1px solid rgba(255,255,255,.17);background:#08090b;color:#fff}
    .notifications-filters label{display:flex;align-items:center;gap:8px;color:#c2c6cb;font-size:.8rem}
    .notifications-filters input[type=checkbox]{accent-color:#1c69d4}
    .notification-mark-all{border:0;background:transparent;color:#69a9ff;font-size:.72rem;font-weight:700;cursor:pointer}
    .notification-list{display:grid;gap:10px}
    .notification-item{position:relative;display:flex;align-items:center;gap:14px;padding:18px 20px;border:1px solid rgba(255,255,255,.12);background:#101215}
    .notification-item.is-unread{border-left:3px solid #1c69d4;background:#111820}
    .notification-item-link{display:flex;align-items:center;gap:14px;flex:1;min-width:0;color:inherit}
    .notification-item-icon{display:grid;place-items:center;flex:0 0 40px;width:40px;height:40px;border-radius:50%;background:rgba(28,105,212,.15);color:#70adff}
    .notification-item-copy{display:grid;gap:4px;min-width:0}
    .notification-item-copy strong{font-size:.9rem}
    .notification-item-copy>span{color:#b4b9bf;font-size:.8rem;line-height:1.5}
    .notification-item-copy time{color:#777f88;font-size:.68rem}
    .notification-mark-read{padding:7px 10px;border:1px solid rgba(255,255,255,.17);background:transparent;color:#aeb8c0;font-size:.65rem;white-space:nowrap;cursor:pointer}
    .notification-mark-read:hover{border-color:#1c69d4;color:#fff}
    .notification-empty,.notification-error,.notification-loading{padding:36px 20px;text-align:center;color:#aeb4bb}
    .notification-empty{display:grid;justify-items:center;gap:8px}
    .notification-empty>i{color:#6eaaff;font-size:1.8rem}
    .notification-empty strong{color:#f5f6f7}
    .notification-empty p{margin:0;color:#939ba4;font-size:.85rem}
    .notification-error{color:#ffb4b4}
    @media(max-width:600px){.notifications-page{padding:130px 16px 55px}.notifications-heading{align-items:flex-start;flex-direction:column}.notifications-filters{align-items:stretch}.notification-item{align-items:flex-start;padding:14px 12px}.notification-mark-read{padding:6px}.notification-item-copy>span{font-size:.74rem}}
  </style>
</head>
<body>
<?php require_once __DIR__ . '/navbar.php'; ?>
<main class="notifications-page">
  <div class="notifications-main notification-widget" data-notification-api="notifications-api.php" data-notification-scope="customer" data-notification-csrf="<?= e(app_csrf_token()) ?>" data-notification-page="1">
    <header class="notifications-heading">
      <div><p class="car-detail-eyebrow">Beamers Legacy / Your account</p><h1>Notifications</h1><p>Updates about your BMW requests and showroom activity.</p></div>
      <button type="button" class="notification-mark-all">Mark all as read</button>
    </header>
    <div class="notifications-filters">
      <input type="search" data-notification-search placeholder="Search notifications" aria-label="Search notifications">
      <label><input type="checkbox" data-notification-unread> Unread only</label>
    </div>
    <section class="notification-list" data-notification-results aria-live="polite"><p class="notification-loading">Loading notifications…</p></section>
  </div>
</main>
</body>
</html>
