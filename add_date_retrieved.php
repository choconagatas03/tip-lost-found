<?php
require_once 'db.php';
try {
    $pdo->exec("ALTER TABLE claims ADD COLUMN IF NOT EXISTS retrieved_date DATE");
    echo "✅ Column 'retrieved_date' added.";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

