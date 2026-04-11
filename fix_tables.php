<?php
// fix_tables.php - Run once, then delete.
require_once 'db.php';

try {
    // Drop existing tables
    $pdo->exec("DROP TABLE IF EXISTS claims CASCADE");
    $pdo->exec("DROP TABLE IF EXISTS lost_items CASCADE");

    // Create lost_items table WITHOUT foreign keys
    $pdo->exec("
        CREATE TABLE lost_items (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL,
            description TEXT NOT NULL,
            item_type VARCHAR(100) NOT NULL,
            date_lost DATE,
            status VARCHAR(50) DEFAULT 'lost',
            image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            claimed_by INT
        )
    ");

    // Create claims table WITHOUT foreign keys
    $pdo->exec("
        CREATE TABLE claims (
            claim_id SERIAL PRIMARY KEY,
            item_id INT NOT NULL,
            claimant_user_id INT NOT NULL,
            claim_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'pending',
            admin_notes TEXT
        )
    ");

    echo "✅ Tables created successfully (without foreign key constraints). Your app should now work.";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>