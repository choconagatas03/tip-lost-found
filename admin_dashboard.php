<?php
include 'includes/header.php';
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: signin.php");
  exit;
}

$count_items   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM lost_items"))['total'];
$count_lost    = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM lost_items WHERE status='lost'"))['total'];
$count_claimed = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM lost_items WHERE status IN ('claimed','returned')"))['total'];
$count_users   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM members"))['total'];

// Recent items
$recent_result = mysqli_query($conn, "
    SELECT lost_items.id, lost_items.item_type, lost_items.status, lost_items.created_at,
           members.fullname AS posted_by
    FROM lost_items
    LEFT JOIN members ON lost_items.user_id = members.id
    ORDER BY lost_items.created_at DESC
    LIMIT 8
");
?>

<!-- Stat Cards -->
<div class="stat-grid">

  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fas fa-boxes-stacked"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?php echo $count_items; ?></div>
      <div class="stat-label">Total Items</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-magnifying-glass"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?php echo $count_lost; ?></div>
      <div class="stat-label">Currently Lost</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-box-open"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?php echo $count_claimed; ?></div>
      <div class="stat-label">Claimed / Returned</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?php echo $count_users; ?></div>
      <div class="stat-label">Registered Users</div>
    </div>
  </div>

</div>

<!-- Quick Actions -->
<div class="quick-actions" style="margin-bottom:28px;">
  <a href="manage_items.php" class="quick-action-card">
    <div class="qai"><i class="fas fa-plus"></i></div>
    <span>Add New Item</span>
  </a>
  <a href="manage_users.php" class="quick-action-card">
    <div class="qai"><i class="fas fa-users-cog"></i></div>
    <span>Manage Users</span>
  </a>
  <a href="reports.php" class="quick-action-card">
    <div class="qai"><i class="fas fa-file-export"></i></div>
    <span>Generate Report</span>
  </a>
</div>

<!-- Recent Activity -->
<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-clock" style="color:var(--tip-gold-deep);margin-right:8px;"></i>Recent Items</h3>
    <a href="manage_items.php" class="btn btn-secondary btn-sm">View All</a>
  </div>

  <?php if ($recent_result && mysqli_num_rows($recent_result) > 0): ?>
    <div class="table-wrapper" style="border:none;border-radius:0;box-shadow:none;">
      <table>
        <thead>
          <tr>
            <th>#ID</th>
            <th>Item Type</th>
            <th>Status</th>
            <th>Posted By</th>
            <th>Date Added</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($recent_result)): ?>
            <tr>
              <td style="font-weight:700;color:var(--gray);">#<?php echo (int)$row['id']; ?></td>
              <td style="font-weight:600;"><?php echo htmlspecialchars($row['item_type']); ?></td>
              <td>
                <?php
                $s = strtolower($row['status']);
                $cls = match ($s) {
                  'lost' => 'badge-lost',
                  'claimed' => 'badge-claimed',
                  'returned' => 'badge-returned',
                  default => 'badge-student'
                };
                ?>
                <span class="badge <?php echo $cls; ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span>
              </td>
              <td><?php echo htmlspecialchars($row['posted_by'] ?? '—'); ?></td>
              <td style="color:var(--gray);font-size:13px;">
                <?php echo date('M j, Y', strtotime($row['created_at'])); ?>
              </td>
              <td>
                <a href="manage_items.php?edit=<?php echo (int)$row['id']; ?>" class="btn btn-secondary btn-sm">
                  <i class="fas fa-pencil"></i> Edit
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-state-icon"><i class="fas fa-box-open"></i></div>
      <h4>No items yet</h4>
      <p>Start by adding a lost item to the system.</p>
      <a href="manage_items.php" class="btn" style="margin-top:4px;"><i class="fas fa-plus"></i> Add Item</a>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>