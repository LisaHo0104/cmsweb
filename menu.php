<?php @include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Menu — Gong cha Australia</title>
   <link rel="stylesheet" href="css/style.css">
   <link rel="icon" href="images/gongcha-logo-new.svg" type="image/svg+xml">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="gc-menu-page">
   <div class="gc-menu-layout">

      <!-- Sidebar -->
      <aside class="gc-menu-sidebar">
         <h1 class="gc-menu-sidebar__title">Our<br>Tea Menu</h1>
         <nav class="gc-menu-sidebar__nav">
            <a href="?cat=all"          class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='all')           ? 'gc-menu-cat--active' : ''; ?>">All</a>
            <a href="?cat=top10"        class="gc-menu-cat <?php echo (!isset($_GET['cat']) || $_GET['cat']==='top10')       ? 'gc-menu-cat--active' : ''; ?>">Top 10</a>
            <a href="?cat=brewed"       class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='brewed')       ? 'gc-menu-cat--active' : ''; ?>">Brewed Tea</a>
            <a href="?cat=creative"     class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='creative')     ? 'gc-menu-cat--active' : ''; ?>">Creative Mix</a>
            <a href="?cat=health"       class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='health')       ? 'gc-menu-cat--active' : ''; ?>">Health Tea</a>
            <a href="?cat=milkfoam"     class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='milkfoam')     ? 'gc-menu-cat--active' : ''; ?>">Milk Foam</a>
            <a href="?cat=milktea"      class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='milktea')      ? 'gc-menu-cat--active' : ''; ?>">Milk Tea</a>
            <a href="?cat=smoothie"     class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='smoothie')     ? 'gc-menu-cat--active' : ''; ?>">Smoothie</a>
            <a href="?cat=yoghurt"      class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='yoghurt')      ? 'gc-menu-cat--active' : ''; ?>">Yoghurt</a>
            <a href="?cat=toppings"     class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='toppings')     ? 'gc-menu-cat--active' : ''; ?>">Toppings</a>
         </nav>
      </aside>

      <!-- Grid -->
      <section class="gc-menu-grid-wrap">
         <div class="gc-menu-grid">
            <?php
               $showed_db = false;

               if(isset($conn)){
                  $cat = isset($_GET['cat']) ? $_GET['cat'] : 'all';
                  if($cat === 'all'){
                     $select = mysqli_query($conn, "SELECT * FROM products");
                  } else {
                     $safe_cat = mysqli_real_escape_string($conn, $cat);
                     $select = mysqli_query($conn, "SELECT * FROM products WHERE category='$safe_cat'");
                  }
                  if($select && mysqli_num_rows($select) > 0){
                     $showed_db = true;
                     while($row = mysqli_fetch_assoc($select)):
            ?>
            <div class="gc-menu-card">
               <div class="gc-menu-card__img-wrap">
                  <img src="uploaded_img/<?php echo htmlspecialchars($row['image']); ?>"
                       alt="<?php echo htmlspecialchars($row['name']); ?>"
                       class="gc-menu-card__img">
               </div>
               <div class="gc-menu-card__info">
                  <h3 class="gc-menu-card__name"><?php echo htmlspecialchars($row['name']); ?></h3>
                  <p class="gc-menu-card__price">$<?php echo htmlspecialchars($row['price']); ?></p>
               </div>
            </div>
            <?php
                     endwhile;
                  }
               }

               // Show demo cards only when no DB products
               if(!$showed_db):
                  $demo_items = [
                     ['name'=>'Earl Grey Milk Tea &amp; 3Js',           'kj'=>'R 1345kJ  L 1814kJ', 'img'=>'images/Gong-Cha-Day-2-0707-RGB.png'],
                     ['name'=>'Matcha Red Bean',                         'kj'=>'R 1230kJ  L 1678kJ', 'img'=>'images/Gong-Cha-Day-2-0060-RGB.png'],
                     ['name'=>'Pearl Milk Tea',                          'kj'=>'R 1666kJ  L 2205kJ', 'img'=>'images/Gong-Cha-Day-2-0756-RGB.png'],
                     ['name'=>'Taro Milk Tea',                           'kj'=>'R 1042kJ  L 1251kJ', 'img'=>'images/Gong-Cha-Day-2-0707-RGB.png'],
                     ['name'=>'Milk Foam Green Tea',                     'kj'=>'R 883kJ   L 1204kJ',  'img'=>'images/Gong-Cha-Day-2-0060-RGB.png'],
                     ['name'=>'Lemon Roasted Melon Tea &amp; Basil Seeds','kj'=>'R 587kJ   L 871kJ',   'img'=>'images/Gong-Cha-Day-2-0756-RGB.png'],
                     ['name'=>'Grape Green Tea &amp; Basil Seeds',       'kj'=>'R 544kJ   L 679kJ',   'img'=>'images/Gong-Cha-Day-2-0707-RGB.png'],
                     ['name'=>'Mango Alisan Tea',                        'kj'=>'R 551kJ   L 725kJ',   'img'=>'images/Gong-Cha-Day-2-0060-RGB.png'],
                     ['name'=>'Lychee Oolong &amp; Aloe',                'kj'=>'R 617kJ   L 691kJ',   'img'=>'images/Gong-Cha-Day-2-0756-RGB.png'],
                     ['name'=>'QQ Passionfruit Green Tea',               'kj'=>'R 1434kJ  L 1768kJ',  'img'=>'images/Gong-Cha-Day-2-0707-RGB.png'],
                  ];
                  foreach($demo_items as $item):
            ?>
            <div class="gc-menu-card">
               <div class="gc-menu-card__img-wrap">
                  <img src="<?php echo $item['img']; ?>" alt="<?php echo $item['name']; ?>" class="gc-menu-card__img">
               </div>
               <div class="gc-menu-card__info">
                  <h3 class="gc-menu-card__name"><?php echo $item['name']; ?></h3>
                  <p class="gc-menu-card__kj"><?php echo $item['kj']; ?></p>
               </div>
            </div>
            <?php
                  endforeach;
               endif;
            ?>
         </div>
      </section>

   </div>
</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
