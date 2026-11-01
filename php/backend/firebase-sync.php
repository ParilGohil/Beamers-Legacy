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

    $firebaseUser = firebase_lookup_id_token(trim((string)($_POST['idToken'] ?? '')));
    $user = firebase_sync_customer($firebaseUser, [
        'first_name' => (string)($_POST['first_name'] ?? ''),
        'last_name' => (string)($_POST['last_name'] ?? ''),
    ]);
    app_set_authenticated_user($user);
    $_SESSION['firebase_email_verified'] = ($firebaseUser['emailVerified'] ?? false) === true;

    echo json_encode(['ok' => true, 'redirect' => app_auth_destination($_POST['return_to'] ?? '')]);
} catch (Throwable $error) {
    $status = $error instanceof FirebaseSyncException
        ? $error->statusCode
        : ($error instanceof mysqli_sql_exception ? 503 : 500);
    http_response_code($status);
    error_log('Customer Firebase session creation failed: ' . $error->getMessage());
    echo json_encode(['ok' => false, 'message' => $error->getMessage()]);
}
