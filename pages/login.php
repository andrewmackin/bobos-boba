<?php
include("includes/init.php");
$nav_login_class = 'current-page';
?>

<!DOCTYPE html>
<html lang="en">

<?php include("includes/head.php");  ?>
<?php include("includes/header.php"); ?>

<body>

  <main>
    <?php if (!is_user_logged_in()) { ?>
      <section>
        <h2>Log In</h2>

        <?php echo_login_form('/', $session_messages); ?>
      <?php } ?>
      </section>
  </main>
  </main>
</body>


<?php include("includes/footer.php"); ?>

</html>
