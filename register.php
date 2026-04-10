<?php
session_start();
include 'includes/header.php';

$errors = $_SESSION['register_errors'] ?? [];
$old_data = $_SESSION['register_old_data'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_old_data']);
?>

<div class="auth-card" style="max-width:1040px;">

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
      <h2>Join the<br><span>community.</span></h2>
      <p>Create your TIP Lost &amp; Found account to report lost items and help reunite the campus community with their belongings.</p>
    </div>

    <div class="auth-panel-dots">
      <span></span><span></span><span></span><span></span>
    </div>
  </div>

  <!-- Right form area -->
  <div class="auth-form-area" style="overflow-y:auto;">
    <h3>Create Account</h3>
    <p>Fill in your details to get started.</p>

    <?php if (!empty($errors)): ?>
      <div class="auth-errors" style="margin-bottom:16px;">
        <?php foreach ($errors as $err): ?>
          <p><i class="fas fa-exclamation-circle" style="margin-right:6px;"></i><?php echo htmlspecialchars($err); ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form action="process_register.php" method="POST" style="gap:14px;">

      <div class="form-group">
        <label for="fullname">Full Name</label>
        <input
          id="fullname"
          type="text"
          name="fullname"
          value="<?php echo htmlspecialchars($old_data['fullname'] ?? ''); ?>"
          placeholder="e.g. Juan dela Cruz"
          pattern="[A-Za-z\s]{3,50}"
          title="Letters and spaces only (3–50 characters)"
          autocomplete="name"
          required>
      </div>

      <div class="form-group">
        <label for="reg_email">Email Address</label>
        <input
          id="reg_email"
          type="email"
          name="email"
          value="<?php echo htmlspecialchars($old_data['email'] ?? ''); ?>"
          placeholder="you@tip.edu.ph"
          autocomplete="email"
          required>
      </div>

      <div class="form-group">
        <label for="phone">Phone Number</label>
        <input
          id="phone"
          type="tel"
          name="phone"
          value="<?php echo htmlspecialchars($old_data['phone'] ?? ''); ?>"
          placeholder="09XXXXXXXXX"
          pattern="09\d{9}"
          maxlength="11"
          title="Must start with 09 and be 11 digits"
          autocomplete="tel"
          required>
      </div>

      <div class="form-group">
        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
          <option value="">— Select Gender —</option>
          <option value="Male" <?php echo ($old_data['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
          <option value="Female" <?php echo ($old_data['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
          <option value="Other" <?php echo ($old_data['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
          <option value="Prefer Not to Say" <?php echo ($old_data['gender'] ?? '') === 'Prefer Not to Say' ? 'selected' : ''; ?>>Prefer Not to Say</option>
        </select>
      </div>

      <div class="form-group">
        <label for="reg_password">Password</label>
        <input
          id="reg_password"
          type="password"
          name="password"
          placeholder="At least 6 characters with letters &amp; numbers"
          minlength="6"
          pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&amp;]{6,}$"
          title="Must contain letters and numbers"
          autocomplete="new-password"
          required>
      </div>

      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input
          id="confirm_password"
          type="password"
          name="confirm_password"
          placeholder="Re-enter your password"
          minlength="6"
          autocomplete="new-password"
          required>
      </div>

      <button type="submit" class="auth-submit-btn" style="margin-top:6px;">
        <i class="fas fa-user-plus" style="margin-right:6px;"></i>
        Create Account
      </button>

    </form>

    <p class="auth-footer-text">Already have an account? <a href="signin.php">Sign in here</a></p>
  </div>

</div>

<script>
  document.getElementById('phone').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);
  });
</script>

<?php include 'includes/footer.php'; ?>