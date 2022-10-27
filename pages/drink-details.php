<?php
include("includes/init.php");

?>

<!DOCTYPE html>
<html lang="en">

<?php include("includes/head.php");

function display_price($price)
{
  $period = strrpos($price, '.');
  if ($period) {
    $init_cents = substr($price, $period + 1, 2);
    $cents = $init_cents . str_repeat('0', 2 - strlen($init_cents));
    $res = substr_replace($price, $cents, $period + 1);
  } else {
    $res = $price . '.' . '00';
  }
  return '$' . $res;
}

if (is_user_logged_in($current_user)) {

  $show_form = true;
  $tags_query = "SELECT id, tag FROM tags";
  $result = exec_sql_query($db, $tags_query);
  $tags = (is_null($result)) ? array() : $result->fetchAll();
  define("MAX_FILE_SIZE", 1000000);

  $adding = $_GET['mode'] == 'add';
  $result = NULL;
  if (!$adding) {
    $drink_id = $_GET['id'];

    $drink_query = "SELECT DISTINCT drinks.id, drinks.drink_name, drinks.drink_desc, drinks.price, drinks.img_ext FROM drinks WHERE (drinks.id = " . "'" . $drink_id . "'" .  ")";

    $result = exec_sql_query($db, $drink_query);

    $drinks = (is_null($result)) ? array() : $result->fetchAll();
    $drink = count($drinks) > 0 ? $drinks[0] : NULL;
  }

  /* Feedback variables */
  $show_name_feedback = false;
  $show_price_feedback = false;
  $show_confirmation = false;


  /* Sticky values */
  if ($result) {
    $sticky_name = $drink['drink_name'];
    $sticky_desc = $drink['drink_desc'];
    $sticky_price = $drink['price'];
    $drink_id = $_GET['id'];
    $drink_tags_query = "SELECT drinks.drink_name, tags.tag FROM drink_tags LEFT OUTER JOIN drinks ON drinks.id = drink_tags.drink_id INNER JOIN tags ON tags.id = drink_tags.tag_id WHERE (drinks.id = '" . $drink_id . "')";
    $drink_tags = exec_sql_query($db, $drink_tags_query)->fetchAll();
    foreach ($drink_tags as $tag) {
      $key = $tag['tag'];
      $tag_lc_dashed = str_replace(' ', '-', strtolower($key));
      $sticky_tags[$tag_lc_dashed] = 'checked';
    }
  }

  $insertion_success = false;
  $insertion_failure = false;

  if (isset($_POST["edit_drink"])) {
    $name = $_POST["name"]; // untrusted
    $description = $_POST["description"]; // untrusted
    $price = $_POST["price"]; // untrusted
    $upload = $_FILES['drink-image'];

    if ($upload['error'] == UPLOAD_ERR_OK) {
      $drink_img_name = basename($upload['name']);
      $upload_ext = strtolower(pathinfo($drink_img_name, PATHINFO_EXTENSION));

      if (!in_array($upload_ext, array('svg', 'png', 'jpg', 'jpeg'))) {
        $form_valid = false;
        $show_file_feedback = true;
      }
    }

    $sticky_tags = array();
    foreach ($tags as $tag) {
      $key = $tag['tag'];
      $tag_lc_dashed = str_replace(' ', '-', strtolower($key));
      $sticky_tags[$tag_lc_dashed] = isset($_POST[$tag_lc_dashed]) ? 'checked' : '';
    }

    $form_valid = true;

    if (empty($name)) {
      $form_valid = false;
      $show_name_feedback = true;
    }

    if (empty($price) || $price < 0) {
      $form_valid = false;
      $show_price_feedback = true;
    }

    if ($form_valid) {
      $show_confirmation = true;
      $show_form = false;
      if ($adding) {
        $sql_query = "INSERT INTO drinks (drink_name, drink_desc, price, img_ext) VALUES (:drink_name, :drink_desc, :price, :img_ext)";
      } else {
        $id = $_GET['id'];
        $sql_query = "UPDATE drinks SET drink_name = :drink_name, drink_desc = :drink_desc,  price = :price, img_ext = :img_ext WHERE (id = " . "'" . $id . "')";
      }
      $sql_params =  array(
        ':drink_name' => $name,
        ':drink_desc' => $description,
        ':price' => $price,
        ':img_ext' => $upload_ext
      );
      $db->beginTransaction();
      $result_drink = exec_sql_query($db, $sql_query, $sql_params);
      $tag_insert_acc = '';
      $tag_delete_acc = '';
      $drink_id = $adding ? $db->lastInsertId('id') : $drink['id'];
      if ($result_drink) {
        $id_filename = 'public/uploads/drinks/' . $drink_id . '.' . $upload_ext;
        move_uploaded_file($upload['tmp_name'], $id_filename);
      }
      $db->commit();
      foreach ($tags as $tag) {
        $key = $tag['tag'];
        $tag_lc_dashed = str_replace(' ', '-', strtolower($key));
        $tag_id = $tag['id'];
        if (isset($_POST[$tag_lc_dashed])) {
          if (empty($tag_insert_acc)) {
            $tag_insert_acc .= 'INSERT INTO drink_tags (drink_id, tag_id) VALUES (' . $drink_id . ',' . $tag_id . ')';
          } else {
            $tag_insert_acc .= ', (' . $drink_id . ',' . $tag_id . ')';
          }
        } else {
          if (empty($tag_delete_acc)) {
            $tag_delete_acc .= "DELETE FROM drink_tags WHERE (" . "drink_id = '" . $drink_id . "' AND tag_id = '" . $tag_id . "')";
          } else {
            $tag_delete_acc .= " OR (drink_id = '" . $drink_id . "' AND tag_id = '" . $tag_id . "')";
          }
        }

        if ($tag_insert_acc) {
          exec_sql_query($db, $tag_insert_acc);
        }

        if ($tag_delete_acc) {
          exec_sql_query($db, $tag_delete_acc);
        }
      }

      if ($result_drink) {
        $insertion_success = true;
      } else {
        $insertion_failure = true;
      }
    } else {
      /* define sticky values for name, desc, price, tags */
      $sticky_name = $name;
      $sticky_desc = $description;
      $sticky_price = $price;
      $sticky_tags = $sticky_tags;
    }
  }

  if (isset($_POST["delete_drink"])) {
    $result1 = exec_sql_query($db, "DELETE FROM drinks WHERE (id = '" . $drink['id'] . "')");
    $result1 = exec_sql_query($db, "DELETE FROM drink_tags WHERE (drink_id = '" . $drink['id'] . "')");
  }
}

if ($_GET['mode'] == 'view') {
  $drink_id = ucwords(str_replace('-', ' ', $_GET['id']));


  $drink_query = "SELECT DISTINCT drinks.id, drinks.drink_name, drinks.drink_desc, drinks.price, drinks.img_ext FROM drinks WHERE (drinks.id = " . "'" . $drink_id . "'" .  ")";

  $result = exec_sql_query($db, $drink_query);
  $drinks = (is_null($result)) ? array() : $result->fetchAll();
}

?>
<?php include("includes/header.php"); ?>

<body>

  <main class='drink-details'>
    <?php if ($_GET['mode'] == 'view' && count($drinks) == 1) {
      foreach ($drinks as $drink) { ?>
        <?php $image_path = is_null($drink['img_ext'])
          ? 'public/uploads/default-image.jpg' : 'public/uploads/drinks/' . $drink['id'] . '.' . $drink['img_ext']; ?>
        <!-- Citation: This was my own drawing -->
        <img class='view-image' src=<?php echo htmlspecialchars($image_path); ?> alt=<?php echo htmlspecialchars($drink['drink_name']) ?>>
        <div class='drink-details-text'>
          <p class='details-name'><?php echo htmlspecialchars($drink['drink_name']); ?></p>
          <p class='details-price'><?php echo 'Price: ' . display_price(htmlspecialchars($drink['price'])); ?></p>
          <p class='details-desc'><?php echo htmlspecialchars($drink['drink_desc']); ?></p>
          <?php if (is_user_logged_in($current_user)) { ?>
            <a href=<?php echo '/drink-details?' . http_build_query(array('mode' => 'edit', 'id' => $drink_id)); ?>>Edit</a>
          <?php } ?>
        </div>
        <?php }
    } else if ($_GET['mode'] == 'edit' || $_GET['mode'] == 'add') {
      if (is_user_logged_in($current_user)) {
        if ($show_form) {
          if (($_GET['mode'] == 'edit' && count($drinks) == 1) || $_GET['mode'] == 'add') {
            $adding = $_GET['mode'] == 'add';
            $title = $adding ? 'Add Drink' : 'Modify Drink';
            $image_path = is_null($drink['img_ext'])
              ? 'public/uploads/default-image.jpg' : 'public/uploads/drinks/' . $drink['id'] . '.' . $drink['img_ext']; ?>
            <h2><?php echo htmlspecialchars($title) ?></h2>
            <?php
            $redirect_url = '/drink-details?';
            $redirect_url .= $adding ? http_build_query(array('mode' => 'add')) :  http_build_query(array('mode' => 'edit', 'id' => $drink_id)); ?>
            <?php if (!$adding) { ?>
              <!-- Citation: This was my own drawing -->
              <img class='modify-image' src='<?php echo htmlspecialchars($image_path); ?>' alt='<?php echo htmlspecialchars($drink['drink_name']); ?>'>
              <form method="post" action=<?php echo '/drink-details?' . http_build_query(array('id' => $drink['id'])); ?> novalidate>
                <button class='delete-button' type="submit" name="delete_drink">Delete Drink</button>
              </form>
            <?php } ?>

            <form method="post" action="<?php echo $redirect_url; ?>" enctype="multipart/form-data" novalidate>
              <?php if ($show_name_feedback) { ?>
                <p class="feedback">Please provide a drink name.</p>
              <?php } ?>
              <div class="group-label-input">
                <label for="name_field">Drink Name (Required): </label>
                <input id="name_field" type="text" name="name" value='<?php echo htmlspecialchars($sticky_name) ?>'>
              </div>

              <br>
              <div class="group-label-input">
                <label for="desc_field">Description: </label>
                <input class='desc-box' id="desc_field" type="text" name="description" value='<?php echo htmlspecialchars($sticky_desc); ?>'>
              </div>

              <br>
              <?php if ($show_price_feedback) { ?>
                <p class="feedback">Please provide a valid price.</p>
              <?php } ?>
              <div class="group-label-input">
                <label for="price_field">Price (Required): </label>
                <input id="price_field" type="number" name="price" value='<?php echo htmlspecialchars($sticky_price); ?>'>
              </div>

              <br>
              <p class='label-align'>Tag(s): </p>
              <?php
              foreach ($tags as $tag) {
                $tag_lc_dashed = str_replace(' ', '-', strtolower($tag['tag'])); ?>
                <div class="group-label-input">
                  <input type='checkbox' id='<?php echo $tag_lc_dashed; ?>' name='<?php echo $tag_lc_dashed; ?>' value=<?php echo $tag['tag']; ?> <?php echo $sticky_tags[$tag_lc_dashed]; ?>>
                  <label for='<?php echo $tag_lc_dashed ?>'><?php echo htmlspecialchars($tag['tag']); ?></label>
                </div>
              <?php } ?>

              <br>
              <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo MAX_FILE_SIZE; ?>" />
              <?php if ($show_file_feedback) { ?>
                <p class="feedback">Please select a valid image.</p>
              <?php } ?>
              <div class="group-label-input">
                <label id="drink-image" for="drink-image">
                  <p class='label-align'>Drink Image:
                    <input id="drink-image" type="file" name="drink-image" accept=".svg, .jpg, .jpeg, .png, image/jpeg, image/png, image/svg+xml" required>
                  </p>
                </label>
              </div>

              <br>
              <div class="align-right">
                <button type="submit" name="edit_drink"><?php echo $title ?></button>
              </div>
            </form>
          <?php } else { ?>
            <p>This drink does not exist</p>
          <?php }
        } else { ?>
          <p>Menu modified successfully!</p>
        <?php }
      } else { ?>
        <p>You must be logged in to view this content.</p>
      <?php }
    } else if (isset($_POST["delete_drink"])) { ?>
      <p>Drink Deleted Successfully!</p>
      <a href='/drinks'>Return to Menu</a>
    <?php } else { ?>
      <h2>Page Not Found</h2>
      <?php echo var_dump($drinks) ?>
      <p>Oops! It looks like this page does not exist.</p>
    <?php } ?>

  </main>
</body>


<?php include("includes/footer.php"); ?>

</html>
