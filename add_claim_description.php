<?php
require_once 'db.php';

try {
    $pdo->exec("ALTER TABLE claims ADD COLUMN IF NOT EXISTS claim_description TEXT");
    echo "✅ Column 'claim_description' added to claims table.";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>