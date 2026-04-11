<?php
// On Railway, these variables are injected automatically via the Environment
$host = getenv('PGHOST') ?: 'postgres.railway.internal';
$port = getenv('PGPORT') ?: '5432';
$db   = getenv('PGDATABASE') ?: 'railway';
$user = getenv('PGUSER') ?: 'postgres';
$pass = getenv('PGPASSWORD') ?: 'ckUGRXeXkugIZOfsNqTWLfucyoqWrtGL'; 

try {
    // Note the change to 'pgsql' for Railway's database
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // We log the error internally but show a generic message to users for security
    error_log("Connection failed: " . $e->getMessage());
    die("Could not connect to the database. Please try again later.");
}
?>