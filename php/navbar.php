<?php
// Shared customer-facing navigation. Every public page includes this so the
// navigation always reflects the current PHP session.
require_once __DIR__ . '/config.php';
start_app_session();
$isLoggedIn = !empty($_SESSION['user_id']);
$userName = trim((string)($_SESSION['user_name'] ?? trim((string)($_SESSION['first_name'] ?? '') . ' ' . (string)($_SESSION['last_name'] ?? ''))));
$userEmail = trim((string)($_SESSION['email'] ?? ''));
$userPhoto = trim((string)($_SESSION['profile_photo_url'] ?? ''));
if (strtolower((string)parse_url($userPhoto, PHP_URL_SCHEME)) !== 'https') {
    $userPhoto = '';
}
$initial = strtoupper(substr($userName !== '' ? $userName : 'U', 0, 1));
$currentPage = strtolower(basename((string)parse_url((string)($_SERVER['SCRIPT_NAME'] ?? ''), PHP_URL_PATH)));
$isCurrentNavPage = static function (array $pages) use ($currentPage): bool {
    return in_array($currentPage, $pages, true);
};
$notificationUnread = 0;
if ($isLoggedIn) {
    try {
        $notificationConnection = db();
        app_ensure_car_catalog_schema($notificationConnection);
        app_ensure_notification_schema($notificationConnection);
        $notificationCount = $notificationConnection->prepare("SELECT COUNT(*) AS `count` FROM `notifications` WHERE `recipient_type` = 'customer' AND `user_id` = ? AND `is_read` = 0");
        $notificationCount->bind_param('i', $_SESSION['user_id']);
        $notificationCount->execute();
        $notificationUnread = (int)$notificationCount->get_result()->fetch_assoc()['count'];
        $notificationCount->close();
    } catch (Throwable $error) {
        error_log('Customer notification badge could not be loaded: ' . $error->getMessage());
    }
}
$navModelGroups = bmw_nav_model_groups();
?>
<nav class="navbar">
  <div class="nav-utility">
    <div class="nav-utility-inner">
      <div class="nav-utility-brand"><strong><?= e(bmw_setting('showroom_name', 'Beamers Legacy')) ?></strong><span><?= e(bmw_setting('city', 'AHMEDABAD')) ?></span></div>
      <strong class="nav-utility-promo"><?= e(bmw_setting('tagline', 'SAVINGS OF UPTO 100% ON ROAD TAX ON i7')) ?></strong>
      <a class="nav-utility-more" href="more bmw.php">KNOW MORE</a>
      <div class="nav-utility-icons" aria-label="Quick links">
        <a href="customer support.php" aria-label="Service calendar"><i class="fa-regular fa-calendar-days"></i></a>
        <a href="customer support.php" aria-label="Contact phone"><i class="fa-solid fa-phone"></i></a>
        <a href="testdrive.php" aria-label="Book a test drive"><i class="fa-solid fa-steering-wheel"></i></a>
        <?php if ($isLoggedIn): ?>
          <div class="notification-widget customer-notification-widget" data-notification-api="notifications-api.php" data-notification-scope="customer" data-notification-csrf="<?= e(app_csrf_token()) ?>">
            <button class="notification-trigger" type="button" aria-label="Notifications<?= $notificationUnread > 0 ? ', ' . $notificationUnread . ' unread' : '' ?>" aria-expanded="false">
              <i class="fa-regular fa-bell" aria-hidden="true"></i>
              <span class="notification-badge"<?= $notificationUnread < 1 ? ' hidden' : '' ?>><?= $notificationUnread > 99 ? '99+' : $notificationUnread ?></span>
            </button>
            <section class="notification-popover" aria-label="Recent notifications" hidden>
              <header><strong>Notifications</strong><button type="button" class="notification-mark-all">Mark all as read</button></header>
              <div class="notification-items" aria-live="polite"><p class="notification-loading">Loading notifications…</p></div>
              <footer><a href="notifications.php">View all notifications</a></footer>
            </section>
          </div>
        <?php endif; ?>
        <div class="nav-profile-dropdown nav-utility-account">
          <button class="nav-profile-trigger" type="button" aria-label="<?= $isLoggedIn ? 'Account menu' : 'Login and register' ?>" aria-haspopup="true" aria-expanded="false">
            <i class="fa-regular fa-user" aria-hidden="true"></i>
          </button>
          <div class="nav-profile-menu">
            <?php if ($isLoggedIn): ?>
              <div class="nav-account-summary">
                <span class="nav-account-avatar" aria-hidden="true"><?php if ($userPhoto !== ''): ?><img src="<?= e($userPhoto) ?>" alt=""><?php else: ?><?= e($initial) ?><?php endif; ?></span>
                <span class="nav-account-identity"><strong><?= e($userName !== '' ? $userName : 'Account') ?></strong><small><?= e($userEmail) ?></small></span>
              </div>
              <a href="account.php"><i class="fa-regular fa-user"></i> My Profile</a>
              <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
            <?php else: ?>
              <a href="loginform.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
              <a href="register.php"><i class="fa-solid fa-user-plus"></i> Register</a>
            <?php endif; ?>
          </div>
        </div>
        <a href="customer support.php" aria-label="Find a location"><i class="fa-solid fa-location-dot"></i></a>
      </div>
    </div>
  </div>
  <div class="nav-shell">
  <a href="home.php" class="nav-brand nav-brand-logo" aria-label="Beamers Legacy home">
    <img src="../images/BMW logo.svg" alt="BMW Logo" />
    <h1>Beamers Legacy</h1>
  </a>
  <div class="nav-menu" aria-label="Primary navigation">
    <a href="home.php" class="<?= $isCurrentNavPage(['home.php']) ? 'active' : '' ?>"<?= $isCurrentNavPage(['home.php']) ? ' aria-current="page"' : '' ?>>Home</a>
    <?php if ($navModelGroups): ?>
    <div class="nav-item-mega">
      <a href="models.php" class="nav-mega-trigger<?= $isCurrentNavPage(['models.php', 'car-details.php', 'electric.php', 'php presentation.php', 'booking.php']) ? ' active' : '' ?>"<?= $isCurrentNavPage(['models.php', 'car-details.php', 'electric.php', 'php presentation.php', 'booking.php']) ? ' aria-current="page"' : '' ?>>
        New Cars
      </a>
      <div class="mega-menu" role="menu" aria-label="BMW model series">
        <div class="mega-menu-inner">
          <div class="mega-menu-groups" role="tablist">
            <?php foreach ($navModelGroups as $i => $group): ?>
              <button type="button"
                      class="mega-group-tab<?= $i === 0 ? ' active' : '' ?>"
                      data-group="<?= e($group['key']) ?>"
                      role="tab"
                      aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                <?php $groupImage = bmw_mega_menu_image((string)($group['cars'][0]['image'] ?? '')); ?>
                <span class="mega-group-preview">
                  <?php if ($groupImage !== ''): ?>
                    <img src="<?= e($groupImage) ?>" alt="" loading="lazy">
                  <?php else: ?>
                    <i class="fa-solid fa-car-side" aria-hidden="true"></i>
                  <?php endif; ?>
                </span>
                <strong><?= e($group['key']) ?></strong>
              </button>
            <?php endforeach; ?>
          </div>
          <div class="mega-menu-panels">
            <?php foreach ($navModelGroups as $i => $group): ?>
              <div class="mega-group-panel<?= $i === 0 ? ' active' : '' ?>" data-group="<?= e($group['key']) ?>" role="tabpanel">
                <?php foreach ($group['cars'] as $car): ?>
                  <?php $carDetailsUrl = 'car-details.php?slug=' . rawurlencode((string)$car['slug']); ?>
                  <article class="mega-car-card">
                    <a href="<?= e($carDetailsUrl) ?>" class="mega-car-info">
                      <h4><?= e((string)$car['name']) ?></h4>
                      <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </a>
                    <a href="<?= e($carDetailsUrl) ?>" class="mega-car-media">
                      <?php $imgSrc = bmw_mega_menu_image((string)($car['image'] ?? '')); ?>
                      <?php if ($imgSrc !== ''): ?>
                        <img src="<?= e($imgSrc) ?>" alt="<?= e((string)$car['name']) ?>" loading="lazy">
                      <?php else: ?>
                        <span class="mega-car-placeholder"><i class="fa-solid fa-car-side" aria-hidden="true"></i></span>
                      <?php endif; ?>
                    </a>
                  </article>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="mega-menu-footer">
          <a href="models.php"><i class="fa-solid fa-grip" aria-hidden="true"></i> View All Models</a>
          <a href="testdrive.php"><i class="fa-solid fa-car" aria-hidden="true"></i> Book A Test Drive</a>
          <a href="all lineup.php"><i class="fa-solid fa-list" aria-hidden="true"></i> All Lineup</a>
        </div>
      </div>
    </div>
    <?php else: ?>
      <a href="models.php" class="<?= $isCurrentNavPage(['models.php', 'car-details.php', 'electric.php', 'php presentation.php', 'booking.php']) ? 'active' : '' ?>"<?= $isCurrentNavPage(['models.php', 'car-details.php', 'electric.php', 'php presentation.php', 'booking.php']) ? ' aria-current="page"' : '' ?>>New Cars</a>
    <?php endif; ?>
    <a href="all lineup.php" class="<?= $isCurrentNavPage(['all lineup.php']) ? 'active' : '' ?>"<?= $isCurrentNavPage(['all lineup.php']) ? ' aria-current="page"' : '' ?>>Pre-Owned Cars</a>
    <a href="services.php" class="<?= $isCurrentNavPage(['services.php', 'testdrive.php', 'carservice.php']) ? 'active' : '' ?>"<?= $isCurrentNavPage(['services.php', 'testdrive.php', 'carservice.php']) ? ' aria-current="page"' : '' ?>>Services</a>
    <a href="configrator.php" class="<?= $isCurrentNavPage(['configrator.php']) ? 'active' : '' ?>"<?= $isCurrentNavPage(['configrator.php']) ? ' aria-current="page"' : '' ?>>Configure &amp; Price</a>
    <a href="models.php" class="<?= $isCurrentNavPage(['more bmw.php']) ? 'active' : '' ?>"<?= $isCurrentNavPage(['more bmw.php']) ? ' aria-current="page"' : '' ?>>Explore</a>
    <a href="customer support.php" class="<?= $isCurrentNavPage(['customer support.php', 'feedback.php']) ? 'active' : '' ?>"<?= $isCurrentNavPage(['customer support.php', 'feedback.php']) ? ' aria-current="page"' : '' ?>>Contact Us</a>
    <a href="visit online shop.php" class="<?= $isCurrentNavPage(['visit online shop.php']) ? 'active' : '' ?>"<?= $isCurrentNavPage(['visit online shop.php']) ? ' aria-current="page"' : '' ?>>Buy Now</a>
  </div>

  <div class="nav-right">
    <a href="testdrive.php" class="btn-test-drive-nav">Book Test Drive</a>
    <?php if ($isLoggedIn): ?>
      <div class="notification-widget customer-notification-widget mobile-notification-widget" data-notification-api="notifications-api.php" data-notification-scope="customer" data-notification-csrf="<?= e(app_csrf_token()) ?>">
        <button class="notification-trigger" type="button" aria-label="Notifications<?= $notificationUnread > 0 ? ', ' . $notificationUnread . ' unread' : '' ?>" aria-expanded="false"><i class="fa-regular fa-bell" aria-hidden="true"></i><span class="notification-badge"<?= $notificationUnread < 1 ? ' hidden' : '' ?>><?= $notificationUnread > 99 ? '99+' : $notificationUnread ?></span></button>
        <section class="notification-popover" aria-label="Recent notifications" hidden><header><strong>Notifications</strong><button type="button" class="notification-mark-all">Mark all as read</button></header><div class="notification-items" aria-live="polite"><p class="notification-loading">Loading notifications…</p></div><footer><a href="notifications.php">View all notifications</a></footer></section>
      </div>
    <?php endif; ?>
    <div class="nav-profile-dropdown">
      <button class="nav-profile-trigger" type="button" aria-label="Account menu" aria-haspopup="true">
        <?php if ($isLoggedIn): ?>
          <span class="nav-user-avatar" aria-hidden="true"><?= e($initial) ?></span>
          <span class="nav-user-name"><?= e($userName !== '' ? $userName : 'Account') ?></span>
        <?php else: ?>
          <i class="far fa-user-circle" aria-hidden="true"></i>
        <?php endif; ?>
      </button>
      <div class="nav-profile-menu">
        <?php if ($isLoggedIn): ?>
          <div class="nav-account-summary">
            <span class="nav-account-avatar" aria-hidden="true"><?= e($initial) ?></span>
            <span class="nav-account-identity"><strong><?= e($userName !== '' ? $userName : 'Account') ?></strong><small><?= e($userEmail) ?></small></span>
          </div>
          <a href="account.php"><i class="fa-regular fa-user"></i> My Profile</a>
          <a href="testdrive.php"><i class="fa-solid fa-car"></i> Book Test Drive</a>
          <a href="carservice.php"><i class="fa-solid fa-screwdriver-wrench"></i> Request Car Service</a>
          <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
        <?php else: ?>
          <a href="loginform.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
          <a href="register.php"><i class="fa-solid fa-user-plus"></i> Register</a>
          <a href="testdrive.php"><i class="fa-solid fa-car"></i> Book Test Drive</a>
          <a href="carservice.php"><i class="fa-solid fa-screwdriver-wrench"></i> Request Car Service</a>
        <?php endif; ?>
        <a href="adminlogin.php"><i class="fa-solid fa-shield-halved"></i> Department Portal</a>
      </div>
    </div>

    <button class="burger-menu" type="button" aria-label="Open Full Menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
  <a href="home.php" class="nav-roundel" aria-label="BMW home">
    <img src="../images/BMW logo.svg" alt="BMW" />
  </a>
  </div>
</nav>
<?php if ($isLoggedIn): ?><script src="../js/notifications.js?v=20260926-1" defer></script><?php endif; ?>
