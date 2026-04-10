<?php
include 'includes/header.php';
include 'db.php';

$type      = trim($_GET['item_type'] ?? '');
$date_lost = trim($_GET['date_lost'] ?? '');
$q         = trim($_GET['q'] ?? '');

$where = "WHERE lost_items.status = 'lost'";

if ($type !== '') {
  $safe = mysqli_real_escape_string($conn, $type);
  $where .= " AND lost_items.item_type LIKE '%$safe%'";
}
if ($date_lost !== '') {
  $safe = mysqli_real_escape_string($conn, $date_lost);
  $where .= " AND lost_items.date_lost = '$safe'";
}
if ($q !== '') {
  $safe = mysqli_real_escape_string($conn, $q);
  $where .= " AND (lost_items.description LIKE '%$safe%' OR lost_items.item_type LIKE '%$safe%')";
}

$sql = "SELECT lost_items.*, members.fullname AS posted_by
        FROM lost_items
        JOIN members ON lost_items.user_id = members.id
        $where
        ORDER BY lost_items.created_at DESC";
$result = mysqli_query($conn, $sql);
$count = $result ? mysqli_num_rows($result) : 0;
?>

<!-- Filters -->
<div class="filters-bar">
  <form method="GET" style="display:contents;">

    <div class="filter-group" style="flex:2;min-width:180px;">
      <label>Search</label>
      <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Keyword — description or type">
    </div>

    <div class="filter-group">
      <label>Item Type</label>
      <input type="text" name="item_type" value="<?php echo htmlspecialchars($type); ?>" placeholder="e.g. Phone, Wallet">
    </div>

    <div class="filter-group">
      <label>Date Lost</label>
      <input type="date" name="date_lost" value="<?php echo htmlspecialchars($date_lost); ?>">
    </div>

    <div class="filter-actions">
      <button type="submit"><i class="fas fa-search"></i> Search</button>
      <a href="index.php" class="btn btn-secondary"><i class="fas fa-xmark"></i> Clear</a>
    </div>

  </form>
</div>

<!-- Result count -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
  <p style="font-size:14px;color:var(--gray);margin:0;">
    <?php if ($count > 0): ?>
      Showing <strong><?php echo $count; ?></strong> lost item<?php echo $count !== 1 ? 's' : ''; ?>
      <?php if ($q || $type || $date_lost): ?> matching your filters<?php endif; ?>
      <?php else: ?>
        No items found
      <?php endif; ?>
  </p>
</div>

<!-- Gallery -->
<?php if ($count > 0): ?>
  <div class="gallery">
    <?php while ($item = mysqli_fetch_assoc($result)):
      $initial = strtoupper(substr($item['posted_by'], 0, 1));
    ?>
      <div class="item-card" style="animation-delay:<?php echo (rand(0, 3) * 60); ?>ms;">

        <?php if ($item['image']): ?>
          <img class="item-card-img" src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['item_type']); ?>">
        <?php else: ?>
          <div class="item-card-img-placeholder">
            <i class="fas fa-image"></i>
            <span>No Photo</span>
          </div>
        <?php endif; ?>

        <div class="item-card-body">
          <div class="item-card-id">Item #<?php echo (int)$item['id']; ?></div>
          <div class="item-card-type"><?php echo htmlspecialchars($item['item_type']); ?></div>

          <?php if (!empty($item['description'])): ?>
            <div class="item-card-desc">
              <?php echo htmlspecialchars(mb_strimwidth($item['description'], 0, 90, '…')); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($item['date_lost'])): ?>
            <div style="font-size:12px;color:var(--gray);display:flex;align-items:center;gap:5px;margin-top:4px;">
              <i class="fas fa-calendar-days" style="color:var(--tip-gold-deep);"></i>
              Lost on <?php echo date('M j, Y', strtotime($item['date_lost'])); ?>
            </div>
          <?php endif; ?>

          <div class="item-card-meta">
            <div class="item-card-poster">
              <div class="item-card-poster-avatar"><?php echo $initial; ?></div>
              <span><?php echo htmlspecialchars($item['posted_by']); ?></span>
            </div>
            <span class="badge badge-lost">Lost</span>
          </div>
        </div>

      </div>
    <?php endwhile; ?>
  </div>

<?php else: ?>
  <div class="empty-state" style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);">
    <div class="empty-state-icon"><i class="fas fa-search"></i></div>
    <h4>No lost items found</h4>
    <p><?php echo ($q || $type || $date_lost) ? 'Try adjusting your filters or clearing the search.' : 'No lost items have been reported yet.'; ?></p>
    <?php if ($q || $type || $date_lost): ?>
      <a href="index.php" class="btn" style="margin-top:4px;"><i class="fas fa-xmark"></i> Clear Filters</a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>