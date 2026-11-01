<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

try {
    admin_ensure_schema($conn);
    admin_require_login();
    app_ensure_notification_schema($conn);
} catch (Throwable $error) {
    http_response_code(500);
    exit('Notifications could not be loaded. Please try again later.');
}

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!admin_verify_csrf()) {
            throw new RuntimeException('Your session expired. Refresh the page and try again.');
        }
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'mark_read') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                throw new InvalidArgumentException('The notification is invalid.');
            }
            $statement = $conn->prepare('UPDATE `notifications` SET `is_read` = 1, `read_at` = CURRENT_TIMESTAMP WHERE `id` = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
            $statement->close();
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                throw new InvalidArgumentException('The notification is invalid.');
            }
            $statement = $conn->prepare('DELETE FROM `notifications` WHERE `id` = ?');
            $statement->bind_param('i', $id);
            $statement->execute();
            $statement->close();
        } elseif ($action === 'mark_all') {
            $conn->query('UPDATE `notifications` SET `is_read` = 1, `read_at` = CURRENT_TIMESTAMP WHERE `is_read` = 0');
        } elseif ($action === 'save_preferences') {
            $categories = ['test_drives', 'service_requests', 'sales', 'registrations', 'feedback', 'stock'];
            $upsert = $conn->prepare('INSERT INTO `notification_preferences` (`category`,`enabled`) VALUES (?,?) ON DUPLICATE KEY UPDATE `enabled` = VALUES(`enabled`)');
            foreach ($categories as $category) {
                $enabled = isset($_POST['preferences'][$category]) ? 1 : 0;
                $upsert->bind_param('si', $category, $enabled);
                $upsert->execute();
            }
            $upsert->close();
        } else {
            throw new InvalidArgumentException('Unsupported notification action.');
        }
        header('Location: notifications.php?updated=1');
        exit;
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}

$search = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
$search = mb_substr($search, 0, 80);
$selectedType = is_string($_GET['type'] ?? null) ? $_GET['type'] : '';
$readFilter = is_string($_GET['read'] ?? null) ? $_GET['read'] : 'all';
$where = [];
$types = '';
$parameters = [];
if ($search !== '') {
    $where[] = '(n.`title` LIKE ? OR n.`message` LIKE ? OR n.`type` LIKE ? OR r.`E-mail` LIKE ? OR a.`email` LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'sssss';
    array_push($parameters, $like, $like, $like, $like, $like);
}
if ($selectedType !== '') {
    $where[] = 'n.`type` = ?';
    $types .= 's';
    $parameters[] = $selectedType;
}
if ($readFilter === 'unread') {
    $where[] = 'n.`is_read` = 0';
} elseif ($readFilter === 'read') {
    $where[] = 'n.`is_read` = 1';
}
$query = "SELECT n.*, COALESCE(NULLIF(CONCAT_WS(' ',r.`First_Name`,r.`Last_Name`),''), a.`name`, 'Unknown recipient') AS recipient_name,
        COALESCE(r.`E-mail`,a.`email`,'') AS recipient_email
    FROM `notifications` n
    LEFT JOIN `register` r ON n.`recipient_type` = 'customer' AND r.`id` = n.`user_id`
    LEFT JOIN `admin_users` a ON n.`recipient_type` = 'admin' AND a.`id` = n.`admin_id`";
if ($where) {
    $query .= ' WHERE ' . implode(' AND ', $where);
}
$query .= ' ORDER BY n.`created_at` DESC,n.`id` DESC LIMIT 100';
$statement = $conn->prepare($query);
if ($parameters) {
    $statement->bind_param($types, ...$parameters);
}
$statement->execute();
$notifications = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();
$typesResult = $conn->query('SELECT DISTINCT `type` FROM `notifications` ORDER BY `type`')->fetch_all(MYSQLI_ASSOC);
$preferences = [];
$preferencesResult = $conn->query('SELECT `category`,`enabled` FROM `notification_preferences`');
while ($row = $preferencesResult->fetch_assoc()) {
    $preferences[(string)$row['category']] = (int)$row['enabled'] === 1;
}
$csrf = admin_csrf_token();
$totalCount = (int)$conn->query('SELECT COUNT(*) AS count FROM `notifications`')->fetch_assoc()['count'];
$unreadCount = (int)$conn->query('SELECT COUNT(*) AS count FROM `notifications` WHERE `is_read` = 0')->fetch_assoc()['count'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Notifications | Beamers Legacy Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="../../css/admin.css?v=20260926-notifications-1">
</head>
<body>
<div class="admin-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="admin_dashboard.php"><img src="../../images/BMW logo.svg" alt="BMW logo"><span><strong>BEAMERS</strong><small>LEGACY / ADMIN</small></span></a>
    <nav class="sidebar-nav" aria-label="Dashboard sections">
      <a href="admin_dashboard.php"><i class="fa-solid fa-grid-2"></i>Overview</a>
      <a href="admin_dashboard.php?section=customers"><i class="fa-solid fa-users"></i>Customers</a>
      <a href="admin_dashboard.php?section=test-drives"><i class="fa-solid fa-car-side"></i>Test drives</a>
      <a href="admin_dashboard.php?section=car-services"><i class="fa-solid fa-screwdriver-wrench"></i>Car service</a>
      <a href="admin_dashboard.php?section=sales"><i class="fa-solid fa-handshake"></i>Sales bookings</a>
      <a href="admin_dashboard.php?section=feedback"><i class="fa-solid fa-message"></i>Feedback</a>
      <a href="admin_dashboard.php?section=cars"><i class="fa-solid fa-car"></i>BMW Cars</a>
      <a class="active" href="notifications.php"><i class="fa-regular fa-bell"></i>Notifications <b><?= $unreadCount ?></b></a>
      <a href="store.php"><i class="fa-solid fa-bag-shopping"></i>BMW Store</a>
    </nav>
    <div class="sidebar-footer"><a href="../home.php"><i class="fa-solid fa-arrow-up-right-from-square"></i>View website</a><a href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a><a href="logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Sign out</a></div>
  </aside>
  <main class="main-content">
    <header class="topbar">
      <button class="menu-toggle" type="button" aria-label="Open navigation" aria-controls="sidebar"><i class="fa-solid fa-bars"></i></button>
      <div class="breadcrumb"><span>Control centre</span><i class="fa-solid fa-chevron-right"></i><strong>Notifications</strong></div>
      <div class="admin-topbar-actions">
        <div class="notification-widget admin-notification-widget" data-notification-api="../notifications-api.php" data-notification-scope="admin" data-notification-csrf="<?= admin_escape(app_csrf_token()) ?>">
          <button class="notification-trigger" type="button" aria-label="Admin notifications" aria-expanded="false"><i class="fa-regular fa-bell"></i><span class="notification-badge" hidden></span></button>
          <section class="notification-popover" aria-label="Recent notifications" hidden><header><strong>Notifications</strong><button type="button" class="notification-mark-all">Mark all as read</button></header><div class="notification-items" aria-live="polite"><p class="notification-loading">Loading notifications…</p></div><footer><a href="notifications.php">View all notifications</a></footer></section>
        </div>
        <div class="admin-profile"><span class="profile-mark"><?= admin_escape(strtoupper(substr((string)$_SESSION['admin_name'], 0, 1))) ?></span><span><strong><?= admin_escape((string)$_SESSION['admin_name']) ?></strong><small>Administrator</small></span></div>
      </div>
    </header>
    <section class="page-content">
      <?php if ($errorMessage !== ''): ?><div class="notice error" role="alert"><?= admin_escape($errorMessage) ?></div><?php elseif (isset($_GET['updated'])): ?><div class="notice success" role="status">Notification settings updated.</div><?php endif; ?>
      <div class="management-heading"><div><p class="eyebrow">SHOWROOM ACTIVITY</p><h1>Notifications</h1><p>Review customer and administrator alerts. Showing the latest 100 matching records.</p></div><form method="post"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="mark_all"><button class="secondary-button" type="submit">Mark all read</button></form></div>
      <section class="panel notification-settings-panel">
        <div class="panel-heading"><div><p class="eyebrow">ALERT CONTROLS</p><h2>Notification categories</h2></div></div>
        <form method="post" class="notification-preferences-form">
          <input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="save_preferences">
          <?php foreach (['test_drives'=>'Test-drive updates','service_requests'=>'Car-service requests','sales'=>'Sales bookings','registrations'=>'New registrations','feedback'=>'Customer feedback','stock'=>'Store stock updates'] as $key=>$label): ?>
            <label><input type="checkbox" name="preferences[<?= admin_escape($key) ?>]" value="1" <?= !empty($preferences[$key]) ? 'checked' : '' ?>><span><?= admin_escape($label) ?></span></label>
          <?php endforeach; ?>
          <button class="primary-button" type="submit">Save preferences</button>
        </form>
      </section>
      <section class="panel notification-management-panel">
        <form class="notification-filter-form" method="get">
          <label>Search<input type="search" name="q" value="<?= admin_escape($search) ?>" placeholder="Title, message, customer or email"></label>
          <label>Type<select name="type"><option value="">All types</option><?php foreach ($typesResult as $typeRow): ?><option value="<?= admin_escape((string)$typeRow['type']) ?>" <?= $selectedType === $typeRow['type'] ? 'selected' : '' ?>><?= admin_escape(ucwords(strtolower(str_replace('_',' ',(string)$typeRow['type'])))) ?></option><?php endforeach; ?></select></label>
          <label>Read state<select name="read"><option value="all" <?= $readFilter === 'all' ? 'selected' : '' ?>>All</option><option value="unread" <?= $readFilter === 'unread' ? 'selected' : '' ?>>Unread</option><option value="read" <?= $readFilter === 'read' ? 'selected' : '' ?>>Read</option></select></label>
          <button class="secondary-button" type="submit">Apply filters</button>
        </form>
        <div class="notification-admin-list">
          <?php foreach ($notifications as $notification): ?>
            <article class="notification-admin-item<?= (int)$notification['is_read'] === 0 ? ' is-unread' : '' ?>">
              <div class="notification-admin-copy"><div><strong><?= admin_escape($notification['title']) ?></strong><span class="notification-type-pill"><?= admin_escape((string)$notification['type']) ?></span></div><p><?= admin_escape($notification['message']) ?></p><small>To <?= admin_escape($notification['recipient_name']) ?><?= $notification['recipient_email'] !== '' ? ' · ' . admin_escape($notification['recipient_email']) : '' ?> · <?= admin_escape(date('d M Y, g:i A', strtotime((string)$notification['created_at']))) ?></small></div>
              <div class="notification-admin-actions">
                <?php if ((int)$notification['is_read'] === 0): ?><form method="post"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?= (int)$notification['id'] ?>"><button class="icon-button" type="submit" aria-label="Mark notification as read"><i class="fa-solid fa-check"></i></button></form><?php endif; ?>
                <form method="post" class="delete-form"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$notification['id'] ?>"><button class="icon-button danger" type="submit" aria-label="Delete notification"><i class="fa-solid fa-trash-can"></i></button></form>
              </div>
            </article>
          <?php endforeach; ?>
          <?php if (!$notifications): ?><div class="empty-state"><i class="fa-regular fa-bell"></i><h3>No notifications found</h3><p>Try a different search or filter.</p></div><?php endif; ?>
        </div>
      </section>
    </section>
  </main>
</div>
<script src="../../js/admin.js"></script><script src="../../js/notifications.js?v=20260926-1" defer></script>
</body>
</html>
