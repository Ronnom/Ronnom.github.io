<?php

define('UPLOAD_DIR', '../assets/uploads/');
define('DEFAULT_AVATAR', '../assets/img/logopc.png');

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "pc_project_db";

// 1. Create Connection
$conn = new mysqli($host, $user, $pass, $dbname);

if (!defined('ACCESS_ALLOWED')) {
    die("Direct access not permitted.");
}

// 2. REMOVED THE 'DIE' COMMAND HERE
// We will check $conn->connect_error in login.php instead.
// This prevents the script from crashing before we can clean the output.

// GLOBAL LOGGING FUNCTION
if (!function_exists('logAudit')) {
    function logAudit($conn, $action, $details) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $uid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        
        $user = "System";
        if (isset($_SESSION['name'])) $user = $_SESSION['name'];
        elseif (isset($_SESSION['username'])) $user = $_SESSION['username'];
        elseif ($uid > 0) $user = "User ID $uid";

        $ip = $_SERVER['REMOTE_ADDR'];

        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $uid, $user, $action, $details, $ip);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Aliases
if (!function_exists('logAction')) {
    function logAction($conn, $action, $details) { logAudit($conn, $action, $details); }
}
if (!function_exists('logSettingsAction')) {
    function logSettingsAction($conn, $action, $details) { logAudit($conn, $action, $details); }
}