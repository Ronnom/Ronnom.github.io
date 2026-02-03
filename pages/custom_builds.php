<?php 
session_start();
define('ACCESS_ALLOWED', true); 

// GENERATE CSRF TOKEN
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
    <title>PC Project - Quotations</title>
    <link rel="icon" type="image/png" href="../assets/img/logopc.png">
    <link href="../fontawesome/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/main.js?v=2"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <script>
        const CURRENT_USER_ROLE = "<?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'staff'; ?>";
        const CSRF_TOKEN = "<?php echo $csrf_token; ?>";
    </script>
    <style>
        /* --- VISUAL PICKER CSS --- */
        .picker-container { display: flex; height: 500px; gap: 20px; }
        .picker-sidebar { width: 200px; border-right: 1px solid #e0e0e0; display: flex; flex-direction: column; gap: 5px; overflow-y: auto; }
        .picker-cat { padding: 10px 15px; cursor: pointer; border-radius: 8px; font-weight: 500; color: #707eae; transition: 0.2s; }
        .picker-cat:hover { background: #f4f7fe; color: #476eef; }
        .picker-cat.active { background: #476eef; color: white; }
        
        /* Service Category Styling */
        .picker-cat.service-cat { color: #2b3674; font-weight: 700; margin-top: 10px; border: 1px dashed #a3aed0; }
        .picker-cat.service-cat:hover, .picker-cat.service-cat.active { background: #2b3674; color: white; border-color: #2b3674; }

        /* Grid Layout */
        .picker-grid { flex: 1; display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); grid-auto-rows: 220px; gap: 15px; overflow-y: auto; padding-right: 5px; }
        
        /* Standard Part Card */
        .part-card { border: 1px solid #eee; border-radius: 12px; overflow: hidden; position: relative; cursor: pointer; transition: 0.2s; background: white; }
        .part-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: #476eef; }
        .part-img { width: 100%; height: 140px; object-fit: cover; background: #f9f9f9; }
        .part-info { padding: 10px; }
        .part-name { font-size: 13px; font-weight: 700; color: #2b3674; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; }
        .part-price { font-size: 14px; color: #476eef; font-weight: 800; }
        .part-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(30, 30, 40, 0.95); opacity: 0; transition: 0.2s; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 15px; text-align: center; color: white; pointer-events: none; }
        .part-card:hover .part-overlay { opacity: 1; }
        .overlay-stock { margin-top: 10px; font-size: 12px; font-weight: 700; color: #05cd99; }
        .overlay-stock.out-of-stock { color: #ff4757; }

        /* --- SERVICE CARD STYLING (NEW) --- */
        .service-card {
            border: 2px solid #e0e0e0; border-radius: 12px; background: white;
            display: flex; flex-direction: column; justify-content: space-between;
            padding: 15px; cursor: pointer; transition: 0.2s; position: relative;
            height: 150px; /* Shorter than parts */
        }
        .service-card:hover { border-color: #476eef; box-shadow: 0 5px 15px rgba(71, 110, 239, 0.15); }
        .service-title { font-size: 16px; font-weight: 700; color: #2b3674; line-height: 1.3; }
        .service-price { font-size: 18px; font-weight: 800; color: #476eef; margin-top: auto; }
        
        /* Admin Controls on Service Card */
        .service-controls { position: absolute; top: 10px; right: 10px; display: flex; gap: 5px; opacity: 0; transition: 0.2s; }
        .service-card:hover .service-controls { opacity: 1; }
        .svc-btn { width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; cursor: pointer; color: white; border: none; }
        .svc-edit { background: #476eef; }
        .svc-del { background: #ee5d50; }

        /* Add New Service Button (Big Plus) */
        .add-service-card {
            border: 2px dashed #a3aed0; border-radius: 12px; background: #f8f9fc;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.2s; height: 150px;
        }
        .add-service-card:hover { background: #e0e7ff; border-color: #476eef; }
        .add-service-icon { font-size: 40px; color: #a3aed0; }
        .add-service-card:hover .add-service-icon { color: #476eef; }

        /* --- CLIENT SEARCH DROPDOWN --- */
        .client-search-container { position: relative; width: 100%; }
        .client-input { width: 100%; padding: 12px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; font-weight: 600; color: #2b3674; }
        .client-input:focus { border-color: #476eef; outline: none; }
        .client-dropdown { 
            position: absolute; top: 100%; left: 0; width: 100%; max-height: 200px; 
            overflow-y: auto; background: white; border: 1px solid #e0e0e0; 
            border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            z-index: 100; display: none; margin-top: 5px;
        }
        .client-option { padding: 10px 15px; cursor: pointer; transition: 0.1s; font-size: 14px; color: #2b3674; border-bottom: 1px solid #f4f7fe; }
        .client-option:hover { background: #f4f7fe; color: #476eef; }
        .client-option small { display: block; color: #a3aed0; font-size: 11px; }

        /* OTHER UTILS */
        .modal-large { max-width: 900px; width: 90%; }
        .client-toggle-btn { background: #e0e7ff; color: #476eef; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; margin-left: 5px; }
        .client-toggle-btn:hover { background: #476eef; color: white; }
        #newClientFields { display: none; background: #f8f9fc; padding: 15px; border-radius: 8px; border: 1px dashed #a3aed0; margin-bottom: 15px; }
        .part-price-input { width: 100%; border: 1px solid transparent; background: transparent; font-weight: 700; color: #2b3674; text-align: right; }
        .part-price-input:focus { border: 1px solid #476eef; background: white; outline: none; border-radius: 4px; padding: 4px; }
        .part-price-input.readonly { color: #707eae; pointer-events: none; }
        
        /* PAYMENT MODAL STYLES */
        .pay-summary { background: #f4f7fe; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .pay-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .pay-total { border-top: 1px dashed #a3aed0; margin-top: 10px; padding-top: 10px; font-size: 18px; font-weight: 800; color: #476eef; }
        .pay-options { display: flex; gap: 10px; margin-bottom: 15px; }
        .pay-opt-btn { flex: 1; padding: 10px; border: 1px solid #eee; border-radius: 8px; cursor: pointer; text-align: center; font-weight: 600; transition: 0.2s; }
        .pay-opt-btn.active { background: #476eef; color: white; border-color: #476eef; }
        .balance-display { margin-top: 15px; font-size: 14px; text-align: right; color: #ee5d50; font-weight: 700; }

        /* Add inside <style> tag */
.status-pill { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; }
.status-pill.pending { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }     /* Orange */
.status-pill.in-progress { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }  /* Blue */
.status-pill.completed { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }    /* Green */
.status-pill.cancelled { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }    /* Red */
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div><h2>Quotation Management</h2></div>
            <div class="user-badge">
                <?php 
                    $hasAvatar = isset($_SESSION['avatar']) && !empty($_SESSION['avatar']);
                    $avatarPath = $hasAvatar ? "../assets/uploads/" . $_SESSION['avatar'] : "../assets/img/logopc.png";
                    $displayImg = $hasAvatar ? "block" : "none";
                ?>
                <img id="headerAvatar" src="<?php echo $avatarPath; ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover; margin-right:8px; display:<?php echo $displayImg; ?>;">   
                <span id="headerUserName"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?></span>
            </div>
        </header>

        <div class="controls-container">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search customer..." onkeyup="resetAndFetch()">
            </div>
            <div class="action-buttons">
                <select class="btn-filter" id="statusFilter" onchange="resetAndFetch()">
                    <option value="All">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                <button class="btn-filter" id="archiveToggleBtn" onclick="toggleArchives()" style="margin-right: 10px;"><i class="fa-solid fa-box-archive"></i> Archives</button>
                <button class="btn-add" onclick="openModal()"><i class="fa-solid fa-plus"></i> Add Quotation</button>
            </div>
        </div>

        <div id="paginationInfo" style="padding: 10px 0; font-size: 14px; color: #707eae;"></div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Ref #</th>
                        <th>Customer</th>
                        <th>Technician</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="buildsTableBody"></tbody>
            </table>
        </div>

        <div id="paginationControls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
            <button class="btn-filter" id="prevBtn" onclick="changePage(-1)" disabled><i class="fa-solid fa-arrow-left"></i> Previous</button>
            <span id="pageNumbers" style="font-weight: 600;">Page 1 of 1</span>
            <button class="btn-filter" id="nextBtn" onclick="changePage(1)" disabled>Next <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </main>

    <div id="buildModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header"><h2 id="modalTitle">Add Quotation</h2><i class="fa-solid fa-xmark close-btn" onclick="closeModal()"></i></div>
            <form id="buildForm">
                <input type="hidden" id="buildId">
                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <label>Select Client</label>
                        <button type="button" class="client-toggle-btn" onclick="toggleNewClientMode()"><i class="fa-solid fa-plus"></i> New Client?</button>
                    </div>
                    
                    <div id="existingClientGroup" class="client-search-container">
                        <input type="text" id="clientSearchInput" class="client-input" placeholder="Type Client Name (e.g. Adrian)..." onkeyup="searchClients()" autocomplete="off">
                        <input type="hidden" id="clientSelectId"> <div id="clientDropdown" class="client-dropdown"></div>
                    </div>

                    <div id="newClientFields">
                        <div class="form-group"><input type="text" id="newClientName" placeholder="Client Name (Required)" style="margin-bottom:10px;"></div>
                        <div class="form-group"><input type="text" id="newClientContact" placeholder="Contact Number" style="margin-bottom:10px;"></div>
                        <div class="form-group"><textarea id="newClientAddress" placeholder="Address" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="form-group"><label>Technician</label><input type="text" id="technician" placeholder="e.g. Mike" required></div>
                <div class="form-group">
                    <label>Products & Services</label>
                    <table class="parts-table">
                        <thead><tr><th style="width:40%">Item</th><th style="width:15%">Stock</th><th style="width:10%">Qty</th><th style="width:25%; text-align:right;">Price (Editable)</th><th style="width:10%"></th></tr></thead>
                        <tbody id="partsBody"></tbody>
                    </table>
                    <button type="button" onclick="openPartPicker()" style="color:#476eef; background:transparent; border:1px dashed #476eef; padding:12px; width:100%; border-radius:8px; cursor:pointer; font-weight:700; margin-top:5px; transition:0.2s;"><i class="fa-solid fa-magnifying-glass-plus"></i> Select Product / Service</button>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label>Status</label><select id="status"><option value="Pending">Pending</option><option value="In Progress">In Progress</option><option value="Completed">Completed</option><option value="Cancelled">Cancelled</option></select></div>
                    <div class="form-group"><label>Labor Fee (₱)</label><input type="number" id="laborFee" value="0" min="0" step="50" oninput="calculateTotal()" style="font-weight:600;"></div>
                    <div class="form-group"><label>Grand Total (₱)</label><input type="text" id="totalPrice" readonly style="font-weight:800; background-color:#e0e7ff; color:#476eef; font-size:16px;" value="0.00"></div>
                </div>
                <div class="form-group"><label>Special Instructions</label><textarea id="buildNotes" rows="3"></textarea></div>
                <div class="modal-footer"><button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button><button type="button" class="btn-save" onclick="openPaymentModal()">Proceed to Payment <i class="fa-solid fa-arrow-right"></i></button></div>
            </form>
        </div>
    </div>

    <div id="pickerModal" class="modal" style="z-index: 2000;">
        <div class="modal-content modal-large">
            <div class="modal-header"><h2><i class="fa-solid fa-box-open"></i> Select Item</h2><i class="fa-solid fa-xmark close-btn" onclick="closePartPicker()"></i></div>
            <div class="picker-container">
                <div class="picker-sidebar" id="pickerCategories">
                    <div class="picker-cat active" onclick="filterPicker('All', this)">All Parts</div>
                    <div class="picker-cat" onclick="filterPicker('Processor', this)">Processor</div>
                    <div class="picker-cat" onclick="filterPicker('Motherboard', this)">Motherboard</div>
                    <div class="picker-cat" onclick="filterPicker('Graphics Card', this)">Graphics Card</div>
                    <div class="picker-cat" onclick="filterPicker('Memory', this)">Memory</div>
                    <div class="picker-cat" onclick="filterPicker('Storage', this)">Storage</div>
                    <div class="picker-cat" onclick="filterPicker('Power Supply', this)">Power Supply</div>
                    <div class="picker-cat" onclick="filterPicker('Case', this)">Case</div>
                    <div class="picker-cat" onclick="filterPicker('Cooling System', this)">Cooling</div>
                    <div class="picker-cat" onclick="filterPicker('Peripherals', this)">Peripherals</div>
                    <div class="picker-cat service-cat" onclick="filterPicker('Service', this)">
                        <i class="fa-solid fa-wrench"></i> Services
                    </div>
                </div>
                <div class="picker-grid" id="pickerGrid"></div>
            </div>
        </div>
    </div>

    <div id="serviceEditModal" class="modal" style="z-index: 2200;">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2 id="svcModalTitle">Add Service</h2>
                <i class="fa-solid fa-xmark close-btn" onclick="document.getElementById('serviceEditModal').style.display='none'"></i>
            </div>
            <form id="serviceForm">
                <input type="hidden" id="svcId">
                <div class="form-group">
                    <label>Service Name</label>
                    <input type="text" id="svcName" placeholder="e.g. Deep Cleaning" required>
                </div>
                <div class="form-group">
                    <label>Base Price (₱)</label>
                    <input type="number" id="svcPrice" placeholder="0.00" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('serviceEditModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn-save">Save Service</button>
                </div>
            </form>
        </div>
    </div>

    <div id="paymentModal" class="modal" style="z-index: 2100;">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header"><h2><i class="fa-solid fa-cash-register"></i> Process Payment</h2><i class="fa-solid fa-xmark close-btn" onclick="closePaymentModal()"></i></div>
            <div class="pay-summary">
                <div class="pay-row"><span>Parts Total:</span> <span id="payParts">0.00</span></div>
                <div class="pay-row"><span>Labor Fee:</span> <span id="payLabor">0.00</span></div>
                <div class="pay-row pay-total"><span>Grand Total:</span> <span id="payGrand">0.00</span></div>
            </div>
            <label style="font-size:12px; font-weight:700; color:#a3aed0;">PAYMENT TYPE</label>
            <div class="pay-options">
                <div class="pay-opt-btn active" id="btnFull" onclick="setPaymentType('Full')">Full Payment</div>
                <div class="pay-opt-btn" id="btnPartial" onclick="setPaymentType('Partial')">Partial / Down</div>
            </div>
            <div class="form-group"><label>Payment Method</label><select id="payMethod" style="width:100%; padding:10px; border-radius:8px; border:1px solid #ccc;"><option value="Cash">Cash</option><option value="G-Cash">G-Cash</option><option value="Bank Transfer">Bank Transfer</option><option value="Cheque">Cheque</option></select></div>
            <div class="form-group"><label>Amount Tendered (₱)</label><input type="number" id="payAmount" style="font-size:18px; font-weight:700; color:#476eef;" oninput="calculateBalance()"></div>
            <div class="balance-display" id="balanceDisplay">Balance Due: ₱0.00</div>
            <div class="modal-footer"><button class="btn-cancel" onclick="closePaymentModal()">Back</button><button class="btn-save" onclick="submitFinalOrder()">Confirm & Pay</button></div>
        </div>
    </div>

    <div id="validationModal" class="modal" style="z-index: 3000;"><div class="modal-content delete-content"><div class="icon-box icon-red"><i class="fa-solid fa-circle-exclamation"></i></div><div class="delete-title">Missing Information</div><div class="delete-text" id="validationText">Please add at least one part.</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" style="width:100%;" onclick="closeValidationModal()">Okay, Got it</button></div></div></div>
    <div id="completeModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-green"><i class="fa-solid fa-check"></i></div><div class="delete-title">Mark as Completed?</div><div class="delete-text">Finalize quote and record revenue?<br><br>Proceed?</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeCompleteModal()">Cancel</button><button class="btn-restore-confirm" onclick="executeComplete()">Yes, Complete It</button></div></div></div>
    
    <div id="archiveModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-orange"><i class="fa-solid fa-box-archive"></i></div><div class="delete-title">Archive?</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('archive')">Cancel</button><button class="btn-archive-confirm" onclick="executeArchive()">Archive</button></div></div></div>
    <div id="restoreModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-green"><i class="fa-solid fa-rotate-left"></i></div><div class="delete-title">Restore?</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('restore')">Cancel</button><button class="btn-restore-confirm" onclick="executeRestore()">Restore</button></div></div></div>
    <div id="deleteModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-red"><i class="fa-solid fa-trash-can"></i></div><div class="delete-title">Delete?</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('delete')">Cancel</button><button class="btn-danger" onclick="executeDelete()">Delete</button></div></div></div>
    <div id="successPopup" class="success-popup"><div class="success-icon"><i class="fa-solid fa-circle-check"></i></div><div class="success-title">Success!</div><div class="success-text" id="successMessage">Completed.</div></div>

    <script>
        const API_URL = "../api/builds_api.php";
        const CLIENTS_API = "../api/clients_api.php";
        const INVENTORY_API = "../api/api.php"; 
        const IMG_PATH = "../assets/uploads/";

        let builds = [];
        let clientsList = [];
        let inventoryList = [];
        let isViewingArchived = false;
        let targetId = null;
        let completeTargetId = null;
        let isNewClientMode = false;
        let currentPage = 1;
        let totalBuildsCount = 0;
        let buildsPerPage = 15;
        let currentPaymentType = "Full"; 

        async function init() {
            if(typeof applyPermissions === 'function') applyPermissions();
            await Promise.all([fetchClients(), fetchInventory(), loadBuilds()]);
        }

        // --- 1. CLIENT SEARCH LOGIC ---
        async function fetchClients() {
            try {
                const res = await fetch(CLIENTS_API + "?action=get_clients&limit=-1", { credentials: 'include' });
                const data = await res.json();
                if(Array.isArray(data)) clientsList = data; else if (data.clients) clientsList = data.clients; else clientsList = [];
            } catch(e) { console.error(e); }
        }

        function searchClients() {
            const input = document.getElementById("clientSearchInput");
            const filter = input.value.toLowerCase();
            const dropdown = document.getElementById("clientDropdown");
            dropdown.innerHTML = "";

            if (filter.length === 0) {
                dropdown.style.display = "none";
                return;
            }

            const matches = clientsList.filter(c => c.name.toLowerCase().includes(filter) && c.is_archived == 0);

            if (matches.length > 0) {
                matches.forEach(c => {
                    const div = document.createElement("div");
                    div.className = "client-option";
                    div.innerHTML = `${c.name} <small>${c.contact_info || 'No contact info'}</small>`;
                    div.onclick = function() {
                        input.value = c.name;
                        document.getElementById("clientSelectId").value = c.id;
                        dropdown.style.display = "none";
                    };
                    dropdown.appendChild(div);
                });
                dropdown.style.display = "block";
            } else {
                dropdown.innerHTML = "<div class='client-option' style='cursor:default; color:#a3aed0;'>No matching clients</div>";
                dropdown.style.display = "block";
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.querySelector('.client-search-container');
            if (container && !container.contains(e.target)) {
                document.getElementById("clientDropdown").style.display = "none";
            }
        });

        async function fetchInventory() {
            try {
                const res = await fetch(INVENTORY_API + "?limit=-1", { credentials: 'include' });
                const data = await res.json();
                inventoryList = Array.isArray(data) ? data : []; 
            } catch(e) { console.error(e); }
        }

        async function loadBuilds() {
            try {
                const search = document.getElementById("searchInput").value;
                const status = document.getElementById("statusFilter").value;
                const archived = isViewingArchived ? 1 : 0;
                
                let url = `${API_URL}?archived=${archived}&page=${currentPage}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&_t=${Date.now()}`;
                
                const res = await fetch(url, { credentials: 'include' });
                const data = await res.json();
                
                if(data.builds) { builds = data.builds; totalBuildsCount = data.total_builds; buildsPerPage = data.limit; } else { builds = []; }
                renderTable(); 
                renderPaginationControls();
            } catch(e) { console.error(e); }
        }
        function resetAndFetch() { currentPage = 1; loadBuilds(); }

        function renderTable() {
            const tbody = document.getElementById("buildsTableBody");
            tbody.innerHTML = "";
            if (builds.length === 0) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px; color:#a3aed0;">No quotations found.</td></tr>'; return; }
            
            builds.forEach(b => {
                let badgeClass = "pending";
                if(b.status === "In Progress") badgeClass = "in-progress";
                if(b.status === "Completed") badgeClass = "completed";
                if(b.status === "Cancelled") badgeClass = "cancelled";

                let payColor = "#ee5d50"; 
                if (b.payment_status === "Partial") payColor = "#ffb547";
                if (b.payment_status === "Paid") payColor = "#05cd99";

                let btns = `<div class="action-btn print-btn" onclick="printJobOrder(${b.id})" title="Print"><i class="fa-solid fa-print"></i></div>`;
                if (isViewingArchived) {
                    if(sessionStorage.getItem("userRole") !== 'sales_manager') btns += `<div class="action-btn restore-btn" onclick="openActionModal('restore', ${b.id})"><i class="fa-solid fa-rotate-left"></i></div>`;
                    if(sessionStorage.getItem("userRole") === 'admin') btns += `<div class="action-btn delete-btn" onclick="openActionModal('delete', ${b.id})"><i class="fa-solid fa-trash"></i></div>`;
                } else {
                    if (b.status !== 'Completed' && b.status !== 'Cancelled') {
                        btns += `<div class="action-btn mark-btn" onclick="openCompleteModal(${b.id})" title="Complete"><i class="fa-solid fa-check"></i></div>`;
                        btns += `<div class="action-btn edit-btn" onclick="editBuild(${b.id})"><i class="fa-solid fa-pen"></i></div>`;
                        if(sessionStorage.getItem("userRole") !== 'sales_manager') btns += `<div class="action-btn archive-btn" onclick="openActionModal('archive', ${b.id})"><i class="fa-solid fa-box-archive"></i></div>`;
                    }
                    if(sessionStorage.getItem("userRole") === 'admin') btns += `<div class="action-btn delete-btn" onclick="openActionModal('delete', ${b.id})"><i class="fa-solid fa-trash"></i></div>`;
                }
                
                

                const row = `
                    <tr class="data-row">
                        <td style="color:#a3aed0; font-size:12px;">#${escapeHtml(b.id)}</td>
                        <td style="font-weight:700;">${escapeHtml(b.display_name)}</td>
                        <td style="font-size:13px;">${escapeHtml(b.technician)}</td>
                        <td style="font-size:13px;"><span class="status-pill ${badgeClass}">${escapeHtml(b.status)}</span></td>
                        <td style="font-weight:700;">₱${Number(escapeHtml(b.total_price)).toLocaleString()}</td>
                        <td style="color:${payColor}; font-weight:600;">₱${Number(escapeHtml(b.amount_paid || 0)).toLocaleString()}</td>
                        <td style="font-size:12px; color:#a3aed0;">₱${Number(escapeHtml(b.balance_due || 0)).toLocaleString()}</td>
                        <td><div class="action-group" style="justify-content: flex-start;">${btns}</div></td>
                    </tr>`;
                tbody.innerHTML += row;
            });
        }

        async function executeSaveBuild(paidAmount = 0, payType = "Unpaid") {
            const parts = [];
            document.querySelectorAll('#partsBody tr').forEach(row => {
                const pid = row.querySelector('.part-select').value;
                const qty = row.querySelector('.part-qty').value;
                const price = row.querySelector('.part-price').value; 
                if(pid) parts.push({ product_id: pid, qty: qty, price: price });
            });

            const id = document.getElementById("buildId").value;
            const labor = parseFloat(document.getElementById("laborFee").value) || 0;
            
            let partsTotal = 0;
            parts.forEach(p => partsTotal += (p.price * p.qty));
            const grandTotal = partsTotal + labor;
            const balance = Math.max(0, grandTotal - paidAmount);
            
            let status = document.getElementById("status").value;
            let payStatus = "Unpaid";
            if (paidAmount >= grandTotal) payStatus = "Paid";
            else if (paidAmount > 0) payStatus = "Partial";

            const payload = {
                id: id, 
                technician: document.getElementById("technician").value, 
                status: status, 
                labor_fee: labor,
                notes: document.getElementById("buildNotes").value, 
                parts: parts,
                amount_paid: paidAmount,
                balance_due: balance,
                payment_status: payStatus,
                payment_method: document.getElementById("payMethod").value
            };

            if (isNewClientMode) {
                const name = document.getElementById("newClientName").value;
                if (!name) { showValidationModal("Client Name is required."); return; }
                payload.new_client = { name: name, contact: document.getElementById("newClientContact").value, address: document.getElementById("newClientAddress").value };
            } else {
                // FIXED: Get ID from hidden input
                const clientId = document.getElementById("clientSelectId").value;
                if (!clientId) { showValidationModal("Please select a client."); return; }
                payload.client_id = clientId;
            }

            try {
                const res = await fetch(API_URL, { 
                    method: id ? "PUT" : "POST", 
                    headers: {"Content-Type":"application/json", 'X-CSRF-Token': CSRF_TOKEN }, 
                    credentials:'include', 
                    body:JSON.stringify(payload)
                });
                
                const result = await res.json();
                if (result.error) { alert("System Error: " + result.error); return; }

                closePaymentModal();
                closeModal(); 
                currentPage = 1; 
                loadBuilds(); 
                fetchInventory(); 
                fetchClients(); 
                showSuccess(id ? "Updated & Payment Recorded!" : "Created & Paid!");

            } catch (err) { alert("Network Error. Check Console."); }
        }

        // --- PART PICKER & SERVICE GRID ---
        function openPartPicker() { document.getElementById("pickerModal").style.display = "block"; filterPicker('All', document.querySelector('.picker-sidebar .picker-cat')); }
        function closePartPicker() { document.getElementById("pickerModal").style.display = "none"; }
        
        function filterPicker(c, e) { 
            document.querySelectorAll('.picker-cat').forEach(el => el.classList.remove('active')); if(e) e.classList.add('active');
            const grid = document.getElementById("pickerGrid"); grid.innerHTML = "";
            
            // --- 2. SERVICE GRID LOGIC ---
            if (c === 'Service') {
                inventoryList.forEach(item => {
                    if (item.category === 'Service') {
                        // Admin Controls
                        let controls = '';
                        if (CURRENT_USER_ROLE === 'admin') {
                            controls = `
                                <div class="service-controls">
                                    <button class="svc-btn svc-edit" onclick="openServiceEditor(${item.id}, event)"><i class="fa-solid fa-pen"></i></button>
                                    <button class="svc-btn svc-del" onclick="deleteService(${item.id}, event)"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            `;
                        }

                        grid.innerHTML += `
                            <div class="service-card" onclick="selectPart(${item.id})">
                                ${controls}
                                <div class="service-title">${item.name}</div>
                                <div class="service-price">₱${Number(item.price).toLocaleString()}</div>
                            </div>
                        `;
                    }
                });

                // Add "New Service" Button at the end (Admin Only)
                if (CURRENT_USER_ROLE === 'admin') {
                    grid.innerHTML += `
                        <div class="add-service-card" onclick="openServiceEditor(null, event)">
                            <i class="fa-solid fa-plus add-service-icon"></i>
                        </div>
                    `;
                }
                return; // Stop here for Services
            }

            // --- STANDARD PARTS LOGIC ---
            inventoryList.forEach(item => { if (item.category === 'Service') return; 
                if (c === 'All' || item.category === c) {
                    const img = item.image ? IMG_PATH + item.image : IMG_PATH + 'default.png';
                    const cls = item.stock==0?'out-of-stock':(item.stock<5?'low':'');
                    grid.innerHTML += `<div class="part-card" onclick="selectPart(${item.id})"><img src="${img}" class="part-img"><div class="part-info"><div class="part-name">${item.name}</div><div class="part-price">₱${Number(item.price).toLocaleString()}</div></div><div class="part-overlay"><div class="overlay-stock ${cls}">${item.stock} in Stock</div><div style="margin-top:10px;font-size:10px;background:white;padding:4px 8px;border-radius:10px;color:#476eef;">Add</div></div></div>`;
                }
            });
        }

        // --- SERVICE EDITOR (ADMIN ONLY) ---
        function openServiceEditor(id, event) {
            if(event) event.stopPropagation(); // Prevent card click
            const modal = document.getElementById("serviceEditModal");
            const title = document.getElementById("svcModalTitle");
            
            if (id) {
                // Edit Mode
                const item = inventoryList.find(i => i.id == id);
                document.getElementById("svcId").value = item.id;
                document.getElementById("svcName").value = item.name;
                document.getElementById("svcPrice").value = item.price;
                title.innerText = "Edit Service";
            } else {
                // Add Mode
                document.getElementById("svcId").value = "";
                document.getElementById("svcName").value = "";
                document.getElementById("svcPrice").value = "";
                title.innerText = "Add New Service";
            }
            modal.style.display = "block";
        }

        // Save Service (Uses existing Inventory API)
        document.getElementById("serviceForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            const id = document.getElementById("svcId").value;
            const name = document.getElementById("svcName").value;
            const price = document.getElementById("svcPrice").value;

            const formData = new FormData();
            if(id) formData.append('id', id);
            formData.append('name', name);
            formData.append('price', price);
            formData.append('category', 'Service');
            formData.append('stock', 9999); // Unlimited stock for services
            formData.append('cost_price', 0);
            formData.append('supplier_id', 0); 
            formData.append('sku', 'SVC-' + Date.now()); // Auto generate SKU

            try {
                const res = await fetch(INVENTORY_API, {
                    method: "POST",
                    headers: { 'X-CSRF-Token': CSRF_TOKEN },
                    credentials: 'include',
                    body: formData
                });
                const data = await res.json();
                if(data.message) {
                    document.getElementById("serviceEditModal").style.display = "none";
                    fetchInventory().then(() => {
                        // Refresh grid if we are on Service tab
                        const activeCat = document.querySelector('.picker-cat.active');
                        if(activeCat && activeCat.innerText.includes("Services")) {
                            filterPicker('Service', activeCat);
                        }
                    });
                    showSuccess("Service Saved!");
                } else {
                    alert("Error: " + data.error);
                }
            } catch(err) { alert("Error saving service."); }
        });

        async function deleteService(id, event) {
            if(event) event.stopPropagation();
            if(!confirm("Delete this service?")) return;
            // Calls existing inventory delete API
            await fetch(`${INVENTORY_API}?id=${id}&type=hard`, { method:"DELETE", headers:{'X-CSRF-Token': CSRF_TOKEN}, credentials:'include'});
            fetchInventory().then(() => {
                filterPicker('Service', document.querySelector('.picker-cat.service-cat'));
            });
        }

        // --- EXISTING FUNCTIONS ---
        function selectPart(id) { addPartRow(id, 1); closePartPicker(); }
        function addPartRow(pid="", qty=1) { 
            const tbody=document.getElementById("partsBody"); const rid="row_"+Date.now();
            let opts='<option value="">Select...</option>';
            // Include Services in dropdown list too
            inventoryList.forEach(i=>{ if(i.stock>0||i.id==pid||i.category==='Service') opts+=`<option value="${i.id}" data-price="${i.price}" ${i.id==pid?'selected':''}>${i.name}</option>`; });
            const roClass = CURRENT_USER_ROLE === 'admin' ? '' : 'readonly';
            const roAttr = CURRENT_USER_ROLE === 'admin' ? '' : 'readonly';
            tbody.insertAdjacentHTML('beforeend', `<tr id="${rid}"><td><select class="part-select" onchange="updateRowPrice(this)">${opts}</select></td><td>-</td><td><input type="number" class="part-qty" value="${qty}" min="1" oninput="calculateTotal()"></td><td><input type="number" class="part-price part-price-input ${roClass}" ${roAttr} step="0.01" oninput="calculateTotal()"></td><td class="btn-sm-remove" onclick="removeRow('${rid}')">X</td></tr>`);
            if(pid) updateRowPrice(tbody.lastElementChild.querySelector('.part-select'));
        }
        function updateRowPrice(el) { const row=el.closest('tr'); const opt=el.options[el.selectedIndex]; if(opt.value) row.querySelector('.part-price').value=parseFloat(opt.getAttribute('data-price')); calculateTotal(); }
        function removeRow(id) { document.getElementById(id).remove(); calculateTotal(); }
        function calculateTotal() { 
            let t=0; document.querySelectorAll('#partsBody tr').forEach(r=>{ t+=(parseFloat(r.querySelector('.part-price').value)||0)*(parseFloat(r.querySelector('.part-qty').value)||0); }); 
            const l=parseFloat(document.getElementById("laborFee").value)||0; document.getElementById("totalPrice").value=(t+l).toLocaleString('en-US',{minimumFractionDigits:2}); 
        }
        function toggleNewClientMode() { isNewClientMode = !isNewClientMode; const btn = document.querySelector(".client-toggle-btn"); if(isNewClientMode){ document.getElementById("existingClientGroup").style.display="none"; document.getElementById("newClientFields").style.display="block"; btn.innerHTML="Select Existing"; document.getElementById("clientSearchInput").value=""; document.getElementById("clientSelectId").value=""; } else { document.getElementById("existingClientGroup").style.display="block"; document.getElementById("newClientFields").style.display="none"; btn.innerHTML="New Client?"; } }
        function showValidationModal(msg) { document.getElementById("validationText").innerText=msg; document.getElementById("validationModal").style.display="block"; }
        function closeValidationModal() { document.getElementById("validationModal").style.display="none"; }
        function closeWarningModal() { document.getElementById("warningModal").style.display="none"; }
        function openModal() { 
            document.getElementById("buildModal").style.display="block"; document.getElementById("buildForm").reset(); document.getElementById("partsBody").innerHTML=""; document.getElementById("buildId").value=""; 
            isNewClientMode=true; toggleNewClientMode(); addPartRow(); 
            // Clear search
            document.getElementById("clientSearchInput").value = "";
            document.getElementById("clientSelectId").value = "";
        }
        function closeModal() { document.getElementById("buildModal").style.display="none"; }
        function escapeHtml(t) { if(!t)return""; return String(t).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;"); }
        
        async function editBuild(id) { 
            const b=builds.find(x=>x.id==id); if(!b)return; 
            openModal(); 
            document.getElementById("buildId").value=b.id; 
            if(b.client_id){ 
                isNewClientMode=false; 
                toggleNewClientMode(); 
                // Populate Search Field with Name
                document.getElementById("clientSearchInput").value = b.display_name;
                document.getElementById("clientSelectId").value = b.client_id;
            } 
            document.getElementById("technician").value=b.technician; 
            document.getElementById("status").value=b.status; 
            document.getElementById("laborFee").value=b.labor_fee; 
            const res=await fetch(`${API_URL}?action=get_items&build_id=${id}`,{credentials:'include'}); 
            const i=await res.json(); 
            document.getElementById("partsBody").innerHTML=""; 
            if(i.length>0) i.forEach(x=>addPartRow(x.product_id, x.quantity)); 
            setTimeout(calculateTotal,100); 
            document.getElementById("modalTitle").innerText = "Edit Quotation"; 
        }

        async function printJobOrder(id) {
            const b = builds.find(x => x.id == id);
            if (!b) return;
            const res = await fetch(`${API_URL}?action=get_items&build_id=${id}`, { credentials: 'include' });
            const items = await res.json();
            let rows = items.map(i => `<tr><td>${i.name}</td><td>${i.quantity}</td><td style="text-align:right">₱${Number(i.price_at_time).toLocaleString()}</td></tr>`).join('');
            const w = window.open('', '', 'width=800,height=600');
            w.document.write(`<html><head><title>Print Job Order</title><style>body{font-family:sans-serif;padding:20px;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background:#f4f4f4;}.header{text-align:center;margin-bottom:30px;}</style></head><body><div class="header"><h1>PC Project Job Order</h1><p>Order #${b.id} | Date: ${b.date_added}</p></div><p><strong>Customer:</strong> ${b.display_name}</p><p><strong>Technician:</strong> ${b.technician}</p><table><thead><tr><th>Item</th><th>Qty</th><th style="text-align:right">Price</th></tr></thead><tbody>${rows}</tbody><tfoot><tr><td colspan="2" style="text-align:right;font-weight:bold;">Total</td><td style="text-align:right;font-weight:bold;">₱${Number(b.total_price).toLocaleString()}</td></tr></tfoot></table><br><p><strong>Notes:</strong> ${b.notes || 'None'}</p><script>window.print();<\/script></body></html>`);
            w.document.close();
        }

        function openPaymentModal() {
            let partsTotal = 0;
            document.querySelectorAll('#partsBody tr').forEach(row => {
                const p = parseFloat(row.querySelector('.part-price').value) || 0;
                const q = parseFloat(row.querySelector('.part-qty').value) || 0;
                partsTotal += (p * q);
            });
            const labor = parseFloat(document.getElementById("laborFee").value) || 0;
            const grandTotal = partsTotal + labor;

            document.getElementById("payParts").innerText = "₱" + partsTotal.toLocaleString();
            document.getElementById("payLabor").innerText = "₱" + labor.toLocaleString();
            document.getElementById("payGrand").innerText = "₱" + grandTotal.toLocaleString();
            
            setPaymentType('Full'); 
            document.getElementById("payAmount").value = grandTotal;
            calculateBalance();

            document.getElementById("paymentModal").style.display = "block";
        }

        function closePaymentModal() { document.getElementById("paymentModal").style.display = "none"; }

        function setPaymentType(type) {
            currentPaymentType = type;
            document.getElementById("btnFull").className = type === 'Full' ? 'pay-opt-btn active' : 'pay-opt-btn';
            document.getElementById("btnPartial").className = type === 'Partial' ? 'pay-opt-btn active' : 'pay-opt-btn';
            
            const total = parseFloat(document.getElementById("payGrand").innerText.replace(/[^0-9.-]+/g,""));
            if (type === 'Full') {
                document.getElementById("payAmount").value = total;
            } else {
                document.getElementById("payAmount").value = "";
                document.getElementById("payAmount").focus();
            }
            calculateBalance();
        }

        function calculateBalance() {
            const total = parseFloat(document.getElementById("payGrand").innerText.replace(/[^0-9.-]+/g,"")) || 0;
            const paid = parseFloat(document.getElementById("payAmount").value) || 0;
            
            if (currentPaymentType === 'Full') {
                const change = paid - total;
                document.getElementById("balanceDisplay").innerText = change >= 0 ? "Change: ₱" + change.toLocaleString() : "Insufficient Amount";
                document.getElementById("balanceDisplay").style.color = change >= 0 ? "#05cd99" : "#ee5d50";
            } else {
                const bal = total - paid;
                document.getElementById("balanceDisplay").innerText = "Balance Due: ₱" + (bal > 0 ? bal.toLocaleString() : "0.00");
                document.getElementById("balanceDisplay").style.color = "#ee5d50";
            }
        }

        function submitFinalOrder() {
            const payAmount = parseFloat(document.getElementById("payAmount").value) || 0;
            const total = parseFloat(document.getElementById("payGrand").innerText.replace(/[^0-9.-]+/g,"")) || 0;

            if (currentPaymentType === 'Full' && payAmount < total) { alert("Full payment requires amount to be equal or greater than Total."); return; }
            if (currentPaymentType === 'Partial' && payAmount <= 0) { alert("Please enter a downpayment amount."); return; }

            executeSaveBuild(payAmount, currentPaymentType);
        }

        function openActionModal(t,i) { targetId=i; if(t==='archive')document.getElementById("archiveModal").style.display='block'; if(t==='restore')document.getElementById("restoreModal").style.display='block'; if(t==='delete')document.getElementById("deleteModal").style.display='block'; }
        function closeActionModal(t) { targetId=null; if(t==='archive')document.getElementById("archiveModal").style.display='none'; if(t==='restore')document.getElementById("restoreModal").style.display='none'; if(t==='delete')document.getElementById("deleteModal").style.display='none'; }
        
        async function executeArchive() { if(targetId) await apiAction('DELETE', `&type=soft&id=${targetId}`, 'Archived'); }
        async function executeRestore() { if(targetId) await apiAction('POST', `&action=restore`, 'Restored', {id: targetId}); }
        async function executeDelete() { if(targetId) await apiAction('DELETE', `&type=hard&id=${targetId}`, 'Deleted'); }
        
        async function apiAction(m, p, msg, b=null) { 
            let o={method:m, credentials:'include', headers:{'X-CSRF-Token':CSRF_TOKEN}}; 
            if(b){o.body=JSON.stringify(b); o.headers["Content-Type"]="application/json";} 
            
            let url = API_URL;
            if (m === 'DELETE') {
                url += `?${p.substring(1)}`;
            } else {
                url += `?action=restore`;
            }

            await fetch(url, o); 
            closeActionModal('archive'); closeActionModal('restore'); closeActionModal('delete'); 
            loadBuilds(); 
            showSuccess(msg); 
        }

        function showSuccess(msg) { const p=document.getElementById("successPopup"); document.getElementById("successMessage").innerText=msg; p.style.display="flex"; setTimeout(()=>{p.style.display="none"},2000); }
        function openCompleteModal(id) { completeTargetId = id; document.getElementById("completeModal").style.display = "block"; }
        function closeCompleteModal() { document.getElementById("completeModal").style.display = "none"; completeTargetId = null; }
        
        async function executeComplete() { 
            if (completeTargetId) { 
                try { 
                    await fetch(API_URL, { 
                        method: "PUT", 
                        credentials: 'include', 
                        headers: { "Content-Type": "application/json", 'X-CSRF-Token': CSRF_TOKEN }, 
                        body: JSON.stringify({ id: completeTargetId, status: 'Completed' }) 
                    }); 
                    closeCompleteModal(); 
                    loadBuilds(); 
                    showSuccess("Marked as Completed!"); 
                } catch (e) { console.error(e); } 
            } 
        }
        
        function changePage(d) { currentPage+=d; loadBuilds(); }
        function renderPaginationControls() { /* Standard */ }
        function toggleArchives() { isViewingArchived = !isViewingArchived; document.getElementById("archiveToggleBtn").innerHTML = isViewingArchived ? '<i class="fa-solid fa-arrow-left"></i> Active' : '<i class="fa-solid fa-box-archive"></i> Archives'; currentPage = 1; loadBuilds(); }

        window.onclick = function(e) { if(e.target == document.getElementById("buildModal")) closeModal(); if(e.target == document.getElementById("paymentModal")) closePaymentModal(); if(e.target == document.getElementById("pickerModal")) closePartPicker(); if(e.target == document.getElementById("warningModal")) closeWarningModal(); if(e.target == document.getElementById("validationModal")) closeValidationModal(); if(e.target == document.getElementById("archiveModal")) closeActionModal('archive'); }
        
        document.addEventListener("DOMContentLoaded", init);
    </script>
</body>
</html>