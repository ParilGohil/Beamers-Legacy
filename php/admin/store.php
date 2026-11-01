<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
admin_ensure_schema($conn);
admin_require_login();
$sidebarCounts = [];
foreach (['customers', 'test-drives', 'car-services', 'sales', 'feedback', 'cars'] as $sidebarEntity) {
    $sidebarCounts[$sidebarEntity] = admin_count($conn, $sidebarEntity);
}
$csrf = admin_csrf_token();
$message = '';
$storeTransactionStarted = false;
$shopSettings = admin_settings($conn);
$categoryEditing = null;

function store_slug(mysqli $conn, string $value, string $table, int $excludeId = 0): string
{
    if (!in_array($table, ['store_products', 'store_categories'], true)) {
        throw new InvalidArgumentException('The selected store slug type is invalid.');
    }
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '', '-'));
    if ($slug === '') {
        throw new InvalidArgumentException('Enter a name that can be used to create a URL slug.');
    }
    $candidate = $slug;
    $suffix = 2;
    while (true) {
        $sql = "SELECT `id` FROM `{$table}` WHERE `slug` = ?" . ($excludeId > 0 ? ' AND `id` <> ?' : '') . ' LIMIT 1';
        $check = $conn->prepare($sql);
        if ($excludeId > 0) {
            $check->bind_param('si', $candidate, $excludeId);
        } else {
            $check->bind_param('s', $candidate);
        }
        $check->execute();
        $exists = (bool)$check->get_result()->fetch_assoc();
        $check->close();
        if (!$exists) return $candidate;
        $candidate = $slug . '-' . $suffix++;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!admin_verify_csrf()) throw new RuntimeException('Your session token expired. Refresh and try again.');
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id < 1) throw new InvalidArgumentException('The selected category is invalid.');
            $conn->begin_transaction();
            $storeTransactionStarted = true;
            $categoryCleanup = $conn->prepare('UPDATE store_products SET category_id=NULL WHERE category_id=?');
            $categoryCleanup->bind_param('i', $id); $categoryCleanup->execute(); $categoryCleanup->close();
            $categoryCleanup = $conn->prepare('DELETE FROM store_categories WHERE id=?');
            $categoryCleanup->bind_param('i', $id); $categoryCleanup->execute(); $categoryCleanup->close();
            $conn->commit();
            $storeTransactionStarted = false;
            $message = 'Category deleted and its products moved to uncategorized.';
        } elseif ($action === 'delete_product') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id < 1) throw new InvalidArgumentException('The selected product is invalid.');
            $conn->begin_transaction();
            $storeTransactionStarted = true;
            $subscriptions = $conn->prepare('DELETE FROM stock_notifications WHERE product_id = ?');
            $subscriptions->bind_param('i', $id); $subscriptions->execute(); $subscriptions->close();
            $productNotifications = $conn->prepare("DELETE FROM notifications WHERE reference_type = 'product' AND reference_id = ?");
            $productNotifications->bind_param('i', $id); $productNotifications->execute(); $productNotifications->close();
            $productImages = $conn->prepare('DELETE FROM store_product_images WHERE product_id = ?');
            $productImages->bind_param('i', $id); $productImages->execute(); $productImages->close();
            $stmt = $conn->prepare('DELETE FROM store_products WHERE id = ?');
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $conn->commit();
            $storeTransactionStarted = false;
            $message = 'Product deleted successfully.';
        } elseif ($action === 'save_storefront') {
            $storefrontValues = [
                'shop_announcement_left' => trim((string)($_POST['shop_announcement_left'] ?? '')),
                'shop_announcement_right' => trim((string)($_POST['shop_announcement_right'] ?? '')),
                'shop_hero_eyebrow' => trim((string)($_POST['shop_hero_eyebrow'] ?? '')),
                'shop_hero_title' => trim((string)($_POST['shop_hero_title'] ?? '')),
                'shop_hero_copy' => trim((string)($_POST['shop_hero_copy'] ?? '')),
                'shop_hero_cta' => trim((string)($_POST['shop_hero_cta'] ?? '')),
            ];
            foreach ($storefrontValues as $key => $value) {
                if ($value === '' || mb_strlen($value) > 180) throw new InvalidArgumentException('All storefront text fields are required and must be 180 characters or fewer.');
            }
            $heroImage = (string)($shopSettings['shop_hero_image'] ?? 'images/m4bg.jpg');
            if (isset($_FILES['hero_image']) && ($_FILES['hero_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $heroImage = admin_store_upload($_FILES['hero_image']);
            }
            $storefrontValues['shop_hero_image'] = $heroImage;
            $saveSetting = $conn->prepare('INSERT INTO dealer_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
            foreach ($storefrontValues as $key => $value) {
                $saveSetting->bind_param('ss', $key, $value);
                $saveSetting->execute();
                $shopSettings[$key] = $value;
            }
            $saveSetting->close();
            $message = 'Storefront appearance saved successfully.';
        } elseif ($action === 'save_category') {
            $categoryId = (int)($_POST['id'] ?? 0);
            $categoryName = trim((string)($_POST['name'] ?? ''));
            $categorySlugInput = trim((string)($_POST['slug'] ?? ''));
            $categoryDescription = trim((string)($_POST['description'] ?? ''));
            $categoryStatus = (string)($_POST['status'] ?? 'active');
            $sortOrder = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT);
            if ($categoryName === '' || mb_strlen($categoryName) > 120 || mb_strlen($categoryDescription) > 2000 || !in_array($categoryStatus, ['active','inactive'], true) || $sortOrder === false || $sortOrder < 0) {
                throw new InvalidArgumentException('Enter a valid category name, status, and non-negative display order.');
            }
            $categorySlug = store_slug($conn, $categorySlugInput !== '' ? $categorySlugInput : $categoryName, 'store_categories', $categoryId);
            $categoryImage = '';
            if (isset($_FILES['category_image']) && ($_FILES['category_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $categoryImage = admin_store_upload($_FILES['category_image']);
            }
            if ($categoryId > 0) {
                $categoryExists = $conn->prepare('SELECT id FROM store_categories WHERE id=? LIMIT 1');
                $categoryExists->bind_param('i', $categoryId);
                $categoryExists->execute();
                $exists = (bool)$categoryExists->get_result()->fetch_assoc();
                $categoryExists->close();
                if (!$exists) throw new RuntimeException('The selected category no longer exists.');
                if ($categoryImage === '') {
                    $existingCategory = $conn->prepare('SELECT image FROM store_categories WHERE id=?');
                    $existingCategory->bind_param('i', $categoryId);
                    $existingCategory->execute();
                    $categoryImage = (string)($existingCategory->get_result()->fetch_assoc()['image'] ?? '');
                    $existingCategory->close();
                }
                $saveCategory = $conn->prepare('UPDATE store_categories SET name=?,slug=?,description=?,image=?,status=?,sort_order=? WHERE id=?');
                $saveCategory->bind_param('sssssii', $categoryName, $categorySlug, $categoryDescription, $categoryImage, $categoryStatus, $sortOrder, $categoryId);
            } else {
                $saveCategory = $conn->prepare('INSERT INTO store_categories (name,slug,description,image,status,sort_order) VALUES (?,?,?,?,?,?)');
                $saveCategory->bind_param('sssssi', $categoryName, $categorySlug, $categoryDescription, $categoryImage, $categoryStatus, $sortOrder);
            }
            $saveCategory->execute();
            $saveCategory->close();
            $message = 'Store category saved successfully.';
        } elseif ($action === 'save') {
            $name = trim((string)($_POST['name'] ?? ''));
            $sku = trim((string)($_POST['sku'] ?? ''));
            $slug = trim((string)($_POST['slug'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            $short = trim((string)($_POST['short_description'] ?? ''));
            $priceInput = $_POST['price'] ?? null;
            $saleInput = $_POST['sale_price'] ?? '';
            $stockInput = $_POST['stock'] ?? null;
            $thresholdInput = $_POST['low_stock_threshold'] ?? 5;
            if (!is_numeric($priceInput) || !is_numeric($stockInput) || !is_numeric($thresholdInput) || ($saleInput !== '' && !is_numeric($saleInput))) {
                throw new InvalidArgumentException('Enter numeric price, sale price, and inventory values.');
            }
            $price = (float)$priceInput;
            $sale = $saleInput === '' ? null : (float)$saleInput;
            $stock = filter_var($stockInput, FILTER_VALIDATE_INT);
            $threshold = filter_var($thresholdInput, FILTER_VALIDATE_INT);
            $status = (string)($_POST['status'] ?? 'draft');
            $badge = trim((string)($_POST['badge'] ?? ''));
            $currency = strtoupper(trim((string)($_POST['currency'] ?? 'INR')));
            $featured = isset($_POST['featured']) ? 1 : 0;
            $image = '';
            if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $image = admin_store_upload($_FILES['image']);
            }
            $categoryInput = $_POST['category_id'] ?? '';
            $categoryId = $categoryInput === '' ? null : filter_var($categoryInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($categoryInput !== '' && $categoryId === false) throw new InvalidArgumentException('Choose a valid product category.');
            if ($categoryId !== null) {
                $categoryExists = $conn->prepare('SELECT id FROM store_categories WHERE id=? LIMIT 1');
                $categoryExists->bind_param('i', $categoryId);
                $categoryExists->execute();
                $exists = (bool)$categoryExists->get_result()->fetch_assoc();
                $categoryExists->close();
                if (!$exists) throw new InvalidArgumentException('The selected category does not exist.');
            }
            if ($name === '') throw new InvalidArgumentException('Product name is required.');
            if (!in_array($status, ['active','draft','out_of_stock','archived'], true)) throw new InvalidArgumentException('Invalid product status.');
            if (!preg_match('/^[A-Z]{3}$/', $currency) || $price < 0 || ($sale !== null && ($sale < 0 || $sale > $price)) || $stock === false || $threshold === false || $stock < 0 || $threshold < 0) {
                throw new InvalidArgumentException('Check the currency, price, sale price, and stock values.');
            }
            $id = (int)($_POST['id'] ?? 0);
            $slug = store_slug($conn, $slug !== '' ? $slug : $name, 'store_products', $id);
            if ($id > 0) {
                $conn->begin_transaction();
                $storeTransactionStarted = true;
                $previous = $conn->prepare('SELECT image,stock,status FROM store_products WHERE id=?');
                $previous->bind_param('i', $id);
                $previous->execute();
                $previousProduct = $previous->get_result()->fetch_assoc();
                $previous->close();
                if (!$previousProduct) throw new RuntimeException('The product could not be found.');
                $wasAvailable = (int)$previousProduct['stock'] > 0 && $previousProduct['status'] === 'active';
                if ($image === '') {
                    $image = (string)$previousProduct['image'];
                }
                $stmt = $conn->prepare('UPDATE store_products SET name=?, sku=NULLIF(?, ""), slug=?, category_id=?, description=?, short_description=?, price=?, sale_price=?, currency=?, stock=?, low_stock_threshold=?, status=?, badge=?, image=?, featured=?, updated_by=? WHERE id=?');
                $adminId = (int)$_SESSION['admin_user_id'];
                $stmt->bind_param('sssissddsiisssiii', $name, $sku, $slug, $categoryId, $description, $short, $price, $sale, $currency, $stock, $threshold, $status, $badge, $image, $featured, $adminId, $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO store_products (name,sku,slug,category_id,description,short_description,price,sale_price,currency,stock,low_stock_threshold,status,badge,image,featured,created_by,updated_by) VALUES (?,NULLIF(?, ""),?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $adminId = (int)$_SESSION['admin_user_id'];
                $stmt->bind_param('sssissddsiisssiii', $name, $sku, $slug, $categoryId, $description, $short, $price, $sale, $currency, $stock, $threshold, $status, $badge, $image, $featured, $adminId, $adminId);
            }
            $stmt->execute(); $stmt->close();
            if ($id > 0) {
                app_notify_product_stock_change($conn, $id, $wasAvailable, $status === 'active' && $stock > 0, $name);
                $conn->commit();
                $storeTransactionStarted = false;
            }
            $message = 'Product saved successfully.';
        }
    } catch (Throwable $error) {
        if ($storeTransactionStarted) {
            $conn->rollback();
            $storeTransactionStarted = false;
        }
        $message = $error->getMessage();
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare('SELECT * FROM store_products WHERE id = ?');
    $id = (int)$_GET['edit']; $stmt->bind_param('i', $id); $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc() ?: null; $stmt->close();
}
$categories = $conn->query('SELECT id,name,slug FROM store_categories ORDER BY sort_order,name')->fetch_all(MYSQLI_ASSOC);
$allCategories = $conn->query('SELECT c.*,(SELECT COUNT(*) FROM store_products p WHERE p.category_id=c.id) AS product_count FROM store_categories c ORDER BY c.sort_order,c.name')->fetch_all(MYSQLI_ASSOC);
if (isset($_GET['edit_category'])) {
    $categoryId = (int)$_GET['edit_category'];
    $categoryLookup = $conn->prepare('SELECT * FROM store_categories WHERE id=? LIMIT 1');
    $categoryLookup->bind_param('i', $categoryId);
    $categoryLookup->execute();
    $categoryEditing = $categoryLookup->get_result()->fetch_assoc() ?: null;
    $categoryLookup->close();
}
$products = $conn->query('SELECT p.*, c.name category_name FROM store_products p LEFT JOIN store_categories c ON c.id=p.category_id ORDER BY p.created_at DESC')->fetch_all(MYSQLI_ASSOC);
$stats = $conn->query("SELECT COUNT(*) total, SUM(status='active') active, SUM(stock=0 OR status='out_of_stock') out_stock, SUM(stock>0 AND stock<=low_stock_threshold) low_stock FROM store_products")->fetch_assoc();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>BMW Store | Admin</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"><link rel="stylesheet" href="../../css/admin.css?v=20260926-notifications-1"></head>
<body><div class="admin-shell">
<aside class="sidebar" id="sidebar"><a class="brand" href="admin_dashboard.php"><img src="../../images/BMW logo.svg" alt="BMW logo"><span><strong>BEAMERS</strong><small>LEGACY / ADMIN</small></span></a><nav class="sidebar-nav" aria-label="Dashboard sections"><a href="admin_dashboard.php"><i class="fa-solid fa-grid-2"></i>Overview</a><a href="admin_dashboard.php?section=customers"><i class="fa-solid fa-users"></i>Customers <b><?= $sidebarCounts['customers'] ?></b></a><a href="admin_dashboard.php?section=test-drives"><i class="fa-solid fa-car-side"></i>Test drives <b><?= $sidebarCounts['test-drives'] ?></b></a><a href="admin_dashboard.php?section=car-services"><i class="fa-solid fa-screwdriver-wrench"></i>Car service <b><?= $sidebarCounts['car-services'] ?></b></a><a href="admin_dashboard.php?section=sales"><i class="fa-solid fa-handshake"></i>Sales bookings <b><?= $sidebarCounts['sales'] ?></b></a><a href="admin_dashboard.php?section=feedback"><i class="fa-solid fa-message"></i>Feedback <b><?= $sidebarCounts['feedback'] ?></b></a><a href="admin_dashboard.php?section=cars"><i class="fa-solid fa-car"></i>BMW Cars <b><?= $sidebarCounts['cars'] ?></b></a><a href="notifications.php"><i class="fa-regular fa-bell"></i>Notifications</a><a class="active" href="store.php"><i class="fa-solid fa-bag-shopping"></i>BMW Store</a></nav><div class="sidebar-footer"><a href="../home.php"><i class="fa-solid fa-arrow-up-right-from-square"></i>View website</a><a href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a><a href="logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i>Sign out</a></div></aside>
<main class="main-content"><header class="topbar"><button class="menu-toggle" type="button" aria-label="Open navigation" aria-controls="sidebar"><i class="fa-solid fa-bars"></i></button><div class="breadcrumb"><span>Control centre</span><i class="fa-solid fa-chevron-right"></i><strong>BMW Store</strong></div><div class="admin-topbar-actions"><?php require __DIR__ . '/notification_widget.php'; ?><div class="admin-profile"><span class="profile-mark"><?= admin_escape(strtoupper(substr((string) $_SESSION['admin_name'], 0, 1))) ?></span><span><strong><?= admin_escape((string) $_SESSION['admin_name']) ?></strong><small>Administrator</small></span></div></div></header><section class="page-content">
<div class="management-heading"><div><p class="eyebrow">BMW MERCHANDISE</p><h1>BMW Store</h1><p>Manage BMW merchandise, products and inventory.</p></div><a class="primary-button" href="#product-form"><i class="fa-solid fa-plus"></i>Add Product</a></div>
<?php if ($message !== ''): ?><div class="notice <?= str_contains($message, 'successfully') ? 'success' : 'error' ?>"><?= admin_escape($message) ?></div><?php endif; ?>
<div class="stats-grid"><div class="stat-card blue"><span class="stat-icon"><i class="fa-solid fa-box"></i></span><span><small>TOTAL PRODUCTS</small><strong><?= (int)($stats['total'] ?? 0) ?></strong></span></div><div class="stat-card blue"><span class="stat-icon"><i class="fa-solid fa-circle-check"></i></span><span><small>ACTIVE PRODUCTS</small><strong><?= (int)($stats['active'] ?? 0) ?></strong></span></div><div class="stat-card red"><span class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></span><span><small>LOW / OUT OF STOCK</small><strong><?= (int)($stats['low_stock'] ?? 0) + (int)($stats['out_stock'] ?? 0) ?></strong></span></div></div>
<details class="record-editor"><summary><span><i class="fa-solid fa-panorama"></i>Storefront appearance</span><i class="fa-solid fa-chevron-down"></i></summary>
<form method="post" enctype="multipart/form-data" class="data-form"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="save_storefront"><div class="form-grid">
<div class="field"><label for="shop-announcement-left">Announcement bar — left</label><input id="shop-announcement-left" name="shop_announcement_left" maxlength="180" value="<?= admin_escape($shopSettings['shop_announcement_left'] ?? 'BMW lifestyle and accessories') ?>" required></div>
<div class="field"><label for="shop-announcement-right">Announcement bar — right</label><input id="shop-announcement-right" name="shop_announcement_right" maxlength="180" value="<?= admin_escape($shopSettings['shop_announcement_right'] ?? 'Curated for BMW owners') ?>" required></div>
<div class="field"><label for="shop-hero-eyebrow">Hero eyebrow</label><input id="shop-hero-eyebrow" name="shop_hero_eyebrow" maxlength="180" value="<?= admin_escape($shopSettings['shop_hero_eyebrow'] ?? 'BMW ORIGINAL ACCESSORIES') ?>" required></div>
<div class="field"><label for="shop-hero-title">Hero heading</label><input id="shop-hero-title" name="shop_hero_title" maxlength="180" value="<?= admin_escape($shopSettings['shop_hero_title'] ?? 'MAKE EVERY DRIVE YOUR OWN.') ?>" required></div>
<div class="field field-textarea"><label for="shop-hero-copy">Hero description</label><textarea id="shop-hero-copy" name="shop_hero_copy" maxlength="180" rows="3" required><?= admin_escape($shopSettings['shop_hero_copy'] ?? 'Discover genuine BMW accessories and lifestyle essentials, selected for the way you drive.') ?></textarea></div>
<div class="field"><label for="shop-hero-cta">Hero button label</label><input id="shop-hero-cta" name="shop_hero_cta" maxlength="180" value="<?= admin_escape($shopSettings['shop_hero_cta'] ?? 'Shop now') ?>" required></div>
<div class="field"><label for="shop-hero-image">Hero image</label><input id="shop-hero-image" name="hero_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif"><small>Optional JPG, PNG, WEBP or GIF image, up to 5 MB. Leave empty to keep the current image.</small></div>
</div><div class="form-actions"><button class="primary-button" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save storefront</button><a class="secondary-button" href="../visit online shop.php" target="_blank" rel="noopener">Preview shop</a></div></form></details>
<details class="record-editor" <?= $categoryEditing ? 'open' : '' ?>><summary><span><i class="fa-solid fa-layer-group"></i><?= $categoryEditing ? 'Edit shop category' : 'Shop categories' ?></span><i class="fa-solid fa-chevron-down"></i></summary>
<form method="post" enctype="multipart/form-data" class="data-form"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="save_category"><input type="hidden" name="id" value="<?= (int)($categoryEditing['id'] ?? 0) ?>"><div class="form-grid">
<div class="field"><label for="category-name">Category name</label><input id="category-name" name="name" value="<?= admin_escape((string)($categoryEditing['name'] ?? '')) ?>" required maxlength="120"></div>
<div class="field"><label for="category-slug">Category slug</label><input id="category-slug" name="slug" value="<?= admin_escape((string)($categoryEditing['slug'] ?? '')) ?>" placeholder="Generated from category name"></div>
<div class="field"><label for="category-order">Display order</label><input id="category-order" name="sort_order" type="number" min="0" value="<?= (int)($categoryEditing['sort_order'] ?? 0) ?>"></div>
<div class="field"><label for="category-status">Visibility</label><select id="category-status" name="status"><option value="active" <?= ($categoryEditing['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Visible</option><option value="inactive" <?= ($categoryEditing['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Hidden</option></select></div>
<div class="field field-textarea"><label for="category-description">Description</label><textarea id="category-description" name="description" rows="2"><?= admin_escape((string)($categoryEditing['description'] ?? '')) ?></textarea></div>
<div class="field"><label for="category-image">Category image</label><input id="category-image" name="category_image" type="file" accept="image/jpeg,image/png,image/webp,image/gif"><small>Optional image for category tiles. Maximum 5 MB.</small></div>
</div><div class="form-actions"><button class="primary-button" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save category</button><?php if ($categoryEditing): ?><a class="secondary-button" href="store.php">Cancel edit</a><?php endif; ?></div></form>
<div class="records-table-wrap"><table class="records-table"><thead><tr><th>Category</th><th>Products</th><th>Display order</th><th>Visibility</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($allCategories as $category): ?><tr><td><strong><?= admin_escape((string)$category['name']) ?></strong><small><?= admin_escape((string)$category['slug']) ?></small></td><td><?= (int)$category['product_count'] ?></td><td><?= (int)$category['sort_order'] ?></td><td><span class="status <?= $category['status'] === 'active' ? 'yes' : 'no' ?>"><?= admin_escape(ucfirst((string)$category['status'])) ?></span></td><td class="row-actions"><a class="icon-button" href="store.php?edit_category=<?= (int)$category['id'] ?>#category-name" aria-label="Edit category"><i class="fa-solid fa-pen"></i></a><form method="post" onsubmit="return confirm('Delete this category? Its products will remain in the store without a category.');"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$category['id'] ?>"><button class="icon-button danger" type="submit" aria-label="Delete category"><i class="fa-solid fa-trash-can"></i></button></form></td></tr><?php endforeach; ?>
</tbody></table></div></details>
<details class="record-editor" id="product-form" <?= $editing ? 'open' : '' ?>><summary><span><i class="fa-solid fa-circle-plus"></i><?= $editing ? 'Edit product' : 'New product' ?></span><i class="fa-solid fa-chevron-down"></i></summary><form method="post" enctype="multipart/form-data" class="data-form"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>"><div class="form-grid">
<?php foreach ([['name','Product Name','text'],['sku','Product SKU','text'],['slug','Product Slug','text'],['short_description','Short Description','text'],['price','Regular Price','number'],['sale_price','Sale Price','number'],['stock','Stock Quantity','number'],['low_stock_threshold','Low Stock Threshold','number'],['badge','Product Badge','text']] as [$key,$label,$type]): ?><div class="field"><label for="product-<?= $key ?>"><?= $label ?></label><input id="product-<?= $key ?>" name="<?= $key ?>" type="<?= $type ?>" value="<?= admin_escape((string)($editing[$key] ?? '')) ?>" <?= $key === 'name' ? 'required' : '' ?> <?= in_array($key,['price','sale_price'],true) ? 'min="0" step="0.01"' : '' ?> <?= in_array($key,['stock','low_stock_threshold'],true) ? 'min="0" step="1"' : '' ?>></div><?php endforeach; ?>
<div class="field"><label for="product-currency">Currency</label><select id="product-currency" name="currency"><option value="INR" <?= ($editing['currency'] ?? 'INR') === 'INR' ? 'selected' : '' ?>>INR — ₹</option><option value="USD" <?= ($editing['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD — $</option></select></div>
<div class="field"><label for="product-image">Product Image</label><input id="product-image" name="image" type="file" accept="image/jpeg,image/png,image/webp,image/gif"><small>Choose a JPG, PNG, WEBP or GIF image. Maximum 5 MB.</small></div>
<div class="field"><label for="product-category">Category</label><select id="product-category" name="category_id"><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (int)($editing['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>><?= admin_escape($category['name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="product-status">Store visibility</label><select id="product-status" name="status"><?php foreach (['active','draft','out_of_stock','archived'] as $status): ?><option value="<?= $status ?>" <?= ($editing['status'] ?? 'draft') === $status ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$status)) ?></option><?php endforeach; ?></select></div>
<div class="field field-textarea"><label for="product-description">Description</label><textarea id="product-description" name="description" rows="4"><?= admin_escape((string)($editing['description'] ?? '')) ?></textarea></div>
<div class="field"><label><input type="checkbox" name="featured" value="1" <?= !empty($editing['featured']) ? 'checked' : '' ?>> Feature on storefront</label><small>Featured items are displayed first and receive a featured badge.</small></div>
</div><div class="form-actions"><button class="primary-button" type="submit"><i class="fa-solid fa-floppy-disk"></i>Save product</button></div></form></details>
<section class="panel records-panel"><div class="records-toolbar"><div><strong><?= count($products) ?> products</strong><span>BMW merchandise catalogue</span></div></div><div class="records-table-wrap"><table class="records-table"><thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Storefront</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead><tbody><?php foreach ($products as $product): ?><tr><td><strong><?= admin_escape($product['name']) ?></strong><small><?= admin_escape((string)$product['sku']) ?></small></td><td><?= admin_escape((string)($product['category_name'] ?? '—')) ?></td><td><?= admin_escape((string)$product['currency']) ?> <?= number_format((float)$product['price'], 2) ?><?php if ((float)($product['sale_price'] ?? 0) > 0): ?><small>Sale: <?= number_format((float)$product['sale_price'], 2) ?></small><?php endif; ?></td><td><?= (int)$product['stock'] ?></td><td><?= !empty($product['featured']) ? '<span class="status yes">Featured</span>' : 'Standard' ?></td><td><span class="status <?= $product['status'] === 'active' ? 'yes' : 'no' ?>"><?= admin_escape(ucwords(str_replace('_',' ',$product['status']))) ?></span></td><td><?= admin_escape(date('d M Y', strtotime($product['created_at']))) ?></td><td class="row-actions"><a class="icon-button" href="store.php?edit=<?= (int)$product['id'] ?>#product-form" aria-label="Edit product"><i class="fa-solid fa-pen"></i></a><form method="post" onsubmit="return confirm('Delete this product?');"><input type="hidden" name="csrf" value="<?= admin_escape($csrf) ?>"><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><button class="icon-button danger" type="submit" aria-label="Delete product"><i class="fa-solid fa-trash-can"></i></button></form></td></tr><?php endforeach; ?></tbody></table></div><?php if (!$products): ?><div class="empty-state"><i class="fa-solid fa-box-open"></i><h3>No products yet</h3><p>Add the first BMW merchandise product.</p></div><?php endif; ?></section>
</section></main></div><script src="../../js/admin.js"></script><script src="../../js/notifications.js?v=20260926-1" defer></script></body></html>
