<?php
session_start();
include 'db.php'; // Ensure this file defines $pdo

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

$_SESSION['signin_old_email'] = $email;

/* 1. Basic Validations */
if (empty($email) || empty($password)) {
    $_SESSION['signin_error'] = "Please enter both email and password.";
    header("Location: signin.php");
    exit;
}

try {
    /* 2. Check user in database - Mapping to your 'users' table */
    $sql = "SELECT user_id, full_name, password, role FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    /* 3. Verify user exists and check password */
    if ($user && password_verify($password, $user['password'])) {
        // SUCCESS: Clear old login data and set session
        unset($_SESSION['signin_old_email']);
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        // FAILURE
        $_SESSION['signin_error'] = "Invalid email or password.";
        header("Location: signin.php");
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['signin_error'] = "Database Error: " . $e->getMessage();
    header("Location: signin.php");
    exit;
}
?>