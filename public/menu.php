<?php require_once '../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Menu — Gong cha Australia</title>
   <link rel="stylesheet" href="../assets/css/style.css">
   <link rel="icon" href="../assets/images/gongcha-logo-new.svg" type="image/svg+xml">
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<main class="gc-menu-page">
   <div class="gc-menu-layout">

      <!-- Sidebar -->
      <aside class="gc-menu-sidebar">
         <h1 class="gc-menu-sidebar__title">Our<br>Tea Menu</h1>
         <nav class="gc-menu-sidebar__nav">
            <a href="?cat=all"      class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='all')       ? 'gc-menu-cat--active' : ''; ?>">All</a>
            <a href="?cat=top10"    class="gc-menu-cat <?php echo (!isset($_GET['cat']) || $_GET['cat']==='top10')    ? 'gc-menu-cat--active' : ''; ?>">Top 10</a>
            <a href="?cat=brewed"   class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='brewed')    ? 'gc-menu-cat--active' : ''; ?>">Brewed Tea</a>
            <a href="?cat=creative" class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='creative')  ? 'gc-menu-cat--active' : ''; ?>">Creative Mix</a>
            <a href="?cat=health"   class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='health')    ? 'gc-menu-cat--active' : ''; ?>">Health Tea</a>
            <a href="?cat=milkfoam" class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='milkfoam')  ? 'gc-menu-cat--active' : ''; ?>">Milk Foam</a>
            <a href="?cat=milktea"  class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='milktea')   ? 'gc-menu-cat--active' : ''; ?>">Milk Tea</a>
            <a href="?cat=smoothie" class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='smoothie')  ? 'gc-menu-cat--active' : ''; ?>">Smoothie</a>
            <a href="?cat=yoghurt"  class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='yoghurt')   ? 'gc-menu-cat--active' : ''; ?>">Yoghurt</a>
            <a href="?cat=toppings" class="gc-menu-cat <?php echo (isset($_GET['cat']) && $_GET['cat']==='toppings')  ? 'gc-menu-cat--active' : ''; ?>">Toppings</a>
         </nav>
      </aside>

      <!-- Grid -->
      <section class="gc-menu-grid-wrap">
         <div class="gc-menu-grid">
            <?php
               if (isset($conn)) {
                  // Read the chosen category from the URL; default to Top 10
                  $cat = isset($_GET['cat']) ? $_GET['cat'] : 'top10';

                  if ($cat === 'all') {
                     $select = mysqli_query($conn, "SELECT * FROM products");
                  } else {
                     // Escape before dropping into the query string
                     $safe_cat = mysqli_real_escape_string($conn, $cat);
                     $select = mysqli_query($conn, "SELECT * FROM products WHERE category='$safe_cat'");
                  }

                  if ($select && mysqli_num_rows($select) > 0) {
                     while ($row = mysqli_fetch_assoc($select)):
            ?>
            <div class="gc-menu-card">
               <div class="gc-menu-card__img-wrap">
                  <img src="../assets/uploads/<?php echo htmlspecialchars($row['image']); ?>"
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
            ?>
         </div>
      </section>

   </div>
</main>

<?php include '../includes/footer.php'; ?>

</body>
</html>
