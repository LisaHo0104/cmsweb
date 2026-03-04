<?php

@include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('location: admin_login.php');
    exit;
}

$id = $_GET['edit'];

if(isset($_POST['update_product'])){

   $product_name = $_POST['product_name'];
   $product_price = $_POST['product_price'];
   $new_image = $_FILES['product_image']['name'];
   $product_image_tmp_name = $_FILES['product_image']['tmp_name'];

   if(empty($product_name) || empty($product_price)){
      $message[] = 'please fill out name and price!';
   }else{

      if(!empty($new_image)){
         // New image uploaded — update image in DB and move file
         $product_image_folder = 'uploaded_img/'.$new_image;
         $update_data = "UPDATE products SET name='$product_name', price='$product_price', image='$new_image' WHERE id = '$id'";
         $upload = mysqli_query($conn, $update_data);
         if($upload){
            move_uploaded_file($product_image_tmp_name, $product_image_folder);
            header('location:admin_page.php');
         }else{
            $message[] = 'could not update the product!';
         }
      }else{
         // No new image — keep existing image, just update name and price
         $update_data = "UPDATE products SET name='$product_name', price='$product_price' WHERE id = '$id'";
         $upload = mysqli_query($conn, $update_data);
         if($upload){
            header('location:admin_page.php');
         }else{
            $message[] = 'could not update the product!';
         }
      }

   }
};

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php
   if(isset($message)){
      foreach($message as $message){
         echo '<span class="message">'.$message.'</span>';
      }
   }
?>

<div class="container">


<div class="admin-product-form-container centered">

   <?php
      
      $select = mysqli_query($conn, "SELECT * FROM products WHERE id = '$id'");
      while($row = mysqli_fetch_assoc($select)){

   ?>
   
   <form action="" method="post" enctype="multipart/form-data">
      <h3 class="title">update the product</h3>
      <input type="text" class="box" name="product_name" value="<?php echo $row['name']; ?>" placeholder="enter the product name">
      <input type="number" min="0" class="box" name="product_price" value="<?php echo $row['price']; ?>" placeholder="enter the product price">
      <input type="file" class="box" name="product_image"  accept="image/png, image/jpeg, image/jpg">
      <input type="submit" value="update product" name="update_product" class="btn">
      <a href="admin_page.php" class="btn">go back!</a>
   </form>
   


   <?php }; ?>

   

</div>

</div>

</body>
</html>