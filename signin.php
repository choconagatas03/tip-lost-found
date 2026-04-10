<?php
session_start();

$success_message = '';
if (isset($_GET['registered'])) {
    $success_message = "Registration successful! Please sign in to continue.";
}

$error = $_SESSION['signin_error'] ?? '';
$old_email = $_SESSION['signin_old_email'] ?? '';

unset($_SESSION['signin_error'], $_SESSION['signin_old_email']);

include 'includes/header.php';
?>

<div class="auth-card">

    <!-- Left panel -->
    <div class="auth-panel">
        <div class="auth-panel-logo">
            <div class="auth-panel-logo-icon"><i class="fas fa-thumbtack"></i></div>
            <div class="auth-panel-logo-text">
                <strong>TIP Manila</strong>
                <span>Lost &amp; Found System</span>
            </div>
        </div>

        <div class="auth-panel-content">
            <h2>Find what<br>was <span>lost.</span></h2>
            <p>The Technological Institute of the Philippines Manila's official lost and found portal. Report, track, and recover lost items on campus.</p>
        </div>

        <div class="auth-panel-dots">
            <span></span><span></span><span></span><span></span>
        </div>
    </div>

    <!-- Right form area -->
    <div class="auth-form-area">
        <h3>Welcome back</h3>
        <p>Sign in to access the Lost &amp; Found portal.</p>

        <?php if ($success_message): ?>
            <div class="alert alert-success" style="margin-bottom:16px;">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:16px;">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="process_signin.php" method="POST">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?php echo htmlspecialchars($old_email); ?>"
                    placeholder="you@tip.edu.ph"
                    autocomplete="email"
                    required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    minlength="6"
                    autocomplete="current-password"
                    required>
            </div>

            <button type="submit" class="auth-submit-btn">
                <i class="fas fa-arrow-right-to-bracket" style="margin-right:6px;"></i>
                Sign In
            </button>

        </form>

        <p class="auth-footer-text">Don't have an account? <a href="register.php">Create one here</a></p>
    </div>

</div>

<?php include 'includes/footer.php'; ?>