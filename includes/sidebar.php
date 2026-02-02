<?php
// Prevent direct access
if (!defined('ACCESS_ALLOWED')) { die("Direct access not permitted."); }

$current_page = strtolower(basename($_SERVER['PHP_SELF']));
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>

<style>
    /* ============================
       1. DESKTOP STYLES (Default)
       ============================ */
    .sidebar {
        display: flex;
        flex-direction: column;
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        width: 250px;
        background: white;
        padding: 20px;
        box-shadow: 2px 0 10px rgba(0,0,0,0.03);
        z-index: 1000;
        transition: all 0.3s ease; /* Smooth slide animation */
    }

    .brand { margin-bottom: 30px; text-align: center; }
    .brand img { width: 50px; margin-bottom: 10px; }
    .brand h3 { color: #476eef; margin: 0; font-size: 1.2rem; }

    .nav-list {
        flex-grow: 1;
        list-style: none;
        padding: 0;
        margin: 0;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .nav-item {
        padding: 12px 15px;
        margin-bottom: 5px;
        border-radius: 10px;
        cursor: pointer;
        color: #a3aed0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s;
        text-decoration: none;
        white-space: nowrap;
    }

    .nav-item:hover, .nav-item.active { background: #476eef; color: white; }
    .nav-item i { width: 20px; text-align: center; }

    .logout-container { margin-top: auto; padding-top: 20px; border-top: 1px solid #f4f7fe; }
    
    .btn-logout {
        width: 100%; padding: 12px; background: #f4f7fe; color: #476eef;
        border: none; border-radius: 10px; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.2s;
    }
    .btn-logout:hover { background: #fee2e2; color: #ef4444; }

    /* MENU TOGGLE BUTTON (Hidden on Desktop) */
    .mobile-menu-btn {
        display: none; /* Hide by default */
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 3000;
        background: #476eef;
        color: white;
        width: 45px;
        height: 45px;
        border-radius: 10px;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(71, 110, 239, 0.3);
    }

    /* ============================
       2. MOBILE STYLES (Overrides)
       ============================ */
    @media (max-width: 1024px) {
        /* Hide Sidebar Off-Screen */
        .sidebar {
            left: -280px; 
            box-shadow: none;
        }

        /* Show Sidebar when Active */
        .sidebar.active {
            left: 0;
            box-shadow: 5px 0 50px rgba(0,0,0,0.2);
        }

        /* Show the Menu Button */
        .mobile-menu-btn {
            display: flex;
        }

        /* RESET CONTENT MARGIN (Crucial Fix) */
        /* This forces the content to stretch full width on mobile */
        body .main-content {
            margin-left: 0 !important;
            width: 100% !important;
            padding: 15px;
        }
    }

    /* Modal Styles */
    .logout-modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); align-items: center; justify-content: center; backdrop-filter: blur(3px); }
    .logout-content { background-color: white; padding: 30px; border-radius: 20px; width: 320px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.15); animation: popIn 0.3s ease; }
    .logout-icon-box { width: 60px; height: 60px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 15px auto; }
    .logout-actions { display: flex; gap: 10px; margin-top: 20px; }
    .btn-cancel-m { flex: 1; padding: 10px; border-radius: 8px; border: 1px solid #ddd; background: white; cursor: pointer; font-weight: 600; color: #666; }
    .btn-confirm-m { flex: 1; padding: 10px; border-radius: 8px; border: none; background: #ef4444; cursor: pointer; font-weight: 600; color: white; text-decoration: none; display: flex; align-items: center; justify-content: center; }
    @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>

<div class="mobile-menu-btn" onclick="toggleSidebar()">
    <i class="fa-solid fa-bars" style="font-size: 20px;"></i>
</div>

<aside class="sidebar">
    <div class="brand">
        <img src="../assets/img/logopc.png" alt="PC Project Logo">
        <h3>PC Project</h3>
    </div>

    <ul class="nav-list">
        <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'index.php' || $current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i> <span>Dashboard</span>
        </a>

        <?php if($role === 'admin' || $role === 'operation_manager'): ?>
        <a href="inventory.php" class="nav-item <?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-box"></i> <span>Inventory</span>
        </a>
        <?php endif; ?>

        <?php if($role === 'admin' || $role === 'sales_associate'): ?>
        <a href="custom_builds.php" class="nav-item <?php echo $current_page == 'custom_builds.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-screwdriver-wrench"></i> <span>Quotation</span>
        </a>
        <a href="purchase_orders.php" class="nav-item <?php echo $current_page == 'purchase_orders.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-box-open"></i> <span>Purchase Orders</span>
        </a>
        <?php endif; ?>

        <?php if($role === 'admin' || $role === 'operation_manager'): ?>
        <a href="suppliers.php" class="nav-item <?php echo $current_page == 'suppliers.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-truck"></i> <span>Suppliers</span>
        </a>
        <a href="clients.php" class="nav-item <?php echo $current_page == 'clients.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-users"></i> <span>Clients</span>
        </a>
        <?php endif; ?>

        <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-chart-line"></i> <span>Reports</span>
        </a>
        
        <?php if($role === 'admin'): ?>
        <a href="audit_logs.php" class="nav-item <?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-shield"></i> <span>Audit Logs</span>
        </a>
        <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-gear"></i> <span>Settings</span>
        </a>
        <?php endif; ?>

        <a href="attendance.php" class="nav-item <?php echo $current_page == 'attendance.php' ? 'active' : ''; ?>">
            <i class="fa-solid fa-calendar-check"></i> <span>Attendance</span>
        </a>
    </ul>

    <div class="logout-container">
        <button class="btn-logout" onclick="openLogoutModal()">
            <i class="fa-solid fa-right-from-bracket"></i> Sign Out
        </button>
    </div>
</aside>

<div id="logoutModal" class="logout-modal">
    <div class="logout-content">
        <div class="logout-icon-box"><i class="fa-solid fa-right-from-bracket"></i></div>
        <h3 style="color: #2b3674; margin-bottom: 5px;">Sign Out?</h3>
        <p style="color: #a3aed0; font-size: 14px; margin-bottom: 20px;">Are you sure you want to end your session?</p>
        <div class="logout-actions">
            <button class="btn-cancel-m" onclick="closeLogoutModal()">Cancel</button>
            <a href="../logout.php" class="btn-confirm-m">Yes, Sign Out</a>
        </div>
    </div>
</div>

<script>
    // 1. Sidebar Toggle Logic
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('active');
    }

    // 2. Close Sidebar when clicking outside (Mobile UX)
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const btn = document.querySelector('.mobile-menu-btn');
        
        // If sidebar is open AND click is NOT on sidebar AND NOT on button
        if (sidebar.classList.contains('active') && !sidebar.contains(event.target) && !btn.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    });

    // 3. Logout Modal Logic
    function openLogoutModal() { document.getElementById('logoutModal').style.display = 'flex'; }
    function closeLogoutModal() { document.getElementById('logoutModal').style.display = 'none'; }
    window.onclick = function(event) {
        let modal = document.getElementById('logoutModal');
        if (event.target == modal) { modal.style.display = "none"; }
    }
</script>