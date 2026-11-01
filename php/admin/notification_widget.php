<div class="notification-widget admin-notification-widget" data-notification-api="../notifications-api.php" data-notification-scope="admin" data-notification-csrf="<?= admin_escape(app_csrf_token()) ?>">
  <button class="notification-trigger" type="button" aria-label="Admin notifications" aria-expanded="false"><i class="fa-regular fa-bell" aria-hidden="true"></i><span class="notification-badge" hidden></span></button>
  <section class="notification-popover" aria-label="Recent notifications" hidden>
    <header><strong>Notifications</strong><button type="button" class="notification-mark-all">Mark all as read</button></header>
    <div class="notification-items" aria-live="polite"><p class="notification-loading">Loading notifications…</p></div>
    <footer><a href="notifications.php">View all notifications</a></footer>
  </section>
</div>
