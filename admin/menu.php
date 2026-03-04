<?php

require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gate — unauthenticated requests bounce straight to login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Add the category column if the products table was created before it was introduced
mysqli_query($conn, "ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(50) NOT NULL DEFAULT 'milktea'");

// Canonical list of categories — keep this in sync with public/menu.php
$categories = [
    'top10'    => 'Top 10',
    'brewed'   => 'Brewed Tea',
    'creative' => 'Creative Mix',
    'health'   => 'Health Tea',
    'milkfoam' => 'Milk Foam',
    'milktea'  => 'Milk Tea',
    'smoothie' => 'Smoothie',
    'yoghurt'  => 'Yoghurt',
    'toppings' => 'Toppings',
];

// Handle the "Add Item" form submission
if (isset($_POST['add_product'])) {
    $product_name     = trim($_POST['product_name']);
    $product_price    = trim($_POST['product_price']);
    // Fall back to 'milktea' if someone submits a category slug we don't recognise
    $product_category = isset($_POST['product_category']) && array_key_exists($_POST['product_category'], $categories)
                        ? $_POST['product_category'] : 'milktea';
    $product_image_tmp_name = $_FILES['product_image']['tmp_name'];
    $product_image_original = $_FILES['product_image']['name'];
    $product_image_ext      = strtolower(pathinfo($product_image_original, PATHINFO_EXTENSION));
    // Generate a unique filename to avoid collisions and spaces in URLs
    $product_image          = 'item_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $product_image_ext;
    $product_image_folder   = '../assets/uploads/' . $product_image;

    if (empty($product_name) || empty($product_price) || empty($product_image_original)) {
        $message[] = 'Please fill out all fields including an image.';
    } else {
        $esc_name  = mysqli_real_escape_string($conn, $product_name);
        $esc_price = mysqli_real_escape_string($conn, $product_price);
        $esc_cat   = mysqli_real_escape_string($conn, $product_category);
        $esc_img   = mysqli_real_escape_string($conn, $product_image);
        if (!move_uploaded_file($product_image_tmp_name, $product_image_folder)) {
            $message[] = 'Image upload failed. Check that assets/uploads/ is writable.';
        } else {
            $insert = "INSERT INTO products(name, price, image, category) VALUES('$esc_name', '$esc_price', '$esc_img', '$esc_cat')";
            if (mysqli_query($conn, $insert)) {
                $message[] = 'New product added successfully!';
            } else {
                // Remove the uploaded file if the DB insert fails
                @unlink($product_image_folder);
                $message[] = 'Could not add the product. Please try again.';
            }
        }
    }
}

// Handle a delete request — cast to int so there's no SQL injection risk
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM products WHERE id = $del_id");
    // Stay on the same category tab after the redirect
    $redirect_cat = isset($_GET['cat']) ? '?cat=' . urlencode($_GET['cat']) : '';
    header('Location: menu.php' . $redirect_cat);
    exit;
}

// Validate the cat param — reject anything that isn't a known slug or 'all'
$active_cat = isset($_GET['cat']) && ($_GET['cat'] === 'all' || array_key_exists($_GET['cat'], $categories))
              ? $_GET['cat'] : 'all';

// Grab the overall item count for the stats card
$count_result   = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
$total_products = $count_result ? mysqli_fetch_assoc($count_result)['total'] : 0;

// Per-category counts power the badge numbers on each tab
$cat_counts = [];
$cr = mysqli_query($conn, "SELECT category, COUNT(*) as cnt FROM products GROUP BY category");
if ($cr) {
    while ($r = mysqli_fetch_assoc($cr)) {
        $cat_counts[$r['category']] = (int)$r['cnt'];
    }
}

?>

<?php
$admin_page_title = 'Menu';
$admin_active_nav = 'menu';
include '../includes/admin_header.php';
?>

<?php if (isset($message)): ?>
    <?php foreach ($message as $msg): ?>
        <div class="adm-alert adm-alert--<?php echo strpos($msg, 'successfully') !== false ? 'success' : 'error'; ?>">
            <i class="fas fa-<?php echo strpos($msg, 'successfully') !== false ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Stats -->
<div class="adm-dash-stats">
    <div class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--brown">
            <i class="fas fa-mug-hot"></i>
        </div>
        <div>
            <div class="adm-stat-card__label">Total Menu Items</div>
            <div class="adm-stat-card__value"><?php echo $total_products; ?></div>
        </div>
    </div>
    <div class="adm-stat-card">
        <div class="adm-stat-card__icon adm-stat-card__icon--green">
            <i class="fas fa-tags"></i>
        </div>
        <div>
            <div class="adm-stat-card__label">Categories</div>
            <div class="adm-stat-card__value"><?php echo count($cat_counts); ?></div>
        </div>
    </div>
</div>

<!-- Add product form -->
<div class="adm-card" style="margin-bottom: 28px;">
    <div class="adm-card__header">
        <div class="adm-card__header-left">
            <div class="adm-card__icon"><i class="fas fa-plus"></i></div>
            <div>
                <div class="adm-card__title">Add New Menu Item</div>
                <div class="adm-card__subtitle">Fill in the details and choose a category</div>
            </div>
        </div>
    </div>
    <div class="adm-card__body">
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
            <div class="adm-add-form-grid">
                <div class="adm-form-group">
                    <label class="adm-label">Item Name</label>
                    <input type="text" name="product_name" class="adm-input" placeholder="e.g. Brown Sugar Milk Tea">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Price ($)</label>
                    <input type="number" name="product_price" class="adm-input" placeholder="e.g. 7.50" step="0.01" min="0">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Category</label>
                    <select name="product_category" class="adm-input adm-select">
                        <?php foreach ($categories as $slug => $label): ?>
                        <option value="<?php echo $slug; ?>"
                            <?php echo ($active_cat !== 'all' && $active_cat === $slug) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Image <span class="adm-hint">PNG, JPG</span></label>
                    <input type="file" name="product_image" class="adm-file adm-input" accept="image/png, image/jpeg, image/jpg">
                </div>
            </div>
            <button type="submit" name="add_product" class="adm-btn adm-btn--primary adm-btn--lg" style="margin-top:8px;">
                <i class="fas fa-plus"></i> Add Item
            </button>
        </form>
    </div>
</div>

<!-- Category filter tabs + table -->
<div class="adm-card">
    <div class="adm-card__header">
        <div class="adm-card__header-left">
            <div class="adm-card__icon"><i class="fas fa-list"></i></div>
            <div>
                <div class="adm-card__title">Menu Items</div>
                <div class="adm-card__subtitle">
                    <?php if ($active_cat === 'all'): ?>
                        Showing all <?php echo $total_products; ?> items
                    <?php else: ?>
                        Showing: <strong><?php echo htmlspecialchars($categories[$active_cat]); ?></strong>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Category tabs -->
    <div class="adm-cat-tabs">
        <a href="menu.php?cat=all"
           class="adm-cat-tab <?php echo $active_cat === 'all' ? 'adm-cat-tab--active' : ''; ?>">
            All
            <span class="adm-cat-tab__count"><?php echo $total_products; ?></span>
        </a>
        <?php foreach ($categories as $slug => $label): ?>
        <a href="menu.php?cat=<?php echo $slug; ?>"
           class="adm-cat-tab <?php echo $active_cat === $slug ? 'adm-cat-tab--active' : ''; ?>">
            <?php echo htmlspecialchars($label); ?>
            <?php if (isset($cat_counts[$slug])): ?>
            <span class="adm-cat-tab__count"><?php echo $cat_counts[$slug]; ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="adm-table-wrap">
        <?php
        if ($active_cat === 'all') {
            $select = mysqli_query($conn, "SELECT * FROM products ORDER BY category, name");
        } else {
            $safe_cat = mysqli_real_escape_string($conn, $active_cat);
            $select   = mysqli_query($conn, "SELECT * FROM products WHERE category='$safe_cat' ORDER BY name");
        }
        ?>
        <?php if ($select && mysqli_num_rows($select) > 0): ?>
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($select)): ?>
                <tr>
                    <td><img src="../assets/uploads/<?php echo htmlspecialchars($row['image']); ?>" class="adm-table-img" alt=""></td>
                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                    <td>
                        <span class="adm-badge adm-badge--cat">
                            <?php echo htmlspecialchars($categories[$row['category']] ?? ucfirst($row['category'])); ?>
                        </span>
                    </td>
                    <td><span class="adm-badge adm-badge--brown">$<?php echo htmlspecialchars($row['price']); ?></span></td>
                    <td>
                        <div class="adm-table-actions">
                            <a href="menu-edit.php?edit=<?php echo $row['id']; ?>&cat=<?php echo urlencode($active_cat); ?>"
                               class="adm-btn adm-btn--ghost">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="menu.php?delete=<?php echo $row['id']; ?>&cat=<?php echo urlencode($active_cat); ?>"
                               class="adm-btn adm-btn--danger"
                               onclick="return confirm('Delete this item?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="padding: 48px; text-align: center; color: var(--adm-text-muted);">
            <i class="fas fa-mug-hot" style="font-size: 40px; margin-bottom: 14px; display: block; opacity: .3;"></i>
            No items in this category yet.<br>Add one using the form above.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
