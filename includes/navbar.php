<?php
// Work out which page we're on so the matching nav link gets the active class
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="gc-navbar">
   <div class="gc-navbar__inner">
      <a href="/cmsweb/public/about.php" class="gc-navbar__logo">
         <img src="<?php echo ASSETS_URL; ?>/images/gongcha-logo-new.svg" alt="Gong cha" width="120" height="24">
      </a>

      <button class="gc-navbar__toggle" id="navToggle" aria-label="Toggle navigation">
         <span></span><span></span><span></span>
      </button>

      <nav class="gc-navbar__nav" id="navMenu">
         <a href="/cmsweb/public/about.php" class="gc-navbar__link <?php echo $current_page === 'about' ? 'gc-navbar__link--active' : ''; ?>">About us</a>
         <a href="/cmsweb/public/menu.php"  class="gc-navbar__link <?php echo $current_page === 'menu'  ? 'gc-navbar__link--active' : ''; ?>">Menu</a>
         <a href="#" class="gc-navbar__link">Merchandise</a>
         <a href="#" class="gc-navbar__link">Membership</a>
         <a href="#" class="gc-navbar__link">New Digital Gift Card</a>
         <a href="#" class="gc-navbar__link">Store finder</a>
         <a href="#" class="gc-navbar__link">Franchise</a>
         <a href="#" class="gc-navbar__link">Jobs</a>
         <a href="#" class="gc-navbar__link">Contact</a>
      </nav>
   </div>
</header>

<script>
   document.getElementById('navToggle').addEventListener('click', function () {
      document.getElementById('navMenu').classList.toggle('gc-navbar__nav--open');
      this.classList.toggle('gc-navbar__toggle--open');
   });
</script>
