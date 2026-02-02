<?php
// 1. ACCESS KEY
define('ACCESS_ALLOWED', true);

// 2. START SESSION
session_start();

// 3. LOG THE ACTION (Optional, ignores errors if DB fails)
try {
    if (file_exists('config/db_connect.php')) {
        include_once 'config/db_connect.php';
        if (isset($_SESSION['user_id']) && isset($conn)) {
            $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
            $u = $_SESSION['user_id'];
            $n = $_SESSION['name'] ?? 'User';
            $a = "Logout";
            $d = "Success";
            $i = $_SERVER['REMOTE_ADDR'];
            if ($stmt) {
                $stmt->bind_param("issss", $u, $n, $a, $d, $i);
                $stmt->execute();
            }
        }
    }
} catch (Exception $e) { }

// 4. DESTROY SESSION
$_SESSION = [];
session_destroy();

// 5. REDIRECT TO LOGIN PAGE
header("Location: login.php");
exit;
?>