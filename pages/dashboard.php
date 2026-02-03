<?php 
session_start();
define('ACCESS_ALLOWED', true); 
require_once '../config/db_connect.php';

// 1. AUTH CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$role = $_SESSION['role']; 
$today = date('Y-m-d');

// --- PRE-FETCH DATA FOR NON-ADMIN ROLES (Server Side) ---
$mySales = 0; $myPendingQuotes = 0;
$todayCash = 0; $todayDigital = 0; $todayTxn = 0;

if ($role === 'sales_associate' || $role === 'sales_manager') {
    // Sales Logic
    $q1 = $conn->query("SELECT SUM(total_price) as total FROM sales WHERE DATE(sale_date) = '$today'"); 
    $mySales = $q1->fetch_assoc()['total'] ?? 0;
    $q2 = $conn->query("SELECT COUNT(*) as count FROM custom_builds WHERE status = 'Pending'");
    $myPendingQuotes = $q2->fetch_assoc()['count'];
}
elseif ($role === 'cashier') {
    // Cashier Logic
    $qC = $conn->query("SELECT SUM(total_price) as total FROM sales WHERE DATE(sale_date) = '$today' AND payment_method LIKE '%Cash%'");
    $todayCash = $qC->fetch_assoc()['total'] ?? 0;
    $qD = $conn->query("SELECT SUM(total_price) as total FROM sales WHERE DATE(sale_date) = '$today' AND payment_method NOT LIKE '%Cash%'");
    $todayDigital = $qD->fetch_assoc()['total'] ?? 0;
    $qT = $conn->query("SELECT COUNT(DISTINCT transaction_id) as count FROM sales WHERE DATE(sale_date) = '$today'");
    $todayTxn = $qT->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Project - Dashboard</title>
    <link rel="icon" type="image/png" href="../assets/img/logopc.png">
    <script src="../assets/js/main.js?v=2"></script>
    <link href="../fontawesome/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/chart.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Extra styles for the new Role Views to match your theme */
        .role-welcome { background: white; padding: 25px; border-radius: 16px; margin-bottom: 25px; border-left: 5px solid #476eef; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
        .role-welcome h2 { margin: 0; color: #2b3674; font-size: 24px; }
        .role-welcome p { margin: 5px 0 0; color: #a3aed0; }
        
        /* Big Action Button for Cashier/Sales */
        .big-btn { background: #476eef; color: white; width: 100%; padding: 25px; border-radius: 16px; font-size: 20px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.2s; box-shadow: 0 8px 20px rgba(71, 110, 239, 0.3); }
        .big-btn:hover { background: #3b5bdb; transform: translateY(-2px); }
        .big-btn-outline { background: white; color: #2b3674; border: 2px solid #e0e7ff; box-shadow: none; }
        .big-btn-outline:hover { border-color: #476eef; background: #f8f9fc; }

        .simple-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .simple-table th { text-align: left; color: #a3aed0; font-size: 12px; padding: 10px; border-bottom: 1px solid #eee; }
        .simple-table td { padding: 12px 10px; font-size: 14px; border-bottom: 1px solid #f4f7fe; color: #2b3674; font-weight: 600; }
    </style>
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div><h2>
                <?php 
                    if($role === 'admin' || $role === 'operation_manager') echo "Main Dashboard";
                    elseif($role === 'sales_associate') echo "Sales Dashboard";
                    elseif($role === 'cashier') echo "POS Terminal";
                ?>
            </h2></div>
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

        <?php if ($role === 'admin' || $role === 'operation_manager' || $role === 'branch_manager'): ?>
        
        <div class="stats-container">
            <div class="card stat-card">
                <i class="fa-solid fa-box stat-icon-bg"></i>
                <div class="stat-title">Total Items</div>
                <div class="stat-value" id="displayTotalItems">...</div>
                <div><span class="badge-green"><i class="fa-solid fa-check"></i> Live</span></div>
            </div>

            <div class="card stat-card">
                <i class="fa-solid fa-triangle-exclamation stat-icon-bg" style="color: #ee5d50;"></i>
                <div class="stat-title">Low Stock</div>
                <div class="stat-value" id="displayLowStock">...</div>
                <div><span class="badge-red">Alert</span></div>
            </div>

            <div class="card stat-card">
                <i class="fa-solid fa-truck-fast stat-icon-bg" style="color: #ffb547;"></i>
                <div class="stat-title">Incoming Orders</div>
                <div class="stat-value" id="displayPendingOrders">0</div>
                <div><span class="badge-orange">Pending</span></div>
            </div>

            <div class="card stat-card">
                <i class="fa-solid fa-coins stat-icon-bg" style="color: #05cd99;"></i>
                <div class="stat-title">Total Value</div>
                <div class="stat-value" id="displayTotalValue" style="font-size: 20px;">...</div>
                <div><span class="badge-green">Asset</span></div>
            </div>

            <div class="card stat-card">
                <i class="fa-solid fa-wrench stat-icon-bg"></i>
                <div class="stat-title">Active Builds</div>
                <div class="stat-value" id="displayActiveBuilds">...</div>
                <div><span class="badge-green">Building</span></div>
            </div>
        </div>

        <div class="content-grid">
            <div class="card chart-section">
                <div class="card-header">
                    <h3>Sales Trend</h3>
                    <a href="reports.php" class="view-link" style="font-size: 12px;">View Report <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div style="height: 250px; width: 100%;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <div class="card category-section">
                <div class="card-header">
                    <h3>Top Categories</h3>
                </div>
                <div style="height: 200px; position: relative;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>

            <div class="card recent-builds">
                <div class="card-header">
                    <h3>Recent Builds</h3>
                    <a href="custom_builds.php" class="view-link">View All</a>
                </div>
                <div id="recentBuildsContainer">
                    <div style="padding:20px; text-align:center; color:#a3aed0;">Loading...</div>
                </div>
            </div>

            <div class="card stock-alerts">
                <div class="card-header">
                    <h3>Stock Alerts <span style="font-size:12px; color:#ee5d50;">(Low)</span></h3>
                    <a href="inventory.php" class="view-link">Manage</a>
                </div>
                <div id="alertContainer">
                    <div style="padding:20px; color:#a3aed0; text-align:center;">Checking stock...</div>
                </div>
            </div>
        </div>

        <?php elseif ($role === 'sales_associate' || $role === 'sales_manager'): ?>
            
            <div class="role-welcome">
                <h2>🚀 Ready to sell, <?php echo htmlspecialchars($_SESSION['name']); ?>?</h2>
                <p>Track your pipeline and manage custom PC builds.</p>
            </div>

            <div class="stats-container">
                <div class="card stat-card">
                    <i class="fa-solid fa-cash-register stat-icon-bg" style="color: #9333ea;"></i>
                    <div class="stat-title">Store Sales (Today)</div>
                    <div class="stat-value">₱<?php echo number_format($mySales); ?></div>
                </div>
                <div class="card stat-card" onclick="window.location.href='quotation.php'" style="cursor:pointer;">
                    <i class="fa-solid fa-file-invoice stat-icon-bg" style="color: #476eef;"></i>
                    <div class="stat-title">Pending Quotes</div>
                    <div class="stat-value"><?php echo $myPendingQuotes; ?></div>
                    <div><span class="badge-orange">Active</span></div>
                </div>
                <div class="card stat-card">
                    <i class="fa-solid fa-calendar-day stat-icon-bg" style="color: #05cd99;"></i>
                    <div class="stat-title">Date</div>
                    <div class="stat-value" style="font-size: 18px;"><?php echo date("F j, Y"); ?></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <button class="big-btn" onclick="window.location.href='quotation.php?action=new'">
                    <i class="fa-solid fa-microchip"></i> New PC Build
                </button>
                <button class="big-btn big-btn-outline" onclick="window.location.href='inventory.php'">
                    <i class="fa-solid fa-magnifying-glass"></i> Check Inventory
                </button>
            </div>

            <div class="card recent-builds" style="width: 100%;">
                <div class="card-header">
                    <h3>Recent Quotations</h3>
                    <a href="quotation.php" class="view-link">View All</a>
                </div>
                <div id="recentBuildsContainer">
                    <div style="padding:20px; text-align:center; color:#a3aed0;">Loading...</div>
                </div>
            </div>

        <?php elseif ($role === 'cashier'): ?>

            <div class="role-welcome">
                <h2>🏧 POS Terminal</h2>
                <p>Register Active for: <b><?php echo date("F j, Y"); ?></b></p>
            </div>

            <div class="stats-container">
                <div class="card stat-card">
                    <i class="fa-solid fa-money-bill-wave stat-icon-bg" style="color: #16a34a;"></i>
                    <div class="stat-title">Cash Collected</div>
                    <div class="stat-value">₱<?php echo number_format($todayCash); ?></div>
                </div>
                <div class="card stat-card">
                    <i class="fa-solid fa-qrcode stat-icon-bg" style="color: #476eef;"></i>
                    <div class="stat-title">Digital Payments</div>
                    <div class="stat-value">₱<?php echo number_format($todayDigital); ?></div>
                </div>
                <div class="card stat-card">
                    <i class="fa-solid fa-receipt stat-icon-bg" style="color: #f97316;"></i>
                    <div class="stat-title">Transactions</div>
                    <div class="stat-value"><?php echo $todayTxn; ?></div>
                </div>
            </div>

            <div style="margin: 30px 0;">
                <button class="big-btn" onclick="window.location.href='reports.php?open_modal=true'">
                    <i class="fa-solid fa-cart-plus"></i> Record New Transaction (F2)
                </button>
            </div>

            <div class="card recent-builds" style="width: 100%;">
                <div class="card-header">
                    <h3>Today's Transactions</h3>
                    <a href="reports.php" class="view-link">View Report</a>
                </div>
                <table class="simple-table">
                    <thead><tr><th>Time</th><th>Ref #</th><th>Amount</th><th>Method</th></tr></thead>
                    <tbody>
                        <?php
                        $recQ = $conn->query("SELECT * FROM sales WHERE DATE(sale_date) = '$today' ORDER BY id DESC LIMIT 10");
                        if($recQ->num_rows > 0) {
                            while($r = $recQ->fetch_assoc()) {
                                echo "<tr>
                                    <td>".date("h:i A", strtotime($r['sale_date']))."</td>
                                    <td style='font-family:monospace;'>{$r['transaction_id']}</td>
                                    <td style='font-weight:800;'>₱".number_format($r['total_price'])."</td>
                                    <td>{$r['payment_method']}</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; color:#a3aed0; padding:20px;'>No transactions yet today.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </main>

    <?php if ($role === 'admin' || $role === 'operation_manager'): ?>
    <div class="fab-container">
       <div class="fab-main"><i class="fa-solid fa-plus"></i></div>
       <a href="reports.php" class="fab-action"><span class="fab-label">Record Sale (F2)</span><div class="fab-btn btn-green"><i class="fa-solid fa-cart-shopping"></i></div></a>
       <a href="custom_builds.php" class="fab-action"><span class="fab-label">New Quatation</span><div class="fab-btn btn-blue"><i class="fa-solid fa-wrench"></i></div></a>
       <a href="purchase_orders.php" class="fab-action"><span class="fab-label">Order Stock</span><div class="fab-btn btn-orange"><i class="fa-solid fa-box"></i></div></a>
    </div>
    <?php endif; ?>

    <script>
        // 1. CONFIG
        const API_INV_STATS = "../api/api.php?action=stats";
        const API_BUILDS = "../api/builds_api.php";
        const API_CHARTS = "../api/api.php?action=charts"; 
        
        // Pass PHP Role to JS
        const currentUserRole = "<?php echo $role; ?>";

        // 2. DASHBOARD LOGIC (ADMIN ONLY)
        async function fetchDashboardStats() {
            try {
                const res = await fetch(API_INV_STATS, { credentials: 'include' });
                const data = await res.json();
                
                // POPULATE STATS
                if(document.getElementById('displayTotalItems')) document.getElementById('displayTotalItems').innerText = data.total_items;
                if(document.getElementById('displayLowStock')) document.getElementById('displayLowStock').innerText = data.low_stock;
                if(document.getElementById('displayPendingOrders')) document.getElementById('displayPendingOrders').innerText = data.pending_orders;
                
                const formattedValue = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(data.total_value);
                if(document.getElementById('displayTotalValue')) document.getElementById('displayTotalValue').innerText = formattedValue;

                // Populate Low Stock List
                const alertContainer = document.getElementById('alertContainer');
                if (alertContainer) {
                    alertContainer.innerHTML = ""; 
                    if (data.alerts && data.alerts.length > 0) {
                        data.alerts.forEach(item => {
                            alertContainer.innerHTML += `
                                <div class="list-item">
                                    <div class="item-info"><h4 style="color:#ee5d50;">${item.name}</h4><p>Critical Level</p></div>
                                    <span class="badge-red" style="padding:2px 8px;">Left: ${item.stock}</span>
                                </div>`;
                        });
                    } else {
                        alertContainer.innerHTML = '<div style="padding:20px; color:#05cd99; text-align:center;"><i class="fa-solid fa-circle-check"></i> Stock Levels Good</div>';
                    }
                }
            } catch (error) { console.error("Error loading stats:", error); }
        }

        async function fetchRecentBuilds() {
            const container = document.getElementById("recentBuildsContainer");
            if (!container) return; // Exit if container doesn't exist (e.g. Cashier view)

            try {
                const response = await fetch(API_BUILDS + "?limit=-1", { credentials: 'include' });
                const data = await response.json();
                
                // For Admin/Sales View
                const activeCount = data.filter(b => b.status === "In Progress" || b.status === "Pending").length;
                if(document.getElementById("displayActiveBuilds")) document.getElementById("displayActiveBuilds").innerText = activeCount;

                const recentThree = data.slice(0, 5);
                container.innerHTML = ""; 

                if (recentThree.length === 0) {
                    container.innerHTML = '<div style="padding:20px; text-align:center; color:#a3aed0;">No orders yet.</div>';
                    return;
                }

                recentThree.forEach(build => {
                    let badgeClass = "badge-orange";
                    if(build.status === "In Progress") badgeClass = "badge-blue"; 
                    if(build.status === "Completed") badgeClass = "badge-green";
                    if(build.status === "Cancelled") badgeClass = "badge-red";

                    let style = "";
                    if(build.status === "In Progress") style = "background:rgba(71, 110, 239, 0.1); color:#476eef; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600;";
                    
                    let spanHtml = style ? `<span style="${style}">${build.status}</span>` : `<span class="${badgeClass}" style="padding:2px 8px; font-size:11px;">${build.status}</span>`;

                    container.innerHTML += `
                        <div class="list-item">
                            <div class="item-info"><h4>${build.display_name || build.customer_name}</h4><p>${build.build_type}</p></div>
                            ${spanHtml}
                        </div>`;
                });
            } catch (error) { console.error("Error loading builds:", error); }
        }

        async function initCharts() {
            try {
                const res = await fetch(API_CHARTS, { credentials: 'include' });
                const data = await res.json();

                if(document.getElementById('salesChart')) {
                    new Chart(document.getElementById('salesChart').getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: data.months,
                            datasets: [{ label: 'Revenue (₱)', data: data.sales, backgroundColor: '#476eef', borderRadius: 5 }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } } }
                    });
                }

                if(document.getElementById('categoryChart')) {
                    new Chart(document.getElementById('categoryChart').getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: data.catLabels,
                            datasets: [{ data: data.catData, backgroundColor: ['#476eef', '#3b5bdb', '#05cd99', '#ee5d50', '#ffb547', '#a3aed0'], borderWidth: 0 }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 10, usePointStyle: true } } } }
                    });
                }
            } catch(e) { console.error("Chart Error", e); }
        }

        if(typeof applyPermissions === 'function') applyPermissions();

        // --- EXECUTE BASED ON ROLE ---
        if (currentUserRole === 'admin' || currentUserRole === 'operation_manager' || currentUserRole === 'branch_manager') {
            fetchDashboardStats();
            fetchRecentBuilds();
            initCharts();
        } else if (currentUserRole === 'sales_associate' || currentUserRole === 'sales_manager') {
            fetchRecentBuilds(); // Sales view also uses recent builds list
        }
    </script>
</body>
</html>