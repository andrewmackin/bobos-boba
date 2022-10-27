<header>
  <h1 class="title">Bobo's Boba</h1>
  <nav>
    <ul>
      <li><a class="<?php echo $nav_home_class; ?>" href="/">Home</a></li>
      <li><a class="<?php echo $nav_menu_class; ?>" href="/drinks">
          Drinks</a></li>
      <li><a class="<?php echo $nav_about_class; ?>" href="/about">About</a></li>

      <?php if (is_user_logged_in()) { ?>
        <li id="nav-logout"><a href="<?php echo logout_url(); ?>">Sign Out</a></li>
      <?php } else { ?>
        <li><a class="<?php echo $nav_login_class; ?> " href='/login'>Log In</a></li>
      <?php } ?>
    </ul>
  </nav>
</header>
