CREATE DATABASE IF NOT EXISTS bmw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bmw;

CREATE TABLE IF NOT EXISTS register (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  First_Name VARCHAR(100) NOT NULL,
  Last_Name VARCHAR(100) NOT NULL,
  `E-mail` VARCHAR(191) NOT NULL UNIQUE,
  Password VARCHAR(255) NOT NULL,
  About VARCHAR(500) NULL,
  Website VARCHAR(255) NULL,
  Username VARCHAR(50) NULL,
  Firebase_UID VARCHAR(128) NULL,
  Photo_URL VARCHAR(500) NULL,
  Auth_Provider VARCHAR(100) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_register_firebase_uid (Firebase_UID)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `test drive` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  Series VARCHAR(100) NOT NULL,
  Model VARCHAR(100) NOT NULL,
  First_Name VARCHAR(100) NOT NULL,
  Last_Name VARCHAR(100) NOT NULL,
  `E-Mail` VARCHAR(191) NOT NULL,
  Mobile_No VARCHAR(30) NOT NULL,
  City VARCHAR(100) NOT NULL,
  Finance VARCHAR(30) NOT NULL DEFAULT 'no',
  Configuration VARCHAR(255) NULL,
  Car_ID INT UNSIGNED NULL,
  Request_Type ENUM('test_drive','offer') NOT NULL DEFAULT 'test_drive',
  Customer_ID INT UNSIGNED NULL,
  Status ENUM('pending','accepted','rejected','rescheduled','cancelled') NOT NULL DEFAULT 'pending',
  Appointment_Date DATE NULL,
  Appointment_Time TIME NULL,
  KEY idx_test_drive_car_id (Car_ID),
  KEY idx_test_drive_customer_id (Customer_ID),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS car_service_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  Vehicle_Model VARCHAR(120) NOT NULL,
  Registration_Number VARCHAR(30) NOT NULL,
  Service_Type VARCHAR(60) NOT NULL,
  First_Name VARCHAR(100) NOT NULL,
  Last_Name VARCHAR(100) NOT NULL,
  `E-Mail` VARCHAR(191) NOT NULL,
  Mobile_No VARCHAR(30) NOT NULL,
  City VARCHAR(100) NOT NULL,
  Preferred_Date DATE NOT NULL,
  Preferred_Time TIME NOT NULL,
  Notes TEXT NULL,
  Customer_ID INT UNSIGNED NULL,
  Status ENUM('pending','accepted','rejected','rescheduled','cancelled') NOT NULL DEFAULT 'pending',
  Appointment_Date DATE NULL,
  Appointment_Time TIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_car_service_customer_id (Customer_ID),
  KEY idx_car_service_status (Status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS car_bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  Car_ID INT UNSIGNED NULL,
  Vehicle_Name VARCHAR(120) NOT NULL,
  Vehicle_Price VARCHAR(80) NULL,
  First_Name VARCHAR(100) NOT NULL,
  Last_Name VARCHAR(100) NOT NULL,
  `E-Mail` VARCHAR(191) NOT NULL,
  Mobile_No VARCHAR(30) NOT NULL,
  City VARCHAR(100) NOT NULL,
  Payment_Method VARCHAR(30) NOT NULL DEFAULT 'finance',
  Trade_In VARCHAR(10) NOT NULL DEFAULT 'no',
  Notes TEXT NULL,
  Customer_ID INT UNSIGNED NULL,
  Status ENUM('pending','contacted','reserved','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_car_bookings_car_id (Car_ID),
  KEY idx_car_bookings_customer_id (Customer_ID),
  KEY idx_car_bookings_status (Status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS feedback (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(191) NOT NULL,
  model VARCHAR(120) NULL,
  rating TINYINT UNSIGNED NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient_type ENUM('customer','admin') NOT NULL,
  user_id INT UNSIGNED NULL,
  admin_id INT UNSIGNED NULL,
  type VARCHAR(60) NOT NULL,
  title VARCHAR(180) NOT NULL,
  message VARCHAR(1000) NOT NULL,
  reference_id INT UNSIGNED NULL,
  reference_type VARCHAR(40) NULL,
  dedupe_key VARCHAR(191) NULL UNIQUE,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_notifications_customer_unread (recipient_type,user_id,is_read,created_at),
  INDEX idx_notifications_admin_unread (recipient_type,admin_id,is_read,created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_preferences (
  category VARCHAR(40) NOT NULL PRIMARY KEY,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notified_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uniq_stock_notification_subscriber (user_id,product_id),
  INDEX idx_stock_notification_product (product_id,notified_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cars (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  series VARCHAR(80) NOT NULL,
  category VARCHAR(30) NOT NULL DEFAULT 'classic',
  vehicle_type VARCHAR(80),
  hero_video VARCHAR(500),
  variant VARCHAR(120),
  hero_subtitle VARCHAR(180),
  price VARCHAR(80),
  emi VARCHAR(80),
  short_description VARCHAR(500),
  editorial_quote TEXT,
  story_sections TEXT,
  power VARCHAR(30),
  acceleration VARCHAR(30),
  drive_or_range VARCHAR(40),
  metric_label VARCHAR(30) NOT NULL DEFAULT 'Drive',
  configure_url VARCHAR(255),
  fuel VARCHAR(40),
  transmission VARCHAR(60),
  engine VARCHAR(120),
  torque VARCHAR(80),
  mileage VARCHAR(80),
  top_speed VARCHAR(80),
  seating_capacity VARCHAR(40),
  image VARCHAR(255),
  brochure VARCHAR(500),
  gallery TEXT,
  highlights TEXT,
  offers TEXT,
  accel_200 VARCHAR(40),
  displacement VARCHAR(60),
  engine_power VARCHAR(100),
  engine_torque VARCHAR(100),
  electric_power VARCHAR(100),
  electric_torque VARCHAR(100),
  battery_capacity VARCHAR(100),
  electric_range VARCHAR(100),
  charging_time VARCHAR(120),
  drivetrain VARCHAR(120),
  suspension VARCHAR(150),
  wheels_front VARCHAR(120),
  wheels_rear VARCHAR(120),
  dimensions VARCHAR(100),
  wheelbase VARCHAR(60),
  weight VARCHAR(60),
  boot_capacity VARCHAR(60),
  fuel_tank VARCHAR(60),
  custom_colors TEXT,
  description TEXT,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dealer_settings (
  setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
  setting_value TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS store_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  slug VARCHAR(140) NOT NULL UNIQUE,
  description TEXT NULL,
  image VARCHAR(255) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS store_collections (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  slug VARCHAR(140) NOT NULL UNIQUE,
  description TEXT NULL,
  cover_image VARCHAR(255) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS store_products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  sku VARCHAR(80) NULL UNIQUE,
  slug VARCHAR(180) NOT NULL UNIQUE,
  category_id INT UNSIGNED NULL,
  collection_id INT UNSIGNED NULL,
  description TEXT NULL,
  short_description VARCHAR(500) NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  sale_price DECIMAL(12,2) NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'INR',
  stock INT NOT NULL DEFAULT 0,
  low_stock_threshold INT NOT NULL DEFAULT 5,
  status ENUM('active','draft','out_of_stock','archived') NOT NULL DEFAULT 'draft',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  badge VARCHAR(30) NULL,
  image VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (category_id), INDEX (collection_id), INDEX (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS store_product_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (product_id)
) ENGINE=InnoDB;

INSERT INTO `cars` (
  `name`, `slug`, `series`, `category`, `vehicle_type`, `hero_subtitle`,
  `price`, `emi`, `power`, `acceleration`, `drive_or_range`, `metric_label`,
  `fuel`, `engine`, `torque`, `top_speed`, `transmission`, `seating_capacity`,
  `mileage`, `image`, `hero_video`, `brochure`, `short_description`,
  `editorial_quote`, `highlights`, `story_sections`, `offers`, `status`
) VALUES (
  'BMW M5 Sedan', 'bmw-m5-sedan', 'M Series', 'm', 'High-Performance Sedan', 'THE M5 SEDAN — PURE M POWER. ELECTRIFIED.',
  '₹ 2,11,50,000', '₹ 1,99,999 / mo*', '727 hp', '3.5 s', '69 km / xDrive', 'Electric Range',
  'Plug-in Hybrid', '4.4L M TwinPower Turbo V8 + Electric Motor', '1,000 Nm', '305 km/h', '8-Speed M Steptronic', '5 Seats',
  '58.8 km/l', 'images/5 series.png', 'videos/video-1.mp4', 'docs/Beamers Legacy.docx',
  'The first-ever electrified BMW M5 Sedan pairs a 585 hp V8 with an electric motor for an astounding 727 hp and 1,000 Nm of torque.',
  '“Supercar performance meets executive luxury: the new BMW M5 Sedan redefines what an M automobile can achieve.”',
  'M HYBRID System with 727 hp (535 kW) and 1,000 Nm torque\n0–100 km/h sprint in 3.5 seconds with launch control\nM xDrive all-wheel drive with drift-ready 2WD rear mode\nBMW Curved Display with M-Specific OS 8.5 & M Head-Up Display\nContour-illuminated Iconic Glow M kidney grille\nLightweight M Carbon roof with aerodynamic quad exhaust pipes',
  'M HYBRID High-Performance Drivetrain|The first-ever electrified M5 combines a ferocious 4.4-litre M TwinPower Turbo V8 engine with a high-torque electric motor integrated into the 8-speed M Steptronic transmission. Result: 727 hp, 1,000 Nm, and instantaneous electric launch.|images/bg m4 image.jpg\nM xDrive All-Wheel Drive with 2WD Drift Mode|The rear-biased M xDrive distributes power seamlessly across all four wheels. Drivers can toggle from 4WD for extreme grip, to 4WD Sport for agility, or disconnect the front axle entirely in 2WD mode for pure rear-wheel drifting thrills.|images/m4 cs.png\nContour-Illuminated Iconic Glow Grille|An illuminated contour line frames the sculpted M kidney grille. Flared wheel arches, an M Carbon roof, aerodynamic mirror caps, and dual twin exhaust tailpipes leave an unmistakable impression from every angle.|images/5 series.png\nDriver-Focused M Cockpit|Frameless 12.3-inch digital instrument cluster and 14.9-inch central display with M Mode graphics, shift lights, drift analyzer, and M Multifunction seats in Merino leather.|images/bmw color 1.jpg',
  'BMW 360° Financial Plan with attractive EMI starting at ₹ 1,99,999/month*\nComplimentary BMW Service Inclusive Plus maintenance package for 3 years / 40,000 km\nComplimentary BMW Wallbox charger with 8 years / 160,000 km battery warranty\nExclusive BMW M Track Experience invitation at Buddh International Circuit',
  'active'
) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- The first admin is created by Admin/bootstrap.php only when
-- BEAMERS_ADMIN_EMAIL and BEAMERS_ADMIN_PASSWORD are set in the PHP environment.
