<?php
// fix_tables.php - Run once to recreate tables correctly, then delete.
require_once 'db.php';

try {
    // Drop existing tables (order matters due to foreign keys)
    $pdo->exec("DROP TABLE IF EXISTS claims CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS lost_items CASCADE");

    // Create lost_items table matching your PHP queries
    $pdo->exec("
        CREATE TABLE lost_items (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            description TEXT NOT NULL,
            item_type VARCHAR(100) NOT NULL,
            date_lost DATE,
            status VARCHAR(50) DEFAULT 'lost',
            image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            claimed_by INT REFERENCES users(user_id) ON DELETE SET NULL
        )
    ");

    // Create claims table
    $pdo->exec("
        CREATE TABLE claims (
            claim_id SERIAL PRIMARY KEY,
            item_id INT NOT NULL REFERENCES lost_items(id) ON DELETE CASCADE,
            claimant_user_id INT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            claim_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'pending',
            admin_notes TEXT
        )
    ");

    echo "✅ Tables 'lost_items' and 'claims' recreated successfully with correct columns!";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
