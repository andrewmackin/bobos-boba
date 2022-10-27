<?php
include("includes/init.php");
$nav_menu_class = "current-page";
?>

<!DOCTYPE html>
<html lang="en">

<?php include("includes/head.php");

$tags_query = "SELECT tag FROM tags";
$result = exec_sql_query($db, $tags_query);
$tags = $result->fetchAll();

$sticky_search = '';
$appending_query = NULL;
$sticky_filters = array();

foreach ($tags as $tag) {
  $tag_name = str_replace(' ', '-', strtolower($tag['tag']));
  if ($_GET[$tag_name] == '1') {
    $sticky_filters[$tag_name] = 'checked';
    if ($appending_query == NULL) {
      $appending_query = " INNER JOIN drink_tags ON drinks.id = drink_tags.drink_id LEFT OUTER JOIN tags ON drink_tags.tag_id = tags.id WHERE (tags.tag = '" .  $tag['tag'] . "')";
    } else {
      $appending_query .= " OR (tags.tag = '" . $tag['tag'] . "')";
    }
  } else {
    $sticky_filters[$tag_name] = '';
  }
}

$search_terms = NULL;
$select_params = array();

if (isset($_GET['q'])) {
  $search_terms = trim($_GET['q']);
  $sticky_search = $search_terms ?? ''; // untrusted

  if (empty($search_terms)) {
    $search_terms = NULL;
  }

  if ($appending_query) {
    $appending_query .= ' AND ';
  } else {
    $appending_query = ' WHERE ';
  }

  $appending_query .= "(drink_name LIKE '%' || :search || '%') OR (drink_desc LIKE '%' || :search || '%')";
  $select_params[':search'] = $search_terms;
}

$sql_query = "SELECT DISTINCT drinks.id, drinks.drink_name, drinks.img_ext FROM drinks";
$sql_query .= $appending_query ?? '';
?>

<body>
  <?php include("includes/header.php"); ?>

  <main>
    <ul class="banner">
      <form class="filter" action="/drinks" method="get" novalidate>
        <li class="left">
          <?php
          $first = true;
          foreach ($tags as $tag) {
            if ($first) $first = false;
            else { ?> <a class="dividing-bar"> | </a> <?php } ?>
            <label class='tag-filter-box'>
              <?php $tag_name = str_replace(' ', '-', strtolower($tag['tag'])); ?>
              <input type="checkbox" id=<?php echo $tag_name; ?> name=<?php echo  $tag_name ?> value="1" <?php echo htmlspecialchars($sticky_filters[$tag_name]); ?>
              class="filter-box"/>
              <?php echo htmlspecialchars($tag['tag']); ?>
            </label>

          <?php } ?>
          <button type="submit" class="filter-button">Filter</button>
        </li>
      </form>

      <li class="right">
        <form>
          <input aria-label="Search" placeholder="Search" id="search" type="text" name="q" required value="<?php echo htmlspecialchars($sticky_search); ?>"
          class="search-input"/>
          <button type="submit">Search</button>
        </form>
      </li>
    </ul>


    <section class='drink-catalog'>
      <?php
      $result = exec_sql_query($db, $sql_query, $select_params);
      $drinks = $result->fetchAll();
      ?>
      <div class='catalog'>
        <?php if (is_user_logged_in($current_user)) { ?>
          <div class='catalog-tile'>
            <a href=<?php echo 'drink-details?' . http_build_query(array('mode' => 'add')); ?>>
              <!-- Citation: This was my own drawing -->
              <img class='tag-image add-image' src='public/uploads/add-drink.png' alt='Add Drink'>
            </a>
          </div>
        <?php
        }
        foreach ($drinks as $drink) { ?>

          <div class='catalog-tile'>
            <?php
            $drink_url = '/drink-details?drink=' . str_replace(' ', '-', strtolower($drink['drink_name']));
            $drink_url = '/drink-details?' . http_build_query(array('mode' => 'view', 'id' => $drink['id']));
            ?>
            <a href='<?php echo htmlspecialchars($drink_url) ?>'>
              <?php $image_path = is_null($drink['img_ext'])
                ? 'public/uploads/default-image.jpg' : 'public/uploads/drinks/' . $drink['id'] . '.' . $drink['img_ext']; ?>
              <!-- Citation: This was my own drawing -->
              <img class='tag-image' src=<?php echo htmlspecialchars($image_path); ?> alt=<?php echo htmlspecialchars($drink['drink_name']) ?>>
            </a>
            <div>
              <a href='<?php echo htmlspecialchars($drink_url) ?>' class='tile-text'><?php echo htmlspecialchars($drink['drink_name']); ?></a>
            </div>
          </div>
        <?php } ?>
      </div>
    </section>

  </main>

  <?php include("includes/footer.php"); ?>
</body>

</html>
