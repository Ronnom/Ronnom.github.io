<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PC Project</title>
    <link rel="icon" type="image/png" href="assets/img/logopc.png">
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7fe; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; }
        h2 { color: #2b3674; margin-bottom: 10px; }
        p { color: #a3aed0; font-size: 14px; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; margin-bottom: 15px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #476eef; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        button:hover { background: #3b5bdb; }
        .back { display: block; margin-top: 15px; color: #a3aed0; text-decoration: none; font-size: 13px; }
        .back:hover { color: #476eef; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Forgot Password?</h2>
        <p>Enter your email to receive a reset link.</p>
        <input type="email" id="email" placeholder="Enter your email" required>
        <button onclick="requestReset()">Send Link</button>
        <div id="msg" style="margin-top:10px; font-size:13px;"></div>
        <a href="index.php" class="back">Back to Login</a>
    </div>

    <script>
        async function requestReset() {
            const email = document.getElementById("email").value;
            const btn = document.querySelector("button");
            const msg = document.getElementById("msg");
            
            btn.innerText = "Sending...";
            btn.disabled = true;

            try {
                const res = await fetch('api/reset_api.php?action=request', {
                    method: 'POST',
                    body: JSON.stringify({ email: email })
                });
                const data = await res.json();
                
                msg.style.color = data.status === "success" ? "green" : "red";
                msg.innerText = data.message;
            } catch (e) {
                msg.style.color = "red";
                msg.innerText = "Connection error.";
            }
            btn.innerText = "Send Link";
            btn.disabled = false;
        }
    </script>
</body>
</html>