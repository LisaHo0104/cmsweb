<?php
@include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Gong cha Australia</title>
   <link rel="stylesheet" href="css/style.css">
   <link rel="icon" href="images/gongcha-logo-new.svg" type="image/svg+xml">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="container">

   <h2 class="section-title">all products</h2>

   <div class="products-grid">
      <?php
         if(isset($conn)){
            $select = mysqli_query($conn, "SELECT * FROM products");
            if($select && mysqli_num_rows($select) > 0){
               while($row = mysqli_fetch_assoc($select)){
      ?>
      <div class="product-card">
         <div class="product-card-img">
            <img src="uploaded_img/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
         </div>
         <div class="product-card-info">
            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
            <p class="product-price">$<?php echo htmlspecialchars($row['price']); ?>/-</p>
         </div>
      </div>
      <?php
            }
         }else{ ?>
      <p class="no-products">no products available at the moment.</p>
      <?php } } ?>
   </div>

</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
