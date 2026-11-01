<?php
declare(strict_types=1);

/* Shared database, authentication and data helpers for the admin area. */
require_once __DIR__ . '/../config.php';
start_app_session();
require_once __DIR__ . '/../connect.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

function admin_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_columns(mysqli $conn, string $table): array
{
    $result = $conn->query("SHOW COLUMNS FROM `{$table}`");
    $columns = [];

    while ($column = $result->fetch_assoc()) {
        $columns[$column['Field']] = $column;
    }

    return $columns;
}

/*
 * The public forms in this project use these three tables.  Creating missing
 * tables and identity columns makes the dashboard usable on a fresh database
 * as well as with the original project schema.
 */
function admin_ensure_schema(mysqli $conn): void
{
    $tables = [
        "CREATE TABLE IF NOT EXISTS `register` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `First_Name` VARCHAR(100) NOT NULL,
            `Last_Name` VARCHAR(100) NOT NULL,
            `E-mail` VARCHAR(191) NOT NULL UNIQUE,
            `Password` VARCHAR(255) NOT NULL,
            `About` VARCHAR(500) NULL,
            `Website` VARCHAR(255) NULL,
            `Username` VARCHAR(50) NULL,
            `Firebase_UID` VARCHAR(128) NULL,
            `Photo_URL` VARCHAR(500) NULL,
            `Auth_Provider` VARCHAR(100) NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `test drive` (
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
            `Customer_ID` INT UNSIGNED NULL,
            `Status` ENUM('pending','accepted','rejected','rescheduled','cancelled') NOT NULL DEFAULT 'pending',
            `Appointment_Date` DATE NULL,
            `Appointment_Time` TIME NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `feedback` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL,
            `email` VARCHAR(191) NOT NULL,
            `model` VARCHAR(120) NULL,
            `rating` TINYINT UNSIGNED NULL,
            `message` TEXT NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `email` VARCHAR(191) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    $tables[] = "CREATE TABLE IF NOT EXISTS `cars` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(120) NOT NULL,
            `slug` VARCHAR(180) NULL,
            `series` VARCHAR(80) NOT NULL,
            `category` VARCHAR(30) NOT NULL DEFAULT 'classic',
            `vehicle_type` VARCHAR(80) NULL,
            `variant` VARCHAR(120) NULL,
            `hero_subtitle` VARCHAR(180) NULL,
            `hero_video` VARCHAR(500) NULL,
            `price` VARCHAR(80) NULL,
            `emi` VARCHAR(80) NULL,
            `short_description` VARCHAR(500) NULL,
            `editorial_quote` TEXT NULL,
            `power` VARCHAR(30) NULL,
            `acceleration` VARCHAR(30) NULL,
            `drive_or_range` VARCHAR(40) NULL,
            `metric_label` VARCHAR(30) NOT NULL DEFAULT 'Drive',
            `configure_url` VARCHAR(255) NULL,
            `fuel` VARCHAR(40) NULL,
            `transmission` VARCHAR(60) NULL,
            `engine` VARCHAR(120) NULL,
            `torque` VARCHAR(80) NULL,
            `mileage` VARCHAR(80) NULL,
            `top_speed` VARCHAR(80) NULL,
            `seating_capacity` VARCHAR(40) NULL,
            `image` VARCHAR(255) NULL,
            `brochure` VARCHAR(500) NULL,
            `gallery` TEXT NULL,
            `story_sections` TEXT NULL,
            `highlights` TEXT NULL,
            `offers` TEXT NULL,
            `description` TEXT NULL,
            `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $tables[] = "CREATE TABLE IF NOT EXISTS `dealer_settings` (
            `setting_key` VARCHAR(120) NOT NULL PRIMARY KEY,
            `setting_value` TEXT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $tables[] = "CREATE TABLE IF NOT EXISTS `store_categories` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(120) NOT NULL UNIQUE,
            `slug` VARCHAR(140) NOT NULL UNIQUE, `description` TEXT NULL, `image` VARCHAR(255) NULL,
            `status` ENUM('active','inactive') NOT NULL DEFAULT 'active', `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $tables[] = "CREATE TABLE IF NOT EXISTS `store_collections` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(120) NOT NULL UNIQUE,
            `slug` VARCHAR(140) NOT NULL UNIQUE, `description` TEXT NULL, `cover_image` VARCHAR(255) NULL,
            `status` ENUM('active','inactive') NOT NULL DEFAULT 'active', `featured` TINYINT(1) NOT NULL DEFAULT 0,
            `sort_order` INT NOT NULL DEFAULT 0, `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $tables[] = "CREATE TABLE IF NOT EXISTS `store_products` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(160) NOT NULL,
            `sku` VARCHAR(80) NULL UNIQUE, `slug` VARCHAR(180) NOT NULL UNIQUE, `category_id` INT UNSIGNED NULL,
            `collection_id` INT UNSIGNED NULL, `description` TEXT NULL, `short_description` VARCHAR(500) NULL,
            `price` DECIMAL(12,2) NOT NULL DEFAULT 0, `sale_price` DECIMAL(12,2) NULL,
            `currency` VARCHAR(10) NOT NULL DEFAULT 'INR', `stock` INT NOT NULL DEFAULT 0,
            `low_stock_threshold` INT NOT NULL DEFAULT 5, `status` ENUM('active','draft','out_of_stock','archived') NOT NULL DEFAULT 'draft',
            `featured` TINYINT(1) NOT NULL DEFAULT 0, `badge` VARCHAR(30) NULL, `image` VARCHAR(255) NULL,
            `created_by` INT UNSIGNED NULL, `updated_by` INT UNSIGNED NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`category_id`), INDEX (`collection_id`), INDEX (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $tables[] = "CREATE TABLE IF NOT EXISTS `store_product_images` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `product_id` INT UNSIGNED NOT NULL,
            `image_url` VARCHAR(255) NOT NULL, `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    foreach ($tables as $sql) {
        $conn->query($sql);
    }

    app_ensure_customer_profile_schema($conn);
    $conn->query("INSERT IGNORE INTO `store_categories` (`name`,`slug`,`description`) VALUES
        ('Clothing','clothing','BMW apparel'),('Accessories','accessories','BMW accessories'),
        ('BMW M','bmw-m','BMW M collection'),('Motorsport','motorsport','Motorsport collection'),
        ('Travel','travel','Travel products'),('Lifestyle','lifestyle','Lifestyle products'),
        ('Kids','kids','BMW Kids'),('Collectibles','collectibles','Collectibles'),
        ('Automotive Accessories','automotive-accessories','Automotive accessories')");

    if (!function_exists('admin_settings')) {
    function admin_settings(mysqli $conn): array
    {
        $settings = [];
        $result = $conn->query('SELECT `setting_key`, `setting_value` FROM `dealer_settings`');
        while ($row = $result->fetch_assoc()) {
            $settings[(string)$row['setting_key']] = (string)($row['setting_value'] ?? '');
        }

        return $settings;
    }

    function admin_settings_save(mysqli $conn, array $input): void
    {
        $definitions = admin_settings_definitions();
        $statement = $conn->prepare(
            'INSERT INTO `dealer_settings` (`setting_key`, `setting_value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)'
        );
        foreach ($definitions as $section) {
            foreach ($section['fields'] as $key => $field) {
                $value = trim(str_replace("\0", '', (string)($input[$key] ?? '')));
                admin_validate_setting($key, $field, $value);
                $statement->bind_param('ss', $key, $value);
                $statement->execute();
            }
        }
        $statement->close();
    }

    function admin_settings_definitions(): array
    {
        return [
            'showroom' => ['title' => 'SHOWROOM INFORMATION', 'fields' => [
                'showroom_name' => ['label' => 'Showroom Name', 'type' => 'text', 'required' => true],
                'legal_business_name' => ['label' => 'Legal Business Name', 'type' => 'text'],
                'tagline' => ['label' => 'Tagline', 'type' => 'text'],
                'dealer_code' => ['label' => 'Dealer Code', 'type' => 'text'],
                'dealership_type' => ['label' => 'Dealership Type', 'type' => 'select', 'options' => ['Authorized BMW Dealer', 'BMW Motorrad Dealer', 'BMW Premium Selection', 'Other']],
            ]],
            'location' => ['title' => 'SHOWROOM LOCATION', 'fields' => [
                'address_line_1' => ['label' => 'Address Line 1', 'type' => 'text'],
                'address_line_2' => ['label' => 'Address Line 2', 'type' => 'text'],
                'area' => ['label' => 'Area / Locality', 'type' => 'text'],
                'city' => ['label' => 'City', 'type' => 'text'],
                'state' => ['label' => 'State', 'type' => 'text'],
                'country' => ['label' => 'Country', 'type' => 'text'],
                'pincode' => ['label' => 'Pincode', 'type' => 'text'],
            ]],
            'coordinates' => ['title' => 'LOCATION', 'fields' => [
                'latitude' => ['label' => 'Latitude', 'type' => 'number'],
                'longitude' => ['label' => 'Longitude', 'type' => 'number'],
                'google_maps_url' => ['label' => 'Google Maps URL', 'type' => 'url'],
            ]],
            'contact' => ['title' => 'CONTACT INFORMATION', 'fields' => [
                'primary_phone' => ['label' => 'Primary Phone', 'type' => 'tel'],
                'secondary_phone' => ['label' => 'Secondary Phone', 'type' => 'tel'],
                'whatsapp_number' => ['label' => 'WhatsApp Number', 'type' => 'tel'],
                'primary_email' => ['label' => 'Primary Email', 'type' => 'email'],
                'sales_email' => ['label' => 'Sales Email', 'type' => 'email'],
                'service_email' => ['label' => 'Service Email', 'type' => 'email'],
                'website_url' => ['label' => 'Website URL', 'type' => 'url'],
            ]],
            'business' => ['title' => 'BUSINESS INFORMATION', 'fields' => [
                'gstin' => ['label' => 'GSTIN', 'type' => 'text'],
                'pan' => ['label' => 'PAN', 'type' => 'text'],
                'cin' => ['label' => 'CIN / Registration Number', 'type' => 'text'],
                'dealer_registration_number' => ['label' => 'Dealer Registration Number', 'type' => 'text'],
                'business_currency' => ['label' => 'Currency', 'type' => 'select', 'options' => ['INR ₹', 'USD $', 'EUR €']],
                'business_country' => ['label' => 'Country', 'type' => 'text'],
                'business_state' => ['label' => 'State', 'type' => 'text'],
                'default_gst_rate' => ['label' => 'Default GST Rate', 'type' => 'number'],
            ]],
            'hours' => ['title' => 'SHOWROOM HOURS', 'fields' => array_combine(
                array_merge(...array_map(static fn(string $day): array => ["hours_{$day}_open", "hours_{$day}_from", "hours_{$day}_to"], ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'])),
                array_merge(...array_map(static fn(string $day): array => [
                    "hours_{$day}_open" => ['label' => ucfirst($day) . ' Open', 'type' => 'select', 'options' => ['1' => 'OPEN', '0' => 'CLOSED']],
                    "hours_{$day}_from" => ['label' => ucfirst($day) . ' Opening time', 'type' => 'time'],
                    "hours_{$day}_to" => ['label' => ucfirst($day) . ' Closing time', 'type' => 'time'],
                ], ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']))
            )],
            'website' => ['title' => 'WEBSITE SETTINGS', 'fields' => [
                'website_name' => ['label' => 'Website Name', 'type' => 'text'],
                'website_title' => ['label' => 'Website Title', 'type' => 'text'],
                'website_description' => ['label' => 'Website Description', 'type' => 'textarea'],
                'logo_path' => ['label' => 'Logo', 'type' => 'file'],
                'favicon_path' => ['label' => 'Favicon', 'type' => 'file'],
            ]],
            'social' => ['title' => 'SOCIAL MEDIA', 'fields' => [
                'instagram_url' => ['label' => 'Instagram', 'type' => 'url'], 'facebook_url' => ['label' => 'Facebook', 'type' => 'url'], 'youtube_url' => ['label' => 'YouTube', 'type' => 'url'], 'linkedin_url' => ['label' => 'LinkedIn', 'type' => 'url'], 'twitter_url' => ['label' => 'X / Twitter', 'type' => 'url'],
            ]],
            'testdrive' => ['title' => 'TEST DRIVE SETTINGS', 'fields' => [
                'test_drive_enabled' => ['label' => 'Test Drive Booking', 'type' => 'select', 'options' => ['1' => 'ENABLED', '0' => 'DISABLED']], 'test_drive_phone' => ['label' => 'Test Drive Contact Number', 'type' => 'tel'], 'test_drive_email' => ['label' => 'Test Drive Email', 'type' => 'email'], 'test_drive_duration' => ['label' => 'Default Test Drive Duration (minutes)', 'type' => 'number'], 'max_advance_booking' => ['label' => 'Maximum Advance Booking (days)', 'type' => 'number'],
            ]],
            'system' => ['title' => 'SYSTEM PREFERENCES', 'fields' => [
                'language' => ['label' => 'Language', 'type' => 'select', 'options' => ['English']], 'system_currency' => ['label' => 'Currency', 'type' => 'select', 'options' => ['INR ₹', 'USD $']], 'timezone' => ['label' => 'Timezone', 'type' => 'select', 'options' => ['Asia/Kolkata', 'UTC']], 'date_format' => ['label' => 'Date Format', 'type' => 'select', 'options' => ['DD/MM/YYYY', 'MM/DD/YYYY']], 'time_format' => ['label' => 'Time Format', 'type' => 'select', 'options' => ['12 Hour', '24 Hour']],
            ]],
        ];
    }

    function admin_validate_setting(string $key, array $field, string $value): void
    {
        if (!empty($field['required']) && $value === '') {
            throw new InvalidArgumentException($field['label'] . ' is required.');
        }
        if ($value !== '' && $field['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException($field['label'] . ' must be a valid email address.');
        }
        if ($value !== '' && $field['type'] === 'url' && !filter_var($value, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException($field['label'] . ' must be a valid URL.');
        }
        if ($value !== '' && in_array($key, ['primary_phone','secondary_phone','whatsapp_number','test_drive_phone'], true) && !preg_match('/^\+?[0-9 ()-]{7,20}$/', $value)) {
            throw new InvalidArgumentException($field['label'] . ' must be a valid phone number.');
        }
        if ($key === 'pincode' && $value !== '' && !preg_match('/^[0-9]{4,10}$/', $value)) {
            throw new InvalidArgumentException('Pincode must contain 4 to 10 digits.');
        }
        if (in_array($key, ['latitude','longitude'], true) && $value !== '' && !is_numeric($value)) {
            throw new InvalidArgumentException($field['label'] . ' must be numeric.');
        }
        if ($key === 'latitude' && ($value < -90 || $value > 90)) throw new InvalidArgumentException('Latitude must be between -90 and 90.');
        if ($key === 'longitude' && ($value < -180 || $value > 180)) throw new InvalidArgumentException('Longitude must be between -180 and 180.');
        if (strlen($value) > 4000) throw new InvalidArgumentException($field['label'] . ' is too long.');
    }
    }

    app_ensure_car_service_schema($conn);
    app_ensure_sales_booking_schema($conn);
    foreach (['register', 'test drive', 'car_service_requests', 'car_bookings', 'feedback', 'admin_users', 'cars'] as $table) {
        $columns = admin_columns($conn, $table);

        if (!isset($columns['id'])) {
            $hasPrimaryKey = false;
            foreach ($columns as $column) {
                if (($column['Key'] ?? '') === 'PRI') {
                    $hasPrimaryKey = true;
                    break;
                }
            }

            $identity = $hasPrimaryKey
                ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE'
                : 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
            $conn->query("ALTER TABLE `{$table}` ADD COLUMN `id` {$identity} FIRST");
        }
    }

    $carColumns = admin_columns($conn, 'cars');
    $carDefaults = [
        'name' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'slug' => 'VARCHAR(180) NULL',
        'series' => "VARCHAR(80) NOT NULL DEFAULT ''",
        'category' => "VARCHAR(30) NOT NULL DEFAULT 'classic'",
        'vehicle_type' => 'VARCHAR(80) NULL',
        'hero_video' => 'VARCHAR(500) NULL',
        'variant' => 'VARCHAR(120) NULL',
        'price' => 'VARCHAR(80) NULL',
        'emi' => 'VARCHAR(80) NULL',
        'short_description' => 'VARCHAR(500) NULL',
        'hero_subtitle' => 'VARCHAR(180) NULL',
        'editorial_quote' => 'TEXT NULL',
        'story_sections' => 'TEXT NULL',
        'power' => 'VARCHAR(30) NULL',
        'acceleration' => 'VARCHAR(30) NULL',
        'drive_or_range' => 'VARCHAR(40) NULL',
        'metric_label' => "VARCHAR(30) NOT NULL DEFAULT 'Drive'",
        'configure_url' => 'VARCHAR(255) NULL',
        'fuel' => 'VARCHAR(40) NULL',
        'transmission' => 'VARCHAR(60) NULL',
        'engine' => 'VARCHAR(120) NULL',
        'torque' => 'VARCHAR(80) NULL',
        'mileage' => 'VARCHAR(80) NULL',
        'top_speed' => 'VARCHAR(80) NULL',
        'seating_capacity' => 'VARCHAR(40) NULL',
        'image' => 'VARCHAR(255) NULL',
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
        'description' => 'TEXT NULL',
        'status' => "ENUM('active','inactive') NOT NULL DEFAULT 'active'",
        'created_at' => 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ];
    foreach ($carDefaults as $column => $definition) {
        if (!isset($carColumns[$column])) {
            $conn->query("ALTER TABLE `cars` ADD COLUMN `{$column}` {$definition}");
        }
    }
    app_ensure_car_catalog_schema($conn);
    app_ensure_notification_schema($conn);

    $feedbackColumns = admin_columns($conn, 'feedback');
    if (!isset($feedbackColumns['rating'])) {
        $conn->query('ALTER TABLE `feedback` ADD COLUMN `rating` TINYINT UNSIGNED NULL AFTER `model`');
    }

    $testDriveColumns = admin_columns($conn, 'test drive');
    if (!isset($testDriveColumns['Configuration'])) {
        $conn->query('ALTER TABLE `test drive` ADD COLUMN `Configuration` VARCHAR(255) NULL');
    }

    $adminCount = (int) $conn->query('SELECT COUNT(*) FROM `admin_users`')->fetch_row()[0];
    if ($adminCount === 0) {
        $adminEmail = trim((string) getenv('BEAMERS_ADMIN_EMAIL'));
        $adminPassword = (string) getenv('BEAMERS_ADMIN_PASSWORD');

        if ($adminEmail !== '' || $adminPassword !== '') {
            if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('BEAMERS_ADMIN_EMAIL must be a valid email address.');
            }
            if (strlen($adminPassword) < 12) {
                throw new RuntimeException('BEAMERS_ADMIN_PASSWORD must be at least 12 characters.');
            }

            $name = trim((string) getenv('BEAMERS_ADMIN_NAME')) ?: 'Showroom Administrator';
            $password = password_hash($adminPassword, PASSWORD_DEFAULT);
            $insert = $conn->prepare('INSERT INTO `admin_users` (`name`, `email`, `password`) VALUES (?, ?, ?)');
            $insert->bind_param('sss', $name, $adminEmail, $password);
            $insert->execute();
            $insert->close();
        }
    }
}

function dealer_setting(mysqli $conn, string $key, string $default = ''): string
{
    $statement = $conn->prepare('SELECT `setting_value` FROM `dealer_settings` WHERE `setting_key` = ? LIMIT 1');
    $statement->bind_param('s', $key);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    $statement->close();
    return $row ? (string)$row['setting_value'] : $default;
}

function admin_entities(): array
{
    return [
        'customers' => [
            'table' => 'register',
            'label' => 'Customers',
            'singular' => 'customer',
            'description' => 'Registered showroom accounts',
            'fields' => [
                'first_name' => ['column' => 'First_Name', 'label' => 'First name', 'type' => 'text', 'required' => true],
                'last_name' => ['column' => 'Last_Name', 'label' => 'Last name', 'type' => 'text', 'required' => true],
                'email' => ['column' => 'E-mail', 'label' => 'Email address', 'type' => 'email', 'required' => true],
            ],
        ],
        'test-drives' => [
            'table' => 'test drive',
            'label' => 'Test drives',
            'singular' => 'test-drive request',
            'description' => 'Requests submitted from the test-drive form',
            'fields' => [
                'series' => ['column' => 'Series', 'label' => 'Series', 'type' => 'select', 'required' => true, 'options' => ['BMW 3 Series', 'BMW 5 Series', 'BMW 7 Series', 'BMW X Series']],
                'model' => ['column' => 'Model', 'label' => 'Model', 'type' => 'text', 'required' => true],
                'first_name' => ['column' => 'First_Name', 'label' => 'First name', 'type' => 'text', 'required' => true],
                'last_name' => ['column' => 'Last_Name', 'label' => 'Last name', 'type' => 'text', 'required' => true],
                'email' => ['column' => 'E-Mail', 'label' => 'Email address', 'type' => 'email', 'required' => true],
                'mobile' => ['column' => 'Mobile_No', 'label' => 'Mobile number', 'type' => 'tel', 'required' => true],
                'city' => ['column' => 'City', 'label' => 'City', 'type' => 'select', 'required' => true, 'options' => ['Ahmedabad', 'Mumbai', 'Delhi', 'Bangalore', 'Chennai']],
                'finance' => ['column' => 'Finance', 'label' => 'Finance interest', 'type' => 'select', 'required' => true, 'options' => ['yes' => 'Yes', 'no' => 'Not interested']],
                'configuration' => ['column' => 'Configuration', 'label' => 'Configurator selection', 'type' => 'text', 'required' => false],
                'car_id' => ['column' => 'Car_ID', 'label' => 'Selected car ID', 'type' => 'number', 'required' => false],
                'request_type' => ['column' => 'Request_Type', 'label' => 'Request type', 'type' => 'select', 'required' => true, 'options' => ['test_drive' => 'Test drive', 'offer' => 'Offer']],
                'appointment_date' => ['column' => 'Appointment_Date', 'label' => 'Preferred / confirmed date', 'type' => 'date', 'required' => false],
                'appointment_time' => ['column' => 'Appointment_Time', 'label' => 'Preferred / confirmed time', 'type' => 'time', 'required' => false],
            ],
        ],
        'car-services' => [
            'table' => 'car_service_requests',
            'label' => 'Car service requests',
            'singular' => 'car-service request',
            'description' => 'Maintenance and repair requests submitted by BMW owners',
            'fields' => [
                'vehicle_model' => ['column' => 'Vehicle_Model', 'label' => 'BMW model', 'type' => 'text', 'required' => true],
                'registration_number' => ['column' => 'Registration_Number', 'label' => 'Registration number', 'type' => 'text', 'required' => true],
                'service_type' => ['column' => 'Service_Type', 'label' => 'Service needed', 'type' => 'select', 'required' => true, 'options' => ['Periodic maintenance', 'Repair / diagnosis', 'Tyres / brakes', 'Bodywork', 'Other']],
                'first_name' => ['column' => 'First_Name', 'label' => 'First name', 'type' => 'text', 'required' => true],
                'last_name' => ['column' => 'Last_Name', 'label' => 'Last name', 'type' => 'text', 'required' => true],
                'email' => ['column' => 'E-Mail', 'label' => 'Email address', 'type' => 'email', 'required' => true],
                'mobile' => ['column' => 'Mobile_No', 'label' => 'Mobile number', 'type' => 'tel', 'required' => true],
                'city' => ['column' => 'City', 'label' => 'Service city', 'type' => 'select', 'required' => true, 'options' => ['Ahmedabad', 'Mumbai', 'Delhi', 'Bangalore', 'Chennai']],
                'preferred_date' => ['column' => 'Preferred_Date', 'label' => 'Preferred date', 'type' => 'date', 'required' => true],
                'preferred_time' => ['column' => 'Preferred_Time', 'label' => 'Preferred time', 'type' => 'time', 'required' => true],
                'notes' => ['column' => 'Notes', 'label' => 'Customer notes', 'type' => 'textarea', 'required' => false],
            ],
        ],
        'sales' => [
            'table' => 'car_bookings',
            'label' => 'Sales bookings',
            'singular' => 'sales booking',
            'description' => 'BMW purchase and reservation requests from prospective buyers',
            'fields' => [
                'car_id' => ['column' => 'Car_ID', 'label' => 'Selected car ID', 'type' => 'number', 'required' => false],
                'vehicle_name' => ['column' => 'Vehicle_Name', 'label' => 'BMW model', 'type' => 'text', 'required' => true],
                'vehicle_price' => ['column' => 'Vehicle_Price', 'label' => 'Vehicle price', 'type' => 'text', 'required' => false],
                'first_name' => ['column' => 'First_Name', 'label' => 'First name', 'type' => 'text', 'required' => true],
                'last_name' => ['column' => 'Last_Name', 'label' => 'Last name', 'type' => 'text', 'required' => true],
                'email' => ['column' => 'E-Mail', 'label' => 'Email address', 'type' => 'email', 'required' => true],
                'mobile' => ['column' => 'Mobile_No', 'label' => 'Mobile number', 'type' => 'tel', 'required' => true],
                'city' => ['column' => 'City', 'label' => 'City', 'type' => 'select', 'required' => true, 'options' => ['Ahmedabad', 'Mumbai', 'Delhi', 'Bangalore', 'Chennai']],
                'payment_method' => ['column' => 'Payment_Method', 'label' => 'Payment preference', 'type' => 'select', 'required' => true, 'options' => ['finance' => 'Finance / EMI', 'cash' => 'Full payment']],
                'trade_in' => ['column' => 'Trade_In', 'label' => 'Trade-in vehicle', 'type' => 'select', 'required' => true, 'options' => ['yes' => 'Yes', 'no' => 'No']],
                'notes' => ['column' => 'Notes', 'label' => 'Customer notes', 'type' => 'textarea', 'required' => false],
            ],
        ],
        'feedback' => [
            'table' => 'feedback',
            'label' => 'Feedback',
            'singular' => 'feedback entry',
            'description' => 'Visitor feedback and showroom ratings',
            'fields' => [
                'name' => ['column' => 'name', 'label' => 'Customer name', 'type' => 'text', 'required' => true],
                'email' => ['column' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true],
                'model' => ['column' => 'model', 'label' => 'BMW model', 'type' => 'text', 'required' => false],
                'rating' => ['column' => 'rating', 'label' => 'Rating', 'type' => 'select', 'required' => false, 'options' => ['1', '2', '3', '4', '5']],
                'message' => ['column' => 'message', 'label' => 'Feedback message', 'type' => 'textarea', 'required' => true],
            ],
        ],
        'cars' => [
            'table' => 'cars',
            'label' => 'BMW Cars',
            'singular' => 'car',
            'description' => 'Manage every detail of your vehicle detail pages: hero presentation, pricing & privileges, performance matrix, powertrain & battery specs, chassis & dimensions, interactive visualizer paints, and editorial stories.',
            'table_fields' => ['name', 'series', 'category', 'price', 'power', 'acceleration', 'fuel', 'image', 'status'],
            'fields' => [
                // 1. IDENTITY & HERO PRESENTATION
                'name' => ['column' => 'name', 'label' => 'Model Name', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. BMW M5 Sedan', 'group' => '1. Identity & Hero Presentation'],
                'series' => ['column' => 'series', 'label' => 'Series', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. M Series, 5 Series, i Series'],
                'category' => ['column' => 'category', 'label' => 'Showroom Category', 'type' => 'select', 'required' => true, 'options' => ['m' => 'M Division', 'electric' => 'i Electric', 'x' => 'X Range SUVs', 'classic' => 'Classic Sedans']],
                'vehicle_type' => ['column' => 'vehicle_type', 'label' => 'Vehicle Type', 'type' => 'text', 'required' => true, 'placeholder' => 'High-Performance Sedan, Coupé, SAV'],
                'variant' => ['column' => 'variant', 'label' => 'Variant / Generation', 'type' => 'text', 'required' => false, 'placeholder' => 'M5 Sedan (G90) Plug-in Hybrid'],
                'hero_subtitle' => ['column' => 'hero_subtitle', 'label' => 'Hero Subtitle / Tagline', 'type' => 'text', 'required' => false, 'placeholder' => 'SUPERCAR PERFORMANCE MEETS ELECTRIFIED LUXURY.'],
                'hero_video' => ['column' => 'hero_video', 'label' => 'Hero Video (path or HTTPS URL)', 'type' => 'text', 'required' => false, 'placeholder' => 'videos/video-1.mp4 or https://...'],
                'configure_url' => ['column' => 'configure_url', 'label' => 'Configurator URL', 'type' => 'text', 'required' => false, 'placeholder' => 'configrator.php'],
                'brochure' => ['column' => 'brochure', 'label' => 'Brochure Path or URL', 'type' => 'text', 'required' => false, 'placeholder' => 'docs/Beamers Legacy.docx'],
                'status' => ['column' => 'status', 'label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],

                // 2. PRICING & FINANCING
                'price' => ['column' => 'price', 'label' => 'Ex-Showroom Starting Price', 'type' => 'text', 'required' => false, 'placeholder' => '₹ 2,11,50,000', 'group' => '2. Pricing & Ownership Privileges'],
                'emi' => ['column' => 'emi', 'label' => 'Monthly EMI Guidance', 'type' => 'text', 'required' => false, 'placeholder' => '₹ 1,99,999 / month*'],
                'offers' => ['column' => 'offers', 'label' => 'Ownership Privileges & Offers (one per line)', 'type' => 'textarea', 'required' => false, 'placeholder' => "BMW 360° Financial Plan with attractive EMI starting at ₹ 1,99,999/month*\nComplimentary BMW Service Inclusive Plus (3 Yrs / 40,000 km)\nComplimentary BMW Wallbox charger with 8-Yr battery warranty\nExclusive BMW M Track Experience invitation at Buddh International Circuit"],

                // 3. HERO PERFORMANCE MATRIX & DYNAMICS
                'power' => ['column' => 'power', 'label' => 'System Total Power Output', 'type' => 'text', 'required' => true, 'placeholder' => '727 hp', 'group' => '3. Key Performance Matrix & Metrics'],
                'acceleration' => ['column' => 'acceleration', 'label' => '0–100 km/h Acceleration', 'type' => 'text', 'required' => true, 'placeholder' => '3.5 s'],
                'accel_200' => ['column' => 'accel_200', 'label' => '0–200 km/h Acceleration', 'type' => 'text', 'required' => false, 'placeholder' => '10.9 s'],
                'torque' => ['column' => 'torque', 'label' => 'Peak System Torque', 'type' => 'text', 'required' => false, 'placeholder' => '1,000 Nm'],
                'top_speed' => ['column' => 'top_speed', 'label' => 'Top Speed', 'type' => 'text', 'required' => false, 'placeholder' => '305 km/h'],
                'drive_or_range' => ['column' => 'drive_or_range', 'label' => 'Electric Range / Drivetrain Value', 'type' => 'text', 'required' => true, 'placeholder' => '69 km / M xDrive'],
                'metric_label' => ['column' => 'metric_label', 'label' => 'Range / Drive Metric Label', 'type' => 'select', 'required' => true, 'options' => ['Electric Range', 'Drive', 'Range', 'Drive / Range']],

                // 4. POWERTRAIN & BATTERY SPECIFICATIONS
                'fuel' => ['column' => 'fuel', 'label' => 'Powertrain / Fuel Type', 'type' => 'select', 'required' => false, 'options' => ['Plug-in Hybrid', 'Petrol', 'Electric', 'Diesel', 'Hybrid'], 'group' => '4. Powertrain, Electric & Battery Data'],
                'engine' => ['column' => 'engine', 'label' => 'Engine Specification', 'type' => 'text', 'required' => false, 'placeholder' => '4.4L M TwinPower Turbo V8 + Electric Motor'],
                'displacement' => ['column' => 'displacement', 'label' => 'Displacement', 'type' => 'text', 'required' => false, 'placeholder' => '4,395 cc'],
                'engine_power' => ['column' => 'engine_power', 'label' => 'Combustion Engine Output', 'type' => 'text', 'required' => false, 'placeholder' => '585 hp (430 kW) @ 5,600–6,500 rpm'],
                'engine_torque' => ['column' => 'engine_torque', 'label' => 'Combustion Engine Torque', 'type' => 'text', 'required' => false, 'placeholder' => '750 Nm @ 1,800–5,400 rpm'],
                'electric_power' => ['column' => 'electric_power', 'label' => 'Electric Motor Output', 'type' => 'text', 'required' => false, 'placeholder' => '197 hp (145 kW) @ 6,000 rpm'],
                'electric_torque' => ['column' => 'electric_torque', 'label' => 'Electric Motor Torque', 'type' => 'text', 'required' => false, 'placeholder' => '280 Nm (450 Nm with pre-gearing)'],
                'battery_capacity' => ['column' => 'battery_capacity', 'label' => 'High-Voltage Battery Capacity', 'type' => 'text', 'required' => false, 'placeholder' => '18.6 kWh net (Lithium-ion)'],
                'electric_range' => ['column' => 'electric_range', 'label' => 'Pure Electric Range (WLTP)', 'type' => 'text', 'required' => false, 'placeholder' => 'Up to 69 km'],
                'charging_time' => ['column' => 'charging_time', 'label' => 'Charging Time (0-100% AC)', 'type' => 'text', 'required' => false, 'placeholder' => 'Approx. 3h 15m @ 7.4 kW Wallbox'],
                'mileage' => ['column' => 'mileage', 'label' => 'Mileage / Consumption', 'type' => 'text', 'required' => false, 'placeholder' => '58.8 km/l (Weighted combined)'],

                // 5. TRANSMISSION, CHASSIS & DIMENSIONS
                'transmission' => ['column' => 'transmission', 'label' => 'Transmission', 'type' => 'text', 'required' => false, 'placeholder' => '8-Speed M Steptronic with Drivelogic', 'group' => '5. Transmission, Chassis & Dimensions'],
                'drivetrain' => ['column' => 'drivetrain', 'label' => 'Drivetrain Concept', 'type' => 'text', 'required' => false, 'placeholder' => 'M xDrive with Active M Differential (4WD, 4WD Sport, 2WD)'],
                'suspension' => ['column' => 'suspension', 'label' => 'Suspension System', 'type' => 'text', 'required' => false, 'placeholder' => 'Adaptive M suspension with electronically controlled dampers'],
                'wheels_front' => ['column' => 'wheels_front', 'label' => 'Front Wheels & Tyres', 'type' => 'text', 'required' => false, 'placeholder' => '285/40 ZR20 on 20-inch M alloy wheels'],
                'wheels_rear' => ['column' => 'wheels_rear', 'label' => 'Rear Wheels & Tyres', 'type' => 'text', 'required' => false, 'placeholder' => '295/35 ZR21 on 21-inch M alloy wheels'],
                'dimensions' => ['column' => 'dimensions', 'label' => 'Dimensions (L × W × H)', 'type' => 'text', 'required' => false, 'placeholder' => '5,096 × 1,970 × 1,510 mm'],
                'wheelbase' => ['column' => 'wheelbase', 'label' => 'Wheelbase', 'type' => 'text', 'required' => false, 'placeholder' => '3,006 mm'],
                'weight' => ['column' => 'weight', 'label' => 'Unladen Weight (DIN)', 'type' => 'text', 'required' => false, 'placeholder' => '2,435 kg'],
                'seating_capacity' => ['column' => 'seating_capacity', 'label' => 'Seating Capacity', 'type' => 'text', 'required' => false, 'placeholder' => '5 Seats'],
                'boot_capacity' => ['column' => 'boot_capacity', 'label' => 'Boot / Luggage Capacity', 'type' => 'text', 'required' => false, 'placeholder' => '466 litres'],
                'fuel_tank' => ['column' => 'fuel_tank', 'label' => 'Fuel Tank Capacity', 'type' => 'text', 'required' => false, 'placeholder' => '60 litres'],

                // 6. EDITORIAL, VISUALIZER PAINTS & MEDIA
                'short_description' => ['column' => 'short_description', 'label' => 'Overview Short Description', 'type' => 'textarea', 'required' => false, 'placeholder' => 'The seventh generation of the BMW M5 Sedan marks the arrival of high-performance electrification...', 'group' => '6. Editorial Stories, Visualizer Paints & Media'],
                'editorial_quote' => ['column' => 'editorial_quote', 'label' => 'Editorial Statement / Quote', 'type' => 'textarea', 'required' => false, 'placeholder' => '“The new BMW M5 Sedan combines legendary M high-performance character with state-of-the-art electrification.”'],
                'highlights' => ['column' => 'highlights', 'label' => 'Feature Highlights (one per line)', 'type' => 'textarea', 'required' => false, 'placeholder' => "M HYBRID System with 727 hp & 1,000 Nm torque\n0–100 km/h in 3.5 seconds with launch control\nM xDrive with 2WD drift mode\nBMW Curved Display with M-Specific OS 8.5\nContour-illuminated Iconic Glow M kidney grille\nLightweight M Carbon roof with quad exhaust tailpipes"],
                'story_sections' => ['column' => 'story_sections', 'label' => 'Design & Engineering Stories (heading | description | image_path per line)', 'type' => 'textarea', 'required' => false, 'placeholder' => "M HYBRID High-Performance Drivetrain|Combines a ferocious V8 with an electric motor.|images/bg m4 image.jpg\nM xDrive with Active M Differential|Switch between 4WD, 4WD Sport, and 2WD drift mode.|images/m4 cs.png"],
                'custom_colors' => ['column' => 'custom_colors', 'label' => 'Interactive Visualizer Paints (Color Name:#HEX per line)', 'type' => 'textarea', 'required' => false, 'placeholder' => "Isle of Man Green Metallic:#134a36\nMarina Bay Blue Metallic:#094381\nFrozen Deep Grey (Matte):#474b4e\nBrooklyn Grey Metallic:#8b9095\nToronto Red Metallic:#a81b23\nCarbon Black Metallic:#151922\nAlpine White Solid:#f0f3f6"],
                'image' => ['column' => 'image', 'label' => 'Hero & Studio Car Image', 'type' => 'file', 'required' => false, 'accept' => 'image/jpeg,image/png,image/webp,image/gif', 'help' => 'Choose a JPG, PNG, WEBP or GIF image from your computer (Max 5 MB).'],
                'gallery' => ['column' => 'gallery', 'label' => 'Gallery Images (one path or HTTPS URL per line)', 'type' => 'textarea', 'required' => false],
            ],
        ],
    ];
}

function admin_entity(string $key): ?array
{
    $entities = admin_entities();
    return $entities[$key] ?? null;
}

function admin_csrf_token(): string
{
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

function admin_verify_csrf(): bool
{
    return isset($_POST['csrf'], $_SESSION['admin_csrf'])
        && hash_equals($_SESSION['admin_csrf'], (string) $_POST['csrf']);
}

function admin_require_login(): void
{
    if (empty($_SESSION['admin_user_id'])) {
        header('Location: ../adminlogin.php?error=session');
        exit;
    }
}

function admin_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function admin_get_flash(): ?array
{
    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    return $flash;
}

function admin_car_upload(array $file): ?string
{
    if (!isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('The car image could not be uploaded. Please choose the image again.');
    }

    if ((int)$file['size'] <= 0 || (int)$file['size'] > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Car images must be smaller than 5 MB.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('Invalid image upload.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($allowed[$mime])) {
        throw new InvalidArgumentException('Only JPG, PNG, WEBP and GIF car images are allowed.');
    }

    $dimensions = @getimagesize($file['tmp_name']);
    if ($dimensions === false || empty($dimensions[0]) || empty($dimensions[1])) {
        throw new InvalidArgumentException('The selected file is not a valid image.');
    }

    $uploadDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cars';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('The car image folder could not be created. Check folder permissions.');
    }

    $baseName = pathinfo((string)$file['name'], PATHINFO_FILENAME);
    $baseName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $baseName) ?: 'bmw-car';
    $baseName = trim($baseName, '-_');
    $fileName = strtolower($baseName) . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The car image could not be saved. Check folder permissions.');
    }

    return 'images/cars/' . $fileName;
}

function admin_delete_car_image(?string $storedPath): void
{
    $storedPath = trim((string)$storedPath);
    if ($storedPath === '' || preg_match('#^https?://#i', $storedPath)) {
        return;
    }

    function admin_store_upload(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Please choose a valid product image.');
        }
        if (($file['size'] ?? 0) > 5 * 1024 * 1024 || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Product images must be valid files smaller than 5 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new InvalidArgumentException('Only valid JPG, PNG, WEBP or GIF images are allowed.');
        }
        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'store';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('The store image folder could not be created.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $name)) {
            throw new RuntimeException('The product image could not be saved.');
        }
        return 'images/store/' . $name;
    }

    function admin_delete_store_image(?string $storedPath): void
    {
        $normalized = str_replace('\\', '/', (string)$storedPath);
        if (!str_starts_with($normalized, 'images/store/')) return;
        $file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
        if (is_file($file)) unlink($file);
    }

    $normalized = ltrim(str_replace('\\', '/', $storedPath), '/');
    if (!str_starts_with($normalized, 'images/cars/')) {
        return;
    }

    $fileName = basename($normalized);
    $target = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'cars' . DIRECTORY_SEPARATOR . $fileName;
    if (is_file($target)) {
        @unlink($target);
    }
}

function admin_car_gallery_uploads(array $files): array
{
    if (!isset($files['gallery_uploads']) || !is_array($files['gallery_uploads'])) {
        return [];
    }

    $uploads = $files['gallery_uploads'];
    if (!isset($uploads['name'], $uploads['type'], $uploads['tmp_name'], $uploads['error'], $uploads['size'])
        || !is_array($uploads['name'])
        || count($uploads['name']) > 10) {
        throw new InvalidArgumentException('Upload no more than 10 gallery images at a time.');
    }

    $savedPaths = [];
    try {
        foreach ($uploads['name'] as $index => $name) {
            $upload = [
                'name' => $name,
                'type' => $uploads['type'][$index] ?? '',
                'tmp_name' => $uploads['tmp_name'][$index] ?? '',
                'error' => $uploads['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $uploads['size'][$index] ?? 0,
            ];
            if ((int)$upload['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $savedPaths[] = admin_car_upload($upload);
        }
    } catch (Throwable $error) {
        foreach ($savedPaths as $path) {
            admin_delete_car_image($path);
        }
        throw $error;
    }

    return $savedPaths;
}

function admin_validate_uploaded_image_field(array $inputFiles): ?array
{
    return isset($inputFiles['image']) && is_array($inputFiles['image']) ? $inputFiles['image'] : null;
}

function admin_validate_entity(array $entity, array $input): array
{
    $values = [];

    foreach ($entity['fields'] as $key => $field) {
        $value = trim(str_replace("\0", '', (string) ($input[$key] ?? '')));
        if ($field['required'] && $value === '') {
            throw new InvalidArgumentException("{$field['label']} is required.");
        }
        if ($key === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        if ($key === 'rating' && $value !== '' && !in_array($value, ['1', '2', '3', '4', '5'], true)) {
            throw new InvalidArgumentException('Rating must be between 1 and 5.');
        }
        $maxLength = in_array($key, ['message', 'notes', 'description', 'editorial_quote', 'gallery', 'story_sections', 'highlights', 'offers', 'custom_colors'], true)
            ? 8000
            : (in_array($key, ['hero_subtitle', 'hero_video', 'brochure'] , true) ? 500 : (in_array($key, ['short_description'], true) ? 1000 : 255));
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException("{$field['label']} is too long.");
        }
        if ($key === 'hero_video' && $value !== '') {
            $videoPath = parse_url($value, PHP_URL_PATH);
            $isHttpsVideo = strtolower((string)parse_url($value, PHP_URL_SCHEME)) === 'https'
                && filter_var($value, FILTER_VALIDATE_URL) !== false
                && is_string($videoPath)
                && preg_match('/\.(mp4|webm)$/i', $videoPath);
            $isLocalVideo = preg_match('#^videos/[A-Za-z0-9._/-]+\.(mp4|webm)$#i', $value)
                && !str_contains($value, '..');
            if (!$isHttpsVideo && !$isLocalVideo) {
                throw new InvalidArgumentException('Hero video must be an MP4 or WEBM under videos/ or an HTTPS video URL.');
            }
        }
        if ($key === 'story_sections' && $value !== '') {
            foreach (preg_split('/\R/', trim($value)) ?: [] as $sectionLine) {
                if (trim($sectionLine) === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $sectionLine, 3));
                if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                    throw new InvalidArgumentException('Each editorial section needs a heading and description separated by |.');
                }
                if (!empty($parts[2])) {
                    $imageUrl = filter_var($parts[2], FILTER_VALIDATE_URL) !== false
                        && strtolower((string)parse_url($parts[2], PHP_URL_SCHEME)) === 'https';
                    $localImage = preg_match('#^(images|docs)/[A-Za-z0-9._/-]+$#', $parts[2])
                        && !str_contains($parts[2], '..');
                    if (!$imageUrl && !$localImage) {
                        throw new InvalidArgumentException('Editorial section images must use an images/ or docs/ path, or an HTTPS URL.');
                    }
                }
            }
        }
        $values[$field['column']] = $value === '' ? null : $value;
    }

    return $values;
}

function admin_email_in_use(mysqli $conn, array $entity, string $email, ?int $excludeId = null): bool
{
    if ($email === '') {
        return false;
    }
    $emailColumn = $entity['fields']['email']['column'];
    $sql = "SELECT `id` FROM `{$entity['table']}` WHERE `{$emailColumn}` = ?";
    if ($excludeId !== null) {
        $sql .= ' AND `id` <> ?';
    }
    $sql .= ' LIMIT 1';
    $statement = $conn->prepare($sql);
    if ($excludeId === null) {
        $statement->bind_param('s', $email);
    } else {
        $statement->bind_param('si', $email, $excludeId);
    }
    $statement->execute();
    $used = $statement->get_result()->num_rows > 0;
    $statement->close();
    return $used;
}

function admin_create_entity(mysqli $conn, string $entityKey, array $input, array $files = []): void
{
    $entity = admin_entity($entityKey);
    if ($entity === null) {
        throw new InvalidArgumentException('Unknown record type.');
    }

    $values = admin_validate_entity($entity, $input);
    $newImage = null;
    $newGallery = [];
    try {
        if ($entityKey === 'customers') {
            $password = (string) ($input['password'] ?? '');
            if (strlen($password) < 8) {
                throw new InvalidArgumentException('A customer password must have at least 8 characters.');
            }
            if (admin_email_in_use($conn, $entity, (string) $values['E-mail'])) {
                throw new InvalidArgumentException('A customer with that email address already exists.');
            }
            $values['Password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if (in_array($entityKey, ['test-drives', 'car-services', 'sales'], true)) {
            $customerId = 0;
            $email = (string)$values['E-Mail'];
            $customerLookup = $conn->prepare('SELECT `id` FROM `register` WHERE LOWER(`E-mail`) = LOWER(?) LIMIT 1');
            $customerLookup->bind_param('s', $email);
            $customerLookup->execute();
            $customer = $customerLookup->get_result()->fetch_assoc();
            $customerLookup->close();
            if ($customer) {
                $customerId = (int)$customer['id'];
            }
            $values['Customer_ID'] = $customerId > 0 ? $customerId : null;
            $values['Status'] = 'pending';
        }

        if ($entityKey === 'cars') {
            $values['slug'] = app_unique_car_slug($conn, (string)$values['name']);
            unset($values['image']);
            $upload = admin_validate_uploaded_image_field($files);
            if ($upload !== null) {
                $newImage = admin_car_upload($upload);
                $values['image'] = $newImage;
            }
            $newGallery = admin_car_gallery_uploads($files);
            if ($newGallery) {
                $galleryLines = preg_split('/\R/', trim((string)($values['gallery'] ?? ''))) ?: [];
                $galleryLines = array_values(array_filter(array_map('trim', $galleryLines)));
                $values['gallery'] = implode("\n", array_merge($galleryLines, $newGallery));
                if (mb_strlen($values['gallery']) > 8000) {
                    throw new InvalidArgumentException('Gallery image paths are too long.');
                }
            }
        }

        $columns = array_keys($values);
        $escapedColumns = implode(', ', array_map(static fn($column) => "`{$column}`", $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $statement = $conn->prepare("INSERT INTO `{$entity['table']}` ({$escapedColumns}) VALUES ({$placeholders})");
        $params = array_values($values);
        $statement->bind_param(str_repeat('s', count($params)), ...$params);
        $statement->execute();
        $statement->close();
    } catch (Throwable $error) {
        if ($newImage !== null) {
            admin_delete_car_image($newImage);
        }
        foreach ($newGallery as $path) {
            admin_delete_car_image($path);
        }
        throw $error;
    }
}

function admin_update_entity(mysqli $conn, string $entityKey, int $id, array $input, array $files = []): void
{
    $entity = admin_entity($entityKey);
    if ($entity === null || $id < 1) {
        throw new InvalidArgumentException('The selected record is invalid.');
    }

    $values = admin_validate_entity($entity, $input);
    $newImage = null;
    $newGallery = [];
    $oldImage = null;
    try {
        if ($entityKey === 'customers') {
            if (admin_email_in_use($conn, $entity, (string) $values['E-mail'], $id)) {
                throw new InvalidArgumentException('A customer with that email address already exists.');
            }
            $password = (string) ($input['password'] ?? '');
            if ($password !== '') {
                if (strlen($password) < 8) {
                    throw new InvalidArgumentException('A new customer password must have at least 8 characters.');
                }
                $values['Password'] = password_hash($password, PASSWORD_DEFAULT);
            } else {
                unset($values['Password']);
            }
        }

        if (in_array($entityKey, ['test-drives', 'car-services', 'sales'], true)) {
            unset($values['Customer_ID']);
            unset($values['Status']);
            unset($values['Appointment_Date']);
            unset($values['Appointment_Time']);
        }

        if ($entityKey === 'cars') {
            $values['slug'] = app_unique_car_slug($conn, (string)$values['name'], $id);
            $current = $conn->prepare('SELECT `image` FROM `cars` WHERE `id` = ? LIMIT 1');
            $current->bind_param('i', $id);
            $current->execute();
            $currentRecord = $current->get_result()->fetch_assoc();
            $current->close();
            if (!$currentRecord) {
                throw new InvalidArgumentException('The selected car no longer exists.');
            }
            $oldImage = $currentRecord['image'] ?? null;
            unset($values['image']);

            $upload = admin_validate_uploaded_image_field($files);
            if ($upload !== null) {
                $newImage = admin_car_upload($upload);
                $values['image'] = $newImage;
            } elseif (!empty($input['remove_image'])) {
                $values['image'] = null;
            }
            $newGallery = admin_car_gallery_uploads($files);
            if ($newGallery) {
                $galleryLines = preg_split('/\R/', trim((string)($values['gallery'] ?? ''))) ?: [];
                $galleryLines = array_values(array_filter(array_map('trim', $galleryLines)));
                $values['gallery'] = implode("\n", array_merge($galleryLines, $newGallery));
                if (mb_strlen($values['gallery']) > 8000) {
                    throw new InvalidArgumentException('Gallery image paths are too long.');
                }
            }
        }

        if (!$values) {
            throw new InvalidArgumentException('There are no changes to save.');
        }

        $set = implode(', ', array_map(static fn($column) => "`{$column}` = ?", array_keys($values)));
        $statement = $conn->prepare("UPDATE `{$entity['table']}` SET {$set} WHERE `id` = ?");
        $params = array_values($values);
        $params[] = $id;
        $statement->bind_param(str_repeat('s', count($values)) . 'i', ...$params);
        $statement->execute();
        $statement->close();

        if ($entityKey === 'cars' && ($newImage !== null || !empty($input['remove_image']))) {
            admin_delete_car_image($oldImage);
        }
    } catch (Throwable $error) {
        if ($newImage !== null) {
            admin_delete_car_image($newImage);
        }
        foreach ($newGallery as $path) {
            admin_delete_car_image($path);
        }
        throw $error;
    }
}

function admin_delete_entity(mysqli $conn, string $entityKey, int $id): void
{
    $entity = admin_entity($entityKey);
    if ($entity === null || $id < 1) {
        throw new InvalidArgumentException('The selected record is invalid.');
    }
    $statement = $conn->prepare("DELETE FROM `{$entity['table']}` WHERE `id` = ?");
    $statement->bind_param('i', $id);
    $statement->execute();
    $statement->close();
}

function admin_records(mysqli $conn, string $entityKey, ?int $limit = null): array
{
    $entity = admin_entity($entityKey);
    if ($entity === null) {
        return [];
    }
    $limitSql = $limit !== null ? ' LIMIT ' . max(1, (int) $limit) : '';
    $result = $conn->query("SELECT * FROM `{$entity['table']}` ORDER BY `id` DESC{$limitSql}");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function admin_count(mysqli $conn, string $entityKey): int
{
    $entity = admin_entity($entityKey);
    if ($entity === null) {
        return 0;
    }
    $result = $conn->query("SELECT COUNT(*) AS total FROM `{$entity['table']}`");
    return (int) $result->fetch_assoc()['total'];
}
