<?php
require_once __DIR__.'/config/database.php';

try {
    $pdo = connectDB();
    
    echo "=== CHECKING SCHEDULES TABLE STRUCTURE ===\n";
    
    // Check if schedules table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'schedules'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "❌ Schedules table does not exist!\n";
        exit;
    }
    
    echo "✅ Schedules table exists\n\n";
    
    // Check table structure
    $stmt = $pdo->prepare("DESCRIBE schedules");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "=== SCHEDULES TABLE COLUMNS ===\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']}\n";
    }
    
    echo "\n=== SAMPLE DATA ===\n";
    $stmt = $pdo->prepare("SELECT * FROM schedules LIMIT 3");
    $stmt->execute();
    $samples = $stmt->fetchAll();
    
    if (empty($samples)) {
        echo "No data in schedules table\n";
    } else {
        foreach ($samples as $sample) {
            echo "ID: {$sample['id']}, Care Type: {$sample['care_type']}, Pet ID: {$sample['pet_id']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
