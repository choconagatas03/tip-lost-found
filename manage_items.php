<?php
// manage_items.php - PostgreSQL version
include 'includes/header.php';
require_once 'db.php';  // Should return a PDO instance

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: signin.php");
    exit;
}

$msg = $_GET['msg'] ?? '';

$edit_item = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    // PDO prepared statement
    $stmt = $pdo->prepare("SELECT * FROM lost_items WHERE id = ?");
    $stmt->execute([$id]);
    $edit_item = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Query with proper PostgreSQL joins (using users table)
$sql = "SELECT li.*, 
               u1.full_name AS posted_by_name, 
               u2.full_name AS claimer_name
        FROM lost_items li
        LEFT JOIN users u1 ON li.user_id = u1.user_id
        LEFT JOIN users u2 ON li.claimed_by = u2.user_id
        ORDER BY li.created_at DESC";
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    </div>

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
    <span style="font-size:13px;color:var(--gray);"><?php echo count($results); ?> records</span>
  </div>

  <?php if (count($results) > 0): ?>
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
          <?php foreach ($results as $row): ?>
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
          <?php endforeach; ?>
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