<?php
session_start();
define('ACCESS_ALLOWED', true); 

// GENERATE TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Project - Settings</title>
    <link rel="icon" type="image/png" href="../assets/img/logopc.png">
    <link href="../fontawesome/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/main.js?v=2"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <script>
        // INJECT TOKEN FOR JS
        const CSRF_TOKEN = "<?php echo $csrf_token; ?>";
        // Renamed to 'currentUserRole' to avoid conflict with main.js if it exists
        const currentUserRole = "<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'staff'; ?>";
    </script>
    <style>
        /* --- NEW ROLE BADGE COLORS --- */
        .role-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .role-admin { background: #e0e7ff; color: #476eef; }             /* Blue */
        .role-hr { background: #fce7f3; color: #db2777; }                /* Pink (New for HR) */
        .role-operation_manager { background: #f3e8ff; color: #9333ea; } /* Purple */
        .role-branch_manager { background: #f3e8ff; color: #9333ea; }    /* Purple (Legacy Support) */
        
        .role-sales_associate { background: #ffedd5; color: #ea580c; }   /* Orange */
        .role-sales_manager { background: #ffedd5; color: #ea580c; }     /* Orange (Legacy Support) */
        
        .role-cashier { background: #dcfce7; color: #16a34a; }           /* Green */
        
        /* Your Existing Styles for VISUAL PICKER CSS etc... */
        .picker-container { display: flex; height: 500px; gap: 20px; }
        /* ... (Keeping existing styles consistent) ... */
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div><h2>System Settings</h2></div>
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

        <div class="settings-container">
            <div class="settings-nav">
                <div class="set-link active-tab" onclick="switchTab('general')"><i class="fa-solid fa-user-gear"></i> General</div>
                <div class="set-link" onclick="switchTab('security')"><i class="fa-solid fa-shield-halved"></i> Security</div>
                <div class="set-link" id="teamTab" onclick="switchTab('team')"><i class="fa-solid fa-users"></i> Team</div>
                <div class="set-link" id="companyTab" onclick="switchTab('company')"><i class="fa-solid fa-building"></i> Company</div>
                <div class="set-link" id="dataTab" onclick="switchTab('data')"><i class="fa-solid fa-database"></i> Data</div>
            </div>

            <div class="settings-content">
                <div id="general" class="tab-content active-content">
                    <div class="section-title"><i class="fa-solid fa-user"></i> <h3>My Profile</h3></div>
                    <form id="profileForm">
                        <div style="display:flex; flex-direction:column; align-items:center; margin-bottom:25px;">
                            <label for="profileUpload" style="cursor:pointer; position:relative;">
                                <div style="width:100px; height:100px; border-radius:50%; overflow:hidden; border:3px solid #e0e0e0; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    <img id="currentAvatar" src="../assets/img/logopc.png" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                                <div style="position:absolute; bottom:0; right:0; background:#476eef; color:white; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid white;">
                                    <i class="fa-solid fa-camera" style="font-size:12px;"></i>
                                </div>
                            </label>
                            <input type="file" id="profileUpload" accept="image/*" style="display:none;" onchange="previewAvatar()">
                            <p style="font-size:12px; color:#a3aed0; margin-top:8px;">Click to change avatar</p>
                        </div>
                        <div class="form-group"><label>Display Name</label><input type="text" id="myInputName" required></div>
                        <div class="form-group"><label>Email</label><input type="text" id="myInputEmail" readonly style="background:#f0f0f0; cursor:not-allowed;"></div>
                        <button type="submit" class="btn-save">Update Profile</button>
                    </form>
                    <hr style="border:0; border-top:1px solid #f4f7fe; margin:30px 0;">
                    <div class="section-title"><i class="fa-solid fa-palette"></i> <h3>Appearance</h3></div>
                    <label style="display:flex; gap:10px; align-items:center; cursor:pointer;">
                        <input type="checkbox" id="darkModeToggle" onchange="toggleDarkMode()"> <span>Enable Dark Mode</span>
                    </label>
                </div>

                <div id="security" class="tab-content">
                    <div class="section-title"><i class="fa-solid fa-lock"></i> <h3>Change Password</h3></div>
                    <div class="form-group"><label>Current Password</label><input type="password" id="currentPass"></div>
                    <div class="form-group"><label>New Password</label><input type="password" id="newPass"></div>
                    <button class="btn-save" onclick="changePassword()">Update Password</button>
                </div>

                <div id="company" class="tab-content">
                    <div class="section-title"><i class="fa-solid fa-building"></i> <h3>Company Information</h3></div>
                    <form id="companyForm">
                        <div class="form-group"><label>Company Name</label><input type="text" id="setCompName" required></div>
                        <div class="form-group"><label>Address</label><textarea id="setCompAddr" rows="3"></textarea></div>
                        <div class="form-grid">
                            <div class="form-group"><label>Official Phone</label><input type="text" id="setCompPhone"></div>
                            <div class="form-group"><label>Official Email</label><input type="email" id="setCompEmail"></div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group"><label>Currency Symbol</label><input type="text" id="setCurrency" placeholder="₱" style="width:80px;"></div>
                            <div class="form-group"><label>Tax Rate (%)</label><input type="number" id="setTax" step="0.01" style="width:100px;"></div>
                            <div class="form-group"><label>Low Stock Warning</label><input type="number" id="setLowStock" style="width:100px;"></div>
                        </div>
                        <button type="submit" class="btn-save">Save Company Settings</button>
                    </form>
                </div>

                <div id="data" class="tab-content">
                    <div class="section-title"><i class="fa-solid fa-database"></i> <h3>Data & Backup</h3></div>
                    
                    <div style="margin-bottom:20px; display:flex; gap:10px;">
                        <a href="../api/settings_api.php?action=export_inventory" class="btn-outline">
                            <i class="fa-solid fa-download"></i> Export Inventory
                        </a>

                        <button class="btn-outline" onclick="document.getElementById('csvInput').click()">
                            <i class="fa-solid fa-upload"></i> Import Inventory
                        </button>
                        <input type="file" id="csvInput" accept=".csv" style="display:none;" onchange="uploadCSV()">
                    </div>
                    
                    <p style="font-size:12px; color:#a3aed0; margin-bottom:20px;">
                        <strong>CSV Format:</strong> Name, Category, Price, Stock, Cost, SKU
                    </p>

                    <hr style="border:0; border-top:1px solid #f4f7fe; margin:20px 0;">

                    <h4 style="margin-bottom:10px; color:#476eef;">Full Database Backup</h4>
                    
                    <div style="margin-bottom:20px; display:flex; gap:10px;">
                        <button class="btn-save" onclick="downloadBackup()">
                            <i class="fa-solid fa-cloud-arrow-down"></i> Backup Database
                        </button>

                        <button class="btn-outline" onclick="document.getElementById('sqlInput').click()" style="color:#ee5d50; border-color:#ee5d50;">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Restore Database
                        </button>
                        <input type="file" id="sqlInput" accept=".sql" style="display:none;" onchange="restoreDB()">
                    </div>

                    <hr style="border:0; border-top:1px solid #f4f7fe; margin:20px 0;">
                    
                    <div>
                        <button class="btn-danger" onclick="openDeleteModal('reset')"><i class="fa-solid fa-trash-can"></i> Clear Transaction History</button>
                    </div>
                </div>

                <div id="team" class="tab-content">
                    <div class="section-title" style="justify-content:space-between;">
                        <div><i class="fa-solid fa-users-gear"></i> <h3>User Management</h3></div>
                        <button class="btn-save" onclick="openUserModal()">+ Add User</button>
                    </div>
                    
                    <table class="user-table">
                        <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Last Active</th><th>Action</th></tr></thead>
                        <tbody id="usersTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add User</h3>
                <i class="fa-solid fa-xmark close-btn" onclick="closeModal()"></i>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId">
                <div class="form-group"><label>Full Name</label><input type="text" id="uName" required></div>
                <div class="form-group"><label>Username</label><input type="text" id="uUser" required></div>
                <div class="form-group"><label>Email</label><input type="email" id="uEmail" required></div>
                <div class="form-group"><label>Password</label><input type="password" id="uPass" placeholder="Leave blank if editing"></div>
                
                <div class="form-group"><label>Role</label>
                    <select id="uRole">
                        <option value="admin">Admin</option>
                        <option value="hr">Human Resources</option> <option value="operation_manager">Operation Manager</option>
                        <option value="sales_associate">Sales Associate</option>
                        <option value="cashier">Cashier</option>
                    </select>
                </div>

                <div class="form-group"><label>Status</label>
                    <select id="uStatus">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-confirm">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content delete-content">
            <div class="delete-icon"><i class="fa-solid fa-trash-can"></i></div>
            <div class="delete-title" id="confirmTitle">Are you sure?</div>
            <div class="delete-text" id="confirmText">This action cannot be undone.</div>
            <div class="modal-footer" style="justify-content: center;">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-danger" onclick="confirmAction()">Confirm</button>
            </div>
        </div>
    </div>

    <div id="successPopup" class="success-popup">
        <div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
        <div class="success-title">Success!</div>
        <div class="success-text" id="successMessage">Action completed successfully.</div>
    </div>

    <div id="errorPopup" class="error-popup">
        <div class="error-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
        <div class="error-title">Error</div>
        <div class="error-text" id="errorMessage">Action failed.</div>
    </div>

    <script>
        const API_URL = "../api/settings_api.php";
        let pendingAction = null;
        let deleteTargetId = null;
        let usersList = [];

        window.onload = function() {
            if(sessionStorage.getItem("darkMode") === "true") {
                document.body.classList.add("dark-mode");
                document.getElementById("darkModeToggle").checked = true;
            }
            
            fetchProfile(); 
            
            if (currentUserRole !== 'admin') {
                document.getElementById("teamTab").style.display = 'none';
                document.getElementById("companyTab").style.display = 'none';
                document.getElementById("dataTab").style.display = 'none';
            } else {
                fetchUsers();
                fetchSettings();
            }
        };

        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active-content'));
            document.querySelectorAll('.set-link').forEach(el => el.classList.remove('active-tab'));
            document.getElementById(tab).classList.add('active-content');
            event.currentTarget.classList.add('active-tab');
        }

        // --- HELPERS ---
        function showSuccess(msg) {
            const popup = document.getElementById("successPopup");
            document.getElementById("successMessage").innerText = msg;
            popup.style.display = "flex"; 
            setTimeout(() => { popup.style.display = "none"; }, 2000);
        }
        function showError(msg) {
            const popup = document.getElementById("errorPopup");
            document.getElementById("errorMessage").innerText = msg;
            popup.style.display = "flex"; 
            setTimeout(() => { popup.style.display = "none"; }, 3000);
        }

        // --- TEAM MANAGEMENT ---
        async function fetchUsers() {
            const res = await fetch(API_URL + "?action=get_users", { credentials: 'include' });
            usersList = await res.json();
            renderUserTable(usersList);
        }

        // Helper to map DB values to Display names
        function formatRole(role) {
            switch(role) {
                case 'admin': return 'Admin';
                case 'hr': return 'Human Resources'; // Added HR Role Display
                case 'operation_manager': return 'Operation Manager';
                case 'branch_manager': return 'Operation Manager'; // Legacy
                case 'sales_associate': return 'Sales Associate';
                case 'sales_manager': return 'Sales Associate'; // Legacy
                case 'cashier': return 'Cashier';
                default: return role;
            }
        }

        function renderUserTable(list) {
            const tbody = document.getElementById("usersTableBody");
            tbody.innerHTML = "";
            list.forEach(u => {
                const statusHtml = u.is_online ? `<span style="color:#05cd99; font-weight:700;">Online</span>` : `<span style="color:#64748b;">Offline</span>`;
                const displayRole = formatRole(u.role);
                const roleClass = "role-" + u.role; // Uses the CSS we added above

                const row = `<tr>
                    <td><div style="font-weight:700;">${escapeHtml(u.full_name)}</div><div style="font-size:12px; color:#a3aed0;">${escapeHtml(u.email)}</div></td>
                    <td>${escapeHtml(u.username)}</td>
                    <td><span class="role-badge ${roleClass}">${displayRole}</span></td>
                    <td>${statusHtml}</td>
                    <td style="font-size:12px;">${u.last_active}</td>
                    <td>
                        <button class="btn-save" style="padding:6px 12px; font-size:12px;" onclick="editUser(${u.id})">Edit</button> 
                        <button class="btn-danger" style="padding:6px 12px; font-size:12px;" onclick="openDeleteModal('user', ${u.id})"><i class="fa-solid fa-trash"></i></button>
                    </td>
                </tr>`;
                tbody.innerHTML += row;
            });
        }

        function editUser(id) {
            const u = usersList.find(user => user.id == id);
            if(u) {
                openUserModal();
                document.getElementById("userId").value = u.id;
                document.getElementById("uName").value = u.full_name;
                document.getElementById("uUser").value = u.username;
                document.getElementById("uEmail").value = u.email;
                
                // Map old roles to new ones if necessary
                let roleVal = u.role;
                if(roleVal === 'branch_manager') roleVal = 'operation_manager';
                if(roleVal === 'sales_manager') roleVal = 'sales_associate';
                
                document.getElementById("uRole").value = roleVal;
                document.getElementById("uStatus").value = u.status;
                document.getElementById("modalTitle").innerText = "Edit User";
            }
        }

        function openUserModal() {
            document.getElementById("userModal").style.display = "block";
            document.getElementById("userForm").reset();
            document.getElementById("userId").value = "";
            document.getElementById("modalTitle").innerText = "Add New User";
        }
        function closeModal() { document.getElementById("userModal").style.display = "none"; }

        // --- SAVE USER ---
        document.getElementById("userForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            const payload = {
                id: document.getElementById("userId").value,
                full_name: document.getElementById("uName").value,
                username: document.getElementById("uUser").value,
                email: document.getElementById("uEmail").value,
                role: document.getElementById("uRole").value,
                status: document.getElementById("uStatus").value,
                password: document.getElementById("uPass").value
            };
            
            const res = await fetch(API_URL + "?action=save_user", {
                method: "POST", 
                headers: { "Content-Type": "application/json", 'X-CSRF-Token': CSRF_TOKEN },
                credentials: 'include', 
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.error) showError(data.error); else { closeModal(); fetchUsers(); showSuccess("User saved."); }
        });

        // --- DELETE USER ---
        async function executeDeleteUser() {
            await fetch(`${API_URL}?action=delete_user&id=${deleteTargetId}`, { 
                method: "DELETE", 
                headers: { 'X-CSRF-Token': CSRF_TOKEN }, 
                credentials: 'include' 
            });
            closeDeleteModal();
            fetchUsers();
            showSuccess("User deleted.");
        }

        // --- PASSWORD CHANGE ---
        async function changePassword() {
            const c = document.getElementById("currentPass").value;
            const n = document.getElementById("newPass").value;
            const res = await fetch(API_URL + "?action=change_password", {
                method: "POST", 
                headers: { "Content-Type": "application/json", 'X-CSRF-Token': CSRF_TOKEN },
                credentials: 'include', 
                body: JSON.stringify({current_password: c, new_password: n})
            });
            const data = await res.json();
            if(data.status === 'success') { showSuccess(data.message); document.getElementById("currentPass").value=""; document.getElementById("newPass").value=""; } 
            else { showError(data.message); }
        }

        // --- COMPANY SETTINGS ---
        async function fetchSettings() {
            const res = await fetch(API_URL + "?action=get_settings", { credentials: 'include' });
            const data = await res.json();
            if(data.company_name) {
                document.getElementById('setCompName').value = data.company_name;
                document.getElementById('setCompAddr').value = data.company_address;
                document.getElementById('setCompPhone').value = data.company_phone;
                document.getElementById('setCompEmail').value = data.company_email;
                document.getElementById('setCurrency').value = data.currency_symbol;
                document.getElementById('setTax').value = data.tax_rate;
                document.getElementById('setLowStock').value = data.low_stock_threshold;
            }
        }

        document.getElementById("companyForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            const payload = {
                company_name: document.getElementById('setCompName').value,
                company_address: document.getElementById('setCompAddr').value,
                company_phone: document.getElementById('setCompPhone').value,
                company_email: document.getElementById('setCompEmail').value,
                currency_symbol: document.getElementById('setCurrency').value,
                tax_rate: document.getElementById('setTax').value,
                low_stock_threshold: document.getElementById('setLowStock').value
            };
            await fetch(API_URL + "?action=save_settings", {
                method: "POST", 
                headers: { "Content-Type": "application/json", 'X-CSRF-Token': CSRF_TOKEN },
                credentials: 'include', 
                body: JSON.stringify(payload)
            });
            showSuccess("Settings updated!");
        });

        // --- RESET & RESTORE ---
        async function executeReset() {
            await fetch(API_URL + "?action=reset_sales", { 
                method: "POST", 
                headers: { 'X-CSRF-Token': CSRF_TOKEN }, 
                credentials: 'include' 
            });
            closeDeleteModal();
            showSuccess("System reset complete.");
        }

        function downloadBackup() { window.location.href = API_URL + "?action=backup_db"; }

        async function restoreDB() {
            const file = document.getElementById('sqlInput').files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('sql_file', file);
            
            await fetch(API_URL + "?action=restore_db", {
                method: "POST",
                headers: { 'X-CSRF-Token': CSRF_TOKEN },
                body: formData
            });
            showSuccess("Database restored.");
            setTimeout(() => location.reload(), 2000);
        }

        // --- PROFILE ---
        async function fetchProfile() {
            const res = await fetch(API_URL + "?action=get_profile&t=" + new Date().getTime(), { credentials: 'include' });
            const data = await res.json();
            if (data.full_name) {
                document.getElementById('myInputName').value = data.full_name;
                document.getElementById('myInputEmail').value = data.email;
                if (data.avatar) {
                    document.getElementById('currentAvatar').src = "../assets/uploads/" + data.avatar;
                    const hImg = document.getElementById('headerAvatar');
                    if(hImg) hImg.src = "../assets/uploads/" + data.avatar;
                }
            }
        }

        document.getElementById("profileForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('full_name', document.getElementById('myInputName').value);
            const fileInput = document.getElementById('profileUpload');
            if (fileInput.files[0]) formData.append('avatar', fileInput.files[0]);

            await fetch(API_URL + "?action=update_profile", {
                method: "POST",
                headers: { 'X-CSRF-Token': CSRF_TOKEN },
                body: formData
            });
            showSuccess("Profile updated!");
            fetchProfile();
        });

        function previewAvatar() {
            const file = document.getElementById('profileUpload').files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) { document.getElementById('currentAvatar').src = e.target.result; }
                reader.readAsDataURL(file);
            }
        }

        // --- MODAL UTILS ---
        function openDeleteModal(type, id = null) {
            document.getElementById("deleteModal").style.display = "block";
            if(type === 'user') {
                deleteTargetId = id;
                document.getElementById("confirmTitle").innerText = "Delete User?";
                pendingAction = executeDeleteUser;
            } else if (type === 'reset') {
                document.getElementById("confirmTitle").innerText = "Wipe All Sales?";
                pendingAction = executeReset;
            }
        }
        function closeDeleteModal() { document.getElementById("deleteModal").style.display = "none"; pendingAction = null; }
        function confirmAction() { if(pendingAction) pendingAction(); }
        function escapeHtml(text) { if(!text)return""; return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;"); }
        function toggleDarkMode() { document.body.classList.toggle("dark-mode"); sessionStorage.setItem("darkMode", document.body.classList.contains("dark-mode")); }
        
        window.onclick = function(e) {
            if(e.target == document.getElementById("userModal")) closeModal();
            if(e.target == document.getElementById("deleteModal")) closeDeleteModal();
        }
    </script>
</body>
</html>