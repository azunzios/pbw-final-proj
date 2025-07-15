<?php
// Debug script to test schedules functionality
session_start();

// Change to the correct directory for includes
chdir(__DIR__);

// Mock a user session for testing (replace with actual user ID from your database)
$_SESSION['user_id'] = 1; // Change this to an actual user ID
$_SESSION['timezone'] = 'Asia/Jakarta';

// Set GET parameters for API
$startDate = date('Y-m-d'); // Today
$endDate = date('Y-m-d', strtotime('+6 days')); // Next 6 days

$_GET['start'] = $startDate;
$_GET['end'] = $endDate;

echo "Testing get-schedules.php API\n";
echo "Date range: $startDate to $endDate\n";
echo "User ID: " . $_SESSION['user_id'] . "\n\n";

// First check if user exists
try {
    require_once 'config/database.php';
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "Error: User ID {$_SESSION['user_id']} not found in database\n";
        echo "Available users:\n";
        $stmt = $pdo->query("SELECT id, username FROM users LIMIT 5");
        while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- ID: {$u['id']}, Username: {$u['username']}\n";
        }
        exit;
    }
    
    echo "Testing with user: {$user['username']} (ID: {$user['id']})\n\n";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit;
}

// Now test the API by changing to api directory
chdir('api');
ob_start();
include 'get-schedules.php';
$output = ob_get_clean();
chdir('..');

echo "API Response:\n";
echo $output . "\n\n";

// Parse the JSON response
$jsonStart = strpos($output, '{"success"');
if ($jsonStart !== false) {
    $jsonOutput = substr($output, $jsonStart);
    $data = json_decode($jsonOutput, true);
    
    if ($data && isset($data['schedules'])) {
        echo "Found " . count($data['schedules']) . " schedules:\n";
        foreach ($data['schedules'] as $schedule) {
            echo "- ID: {$schedule['id']}, Instance ID: " . ($schedule['instance_id'] ?? 'NULL') . ", Date: {$schedule['date']}, Time: {$schedule['schedule_time']}, Type: {$schedule['care_type']}, Pet: {$schedule['pet_name']}, Status: {$schedule['status']}\n";
        }
    } else {
        echo "No schedules found or API error\n";
        if ($data && isset($data['message'])) {
            echo "Error message: " . $data['message'] . "\n";
        }
    }
} else {
    echo "Could not find JSON in API response\n";
}
?>
