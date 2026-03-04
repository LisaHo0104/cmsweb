<?php

require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only admins get in — everyone else goes back to the login page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Make sure the storage table exists before we try to read or write anything
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS about_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_key VARCHAR(100) NOT NULL UNIQUE,
    field_value TEXT NOT NULL
)");

// Hard-coded defaults — these get seeded into the DB on first run so the page
// always has something sensible to display out of the box
$defaults = [
    'story_title'        => "Australia's Number 1 Bubble Tea Brand",
    'story_para1'        => 'As Australia\'s largest bubble tea chain by store count, Gong cha Australia <strong>operates over 170 stores nationwide</strong>—more locations than any other bubble tea brand in the country. This unmatched store number reflects our rapid growth since entering the market in 2012 and underscores the trust Australians place in our premium, freshly brewed teas. Across the country, our stores serve millions of customers each year, making Gong cha a household name in Australian bubble tea culture.',
    'story_para2'        => 'Gong cha was founded in Kaohsiung, Taiwan in 2006 with a simple mission — to serve the finest quality tea to every customer, every time. Today, Gong cha has grown to over 2,000 stores across more than 25 countries.',
    'story_image'        => '../assets/images/Gong-Cha-Day-2-0707-RGB.png',
    'story_image_alt'    => 'Gong cha drinks',
    'section2_title'     => 'Our Footprint in Numbers',
    'section2_text'      => 'Across these locations—from bustling metropolitan outlets to inviting regional cafés—we serve <strong>more than 15 million drinks every year</strong>, delighting customers with freshly brewed teas and customisable creations day in, day out.',
    'section2_image'     => '../assets/images/Gong-Cha-Day-2-0756-RGB.png',
    'section2_image_alt' => 'Gong cha store',
    'section3_title'     => 'Our Journey In Australia',
    'section3_text'      => 'Since entering the Australian market in 2012, Gong Cha has taken the country by storm—expanding rapidly from our first Sydney and Melbourne outlets to nationwide. You\'ll now find Gong Cha in bustling city centres, suburban precincts, transit hubs, and even regional towns.',
    'section3_image'     => '../assets/images/Gong-Cha-Day-2-0060-RGB.png',
    'section3_image_alt' => 'Gong cha journey',
];

// Insert any missing keys with their defaults so the DB is always fully populated
foreach ($defaults as $key => $value) {
    $esc_key = mysqli_real_escape_string($conn, $key);
    $esc_val = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT IGNORE INTO about_content (field_key, field_value) VALUES ('$esc_key', '$esc_val')");
}

$messages = [];

// Handles a single file-input upload. Returns the new relative path on success,
// an "error:..." string on failure, or null if no file was submitted.
function handle_image_upload($file_key) {
    if (empty($_FILES[$file_key]['name'])) return null;

    $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif', 'image/svg+xml'];
    $mime = $_FILES[$file_key]['type'];
    if (!in_array($mime, $allowed)) return 'error:Invalid image type.';

    $ext      = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
    $filename = $file_key . '_' . time() . '.' . $ext;
    $dest     = '../assets/uploads/' . $filename;

    if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $dest)) {
        // Store a path relative to the web root so about.php can build the URL
        return 'assets/uploads/' . $filename;
    }

    return 'error:Failed to upload image. Check folder permissions.';
}

if (isset($_POST['save_about'])) {
    $upload_errors = [];

    // Try to move any newly uploaded images first; collect errors before touching the DB
    $image_fields = ['story_image', 'section2_image', 'section3_image'];
    $new_images   = [];
    foreach ($image_fields as $field) {
        $result = handle_image_upload($field);
        if ($result && strpos($result, 'error:') === 0) {
            $upload_errors[] = substr($result, 6);
        } elseif ($result) {
            $new_images[$field] = $result;
        }
    }

    if (empty($upload_errors)) {
        // Upsert every text field that was submitted
        $text_fields = [
            'story_title', 'story_para1', 'story_para2', 'story_image_alt',
            'section2_title', 'section2_text', 'section2_image_alt',
            'section3_title', 'section3_text', 'section3_image_alt',
        ];
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                $esc_key = mysqli_real_escape_string($conn, $field);
                $esc_val = mysqli_real_escape_string($conn, $_POST[$field]);
                mysqli_query($conn, "INSERT INTO about_content (field_key, field_value) VALUES ('$esc_key', '$esc_val')
                    ON DUPLICATE KEY UPDATE field_value = '$esc_val'");
            }
        }
        // Upsert the image paths for any files that were actually uploaded
        foreach ($new_images as $field => $path) {
            $esc_key = mysqli_real_escape_string($conn, $field);
            $esc_val = mysqli_real_escape_string($conn, $path);
            mysqli_query($conn, "INSERT INTO about_content (field_key, field_value) VALUES ('$esc_key', '$esc_val')
                ON DUPLICATE KEY UPDATE field_value = '$esc_val'");
        }
        $messages[] = ['type' => 'success', 'text' => 'About page updated successfully!'];
    } else {
        foreach ($upload_errors as $err) {
            $messages[] = ['type' => 'error', 'text' => $err];
        }
    }
}

// Load the current DB values on top of the defaults so the form is always pre-filled
$content = $defaults;
$result  = mysqli_query($conn, "SELECT field_key, field_value FROM about_content");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $content[$row['field_key']] = $row['field_value'];
    }
}

// Same legacy path migration as public/about.php — keeps the preview image working
// even if the stored path predates the assets restructure
$image_keys = ['story_image', 'section2_image', 'section3_image'];
foreach ($image_keys as $key) {
    if (isset($content[$key])) {
        $val = $content[$key];
        if (strpos($val, 'images/') === 0) {
            $content[$key] = '../assets/' . $val;
        } elseif (strpos($val, 'uploaded_img/') === 0) {
            $content[$key] = '../assets/uploads/' . substr($val, strlen('uploaded_img/'));
        }
    }
}

// Shorthand escape helper used throughout the form template below
function esc($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Drives the section loop below — add a new entry here to render another section card
$sections = [
    [
        'label'     => 'Section 1 — Main headline (image left, text right)',
        'icon'      => 'fas fa-star',
        'title_key' => 'story_title',
        'title_lbl' => 'Headline',
        'text_keys' => [
            ['story_para1', 'Paragraph 1 <span class="about-admin-hint">(HTML allowed: &lt;strong&gt;)</span>'],
            ['story_para2', 'Paragraph 2'],
        ],
        'img_key'   => 'story_image',
        'alt_key'   => 'story_image_alt',
        'title_big' => true,
    ],
    [
        'label'     => 'Section 2 — Our Footprint (text left, image right)',
        'icon'      => 'fas fa-chart-bar',
        'title_key' => 'section2_title',
        'title_lbl' => 'Heading',
        'text_keys' => [
            ['section2_text', 'Body text <span class="about-admin-hint">(HTML allowed: &lt;strong&gt;)</span>'],
        ],
        'img_key'   => 'section2_image',
        'alt_key'   => 'section2_image_alt',
        'title_big' => false,
    ],
    [
        'label'     => 'Section 3 — Our Journey (text left, image right)',
        'icon'      => 'fas fa-map-marker-alt',
        'title_key' => 'section3_title',
        'title_lbl' => 'Heading',
        'text_keys' => [
            ['section3_text', 'Body text <span class="about-admin-hint">(HTML allowed: &lt;strong&gt;)</span>'],
        ],
        'img_key'   => 'section3_image',
        'alt_key'   => 'section3_image_alt',
        'title_big' => false,
    ],
];

?>

<?php
$admin_page_title = 'Edit About Page';
$admin_active_nav = 'about';
include '../includes/admin_header.php';
?>

<?php foreach ($messages as $msg): ?>
    <div class="adm-alert adm-alert--<?php echo $msg['type']; ?>">
        <i class="fas fa-<?php echo $msg['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo esc($msg['text']); ?>
    </div>
<?php endforeach; ?>

<div style="display:flex; justify-content:flex-end; margin-bottom:20px;">
    <a href="../public/about.php" target="_blank" class="adm-btn adm-btn--ghost">
        <i class="fas fa-eye"></i> Preview About Page
    </a>
</div>

<form action="about.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="save_about" value="1">

    <div class="adm-about-sections">

        <?php foreach ($sections as $sec): ?>
        <div class="adm-about-section">
            <div class="adm-about-section__head">
                <i class="<?php echo $sec['icon']; ?>"></i>
                <h2><?php echo $sec['label']; ?></h2>
            </div>
            <div class="adm-about-section__body">

                <!-- Title/Headline -->
                <div class="adm-form-group">
                    <label class="adm-label"><?php echo $sec['title_lbl']; ?></label>
                    <input type="text" name="<?php echo $sec['title_key']; ?>" class="adm-input"
                           value="<?php echo esc($content[$sec['title_key']]); ?>">
                </div>

                <!-- Text fields -->
                <?php foreach ($sec['text_keys'] as [$field_key, $field_lbl]): ?>
                <div class="adm-form-group">
                    <label class="adm-label">
                        <?php echo $field_lbl; ?>
                    </label>
                    <textarea name="<?php echo $field_key; ?>" class="adm-textarea" rows="5"><?php echo esc($content[$field_key]); ?></textarea>
                </div>
                <?php endforeach; ?>

                <!-- Image -->
                <div class="adm-form-group">
                    <label class="adm-label">Image</label>
                    <div class="adm-img-row">
                        <div class="adm-img-preview">
                            <img id="preview-<?php echo $sec['img_key']; ?>"
                                 src="<?php echo esc($content[$sec['img_key']]); ?>"
                                 alt="Current image">
                            <span>Current image</span>
                        </div>
                        <div class="adm-img-upload-area">
                            <div class="adm-form-group">
                                <label class="adm-label">Upload new image <span class="adm-hint">Leave blank to keep current</span></label>
                                <input type="file"
                                       name="<?php echo $sec['img_key']; ?>"
                                       accept="image/png, image/jpeg, image/jpg, image/webp, image/gif, image/svg+xml"
                                       class="adm-file adm-input"
                                       data-preview="preview-<?php echo $sec['img_key']; ?>">
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-label">Image alt text</label>
                                <input type="text" name="<?php echo $sec['alt_key']; ?>" class="adm-input"
                                       value="<?php echo esc($content[$sec['alt_key']]); ?>"
                                       placeholder="Describe the image for accessibility">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <div style="margin-top: 28px; display:flex; justify-content:flex-end;">
        <button type="submit" class="adm-btn adm-btn--primary adm-btn--lg">
            <i class="fas fa-save"></i> Save All Changes
        </button>
    </div>

</form>

<script>
// Live-preview any image the admin picks before they hit Save
document.querySelectorAll('input[type="file"][data-preview]').forEach(function(input) {
    input.addEventListener('change', function() {
        var file = this.files[0];
        if (file) {
            var reader    = new FileReader();
            var previewId = this.getAttribute('data-preview');
            reader.onload = function(e) {
                document.getElementById(previewId).src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>

<?php include '../includes/admin_footer.php'; ?>
