<?php

require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect unauthenticated visitors away before they see anything
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Cast to int immediately — we use this directly in SQL queries below
$id = (int)$_GET['edit'];
// Remember which category tab the admin came from so we can return them there after saving
$return_cat = isset($_GET['cat']) ? $_GET['cat'] : 'all';

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

if (isset($_POST['update_product'])) {
    $product_name     = trim($_POST['product_name']);
    $product_price    = trim($_POST['product_price']);
    // Whitelist the category — anything unknown falls back to 'milktea'
    $product_category = isset($_POST['product_category']) && array_key_exists($_POST['product_category'], $categories)
                        ? $_POST['product_category'] : 'milktea';
    $new_image_original     = $_FILES['product_image']['name'];
    $product_image_tmp_name = $_FILES['product_image']['tmp_name'];

    if (empty($product_name) || empty($product_price)) {
        $message[] = 'Please fill out name and price!';
    } else {
        $esc_name  = mysqli_real_escape_string($conn, $product_name);
        $esc_price = mysqli_real_escape_string($conn, $product_price);
        $esc_cat   = mysqli_real_escape_string($conn, $product_category);

        if (!empty($new_image_original)) {
            // A new image was uploaded — generate a unique filename to avoid collisions and spaces
            $ext        = strtolower(pathinfo($new_image_original, PATHINFO_EXTENSION));
            $new_image  = 'item_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $img_folder = '../assets/uploads/' . $new_image;

            if (!move_uploaded_file($product_image_tmp_name, $img_folder)) {
                $message[] = 'Image upload failed. Check that assets/uploads/ is writable.';
            } else {
                $esc_img     = mysqli_real_escape_string($conn, $new_image);
                $update_data = "UPDATE products SET name='$esc_name', price='$esc_price', category='$esc_cat', image='$esc_img' WHERE id=$id";
                if (mysqli_query($conn, $update_data)) {
                    header('Location: menu.php?cat=' . urlencode($return_cat));
                    exit;
                } else {
                    @unlink($img_folder);
                    $message[] = 'Could not update the product!';
                }
            }
        } else {
            // No new image — leave the existing filename as-is
            $update_data = "UPDATE products SET name='$esc_name', price='$esc_price', category='$esc_cat' WHERE id=$id";
            if (mysqli_query($conn, $update_data)) {
                header('Location: menu.php?cat=' . urlencode($return_cat));
                exit;
            } else {
                $message[] = 'Could not update the product!';
            }
        }
    }
}

?>

<?php
$admin_page_title = 'Edit Menu Item';
$admin_active_nav = 'menu';
include '../includes/admin_header.php';
?>

<?php if (isset($message)): ?>
    <?php foreach ($message as $msg): ?>
        <div class="adm-alert adm-alert--error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
$select = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
$row = mysqli_fetch_assoc($select);
if ($row):
?>

<div style="max-width: 640px;">
    <div class="adm-card">
        <div class="adm-card__header">
            <div class="adm-card__header-left">
                <div class="adm-card__icon"><i class="fas fa-edit"></i></div>
                <div>
                    <div class="adm-card__title">Edit Menu Item</div>
                    <div class="adm-card__subtitle">Update the details below</div>
                </div>
            </div>
        </div>
        <div class="adm-card__body">
            <form action="" method="post" enctype="multipart/form-data">

                <div class="adm-add-form-grid">
                    <div class="adm-form-group">
                        <label class="adm-label">Item Name</label>
                        <input type="text" class="adm-input" name="product_name"
                               value="<?php echo htmlspecialchars($row['name']); ?>"
                               placeholder="Enter item name">
                    </div>

                    <div class="adm-form-group">
                        <label class="adm-label">Price ($)</label>
                        <input type="number" min="0" step="0.01" class="adm-input" name="product_price"
                               value="<?php echo htmlspecialchars($row['price']); ?>"
                               placeholder="Enter price">
                    </div>

                    <div class="adm-form-group adm-form-group--full">
                        <label class="adm-label">Category</label>
                        <select name="product_category" class="adm-input adm-select">
                            <?php foreach ($categories as $slug => $label): ?>
                            <option value="<?php echo $slug; ?>"
                                <?php echo (($row['category'] ?? '') === $slug) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="adm-form-group adm-form-group--full">
                        <label class="adm-label">Current Image</label>
                        <img src="../assets/uploads/<?php echo htmlspecialchars($row['image']); ?>"
                             style="display:block; width:130px; height:100px; object-fit:cover; border-radius:8px; border:1px solid var(--adm-border); margin-bottom:12px;">
                        <label class="adm-label">Replace Image <span class="adm-hint">Leave blank to keep current</span></label>
                        <input type="file" class="adm-file adm-input" name="product_image" accept="image/png, image/jpeg, image/jpg">
                    </div>
                </div>

                <hr class="adm-divider">

                <div style="display:flex; gap:12px;">
                    <button type="submit" name="update_product" class="adm-btn adm-btn--primary adm-btn--lg">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="menu.php?cat=<?php echo urlencode($return_cat); ?>" class="adm-btn adm-btn--ghost adm-btn--lg">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include '../includes/admin_footer.php'; ?>
