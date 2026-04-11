<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: signin.php");
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $act = $_GET['action'];
    $id  = (int)$_GET['id'];

    try {
        if ($act === 'delete') {
            if ($id === (int)$_SESSION['user_id']) {
                header("Location: manage_users.php?msg=Cannot delete yourself&type=error");
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id");
            $stmt->execute(['id' => $id]);
            header("Location: manage_users.php?msg=User deleted&type=success");
            exit;
        }

        if ($act === 'promote') {
            $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = :id")->execute(['id' => $id]);
            header("Location: manage_users.php?msg=Promoted to Admin&type=success");
            exit;
        }

        if ($act === 'demote') {
            $pdo->prepare("UPDATE users SET role = 'student' WHERE user_id = :id")->execute(['id' => $id]);
            header("Location: manage_users.php?msg=Demoted to Student&type=success");
            exit;
        }
    } catch (PDOException $e) {
        $errorMsg = "Cannot delete user: They have posted items in the database.";
        header("Location: manage_users.php?msg=" . urlencode($errorMsg) . "&type=error");
        exit;
    }
}

// Fetch users
$users = $pdo->query("SELECT user_id, full_name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
include 'includes/header.php';
?>

<div class="card" style="margin-top:20px; padding:20px;">
    <h3>Manage Users</h3>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-<?php echo $_GET['type'] ?? 'info'; ?>">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <?php if (count($users) > 0): ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td class="table-actions">
                                <?php if ($user['role'] !== 'admin'): ?>
                                    <a href="manage_users.php?action=promote&id=<?php echo $user['user_id']; ?>" class="btn btn-success btn-sm">Promote</a>
                                <?php else: ?>
                                    <a href="manage_users.php?action=demote&id=<?php echo $user['user_id']; ?>" class="btn btn-warning btn-sm">Demote</a>
                                <?php endif; ?>
                                <a href="manage_users.php?action=delete&id=<?php echo $user['user_id']; ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Delete this user? All their items and claims will be lost.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">No users found.</div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>