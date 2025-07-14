<?php
session_start();
require_once 'utils/timezone.php';

echo "<h1>Timezone Debug</h1>";

// Simulate user session
$_SESSION['user_id'] = 1; // Assuming user ID 1 exists
$_SESSION['timezone'] = 'Asia/Jayapura'; // Set to Jayapura for testing

echo "<h2>Session Info:</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Session Timezone: " . ($_SESSION['timezone'] ?? 'Not set') . "<br>";

echo "<h2>Timezone Test:</h2>";
$userTimezone = getUserTimezone();
echo "getUserTimezone(): " . $userTimezone . "<br>";

echo "<h2>Current Time in Different Timezones:</h2>";

// Jakarta time
$jakartaTime = new DateTime();
$jakartaTime->setTimezone(new DateTimeZone('Asia/Jakarta'));
echo "Jakarta (WIB): " . $jakartaTime->format('Y-m-d H:i:s T') . "<br>";

// Jayapura time  
$jayapuraTime = new DateTime();
$jayapuraTime->setTimezone(new DateTimeZone('Asia/Jayapura'));
echo "Jayapura (WIT): " . $jayapuraTime->format('Y-m-d H:i:s T') . "<br>";

// User timezone
$userTime = new DateTime();
$userTime->setTimezone(new DateTimeZone($userTimezone));
echo "User Timezone ($userTimezone): " . $userTime->format('Y-m-d H:i:s T') . "<br>";

echo "<h2>Formatted with utility function:</h2>";
echo "formatDateTimeWithTimezone(): " . formatDateTimeWithTimezone(new DateTime(), 'Y-m-d H:i:s T') . "<br>";
?>
