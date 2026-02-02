<?php
// 1. ENABLE ERROR REPORTING
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. DEFINE ACCESS
define('ACCESS_ALLOWED', true);

// 3. START BUFFER & SESSION
ob_start();
session_start();

// 4. INCLUDE DATABASE
if (file_exists('config/db_connect.php')) {
    require_once 'config/db_connect.php';
} else {
    die("Error: config/db_connect.php not found.");
}

// 5. CHECK CONNECTION
if (!isset($conn) || $conn->connect_error) {
    die("Database Connection Failed: " . ($conn->connect_error ?? 'Unknown Error'));
}

// 6. HANDLE LOGIN POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean();
    header("Content-Type: application/json");

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if (!$username || !$password) {
        echo json_encode(["status" => "error", "message" => "Please enter both fields"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, username, password, role, full_name, avatar, locked_until, login_attempts FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Check Lock
        if ($user['locked_until'] && new DateTime($user['locked_until']) > new DateTime()) {
            echo json_encode(["status" => "error", "message" => "Account locked. Try again later."]);
            exit;
        }

        // Verify Password
        if (password_verify($password, $user['password'])) {
            // Success: Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['avatar'] = $user['avatar'];
            
            // Reset attempts
            $conn->query("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = " . $user['id']);

            // --- [RESTORED] AUDIT LOGGING ---
            $log_action = "User Login";
            $log_details = "User logged in successfully.";
            $log_ip = $_SERVER['REMOTE_ADDR'];

            $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, username, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
            if ($log_stmt) {
                // Ensure we bind parameters correctly: integer, string, string, string, string
                $log_stmt->bind_param("issss", $user['id'], $user['username'], $log_action, $log_details, $log_ip);
                $log_stmt->execute();
                $log_stmt->close();
            }
            // --------------------------------

            echo json_encode(["status" => "success", "user" => $user]);
            exit;
        } else {
            // Fail
            $attempts = $user['login_attempts'] + 1;
            if ($attempts >= 5) {
                $lock = date("Y-m-d H:i:s", strtotime("+15 minutes"));
                $conn->query("UPDATE users SET login_attempts = $attempts, locked_until = '$lock' WHERE id = " . $user['id']);
                echo json_encode(["status" => "error", "message" => "Account locked due to too many attempts."]);
            } else {
                $conn->query("UPDATE users SET login_attempts = $attempts WHERE id = " . $user['id']);
                echo json_encode(["status" => "error", "message" => "Invalid Password. Attempts left: " . (5 - $attempts)]);
            }
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PC Project</title>
    <link rel="icon" type="image/png" href="assets/img/logopc.png">
    
    <link href="fontawesome/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="login-section">
        <div class="login-wrapper">
            <div class="brand-header">
                <img src="assets/img/logopc.png" alt="Logo">
                <div class="project-title">PC Project</div>
                <h1>Welcome Back</h1>
                <p>Enter your credentials to access the system.</p>
            </div>

            <div id="errorText" class="error-message"></div>

            <form id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fa-regular fa-user input-icon" aria-hidden="true"></i>
                        <input type="text" id="username" placeholder="e.g. admin" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" placeholder="••••••••" required>
                        <i class="fa-regular fa-eye toggle-password" onclick="togglePassword()"></i>
                    </div>
                    <div style="text-align: right; margin-top: 8px;">
                        <a href="forgot_password.php" style="font-size: 12px; color: #476eef; text-decoration: none;">Forgot Password?</a>
                    </div>
                </div>

                <button type="submit" id="btnSignIn" class="btn-login">Sign In</button>
            </form>

            <div class="footer">
                © 2025 PC Project. All rights reserved.
            </div>
        </div>
    </div>

    <div class="visual-section">
        <div class="circle c1"></div>
        <div class="circle c2"></div>
        <div class="showcase-text">
            <h2>Inventory System</h2>
            <p>Manage stocks, track builds, and handle clients<br>with efficiency and precision.</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const field = document.getElementById("password");
            const icon = document.querySelector(".toggle-password");
            if (field.type === "password") { field.type = "text"; icon.classList.replace("fa-eye", "fa-eye-slash"); } 
            else { field.type = "password"; icon.classList.replace("fa-eye-slash", "fa-eye"); }
        }

        document.getElementById("loginForm").addEventListener("submit", async function (e) {
            e.preventDefault();

            const u = document.getElementById("username").value.trim();
            const p = document.getElementById("password").value.trim();
            const err = document.getElementById("errorText");
            const btn = document.getElementById("btnSignIn");

            err.style.display = "none";
            btn.disabled = true;
            btn.innerText = "Verifying...";

            try {
                const res = await fetch('login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include', 
                    body: JSON.stringify({ username: u, password: p })
                });

                const rawText = await res.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (jsonError) {
                    alert("SERVER ERROR:\n" + rawText);
                    err.innerText = "System Error. Check console.";
                    err.style.display = "block";
                    btn.disabled = false;
                    btn.innerText = "Sign In";
                    return;
                }

                if (data.status === "success") {
                    sessionStorage.setItem("sessionToken", "active");
                    sessionStorage.setItem("userRole", data.user.role);       
                    sessionStorage.setItem("userName", data.user.full_name);
                    if (data.user.avatar) sessionStorage.setItem("userAvatar", data.user.avatar);
                    
                    // Fixed Redirection Link
                    window.location.href = "pages/dashboard.php"; 
                } else {
                    throw new Error(data.message || "Login failed");
                }

            } catch (error) {
                err.innerText = error.message;
                err.style.display = "block";
                btn.disabled = false;
                btn.innerText = "Sign In";
            }
        });
    </script>
</body>
</html>