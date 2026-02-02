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

                // 1. Get Raw Text First (To debug hidden errors)
                const rawText = await res.text();

                // 2. Try to Parse JSON
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (jsonError) {
                    // IF THIS RUNS, IT MEANS PHP IS PRINTING HTML ERRORS
                    alert("SERVER ERROR:\n" + rawText); // Show the raw error in a popup
                    throw new Error("Invalid Server Response");
                }

                if (data.status === "success") {
                    sessionStorage.setItem("sessionToken", "active");
                    sessionStorage.setItem("userRole", data.user.role);       
                    sessionStorage.setItem("userName", data.user.full_name);
                    if (data.user.avatar) sessionStorage.setItem("userAvatar", data.user.avatar);
                    sessionStorage.setItem("csrfToken", data.csrf_token);
                    
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