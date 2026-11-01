<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
start_app_session();
app_ensure_notification_schema(db());
$stockNotice = (string)($_SESSION['stock_notice_flash'] ?? '');
unset($_SESSION['stock_notice_flash']);
$stockCsrf = app_csrf_token();
function store_image_url(string $path): string
{
  return str_starts_with($path, 'images/') ? '../' . $path : $path;
}
$storeProducts = [];
$storeCategories = [];
$subscribedProductIds = [];
$shopSettings = [];
try {
  $settingsResult = db()->query("SELECT `setting_key`,`setting_value` FROM `dealer_settings` WHERE `setting_key` LIKE 'shop_%'");
  while ($setting = $settingsResult->fetch_assoc()) {
    $shopSettings[(string)$setting['setting_key']] = (string)$setting['setting_value'];
  }
  $categoryResult = db()->query("SELECT c.*,(SELECT COUNT(*) FROM `store_products` p WHERE p.category_id=c.id AND p.status IN ('active','out_of_stock')) AS product_count,(SELECT p.image FROM `store_products` p WHERE p.category_id=c.id AND p.status IN ('active','out_of_stock') AND p.image IS NOT NULL AND p.image<>'' ORDER BY p.featured DESC,p.created_at DESC LIMIT 1) AS product_image FROM `store_categories` c WHERE c.status='active' ORDER BY c.sort_order,c.name");
  $storeCategories = $categoryResult->fetch_all(MYSQLI_ASSOC);
  $storeResult = db()->query("SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM store_products p LEFT JOIN store_categories c ON c.id = p.category_id WHERE p.status IN ('active','out_of_stock') ORDER BY p.featured DESC, p.created_at DESC");
  $storeProducts = $storeResult->fetch_all(MYSQLI_ASSOC);
  $userId = (int)($_SESSION['user_id'] ?? 0);
  if ($userId > 0) {
    $subscriptions = db()->prepare('SELECT product_id FROM stock_notifications WHERE user_id = ? AND notified_at IS NULL');
    $subscriptions->bind_param('i', $userId);
    $subscriptions->execute();
    foreach ($subscriptions->get_result()->fetch_all(MYSQLI_ASSOC) as $subscription) {
      $subscribedProductIds[(int)$subscription['product_id']] = true;
    }
    $subscriptions->close();
  }
} catch (Throwable $error) {
  error_log('Lifestyle shop inventory could not be loaded: ' . $error->getMessage());
  $storeProducts = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BMW Lifestyle Online Shop | Beamers Legacy</title>
  <meta name="description" content="Shop the official BMW Lifestyle Collection. Premium apparel, carbon chronographs, sports luggage collections, and precision driving accessories." />
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Premium Stylesheets -->
  <link rel="stylesheet" href="../css/premium.css?v=20261025-navbar-active-1" />
  
  <style>
    .store-announcement { display:flex; justify-content:center; gap:32px; padding:10px 5%; background:#090b0e; border-bottom:1px solid rgba(255,255,255,.08); color:#d7d9dc; font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
    .store-announcement span { display:flex; align-items:center; gap:9px; }
    .store-announcement i { color:#68a9ff; }
    .shop-container { width:min(1320px,92%); margin:0 auto; }
    .shop-hero-section { position:relative; min-height:540px; display:flex; align-items:center; overflow:hidden; background:#080b0f; }
    .shop-hero-section::before { content:""; position:absolute; inset:0; background:linear-gradient(90deg,rgba(5,8,12,.96) 0%,rgba(5,8,12,.8) 38%,rgba(5,8,12,.12) 100%); z-index:1; }
    .shop-hero-image { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:center 55%; opacity:.78; }
    .shop-hero-content { position:relative; z-index:2; width:min(1320px,92%); margin:0 auto; padding:88px 0; }
    .shop-hero-copy-block { max-width:590px; }
    .shop-eyebrow { color:#70b5ff; font-size:.72rem; font-weight:800; letter-spacing:.2em; text-transform:uppercase; }
    .shop-hero-content h1 { max-width:570px; margin:18px 0; color:#fff; font-size:clamp(2.7rem,6vw,5.3rem); font-weight:800; letter-spacing:-.055em; line-height:.98; text-transform:uppercase; }
    .shop-hero-content h1 span { color:#78b8ff; }
    .shop-hero-description { max-width:460px; margin-bottom:26px; color:#d3d7dc; font-size:1rem; line-height:1.65; }
    .store-search-bar { display:flex; max-width:570px; gap:8px; padding:8px; background:rgba(8,11,15,.88); border:1px solid rgba(255,255,255,.18); backdrop-filter:blur(12px); }
    .store-search-bar select,.store-search-bar input { min-width:0; min-height:44px; padding:0 12px; border:0; outline:0; background:#171b20; color:#fff; }
    .store-search-bar select { flex:0 0 145px; }
    .store-search-bar input { flex:1; }
    .store-search-bar button,.store-cta { display:inline-flex; align-items:center; justify-content:center; gap:10px; min-height:44px; padding:0 20px; border:0; background:#1673d1; color:#fff; font-size:.72rem; font-weight:800; letter-spacing:.08em; text-decoration:none; text-transform:uppercase; cursor:pointer; transition:background .2s,transform .2s; }
    .store-search-bar button:hover,.store-cta:hover { background:#3189e0; transform:translateY(-1px); }
    .store-benefits { position:relative; z-index:2; display:grid; grid-template-columns:repeat(4,1fr); padding:24px max(4%,calc((100% - 1320px)/2)); background:#101317; border-bottom:1px solid rgba(255,255,255,.08); }
    .store-benefit { display:flex; align-items:center; justify-content:center; gap:13px; padding:9px 16px; border-right:1px solid rgba(255,255,255,.1); }
    .store-benefit:last-child { border:0; }
    .store-benefit i { color:#78b8ff; font-size:1.25rem; }
    .store-benefit strong,.store-benefit small { display:block; }
    .store-benefit strong { color:#f6f7f8; font-size:.72rem; letter-spacing:.06em; text-transform:uppercase; }
    .store-benefit small { margin-top:4px; color:#9299a2; font-size:.68rem; }
    .shop-section { padding:74px 0; }
    .shop-section-heading { display:flex; align-items:end; justify-content:space-between; gap:20px; margin-bottom:28px; }
    .shop-section-heading .shop-eyebrow { margin:0 0 9px; }
    .shop-section-heading h2 { margin:0; color:#f6f7f8; font-size:clamp(1.65rem,3.4vw,2.5rem); font-weight:750; letter-spacing:-.035em; text-transform:uppercase; }
    .shop-section-heading > a { color:#dce2e8; font-size:.75rem; font-weight:700; letter-spacing:.08em; text-decoration:none; text-transform:uppercase; }
    .shop-category-grid { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:14px; }
    .shop-category-card { position:relative; min-height:165px; display:flex; align-items:end; overflow:hidden; padding:17px; border:1px solid rgba(255,255,255,.1); background:#14181d; color:#fff; text-decoration:none; }
    .shop-category-card::after { content:""; position:absolute; inset:35% 0 0; background:linear-gradient(transparent,rgba(0,0,0,.9)); }
    .shop-category-card img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:.63; transition:transform .35s,opacity .35s; }
    .shop-category-card:hover img { opacity:.85; transform:scale(1.06); }
    .shop-category-copy { position:relative; z-index:1; }
    .shop-category-copy strong { display:block; font-size:.87rem; letter-spacing:.04em; text-transform:uppercase; }
    .shop-category-copy small { display:block; margin-top:5px; color:#c2c8ce; font-size:.7rem; }
    .shop-toolbar { display:flex; align-items:center; justify-content:space-between; gap:20px; margin:20px 0; }
    .shop-categories { display:flex; flex-wrap:wrap; gap:8px; }
    .shop-filter { padding:9px 13px; border:1px solid rgba(255,255,255,.15); background:transparent; color:#b4bbc3; font-size:.68rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; cursor:pointer; }
    .shop-filter:hover,.shop-filter.is-active { border-color:#1673d1; background:#1673d1; color:#fff; }
    .shop-search { display:flex; align-items:center; gap:10px; min-width:220px; padding:0 12px; border:1px solid rgba(255,255,255,.16); color:#aeb5bd; }
    .shop-search input { width:100%; min-height:40px; border:0; outline:0; background:transparent; color:#fff; }
    .shop-result-count { color:#9299a2; font-size:.76rem; }
    .shop-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; margin-top:18px; }
    .product-card { position:relative; display:flex; min-width:0; flex-direction:column; justify-content:space-between; padding:14px; border:1px solid rgba(255,255,255,.09); background:#f4f5f6; color:#15191e; transition:transform .22s,box-shadow .22s; }
    .product-card:hover { z-index:1; transform:translateY(-4px); box-shadow:0 18px 45px rgba(0,0,0,.3); }
    .product-card[hidden] { display:none!important; }
    .product-card .category-lbl { color:#59626d; font-size:.62rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
    .product-card h3 { min-height:40px; margin:7px 0 0; color:#161a1f; font-size:.94rem; font-weight:800; letter-spacing:-.015em; text-transform:uppercase; }
    .product-card .img-wrap { position:relative; height:190px; margin:10px -2px 12px; overflow:hidden; background:#e7e9eb; }
    .product-card .img-wrap img { width:100%; height:100%; object-fit:contain; mix-blend-mode:multiply; transition:transform .3s; }
    .product-card:hover .img-wrap img { transform:scale(1.06); }
    .product-card .product-description { min-height:38px; margin:0 0 13px; color:#58616b; font-size:.75rem; line-height:1.5; }
    .product-badge { position:absolute; top:12px; left:12px; z-index:2; padding:6px 8px; background:#1673d1; color:white; font-size:.59rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .product-card .price-buy-row { display:flex; align-items:center; justify-content:space-between; gap:8px; border-top:1px solid #d9dde1; padding-top:13px; }
    .product-card .price { color:#101419; font-size:1.03rem; font-weight:850; white-space:nowrap; }
    .product-price-old { display:block; margin-top:2px; color:#7c838a; font-size:.65rem; text-decoration:line-through; }
    .btn-buy-product { min-height:38px; padding:0 12px; border:0; background:#1673d1; color:#fff; font-size:.64rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; cursor:pointer; transition:background .2s; }
    .btn-buy-product:hover { background:#0d5cae; }
    .stock-notify-form { margin-top:12px; }
    .stock-notify-state { display:inline-block; margin-top:12px; }
    .stock-notify-link { display:inline-block; margin-top:12px; text-decoration:none; }
    .store-offer-grid { display:grid; grid-template-columns:1.12fr .88fr; gap:16px; }
    .store-offer { position:relative; min-height:225px; display:flex; align-items:center; overflow:hidden; padding:28px; border:1px solid rgba(255,255,255,.1); background:#121820; }
    .store-offer::after { content:""; position:absolute; inset:0; background:linear-gradient(90deg,rgba(8,11,15,.96),rgba(8,11,15,.28)); }
    .store-offer img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:.62; }
    .store-offer-content { position:relative; z-index:1; max-width:400px; }
    .store-offer h3 { margin:8px 0; color:#fff; font-size:clamp(1.5rem,3vw,2.15rem); line-height:1.08; text-transform:uppercase; }
    .store-offer p { color:#c5cbd2; font-size:.82rem; line-height:1.55; }
    .store-offer .shop-eyebrow { margin:0; }
    .store-empty-state { grid-column:1/-1; padding:48px 18px; border:1px dashed rgba(255,255,255,.18); color:#c4c9ce; text-align:center; }
    .shop-cart-toast { position:fixed; right:22px; bottom:22px; z-index:1000; padding:14px 20px; border:1px solid rgba(255,255,255,.15); background:#111820; color:#fff; opacity:0; pointer-events:none; transform:translateY(12px); transition:.2s; }
    .shop-cart-toast.is-visible { opacity:1; transform:translateY(0); }
    @media(max-width:1000px) { .shop-category-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .shop-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .store-benefits { grid-template-columns:repeat(2,1fr); } .store-benefit:nth-child(2) { border-right:0; } }
    @media(max-width:700px) { .store-announcement { flex-wrap:wrap; gap:8px 18px; font-size:.58rem; } .shop-hero-section { min-height:535px; } .shop-hero-section::before { background:linear-gradient(90deg,rgba(5,8,12,.94),rgba(5,8,12,.56)),linear-gradient(0deg,rgba(5,8,12,.7),transparent); } .shop-hero-content { padding:68px 0; } .shop-hero-content h1 { font-size:clamp(2.55rem,12vw,4rem); } .store-search-bar { display:grid; grid-template-columns:1fr; } .store-search-bar select { flex:auto; } .store-benefits { padding:14px 4%; } .store-benefit { justify-content:flex-start; gap:10px; padding:12px 8px; } .store-benefit strong { font-size:.62rem; } .store-benefit small { font-size:.59rem; } .shop-section { padding:52px 0; } .shop-category-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; } .shop-category-card { min-height:135px; padding:12px; } .shop-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; } .product-card { padding:10px; } .product-card .img-wrap { height:145px; } .product-card h3 { font-size:.78rem; } .product-card .product-description { font-size:.68rem; } .product-card .price { font-size:.87rem; } .btn-buy-product { padding:0 8px; font-size:.56rem; } .shop-toolbar { align-items:stretch; flex-direction:column; } .shop-search { width:100%; } .shop-section-heading { align-items:flex-start; flex-direction:column; } .store-offer-grid { grid-template-columns:1fr; } .store-offer { min-height:200px; padding:22px; } }
    @media(max-width:380px) { .shop-grid { grid-template-columns:1fr; } .product-card .img-wrap { height:190px; } }
  </style>
</head>

<body>

  <div class="store-announcement" aria-label="Store benefits">
    <span><i class="fa-solid fa-car-side" aria-hidden="true"></i><?= e($shopSettings['shop_announcement_left'] ?? 'BMW lifestyle and accessories') ?></span>
    <span><i class="fa-solid fa-star" aria-hidden="true"></i><?= e($shopSettings['shop_announcement_right'] ?? 'Curated for BMW owners') ?></span>
  </div>

  <!-- ==========================================================================
       GLOBAL NAVBAR Header
       ========================================================================== */ -->
  <?php require_once __DIR__ . '/navbar.php'; ?>

  <!-- ==========================================================================
       FULLSCREEN OVERLAY MENU
       ========================================================================== */ -->
  <div class="fs-menu-overlay">
    <div class="fs-menu-container">
      <div class="fs-menu-links">
        <a href="models.php" data-preview="img-m5">BMW Models</a>
        <a href="all lineup.php" data-preview="img-lineup">Showroom Lineup</a>
        <a href="Electric.php" data-preview="img-electric">Electric Driving</a>
        <a href="configrator.php" data-preview="img-configurator">Configure &amp; Price</a>
        <a href="visit online shop.php" data-preview="img-lifestyle">Lifestyle Shop</a>
        <a href="customer support.php" data-preview="img-support">Support Center</a>
        <a href="testdrive.php" data-preview="img-testdrive">Book Test Drive</a>
      </div>
      <div class="fs-menu-preview">
        <img id="img-m5" src="../images/M8 compi.png" class="active" alt="BMW Models" />
        <img id="img-lineup" src="../images/m4 cs.png" alt="Showroom Lineup" />
        <img id="img-electric" src="../images/ix drive50.png" alt="Electric Driving" />
        <img id="img-configurator" src="../images/bmw color 1.jpg" alt="Configurator" />
        <img id="img-lifestyle" src="../images/shop 1.jpg" alt="Lifestyle Shop" />
        <img id="img-support" src="../images/contact 1.webp" alt="Support Center" />
        <img id="img-testdrive" src="../images/m4bg.jpg" alt="Book Test Drive" />
      </div>
    </div>
  </div>

  <section class="shop-hero-section" aria-labelledby="shop-hero-title">
    <img class="shop-hero-image" src="<?= e(store_image_url($shopSettings['shop_hero_image'] ?? 'images/m4bg.jpg')) ?>" alt="" fetchpriority="high">
    <div class="shop-hero-content">
      <div class="shop-hero-copy-block">
        <p class="shop-eyebrow"><?= e($shopSettings['shop_hero_eyebrow'] ?? 'BMW ORIGINAL ACCESSORIES') ?></p>
        <h1 id="shop-hero-title"><?= e($shopSettings['shop_hero_title'] ?? 'MAKE EVERY DRIVE YOUR OWN.') ?></h1>
        <p class="shop-hero-description"><?= e($shopSettings['shop_hero_copy'] ?? 'Discover genuine BMW accessories and lifestyle essentials, selected for the way you drive.') ?></p>
        <form class="store-search-bar" id="store-hero-search" role="search">
          <select id="store-hero-category" aria-label="Choose a product category">
            <option value="all">All categories</option>
            <?php foreach ($storeCategories as $category): ?><option value="<?= e((string)$category['slug']) ?>"><?= e((string)$category['name']) ?></option><?php endforeach; ?>
          </select>
          <input id="store-hero-query" type="search" placeholder="Find your BMW essentials" aria-label="Search the store">
          <button type="submit"><?= e($shopSettings['shop_hero_cta'] ?? 'Shop now') ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
        </form>
      </div>
    </div>
  </section>
  <section class="store-benefits" aria-label="Shopping benefits">
    <div class="store-benefit"><i class="fa-solid fa-medal" aria-hidden="true"></i><span><strong>Genuine BMW quality</strong><small>Designed for your BMW lifestyle</small></span></div>
    <div class="store-benefit"><i class="fa-solid fa-box-open" aria-hidden="true"></i><span><strong>Curated collection</strong><small>Essentials for every BMW fan</small></span></div>
    <div class="store-benefit"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i><span><strong>Easy to explore</strong><small>Browse and add favourites to your cart</small></span></div>
    <div class="store-benefit"><i class="fa-solid fa-headset" aria-hidden="true"></i><span><strong>Personal assistance</strong><small>Our BMW team is here to help</small></span></div>
  </section>
  <section class="shop-section" id="shop-categories">
    <div class="shop-container">
      <div class="shop-section-heading"><div><p class="shop-eyebrow">CURATED FOR BMW OWNERS</p><h2>Shop by category</h2></div><a href="#lifestyle-catalog">Explore all products <i class="fa-solid fa-arrow-right"></i></a></div>
      <div class="shop-category-grid">
        <?php foreach ($storeCategories as $category): ?>
          <a href="#lifestyle-catalog" class="shop-category-card category-shortcut" data-filter="<?= e((string)$category['slug']) ?>">
            <img src="<?= e(store_image_url((string)($category['image'] ?: ($category['product_image'] ?: 'images/shop 1.jpg')))) ?>" alt="" loading="lazy">
            <span class="shop-category-copy"><strong><?= e((string)$category['name']) ?></strong><small><?= (int)$category['product_count'] ?> products <i class="fa-solid fa-arrow-right"></i></small></span>
          </a>
        <?php endforeach; ?>
        <?php if (!$storeCategories): ?><p class="shop-result-count">Shop categories will appear here when they are set up in the admin panel.</p><?php endif; ?>
      </div>
    </div>
  </section>
  <section class="shop-section" id="lifestyle-catalog">
    <div class="shop-container">
      <div class="shop-section-heading"><div><p class="shop-eyebrow">BMW COLLECTION</p><h2>Selected for you</h2></div><a href="#all-store-products">View all <i class="fa-solid fa-arrow-right"></i></a></div>
      <?php if ($stockNotice !== ''): ?><p class="shop-result-count" role="status"><?= e($stockNotice) ?></p><?php endif; ?>
      <div class="shop-toolbar">
        <div class="shop-categories" role="group" aria-label="Filter products by category">
          <button class="shop-filter is-active" type="button" data-filter="all">All products</button>
          <?php foreach ($storeCategories as $category): ?><button class="shop-filter" type="button" data-filter="<?= e((string)$category['slug']) ?>"><?= e((string)$category['name']) ?></button><?php endforeach; ?>
        </div>
        <label class="shop-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="shop-search" type="search" placeholder="Search products..." aria-label="Search products"></label>
      </div>
      <p class="shop-result-count" id="shop-result-count" aria-live="polite">Showing <?= count($storeProducts) ?> products</p>
      <div class="shop-grid" id="all-store-products">
        <?php foreach ($storeProducts as $product): ?>
          <?php
            $inStock = (int)$product['stock'] > 0 && $product['status'] === 'active';
            $currentPrice = (float)($product['sale_price'] ?? 0) > 0 ? (float)$product['sale_price'] : (float)$product['price'];
            $currency = (string)($product['currency'] ?? 'INR');
            $currencySymbol = $currency === 'INR' ? '₹' : ($currency === 'USD' ? '$' : $currency . ' ');
          ?>
          <article class="product-card" id="product-<?= (int)$product['id'] ?>" data-slug="<?= e((string)$product['slug']) ?>" data-category="<?= e((string)($product['category_slug'] ?? 'uncategorized')) ?>" data-name="<?= e((string)$product['name']) ?>" style="scroll-margin-top:120px;">
            <?php if (!empty($product['badge'])): ?><span class="product-badge"><?= e((string)$product['badge']) ?></span><?php elseif ((int)$product['featured'] === 1): ?><span class="product-badge">Featured</span><?php endif; ?>
            <div>
              <span class="category-lbl"><?= e((string)($product['category_name'] ?? 'BMW Collection')) ?></span>
              <h3><?= e((string)$product['name']) ?></h3>
              <div class="img-wrap"><img src="<?= e(store_image_url((string)($product['image'] ?: 'images/shop 1.jpg'))) ?>" alt="<?= e((string)$product['name']) ?>" loading="lazy"></div>
              <p class="product-description"><?= e((string)($product['short_description'] ?: 'Genuine BMW quality, selected for your everyday drive.')) ?></p>
            </div>
            <div>
              <div class="price-buy-row"><span><strong class="price"><?= e($currencySymbol) ?><?= number_format($currentPrice, 2) ?></strong><?php if ($currentPrice < (float)$product['price']): ?><small class="product-price-old"><?= e($currencySymbol) ?><?= number_format((float)$product['price'], 2) ?></small><?php endif; ?></span>
                <?php if ($inStock): ?><button class="btn-buy-product" data-product="<?= e((string)$product['name']) ?>" data-price="<?= $currentPrice ?>" data-product-id="<?= (int)$product['id'] ?>">Add to cart</button><?php else: ?><span class="category-lbl" aria-label="Currently unavailable">Out of stock</span><?php endif; ?>
              </div>
              <?php if (!$inStock): ?>
                <?php if (!empty($_SESSION['user_id'])): ?>
                  <?php if (isset($subscribedProductIds[(int)$product['id']])): ?><span class="category-lbl stock-notify-state" role="status">Restock alert requested</span>
                  <?php else: ?><form method="post" action="stock-notify.php" class="stock-notify-form"><input type="hidden" name="csrf" value="<?= e($stockCsrf) ?>"><input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>"><button class="btn-buy-product" type="submit">Notify me when available</button></form><?php endif; ?>
                <?php else: ?><a class="btn-buy-product stock-notify-link" href="loginform.php?return_to=<?= rawurlencode('visit online shop.php?product=' . (string)$product['slug']) ?>">Sign in for stock alert</a><?php endif; ?>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
        <?php if (!$storeProducts): ?><div class="store-empty-state"><i class="fa-solid fa-box-open"></i><h3>New BMW essentials are on the way</h3><p>Please check back soon for the latest collection.</p></div><?php endif; ?>
      </div>
    </div>
  </section>
  <section class="shop-section" aria-label="BMW collection highlights">
    <div class="shop-container store-offer-grid">
      <article class="store-offer"><img src="../images/m4bg.jpg" alt="" loading="lazy"><div class="store-offer-content"><p class="shop-eyebrow">BMW M COLLECTION</p><h3>Precision for every journey.</h3><p>Discover pieces inspired by performance, crafted to carry the spirit of BMW M beyond the road.</p><a class="store-cta" href="#lifestyle-catalog">Explore the collection <i class="fa-solid fa-arrow-right"></i></a></div></article>
      <article class="store-offer"><img src="../images/shop 1.jpg" alt="" loading="lazy"><div class="store-offer-content"><p class="shop-eyebrow">THE BMW LIFESTYLE</p><h3>Made to move with you.</h3><p>Everyday essentials, travel pieces and thoughtful gifts for the people who love the drive.</p><a class="store-cta" href="#shop-categories">Browse categories <i class="fa-solid fa-arrow-right"></i></a></div></article>
    </div>
  </section>

  <?php if (false): ?>
  <!-- ==========================================================================
       LEGACY SHOP CONTENT
       ========================================================================== */ -->
  <div class="shop-hero-section">
    <span class="section-subtitle">BMW COLLECTION</span>
    <h1 class="section-title text-gradient">EXPERIENCE BMW BEYOND THE ROAD</h1>
    <p class="shop-hero-copy">Discover premium BMW lifestyle products, apparel, accessories and collectibles designed for enthusiasts.</p>
    
    <div class="shop-promo-banner">
      <img src="../images/shop 1.jpg" alt="BMW Lifestyle Banner" />
      <div class="shop-promo-content" style="text-align: left;">
        <h2>THE BMW M<br />COLLECTION</h2>
        <p style="color: var(--text-secondary); margin-bottom: 25px; font-weight: 300;">
          Express your passion for high precision engineering wherever you travel. Hand-crafted luggage, functional carbon chronographs, and outdoor apparel.
        </p>
        <a class="btn-primary" href="#lifestyle-catalog">Shop Collection</a>
        <a class="btn-secondary" href="#lifestyle-catalog" style="margin-left:10px;">Explore BMW M</a>
      </div>
    </div>
  </div>

  <!-- ==========================================================================
       PRODUCTS GRID CATALOG
       ========================================================================== */ -->
  <section class="shop-catalog-section" id="lifestyle-catalog">
    <div class="shop-container">
      <span class="section-subtitle">CURATED GEAR</span>
      <h2 style="font-size: 2.2rem; font-weight: 800; text-transform: uppercase;">LIFESTYLE SELECTIONS</h2>
      <div class="shop-toolbar">
        <div class="shop-categories" role="tablist" aria-label="Product categories">
          <button class="shop-filter is-active" type="button" data-filter="all">All</button>
          <button class="shop-filter" type="button" data-filter="clothing">Clothing</button>
          <button class="shop-filter" type="button" data-filter="accessories">Accessories</button>
          <button class="shop-filter" type="button" data-filter="bmw-m">BMW M</button>
          <button class="shop-filter" type="button" data-filter="motorsport">Motorsport</button>
          <button class="shop-filter" type="button" data-filter="travel">Travel</button>
          <button class="shop-filter" type="button" data-filter="lifestyle">Lifestyle</button>
        </div>
        <label class="shop-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input id="shop-search" type="search" placeholder="Search products..." aria-label="Search products"></label>
      </div>
      <?php if ($stockNotice !== ''): ?><p class="shop-result-count" role="status"><?= e($stockNotice) ?></p><?php endif; ?>
      <p class="shop-result-count" id="shop-result-count" aria-live="polite">Showing <?= count($storeProducts) ?> products</p>
      
      <div class="shop-grid">
        <?php foreach ($storeProducts as $product): ?>
        <?php $inStock = (int)$product['stock'] > 0 && $product['status'] === 'active'; ?>
        <?php $categoryFilter = strtolower((string)preg_replace('/[^a-z0-9]+/i', '-', trim((string)($product['category_name'] ?? 'lifestyle')))); ?>
        <div class="product-card" id="product-<?= (int)$product['id'] ?>" data-slug="<?= e((string)$product['slug']) ?>" data-category="<?= e($categoryFilter) ?>" data-name="<?= e((string)$product['name']) ?>" style="scroll-margin-top: 120px;">
          <div>
            <span class="category-lbl"><?= e((string)($product['category_name'] ?? 'BMW Lifestyle')) ?></span>
            <h3><?= e((string)$product['name']) ?></h3>
            <div class="img-wrap"><img src="<?= e(store_image_url((string)($product['image'] ?: 'images/shop 1.jpg'))) ?>" alt="<?= e((string)$product['name']) ?>" /></div>
            <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;"><?= e((string)($product['short_description'] ?? 'BMW lifestyle merchandise.')) ?></p>
          </div>
          <div>
            <div class="price-buy-row"><span class="price">₹<?= number_format((float)($product['sale_price'] ?: $product['price']), 2) ?></span>
              <?php if ($inStock): ?>
                <button class="btn-buy-product" data-product="<?= e((string)$product['name']) ?>" data-price="<?= (float)($product['sale_price'] ?: $product['price']) ?>">Add to Cart</button>
              <?php else: ?>
                <span class="category-lbl" aria-label="Currently unavailable">Out of stock</span>
              <?php endif; ?>
            </div>
            <?php if (!$inStock): ?>
              <?php if (!empty($_SESSION['user_id'])): ?>
                <?php if (isset($subscribedProductIds[(int)$product['id']])): ?>
                  <span class="category-lbl stock-notify-state" role="status">Restock alert requested</span>
                <?php else: ?>
                  <form method="post" action="stock-notify.php" class="stock-notify-form">
                    <input type="hidden" name="csrf" value="<?= e($stockCsrf) ?>">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                    <button class="btn-buy-product" type="submit">Notify me when available</button>
                  </form>
                <?php endif; ?>
              <?php else: ?>
                <a class="btn-buy-product stock-notify-link" href="loginform.php?return_to=<?= rawurlencode('visit online shop.php?product=' . (string)$product['slug']) ?>">Sign in for stock alert</a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="product-card" data-category="bmw-m motorsport" data-name="BMW M Chronograph">
          <div>
            <span class="category-lbl">Chronos</span>
            <h3>BMW M Chronograph</h3>
            <div class="img-wrap"><img src="../images/shop 1.jpg" alt="BMW M Chronograph" /></div>
            <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">Premium brushed stainless steel with carbon fibers and automatic movement metrics.</p>
          </div>
          <div><div class="price-buy-row"><span class="price">$450</span><button class="btn-buy-product" data-product="BMW M Chronograph" data-price="450">Add to Cart</button></div></div>
        </div>
        <div class="product-card" data-category="travel lifestyle" data-name="BMW Boardcase Sport">
          <div>
            <span class="category-lbl">Travel</span>
            <h3>BMW Boardcase Sport</h3>
            <div class="img-wrap"><img src="../images/shop 1.jpg" alt="BMW Boardcase Sport" /></div>
            <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">Lightweight polycarbonate hard shell trolley with double-glide wheels under M branding.</p>
          </div>
          <div><div class="price-buy-row"><span class="price">$280</span><button class="btn-buy-product" data-product="BMW Boardcase Sport" data-price="280">Add to Cart</button></div></div>
        </div>
        <div class="product-card" data-category="clothing motorsport" data-name="M Leather Jacket">
          <div>
            <span class="category-lbl">Apparel</span>
            <h3>M Leather Jacket</h3>
            <div class="img-wrap"><img src="../images/shop 1.jpg" alt="M Leather Jacket" /></div>
            <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">Genuine lambskin leather with red M detailing, tailored motorsport collar styles.</p>
          </div>
          <div><div class="price-buy-row"><span class="price">$390</span><button class="btn-buy-product" data-product="M Leather Jacket" data-price="390">Add to Cart</button></div></div>
        </div>
        <div class="product-card" data-category="accessories lifestyle" data-name="Active Sunglasses">
          <div>
            <span class="category-lbl">Accessories</span>
            <h3>Active Sunglasses</h3>
            <div class="img-wrap"><img src="../images/shop 1.jpg" alt="Active Sunglasses" /></div>
            <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.4;">Polarized UV protection filters seated inside premium aluminum frames with temple details.</p>
          </div>
          <div><div class="price-buy-row"><span class="price">$140</span><button class="btn-buy-product" data-product="Active Sunglasses" data-price="140">Add to Cart</button></div></div>
        </div>
      </div>
      
    </div>
  </section>

  <?php endif; ?>

  <div id="shop-cart-toast" class="shop-cart-toast" role="status" aria-live="polite"></div>
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const cards = Array.from(document.querySelectorAll('#all-store-products .product-card'));
    const filters = Array.from(document.querySelectorAll('.shop-filter'));
    const search = document.getElementById('shop-search');
    const count = document.getElementById('shop-result-count');
    const toast = document.getElementById('shop-cart-toast');
    const heroSearch = document.getElementById('store-hero-search');
    const heroCategory = document.getElementById('store-hero-category');
    const heroQuery = document.getElementById('store-hero-query');
    let activeFilter = 'all';

    function showToast(message) {
      if (!toast) return;
      toast.textContent = message;
      toast.classList.add('is-visible');
      window.setTimeout(function () { toast.classList.remove('is-visible'); }, 2200);
    }
    function renderProducts() {
      const query = (search ? search.value : '').trim().toLowerCase();
      let visible = 0;
      cards.forEach(function (card) {
        const matchesFilter = activeFilter === 'all' || card.dataset.category === activeFilter;
        const matchesSearch = !query || (card.dataset.name || '').toLowerCase().includes(query);
        const shouldShow = matchesFilter && matchesSearch;
        card.hidden = !shouldShow;
        if (shouldShow) visible++;
      });
      if (count) count.textContent = 'Showing ' + visible + ' product' + (visible === 1 ? '' : 's');
    }
    function selectCategory(category) {
      activeFilter = category || 'all';
      filters.forEach(function (item) {
        item.classList.toggle('is-active', item.dataset.filter === activeFilter);
      });
      renderProducts();
    }
    filters.forEach(function (filter) {
      filter.addEventListener('click', function () {
        selectCategory(filter.dataset.filter);
      });
    });
    document.querySelectorAll('.category-shortcut').forEach(function (link) {
      link.addEventListener('click', function () { selectCategory(link.dataset.filter); });
    });
    if (search) search.addEventListener('input', renderProducts);
    if (heroSearch) {
      heroSearch.addEventListener('submit', function (event) {
        event.preventDefault();
        if (search && heroQuery) search.value = heroQuery.value;
        selectCategory(heroCategory ? heroCategory.value : 'all');
        document.getElementById('lifestyle-catalog').scrollIntoView({behavior: 'smooth'});
      });
    }
    document.querySelectorAll('.btn-buy-product[data-product]').forEach(function (button) {
      button.addEventListener('click', function () {
        const item = {id: Number(button.dataset.productId || 0), name: button.dataset.product, price: Number(button.dataset.price || 0)};
        let cart = [];
        try {
          const storedCart = JSON.parse(localStorage.getItem('beamers_cart') || '[]');
          if (Array.isArray(storedCart)) cart = storedCart;
        } catch (error) {
          localStorage.removeItem('beamers_cart');
        }
        cart.push(item);
        localStorage.setItem('beamers_cart', JSON.stringify(cart));
        showToast(item.name + ' added to your cart (' + cart.length + ' item' + (cart.length === 1 ? '' : 's') + ').');
      });
    });
    renderProducts();
    const requestedProduct = new URLSearchParams(window.location.search).get('product');
    if (requestedProduct) {
      const productCard = cards.find(function (card) { return card.dataset.slug === requestedProduct; });
      if (productCard) productCard.scrollIntoView({behavior: 'smooth', block: 'start'});
    }
  });
  </script>

  <!-- ==========================================================================
       GLOBAL PREMIUM DEEP FOOTER
       ========================================================================== */ -->
  <footer class="premium-footer">
    <div class="footer-top">
      <div class="footer-info">
        <h2>BEAMERS LEGACY</h2>
        <p>Authentic BMW dealership supplying the ultimate driving machines, precision maintenance, and custom accessories.</p>
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
          <li><a href="customer support.php">Customer Service</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h3>Department</h3>
        <ul>
          <?php if (!empty($_SESSION['user_id'])): ?>
            <li><a href="account.php">My Profile</a></li>
            <li><a href="logout.php">Sign Out</a></li>
          <?php else: ?>
            <li><a href="loginform.php">User Portal login</a></li>
            <li><a href="register.php">Create Account</a></li>
          <?php endif; ?>
          <li><a href="adminlogin.php">Staff & Admin Access</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; 2026 Beamers Legacy Dealership. Preserving Bavarian Precision. Built for scroll control.</p>
      <div class="footer-social-links">
        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
      </div>
    </div>
  </footer>

  <!-- ==========================================================================
       EXTERNAL ENGINE SCRIPTS
       ========================================================================== */ -->
  <!-- GSAP Core -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  
  <!-- Lenis Smooth scroll -->
  <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.36/dist/lenis.min.js"></script>
  
  <!-- Core script engines -->
  <script src="../js/premium.js"></script>

</body>

</html>
