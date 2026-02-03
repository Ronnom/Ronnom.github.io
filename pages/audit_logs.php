<?php 
session_start();
define('ACCESS_ALLOWED', true); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Project - Audit Logs</title>
    <link rel="icon" type="image/png" href="../assets/img/logopc.png">
    <script>
        if (sessionStorage.getItem("userRole") !== "admin") {
            alert("Access Denied: Admins Only");
            window.location.href = "dashboard.php"; 
        }
    </script>
    <link href="../fontawesome/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/main.js?v=2"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div><h2>Audit Logs</h2></div>
            <div class="user-badge">
                <?php 
                $hasAvatar = isset($_SESSION['avatar']) && !empty($_SESSION['avatar']);
                $avatarPath = $hasAvatar ? "../assets/uploads/" . $_SESSION['avatar'] : "../assets/img/logopc.png";
                $displayImg = $hasAvatar ? "block" : "none";
                $displayIcon = $hasAvatar ? "none" : "inline-block";
                ?>
                <img id="headerAvatar" src="<?php echo $avatarPath; ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover; margin-right:8px; display:<?php echo $displayImg; ?>;">   
                <i id="headerIcon" class="fa-solid fa-user-circle fa-lg" style="display:<?php echo $displayIcon; ?>;"></i>
                <span id="headerUserName"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?></span>
            </div>
        </header>

        <div class="controls-container">
            <div class="search-wrapper" style="display: flex; align-items: center; gap: 10px; background-color: #f4f7fe; padding: 12px 20px; border-radius: 30px; flex-grow:1;">
                <i class="fa-solid fa-magnifying-glass" style="color:#a3aed0;"></i>
                <input type="text" id="logSearch" placeholder="Deep Search: Username, Details, IP..." style="border: none; background: transparent; outline: none; width: 100%; font-size: 14px; color: #2b3674;" onkeyup="performSearch()">
            </div>
            
            <select id="actionFilter" onchange="performSearch()" style="padding: 12px; border-radius: 12px; border: 1px solid #e0e0e0; color: #2b3674; font-weight:600;">
                <option value="All">All Actions</option>
                <option value="Login">Logins</option>
                <option value="Edit">Edits</option>
                <option value="Delete">Deletes</option>
                <option value="Restore">Restores</option>
                <option value="Archive">Archives</option>
            </select>
        </div>
        
        <div id="paginationInfo" style="padding: 10px 0; font-size: 14px; color: #707eae;"></div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody id="logsBody"></tbody>
            </table>
        </div>
        
        <div id="paginationControls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
            <button class="btn-filter" id="prevBtn" onclick="changePage(-1)" disabled><i class="fa-solid fa-arrow-left"></i> Previous</button>
            <span id="pageNumbers" style="font-weight: 600;">Page 1 of 1</span>
            <button class="btn-filter" id="nextBtn" onclick="changePage(1)" disabled>Next <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </main>

    <script>
        const API_URL = "../api/audit_api.php";

        let totalLogCount = 0;
        let logsPerPage = 15;
        let currentPage = 1;
        let searchTimeout = null; // Debounce timer

        function timeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            let interval = seconds / 31536000; if (interval > 1) return Math.floor(interval) + " years ago";
            interval = seconds / 2592000; if (interval > 1) return Math.floor(interval) + " months ago";
            interval = seconds / 86400; if (interval > 1) return Math.floor(interval) + " days ago";
            interval = seconds / 3600; if (interval > 1) return Math.floor(interval) + " hrs ago";
            interval = seconds / 60; if (interval > 1) return Math.floor(interval) + " mins ago";
            return "Just now";
        }

        function escapeHtml(text) {
            if (!text) return "";
            return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
        }

        async function loadLogs() {
            try {
                // 1. Get Values
                const search = document.getElementById("logSearch").value;
                const type = document.getElementById("actionFilter").value;
                const tbody = document.getElementById("logsBody");
                
                // 2. Build Deep Search URL
                let url = `${API_URL}?page=${currentPage}&limit=${logsPerPage}&search=${encodeURIComponent(search)}&action=${encodeURIComponent(type)}`;
                
                // 3. Fetch from Server
                const res = await fetch(url, { credentials: 'include' });
                const data = await res.json();
                
                if (data.error) { alert(data.error); return; }

                totalLogCount = data.total_logs;
                const logs = data.logs;

                // 4. Render Rows
                tbody.innerHTML = "";
                if(logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#a3aed0;">No matching records found.</td></tr>';
                    renderPaginationControls(0);
                    return;
                }

                logs.forEach(log => {
                    let badgeClass = "log-create";
                    let icon = '<i class="fa-solid fa-info-circle"></i>';
                    const act = (log.action || "").toLowerCase();

                    if (act.includes('login')) { badgeClass = "log-login"; icon = '<i class="fa-solid fa-key"></i>'; } 
                    else if (act.includes('logout')) { badgeClass = "log-logout"; icon = '<i class="fa-solid fa-door-open"></i>'; } 
                    else if (act.includes('delete') || act.includes('void') || act.includes('archive')) { badgeClass = "log-delete"; icon = '<i class="fa-solid fa-trash-can"></i>'; } 
                    else if (act.includes('update') || act.includes('edit')) { badgeClass = "log-update"; icon = '<i class="fa-solid fa-pen-to-square"></i>'; } 
                    else if (act.includes('create') || act.includes('add')) { badgeClass = "log-create"; icon = '<i class="fa-solid fa-plus"></i>'; } 
                    else if (act.includes('restore')) { badgeClass = "log-update"; icon = '<i class="fa-solid fa-rotate-left"></i>'; }

                    const row = `
                    <tr class="data-row">
                    <td>
                        <div style="font-weight:600; color:#2b3674;">${timeAgo(log.timestamp)}</div>
                        <div style="font-size:11px; color:#a3aed0;">${escapeHtml(log.timestamp)}</div>
                    </td>
                    <td style="font-weight:700;">${escapeHtml(log.username)}</td>
                    <td><span class="log-badge ${badgeClass}" style="display:flex; align-items:center; gap:6px; width:fit-content;">${icon} ${escapeHtml(log.action)}</span></td>
                    <td style="font-size:13px;">${escapeHtml(log.details)}</td>
                    <td style="font-family:monospace; color:#a3aed0; font-size:12px;">${escapeHtml(log.ip_address)}</td>
                    </tr>`;
                    tbody.innerHTML += row;
                });
                
                renderPaginationControls(logs.length); 

            } catch (err) { console.error(err); }
        }
        
        // Debounce Search (Waits 300ms after typing stops before searching)
        function performSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1; // Reset to page 1 on new search
                loadLogs();
            }, 300);
        }

        function renderPaginationControls(currentCount) {
            const totalPages = Math.ceil(totalLogCount / logsPerPage);
            document.getElementById('pageNumbers').innerText = `Page ${currentPage} of ${totalPages || 1}`;
            
            let start = ((currentPage - 1) * logsPerPage) + 1;
            let end = ((currentPage - 1) * logsPerPage) + currentCount;
            if(totalLogCount === 0) { start = 0; end = 0; }
            
            document.getElementById('paginationInfo').innerText = `Displaying ${start} - ${end} of ${totalLogCount} records`;

            const prev = document.getElementById('prevBtn');
            const next = document.getElementById('nextBtn');

            prev.disabled = currentPage <= 1;
            next.disabled = currentPage >= totalPages;
            
            // Visibility logic
            const controls = document.getElementById('paginationControls');
            controls.style.display = (totalLogCount > 0) ? 'flex' : 'none';
        }

        function changePage(direction) {
            const totalPages = Math.ceil(totalLogCount / logsPerPage);
            const newPage = currentPage + direction;
            if (newPage >= 1 && newPage <= totalPages) {
                currentPage = newPage;
                loadLogs();
            }
        }

        loadLogs();
    </script>
</body>
</html>