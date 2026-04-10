<?php
include 'includes/header.php';
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: signin.php");
  exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
  $act = $_GET['action'];
  $id  = (int)$_GET['id'];

  if ($act === 'delete') {
    if ($id === (int)$_SESSION['user_id']) {
      header("Location: manage_users.php?msg=Cannot delete self&type=error");
      exit;
    }
    $stmt = mysqli_prepare($conn, "DELETE FROM members WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: manage_users.php?msg=User deleted successfully&type=success");
    exit;
  }
  if ($act === 'promote') {
    $stmt = mysqli_prepare($conn, "UPDATE members SET role='admin' WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: manage_users.php?msg=User promoted to admin&type=success");
    exit;
  }
  if ($act === 'demote') {
    $stmt = mysqli_prepare($conn, "UPDATE members SET role='student' WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: manage_users.php?msg=User demoted to student&type=success");
    exit;
  }
}

$result = mysqli_query($conn, "SELECT id, fullname, email, phone, role, created_at FROM members ORDER BY created_at DESC");
$msg  = $_GET['msg'] ?? '';
$type = $_GET['type'] ?? 'success';
?>

<?php if ($msg): ?>
  <div class="alert alert-<?php echo $type === 'error' ? 'danger' : 'success'; ?>">
    <i class="fas fa-<?php echo $type === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
    <span><?php echo htmlspecialchars($msg); ?></span>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3><i class="fas fa-users" style="color:var(--tip-gold-deep);margin-right:8px;"></i>All Members</h3>
    <?php if ($result): ?>
      <span style="font-size:13px;color:var(--gray);"><?php echo mysqli_num_rows($result); ?> users</span>
    <?php endif; ?>
  </div>

  <?php if ($result && mysqli_num_rows($result) > 0): ?>
    <div class="table-wrapper" style="border:none;border-radius:0;box-shadow:none;">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Member</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($u = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td style="font-weight:700;color:var(--gray-light);font-size:12px;"><?php echo (int)$u['id']; ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px;">
                  <div style="width:34px;height:34px;background:var(--tip-gold-muted);border:1px solid var(--tip-gold-border);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#8B6914;flex-shrink:0;">
                    <?php echo strtoupper(substr($u['fullname'], 0, 1)); ?>
                  </div>
                  <span style="font-weight:600;"><?php echo htmlspecialchars($u['fullname']); ?></span>
                </div>
              </td>
              <td style="font-size:13px;color:var(--gray);"><?php echo htmlspecialchars($u['email']); ?></td>
              <td style="font-size:13px;"><?php echo htmlspecialchars($u['phone']); ?></td>
              <td>
                <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-admin' : 'badge-student'; ?>">
                  <?php echo htmlspecialchars(ucfirst($u['role'])); ?>
                </span>
              </td>
              <td style="font-size:13px;color:var(--gray);">
                <?php echo date('M j, Y', strtotime($u['created_at'])); ?>
              </td>
              <td>
                <div class="table-actions">
                  <?php if ($u['role'] === 'student'): ?>
                    <a href="manage_users.php?action=promote&id=<?php echo (int)$u['id']; ?>"
                      class="btn btn-info btn-sm"
                      onclick="return confirm('Promote this user to admin?');">
                      <i class="fas fa-arrow-up"></i> Promote
                    </a>
                  <?php else: ?>
                    <a href="manage_users.php?action=demote&id=<?php echo (int)$u['id']; ?>"
                      class="btn btn-secondary btn-sm"
                      onclick="return confirm('Demote this admin to student?');">
                      <i class="fas fa-arrow-down"></i> Demote
                    </a>
                  <?php endif; ?>

                  <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                    <a href="manage_users.php?action=delete&id=<?php echo (int)$u['id']; ?>"
                      class="btn btn-danger btn-sm"
                      onclick="return confirm('Delete this user? This cannot be undone.');">
                      <i class="fas fa-trash"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-state-icon"><i class="fas fa-users-slash"></i></div>
      <h4>No users found</h4>
      <p>No registered members yet.</p>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>