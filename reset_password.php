<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="icon" type="image/png" href="assets/img/logopc.png">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7fe; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; }
        h2 { color: #2b3674; margin-bottom: 20px; }
        
        .input-group { position: relative; margin-bottom: 15px; }
        input { width: 100%; padding: 12px 40px 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; box-sizing: border-box; font-family: 'Inter'; }
        input:focus { border-color: #476eef; outline: none; }
        
        .toggle-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #a3aed0; cursor: pointer; }

        button { width: 100%; padding: 12px; background: #05cd99; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; font-size: 14px; transition: 0.2s; }
        button:hover { background: #04b083; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(5, 205, 153, 0.3); }

        /* Error Text Style */
        .error-text { color: #ee5d50; font-size: 13px; margin-top: 10px; display: none; }
        .success-text { color: #05cd99; font-size: 13px; margin-top: 10px; display: none; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Set New Password</h2>
        <div class="input-group">
            <input type="password" id="newPass" placeholder="New Password" required>
            <i class="fa-solid fa-eye toggle-icon" onclick="togglePass('newPass', this)"></i>
        </div>
        <div class="input-group">
            <input type="password" id="confirmPass" placeholder="Confirm Password" required>
            <i class="fa-solid fa-eye toggle-icon" onclick="togglePass('confirmPass', this)"></i>
        </div>

        <button onclick="doReset()">Change Password</button>
        
        <div id="msg" class="error-text"></div>
    </div>

    <script>
        // Get Token from URL
        const params = new URLSearchParams(window.location.search);
        const token = params.get("token");

        if (!token) {
            document.body.innerHTML = "<h3 style='text-align:center; color:#ee5d50; font-family:Inter;'>Invalid or Missing Token</h3>";
        }

        // Toggle Visibility
        function togglePass(id, icon) {
            const field = document.getElementById(id);
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // --- CLIENT SIDE CHECK ---
        function isStrong(password) {
            if (password.length < 8) return "Password must be at least 8 characters long.";
            if (!/[0-9]/.test(password)) return "Password must contain at least one number.";
            if (!/[A-Z]/.test(password)) return "Password must contain at least one uppercase letter.";
            return true;
        }

        async function doReset() {
            const pass = document.getElementById("newPass").value;
            const confirm = document.getElementById("confirmPass").value;
            const msg = document.getElementById("msg");
            
            // Clear previous messages
            msg.style.display = "none";
            msg.style.color = "#ee5d50"; // Reset to red

            // 1. Check Matching
            if (pass !== confirm) {
                msg.innerText = "Passwords do not match.";
                msg.style.display = "block";
                return;
            }

            // 2. Check Strength
            const strengthCheck = isStrong(pass);
            if (strengthCheck !== true) {
                msg.innerText = strengthCheck;
                msg.style.display = "block";
                return;
            }

            const btn = document.querySelector("button");
            btn.innerText = "Updating...";
            btn.disabled = true;

            try {
                const res = await fetch('api/reset_api.php?action=reset', {
                    method: 'POST',
                    body: JSON.stringify({ token: token, password: pass })
                });
                const data = await res.json();

                if (data.status === 'success') {
                    document.querySelector(".card").innerHTML = `
                        <div style="font-size:50px; color:#05cd99; margin-bottom:15px;"><i class="fa-solid fa-circle-check"></i></div>
                        <h2 style="color:#05cd99; margin-bottom:10px;">Success!</h2>
                        <p style="color:#a3aed0; margin-bottom:20px;">Your password has been updated.</p>
                        <a href="index.php" style="text-decoration:none; display:inline-block; padding:12px 30px; background:#476eef; color:white; border-radius:10px; font-weight:600;">Back to Login</a>
                    `;
                } else {
                    msg.innerText = data.message;
                    msg.style.display = "block";
                    btn.innerText = "Change Password";
                    btn.disabled = false;
                }
            } catch (err) {
                msg.innerText = "Connection error.";
                msg.style.display = "block";
                btn.innerText = "Change Password";
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>