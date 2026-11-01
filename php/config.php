<?php
declare(strict_types=1);

/*
 * Beamers Legacy / BMW Showroom
 * Central configuration for MySQL + Firebase Authentication.
 *
 * For XAMPP:
 *   DB_HOST=localhost
 *   DB_NAME=bmw
 *   DB_USER=root
 *   DB_PASS=
 *
 * Firebase is used for customer/admin authentication.
 * Put your Firebase Web API key here after creating the Firebase project.
 */
const DB_HOST = 'localhost';
const DB_NAME = 'bmw';
const DB_USER = 'root';
const DB_PASS = '';

const FIREBASE_API_KEY = 'AIzaSyBOExkFVOxuitTwgQ-jRzDzsCtOFUw6Knc';
function db(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
    return $conn;
}

function app_ensure_customer_profile_schema(mysqli $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `register`");
    while ($column = $result->fetch_assoc()) {
        $columns[(string)$column['Field']] = true;
    }

    $profileColumns = [
        'About' => "ALTER TABLE `register` ADD COLUMN `About` VARCHAR(500) NULL",
        'Website' => "ALTER TABLE `register` ADD COLUMN `Website` VARCHAR(255) NULL",
        'Username' => "ALTER TABLE `register` ADD COLUMN `Username` VARCHAR(50) NULL",
        'Firebase_UID' => "ALTER TABLE `register` ADD COLUMN `Firebase_UID` VARCHAR(128) NULL",
        'Photo_URL' => "ALTER TABLE `register` ADD COLUMN `Photo_URL` VARCHAR(500) NULL",
        'Auth_Provider' => "ALTER TABLE `register` ADD COLUMN `Auth_Provider` VARCHAR(100) NULL",
    ];
    foreach ($profileColumns as $column => $sql) {
        if (!isset($columns[$column])) {
            $conn->query($sql);
        }
    }
    $indexes = $conn->query("SHOW INDEX FROM `register`");
    $hasFirebaseUidIndex = false;
    while ($index = $indexes->fetch_assoc()) {
        if ((string)$index['Key_name'] === 'uniq_register_firebase_uid') {
            $hasFirebaseUidIndex = true;
            break;
        }
    }
    if (!$hasFirebaseUidIndex) {
        $conn->query("ALTER TABLE `register` ADD UNIQUE KEY `uniq_register_firebase_uid` (`Firebase_UID`)");
    }
    $checked = true;
}

function start_app_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', '1');
        session_set_cookie_params([
            'httponly' => true,
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function app_set_authenticated_user(array $user): void
{
    start_app_session();
    $userId = (int)($user['id'] ?? 0);
    if ($userId < 1) {
        throw new InvalidArgumentException('A valid customer record is required to start a session.');
    }

    session_regenerate_id(true);
    $firstName = trim((string)($user['First_Name'] ?? ''));
    $lastName = trim((string)($user['Last_Name'] ?? ''));
    $email = trim((string)($user['E-mail'] ?? $user['email'] ?? ''));
    $_SESSION['user_id'] = $userId;
    $_SESSION['email'] = $email;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;
    $_SESSION['user_name'] = trim($firstName . ' ' . $lastName);
    $_SESSION['full_name'] = trim($firstName . ' ' . $lastName);
    $firebaseUid = trim((string)($user['Firebase_UID'] ?? $user['firebase_uid'] ?? ''));
    $photoUrl = trim((string)($user['Photo_URL'] ?? $user['photo_url'] ?? ''));
    $provider = trim((string)($user['Auth_Provider'] ?? $user['auth_provider'] ?? ''));
    if ($firebaseUid !== '') {
        $_SESSION['firebase_uid'] = $firebaseUid;
    }
    if ($photoUrl !== '' && strtolower((string)parse_url($photoUrl, PHP_URL_SCHEME)) === 'https') {
        $_SESSION['profile_photo_url'] = $photoUrl;
    } else {
        unset($_SESSION['profile_photo_url']);
    }
    if ($provider !== '') {
        $_SESSION['auth_provider'] = $provider;
    }
}

function app_verify_customer_password(mysqli $conn, array $user, string $password): bool
{
    $storedPassword = (string)($user['Password'] ?? '');
    $isHash = password_get_info($storedPassword)['algoName'] !== 'unknown';
    $valid = password_verify($password, $storedPassword)
        || (!$isHash && $storedPassword !== '' && hash_equals($storedPassword, $password));
    if (!$valid) {
        return false;
    }

    if (!$isHash || password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $userId = (int)($user['id'] ?? 0);
        if ($userId < 1) {
            throw new InvalidArgumentException('A valid customer record is required.');
        }
        $statement = $conn->prepare('UPDATE `register` SET `Password` = ? WHERE `id` = ?');
        $statement->bind_param('si', $newHash, $userId);
        $statement->execute();
        $statement->close();
    }
    return true;
}

function app_auth_destination($destination, string $default = 'home.php'): string
{
    if (!is_string($destination) || $destination === '' || strlen($destination) > 500) {
        return $default;
    }
    if (preg_match('/[\x00-\x1F\x7F]/', $destination) || str_contains($destination, '\\') || str_starts_with($destination, '/') || str_starts_with($destination, '//')) {
        return $default;
    }

    $parts = parse_url($destination);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
        return $default;
    }
    $path = (string)($parts['path'] ?? '');
    if ($path === '' || basename($path) !== $path || !preg_match('/^[A-Za-z0-9 _.-]+\.php$/', $path) || str_contains($path, '..')) {
        return $default;
    }
    return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
}

function app_destroy_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        start_app_session();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function app_csrf_token(): string
{
    start_app_session();
    if (empty($_SESSION['app_csrf'])) {
        $_SESSION['app_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['app_csrf'];
}

function app_verify_csrf(): bool
{
    return isset($_POST['csrf'], $_SESSION['app_csrf'])
        && is_string($_POST['csrf'])
        && is_string($_SESSION['app_csrf'])
        && hash_equals($_SESSION['app_csrf'], $_POST['csrf']);
}

function app_ensure_notification_schema(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `recipient_type` ENUM('customer','admin') NOT NULL,
        `user_id` INT UNSIGNED NULL,
        `admin_id` INT UNSIGNED NULL,
        `type` VARCHAR(60) NOT NULL,
        `title` VARCHAR(180) NOT NULL,
        `message` VARCHAR(1000) NOT NULL,
        `reference_id` INT UNSIGNED NULL,
        `reference_type` VARCHAR(40) NULL,
        `dedupe_key` VARCHAR(191) NULL UNIQUE,
        `is_read` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `read_at` TIMESTAMP NULL DEFAULT NULL,
        INDEX `idx_notifications_customer_unread` (`recipient_type`,`user_id`,`is_read`,`created_at`),
        INDEX `idx_notifications_admin_unread` (`recipient_type`,`admin_id`,`is_read`,`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `notification_preferences` (
        `category` VARCHAR(40) NOT NULL PRIMARY KEY,
        `enabled` TINYINT(1) NOT NULL DEFAULT 1,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("CREATE TABLE IF NOT EXISTS `stock_notifications` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `product_id` INT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `notified_at` TIMESTAMP NULL DEFAULT NULL,
        UNIQUE KEY `uniq_stock_notification_subscriber` (`user_id`,`product_id`),
        INDEX `idx_stock_notification_product` (`product_id`,`notified_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $conn->query("INSERT IGNORE INTO `notification_preferences` (`category`,`enabled`) VALUES
        ('test_drives',1),('service_requests',1),('sales',1),('registrations',1),('feedback',1),('stock',1)");

    $driveColumns = [];
    $result = $conn->query("SHOW COLUMNS FROM `test drive`");
    while ($column = $result->fetch_assoc()) {
        $driveColumns[(string)$column['Field']] = true;
    }
    $addColumns = [
        'Customer_ID' => 'INT UNSIGNED NULL',
        'Status' => "ENUM('pending','accepted','rejected','rescheduled','cancelled') NOT NULL DEFAULT 'pending'",
        'Appointment_Date' => 'DATE NULL',
        'Appointment_Time' => 'TIME NULL',
    ];
    foreach ($addColumns as $column => $definition) {
        if (!isset($driveColumns[$column])) {
            $conn->query("ALTER TABLE `test drive` ADD COLUMN `{$column}` {$definition}");
        }
    }

    $hasCustomerIndex = false;
    $indexes = $conn->query("SHOW INDEX FROM `test drive`");
    while ($index = $indexes->fetch_assoc()) {
        if ((string)$index['Column_name'] === 'Customer_ID') {
            $hasCustomerIndex = true;
            break;
        }
    }
    if (!$hasCustomerIndex) {
        $conn->query("ALTER TABLE `test drive` ADD KEY `idx_test_drive_customer_id` (`Customer_ID`)");
    }
    $conn->query("UPDATE `test drive` d INNER JOIN `register` r ON LOWER(d.`E-Mail`) = LOWER(r.`E-mail`) SET d.`Customer_ID` = r.`id` WHERE d.`Customer_ID` IS NULL");
    $ensured = true;
}

function app_notification_enabled(mysqli $conn, string $category): bool
{
    $statement = $conn->prepare('SELECT `enabled` FROM `notification_preferences` WHERE `category` = ? LIMIT 1');
    $statement->bind_param('s', $category);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $statement->close();
    return $row === null || (int)$row['enabled'] === 1;
}

function app_create_notification(
    mysqli $conn,
    string $recipientType,
    int $recipientId,
    string $type,
    string $title,
    string $message,
    ?int $referenceId = null,
    ?string $referenceType = null,
    ?string $dedupeKey = null
): void {
    if (!in_array($recipientType, ['customer', 'admin'], true) || $recipientId < 1) {
        throw new InvalidArgumentException('Notification recipient is invalid.');
    }
    $userId = $recipientType === 'customer' ? $recipientId : null;
    $adminId = $recipientType === 'admin' ? $recipientId : null;
    $statement = $conn->prepare("INSERT IGNORE INTO `notifications`
        (`recipient_type`,`user_id`,`admin_id`,`type`,`title`,`message`,`reference_id`,`reference_type`,`dedupe_key`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $statement->bind_param('siisssiss', $recipientType, $userId, $adminId, $type, $title, $message, $referenceId, $referenceType, $dedupeKey);
    $statement->execute();
    $statement->close();
}

function app_notify_admins(
    mysqli $conn,
    string $category,
    string $type,
    string $title,
    string $message,
    ?int $referenceId = null,
    ?string $referenceType = null,
    ?string $dedupePrefix = null
): void {
    if (!app_notification_enabled($conn, $category)) {
        return;
    }
    $admins = $conn->query('SELECT `id` FROM `admin_users`')->fetch_all(MYSQLI_ASSOC);
    foreach ($admins as $admin) {
        $adminId = (int)$admin['id'];
        app_create_notification(
            $conn,
            'admin',
            $adminId,
            $type,
            $title,
            $message,
            $referenceId,
            $referenceType,
            $dedupePrefix === null ? null : $dedupePrefix . ':' . $adminId
        );
    }
}

function app_notify_product_stock_change(mysqli $conn, int $productId, bool $wasAvailable, bool $isAvailable, string $productName): void
{
    if ($wasAvailable === $isAvailable) {
        return;
    }

    if (!$isAvailable) {
        $statement = $conn->prepare('UPDATE `stock_notifications` SET `notified_at` = NULL WHERE `product_id` = ?');
        $statement->bind_param('i', $productId);
        $statement->execute();
        $statement->close();
        return;
    }

    if (!app_notification_enabled($conn, 'stock')) {
        return;
    }

    $statement = $conn->prepare('SELECT `user_id` FROM `stock_notifications` WHERE `product_id` = ? AND `notified_at` IS NULL FOR UPDATE');
    $statement->bind_param('i', $productId);
    $statement->execute();
    $subscribers = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();

    $markNotified = $conn->prepare('UPDATE `stock_notifications` SET `notified_at` = CURRENT_TIMESTAMP WHERE `user_id` = ? AND `product_id` = ? AND `notified_at` IS NULL');
    foreach ($subscribers as $subscriber) {
        $userId = (int)$subscriber['user_id'];
        app_create_notification(
            $conn,
            'customer',
            $userId,
            'PRODUCT_BACK_IN_STOCK',
            'Back in stock',
            $productName . ' is available again.',
            $productId,
            'product'
        );
        $markNotified->bind_param('ii', $userId, $productId);
        $markNotified->execute();
    }
    $markNotified->close();
}

function app_notification_url(array $notification, bool $admin = false): string
{
    $referenceId = (int)($notification['reference_id'] ?? 0);
    $referenceType = (string)($notification['reference_type'] ?? '');
    if ($admin && $referenceType === 'test_drive' && $referenceId > 0) {
        return 'admin_dashboard.php?section=test-drives#request-' . $referenceId;
    }
    if ($admin && $referenceType === 'car_service' && $referenceId > 0) {
        return 'admin_dashboard.php?section=car-services#service-request-' . $referenceId;
    }
    if ($admin && $referenceType === 'sales_booking' && $referenceId > 0) {
        return 'admin_dashboard.php?section=sales#sales-booking-' . $referenceId;
    }
    if ($admin && $referenceType === 'feedback' && $referenceId > 0) {
        return 'admin_dashboard.php?section=feedback#feedback-' . $referenceId;
    }
    if (!$admin && $referenceType === 'test_drive' && $referenceId > 0) {
        return 'account.php#test-drive-' . $referenceId;
    }
    if (!$admin && $referenceType === 'car_service' && $referenceId > 0) {
        return 'account.php#service-request-' . $referenceId;
    }
    if (!$admin && $referenceType === 'sales_booking' && $referenceId > 0) {
        return 'account.php#sales-booking-' . $referenceId;
    }
    if ($referenceType === 'car' && !empty($notification['reference_slug'])) {
        return 'car-details.php?slug=' . rawurlencode((string)$notification['reference_slug']);
    }
    if ($referenceType === 'product' && $referenceId > 0) {
        return 'visit online shop.php#product-' . $referenceId;
    }
    return $admin ? 'admin/notifications.php' : 'notifications.php';
}

function app_ensure_car_service_schema(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS `car_service_requests` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `Vehicle_Model` VARCHAR(120) NOT NULL,
        `Registration_Number` VARCHAR(30) NOT NULL,
        `Service_Type` VARCHAR(60) NOT NULL,
        `First_Name` VARCHAR(100) NOT NULL,
        `Last_Name` VARCHAR(100) NOT NULL,
        `E-Mail` VARCHAR(191) NOT NULL,
        `Mobile_No` VARCHAR(30) NOT NULL,
        `City` VARCHAR(100) NOT NULL,
        `Preferred_Date` DATE NOT NULL,
        `Preferred_Time` TIME NOT NULL,
        `Notes` TEXT NULL,
        `Customer_ID` INT UNSIGNED NULL,
        `Status` ENUM('pending','accepted','rejected','rescheduled','cancelled') NOT NULL DEFAULT 'pending',
        `Appointment_Date` DATE NULL,
        `Appointment_Time` TIME NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_car_service_customer_id` (`Customer_ID`),
        KEY `idx_car_service_status` (`Status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `car_service_requests`");
    while ($column = $result->fetch_assoc()) {
        $columns[(string)$column['Field']] = true;
    }
    $columnsToAdd = [
        'Vehicle_Model' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'Registration_Number' => "VARCHAR(30) NOT NULL DEFAULT ''",
        'Service_Type' => "VARCHAR(60) NOT NULL DEFAULT 'Periodic maintenance'",
        'First_Name' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'Last_Name' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'E-Mail' => "VARCHAR(191) NOT NULL DEFAULT ''",
        'Mobile_No' => "VARCHAR(30) NOT NULL DEFAULT ''",
        'City' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'Preferred_Date' => 'DATE NULL',
        'Preferred_Time' => 'TIME NULL',
        'Notes' => 'TEXT NULL',
        'Customer_ID' => 'INT UNSIGNED NULL',
        'Status' => "ENUM('pending','accepted','rejected','rescheduled','cancelled') NOT NULL DEFAULT 'pending'",
        'Appointment_Date' => 'DATE NULL',
        'Appointment_Time' => 'TIME NULL',
        'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ];
    foreach ($columnsToAdd as $column => $definition) {
        if (!isset($columns[$column])) {
            $conn->query("ALTER TABLE `car_service_requests` ADD COLUMN `{$column}` {$definition}");
        }
    }

    $indexes = $conn->query("SHOW INDEX FROM `car_service_requests`");
    $hasCustomerIndex = false;
    $hasStatusIndex = false;
    while ($index = $indexes->fetch_assoc()) {
        $hasCustomerIndex = $hasCustomerIndex || (string)$index['Column_name'] === 'Customer_ID';
        $hasStatusIndex = $hasStatusIndex || (string)$index['Column_name'] === 'Status';
    }
    if (!$hasCustomerIndex) {
        $conn->query("ALTER TABLE `car_service_requests` ADD KEY `idx_car_service_customer_id` (`Customer_ID`)");
    }
    if (!$hasStatusIndex) {
        $conn->query("ALTER TABLE `car_service_requests` ADD KEY `idx_car_service_status` (`Status`)");
    }
    $conn->query("UPDATE `car_service_requests` s INNER JOIN `register` r ON LOWER(s.`E-Mail`) = LOWER(r.`E-mail`) SET s.`Customer_ID` = r.`id` WHERE s.`Customer_ID` IS NULL");
    $ensured = true;
}

function app_ensure_sales_booking_schema(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS `car_bookings` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `Car_ID` INT UNSIGNED NULL,
        `Vehicle_Name` VARCHAR(120) NOT NULL,
        `Vehicle_Price` VARCHAR(80) NULL,
        `First_Name` VARCHAR(100) NOT NULL,
        `Last_Name` VARCHAR(100) NOT NULL,
        `E-Mail` VARCHAR(191) NOT NULL,
        `Mobile_No` VARCHAR(30) NOT NULL,
        `City` VARCHAR(100) NOT NULL,
        `Payment_Method` VARCHAR(30) NOT NULL DEFAULT 'finance',
        `Trade_In` VARCHAR(10) NOT NULL DEFAULT 'no',
        `Notes` TEXT NULL,
        `Customer_ID` INT UNSIGNED NULL,
        `Status` ENUM('pending','contacted','reserved','completed','cancelled') NOT NULL DEFAULT 'pending',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_car_bookings_car_id` (`Car_ID`),
        KEY `idx_car_bookings_customer_id` (`Customer_ID`),
        KEY `idx_car_bookings_status` (`Status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `car_bookings`");
    while ($column = $result->fetch_assoc()) {
        $columns[(string)$column['Field']] = true;
    }
    $columnsToAdd = [
        'Car_ID' => 'INT UNSIGNED NULL',
        'Vehicle_Name' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'Vehicle_Price' => 'VARCHAR(80) NULL',
        'First_Name' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'Last_Name' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'E-Mail' => "VARCHAR(191) NOT NULL DEFAULT ''",
        'Mobile_No' => "VARCHAR(30) NOT NULL DEFAULT ''",
        'City' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'Payment_Method' => "VARCHAR(30) NOT NULL DEFAULT 'finance'",
        'Trade_In' => "VARCHAR(10) NOT NULL DEFAULT 'no'",
        'Notes' => 'TEXT NULL',
        'Customer_ID' => 'INT UNSIGNED NULL',
        'Status' => "ENUM('pending','contacted','reserved','completed','cancelled') NOT NULL DEFAULT 'pending'",
        'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ];
    foreach ($columnsToAdd as $column => $definition) {
        if (!isset($columns[$column])) {
            $conn->query("ALTER TABLE `car_bookings` ADD COLUMN `{$column}` {$definition}");
        }
    }

    $indexes = $conn->query("SHOW INDEX FROM `car_bookings`");
    $indexedColumns = [];
    while ($index = $indexes->fetch_assoc()) {
        $indexedColumns[(string)$index['Column_name']] = true;
    }
    foreach (['Car_ID' => 'idx_car_bookings_car_id', 'Customer_ID' => 'idx_car_bookings_customer_id', 'Status' => 'idx_car_bookings_status'] as $column => $indexName) {
        if (!isset($indexedColumns[$column])) {
            $conn->query("ALTER TABLE `car_bookings` ADD KEY `{$indexName}` (`{$column}`)");
        }
    }
    $conn->query("UPDATE `car_bookings` b INNER JOIN `register` r ON LOWER(b.`E-Mail`) = LOWER(r.`E-mail`) SET b.`Customer_ID` = r.`id` WHERE b.`Customer_ID` IS NULL");
    $ensured = true;
}

function app_update_testdrive_status(
    mysqli $conn,
    int $requestId,
    string $nextStatus,
    string $appointmentDate = '',
    string $appointmentTime = ''
): bool {
    if ($requestId < 1 || !in_array($nextStatus, ['accepted', 'rejected', 'rescheduled'], true)) {
        throw new InvalidArgumentException('The selected test-drive status is invalid.');
    }
    if (in_array($nextStatus, ['accepted', 'rescheduled'], true)
        && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointmentDate)
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $appointmentTime))) {
        throw new InvalidArgumentException('Choose a valid appointment date and time before confirming the request.');
    }

    $conn->begin_transaction();
    try {
        $lookup = $conn->prepare("SELECT `id`,`Customer_ID`,`Series`,`Model`,`Status`,`Appointment_Date`,`Appointment_Time`
            FROM `test drive` WHERE `id` = ? FOR UPDATE");
        $lookup->bind_param('i', $requestId);
        $lookup->execute();
        $request = $lookup->get_result()->fetch_assoc();
        $lookup->close();
        if (!$request) {
            throw new InvalidArgumentException('The test-drive request no longer exists.');
        }

        $currentStatus = (string)$request['Status'];
        $allowedTransition = ($currentStatus === 'pending' && in_array($nextStatus, ['accepted', 'rejected'], true))
            || (in_array($currentStatus, ['accepted', 'rescheduled'], true) && $nextStatus === 'rescheduled');
        if (!$allowedTransition) {
            throw new InvalidArgumentException('This test-drive request has already been processed and cannot be changed to that status.');
        }
        if ($nextStatus === 'rescheduled'
            && $currentStatus === 'rescheduled'
            && (string)$request['Appointment_Date'] === $appointmentDate
            && substr((string)$request['Appointment_Time'], 0, 5) === $appointmentTime) {
            $conn->commit();
            return false;
        }

        $update = $conn->prepare("UPDATE `test drive` SET `Status` = ?, `Appointment_Date` = NULLIF(?, ''), `Appointment_Time` = NULLIF(?, '') WHERE `id` = ?");
        $updateDate = $nextStatus === 'rejected' ? '' : $appointmentDate;
        $updateTime = $nextStatus === 'rejected' ? '' : $appointmentTime;
        $update->bind_param('sssi', $nextStatus, $updateDate, $updateTime, $requestId);
        $update->execute();
        $update->close();

        $model = trim((string)$request['Model']);
        if ($nextStatus === 'accepted') {
            $dateLabel = date('j F', strtotime($appointmentDate));
            $timeLabel = date('g:i A', strtotime($appointmentTime));
            $title = 'Test Drive Accepted';
            $message = "Your test drive request for {$model} has been accepted. Your appointment is scheduled for {$dateLabel} at {$timeLabel}.";
            $type = 'TEST_DRIVE_ACCEPTED';
        } elseif ($nextStatus === 'rejected') {
            $title = 'Test Drive Update';
            $message = "Your test drive request for {$model} has been declined. Please contact our dealership or choose another available date.";
            $type = 'TEST_DRIVE_REJECTED';
        } else {
            $dateLabel = date('j F', strtotime($appointmentDate));
            $timeLabel = date('g:i A', strtotime($appointmentTime));
            $title = 'Test Drive Rescheduled';
            $message = "Your {$model} test drive has been rescheduled to {$dateLabel} at {$timeLabel}.";
            $type = 'TEST_DRIVE_RESCHEDULED';
        }

        $customerId = (int)($request['Customer_ID'] ?? 0);
        if ($customerId > 0 && app_notification_enabled($conn, 'test_drives')) {
            app_create_notification(
                $conn,
                'customer',
                $customerId,
                $type,
                $title,
                $message,
                $requestId,
                'test_drive',
                'test-drive:' . $requestId . ':' . $nextStatus . ':' . ($nextStatus === 'rescheduled' ? $appointmentDate . '-' . str_replace(':', '', $appointmentTime) : '1')
            );
        }
        $conn->commit();
        return true;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

function app_update_car_service_status(
    mysqli $conn,
    int $requestId,
    string $nextStatus,
    string $appointmentDate = '',
    string $appointmentTime = ''
): bool {
    if ($requestId < 1 || !in_array($nextStatus, ['accepted', 'rejected', 'rescheduled'], true)) {
        throw new InvalidArgumentException('The selected service-request status is invalid.');
    }
    if (in_array($nextStatus, ['accepted', 'rescheduled'], true)
        && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointmentDate)
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $appointmentTime))) {
        throw new InvalidArgumentException('Choose a valid appointment date and time before confirming the service request.');
    }

    app_ensure_car_service_schema($conn);
    $conn->begin_transaction();
    try {
        $lookup = $conn->prepare("SELECT `id`,`Customer_ID`,`Vehicle_Model`,`Registration_Number`,`Status`,`Appointment_Date`,`Appointment_Time`
            FROM `car_service_requests` WHERE `id` = ? FOR UPDATE");
        $lookup->bind_param('i', $requestId);
        $lookup->execute();
        $request = $lookup->get_result()->fetch_assoc();
        $lookup->close();
        if (!$request) {
            throw new InvalidArgumentException('The car-service request no longer exists.');
        }

        $currentStatus = (string)$request['Status'];
        $allowedTransition = ($currentStatus === 'pending' && in_array($nextStatus, ['accepted', 'rejected'], true))
            || (in_array($currentStatus, ['accepted', 'rescheduled'], true) && $nextStatus === 'rescheduled');
        if (!$allowedTransition) {
            throw new InvalidArgumentException('This car-service request has already been processed and cannot be changed to that status.');
        }
        if ($nextStatus === 'rescheduled'
            && $currentStatus === 'rescheduled'
            && (string)$request['Appointment_Date'] === $appointmentDate
            && substr((string)$request['Appointment_Time'], 0, 5) === $appointmentTime) {
            $conn->commit();
            return false;
        }

        $update = $conn->prepare("UPDATE `car_service_requests` SET `Status` = ?, `Appointment_Date` = NULLIF(?, ''), `Appointment_Time` = NULLIF(?, '') WHERE `id` = ?");
        $updateDate = $nextStatus === 'rejected' ? '' : $appointmentDate;
        $updateTime = $nextStatus === 'rejected' ? '' : $appointmentTime;
        $update->bind_param('sssi', $nextStatus, $updateDate, $updateTime, $requestId);
        $update->execute();
        $update->close();

        $vehicle = trim((string)$request['Vehicle_Model']);
        $registration = trim((string)$request['Registration_Number']);
        if ($nextStatus === 'accepted') {
            $dateLabel = date('j F', strtotime($appointmentDate));
            $timeLabel = date('g:i A', strtotime($appointmentTime));
            $title = 'Car Service Accepted';
            $message = "Your service request for {$vehicle} ({$registration}) has been accepted. Your appointment is scheduled for {$dateLabel} at {$timeLabel}.";
            $type = 'CAR_SERVICE_ACCEPTED';
        } elseif ($nextStatus === 'rejected') {
            $title = 'Car Service Update';
            $message = "Your service request for {$vehicle} ({$registration}) could not be confirmed. Please contact the service team to choose another slot.";
            $type = 'CAR_SERVICE_REJECTED';
        } else {
            $dateLabel = date('j F', strtotime($appointmentDate));
            $timeLabel = date('g:i A', strtotime($appointmentTime));
            $title = 'Car Service Rescheduled';
            $message = "Your {$vehicle} ({$registration}) service appointment has been rescheduled to {$dateLabel} at {$timeLabel}.";
            $type = 'CAR_SERVICE_RESCHEDULED';
        }

        $customerId = (int)($request['Customer_ID'] ?? 0);
        if ($customerId > 0 && app_notification_enabled($conn, 'service_requests')) {
            app_create_notification(
                $conn,
                'customer',
                $customerId,
                $type,
                $title,
                $message,
                $requestId,
                'car_service',
                'car-service:' . $requestId . ':' . $nextStatus . ':' . ($nextStatus === 'rescheduled' ? $appointmentDate . '-' . str_replace(':', '', $appointmentTime) : '1')
            );
        }
        $conn->commit();
        return true;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

function app_update_sales_booking_status(mysqli $conn, int $bookingId, string $nextStatus): bool
{
    $statuses = ['pending', 'contacted', 'reserved', 'completed', 'cancelled'];
    if ($bookingId < 1 || !in_array($nextStatus, $statuses, true)) {
        throw new InvalidArgumentException('The selected sales-booking status is invalid.');
    }

    app_ensure_sales_booking_schema($conn);
    $conn->begin_transaction();
    try {
        $lookup = $conn->prepare('SELECT `id`,`Customer_ID`,`Vehicle_Name`,`Status` FROM `car_bookings` WHERE `id` = ? FOR UPDATE');
        $lookup->bind_param('i', $bookingId);
        $lookup->execute();
        $booking = $lookup->get_result()->fetch_assoc();
        $lookup->close();
        if (!$booking) {
            throw new InvalidArgumentException('The sales booking no longer exists.');
        }
        if ((string)$booking['Status'] === $nextStatus) {
            $conn->commit();
            return false;
        }

        $update = $conn->prepare('UPDATE `car_bookings` SET `Status` = ? WHERE `id` = ?');
        $update->bind_param('si', $nextStatus, $bookingId);
        $update->execute();
        $update->close();

        $vehicle = trim((string)$booking['Vehicle_Name']);
        $messages = [
            'pending' => ['Sales Booking Update', "Your booking request for {$vehicle} is being reviewed by our sales team.", 'SALES_BOOKING_PENDING'],
            'contacted' => ['Sales Advisor Assigned', "A sales advisor will contact you shortly about your {$vehicle} booking.", 'SALES_BOOKING_CONTACTED'],
            'reserved' => ['BMW Reservation Confirmed', "Your {$vehicle} reservation has been confirmed. Your sales advisor will guide you through the next steps.", 'SALES_BOOKING_RESERVED'],
            'completed' => ['BMW Booking Completed', "Your {$vehicle} booking has been completed. Thank you for choosing Beamers Legacy.", 'SALES_BOOKING_COMPLETED'],
            'cancelled' => ['BMW Booking Update', "Your {$vehicle} booking has been cancelled. Please contact our sales team if you would like help with another BMW.", 'SALES_BOOKING_CANCELLED'],
        ];
        [$title, $message, $type] = $messages[$nextStatus];
        $customerId = (int)($booking['Customer_ID'] ?? 0);
        if ($customerId > 0 && app_notification_enabled($conn, 'sales')) {
            app_create_notification($conn, 'customer', $customerId, $type, $title, $message, $bookingId, 'sales_booking', 'sales-booking:' . $bookingId . ':' . $nextStatus);
        }
        $conn->commit();
        return true;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function app_car_slug_base(string $name): string
{
    $transliterated = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) : $name;
    $slug = strtolower(trim((string)$transliterated));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    if ($slug === '') {
        $slug = 'car';
    }
    return str_starts_with($slug, 'bmw-') ? $slug : 'bmw-' . $slug;
}

function app_unique_car_slug(mysqli $conn, string $name, ?int $excludeId = null): string
{
    $base = substr(app_car_slug_base($name), 0, 180);
    $slug = $base;
    $suffix = 2;
    while (true) {
        $query = 'SELECT `id` FROM `cars` WHERE `slug` = ?';
        if ($excludeId !== null) {
            $query .= ' AND `id` <> ?';
        }
        $query .= ' LIMIT 1';
        $statement = $conn->prepare($query);
        if ($excludeId === null) {
            $statement->bind_param('s', $slug);
        } else {
            $statement->bind_param('si', $slug, $excludeId);
        }
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();
        if (!$exists) {
            return $slug;
        }
        $ending = '-' . $suffix++;
        $slug = substr($base, 0, 180 - strlen($ending)) . $ending;
    }
}

function app_ensure_car_catalog_schema(mysqli $conn): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS `cars` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(120) NOT NULL,
        `series` VARCHAR(80) NOT NULL,
        `category` VARCHAR(30) NOT NULL DEFAULT 'classic',
        `vehicle_type` VARCHAR(80) NULL,
        `hero_video` VARCHAR(500) NULL,
        `price` VARCHAR(80) NULL,
        `power` VARCHAR(30) NULL,
        `acceleration` VARCHAR(30) NULL,
        `drive_or_range` VARCHAR(40) NULL,
        `metric_label` VARCHAR(30) NOT NULL DEFAULT 'Drive',
        `configure_url` VARCHAR(255) NULL,
        `fuel` VARCHAR(40) NULL,
        `transmission` VARCHAR(60) NULL,
        `image` VARCHAR(255) NULL,
        `description` TEXT NULL,
        `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $columnsToAdd = [
        'name' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'series' => "VARCHAR(80) NOT NULL DEFAULT ''",
        'category' => "VARCHAR(30) NOT NULL DEFAULT 'classic'",
        'vehicle_type' => 'VARCHAR(80) NULL',
        'hero_video' => 'VARCHAR(500) NULL',
        'price' => 'VARCHAR(80) NULL',
        'power' => 'VARCHAR(30) NULL',
        'acceleration' => 'VARCHAR(30) NULL',
        'drive_or_range' => 'VARCHAR(40) NULL',
        'metric_label' => "VARCHAR(30) NOT NULL DEFAULT 'Drive'",
        'configure_url' => 'VARCHAR(255) NULL',
        'fuel' => 'VARCHAR(40) NULL',
        'transmission' => 'VARCHAR(60) NULL',
        'image' => 'VARCHAR(255) NULL',
        'description' => 'TEXT NULL',
        'status' => "ENUM('active','inactive') NOT NULL DEFAULT 'active'",
        'slug' => 'VARCHAR(180) NULL',
        'variant' => 'VARCHAR(120) NULL',
        'emi' => 'VARCHAR(80) NULL',
        'short_description' => 'VARCHAR(500) NULL',
        'hero_subtitle' => 'VARCHAR(180) NULL',
        'editorial_quote' => 'TEXT NULL',
        'story_sections' => 'TEXT NULL',
        'engine' => 'VARCHAR(120) NULL',
        'torque' => 'VARCHAR(80) NULL',
        'mileage' => 'VARCHAR(80) NULL',
        'top_speed' => 'VARCHAR(80) NULL',
        'seating_capacity' => 'VARCHAR(40) NULL',
        'brochure' => 'VARCHAR(500) NULL',
        'gallery' => 'TEXT NULL',
        'highlights' => 'TEXT NULL',
        'offers' => 'TEXT NULL',
        'accel_200' => 'VARCHAR(40) NULL',
        'displacement' => 'VARCHAR(60) NULL',
        'engine_power' => 'VARCHAR(100) NULL',
        'engine_torque' => 'VARCHAR(100) NULL',
        'electric_power' => 'VARCHAR(100) NULL',
        'electric_torque' => 'VARCHAR(100) NULL',
        'battery_capacity' => 'VARCHAR(100) NULL',
        'electric_range' => 'VARCHAR(100) NULL',
        'charging_time' => 'VARCHAR(120) NULL',
        'drivetrain' => 'VARCHAR(120) NULL',
        'suspension' => 'VARCHAR(150) NULL',
        'wheels_front' => 'VARCHAR(120) NULL',
        'wheels_rear' => 'VARCHAR(120) NULL',
        'dimensions' => 'VARCHAR(100) NULL',
        'wheelbase' => 'VARCHAR(60) NULL',
        'weight' => 'VARCHAR(60) NULL',
        'boot_capacity' => 'VARCHAR(60) NULL',
        'fuel_tank' => 'VARCHAR(60) NULL',
        'custom_colors' => 'TEXT NULL',
    ];
    $columns = [];
    $result = $conn->query('SHOW COLUMNS FROM `cars`');
    while ($column = $result->fetch_assoc()) {
        $columns[(string)$column['Field']] = true;
    }
    foreach ($columnsToAdd as $column => $definition) {
        if (!isset($columns[$column])) {
            $conn->query("ALTER TABLE `cars` ADD COLUMN `{$column}` {$definition}");
        }
    }

    $rows = $conn->query('SELECT `id`, `name`, `slug` FROM `cars` ORDER BY `id`')->fetch_all(MYSQLI_ASSOC);
    $usedSlugs = [];
    $updateSlug = $conn->prepare('UPDATE `cars` SET `slug` = ? WHERE `id` = ?');
    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $current = strtolower(trim((string)($row['slug'] ?? '')));
        $base = preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $current)
            ? substr($current, 0, 180)
            : app_car_slug_base((string)$row['name']);
        $slug = $base;
        $suffix = 2;
        while (isset($usedSlugs[$slug])) {
            $ending = '-' . $suffix++;
            $slug = substr($base, 0, 180 - strlen($ending)) . $ending;
        }
        $usedSlugs[$slug] = true;
        if ($current !== $slug) {
            $updateSlug->bind_param('si', $slug, $id);
            $updateSlug->execute();
        }
    }
    $updateSlug->close();

    $hasUniqueSlug = false;
    $indexes = $conn->query('SHOW INDEX FROM `cars`');
    while ($index = $indexes->fetch_assoc()) {
        if ((string)$index['Column_name'] === 'slug' && (int)$index['Non_unique'] === 0) {
            $hasUniqueSlug = true;
            break;
        }
    }
    $conn->query('ALTER TABLE `cars` MODIFY COLUMN `slug` VARCHAR(180) NOT NULL');
    if (!$hasUniqueSlug) {
        $conn->query('ALTER TABLE `cars` ADD UNIQUE KEY `cars_slug_unique` (`slug`)');
    }

    $conn->query("CREATE TABLE IF NOT EXISTS `test drive` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `Series` VARCHAR(100) NOT NULL,
        `Model` VARCHAR(100) NOT NULL,
        `First_Name` VARCHAR(100) NOT NULL,
        `Last_Name` VARCHAR(100) NOT NULL,
        `E-Mail` VARCHAR(191) NOT NULL,
        `Mobile_No` VARCHAR(30) NOT NULL,
        `City` VARCHAR(100) NOT NULL,
        `Finance` VARCHAR(30) NOT NULL DEFAULT 'no',
        `Configuration` VARCHAR(255) NULL,
        `Car_ID` INT UNSIGNED NULL,
        `Request_Type` ENUM('test_drive','offer') NOT NULL DEFAULT 'test_drive',
        `Customer_ID` INT UNSIGNED NULL,
        `Status` ENUM('pending','accepted','rejected','rescheduled','cancelled') NOT NULL DEFAULT 'pending',
        `Appointment_Date` DATE NULL,
        `Appointment_Time` TIME NULL,
        KEY `idx_test_drive_car_id` (`Car_ID`),
        KEY `idx_test_drive_customer_id` (`Customer_ID`),
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $testDriveColumns = [];
    $testDriveTable = $conn->query("SHOW TABLES LIKE 'test drive'");
    if ($testDriveTable->num_rows > 0) {
        $result = $conn->query("SHOW COLUMNS FROM `test drive`");
        while ($column = $result->fetch_assoc()) {
            $testDriveColumns[(string)$column['Field']] = true;
        }
        if (!isset($testDriveColumns['Car_ID'])) {
            $conn->query("ALTER TABLE `test drive` ADD COLUMN `Car_ID` INT UNSIGNED NULL");
        }
        if (!isset($testDriveColumns['Request_Type'])) {
            $conn->query("ALTER TABLE `test drive` ADD COLUMN `Request_Type` ENUM('test_drive','offer') NOT NULL DEFAULT 'test_drive'");
        }
        $hasCarIndex = false;
        $indexes = $conn->query("SHOW INDEX FROM `test drive`");
        while ($index = $indexes->fetch_assoc()) {
            if ((string)$index['Column_name'] === 'Car_ID') {
                $hasCarIndex = true;
                break;
            }
        }
        if (!$hasCarIndex) {
            $conn->query("ALTER TABLE `test drive` ADD KEY `idx_test_drive_car_id` (`Car_ID`)");
        }
    }

    $seedCars = [
        ['BMW M5 Sedan', 'M Series', 'm', 'High-Performance Sedan', '₹ 2,11,50,000', '727', '3.5s', '69km', 'Range', 'images/5 series.png'],
        ['BMW M2', 'M Series', 'm', 'Coupé', '$63,200', '460', '4.1s', 'RWD', 'Drive', 'images/m2.png'],
        ['BMW M4 CS', 'M Series', 'm', 'LTD Edition', '$123,500', '550', '3.4s', 'AWD', 'Drive', 'images/m4 cs.png'],
        ['BMW i4', 'i Series', 'electric', 'Gran Coupé', '$57,300', '340', '5.7s', '301mi', 'Range', 'images/i4.png'],
        ['BMW i5', 'i Series', 'electric', 'Sedan', '$66,800', '340', '5.7s', '295mi', 'Range', 'images/i5.png'],
        ['BMW i7 M70', 'i Series', 'electric', 'Flagship Sedan', '$168,500', '659', '3.7s', '291mi', 'Range', 'images/i7-m70-760x360.png'],
        ['BMW X1', 'X Series', 'x', 'Compact SAV', '$39,100', '241', '6.2s', 'xDrive', 'Drive', 'images/X1.png'],
        ['BMW X5', 'X Series', 'x', 'Mid-Size SAV', '$65,200', '375', '5.3s', 'xDrive', 'Drive', 'images/x5.png'],
        ['BMW X7', 'X Series', 'x', 'Flagship SAV', '$81,900', '375', '5.6s', 'xDrive', 'Drive', 'images/x7.png'],
        ['3 Series LWB', '3 Series', 'classic', 'Sedan', '$44,500', '255', '5.6s', 'RWD/xDry', 'Drive', 'images/3 series longbase.png'],
        ['BMW 5 Series', '5 Series', 'classic', 'Executive Sedan', '$57,900', '255', '5.7s', 'RWD/xDry', 'Drive', 'images/5 series.png'],
        ['BMW 7 Series', '7 Series', 'classic', 'Luxury Sedan', '$95,700', '375', '5.2s', 'RWD/xDry', 'Drive', 'images/7 series.png'],
    ];
    $conn->query("CREATE TABLE IF NOT EXISTS `app_migrations` (
        `migration_key` VARCHAR(120) NOT NULL PRIMARY KEY,
        `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $seedMigration = 'seed_initial_showroom_cars_v1';
    $conn->begin_transaction();
    try {
        $claimMigration = $conn->prepare('INSERT IGNORE INTO `app_migrations` (`migration_key`) VALUES (?)');
        $claimMigration->bind_param('s', $seedMigration);
        $claimMigration->execute();
        $claimedMigration = $claimMigration->affected_rows === 1;
        $claimMigration->close();
        if ($claimedMigration) {
        $findCar = $conn->prepare('SELECT `id` FROM `cars` WHERE LOWER(`name`) = LOWER(?) LIMIT 1');
        $insertCar = $conn->prepare("INSERT INTO `cars` (`name`,`series`,`category`,`vehicle_type`,`price`,`power`,`acceleration`,`drive_or_range`,`metric_label`,`image`,`slug`,`status`) VALUES (?,?,?,?,?,?,?,?,?,?,?,'active')");
        foreach ($seedCars as $seed) {
            [$name, $series, $category, $type, $price, $power, $acceleration, $drive, $metric, $image] = $seed;
            $findCar->bind_param('s', $name);
            $findCar->execute();
            if ($findCar->get_result()->num_rows > 0) {
                continue;
            }
            $slug = app_unique_car_slug($conn, $name);
            $insertCar->bind_param('sssssssssss', $name, $series, $category, $type, $price, $power, $acceleration, $drive, $metric, $image, $slug);
            $insertCar->execute();
        }
        $findCar->close();
        $insertCar->close();
        }
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }

    $m5MigrationKey = 'seed_bmw_m5_sedan_flagship_v1';
    $conn->begin_transaction();
    try {
        $claimM5 = $conn->prepare('INSERT IGNORE INTO `app_migrations` (`migration_key`) VALUES (?)');
        if ($claimM5) {
            $claimM5->bind_param('s', $m5MigrationKey);
            $claimM5->execute();
            $claimedM5 = $claimM5->affected_rows === 1;
            $claimM5->close();
            if ($claimedM5) {
                $checkM5 = $conn->prepare("SELECT `id` FROM `cars` WHERE `slug` = 'bmw-m5-sedan' OR LOWER(`name`) = 'bmw m5 sedan' LIMIT 1");
                if ($checkM5) {
                    $checkM5->execute();
                    $m5Exists = $checkM5->get_result()->num_rows > 0;
                    $checkM5->close();
                    if (!$m5Exists) {
                        $m5Name = 'BMW M5 Sedan';
                        $m5Slug = 'bmw-m5-sedan';
                        $m5Series = 'M Series';
                        $m5Category = 'm';
                        $m5Type = 'High-Performance Sedan';
                        $m5Subtitle = 'THE M5 SEDAN — PURE M POWER. ELECTRIFIED.';
                        $m5Price = '₹ 2,11,50,000';
                        $m5Emi = '₹ 1,99,999 / mo*';
                        $m5Power = '727 hp';
                        $m5Acc = '3.5 s';
                        $m5Drive = '69 km / xDrive';
                        $m5Metric = 'Electric Range';
                        $m5Fuel = 'Plug-in Hybrid';
                        $m5Engine = '4.4L M TwinPower Turbo V8 + Electric Motor';
                        $m5Torque = '1,000 Nm';
                        $m5Speed = '305 km/h';
                        $m5Trans = '8-Speed M Steptronic';
                        $m5Seats = '5 Seats';
                        $m5Mileage = '58.8 km/l';
                        $m5Image = 'images/5 series.png';
                        $m5Video = 'videos/video-1.mp4';
                        $m5Brochure = 'docs/Beamers Legacy.docx';
                        $m5ShortDesc = 'The first-ever electrified BMW M5 Sedan pairs a 585 hp V8 with an electric motor for an astounding 727 hp and 1,000 Nm of torque.';
                        $m5Quote = '“Supercar performance meets executive luxury: the new BMW M5 Sedan redefines what an M automobile can achieve.”';
                        $m5Highlights = "M HYBRID System with 727 hp (535 kW) and 1,000 Nm torque\n0–100 km/h sprint in 3.5 seconds with launch control\nM xDrive all-wheel drive with drift-ready 2WD rear mode\nBMW Curved Display with M-Specific OS 8.5 & M Head-Up Display\nContour-illuminated Iconic Glow M kidney grille\nLightweight M Carbon roof with aerodynamic quad exhaust pipes";
                        $m5Stories = "M HYBRID Electrified Performance|The first-ever electrified M5 combines a ferocious 4.4-litre M TwinPower Turbo V8 engine with a high-torque electric motor integrated into the 8-speed M Steptronic transmission. Result: 727 hp, 1,000 Nm, and instantaneous electric launch.|images/bg m4 image.jpg\nM xDrive All-Wheel Drive with 2WD Drift Mode|The rear-biased M xDrive distributes power seamlessly across all four wheels. Drivers can toggle from 4WD for extreme grip, to 4WD Sport for agility, or disconnect the front axle entirely in 2WD mode for pure rear-wheel drifting thrills.|images/m4 cs.png\nContour-Illuminated Iconic Glow Grille|An illuminated contour line frames the sculpted M kidney grille. Flared wheel arches, an M Carbon roof, aerodynamic mirror caps, and dual twin exhaust tailpipes leave an unmistakable impression from every angle.|images/5 series.png\nDriver-Focused M Cockpit|Frameless 12.3-inch digital instrument cluster and 14.9-inch central display with M Mode graphics, shift lights, drift analyzer, and M Multifunction seats in Merino leather.|images/bmw color 1.jpg";
                        $m5Offers = "BMW 360° Financial Plan with attractive EMI starting at ₹ 1,99,999/month*\nComplimentary BMW Service Inclusive Plus maintenance package for 3 years / 40,000 km\nComplimentary BMW Wallbox charger with 8 years / 160,000 km battery warranty\nExclusive BMW M Track Experience invitation at Buddh International Circuit";
                        
                        $insertM5 = $conn->prepare("INSERT INTO `cars` (
                            `name`, `slug`, `series`, `category`, `vehicle_type`, `hero_subtitle`,
                            `price`, `emi`, `power`, `acceleration`, `drive_or_range`, `metric_label`,
                            `fuel`, `engine`, `torque`, `top_speed`, `transmission`, `seating_capacity`,
                            `mileage`, `image`, `hero_video`, `brochure`, `short_description`,
                            `editorial_quote`, `highlights`, `story_sections`, `offers`, `status`
                        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'active')");
                        if ($insertM5) {
                            $insertM5->bind_param(
                                'sssssssssssssssssssssssssss',
                                $m5Name, $m5Slug, $m5Series, $m5Category, $m5Type, $m5Subtitle,
                                $m5Price, $m5Emi, $m5Power, $m5Acc, $m5Drive, $m5Metric,
                                $m5Fuel, $m5Engine, $m5Torque, $m5Speed, $m5Trans, $m5Seats,
                                $m5Mileage, $m5Image, $m5Video, $m5Brochure, $m5ShortDesc,
                                $m5Quote, $m5Highlights, $m5Stories, $m5Offers
                            );
                            $insertM5->execute();
                            $insertM5->close();
                        }
                    }
                }
            }
        }
        $conn->commit();
    } catch (Throwable $m5Error) {
        $conn->rollback();
        error_log('M5 seed migration notice: ' . $m5Error->getMessage());
    }
    $ensured = true;
}

/*
 * Mega-menu helpers
 * ------------------------------------------------------------------
 * The public nav's "Models" mega-menu groups the live `cars` table by
 * BMW series family (2/3/5/7/M/X/i/...) the same way a dealer site
 * groups its "New Cars" dropdown. These helpers are intentionally
 * separate from models.php's own showroom_car_image() so both files
 * can require config.php without redeclaring functions.
 */
function bmw_series_group_key(string $series): string
{
    $s = trim($series);
    if ($s === '') {
        return '?';
    }
    if (preg_match('/^([0-9])/', $s, $m)) {
        return $m[1];
    }
    $first = strtoupper(substr($s, 0, 1));
    if (in_array($first, ['X', 'I', 'M', 'Z'], true)) {
        return $first;
    }
    return $first;
}

function bmw_series_group_order(): array
{
    return ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '7' => 6, 'M' => 7, 'X' => 8, 'I' => 9, 'Z' => 10];
}

/**
 * Fetch active cars from the DB grouped by series family for the nav
 * mega-menu. Returns [] on any DB failure so the caller can gracefully
 * fall back to a plain link instead of a broken menu.
 *
 * @param int $perGroup Max cars shown per group in the menu.
 */
function bmw_nav_model_groups(int $perGroup = 50): array
{
    try {
        $conn = db();
        app_ensure_car_catalog_schema($conn);
        $result = $conn->query("SELECT id, name, series, image, slug FROM cars WHERE status = 'active' ORDER BY id DESC");
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Throwable $e) {
        return [];
    }

    $groups = [];
    foreach ($rows as $row) {
        $key = bmw_series_group_key((string)($row['series'] ?? ''));
        if (!isset($groups[$key])) {
            $groups[$key] = ['key' => $key, 'cars' => []];
        }
        if (count($groups[$key]['cars']) < $perGroup) {
            $groups[$key]['cars'][] = $row;
        }
    }

    $order = bmw_series_group_order();
    uksort($groups, function (string $a, string $b) use ($order): int {
        return ($order[strtoupper($a)] ?? 99) <=> ($order[strtoupper($b)] ?? 99);
    });

    return array_values($groups);
}

function bmw_mega_menu_image(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $path = str_replace('\\', '/', $path);
    if (str_starts_with($path, 'images/')) {
        return '../' . $path;
    }
    if (str_starts_with($path, '../') || str_starts_with($path, '/')) {
        return $path;
    }
    return '../' . ltrim($path, '/');
}

function bmw_setting(string $key, string $default = ''): string
{
    try {
        $conn = db();
        $statement = $conn->prepare('SELECT `setting_value` FROM `dealer_settings` WHERE `setting_key` = ? LIMIT 1');
        $statement->bind_param('s', $key);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        return $row ? (string)$row['setting_value'] : $default;
    } catch (Throwable $error) {
        return $default;
    }
}
