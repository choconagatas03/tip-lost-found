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
// ... keep validations the same ...

/* Check email uniqueness */
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    $errors[] = "Email is already registered.";
}

/* Insert user */
if (empty($errors)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (full_name, email, phone_number, password, gender, role)
            VALUES (:name, :email, :phone, :pass, :gender, 'student')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'name'   => $fullname,
        'email'  => $email,
        'phone'  => $phone,
        'pass'   => $hashed_password,
        'gender' => $gender
    ]);

    header("Location: signin.php?registered=1");
    exit;
}
?>