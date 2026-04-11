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
            // Logic: Attempt delete, catch foreign key error if items exist
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
        // Specifically handling the Foreign Key error (Postgres code 23503)
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
        <div class="alert"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>
    </div>
<?php include 'includes/footer.php'; ?>