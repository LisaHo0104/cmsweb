<?php

require_once '../config.php';

// These are the fallback values shown when the DB has no rows yet (fresh install, etc.)
$content = [
    'story_title'     => "Australia's Number 1 Bubble Tea Brand",
    'story_para1'     => 'As Australia\'s largest bubble tea chain by store count, Gong cha Australia <strong>operates over 170 stores nationwide</strong>—more locations than any other bubble tea brand in the country.  This unmatched store number reflects our rapid growth since entering the market in 2012 and underscores the trust Australians place in our premium, freshly brewed teas Across the country,',
    'story_para2'     => '',
    'story_image'     => '../assets/images/Gong-Cha-Day-2-0707-RGB.png',
    'story_image_alt' => '',
    'section2_title'  => 'Our Footprint in Numbers',
    'section2_text'   => 'Across these locations—from bustling metropolitan outlets to inviting regional cafés—we serve <b>more than 15 million drinks every year</b>, delighting customers with freshly brewed teas and customisable creations day in, day out.',
    'section2_image'  => '../assets/images/Gong-Cha-Day-2-0756-RGB.png',
    'section2_image_alt' => '',
    'section3_title'  => 'Our Journey In Australia',
    'section3_text'   => 'Since entering the Australian market in 2012, Gong Cha has taken the country by storm—expanding rapidly from our first Sydney and Melbourne outlets to nationwide. You\'ll now find Gong Cha in bustling city centres, suburban precincts, transit hubs, and even regional towns — underscoring our commitment to serving communities of all sizes.',
    'section3_image'  => '../assets/images/Gong-Cha-Day-2-0060-RGB.png',
    'section3_image_alt' => '',
];

// Pull the real content from the DB and overwrite any matching fallback keys
if ($conn) {
    $result = mysqli_query($conn, "SELECT field_key, field_value FROM about_content");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $content[$row['field_key']] = $row['field_value'];
        }
    }
}

// Fix up image paths that were saved before the assets folder was reorganised.
// Old format: "images/foo.png"      → "../assets/images/foo.png"
// Old format: "uploaded_img/foo.png" → "../assets/uploads/foo.png"
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

// Shorthand so we don't have to repeat htmlspecialchars() everywhere in the template
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>About Us — Gong cha Australia</title>
   <link rel="stylesheet" href="../assets/css/style.css">
   <link rel="icon" href="../assets/images/gongcha-logo-new.svg" type="image/svg+xml">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="about-us">
   <div class="container">
      <div class="row">

         <!-- Left: page title -->
         <div class="left-menu wowo fadeInUp">
            <h1>About us</h1>
         </div>

         <!-- Right: all content sections -->
         <div class="right-content wowo fadeInUp">

            <!-- Section 1: image left, text right, border top-left -->
            <div class="top-content-list-one border-top-left">
               <div class="img shade">
                  <div class="border">
                     <img src="<?php echo e($content['story_image']); ?>" alt="<?php echo e($content['story_image_alt']); ?>">
                  </div>
               </div>
               <div class="text">
                  <h2>
                     <span><?php echo $content['story_title']; ?></span>
                  </h2>
                  <img src="" alt="" class="cue-cart">
                  <div class="text-medium">
                     <p><?php echo $content['story_para1']; ?></p>
                     <?php if (!empty($content['story_para2'])): ?>
                     <p><?php echo $content['story_para2']; ?></p>
                     <?php endif; ?>
                  </div>
               </div>
            </div>

            <!-- Section 2: text left, image right, border bottom-right -->
            <div class="content-list text-img another border-bottom-right">
               <div class="text">
                  <h3><?php echo e($content['section2_title']); ?></h3>
                  <p><?php echo $content['section2_text']; ?></p>
               </div>
               <div class="img">
                  <div class="border">
                     <img src="<?php echo e($content['section2_image']); ?>" alt="<?php echo e($content['section2_image_alt']); ?>">
                  </div>
               </div>
            </div>

            <!-- Section 3: text left, image right, border bottom-right -->
            <div class="content-list text-img another border-bottom-right">
               <div class="text">
                  <h3><?php echo e($content['section3_title']); ?></h3>
                  <p><?php echo $content['section3_text']; ?></p>
               </div>
               <div class="img">
                  <div class="border">
                     <img src="<?php echo e($content['section3_image']); ?>" alt="<?php echo e($content['section3_image_alt']); ?>">
                  </div>
               </div>
            </div>

         </div><!-- /.right-content -->
      </div><!-- /.row -->
   </div><!-- /.container -->
</div><!-- /.about-us -->

<?php include '../includes/footer.php'; ?>

</body>
</html>
