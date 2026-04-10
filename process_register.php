<?php
session_start();
include 'db.php';

$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$gender = $_POST['gender'] ?? '';

$errors = [];
$_SESSION['register_old_data'] = $_POST;

/* Required fields */
if (!$fullname || !$email || !$phone || !$password || !$confirm_password || !$gender) {
    $errors[] = "All fields are required.";
}

/* Full name validation */
if (!preg_match("/^[A-Za-z\s]{3,50}$/", $fullname)) {
    $errors[] = "Full name must contain only letters and spaces (3-50 characters).";
}

/* Email validation */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}

/* Phone validation */
if (!preg_match('/^09\d{9}$/', $phone)) {
    $errors[] = "Phone must start with 09 and be exactly 11 digits.";
}

/* Password validation */
if (strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters.";
}

if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $password)) {
    $errors[] = "Password must contain letters and numbers.";
}

/* Confirm password */
if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

/* Check email uniqueness */
$sql_email = "SELECT id FROM members WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql_email);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    $errors[] = "Email is already registered.";
}
mysqli_stmt_close($stmt);

/* Check phone uniqueness */
$sql_phone = "SELECT id FROM members WHERE phone = ?";
$stmt = mysqli_prepare($conn, $sql_phone);
mysqli_stmt_bind_param($stmt, "s", $phone);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    $errors[] = "Phone number is already registered.";
}
mysqli_stmt_close($stmt);

/* If errors, redirect back with session */
if ($errors) {
    $_SESSION['register_errors'] = $errors;
    header("Location: register.php");
    exit;
}

/* Insert user */
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO members (fullname, email, phone, password, gender, role)
        VALUES (?, ?, ?, ?, ?, 'student')";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssss", $fullname, $email, $phone, $hashed_password, $gender);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

unset($_SESSION['register_old_data']);

/* Redirect to signin with success message */
header("Location: signin.php?registered=1");
exit;
?>