<?php
session_start();
include 'db.php';

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

$_SESSION['signin_old_email'] = $email;

/* Check empty fields */
if (empty($email) || empty($password)) {
    $_SESSION['signin_error'] = "Please enter both email and password.";
    header("Location: signin.php");
    exit;
}

/* Validate email format */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['signin_error'] = "Please enter a valid email address.";
    header("Location: signin.php");
    exit;
}

/* Password length validation */
if (strlen($password) < 6) {
    $_SESSION['signin_error'] = "Password must be at least 6 characters.";
    header("Location: signin.php");
    exit;
}

/* Check user in database */
$sql = "SELECT id, fullname, password, role FROM members WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $_SESSION['signin_error'] = "Database error.";
    header("Location: signin.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

/* Verify password */
if ($user && password_verify($password, $user['password'])) {

    unset($_SESSION['signin_error'], $_SESSION['signin_old_email']);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['role'] = $user['role'];

    if ($user['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: index.php");
    }

    exit;

} else {
    $_SESSION['signin_error'] = "Invalid email or password.";
    header("Location: signin.php");
    exit;
}
?>