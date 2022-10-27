<?php
include("includes/init.php");
$nav_home_class = "current-page";
?>

<!DOCTYPE html>
<html lang="en">

<?php include("includes/head.php"); ?>

<body>
  <?php include("includes/header.php"); ?>
  <main>
    <h2 class='home-title'>Menu</h2>
    <p class='home-subtitle'>Here at Bobo's Boba, we serve freshly-made delicious boba and tea. All drinks are served with boba.</p>

    <?php
    // get all tags
    $result = exec_sql_query($db, "SELECT tags.id, tags.tag, tags.img_ext FROM tags");
    $tags = $result->fetchAll();
    ?>
    <div class='catalog'>
      <?php if (is_user_logged_in($current_user)) { ?>
        <div class='catalog-tile'>
          <a href=<?php echo 'tag-details?' . http_build_query(array('mode' => 'add')); ?>>
            <!-- Citation: This was my own drawing -->
            <img class='tag-image add-image' src='public/uploads/add-tag.png' alt='Add Tag'>
          </a>
        </div>
      <?php
      }
      foreach ($tags as $tag) { ?>
        <div class='catalog-tile'>
          <?php $tag_url = '/drinks?' . str_replace(' ', '-', strtolower($tag['tag'])) . '=1'; ?>
          <a href=<?php echo $tag_url; ?>>
            <?php $image_path = is_null($tag['img_ext']) ? 'public/uploads/default-image.jpg' : 'public/uploads/tags/' . $tag['id'] . '.' . $tag['img_ext']; ?>
            <img class='tag-image' src='<?php echo $image_path ?>' alt=<?php echo htmlspecialchars($tag['tag']) ?>> <!-- Citation: This was my own drawing -->
          </a>
          <div class='tile-text'>
            <a href=<?php echo $tag_url ?> class=''><?php echo htmlspecialchars($tag['tag']); ?></a>
          </div>
          <?php if (is_user_logged_in($current_user)) { ?>
            <a href=<?php echo '/tag-details?' . http_build_query(array('mode' => 'edit', 'id' => $tag['id'])); ?>>(Edit)</a>
          <?php } ?>
        </div>
      <?php } ?>
    </div>

    <h2 class='home-title'>Reviews</h2>
    <div class="catalog">
        <div class='catalog-tile'>
          <div class='review'>
            <p class='review-text'>Bobo's is a must-go whenever I'm in the area!</p>
            <p class='review-author'>- Kate</p>
          </div>
        </div>

        <div class='catalog-tile'>
          <div class='review'>
            <p class='review-text'>I really enjoy coming to Bobo's whenever I'm looking to get work done or relax. The atmosphere is so peaceful!</p>
            <p class='review-author'>- Katya</p>
          </div>
        </div>

        <div class='catalog-tile'>
          <div class='review'>
            <p class='review-text'>Their tea is made fresh and never disappoints.</p>
            <p class='review-author'>- David</p>
          </div>
        </div>
    </div>

  </main>
  <?php include("includes/footer.php"); ?>
</body>

</html>
