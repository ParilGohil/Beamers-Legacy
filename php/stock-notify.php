<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId < 1) {
    $destination = 'visit online shop.php';
    header('Location: loginform.php?return_to=' . rawurlencode($destination));
    exit;
}
if (!app_verify_csrf()) {
    http_response_code(419);
    exit('Your session token expired. Return to the shop and try again.');
}

$productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($productId === false) {
    http_response_code(400);
    exit('Product selection is invalid.');
}

$conn = db();
app_ensure_notification_schema($conn);
$statement = $conn->prepare("SELECT `slug`,`stock`,`status` FROM `store_products` WHERE `id` = ? AND `status` IN ('active','out_of_stock') LIMIT 1");
$statement->bind_param('i', $productId);
$statement->execute();
$product = $statement->get_result()->fetch_assoc();
$statement->close();

if (!$product) {
    http_response_code(404);
    exit('This product is not available for stock alerts.');
}
if ((int)$product['stock'] > 0 && $product['status'] === 'active') {
    $_SESSION['stock_notice_flash'] = 'This product is back in stock now.';
} else {
    $subscribe = $conn->prepare('INSERT INTO `stock_notifications` (`user_id`,`product_id`) VALUES (?,?) ON DUPLICATE KEY UPDATE `notified_at` = NULL');
    $subscribe->bind_param('ii', $userId, $productId);
    $subscribe->execute();
    $subscribe->close();
    $_SESSION['stock_notice_flash'] = 'We will notify you when this product is back in stock.';
}

header('Location: visit online shop.php?product=' . rawurlencode((string)$product['slug']) . '#product-' . $productId);
exit;
