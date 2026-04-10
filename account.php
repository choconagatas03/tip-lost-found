<?php
include 'includes/header.php';
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$profile_msg = '';
$profile_type = '';
$password_msg = '';
$password_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        if ($fullname && $phone) {
            $sql = "UPDATE members SET fullname=?, phone=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $fullname, $phone, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['fullname'] = $fullname;
            $profile_msg  = "Profile updated successfully.";
            $profile_type = "success";
        } else {
            $profile_msg  = "Please fill in all required fields.";
            $profile_type = "danger";
        }
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_new_password'] ?? '';

        $sql = "SELECT password FROM members WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($row && password_verify($current, $row['password'])) {
            if (strlen($new) >= 6 && $new === $confirm) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $sql2 = "UPDATE members SET password=? WHERE id=?";
                $stmt2 = mysqli_prepare($conn, $sql2);
                mysqli_stmt_bind_param($stmt2, "si", $hash, $user_id);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
                $password_msg  = "Password changed successfully.";
                $password_type = "success";
            } else {
                $password_msg  = "New password must be at least 6 characters and both entries must match.";
                $password_type = "danger";
            }
        } else {
            $password_msg  = "Current password is incorrect.";
            $password_type = "danger";
        }
    }
}

$sql = "SELECT id, fullname, email, phone, gender, role, created_at FROM members WHERE id=?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$initial = strtoupper(substr($user['fullname'], 0, 1));
?>

<!-- Profile hero -->
<div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:24px 28px;margin-bottom:22px;display:flex;align-items:center;gap:20px;box-shadow:var(--shadow-sm);">
    <div style="width:64px;height:64px;background:var(--tip-gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;color:var(--ink);flex-shrink:0;box-shadow:var(--shadow-gold);">
        <?php echo $initial; ?>
    </div>
    <div>
        <h2 style="font-family:var(--font-display);font-size:20px;font-weight:800;color:var(--ink);letter-spacing:-0.02em;margin-bottom:3px;">
            <?php echo htmlspecialchars($user['fullname']); ?>
        </h2>
        <p style="font-size:13px;color:var(--gray);margin:0;">
            <?php echo htmlspecialchars($user['email']); ?> &nbsp;·&nbsp;
            <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : 'badge-student'; ?>">
                <?php echo ucfirst($user['role']); ?>
            </span>
        </p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

    <!-- Profile Form -->
    <div class="form-section" style="margin-bottom:0;">
        <h3><i class="fas fa-user-pen"></i> Edit Profile</h3>

        <?php if ($profile_msg): ?>
            <div class="alert alert-<?php echo $profile_type; ?>" style="margin-bottom:18px;">
                <i class="fas fa-<?php echo $profile_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($profile_msg); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
            </div>

            <div class="form-group" style="margin-top:14px;">
                <label>Email Address</label>
                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                <p style="font-size:11px;color:var(--gray-light);margin-top:5px;font-weight:500;">Email cannot be changed.</p>
            </div>

            <div class="form-group" style="margin-top:14px;">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            </div>

            <div class="form-group" style="margin-top:14px;">
                <label>Gender</label>
                <input type="text" value="<?php echo htmlspecialchars($user['gender'] ?? '—'); ?>" disabled>
            </div>

            <button type="submit" name="update_profile" style="margin-top:20px;">
                <i class="fas fa-floppy-disk"></i> Save Changes
            </button>
        </form>
    </div>

    <!-- Password Form -->
    <div class="form-section" style="margin-bottom:0;">
        <h3><i class="fas fa-lock"></i> Change Password</h3>

        <?php if ($password_msg): ?>
            <div class="alert alert-<?php echo $password_type; ?>" style="margin-bottom:18px;">
                <i class="fas fa-<?php echo $password_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($password_msg); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" placeholder="Enter your current password" required>
            </div>

            <div class="form-group" style="margin-top:14px;">
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="At least 6 characters" minlength="6" required>
            </div>

            <div class="form-group" style="margin-top:14px;">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_new_password" placeholder="Repeat new password" minlength="6" required>
            </div>

            <button type="submit" name="change_password" style="margin-top:20px;">
                <i class="fas fa-key"></i> Update Password
            </button>
        </form>
    </div>

</div>

<style>
    @media (max-width: 768px) {
        div[style*="grid-template-columns:1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>