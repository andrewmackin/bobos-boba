<?php include("includes/init.php"); ?>

<!DOCTYPE html>
<html lang="en">

<?php include("includes/head.php");

if (is_user_logged_in($current_user)) {

  $show_form = true;
  $drinks_query = "SELECT id, drink_name FROM drinks";
  $drinks = exec_sql_query($db, $drinks_query)->fetchAll();

  define("MAX_FILE_SIZE", 1000000);
  $adding = $_GET['mode'] == 'add';
  if (!$adding) {
    $tag_id = $_GET['id'];
    $tags = exec_sql_query($db, "SELECT DISTINCT id, tag, img_ext FROM tags WHERE (id = '" . $tag_id . "')")->fetchAll();
    $tag = count($tags) > 0 ? $tags[0] : NULL;
  }

  /* Feedback variables */
  $show_name_feedback = false;

  if ($tag) {
    $sticky_name = $tag['tag'];
    $tag_drinks_query = "SELECT DISTINCT drinks.drink_name FROM drink_tags INNER JOIN drinks ON (drink_tags.drink_id = drinks.id) WHERE (drink_tags.tag_id = '" . $tag_id . "')";
    $tag_drinks = exec_sql_query($db, $tag_drinks_query)->fetchAll();
    foreach ($tag_drinks as $drink) {
      $key = $drink['drink_name'];
      $drink_lc_dashed = str_replace(' ', '-', strtolower($key));
      $sticky_drinks[$drink_lc_dashed] = 'checked';
    }
  }

  $insertion_success = false;
  $insertion_failure = false;

  if (isset($_POST["edit_tag"])) {
    $tag_name = $_POST["tag-name"]; /* untrusted */

    $upload = $_FILES['tag-image'];

    if ($upload['error'] == UPLOAD_ERR_OK) {
      $tag_img_name = basename($upload['name']);
      $upload_ext = strtolower(pathinfo($tag_img_name, PATHINFO_EXTENSION));

      if (!in_array($upload_ext, array('svg', 'png', 'jpg', 'jpeg'))) {
        $form_valid = false;
        $show_file_feedback = true;
      }
    }

    $sticky_drinks = array();
    foreach ($drinks as $drink) {
      $key = $drink['drink_name'];
      $drink_lc_dashed = str_replace(' ', '-', strtolower($key));
      $sticky_drinks[$drink_lc_dashed] = isset($_POST[$drink_lc_dashed]) ? 'checked' : '';
    }

    $form_valid = true;

    if (empty($tag_name)) {
      $form_valid = false;
      $show_name_feedback = true;
    }

    if ($form_valid) {
      $show_confirmation = true;
      $show_form = false;
      if ($adding) {
        $sql_query = "INSERT INTO tags (tag, img_ext) VALUES (:tag, :img_ext)";
      } else {
        $sql_query = "UPDATE tags SET tag = :tag, img_ext = :img_ext WHERE (id = '" . $tag_id . "')";
      }

      $sql_params = array(
        ':tag' => $tag_name,
        ':img_ext' => $upload_ext
      );

      $db->beginTransaction();
      $result_tag = exec_sql_query($db, $sql_query, $sql_params);
      $drink_insert_acc = '';
      $drink_delete_acc = '';
      $tag_id = $adding ? $db->lastInsertId('id') : $tag_id;
      if ($result_tag) {
        $id_filename = 'public/uploads/tags/' . $tag_id . '.' . $upload_ext;
        move_uploaded_file($upload['tmp_name'], $id_filename);
      }
      $db->commit();
      foreach ($drinks as $drink) {
        $key = $drink['drink_name'];
        $drink_lc_dashed = str_replace(' ', '-', strtolower($key));
        $drink_id = $drink['id'];
        if (isset($_POST[$drink_lc_dashed])) {
          if (empty($drink_insert_acc)) {
            $drink_insert_acc .= "INSERT INTO drink_tags (drink_id, tag_id) VALUES (" . $drink_id . "," . $tag_id . ")";
          } else {
            $drink_insert_acc .= ", (" . $drink_id . "," . $tag_id . ")";
          }
        } else {
          if (empty($drink_delete_acc)) {
            $drink_delete_acc .= "DELETE FROM drink_tags WHERE (drink_id = '" . $drink_id . "' AND tag_id = '" . $tag_id . "')";
          } else {
            $drink_delete_acc .= " OR (drink_id = '" . $drink_id . "' AND tag_id = '" . $tag_id . "')";
          }
        }

        if ($drink_insert_acc) {
          exec_sql_query($db, $drink_insert_acc);
        }

        if ($drink_delete_acc) {
          exec_sql_query($db, $drink_delete_acc);
        }
      }

      if ($result_tag) {
        $insertion_success = true;
      } else {
        $insertion_failure = true;
      }
    } else {
      /* define sticky values for name */
      $sticky_name = $tag_name;
    }
  }

  if (isset($_POST["delete_tag"])) {
    exec_sql_query($db, "DELETE FROM tags WHERE (id = '" . $tag_id . "')");
    exec_sql_query($db, "DELETE FROM drink_tags WHERE (tag_id = '" . $tag_id . "')");
  }
}
?>


<?php include("includes/header.php"); ?>

<body>
  <main class='tag-details'>
    <?php if ($_GET['mode'] == 'edit' || $_GET['mode'] == 'add') {
      if (is_user_logged_in($current_user)) {
        if ($show_form) {
          if (($_GET['mode'] == 'edit' && $tag) || $_GET['mode'] == 'add') {
            $adding = $_GET['mode'] == 'add';
            $title = $adding ? 'Add Tag' : 'Modify Tag'; ?>
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <?php
            $image_path = is_null($tag['img_ext']) ? 'public/uploads/default-image.jpg' : 'public/uploads/tags/' . $tag_id . '.' . $tag['img_ext'];

            if (!$adding) { ?>
              <!-- Citation: This is my own image -->
              <img class='modify-image' src='<?php echo htmlspecialchars($image_path); ?>' alt='<?php echo htmlspecialchars($tag['tag']); ?>'>
              <?php $redirect_url = '/tag-details?' . http_build_query(array('id' => $tag_id)); ?>
              <form method="post" action="<?php echo $redirect_url ?>" novalidate>
                <button class='delete-button' type="submit" name="delete_tag">Delete Tag</button>
              </form>
            <?php } ?>
            <?php $redirect_url = '/tag-details?';
            $redirect_url .= $adding ? http_build_query(array('mode' => 'add')) : http_build_query(array('mode' => 'edit', 'id' => $tag_id)); ?>
            <form method="post" action="<?php echo $redirect_url ?>" enctype="multipart/form-data" novalidate>
              <?php if ($show_name_feedback) { ?>
                <p class="feedback">Please prove a tag name.</p>
              <?php } ?>
              <div class="group-label-input">
                <label for="name_field">Tag Name (Required): </label>
                <input id="name_field" type="text" name="tag-name" value='<?php echo htmlspecialchars($sticky_name) ?>'>
              </div>

              <br>
              <p class='label-align'>Drink(s): </p>
              <?php
              foreach ($drinks as $drink) {
                $drink_lc_dashed = str_replace(' ', '-', strtolower($drink['drink_name'])); ?>
                <div class="group-label-input">
                  <input type='checkbox' id='<?php echo $drink_lc_dashed; ?>' name='<?php echo $drink_lc_dashed; ?>' value=<?php echo $drink['drink_name']; ?> <?php echo $sticky_drinks[$drink_lc_dashed]; ?>>
                  <label for='<?php echo $drink_lc_dashed ?>'><?php echo htmlspecialchars($drink['drink_name']); ?></label>
                </div>
              <?php } ?>

              <br>
              <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo MAX_FILE_SIZE; ?>" />
              <?php if ($show_file_feedback) { ?>
                <p class="feedback">Please select a valid image.</p>
              <?php } ?>
              <div class="group-label-input">
                <label id="tag-image" for="tag-image">
                  <p class='label-align'>Tag Image:
                    <input id="tag-image" type="file" name="tag-image" accept=".svg, .jpg, .jpeg, .png, image/jpeg, image/png, image/svg+xml" required>
                  </p>
                </label>
              </div>

              <br>
              <div class="align-right">
                <button type="submit" name="edit_tag"><?php echo $title ?></button>
              </div>
            </form>
          <?php } else { ?>
            <p>This drink does not exist</p>
          <?php }
        } else { ?>
          <p>Tags modified successfully!</p>
        <?php }
      } else { ?>
        <p>You must be logged in to view this content.</p>
      <?php }
    } else if (isset($_POST["delete_tag"])) { ?>
      <p>Tag Deleted Successfully!</p>
      <a href='/home'>Return to Home</a>
    <?php } else { ?>
      <h2>Page Not Found</h2>
      <p>Oops! It looks like this page does not exist.</p>
    <?php } ?>
  </main>
</body>

<?php include("includes/footer.php"); ?>

</html>
