<?php
// setup_db.php – run once, then delete
require_once 'db.php'; // reuse your existing connection

try {
    // Create lost_items table (adjust columns to match your PHP code)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lost_items (
            id SERIAL PRIMARY KEY,
            item_name VARCHAR(255) NOT NULL,
            description TEXT,
            location VARCHAR(255),
            date_reported TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'pending',
            reported_by VARCHAR(255),
            contact_info VARCHAR(255)
        );
    ");

    // Create claims table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS claims (
            id SERIAL PRIMARY KEY,
            lost_item_id INT REFERENCES lost_items(id) ON DELETE CASCADE,
            claimant_name VARCHAR(255) NOT NULL,
            claimant_contact VARCHAR(255),
            claim_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) DEFAULT 'pending',
            additional_info TEXT
        );
    ");

    echo "✅ Tables 'lost_items' and 'claims' created successfully!";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
