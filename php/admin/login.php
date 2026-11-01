<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../adminlogin.php');
    exit;
}

try {
    if (!app_verify_csrf()) { throw new RuntimeException('invalid'); }
    admin_ensure_schema($conn);
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        throw new InvalidArgumentException('invalid');
    }

    $statement = $conn->prepare('SELECT `id`, `name`, `email`, `password` FROM `admin_users` WHERE `email` = ? LIMIT 1');
    $statement->bind_param('s', $email);
    $statement->execute();
    $admin = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$admin || !password_verify($password, $admin['password'])) {
        throw new InvalidArgumentException('invalid');
    }

    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = (int) $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    header('Location: admin_dashboard.php');
    exit;
} catch (Throwable $error) {
    header('Location: ../adminlogin.php?error=invalid');
    exit;
}
