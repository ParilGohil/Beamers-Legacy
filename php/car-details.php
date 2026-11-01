<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();

$car = null;
$loadError = false;
$rawSlug = $_GET['slug'] ?? '';
$slug = is_string($rawSlug) ? trim($rawSlug) : '';

// Canonical BMW M5 Sedan flagship default data
$defaultM5Data = [
    'id' => 101,
    'name' => 'BMW M5 Sedan',
    'slug' => 'bmw-m5-sedan',
    'series' => 'M Series',
    'category' => 'm',
    'vehicle_type' => 'High-Performance Sedan',
    'hero_subtitle' => 'THE FIRST-EVER ELECTRIFIED BMW M5 SEDAN WITH M HYBRID TECHNOLOGY',
    'variant' => 'M5 Sedan (G90) Plug-in Hybrid',
    'price' => '₹ 2,11,50,000',
    'emi' => '₹ 1,99,999 / month*',
    'power' => '727 hp',
    'acceleration' => '3.5 s',
    'drive_or_range' => '69 km / M xDrive',
    'metric_label' => 'Electric Range',
    'fuel' => 'Plug-in Hybrid (Petrol)',
    'engine' => '4.4L M TwinPower Turbo V8 (S68) + Synchronous Electric Motor',
    'torque' => '1,000 Nm',
    'top_speed' => '305 km/h',
    'transmission' => '8-Speed M Steptronic with Drivelogic',
    'seating_capacity' => '5 Seats',
    'mileage' => '58.8 km/l (Weighted combined)',
    'image' => 'images/5 series.png',
    'hero_video' => 'videos/video-1.mp4',
    'brochure' => 'docs/Beamers Legacy.docx',
    'short_description' => 'The seventh generation of the BMW M5 Sedan marks the arrival of high-performance electrification. Combining a high-revving 4.4-litre V8 engine with an electric motor, the M HYBRID system produces 727 hp and 1,000 Nm of torque. With M xDrive intelligent all-wheel drive, Adaptive M suspension, and the iconic BMW Curved Display, it seamlessly blends track adrenaline with executive luxury.',
    'editorial_quote' => '“The new BMW M5 Sedan combines legendary M high-performance character with state-of-the-art electrification. It is the most powerful BMW M5 ever engineered.”',
    'highlights' => "M HYBRID System with 727 hp (535 kW) and 1,000 Nm torque\n0–100 km/h sprint in 3.5 seconds with launch control\nM xDrive all-wheel drive with drift-ready 2WD rear mode\nBMW Curved Display with M-Specific OS 8.5 & M Head-Up Display\nContour-illuminated Iconic Glow M kidney grille\nLightweight M Carbon roof with aerodynamic quad exhaust pipes",
    'story_sections' => "M HYBRID High-Performance Drivetrain|The first-ever electrified M5 combines a ferocious 4.4-litre M TwinPower Turbo V8 engine with a high-torque electric motor integrated into the 8-speed M Steptronic transmission. Result: 727 hp, 1,000 Nm, and instantaneous electric launch.|images/bg m4 image.jpg\nM xDrive All-Wheel Drive with 2WD Drift Mode|The rear-biased M xDrive distributes power seamlessly across all four wheels. Drivers can toggle from 4WD for extreme grip, to 4WD Sport for agility, or disconnect the front axle entirely in 2WD mode for pure rear-wheel drifting thrills.|images/m4 cs.png\nContour-Illuminated Iconic Glow Grille|An illuminated contour line frames the sculpted M kidney grille. Flared wheel arches, an M Carbon roof, aerodynamic mirror caps, and dual twin exhaust tailpipes leave an unmistakable impression from every angle.|images/5 series.png\nDriver-Focused M Cockpit|Frameless 12.3-inch digital instrument cluster and 14.9-inch central display with M Mode graphics, shift lights, drift analyzer, and M Multifunction seats in Merino leather.|images/bmw color 1.jpg",
    'offers' => "BMW 360° Financial Plan with attractive EMI starting at ₹ 1,99,999/month*\nComplimentary BMW Service Inclusive Plus maintenance package for 3 years / 40,000 km\nComplimentary BMW Wallbox charger with 8 years / 160,000 km battery warranty\nExclusive BMW M Track Experience invitation at Buddh International Circuit on handover",
    'configure_url' => 'configrator.php',
    'accel_200' => '10.9 s',
    'displacement' => '4,395 cc',
    'engine_power' => '585 hp (430 kW) @ 5,600–6,500 rpm',
    'engine_torque' => '750 Nm @ 1,800–5,400 rpm',
    'electric_power' => '197 hp (145 kW) @ 6,000 rpm',
    'electric_torque' => '280 Nm (450 Nm with pre-gearing)',
    'battery_capacity' => '18.6 kWh net (Lithium-ion)',
    'electric_range' => 'Up to 69 km (WLTP)',
    'charging_time' => 'Approx. 3h 15m @ 7.4 kW Wallbox',
    'drivetrain' => 'M xDrive with Active M Differential (4WD, 4WD Sport, 2WD)',
    'suspension' => 'Adaptive M suspension with electronically controlled dampers',
    'wheels_front' => '285/40 ZR20 on 20-inch M alloy wheels',
    'wheels_rear' => '295/35 ZR21 on 21-inch M alloy wheels',
    'dimensions' => '5,096 × 1,970 × 1,510 mm',
    'wheelbase' => '3,006 mm',
    'weight' => '2,435 kg (DIN)',
    'boot_capacity' => '466 litres',
    'fuel_tank' => '60 litres',
    'custom_colors' => "Isle of Man Green Metallic:#134a36\nMarina Bay Blue Metallic:#094381\nFrozen Deep Grey (Matte):#474b4e\nBrooklyn Grey Metallic:#8b9095\nToronto Red Metallic:#a81b23\nCarbon Black Metallic:#151922\nAlpine White Solid:#f0f3f6",
];

if ($slug !== '' && strlen($slug) <= 180 && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
    try {
        $conn = db();
        app_ensure_car_catalog_schema($conn);
        $statement = $conn->prepare("SELECT * FROM `cars` WHERE (`slug` = ? OR `slug` = ?) AND `status` = 'active' LIMIT 1");
        $altSlug = $slug === 'bmw-m5' ? 'bmw-m5-sedan' : $slug;
        $statement->bind_param('ss', $slug, $altSlug);
        $statement->execute();
        $car = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
    } catch (Throwable $error) {
        error_log('BMW car details could not be loaded: ' . $error->getMessage());
        $loadError = true;
    }
}

// If no slug requested, or if M5 requested and not yet seeded, default to BMW M5 Sedan
if (!$car && !$loadError && ($slug === '' || $slug === 'bmw-m5' || $slug === 'bmw-m5-sedan')) {
    $car = $defaultM5Data;
} elseif (!$car && !$loadError && $slug !== '') {
    http_response_code(404);
}

$detailCar = $car;
$isM5 = $detailCar && (
    str_contains(strtolower((string)$detailCar['name']), 'm5') ||
    str_contains(strtolower((string)$detailCar['slug']), 'm5') ||
    str_contains(strtolower((string)$detailCar['hero_subtitle']), 'm5')
);

$mediaUrl = static function (?string $path): string {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return filter_var($path, FILTER_VALIDATE_URL) ? $path : '';
    }
    $path = str_replace('\\', '/', $path);
    if (preg_match('#^(images|docs|downloads|brochures|videos)/#', $path)) {
        return '../' . $path;
    }
    if (preg_match('#^\.\./(images|docs|downloads|brochures|videos)/#', $path)) {
        return $path;
    }
    return '';
};

$lines = static function (?string $value): array {
    $value = trim((string)$value);
    if ($value === '') {
        return [];
    }
    $decoded = json_decode($value, true);
    $items = is_array($decoded) ? $decoded : preg_split('/\R/', $value);
    return array_values(array_filter(array_map(static fn($item): string => trim((string)$item), $items), static fn(string $item): bool => $item !== ''));
};

$gallery = $detailCar ? array_values(array_filter(array_map($mediaUrl, $lines((string)($detailCar['gallery'] ?? ''))))) : [];
$highlights = $detailCar ? $lines((string)($detailCar['highlights'] ?? '')) : [];
$offers = $detailCar ? $lines((string)($detailCar['offers'] ?? '')) : [];
$parsedColors = [];
if ($detailCar && !empty($detailCar['custom_colors'])) {
    foreach ($lines((string)$detailCar['custom_colors']) as $colorLine) {
        $colorParts = explode(':', $colorLine, 2);
        $cName = trim($colorParts[0] ?? '');
        $cHex = trim($colorParts[1] ?? '');
        if ($cName !== '') {
            $parsedColors[] = [
                'name' => $cName,
                'tint' => $cHex !== '' ? $cHex : '#134a36',
            ];
        }
    }
}
if (!$parsedColors) {
    $parsedColors = [
        ['name' => 'Isle of Man Green Metallic', 'tint' => '#134a36'],
        ['name' => 'Marina Bay Blue Metallic', 'tint' => '#094381'],
        ['name' => 'Frozen Deep Grey (Matte)', 'tint' => '#474b4e'],
        ['name' => 'Brooklyn Grey Metallic', 'tint' => '#8b9095'],
        ['name' => 'Toronto Red Metallic', 'tint' => '#a81b23'],
        ['name' => 'Carbon Black Metallic', 'tint' => '#151922'],
        ['name' => 'Alpine White Solid', 'tint' => '#f0f3f6'],
    ];
}
$heroImage = $detailCar ? $mediaUrl((string)($detailCar['image'] ?? '')) : '';
if ($heroImage === '' && $detailCar) {
    $heroImage = '../images/5 series.png';
}

$heroVideo = '';
if ($detailCar) {
    $videoPath = trim((string)($detailCar['hero_video'] ?? ''));
    if ($videoPath !== '') {
        $parsedVideoPath = parse_url($videoPath, PHP_URL_PATH);
        if (strtolower((string)parse_url($videoPath, PHP_URL_SCHEME)) === 'https'
            && filter_var($videoPath, FILTER_VALIDATE_URL) !== false
            && is_string($parsedVideoPath)
            && preg_match('/\.(mp4|webm)$/i', $parsedVideoPath)) {
            $heroVideo = $videoPath;
        } else {
            $normalizedVideoPath = str_replace('\\', '/', $videoPath);
            if (preg_match('#^(?:videos/)?[A-Za-z0-9._/-]+\.(mp4|webm)$#i', $normalizedVideoPath)
                && !str_contains($normalizedVideoPath, '..')) {
                $heroVideo = str_starts_with($normalizedVideoPath, 'videos/') ? '../' . $normalizedVideoPath : '../videos/' . $normalizedVideoPath;
            }
        }
    }
    if ($heroVideo === '' && $isM5) {
        $heroVideo = '../videos/video-1.mp4';
    }
}

$brochure = $detailCar ? $mediaUrl((string)($detailCar['brochure'] ?? '')) : '';
if ($brochure === '' && $detailCar) {
    $brochure = '../docs/Beamers Legacy.docx';
}

$editorialStatement = $detailCar ? trim((string)($detailCar['editorial_quote'] ?? '')) : '';
$storySections = [];
if ($detailCar) {
    foreach ($lines((string)($detailCar['story_sections'] ?? '')) as $sectionLine) {
        $parts = array_map('trim', explode('|', $sectionLine, 3));
        if (count($parts) >= 2 && $parts[0] !== '' && $parts[1] !== '') {
            $storySections[] = [
                'heading' => $parts[0],
                'description' => $parts[1],
                'image' => $mediaUrl($parts[2] ?? ''),
            ];
        }
    }
}

$heroStats = [];
if ($detailCar) {
    if (!empty($detailCar['power'])) {
        $heroStats['System Output'] = (string)$detailCar['power'];
    }
    if (!empty($detailCar['acceleration'])) {
        $heroStats['0–100 km/h'] = (string)$detailCar['acceleration'];
    }
    if (!empty($detailCar['torque'])) {
        $heroStats['Max Torque'] = (string)$detailCar['torque'];
    } elseif (!empty($detailCar['top_speed'])) {
        $heroStats['Top Speed'] = (string)$detailCar['top_speed'];
    }
    if (!empty($detailCar['drive_or_range'])) {
        $heroStats[(string)($detailCar['metric_label'] ?: 'Range / Drive')] = (string)$detailCar['drive_or_range'];
    }
    if ($isM5 && count($heroStats) < 5) {
        $heroStats['Top Speed'] = '305 km/h';
    }
}

$testDriveUrl = $detailCar ? 'testdrive.php?car_id=' . (int)($detailCar['id'] ?? 0) . '&inquiry=test_drive' : 'testdrive.php';
$offerUrl = $detailCar ? 'testdrive.php?car_id=' . (int)($detailCar['id'] ?? 0) . '&inquiry=offer' : 'testdrive.php';
$bookingUrl = $detailCar ? 'booking.php?car_id=' . (int)($detailCar['id'] ?? 0) : 'booking.php';
$configureUrl = $detailCar ? trim((string)($detailCar['configure_url'] ?? '')) : '';
if ($configureUrl === '' && $detailCar) {
    $configureUrl = 'configrator.php?car_id=' . (int)($detailCar['id'] ?? 0);
} elseif ($configureUrl === '') {
    $configureUrl = 'configrator.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $detailCar ? e((string)$detailCar['name']) . ' | Official Model Overview | Beamers Legacy' : ($loadError ? 'Temporarily Unavailable' : 'Car Not Found') . ' | Beamers Legacy' ?></title>
  <meta name="description" content="Explore the <?= $detailCar ? e((string)$detailCar['name']) : 'BMW' ?>: Discover innovative M HYBRID performance, cutting-edge technology, technical specifications, and book an exclusive test drive.">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Premium Stylesheets -->
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1">
  
  <style>
    :root {
      --bmw-m-light-blue: #0088cc;
      --bmw-m-dark-blue: #002b49;
      --bmw-m-red: #e21a22;
      --bmw-cyan: #00a2ff;
      --bmw-electric-blue: #1c69d4;
      --bmw-bg-dark: #07080a;
      --bmw-bg-card: rgba(18, 22, 28, 0.72);
      --bmw-border-light: rgba(255, 255, 255, 0.12);
      --bmw-border-hover: rgba(255, 255, 255, 0.35);
      --bmw-text-main: #f5f6f8;
      --bmw-text-sub: #9ea8b5;
    }

    body {
      background-color: var(--bmw-bg-dark);
      color: var(--bmw-text-main);
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      margin: 0;
      padding: 0;
      overflow-x: hidden;
    }

    /* M Tricolor Slash Motif */
    .bmw-m-badge {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      vertical-align: middle;
      margin-right: 8px;
    }
    .bmw-m-stripe {
      display: inline-block;
      width: 4px;
      height: 14px;
      transform: skewX(-20deg);
    }
    .bmw-m-stripe.cyan { background-color: var(--bmw-m-light-blue); }
    .bmw-m-stripe.dark-blue { background-color: var(--bmw-m-dark-blue); }
    .bmw-m-stripe.red { background-color: var(--bmw-m-red); }
    .bmw-m-letter {
      font-style: italic;
      font-weight: 900;
      font-size: 0.95rem;
      letter-spacing: -0.05em;
      color: #fff;
      margin-left: 1px;
    }

    /* Sticky BMW Model Sub-Bar */
    .bmw-model-subnav {
      position: sticky;
      top: var(--navbar-height-scrolled, 70px);
      z-index: 850;
      background: rgba(7, 9, 12, 0.94);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      border-bottom: 1px solid var(--bmw-border-light);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.45);
      transition: transform 0.3s ease;
    }
    .bmw-model-subnav-inner {
      width: min(1440px, calc(100% - 60px));
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 64px;
      gap: 20px;
    }
    .bmw-model-subnav-title {
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
    }
    .bmw-model-subnav-title strong {
      font-size: 0.95rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #fff;
    }
    .bmw-model-subnav-title span {
      font-size: 0.68rem;
      color: var(--bmw-text-sub);
      font-weight: 500;
      letter-spacing: 0.04em;
    }
    .bmw-model-subnav-links {
      display: flex;
      align-items: center;
      gap: clamp(14px, 2.5vw, 36px);
      overflow-x: auto;
      scrollbar-width: none;
      white-space: nowrap;
    }
    .bmw-model-subnav-links::-webkit-scrollbar { display: none; }
    .bmw-model-subnav-links a {
      color: var(--bmw-text-sub);
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding: 21px 2px 19px;
      border-bottom: 2px solid transparent;
      transition: color 0.2s, border-color 0.2s;
    }
    .bmw-model-subnav-links a:hover,
    .bmw-model-subnav-links a.active {
      color: #fff;
      border-bottom-color: var(--bmw-cyan);
    }
    .bmw-model-subnav-actions {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-shrink: 0;
    }
    .bmw-btn-subnav-primary {
      background: #fff;
      color: #000;
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding: 9px 18px;
      border: 1px solid #fff;
      transition: all 0.25s ease;
      white-space: nowrap;
      text-decoration: none;
    }
    .bmw-btn-subnav-primary:hover {
      background: var(--bmw-electric-blue);
      border-color: var(--bmw-electric-blue);
      color: #fff;
    }
    .bmw-btn-subnav-outline {
      background: transparent;
      color: #fff;
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      padding: 9px 16px;
      border: 1px solid rgba(255, 255, 255, 0.4);
      transition: all 0.25s ease;
      white-space: nowrap;
      text-decoration: none;
    }
    .bmw-btn-subnav-outline:hover {
      border-color: #fff;
      background: rgba(255, 255, 255, 0.1);
    }

    /* Hero Section */
    .bmw-hero {
      position: relative;
      min-height: calc(100svh - 70px);
      height: min(940px, 100svh);
      overflow: hidden;
      display: flex;
      align-items: flex-end;
      background: radial-gradient(ellipse at 50% 50%, #151d26 0%, #0a0d12 45%, #050608 100%);
      isolation: isolate;
    }
    .bmw-hero-media {
      position: absolute;
      inset: 0;
      z-index: 0;
      pointer-events: none;
    }
    .bmw-hero-media video,
    .bmw-hero-media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center 48%;
      filter: brightness(0.8) contrast(1.08);
    }
    .bmw-hero-overlay {
      position: absolute;
      inset: 0;
      z-index: 1;
      background: linear-gradient(180deg, rgba(6, 8, 11, 0.55) 0%, rgba(6, 8, 11, 0.15) 35%, rgba(6, 8, 11, 0.6) 70%, #07080a 100%);
      pointer-events: none;
    }
    .bmw-hero-glow-line {
      position: absolute;
      bottom: 120px;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent 0%, rgba(0, 162, 255, 0.5) 50%, transparent 100%);
      pointer-events: none;
      z-index: 2;
    }
    .bmw-video-control {
      position: absolute;
      top: 32px;
      right: max(32px, calc((100vw - 1440px) / 2));
      z-index: 10;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      border: 1px solid rgba(255, 255, 255, 0.4);
      background: rgba(0, 0, 0, 0.45);
      backdrop-filter: blur(8px);
      color: #fff;
      display: grid;
      place-items: center;
      cursor: pointer;
      transition: all 0.25s ease;
    }
    .bmw-video-control:hover {
      background: var(--bmw-electric-blue);
      border-color: var(--bmw-electric-blue);
      transform: scale(1.05);
    }

    .bmw-hero-container {
      position: relative;
      z-index: 5;
      width: min(1440px, calc(100% - 60px));
      margin: 0 auto;
      padding-bottom: clamp(140px, 18vh, 180px);
    }
    .bmw-hero-content {
      max-width: 860px;
    }
    .bmw-hero-eyebrow {
      display: inline-flex;
      align-items: center;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: #b5c3d2;
      margin-bottom: 18px;
    }
    .bmw-hero-title {
      font-size: clamp(3.2rem, 7.8vw, 7.5rem);
      line-height: 0.92;
      font-weight: 900;
      letter-spacing: -0.055em;
      text-transform: uppercase;
      margin: 0 0 16px;
      color: #ffffff;
      text-shadow: 0 12px 35px rgba(0, 0, 0, 0.65);
    }
    .bmw-hero-subtitle {
      font-size: clamp(0.95rem, 1.6vw, 1.25rem);
      font-weight: 400;
      color: rgba(240, 244, 248, 0.88);
      letter-spacing: 0.08em;
      text-transform: uppercase;
      margin: 0 0 20px;
      max-width: 700px;
    }
    .bmw-hero-price-wrap {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 14px;
      font-size: 0.82rem;
      color: var(--bmw-text-sub);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 28px;
    }
    .bmw-hero-price-wrap strong {
      color: #fff;
      font-size: 1.25rem;
      font-weight: 800;
      letter-spacing: 0;
    }
    .bmw-hero-emi-pill {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.16);
      padding: 4px 12px;
      font-size: 0.72rem;
      color: #e2e8f0;
      letter-spacing: 0.04em;
    }
    .bmw-hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
    }
    .bmw-hero-btn-primary {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      background: #ffffff;
      color: #07080a;
      padding: 14px 28px;
      font-size: 0.74rem;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      text-decoration: none;
      transition: all 0.25s ease;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
    }
    .bmw-hero-btn-primary:hover {
      background: var(--bmw-electric-blue);
      color: #fff;
      transform: translateY(-2px);
    }
    .bmw-hero-btn-outline {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: rgba(8, 12, 16, 0.45);
      color: #ffffff;
      padding: 14px 24px;
      font-size: 0.74rem;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      text-decoration: none;
      border: 1px solid rgba(255, 255, 255, 0.45);
      backdrop-filter: blur(8px);
      transition: all 0.25s ease;
    }
    .bmw-hero-btn-outline:hover {
      border-color: #fff;
      background: rgba(255, 255, 255, 0.12);
      transform: translateY(-2px);
    }

    /* Key Performance Matrix at Hero Bottom */
    .bmw-hero-stats-bar {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      z-index: 6;
      background: rgba(9, 12, 17, 0.88);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-top: 1px solid var(--bmw-border-light);
    }
    .bmw-hero-stats-inner {
      width: min(1440px, calc(100% - 60px));
      margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    }
    .bmw-hero-stat-col {
      padding: 18px 24px;
      border-right: 1px solid var(--bmw-border-light);
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .bmw-hero-stat-col:last-child { border-right: none; }
    .bmw-hero-stat-val {
      font-size: clamp(1.4rem, 2.5vw, 2.1rem);
      font-weight: 800;
      letter-spacing: -0.04em;
      color: #fff;
      line-height: 1.1;
    }
    .bmw-hero-stat-label {
      font-size: 0.64rem;
      font-weight: 700;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--bmw-text-sub);
      margin-top: 6px;
    }

    /* General Section Layout */
    .bmw-section {
      padding: clamp(70px, 9vw, 130px) 0;
      border-bottom: 1px solid var(--bmw-border-light);
      scroll-margin-top: calc(var(--navbar-height-scrolled, 70px) + 64px);
    }
    .bmw-container {
      width: min(1380px, calc(100% - 60px));
      margin: 0 auto;
    }
    .bmw-section-header {
      max-width: 880px;
      margin-bottom: clamp(36px, 5vw, 64px);
    }
    .bmw-eyebrow {
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.28em;
      text-transform: uppercase;
      color: var(--bmw-cyan);
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .bmw-eyebrow::before {
      content: "";
      display: inline-block;
      width: 20px;
      height: 2px;
      background: var(--bmw-cyan);
    }
    .bmw-section-title {
      font-size: clamp(2rem, 4.4vw, 3.8rem);
      font-weight: 800;
      letter-spacing: -0.04em;
      text-transform: uppercase;
      line-height: 1.05;
      margin: 0 0 18px;
      color: #fff;
    }
    .bmw-section-desc {
      font-size: clamp(0.96rem, 1.25vw, 1.12rem);
      line-height: 1.75;
      color: var(--bmw-text-sub);
      max-width: 78ch;
      margin: 0;
    }

    /* Overview Narrative & Quote */
    .bmw-overview-grid {
      display: grid;
      grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
      gap: clamp(30px, 6vw, 80px);
      align-items: center;
    }
    .bmw-quote-card {
      position: relative;
      background: linear-gradient(135deg, rgba(25, 34, 46, 0.6) 0%, rgba(9, 13, 18, 0.8) 100%);
      border: 1px solid var(--bmw-border-light);
      border-left: 4px solid var(--bmw-m-light-blue);
      padding: clamp(30px, 5vw, 50px);
      border-radius: 2px;
    }
    .bmw-quote-text {
      font-size: clamp(1.15rem, 2vw, 1.6rem);
      font-weight: 300;
      line-height: 1.5;
      color: #e2e8f0;
      margin: 0 0 20px;
    }
    .bmw-pills-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 24px;
    }
    .bmw-spec-pill {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.14);
      padding: 7px 15px;
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #cbd5e1;
    }

    /* Highlights Section (Feature Cards Grid) */
    .bmw-highlights-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 24px;
    }
    .bmw-highlight-card {
      position: relative;
      background: var(--bmw-bg-card);
      border: 1px solid var(--bmw-border-light);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    }
    .bmw-highlight-card:hover {
      transform: translateY(-6px);
      border-color: rgba(255, 255, 255, 0.3);
      box-shadow: 0 18px 40px rgba(0, 0, 0, 0.5);
    }
    .bmw-highlight-media {
      position: relative;
      width: 100%;
      height: 250px;
      background: #0e1218;
      overflow: hidden;
    }
    .bmw-highlight-media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.6s ease;
    }
    .bmw-highlight-card:hover .bmw-highlight-media img {
      transform: scale(1.05);
    }
    .bmw-highlight-badge {
      position: absolute;
      top: 16px;
      left: 16px;
      background: rgba(7, 9, 12, 0.85);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
      font-size: 0.65rem;
      font-weight: 800;
      letter-spacing: 0.12em;
      padding: 5px 11px;
      text-transform: uppercase;
    }
    .bmw-highlight-body {
      padding: 28px 24px 32px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    .bmw-highlight-body h3 {
      font-size: 1.25rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      text-transform: uppercase;
      margin: 0 0 12px;
      color: #fff;
      line-height: 1.25;
    }
    .bmw-highlight-body p {
      font-size: 0.88rem;
      line-height: 1.7;
      color: var(--bmw-text-sub);
      margin: 0;
      flex: 1;
    }

    /* Interactive Color Visualizer Stage */
    .bmw-visualizer-section {
      background: radial-gradient(ellipse at 50% 60%, #17212e 0%, #0c1117 50%, #060709 100%);
      border-top: 1px solid var(--bmw-border-light);
      border-bottom: 1px solid var(--bmw-border-light);
      position: relative;
    }
    .bmw-visualizer-stage {
      position: relative;
      min-height: 480px;
      height: clamp(380px, 50vw, 580px);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 20px 0 40px;
      overflow: hidden;
    }
    .bmw-visualizer-display {
      position: relative;
      width: 100%;
      height: 100%;
      max-width: 1080px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .bmw-visualizer-car-img {
      position: relative;
      z-index: 1;
      width: 90%;
      max-height: 90%;
      object-fit: contain;
      filter: drop-shadow(0 35px 50px rgba(0, 0, 0, 0.75));
      transition: filter 0.4s ease;
    }
    .bmw-visualizer-color-wash {
      position: absolute;
      inset: 8%;
      z-index: 2;
      mix-blend-mode: color;
      opacity: 0.48;
      pointer-events: none;
      background: var(--car-tint, transparent);
      transition: background-color 0.4s ease, opacity 0.4s ease;
      mask-image: radial-gradient(ellipse at center, black 60%, transparent 80%);
      -webkit-mask-image: radial-gradient(ellipse at center, black 60%, transparent 80%);
    }
    .bmw-visualizer-glow {
      position: absolute;
      bottom: 12%;
      width: 70%;
      height: 30px;
      background: radial-gradient(ellipse at center, rgba(0, 162, 255, 0.28) 0%, transparent 70%);
      filter: blur(15px);
      z-index: 0;
    }
    .bmw-visualizer-controls {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 24px;
      max-width: 780px;
      margin: 0 auto;
      text-align: center;
    }
    .bmw-color-info {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.85rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      font-weight: 700;
    }
    .bmw-color-swatches {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 16px;
    }
    .bmw-swatch-btn {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      border: 2px solid rgba(255, 255, 255, 0.25);
      cursor: pointer;
      position: relative;
      transition: all 0.25s ease;
      padding: 0;
      background: transparent;
      outline: none;
    }
    .bmw-swatch-btn::after {
      content: "";
      position: absolute;
      inset: 3px;
      border-radius: 50%;
      background: var(--swatch-color);
      box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.3), inset 0 -2px 4px rgba(0, 0, 0, 0.4);
    }
    .bmw-swatch-btn:hover {
      transform: scale(1.15);
      border-color: rgba(255, 255, 255, 0.7);
    }
    .bmw-swatch-btn.active {
      transform: scale(1.2);
      border-color: #fff;
      box-shadow: 0 0 18px rgba(0, 162, 255, 0.6);
    }

    /* Design Tabs (Exterior vs Interior) */
    .bmw-tabs-nav {
      display: flex;
      gap: 16px;
      border-bottom: 1px solid var(--bmw-border-light);
      margin-bottom: 40px;
    }
    .bmw-tab-btn {
      background: transparent;
      border: 0;
      border-bottom: 3px solid transparent;
      padding: 16px 24px;
      font-size: 0.82rem;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      color: var(--bmw-text-sub);
      cursor: pointer;
      transition: all 0.25s ease;
    }
    .bmw-tab-btn:hover {
      color: #fff;
    }
    .bmw-tab-btn.active {
      color: #fff;
      border-bottom-color: var(--bmw-cyan);
    }
    .bmw-tab-panel {
      display: none;
    }
    .bmw-tab-panel.active {
      display: block;
      animation: bmwFadeIn 0.4s ease;
    }
    @keyframes bmwFadeIn {
      from { opacity: 0; transform: translateY(12px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .bmw-design-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 30px;
    }
    .bmw-design-item {
      background: var(--bmw-bg-card);
      border: 1px solid var(--bmw-border-light);
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    .bmw-design-item-media {
      position: relative;
      height: clamp(240px, 28vw, 360px);
      background: #0e1218;
      overflow: hidden;
    }
    .bmw-design-item-media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.6s ease;
    }
    .bmw-design-item:hover .bmw-design-item-media img {
      transform: scale(1.04);
    }
    .bmw-design-item-body {
      padding: 28px;
    }
    .bmw-design-item-body h4 {
      font-size: 1.25rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.02em;
      margin: 0 0 10px;
      color: #fff;
    }
    .bmw-design-item-body p {
      font-size: 0.88rem;
      line-height: 1.7;
      color: var(--bmw-text-sub);
      margin: 0;
    }

    /* Driving Dynamics (4 Pillars Grid) */
    .bmw-dynamics-section {
      background: linear-gradient(180deg, #07090c 0%, #0e131a 50%, #07090c 100%);
      position: relative;
    }
    .bmw-dynamics-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 20px;
    }
    .bmw-dynamic-pillar {
      background: rgba(14, 18, 24, 0.7);
      border: 1px solid var(--bmw-border-light);
      padding: 32px 24px;
      position: relative;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
    }
    .bmw-dynamic-pillar::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--bmw-m-light-blue), var(--bmw-m-red));
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    .bmw-dynamic-pillar:hover {
      transform: translateY(-6px);
      border-color: rgba(255, 255, 255, 0.28);
      background: rgba(22, 28, 38, 0.8);
    }
    .bmw-dynamic-pillar:hover::before {
      opacity: 1;
    }
    .bmw-pillar-number {
      font-size: 0.75rem;
      font-weight: 800;
      letter-spacing: 0.16em;
      color: var(--bmw-cyan);
      margin-bottom: 18px;
    }
    .bmw-dynamic-pillar h3 {
      font-size: 1.18rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: -0.02em;
      margin: 0 0 14px;
      color: #fff;
      line-height: 1.3;
    }
    .bmw-dynamic-pillar p {
      font-size: 0.85rem;
      line-height: 1.7;
      color: var(--bmw-text-sub);
      margin: 0;
    }

    /* Technical Specifications Tabbed Table */
    .bmw-specs-card {
      background: var(--bmw-bg-card);
      border: 1px solid var(--bmw-border-light);
      padding: clamp(24px, 4vw, 44px);
    }
    .bmw-spec-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    .bmw-spec-table tr {
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      transition: background-color 0.2s;
    }
    .bmw-spec-table tr:hover {
      background-color: rgba(255, 255, 255, 0.03);
    }
    .bmw-spec-table th,
    .bmw-spec-table td {
      padding: 16px 12px;
      text-align: left;
    }
    .bmw-spec-table th {
      color: var(--bmw-text-sub);
      font-size: 0.78rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      width: 45%;
    }
    .bmw-spec-table td {
      color: #fff;
      font-size: 0.85rem;
      font-weight: 700;
      letter-spacing: 0.02em;
      text-align: right;
    }

    /* Financial & Ownership Privileges */
    .bmw-privileges-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 20px;
      margin-top: 36px;
    }
    .bmw-privilege-item {
      border: 1px solid var(--bmw-border-light);
      background: rgba(255, 255, 255, 0.03);
      padding: 26px 22px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .bmw-privilege-icon {
      font-size: 1.6rem;
      color: var(--bmw-cyan);
    }
    .bmw-privilege-item strong {
      font-size: 0.95rem;
      font-weight: 800;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .bmw-privilege-item span {
      font-size: 0.8rem;
      line-height: 1.6;
      color: var(--bmw-text-sub);
    }

    /* Test Drive & Enquiry Booking Form */
    .bmw-inquiry-layout {
      display: grid;
      grid-template-columns: minmax(0, 0.85fr) minmax(0, 1.15fr);
      gap: clamp(30px, 6vw, 80px);
      align-items: center;
    }
    .bmw-inquiry-visual {
      position: relative;
      background: radial-gradient(ellipse at center, #182638 0%, #080c12 70%);
      border: 1px solid var(--bmw-border-light);
      padding: 40px 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 480px;
    }
    .bmw-inquiry-visual img {
      max-width: 95%;
      max-height: 400px;
      object-fit: contain;
      filter: drop-shadow(0 25px 40px rgba(0, 0, 0, 0.7));
    }
    .bmw-form-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 20px;
    }
    .bmw-form-field {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .bmw-form-field.full-width {
      grid-column: 1 / -1;
    }
    .bmw-form-field label {
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--bmw-text-sub);
    }
    .bmw-form-input,
    .bmw-form-select {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 0;
      color: #fff;
      font-size: 0.85rem;
      padding: 13px 16px;
      outline: none;
      transition: border-color 0.25s ease, background 0.25s ease;
      font-family: inherit;
    }
    .bmw-form-input:focus,
    .bmw-form-select:focus {
      border-color: var(--bmw-cyan);
      background: rgba(255, 255, 255, 0.08);
    }
    .bmw-form-select option {
      background: #0f1318;
      color: #fff;
    }
    .bmw-consent-box {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      font-size: 0.75rem;
      line-height: 1.5;
      color: var(--bmw-text-sub);
      cursor: pointer;
    }
    .bmw-consent-box input {
      margin-top: 3px;
      accent-color: var(--bmw-electric-blue);
      width: 16px;
      height: 16px;
    }
    .bmw-btn-submit {
      grid-column: 1 / -1;
      background: #fff;
      color: #000;
      border: 0;
      padding: 16px 28px;
      font-size: 0.75rem;
      font-weight: 800;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      cursor: pointer;
      transition: all 0.25s ease;
      font-family: inherit;
    }
    .bmw-btn-submit:hover {
      background: var(--bmw-electric-blue);
      color: #fff;
    }

    /* Bottom Final Call to Action Banner */
    .bmw-cta-banner {
      background: linear-gradient(135deg, #121822 0%, #080a0e 100%);
      border: 1px solid var(--bmw-border-light);
      padding: clamp(36px, 6vw, 64px);
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 30px;
    }
    .bmw-cta-copy h2 {
      font-size: clamp(1.8rem, 3.5vw, 2.8rem);
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: -0.04em;
      margin: 0 0 10px;
      color: #fff;
    }
    .bmw-cta-copy p {
      font-size: 0.95rem;
      color: var(--bmw-text-sub);
      margin: 0;
    }
    .bmw-cta-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
    }

    /* Mobile Floating Action Bar */
    .bmw-mobile-sticky-bar {
      display: none;
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      z-index: 890;
      background: rgba(7, 9, 12, 0.96);
      backdrop-filter: blur(16px);
      border-top: 1px solid var(--bmw-border-light);
      padding: 12px 18px;
      justify-content: space-between;
      gap: 12px;
      box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.6);
    }
    .bmw-mobile-sticky-bar a {
      flex: 1;
      text-align: center;
      padding: 12px 10px;
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      text-decoration: none;
    }
    .bmw-mobile-btn-primary {
      background: #fff;
      color: #000;
    }
    .bmw-mobile-btn-outline {
      border: 1px solid rgba(255, 255, 255, 0.4);
      color: #fff;
    }

    /* Responsive Adjustments */
    @media (max-width: 1080px) {
      .bmw-highlights-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .bmw-dynamics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .bmw-privileges-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .bmw-overview-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 800px) {
      .bmw-model-subnav-links { display: none; }
      .bmw-model-subnav-inner { justify-content: space-between; }
      .bmw-design-grid { grid-template-columns: 1fr; }
      .bmw-inquiry-layout { grid-template-columns: 1fr; }
      .bmw-mobile-sticky-bar { display: flex; }
      .bmw-hero { min-height: 720px; }
      .bmw-hero-container { padding-bottom: 160px; }
    }
    @media (max-width: 600px) {
      .bmw-highlights-grid { grid-template-columns: 1fr; }
      .bmw-dynamics-grid { grid-template-columns: 1fr; }
      .bmw-privileges-grid { grid-template-columns: 1fr; }
      .bmw-form-grid { grid-template-columns: 1fr; }
      .bmw-hero-stats-inner { grid-template-columns: repeat(2, 1fr); }
      .bmw-hero-stat-col:last-child { grid-column: span 2; }
      .bmw-hero-actions { flex-direction: column; }
      .bmw-hero-btn-primary, .bmw-hero-btn-outline { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>

<!-- GLOBAL DEALERSHIP NAVBAR -->
<?php require_once __DIR__ . '/navbar.php'; ?>

<?php if ($loadError): ?>
  <main class="bmw-section" style="min-height: 60vh; display: grid; place-items: center; text-align: center;">
    <div class="bmw-container">
      <div class="bmw-eyebrow" style="justify-content: center;">Beamers Legacy Showroom</div>
      <h1 class="bmw-section-title">Temporarily Unavailable</h1>
      <p class="bmw-section-desc" style="margin: 0 auto 30px;">We could not retrieve this vehicle at the moment. Please try again or browse our latest models.</p>
      <a href="models.php" class="bmw-hero-btn-primary" style="display: inline-block;">Return to Showroom</a>
    </div>
  </main>
<?php elseif (!$detailCar): ?>
  <main class="bmw-section" style="min-height: 60vh; display: grid; place-items: center; text-align: center;">
    <div class="bmw-container">
      <div class="bmw-eyebrow" style="justify-content: center;">Beamers Legacy Showroom</div>
      <h1 class="bmw-section-title">Model Not Found</h1>
      <p class="bmw-section-desc" style="margin: 0 auto 30px;">The requested vehicle is currently not in our catalog. Explore our flagship models in the showroom.</p>
      <a href="models.php" class="bmw-hero-btn-primary" style="display: inline-block;">Browse All Models</a>
    </div>
  </main>
<?php else: ?>

  <!-- STICKY MODEL SUB-NAVIGATION -->
  <nav class="bmw-model-subnav" id="model-subnav" aria-label="BMW Model Sub-navigation">
    <div class="bmw-model-subnav-inner">
      <div class="bmw-model-subnav-title">
        <strong>
          <?php if ($isM5): ?>
            <span class="bmw-m-badge"><span class="bmw-m-stripe cyan"></span><span class="bmw-m-stripe dark-blue"></span><span class="bmw-m-stripe red"></span><span class="bmw-m-letter">M</span></span>
          <?php endif; ?>
          <?= e((string)$detailCar['name']) ?>
        </strong>
        <span><?= e((string)($detailCar['fuel'] ?? 'High-Performance')) ?> | Ex-showroom from <?= e((string)($detailCar['price'] ?: 'Price on Request')) ?></span>
      </div>

      <div class="bmw-model-subnav-links">
        <a href="#top" class="active">Overview</a>
        <a href="#highlights">Highlights</a>
        <a href="#visualizer">Visualizer</a>
        <a href="#design">Design</a>
        <a href="#dynamics">Dynamics</a>
        <a href="#specifications">Technical Data</a>
        <a href="#privileges">Privileges</a>
      </div>

      <div class="bmw-model-subnav-actions">
        <a href="<?= e($configureUrl) ?>" class="bmw-btn-subnav-primary">Configure Now</a>
        <a href="#test-drive" class="bmw-btn-subnav-outline">Book Test Drive</a>
      </div>
    </div>
  </nav>

  <!-- HERO SECTION -->
  <header class="bmw-hero" id="top">
    <?php if ($heroVideo !== '' || $heroImage !== ''): ?>
      <div class="bmw-hero-media" aria-hidden="true">
        <?php if ($heroVideo !== ''): ?>
          <video autoplay muted loop playsinline preload="metadata"<?= $heroImage !== '' ? ' poster="' . e($heroImage) . '"' : '' ?>>
            <source src="<?= e($heroVideo) ?>" type="<?= str_ends_with(strtolower((string)parse_url($heroVideo, PHP_URL_PATH)), '.webm') ? 'video/webm' : 'video/mp4' ?>">
          </video>
        <?php elseif ($heroImage !== ''): ?>
          <img src="<?= e($heroImage) ?>" alt="<?= e((string)$detailCar['name']) ?> Hero View" fetchpriority="high">
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="bmw-hero-overlay"></div>
    <div class="bmw-hero-glow-line"></div>

    <?php if ($heroVideo !== ''): ?>
      <button class="bmw-video-control" type="button" aria-label="Pause hero background video" data-hero-video-toggle>
        <i class="fa-solid fa-pause" aria-hidden="true"></i>
      </button>
    <?php endif; ?>

    <div class="bmw-hero-container">
      <div class="bmw-hero-content">
        <p class="bmw-hero-eyebrow">
          <?php if ($isM5): ?>
            <span class="bmw-m-badge"><span class="bmw-m-stripe cyan"></span><span class="bmw-m-stripe dark-blue"></span><span class="bmw-m-stripe red"></span><span class="bmw-m-letter">M</span></span>
            THE FIRST-EVER ELECTRIFIED BMW M5 SEDAN
          <?php else: ?>
            <?= e((string)$detailCar['series']) ?> &bull; <?= e((string)$detailCar['vehicle_type']) ?>
          <?php endif; ?>
        </p>

        <h1 class="bmw-hero-title">
          <?= $isM5 ? 'THE M5 SEDAN' : e((string)$detailCar['name']) ?>
        </h1>

        <p class="bmw-hero-subtitle">
          <?= !empty($detailCar['hero_subtitle']) ? e((string)$detailCar['hero_subtitle']) : ($isM5 ? 'SUPERCAR PERFORMANCE MEETS ELECTRIFIED LUXURY.' : e((string)$detailCar['short_description'])) ?>
        </p>

        <div class="bmw-hero-price-wrap">
          <span>Starting from</span>
          <strong><?= e((string)($detailCar['price'] ?: 'Price on Request')) ?></strong>
          <?php if (!empty($detailCar['emi'])): ?>
            <span class="bmw-hero-emi-pill">EMI starting at <?= e((string)$detailCar['emi']) ?></span>
          <?php endif; ?>
        </div>

        <div class="bmw-hero-actions">
          <a href="<?= e($configureUrl) ?>" class="bmw-hero-btn-primary">
            <span>Configure &amp; Price</span>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </a>
          <a href="#test-drive" class="bmw-hero-btn-outline">
            <i class="fa-solid fa-steering-wheel" aria-hidden="true"></i>
            <span>Book a Test Drive</span>
          </a>
          <a href="<?= e($bookingUrl) ?>" class="bmw-hero-btn-outline">
            <span>Enquire to Buy</span>
          </a>
        </div>
      </div>
    </div>

    <!-- KEY PERFORMANCE STATS BAR -->
    <?php if ($heroStats): ?>
      <div class="bmw-hero-stats-bar">
        <div class="bmw-hero-stats-inner">
          <?php foreach ($heroStats as $label => $value): ?>
            <div class="bmw-hero-stat-col">
              <span class="bmw-hero-stat-val"><?= e($value) ?></span>
              <span class="bmw-hero-stat-label"><?= e($label) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </header>

  <main>
    <!-- OVERVIEW SECTION -->
    <section class="bmw-section" id="overview">
      <div class="bmw-container">
        <div class="bmw-overview-grid">
          <div>
            <div class="bmw-eyebrow">
              <?php if ($isM5): ?><span class="bmw-m-badge"><span class="bmw-m-stripe cyan"></span><span class="bmw-m-stripe dark-blue"></span><span class="bmw-m-stripe red"></span><span class="bmw-m-letter">M</span></span><?php endif; ?>
              A New Chapter of Motorsport Excellence
            </div>
            <h2 class="bmw-section-title">
              <?= $isM5 ? 'The Seventh Generation: Electrified M Power' : e((string)$detailCar['name']) ?>
            </h2>
            <p class="bmw-section-desc">
              <?= !empty($detailCar['short_description']) ? e((string)$detailCar['short_description']) : 'Experience track-bred engineering, precision dynamics, and bespoke Bavarian craftsmanship designed to push the boundaries of pure automotive passion.' ?>
            </p>
            <div class="bmw-pills-row">
              <span class="bmw-spec-pill"><i class="fa-solid fa-bolt" style="color:var(--bmw-cyan); margin-right:6px;"></i> <?= e((string)($detailCar['fuel'] ?? 'M HYBRID')) ?></span>
              <span class="bmw-spec-pill"><i class="fa-solid fa-gauge-high" style="color:var(--bmw-m-red); margin-right:6px;"></i> <?= e((string)($detailCar['power'] ?? '727 hp')) ?></span>
              <span class="bmw-spec-pill"><i class="fa-solid fa-stopwatch" style="margin-right:6px;"></i> 0-100 in <?= e((string)($detailCar['acceleration'] ?? '3.5s')) ?></span>
              <span class="bmw-spec-pill"><i class="fa-solid fa-arrows-split-up-and-left" style="margin-right:6px;"></i> <?= e((string)($detailCar['transmission'] ?? '8-Speed Steptronic')) ?></span>
            </div>
          </div>

          <div class="bmw-quote-card">
            <p class="bmw-quote-text">
              <?= $editorialStatement !== '' ? e($editorialStatement) : '“Supercar performance meets executive luxury: the new BMW M5 Sedan redefines what an M automobile can achieve on the track and open highway.”' ?>
            </p>
            <div style="font-size:0.72rem; font-weight:800; letter-spacing:0.18em; text-transform:uppercase; color:#fff;">
              BMW M Motorsport Engineering Division
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- HIGHLIGHTS SECTION -->
    <section class="bmw-section" id="highlights">
      <div class="bmw-container">
        <div class="bmw-section-header">
          <div class="bmw-eyebrow">Highlights of the <?= e((string)$detailCar['name']) ?></div>
          <h2 class="bmw-section-title">Fascinating in Every Detail</h2>
          <p class="bmw-section-desc">
            Discover the key innovations that set this machine apart — from the ground-breaking M HYBRID drivetrain to the driver-centric cockpit.
          </p>
        </div>

        <div class="bmw-highlights-grid">
          <!-- Card 1: Drivetrain -->
          <article class="bmw-highlight-card">
            <div class="bmw-highlight-media">
              <span class="bmw-highlight-badge">Powertrain</span>
              <img src="../images/bg m4 image.jpg" alt="M HYBRID System" loading="lazy">
            </div>
            <div class="bmw-highlight-body">
              <h3>M HYBRID High-Performance Drivetrain</h3>
              <p>A high-revving 4.4-litre M TwinPower Turbo V8 combustion engine teams up with an electric motor integrated directly into the 8-speed transmission. Total output: 727 hp and 1,000 Nm of torque with lightning-quick throttle response.</p>
            </div>
          </article>

          <!-- Card 2: Drivetrain Dynamics -->
          <article class="bmw-highlight-card">
            <div class="bmw-highlight-media">
              <span class="bmw-highlight-badge">Traction &amp; Agility</span>
              <img src="../images/m4 cs.png" alt="M xDrive All-Wheel Drive" loading="lazy">
            </div>
            <div class="bmw-highlight-body">
              <h3>M xDrive with Active M Differential</h3>
              <p>Rear-biased all-wheel-drive dynamics. Effortlessly toggle between 4WD for all-weather grip, 4WD Sport for sharper corner carving, or 2WD for pure rear-wheel-drive racetrack drift character.</p>
            </div>
          </article>

          <!-- Card 3: Grille / Front Fascia -->
          <article class="bmw-highlight-card">
            <div class="bmw-highlight-media">
              <span class="bmw-highlight-badge">Iconic Styling</span>
              <img src="../images/5 series.png" alt="Iconic Glow Kidney Grille" loading="lazy">
            </div>
            <div class="bmw-highlight-body">
              <h3>Iconic Glow Illuminated Kidney Grille</h3>
              <p>Contour illumination outlines the distinct M kidney grille. Flanked by sharp adaptive LED headlights and wide aerodynamic air curtains for an imposing road presence day and night.</p>
            </div>
          </article>

          <!-- Card 4: Cockpit & Display -->
          <article class="bmw-highlight-card">
            <div class="bmw-highlight-media">
              <span class="bmw-highlight-badge">Cockpit &amp; Tech</span>
              <img src="../images/bmw color 1.jpg" alt="BMW Curved Display with M OS 8.5" loading="lazy">
            </div>
            <div class="bmw-highlight-body">
              <h3>BMW Curved Display with M-Specific OS 8.5</h3>
              <p>Frameless, driver-focused glass screen uniting a 12.3-inch instrument cluster and 14.9-inch central display. Features M Mode graphics, G-meter, shift indicator, and quick-access M Setup telemetry.</p>
            </div>
          </article>

          <!-- Card 5: Seating & Luxury -->
          <article class="bmw-highlight-card">
            <div class="bmw-highlight-media">
              <span class="bmw-highlight-badge">Interior Craft</span>
              <img src="../images/bmw 3.webp" alt="M Multifunction Sport Seats" loading="lazy">
            </div>
            <div class="bmw-highlight-body">
              <h3>M Multifunction Seats in Merino Leather</h3>
              <p>Sculptured racing ergonomics with illuminated M5 emblems, active ventilation, pneumatic lumbar support, and contrasting M-colour stitching for peerless comfort on track or cross-country.</p>
            </div>
          </article>

          <!-- Card 6: Carbon Aerodynamics -->
          <article class="bmw-highlight-card">
            <div class="bmw-highlight-media">
              <span class="bmw-highlight-badge">Aerodynamics</span>
              <img src="../images/M8 compi.png" alt="M Carbon Roof & Aerodynamics" loading="lazy">
            </div>
            <div class="bmw-highlight-body">
              <h3>M Carbon Roof &amp; Lightweight Aero</h3>
              <p>Carbon-fibre reinforced plastic (CFRP) roof lowers the center of gravity. Complemented by flared wheel arches, M side mirrors, rear diffuser, and dual quad 100mm exhaust tailpipes.</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- INTERACTIVE COLOR & EXTERIOR VISUALIZER -->
    <section class="bmw-section bmw-visualizer-section" id="visualizer">
      <div class="bmw-container">
        <div class="bmw-section-header" style="text-align: center; margin: 0 auto clamp(30px, 4vw, 50px);">
          <div class="bmw-eyebrow" style="justify-content: center;">Exterior Personalisation</div>
          <h2 class="bmw-section-title">Discover Your Look</h2>
          <p class="bmw-section-desc" style="margin: 0 auto;">
            Select from iconic BMW M metallic and matte finishes to preview your vehicle’s aesthetic.
          </p>
        </div>

        <div class="bmw-visualizer-stage">
          <div class="bmw-visualizer-glow"></div>
          <div class="bmw-visualizer-display">
            <img class="bmw-visualizer-car-img" id="visualizer-car" src="<?= e($heroImage) ?>" alt="<?= e((string)$detailCar['name']) ?> Studio View">
            <div class="bmw-visualizer-color-wash" id="visualizer-wash" style="--car-tint: #134a36;"></div>
          </div>
        </div>

        <div class="bmw-visualizer-controls">
          <div class="bmw-color-info">
            <span style="color:var(--bmw-text-sub);">Selected Finish:</span>
            <strong id="visualizer-color-name" style="color:#fff;"><?= e($parsedColors[0]['name'] ?? 'BMW Metallic') ?></strong>
          </div>

          <div class="bmw-color-swatches" role="radiogroup" aria-label="BMW Exterior Paint Finishes">
            <?php foreach ($parsedColors as $i => $swatch): ?>
              <button class="bmw-swatch-btn<?= $i === 0 ? ' active' : '' ?>" type="button" role="radio" aria-checked="<?= $i === 0 ? 'true' : 'false' ?>" data-color-name="<?= e($swatch['name']) ?>" data-tint="<?= e($swatch['tint']) ?>" style="--swatch-color: <?= e($swatch['tint']) ?>;" title="<?= e($swatch['name']) ?>"></button>
            <?php endforeach; ?>
          </div>

          <div style="margin-top: 10px;">
            <a href="<?= e($configureUrl) ?>" class="bmw-btn-subnav-primary" style="padding: 12px 28px;">
              Open Full 3D Configurator <i class="fa-solid fa-sliders" style="margin-left: 8px;"></i>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- DESIGN HIGHLIGHTS (EXTERIOR & INTERIOR TABS) -->
    <section class="bmw-section" id="design">
      <div class="bmw-container">
        <div class="bmw-section-header">
          <div class="bmw-eyebrow">Aesthetic Masterpiece</div>
          <h2 class="bmw-section-title">Design Highlights</h2>
          <p class="bmw-section-desc">
            Explore the harmonious dialogue between athletic exterior proportions and avant-garde cockpit luxury.
          </p>
        </div>

        <!-- TABS NAV -->
        <div class="bmw-tabs-nav" role="tablist">
          <button class="bmw-tab-btn active" type="button" role="tab" aria-selected="true" aria-controls="tab-exterior" id="tab-btn-exterior">Exterior Highlights</button>
          <button class="bmw-tab-btn" type="button" role="tab" aria-selected="false" aria-controls="tab-interior" id="tab-btn-interior">Interior Highlights</button>
        </div>

        <!-- EXTERIOR TAB PANEL -->
        <div class="bmw-tab-panel active" id="tab-exterior" role="tabpanel" aria-labelledby="tab-btn-exterior">
          <div class="bmw-design-grid">
            <article class="bmw-design-item">
              <div class="bmw-design-item-media">
                <img src="../images/bmw 4.jpg" alt="Iconic Glow Grille & Sculpted Air Inlets" loading="lazy">
              </div>
              <div class="bmw-design-item-body">
                <h4>01. Iconic Glow Illuminated Kidney Grille</h4>
                <p>The illuminated contour of the M kidney grille creates an unmistakable visual signature. Massive central cooling air intakes feed oxygen to the high-revving V8 power plant.</p>
              </div>
            </article>

            <article class="bmw-design-item">
              <div class="bmw-design-item-media">
                <img src="../images/5 series.png" alt="Flared Wheel Arches" loading="lazy">
              </div>
              <div class="bmw-design-item-body">
                <h4>02. Muscular Flared Fenders (+75mm)</h4>
                <p>Flared front and rear wheel arches dramatically widen the vehicle's track, establishing a low-slung, planted stance ready to conquer apexes and straightaways.</p>
              </div>
            </article>

            <article class="bmw-design-item">
              <div class="bmw-design-item-media">
                <img src="../images/bg m4 image.jpg" alt="M Carbon Roof" loading="lazy">
              </div>
              <div class="bmw-design-item-body">
                <h4>03. Weight-Saving M Carbon Roof</h4>
                <p>Constructed from lightweight carbon-fibre reinforced plastic (CFRP), the roof lowers the vehicle's center of gravity for heightened lateral responsiveness and stability.</p>
              </div>
            </article>

            <article class="bmw-design-item">
              <div class="bmw-design-item-media">
                <img src="../images/M8 compi.png" alt="Quad Exhaust Tailpipes and Diffuser" loading="lazy">
              </div>
              <div class="bmw-design-item-body">
                <h4>04. Quad 100mm Tailpipes &amp; Diffuser</h4>
                <p>Twin dual-branch exhaust tailpipes in black chrome protrude boldly from a functional aerodynamic rear diffuser, generating the signature M acoustic roar.</p>
              </div>
            </article>
          </div>
        </div>

        <!-- INTERIOR TAB PANEL -->
        <div class="bmw-tab-panel" id="tab-interior" role="tabpanel" aria-labelledby="tab-btn-interior">
          <div class="bmw-design-grid">
            <article class="bmw-design-item">
              <div class="bmw-design-item-media">
                <img src="../images/bmw color 1.jpg" alt="BMW Curved Display with M OS 8.5" loading="lazy">
              </div>
              <div class="bmw-design-item-body">
                <h4>01. BMW Curved Display &amp; M Head-Up Display</h4>
                <p>A frameless 12.3-inch information display and 14.9-inch control display integrate with BMW OS 8.5, displaying track-inspired graphics, shift lights, and drift analysis telemetry.</p>
              </div>
            </article>

            <article class="bmw-design-item">
              <div class="bmw-design-item-media">
                <img src="../images/bmw 3.webp" alt="Flat-bottom M Leather Steering Wheel" loading="lazy">
              </div>
              <div class="bmw-design-item-body">
                <h4>02. Flat-Bottom M Steering Wheel with M1/M2 Buttons</h4>
                <p>Featuring a 12 o'clock center marking, carbon paddle shifters, and tactile red M buttons allowing instantaneous activation of customized dynamic vehicle setups.</p>
              </div>
            </article>

            <article class="bmw-design-item">
              <div class="bmw-design-item-media">
                <img src="../images/bmw color 2.webp" alt="M Interaction Bar" loading="lazy">
              </div>
              <div class="bmw-design-item-body">
                <h4>03. Crystalline M Interaction Bar</h4>
                <p>Dynamic ambient lighting with crystalline faceted glass stretches across the instrument panel, illuminating in authentic BMW M motorsport colours on vehicle entry.</p>
              </div>
            </article>

            <article class="bmw-design-item">
              <div class="bmw-design-item-media">
                <img src="../images/bmw 1.webp" alt="Bowers & Wilkins Diamond Surround Sound" loading="lazy">
              </div>
              <div class="bmw-design-item-body">
                <h4>04. Bowers &amp; Wilkins Diamond Surround Sound</h4>
                <p>18 high-performance speakers with 655 watts of acoustic output deliver an immersive studio-grade soundscape inside the acoustic-glass insulated cabin.</p>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>

    <!-- DRIVING DYNAMICS (4 MOTORSPORT PILLARS) -->
    <section class="bmw-section bmw-dynamics-section" id="dynamics">
      <div class="bmw-container">
        <div class="bmw-section-header">
          <div class="bmw-eyebrow">
            <span class="bmw-m-badge"><span class="bmw-m-stripe cyan"></span><span class="bmw-m-stripe dark-blue"></span><span class="bmw-m-stripe red"></span><span class="bmw-m-letter">M</span></span>
            Motorsport Architecture
          </div>
          <h2 class="bmw-section-title">Engineered for the Limits</h2>
          <p class="bmw-section-desc">
            Tested on the Nürburgring Nordschleife. Every chassis component and powertrain algorithm is calibrated to deliver instant feedback, razor-sharp steering precision, and unshakable high-speed stability.
          </p>
        </div>

        <div class="bmw-dynamics-grid">
          <div class="bmw-dynamic-pillar">
            <span class="bmw-pillar-number">PILLAR 01</span>
            <h3>M HYBRID Power Unit</h3>
            <p>An innovative marriage of high-revving V8 internal combustion and a permanently excited synchronous electric motor. The electric motor delivers instantaneous torque right off idle, while the V8 unleashes breathtaking top-end power up to 7,200 rpm.</p>
          </div>

          <div class="bmw-dynamic-pillar">
            <span class="bmw-pillar-number">PILLAR 02</span>
            <h3>Adaptive M Suspension</h3>
            <p>Electronically controlled dampers continuously adapt to road surface conditions and driving dynamics in milliseconds. Experience supreme ride refinement during highway cruises and unyielding chassis stiffness on the track.</p>
          </div>

          <div class="bmw-dynamic-pillar">
            <span class="bmw-pillar-number">PILLAR 03</span>
            <h3>M Setup &amp; M Mode</h3>
            <p>Direct access via the center console allows bespoke tuning of engine response, suspension firmness, steering resistance, braking feel, and M xDrive distribution. Save your favorite setups to the red M1 and M2 steering buttons.</p>
          </div>

          <div class="bmw-dynamic-pillar">
            <span class="bmw-pillar-number">PILLAR 04</span>
            <h3>M Carbon Ceramic Brakes</h3>
            <p>Lightweight 6-piston fixed front calipers finished in striking gold. Delivers immense thermal endurance, unyielding fade resistance from speeds over 300 km/h, and significant reduction in unsprung rotational mass.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- TECHNICAL SPECIFICATIONS TABBED ACCORDION -->
    <section class="bmw-section" id="specifications">
      <div class="bmw-container">
        <div class="bmw-section-header">
          <div class="bmw-eyebrow">Technical Blueprint</div>
          <h2 class="bmw-section-title">Technical Specifications</h2>
          <p class="bmw-section-desc">
            Explore complete powertrain, battery, dimensional, and performance data for the <?= e((string)$detailCar['name']) ?>.
          </p>
        </div>

        <!-- TECH SPECS TABS -->
        <div class="bmw-tabs-nav" role="tablist">
          <button class="bmw-tab-btn active" type="button" role="tab" aria-selected="true" aria-controls="spec-engine" id="spec-btn-engine">Engine &amp; Performance</button>
          <button class="bmw-tab-btn" type="button" role="tab" aria-selected="false" aria-controls="spec-electric" id="spec-btn-electric">Electric Drive &amp; Battery</button>
          <button class="bmw-tab-btn" type="button" role="tab" aria-selected="false" aria-controls="spec-drivetrain" id="spec-btn-drivetrain">Transmission &amp; Drivetrain</button>
          <button class="bmw-tab-btn" type="button" role="tab" aria-selected="false" aria-controls="spec-dimensions" id="spec-btn-dimensions">Dimensions &amp; Weight</button>
        </div>

        <div class="bmw-specs-card">
          <!-- ENGINE & PERFORMANCE PANEL -->
          <div class="bmw-tab-panel active" id="spec-engine" role="tabpanel" aria-labelledby="spec-btn-engine">
            <table class="bmw-spec-table">
              <tbody>
                <tr><th>Engine Type</th><td><?= e((string)($detailCar['engine'] ?: '4.4L M TwinPower Turbo V8 (S68) + Electric Motor')) ?></td></tr>
                <tr><th>Cylinders / Valves</th><td>8 / 4 (V90 layout)</td></tr>
                <tr><th>Displacement</th><td><?= e((string)(!empty($detailCar['displacement']) ? $detailCar['displacement'] : '4,395 cc')) ?></td></tr>
                <tr><th>Total System Output</th><td><strong><?= e((string)($detailCar['power'] ?: '535 kW (727 hp)')) ?></strong></td></tr>
                <tr><th>Total Peak Torque</th><td><strong><?= e((string)($detailCar['torque'] ?: '1,000 Nm @ 1,800–5,400 rpm')) ?></strong></td></tr>
                <tr><th>Combustion Engine Power</th><td><?= e((string)(!empty($detailCar['engine_power']) ? $detailCar['engine_power'] : '430 kW (585 hp) @ 5,600–6,500 rpm')) ?></td></tr>
                <tr><th>Combustion Engine Torque</th><td><?= e((string)(!empty($detailCar['engine_torque']) ? $detailCar['engine_torque'] : '750 Nm @ 1,800–5,400 rpm')) ?></td></tr>
                <tr><th>Acceleration (0–100 km/h)</th><td><strong><?= e((string)($detailCar['acceleration'] ?: '3.5 seconds')) ?></strong></td></tr>
                <tr><th>Acceleration (0–200 km/h)</th><td><?= e((string)(!empty($detailCar['accel_200']) ? $detailCar['accel_200'] : '10.9 seconds')) ?></td></tr>
                <tr><th>Top Speed (Standard)</th><td>250 km/h (electronically governed)</td></tr>
                <tr><th>Top Speed (M Driver’s Package)</th><td><strong><?= e((string)($detailCar['top_speed'] ?: '305 km/h')) ?></strong></td></tr>
              </tbody>
            </table>
          </div>

          <!-- ELECTRIC DRIVE & BATTERY PANEL -->
          <div class="bmw-tab-panel" id="spec-electric" role="tabpanel" aria-labelledby="spec-btn-electric">
            <table class="bmw-spec-table">
              <tbody>
                <tr><th>Electric Motor Type</th><td>Permanently excited synchronous motor</td></tr>
                <tr><th>Electric Motor Power</th><td><?= e((string)(!empty($detailCar['electric_power']) ? $detailCar['electric_power'] : '145 kW (197 hp) @ 6,000 rpm')) ?></td></tr>
                <tr><th>Electric Motor Torque</th><td><?= e((string)(!empty($detailCar['electric_torque']) ? $detailCar['electric_torque'] : '280 Nm (Nominal) / 450 Nm (Effective with pre-gearing)')) ?></td></tr>
                <tr><th>High-Voltage Battery Type</th><td>Lithium-ion</td></tr>
                <tr><th>Net Usable Battery Capacity</th><td><?= e((string)(!empty($detailCar['battery_capacity']) ? $detailCar['battery_capacity'] : '18.6 kWh (22.1 kWh Gross)')) ?></td></tr>
                <tr><th>Pure Electric Range (WLTP)</th><td><strong><?= e((string)(!empty($detailCar['electric_range']) ? $detailCar['electric_range'] : (!empty($detailCar['drive_or_range']) ? $detailCar['drive_or_range'] : 'Up to 69 km'))) ?></strong></td></tr>
                <tr><th>Electric Top Speed</th><td>140 km/h</td></tr>
                <tr><th>Charging Time (0–100% AC 7.4 kW)</th><td><?= e((string)(!empty($detailCar['charging_time']) ? $detailCar['charging_time'] : 'Approx. 3 hours 15 minutes (BMW Wallbox)')) ?></td></tr>
                <tr><th>Energy Consumption / Mileage</th><td><?= e((string)(!empty($detailCar['mileage']) ? $detailCar['mileage'] : '58.8 km/l (Weighted combined)')) ?></td></tr>
              </tbody>
            </table>
          </div>

          <!-- TRANSMISSION & DRIVETRAIN PANEL -->
          <div class="bmw-tab-panel" id="spec-drivetrain" role="tabpanel" aria-labelledby="spec-btn-drivetrain">
            <table class="bmw-spec-table">
              <tbody>
                <tr><th>Transmission</th><td><?= e((string)($detailCar['transmission'] ?: '8-Speed M Steptronic with Drivelogic')) ?></td></tr>
                <tr><th>Drive Concept</th><td><?= e((string)(!empty($detailCar['drivetrain']) ? $detailCar['drivetrain'] : 'M xDrive All-Wheel Drive with Active M Differential')) ?></td></tr>
                <tr><th>Selectable Drive Modes</th><td>4WD, 4WD Sport, 2WD (Pure Rear-Wheel Drive)</td></tr>
                <tr><th>Suspension</th><td><?= e((string)(!empty($detailCar['suspension']) ? $detailCar['suspension'] : 'Adaptive M suspension with electronically controlled dampers')) ?></td></tr>
                <tr><th>Steering System</th><td>M Servotronic variable-ratio electric power steering</td></tr>
                <tr><th>Front Tyres &amp; Wheels</th><td><?= e((string)(!empty($detailCar['wheels_front']) ? $detailCar['wheels_front'] : '285/40 ZR20 on 20-inch M alloy wheels')) ?></td></tr>
                <tr><th>Rear Tyres &amp; Wheels</th><td><?= e((string)(!empty($detailCar['wheels_rear']) ? $detailCar['wheels_rear'] : '295/35 ZR21 on 21-inch M alloy wheels')) ?></td></tr>
              </tbody>
            </table>
          </div>

          <!-- DIMENSIONS & WEIGHT PANEL -->
          <div class="bmw-tab-panel" id="spec-dimensions" role="tabpanel" aria-labelledby="spec-btn-dimensions">
            <table class="bmw-spec-table">
              <tbody>
                <tr><th>Dimensions (L × W × H)</th><td><?= e((string)(!empty($detailCar['dimensions']) ? $detailCar['dimensions'] : '5,096 × 1,970 × 1,510 mm')) ?></td></tr>
                <tr><th>Wheelbase</th><td><?= e((string)(!empty($detailCar['wheelbase']) ? $detailCar['wheelbase'] : '3,006 mm')) ?></td></tr>
                <tr><th>Front Track Width</th><td>1,710 mm (+75mm wider than standard 5 Series)</td></tr>
                <tr><th>Rear Track Width</th><td>1,683 mm (+48mm wider than standard 5 Series)</td></tr>
                <tr><th>Luggage / Boot Capacity</th><td><?= e((string)(!empty($detailCar['boot_capacity']) ? $detailCar['boot_capacity'] : '466 litres')) ?></td></tr>
                <tr><th>Unladen Weight (DIN)</th><td><?= e((string)(!empty($detailCar['weight']) ? $detailCar['weight'] : '2,435 kg')) ?></td></tr>
                <tr><th>Fuel Tank Capacity</th><td><?= e((string)(!empty($detailCar['fuel_tank']) ? $detailCar['fuel_tank'] : '60 litres')) ?></td></tr>
                <tr><th>Seating Capacity</th><td><?= e((string)($detailCar['seating_capacity'] ?: '5 Seats')) ?></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- OWNERSHIP PRIVILEGES & FINANCIAL SERVICES -->
    <section class="bmw-section" id="privileges">
      <div class="bmw-container">
        <div class="bmw-section-header">
          <div class="bmw-eyebrow">Bespoke Ownership</div>
          <h2 class="bmw-section-title">Pricing &amp; Privileges</h2>
          <p class="bmw-section-desc">
            Transparent acquisition, flexible financing through BMW Financial Services, and complete peace of mind with all-inclusive maintenance packages.
          </p>
        </div>

        <div class="bmw-privileges-grid">
          <?php if (!empty($offers)): ?>
            <?php foreach ($offers as $i => $offer): ?>
              <div class="bmw-privilege-item">
                <i class="<?= $i === 0 ? 'fa-solid fa-wallet' : ($i === 1 ? 'fa-solid fa-shield-halved' : ($i === 2 ? 'fa-solid fa-charging-station' : ($i === 3 ? 'fa-solid fa-flag-checkered' : 'fa-solid fa-award'))) ?> bmw-privilege-icon"></i>
                <strong>Privilege <?= sprintf('%02d', $i + 1) ?></strong>
                <span><?= e($offer) ?></span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="bmw-privilege-item">
              <i class="fa-solid fa-wallet bmw-privilege-icon"></i>
              <strong>BMW 360° Plan</strong>
              <span>Customized finance packages with low monthly installments starting from <?= e((string)($detailCar['emi'] ?: '₹ 1,99,999/mo')) ?> and guaranteed buyback value.</span>
            </div>

            <div class="bmw-privilege-item">
              <i class="fa-solid fa-shield-halved bmw-privilege-icon"></i>
              <strong>BMW Service Inclusive</strong>
              <span>Enjoy complimentary 3 Years / 40,000 km BMW Service Inclusive Plus covering wear-and-tear items and maintenance.</span>
            </div>

            <div class="bmw-privilege-item">
              <i class="fa-solid fa-charging-station bmw-privilege-icon"></i>
              <strong>Wallbox &amp; Battery Warranty</strong>
              <span>Complimentary BMW Wallbox charger installation with comprehensive 8-Year / 160,000 km high-voltage battery guarantee.</span>
            </div>

            <div class="bmw-privilege-item">
              <i class="fa-solid fa-flag-checkered bmw-privilege-icon"></i>
              <strong>BMW M Track Day</strong>
              <span>Exclusive invitation to a professional BMW M Track Experience at the Buddh International Circuit upon taking delivery.</span>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- TEST DRIVE & PURCHASE INQUIRY FORM -->
    <section class="bmw-section" id="test-drive">
      <div class="bmw-container">
        <div class="bmw-inquiry-layout">
          <div class="bmw-inquiry-visual">
            <img src="<?= e($heroImage) ?>" alt="<?= e((string)$detailCar['name']) ?> Test Drive Booking">
          </div>

          <div>
            <div class="bmw-section-header" style="margin-bottom: 28px;">
              <div class="bmw-eyebrow">The Ultimate Driving Machine</div>
              <h2 class="bmw-section-title" style="font-size: clamp(1.8rem, 3.2vw, 2.6rem);">Book a Test Drive</h2>
              <p class="bmw-section-desc" style="font-size: 0.95rem;">
                Experience the thunderous response and dynamic agility of the <?= e((string)$detailCar['name']) ?> firsthand. Our certified BMW Client Advisors will coordinate your personalized drive.
              </p>
            </div>

            <form class="bmw-form-grid" method="post" action="testdrive.php">
              <input type="hidden" name="csrf" value="<?= e(app_csrf_token()) ?>">
              <input type="hidden" name="Car_ID" value="<?= (int)($detailCar['id'] ?? 0) ?>">
              <input type="hidden" name="Series" value="<?= e((string)$detailCar['series']) ?>">
              <input type="hidden" name="Model" value="<?= e((string)$detailCar['name']) ?>">
              <input type="hidden" name="Request_Type" value="test_drive">
              <input type="hidden" name="Finance" value="no">

              <div class="bmw-form-field">
                <label for="first-name">First Name *</label>
                <input class="bmw-form-input" id="first-name" name="First_Name" autocomplete="given-name" maxlength="100" required placeholder="e.g. Arjun">
              </div>

              <div class="bmw-form-field">
                <label for="last-name">Last Name *</label>
                <input class="bmw-form-input" id="last-name" name="Last_Name" autocomplete="family-name" maxlength="100" required placeholder="e.g. Patel">
              </div>

              <div class="bmw-form-field">
                <label for="email">Email Address *</label>
                <input class="bmw-form-input" id="email" name="E-Mail" type="email" autocomplete="email" maxlength="191" required placeholder="name@example.com">
              </div>

              <div class="bmw-form-field">
                <label for="phone">Phone Number *</label>
                <input class="bmw-form-input" id="phone" name="Mobile_No" type="tel" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" placeholder="10-digit mobile" required>
              </div>

              <div class="bmw-form-field full-width">
                <label for="city">Select Dealership City *</label>
                <select class="bmw-form-select" id="city" name="City" required>
                  <option value="">Choose nearest BMW center</option>
                  <option value="Ahmedabad" selected>Ahmedabad (Beamers Legacy Flagship)</option>
                  <option value="Mumbai">Mumbai (Worli &amp; Infinity Mall)</option>
                  <option value="Delhi">Delhi / NCR (Bird Automotive &amp; Deutsche)</option>
                  <option value="Bangalore">Bangalore (Navnit Motors)</option>
                  <option value="Hyderabad">Hyderabad (KUN Exclusive)</option>
                  <option value="Chennai">Chennai (KUN Motor Co)</option>
                  <option value="Pune">Pune (Bavaria Motors)</option>
                </select>
              </div>

              <div class="bmw-form-field full-width">
                <label class="bmw-consent-box">
                  <input type="checkbox" name="Privacy_Consent" value="yes" required>
                  <span>I authorize Beamers Legacy &amp; BMW India to contact me regarding my test drive request and accept the dealership privacy terms.</span>
                </label>
              </div>

              <button class="bmw-btn-submit" type="submit">
                Request an Exclusive Appointment <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- FINAL CALL TO ACTION BANNER -->
    <section class="bmw-section" style="padding-top: 0;">
      <div class="bmw-container">
        <div class="bmw-cta-banner">
          <div class="bmw-cta-copy">
            <div class="bmw-eyebrow" style="margin-bottom: 8px;">
              <?php if ($isM5): ?><span class="bmw-m-badge"><span class="bmw-m-stripe cyan"></span><span class="bmw-m-stripe dark-blue"></span><span class="bmw-m-stripe red"></span><span class="bmw-m-letter">M</span></span><?php endif; ?>
              The Ultimate Driving Experience
            </div>
            <h2>Are You Ready for the <?= e((string)$detailCar['name']) ?>?</h2>
            <p>Configure your personal vehicle, request custom finance quotes, or speak with an M Certified specialist today.</p>
          </div>

          <div class="bmw-cta-buttons">
            <a href="<?= e($configureUrl) ?>" class="bmw-hero-btn-primary">
              <span>Configure Now</span>
              <i class="fa-solid fa-sliders" aria-hidden="true"></i>
            </a>
            <a href="<?= e($bookingUrl) ?>" class="bmw-hero-btn-outline">
              <span>Reserve Model</span>
            </a>
            <?php if ($brochure !== ''): ?>
              <a href="<?= e($brochure) ?>" class="bmw-hero-btn-outline" download>
                <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                <span>Brochure</span>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- MOBILE FLOATING STICKY ACTION BAR -->
  <div class="bmw-mobile-sticky-bar" aria-label="Mobile quick actions">
    <a href="<?= e($configureUrl) ?>" class="bmw-mobile-btn-primary">Configure</a>
    <a href="#test-drive" class="bmw-mobile-btn-outline">Book Test Drive</a>
  </div>

<?php endif; ?>

<!-- DEALERSHIP FOOTER -->
<footer class="premium-footer">
  <div class="footer-top">
    <div class="footer-info">
      <h2>BEAMERS LEGACY</h2>
      <p>Authorized BMW dealership delivering Bavarian precision, bespoke high-performance automobiles, and peerless client care.</p>
    </div>
    <div class="footer-col">
      <h3>Showroom Pages</h3>
      <ul>
        <li><a href="home.php">Home</a></li>
        <li><a href="models.php">All Models</a></li>
        <li><a href="Electric.php">BMW Electric</a></li>
        <li><a href="all lineup.php">Showroom Lineup</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h3>Services</h3>
      <ul>
        <li><a href="configrator.php">Configure &amp; Price</a></li>
        <li><a href="visit online shop.php">Lifestyle Shop</a></li>
        <li><a href="testdrive.php">Book Test Drive</a></li>
        <li><a href="customer support.php">Customer Support</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h3>Department</h3>
      <ul>
        <?php if (!empty($_SESSION['user_id'])): ?>
          <li><a href="account.php">My Profile</a></li>
          <li><a href="logout.php">Sign Out</a></li>
        <?php else: ?>
          <li><a href="loginform.php">User Portal Login</a></li>
          <li><a href="register.php">Create Account</a></li>
        <?php endif; ?>
        <li><a href="adminlogin.php">Staff &amp; Admin Access</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; 2026 Beamers Legacy Dealership. Preserving Bavarian Precision.</p>
    <div class="footer-social-links">
      <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
      <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
      <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
      <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
    </div>
  </div>
</footer>

<script src="../js/premium.js"></script>
<script>
  // 1. Hero Video Toggle
  const heroVideo = document.querySelector('.bmw-hero-media video');
  const videoToggle = document.querySelector('[data-hero-video-toggle]');
  if (heroVideo && videoToggle) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      heroVideo.pause();
      videoToggle.setAttribute('aria-label', 'Play hero video');
      videoToggle.innerHTML = '<i class="fa-solid fa-play" aria-hidden="true"></i>';
    }
    videoToggle.addEventListener('click', function () {
      if (heroVideo.paused) {
        heroVideo.play().catch(function (e) { console.warn(e); });
        videoToggle.setAttribute('aria-label', 'Pause hero video');
        videoToggle.innerHTML = '<i class="fa-solid fa-pause" aria-hidden="true"></i>';
      } else {
        heroVideo.pause();
        videoToggle.setAttribute('aria-label', 'Play hero video');
        videoToggle.innerHTML = '<i class="fa-solid fa-play" aria-hidden="true"></i>';
      }
    });
  }

  // 2. Interactive Color Visualizer
  const visualizerWash = document.getElementById('visualizer-wash');
  const visualizerColorName = document.getElementById('visualizer-color-name');
  const swatchButtons = document.querySelectorAll('.bmw-swatch-btn');
  swatchButtons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      swatchButtons.forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-checked', 'false');
      });
      btn.classList.add('active');
      btn.setAttribute('aria-checked', 'true');
      const tint = btn.getAttribute('data-tint');
      const name = btn.getAttribute('data-color-name');
      if (visualizerWash) {
        visualizerWash.style.setProperty('--car-tint', tint);
      }
      if (visualizerColorName) {
        visualizerColorName.textContent = name;
      }
    });
  });

  // 3. Design Tabs (Exterior vs Interior)
  const tabButtons = document.querySelectorAll('.bmw-tabs-nav .bmw-tab-btn');
  tabButtons.forEach(function (tab) {
    tab.addEventListener('click', function () {
      const parentNav = tab.closest('.bmw-tabs-nav');
      if (!parentNav) return;
      const targetPanelId = tab.getAttribute('aria-controls');
      const siblingTabs = parentNav.querySelectorAll('.bmw-tab-btn');
      siblingTabs.forEach(t => {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
      });
      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');

      // Hide sibling panels and show target panel
      const container = parentNav.parentElement;
      const panels = container.querySelectorAll('.bmw-tab-panel');
      panels.forEach(p => p.classList.remove('active'));
      const targetPanel = document.getElementById(targetPanelId);
      if (targetPanel) {
        targetPanel.classList.add('active');
      }
    });
  });

  // 4. Sticky Subnav Active Link Highlighting (Scrollspy)
  const subnavLinks = document.querySelectorAll('.bmw-model-subnav-links a');
  const sections = Array.from(subnavLinks).map(link => {
    const id = link.getAttribute('href').replace('#', '');
    return document.getElementById(id);
  }).filter(Boolean);

  if ('IntersectionObserver' in window && sections.length > 0) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const id = entry.target.getAttribute('id');
          subnavLinks.forEach(link => {
            if (link.getAttribute('href') === '#' + id) {
              link.classList.add('active');
            } else {
              link.classList.remove('active');
            }
          });
        }
      });
    }, { rootMargin: '-20% 0px -70% 0px' });

    sections.forEach(sec => observer.observe(sec));
  }
</script>
</body>
</html>
