<?php
include 'includes/header.php';
include 'db.php';

$sql = "SELECT lost_items.*, m.fullname AS claimer
        FROM lost_items
        LEFT JOIN members m ON lost_items.claimed_by = m.id
        WHERE lost_items.status IN ('claimed','returned')
        ORDER BY lost_items.claim_date DESC";
$result = mysqli_query($conn, $sql);
$count  = $result ? mysqli_num_rows($result) : 0;
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
  <p style="font-size:14px;color:var(--gray);margin:0;">
    <strong><?php echo $count; ?></strong> item<?php echo $count !== 1 ? 's' : ''; ?> claimed or returned
  </p>
</div>

<?php if ($count > 0): ?>
  <div class="gallery">
    <?php while ($item = mysqli_fetch_assoc($result)):
      $status_cls = strtolower($item['status']) === 'returned' ? 'badge-returned' : 'badge-claimed';
    ?>
      <div class="item-card">

        <?php if ($item['image']): ?>
          <img class="item-card-img" src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['item_type']); ?>">
        <?php else: ?>
          <div class="item-card-img-placeholder">
            <i class="fas fa-box-open"></i>
            <span>No Photo</span>
          </div>
        <?php endif; ?>

        <div class="item-card-body">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <div class="item-card-id">Item #<?php echo (int)$item['id']; ?></div>
            <span class="badge <?php echo $status_cls; ?>"><?php echo htmlspecialchars(ucfirst($item['status'])); ?></span>
          </div>

          <div class="item-card-type"><?php echo htmlspecialchars($item['item_type']); ?></div>

          <?php if (!empty($item['description'])): ?>
            <div class="item-card-desc">
              <?php echo htmlspecialchars(mb_strimwidth($item['description'], 0, 90, '…')); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($item['date_lost'])): ?>
            <div style="font-size:12px;color:var(--gray);display:flex;align-items:center;gap:5px;margin-top:4px;">
              <i class="fas fa-calendar-days" style="color:var(--gray-light);"></i>
              Lost: <?php echo date('M j, Y', strtotime($item['date_lost'])); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($item['claim_date'])): ?>
            <div style="font-size:12px;color:var(--success);display:flex;align-items:center;gap:5px;">
              <i class="fas fa-check-circle"></i>
              Claimed: <?php echo date('M j, Y', strtotime($item['claim_date'])); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($item['claimer'])): ?>
            <div class="item-card-meta" style="margin-top:8px;padding-top:10px;border-top:1px solid var(--border);">
              <div class="item-card-poster">
                <div class="item-card-poster-avatar" style="background:var(--success-bg);border-color:#BBF7D0;color:var(--success);">
                  <?php echo strtoupper(substr($item['claimer'], 0, 1)); ?>
                </div>
                <span><?php echo htmlspecialchars($item['claimer']); ?></span>
              </div>
            </div>
          <?php endif; ?>

        </div>
      </div>
    <?php endwhile; ?>
  </div>

<?php else: ?>
  <div class="empty-state" style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);">
    <div class="empty-state-icon"><i class="fas fa-box-open"></i></div>
    <h4>No claimed items yet</h4>
    <p>Items that have been successfully claimed or returned will appear here.</p>
  </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>