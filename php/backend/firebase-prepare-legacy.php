<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/firebase.php';

start_app_session();
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new FirebaseSyncException('Use the sign-in form to continue.', 405);
    }
    if (!app_verify_csrf()) {
        throw new FirebaseSyncException('Your session token expired. Refresh the page and try again.', 403);
    }
    $linked = firebase_prepare_legacy_link(
        (string)($_POST['email'] ?? ''),
        (string)($_POST['password'] ?? '')
    );
    echo json_encode(['ok' => true, 'legacy_link_authorized' => $linked]);
} catch (Throwable $error) {
    $status = $error instanceof FirebaseSyncException
        ? $error->statusCode
        : ($error instanceof mysqli_sql_exception ? 503 : 500);
    http_response_code($status);
    error_log('Legacy customer Firebase linking preparation failed: ' . $error->getMessage());
    echo json_encode(['ok' => false, 'message' => $error->getMessage()]);
}
