<?php
include 'includes/header.php';
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: signin.php");
  exit;
}

$msg = $_GET['msg'] ?? '';

$edit_item = null;
if (isset($_GET['edit'])) {
  $id = (int)$_GET['edit'];
  $stmt = mysqli_prepare($conn, "SELECT * FROM lost_items WHERE id=?");
  mysqli_stmt_bind_param($stmt, "i", $id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $edit_item = mysqli_fetch_assoc($res);
  mysqli_stmt_close($stmt);
}

$sql = "SELECT lost_items.*, m1.fullname AS posted_by_name, m2.fullname AS claimer_name
        FROM lost_items
        LEFT JOIN members m1 ON lost_items.user_id = m1.id
        LEFT JOIN members m2 ON lost_items.claimed_by = m2.id
        ORDER BY lost_items.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<?php if ($msg): ?>
  <div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <span><?php echo htmlspecialchars($msg); ?></span>
  </div>
<?php endif; ?>

<!-- Add / Edit Form -->
<div class="form-section">
  <h3>
    <i class="fas <?php echo $edit_item ? 'fa-pencil' : 'fa-plus-circle'; ?>"></i>
    <?php echo $edit_item ? 'Edit Item' : 'Add New Item'; ?>
  </h3>

  <form action="process_item.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="<?php echo $edit_item ? 'update' : 'add'; ?>">
    <?php if ($edit_item): ?>
      <input type="hidden" name="id" value="<?php echo (int)$edit_item['id']; ?>">
    <?php endif; ?>

    <div class="form-grid">

      <div class="form-group full-width">
        <label>Description</label>
        <textarea name="description" rows="3" placeholder="Describe the item in detail..." required><?php echo htmlspecialchars($edit_item['description'] ?? ''); ?></textarea>
      </div>

      <div class="form-group">
        <label>Item Type</label>
        <input type="text" name="item_type" value="<?php echo htmlspecialchars($edit_item['item_type'] ?? ''); ?>" placeholder="e.g. Phone, Wallet, Bag" required>
      </div>

      <div class="form-group">
        <label>Date Lost</label>
        <input type="date" name="date_lost" value="<?php echo htmlspecialchars($edit_item['date_lost'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <?php foreach (['lost' => 'Lost', 'claimed' => 'Claimed', 'returned' => 'Returned'] as $val => $label):
            $sel = (($edit_item['status'] ?? 'lost') === $val) ? 'selected' : '';
          ?>
            <option value="<?php echo $val; ?>" <?php echo $sel; ?>><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Item Image</label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/gif">
        <?php if (!empty($edit_item['image'])): ?>
          <div style="margin-top:10px;display:flex;align-items:center;gap:10px;">
            <img src="uploads/<?php echo htmlspecialchars($edit_item['image']); ?>" alt="Current" style="width:64px;height:64px;object-fit:cover;border-radius:var(--r-sm);border:1px solid var(--border);">
            <span style="font-size:12px;color:var(--gray);">Current image — upload a new one to replace it</span>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /form-grid -->

    <div style="display:flex;gap:10px;margin-top:8px;">
      <button type="submit" class="btn-primary">
        <i class="fas <?php echo $edit_item ? 'fa-floppy-disk' : 'fa-plus'; ?>"></i>
        <?php echo $edit_item ? 'Save Changes' : 'Add Item'; ?>
      </button>
      <?php if ($edit_item): ?>
        <a href="manage_items.php" class="btn btn-secondary"><i class="fas fa-xmark"></i> Cancel</a>
      <?php endif; ?>
    </div>

  </form>
</div>

<!-- Items Table -->
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-clipboard-list" style="color:var(--tip-gold-deep);margin-right:8px;"></i>All Items</h3>
    <span style="font-size:13px;color:var(--gray);"><?php echo $result ? mysqli_num_rows($result) : 0; ?> records</span>
  </div>

  <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <div class="table-wrapper" style="border:none;border-radius:0;box-shadow:none;">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Image</th>
            <th>Description</th>
            <th>Type</th>
            <th>Status</th>
            <th>Posted By</th>
            <th>Claimed By</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td style="font-weight:700;color:var(--gray-light);font-size:12px;">#<?php echo (int)$row['id']; ?></td>
              <td>
                <?php if ($row['image']): ?>
                  <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="Item">
                <?php else: ?>
                  <div style="width:48px;height:48px;background:var(--surface);border-radius:var(--r-xs);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--gray-light);font-size:18px;">
                    <i class="fas fa-image"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td style="max-width:200px;">
                <span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:13px;">
                  <?php echo htmlspecialchars($row['description']); ?>
                </span>
              </td>
              <td style="font-weight:600;"><?php echo htmlspecialchars($row['item_type']); ?></td>
              <td>
                <?php
                $s = strtolower($row['status']);
                $cls = match ($s) {
                  'lost' => 'badge-lost',
                  'claimed' => 'badge-claimed',
                  'returned' => 'badge-returned',
                  default => ''
                };
                ?>
                <span class="badge <?php echo $cls; ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span>
              </td>
              <td style="font-size:13px;"><?php echo htmlspecialchars($row['posted_by_name'] ?? '—'); ?></td>
              <td style="font-size:13px;"><?php echo htmlspecialchars($row['claimer_name'] ?? '—'); ?></td>
              <td>
                <div class="table-actions">
                  <a href="manage_items.php?edit=<?php echo (int)$row['id']; ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-pencil"></i>
                  </a>
                  <a href="delete_item.php?id=<?php echo (int)$row['id']; ?>"
                    class="btn btn-danger btn-sm"
                    onclick="return confirm('Delete this item? This cannot be undone.');">
                    <i class="fas fa-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-state-icon"><i class="fas fa-clipboard"></i></div>
      <h4>No items recorded yet</h4>
      <p>Use the form above to add your first lost item entry.</p>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>