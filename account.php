<?php
session_start();
require_once 'db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// 1. Fetch User Data
try {
    $sql = "SELECT user_id, email, full_name, phone, role, created_at, avatar, password FROM users WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// 2. Update Profile Logic (No Address)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    try {
        $up_sql = "UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?";
        $pdo->prepare($up_sql)->execute([$full_name, $phone, $user_id]);
        $message = "Profile updated successfully.";
        $user['full_name'] = $full_name;
        $user['phone'] = $phone;
    } catch (PDOException $e) { $error = "Update failed."; }
}

// 3. Change Password Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (password_verify($current, $user['password'])) {
        if ($new === $confirm && strlen($new) >= 6) {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$new_hash, $user_id]);
            $message = "Password updated!";
        } else { $error = "Passwords must match and be 6+ characters."; }
    } else { $error = "Current password incorrect."; }
}

include 'includes/header.php';
?>

<style>
    .pass-container { position: relative; display: flex; align-items: center; margin-bottom: 15px; }
    .pass-container input { width: 100%; padding-right: 40px !important; }
    .toggle-eye { position: absolute; right: 12px; cursor: pointer; color: #888; }
    .toggle-eye:hover { color: #d4af37; }
</style>

<div class="account-container" style="max-width: 1100px; margin: 40px auto; background: white; border-radius: 15px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); font-family: sans-serif;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #f8f9fa; padding-bottom: 20px;">
        <h2 style="margin: 0;"><i class="fas fa-user-shield" style="color: #d4af37; margin-right: 10px;"></i>Account Settings</h2>
        <a href="logout.php" style="text-decoration: none; color: #dc3545; font-weight: bold;">Logout</a>
    </div>

    <?php if ($message): ?><div style="padding:15px; background:#e6fffa; color:#234e52; border-left:5px solid #38b2ac; margin-bottom:20px;"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div style="padding:15px; background:#fff5f5; color:#822727; border-left:5px solid #e53e3e; margin-bottom:20px;"><?php echo $error; ?></div><?php endif; ?>

    <div style="display: grid; grid-template-columns: 250px 1fr 1fr; gap: 40px;">
        
        <div style="text-align: center; border-right: 1px solid #eee; padding-right: 20px;">
            <?php if ($user['avatar']): ?>
                <img src="uploads/avatars/<?php echo $user['avatar']; ?>" style="width:160px; height:160px; border-radius:50%; object-fit:cover; border:4px solid #fff; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
            <?php else: ?>
                <div style="width:160px; height:160px; border-radius:50%; background:#fef3c7; display:flex; align-items:center; justify-content:center; font-size:50px; color:#8B6914; margin:0 auto; border:4px solid #fff; box-shadow:0 4px 10px rgba(0,0,0,0.1);"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" style="margin-top:15px;">
                <input type="file" name="avatar" style="font-size:11px; margin-bottom:10px; width:100%;">
                <button type="submit" name="upload_avatar" style="width:100%; padding:8px; background:#1a1a1a; color:white; border:none; border-radius:6px; cursor:pointer;">Update Photo</button>
            </form>
        </div>

        <div>
            <h4 style="color:#d4af37; margin-bottom:15px;">PROFILE INFO</h4>
            <form method="POST">
                <label style="display:block; font-size:11px; font-weight:bold; color:#888;">FULL NAME</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; margin-bottom:15px;" required>
                
                <label style="display:block; font-size:11px; font-weight:bold; color:#888;">EMAIL (Read-only)</label>
                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" style="width:100%; padding:10px; border:1px solid #eee; border-radius:8px; background:#f9f9f9; margin-bottom:15px;" disabled>
                
                <label style="display:block; font-size:11px; font-weight:bold; color:#888;">PHONE</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; margin-bottom:20px;">
                
                <button type="submit" name="update_profile" style="width:100%; padding:12px; background:#d4af37; color:white; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">Save Changes</button>
            </form>
        </div>

        <div>
            <h4 style="color:#d4af37; margin-bottom:15px;">SECURITY</h4>
            <form method="POST">
                <label style="display:block; font-size:11px; font-weight:bold; color:#888;">CURRENT PASSWORD</label>
                <div class="pass-container">
                    <input type="password" name="current_password" class="toggle-input" required style="padding:10px; border:1px solid #ddd; border-radius:8px;">
                    <i class="fas fa-eye toggle-eye"></i>
                </div>

                <label style="display:block; font-size:11px; font-weight:bold; color:#888;">NEW PASSWORD</label>
                <div class="pass-container">
                    <input type="password" name="new_password" class="toggle-input" required style="padding:10px; border:1px solid #ddd; border-radius:8px;">
                    <i class="fas fa-eye toggle-eye"></i>
                </div>

                <label style="display:block; font-size:11px; font-weight:bold; color:#888;">CONFIRM NEW</label>
                <div class="pass-container">
                    <input type="password" name="confirm_password" class="toggle-input" required style="padding:10px; border:1px solid #ddd; border-radius:8px;">
                    <i class="fas fa-eye toggle-eye"></i>
                </div>
                
                <button type="submit" name="change_password" style="width:100%; padding:12px; background:#1a1a1a; color:white; border:none; border-radius:8px; font-weight:bold; cursor:pointer;">Update Password</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.toggle-eye').forEach(eye => {
        eye.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });
</script>

<?php include 'includes/footer.php'; ?>