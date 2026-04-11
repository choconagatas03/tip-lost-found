<?php
// Railway provides these. If they are empty, getenv() returns false.
$host = getenv('PGHOST');
$port = getenv('PGPORT');
$db   = getenv('PGDATABASE');
$user = getenv('PGUSER');
$pass = getenv('PGPASSWORD'); 

// Check if variables are actually loaded to avoid the "startup packet" error
if (!$host || !$user || !$pass) {
    die("Database environment variables are missing in Railway!");
}

try {
    // Added sslmode=require which is often mandatory for cloud Postgres
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Could not connect to the database.");
}