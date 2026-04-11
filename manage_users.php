<?php
session_start();
include 'db.php';

// 1. Security Check FIRST
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: signin.php");
    exit;
}

// 2. Handle Actions (Promote, Demote, Delete) BEFORE ANY HTML
if (isset($_GET['action']) && isset($_GET['id'])) {
    $act = $_GET['action'];
    $id  = (int)$_GET['id'];

    try {
        if ($act === 'delete') {
            if ($id === (int)$_SESSION['user_id']) {
                header("Location: manage_users.php?msg=Cannot delete self&type=error");
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id");
            $stmt->execute(['id' => $id]);
            header("Location: manage_users.php?msg=User deleted successfully&type=success");
            exit;
        }

        if ($act === 'promote') {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = :id");
            $stmt->execute(['id' => $id]);
            header("Location: manage_users.php?msg=User promoted to admin&type=success");
            exit;
        }

        if ($act === 'demote') {
            $stmt = $pdo->prepare("UPDATE users SET role = 'student' WHERE user_id = :id");
            $stmt->execute(['id' => $id]);
            header("Location: manage_users.php?msg=User demoted to student&type=success");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: manage_users.php?msg=Error: " . urlencode($e->getMessage()) . "&type=error");
        exit;
    }
}

// 3. Fetch all users
try {
    $stmt = $pdo->query("SELECT user_id, full_name, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $user_count = count($users);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// 4. NOW include the header and start HTML output
include 'includes/header.php';

$msg  = $_GET['msg'] ?? '';
$type = $_GET['type'] ?? 'success';
?>

<?php if ($msg): ?>
    <div class="alert alert-<?php echo $type === 'error' ? 'danger' : 'success'; ?>" 
         style="padding:15px; border-radius:8px; margin-bottom:20px; background: <?php echo $type === 'error' ? '#fee2e2' : '#dcfce7'; ?>; color: <?php echo $type === 'error' ? '#991b1b' : '#166534'; ?>;">
        <i class="fas fa-<?php echo $type === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
        <span><?php echo htmlspecialchars($msg); ?></span>
    </div>
<?php endif; ?>

<div class="card" style="background:white; padding:20px; border-radius:10px; border: 1px solid #eee;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h3><i class="fas fa-users" style="color:#d4af37;margin-right:8px;"></i>All Members</h3>
        <span style="font-size:13px;color:#666;"><?php echo $user_count; ?> users</span>
    </div>

    <?php if ($user_count > 0): ?>
        <div class="table-wrapper">
            <table style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align:left; border-bottom:1px solid #eee;">
                        <th style="padding:10px;">#</th>
                        <th style="padding:10px;">Member</th>
                        <th style="padding:10px;">Email</th>
                        <th style="padding:10px;">Role</th>
                        <th style="padding:10px;">Joined</th>
                        <th style="padding:10px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr style="border-bottom: 1px solid #f9f9f9;">
                            <td style="padding:10px; font-weight:700; color:#999; font-size:12px;"><?php echo (int)$u['user_id']; ?></td>
                            <td style="padding:10px;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="width:34px; height:34px; background:#fef3c7; border:1px solid #fde68a; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#8B6914;">
                                        <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                    </div>
                                    <span style="font-weight:600;"><?php echo htmlspecialchars($u['full_name']); ?></span>
                                </div>
                            </td>
                            <td style="padding:10px; font-size:13px; color:#666;"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td style="padding:10px;">
                                <span class="badge" style="padding:4px 8px; border-radius:4px; font-size:11px; background: <?php echo $u['role'] === 'admin' ? '#dbeafe' : '#f3f4f6'; ?>;">
                                    <?php echo htmlspecialchars(ucfirst($u['role'])); ?>
                                </span>
                            </td>
                            <td style="padding:10px; font-size:13px; color:#666;">
                                <?php echo date('M j, Y', strtotime($u['created_at'])); ?>
                            </td>
                            <td style="padding:10px;">
                                <div style="display:flex; gap:8px;">
                                    <?php if ($u['role'] === 'student'): ?>
                                        <a href="manage_users.php?action=promote&id=<?php echo (int)$u['user_id']; ?>" 
                                           style="color:#2563eb; text-decoration:none; font-size:12px;" 
                                           onclick="return confirm('Promote to admin?');">Promote</a>
                                    <?php else: ?>
                                        <a href="manage_users.php?action=demote&id=<?php echo (int)$u['user_id']; ?>" 
                                           style="color:#4b5563; text-decoration:none; font-size:12px;" 
                                           onclick="return confirm('Demote to student?');">Demote</a>
                                    <?php endif; ?>

                                    <?php if ($u['user_id'] !== (int)$_SESSION['user_id']): ?>
                                        <a href="manage_users.php?action=delete&id=<?php echo (int)$u['user_id']; ?>" 
                                           style="color:#dc2626; text-decoration:none; font-size:12px;" 
                                           onclick="return confirm('Delete user?');">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align:center; padding:40px;">
            <p>No users found.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>