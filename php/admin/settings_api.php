<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
admin_ensure_schema($conn);
admin_require_login();
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(['success' => true, 'settings' => admin_settings($conn)]);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !admin_verify_csrf()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid settings request.']);
        exit;
    }
    admin_settings_save($conn, $_POST);
    echo json_encode(['success' => true, 'message' => 'Settings updated successfully.', 'settings' => admin_settings($conn)]);
} catch (Throwable $error) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
}
