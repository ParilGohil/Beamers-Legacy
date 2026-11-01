<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function notification_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$adminScope = (($_GET['scope'] ?? $_POST['scope'] ?? '') === 'admin');
$recipientType = $adminScope ? 'admin' : 'customer';
$recipientId = (int)($_SESSION[$adminScope ? 'admin_user_id' : 'user_id'] ?? 0);
if ($recipientId < 1) {
    notification_json(['error' => 'Authentication required.'], 401);
}
if ($adminScope && empty($_SESSION['admin_user_id'])) {
    notification_json(['error' => 'Administrator access required.'], 403);
}

try {
    $conn = db();
    app_ensure_car_catalog_schema($conn);
    app_ensure_notification_schema($conn);
    $ownerColumn = $adminScope ? 'admin_id' : 'user_id';
    $ownerId = $recipientId;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!app_verify_csrf()) {
            notification_json(['error' => 'Your session expired. Refresh and try again.'], 419);
        }
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        if ($action === 'mark_all') {
            $statement = $conn->prepare("UPDATE `notifications` SET `is_read` = 1, `read_at` = CURRENT_TIMESTAMP WHERE `recipient_type` = ? AND `{$ownerColumn}` = ? AND `is_read` = 0");
            $statement->bind_param('si', $recipientType, $ownerId);
        } elseif (in_array($action, ['mark_read', 'delete'], true)) {
            $notificationId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($notificationId === false) {
                notification_json(['error' => 'Notification ID is invalid.'], 400);
            }
            if ($action === 'mark_read') {
                $statement = $conn->prepare("UPDATE `notifications` SET `is_read` = 1, `read_at` = CURRENT_TIMESTAMP WHERE `id` = ? AND `recipient_type` = ? AND `{$ownerColumn}` = ? AND `is_read` = 0");
            } else {
                $statement = $conn->prepare("DELETE FROM `notifications` WHERE `id` = ? AND `recipient_type` = ? AND `{$ownerColumn}` = ?");
            }
            $statement->bind_param('isi', $notificationId, $recipientType, $ownerId);
        } else {
            notification_json(['error' => 'Unsupported notification action.'], 400);
        }
        $statement->execute();
        $affected = $statement->affected_rows;
        $statement->close();
        notification_json(['ok' => true, 'changed' => $affected]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        notification_json(['error' => 'Method not allowed.'], 405);
    }
    $action = is_string($_GET['action'] ?? null) ? $_GET['action'] : 'list';
    if ($action === 'count') {
        $statement = $conn->prepare("SELECT COUNT(*) AS `count` FROM `notifications` WHERE `recipient_type` = ? AND `{$ownerColumn}` = ? AND `is_read` = 0");
        $statement->bind_param('si', $recipientType, $ownerId);
        $statement->execute();
        $count = (int)$statement->get_result()->fetch_assoc()['count'];
        $statement->close();
        notification_json(['unread' => $count]);
    }
    if ($action !== 'list') {
        notification_json(['error' => 'Unsupported notification action.'], 400);
    }

    $limit = filter_var($_GET['limit'] ?? 20, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]) ?: 20;
    $onlyUnread = ($_GET['unread'] ?? '') === '1';
    $where = "`recipient_type` = ? AND `{$ownerColumn}` = ?";
    $types = 'si';
    $parameters = [$recipientType, $ownerId];
    if ($onlyUnread) {
        $where .= ' AND `is_read` = 0';
    }
    $search = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
    if ($search !== '') {
        $search = mb_substr($search, 0, 80);
        $where .= ' AND (`title` LIKE ? OR `message` LIKE ? OR `type` LIKE ?)';
        $like = '%' . $search . '%';
        $types .= 'sss';
        array_push($parameters, $like, $like, $like);
    }

    $sql = "SELECT n.`id`,n.`type`,n.`title`,n.`message`,n.`reference_id`,n.`reference_type`,n.`is_read`,n.`created_at`,
            c.`slug` AS `reference_slug`
        FROM `notifications` n
        LEFT JOIN `cars` c ON n.`reference_type` = 'car' AND c.`id` = n.`reference_id`
        WHERE {$where} ORDER BY n.`created_at` DESC,n.`id` DESC LIMIT ?";
    $types .= 'i';
    $parameters[] = $limit;
    $statement = $conn->prepare($sql);
    $statement->bind_param($types, ...$parameters);
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    foreach ($rows as &$row) {
        $row['url'] = app_notification_url($row, $adminScope);
    }
    unset($row);

    $countStatement = $conn->prepare("SELECT COUNT(*) AS `count` FROM `notifications` WHERE `recipient_type` = ? AND `{$ownerColumn}` = ? AND `is_read` = 0");
    $countStatement->bind_param('si', $recipientType, $ownerId);
    $countStatement->execute();
    $unreadCount = (int)$countStatement->get_result()->fetch_assoc()['count'];
    $countStatement->close();
    notification_json(['notifications' => $rows, 'unread' => $unreadCount]);
} catch (Throwable $error) {
    error_log('Notification API request failed: ' . $error->getMessage());
    notification_json(['error' => 'Notifications are temporarily unavailable.'], 500);
}
