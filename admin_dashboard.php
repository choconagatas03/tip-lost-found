<?php
session_start();
include 'db.php'; // Using your PDO connection
include 'includes/header.php';

// Security Check: Only let admins stay here
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: signin.php");
    exit;
}

try {
    // 1. Fetch Stats using PDO/PostgreSQL syntax
    $count_items   = (int)$pdo->query("SELECT COUNT(*) FROM lost_items")->fetchColumn();
    $count_lost    = (int)$pdo->query("SELECT COUNT(*) FROM lost_items WHERE status='lost'")->fetchColumn();
    $count_claimed = (int)$pdo->query("SELECT COUNT(*) FROM lost_items WHERE status IN ('claimed','returned')")->fetchColumn();
    $count_users   = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // 2. Fetch Recent Items (Joining with your 'users' table)
    $stmt = $pdo->query("
        SELECT li.id, li.item_type, li.status, li.created_at, u.full_name AS posted_by
        FROM lost_items li
        LEFT JOIN users u ON li.user_id = u.user_id
        ORDER BY li.created_at DESC
        LIMIT 8
    ");
    $recent_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

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

<div class="quick-actions" style="margin-bottom:28px;">
  <a href="manage_items.php" class="quick-action-card">
    <div class="qai"><i class="fas fa-plus"></i></div>
    <span>Add New Item</span>
  </a>
  <a href="manage_users.php" class="quick-action-card">
    <div class="qai"><i class="fas fa-users-cog"></i></div>
    <span>Manage Users</span>
  </a>
</div>

<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-clock" style="color:#d4af37;margin-right:8px;"></i>Recent Items</h3>
    <a href="manage_items.php" class="btn btn-secondary btn-sm">View All</a>
  </div>

  <?php if (count($recent_items) > 0): ?>
    <div class="table-wrapper">
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
          <?php foreach ($recent_items as $row): ?>
            <tr>
              <td>#<?php echo (int)$row['id']; ?></td>
              <td><?php echo htmlspecialchars($row['item_type']); ?></td>
              <td>
                <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                    <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($row['posted_by'] ?? '—'); ?></td>
              <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
              <td><a href="manage_items.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm">Edit</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state" style="padding: 20px; text-align: center;">
      <p>No recent activity found.</p>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>