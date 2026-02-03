<?php 
session_start();
define('ACCESS_ALLOWED', true); 

// 1. GENERATE CSRF TOKEN IF MISSING
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
    <title>PC Project - Sales Reports</title>
    <link rel="icon" type="image/png" href="../assets/img/logopc.png">
    <link rel="stylesheet" href="../assets/css/flatpickr.min.css">
    <script src="../assets/js/flatpickr.js"></script>
    <script src="../assets/js/chart.js"></script>
    <link href="../fontawesome/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/main.js?v=2"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <script>
        // PASS TOKEN AND USER TO JS
        const CURRENT_USER = "<?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Kyle Yee'; ?>";
        const CSRF_TOKEN = "<?php echo $csrf_token; ?>";
    </script>
    <style>
        .batch-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .batch-table th { background: #f8f9fc; padding: 10px; text-align: left; font-size: 12px; color: #a3aed0; font-weight: 600; border-bottom: 1px solid #eee; }
        .batch-table td { padding: 8px; border-bottom: 1px solid #eee; }
        .batch-input { width: 100%; border: 1px solid #e0e0e0; padding: 8px; border-radius: 8px; font-size: 13px; }
        .batch-select { width: 100%; border: 1px solid #e0e0e0; padding: 8px; border-radius: 8px; font-size: 13px; background: white; }
        .btn-add-row { background: #e0e7ff; color: #476eef; border: none; padding: 10px; width: 100%; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px; transition: 0.2s; }
        .btn-add-row:hover { background: #d0dbff; }
        .total-display { font-size: 18px; font-weight: 800; color: #2b3674; text-align: right; margin-top: 15px; }
        .scan-box-wrapper { display: flex; gap: 10px; margin-bottom: 15px; background: #f4f7fe; padding: 10px; border-radius: 8px; border: 1px dashed #a3aed0; align-items: center; }
        .scan-input { border: none; background: transparent; font-family: monospace; font-weight: 700; font-size: 14px; color: #476eef; flex: 1; outline: none; }
        .payment-section { background: #f8f9fc; padding: 15px; border-radius: 8px; margin-top: 15px; border: 1px solid #e0e7ff; }
        .payment-row { display: flex; justify-content: space-between; align-items: center; gap: 15px; margin-bottom: 10px; }
        .payment-label { font-weight: 600; color: #2b3674; }
        .big-money { font-size: 20px; font-weight: 800; width: 150px; text-align: right; padding: 5px; border: 2px solid #476eef; border-radius: 5px; }
        .change-text { font-size: 20px; font-weight: 800; color: #05cd99; }
        #paymentRefGroup { display: none; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #ccc; }
        .ref-input { width: 100%; padding: 8px; border: 1px solid #476eef; border-radius: 5px; font-family: monospace; color: #476eef; font-weight: 700; }

        /* --- NEW PAYMENT BUTTON STYLES --- */
        .payment-options { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px; }
        .pay-btn {
            background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 10px 5px;
            text-align: center; cursor: pointer; transition: 0.2s; color: #64748b; font-size: 12px; font-weight: 600;
            display: flex; flex-direction: column; align-items: center; gap: 5px;
        }
        .pay-btn i { font-size: 18px; margin-bottom: 2px; }
        .pay-btn:hover { border-color: #476eef; color: #476eef; background: #f4f7fe; }
        .pay-btn.active { background: #476eef; color: white; border-color: #476eef; box-shadow: 0 4px 10px rgba(71, 110, 239, 0.3); }
        .pay-btn.active i { color: white; }
    </style>
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div><h2>Financial Reports</h2></div>
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

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon icon-blue"><i class="fa-solid fa-peso-sign"></i></div>
                <div class="kpi-info"><h3>Total Revenue</h3><p id="kpiRevenue">₱0.00</p></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-orange"><i class="fa-solid fa-money-bill-transfer"></i></div>
                <div class="kpi-info"><h3>Total Cost</h3><p id="kpiCost">₱0.00</p></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon icon-green"><i class="fa-solid fa-chart-line"></i></div>
                <div class="kpi-info"><h3>Gross Profit</h3><p id="kpiProfit">₱0.00</p></div>
            </div>
        </div>

        <div class="chart-container"><canvas id="salesChart"></canvas></div>

        <div class="controls-container">
            <div class="search-wrapper"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="searchInput" placeholder="Search..." onkeyup="resetAndFetch()"></div>
            <div class="action-buttons">
                <input type="text" id="dateRange" class="btn-filter" placeholder="Filter by Date" style="width: 200px;">
                <select class="btn-filter" id="statusFilter" onchange="resetAndFetch()">
                    <option value="All">All Transactions</option>
                    <option value="Sold">Sold Only</option>
                    <option value="Returned">Returned Only</option>
                </select>
                <button class="btn-add" onclick="exportToCSV()"><i class="fa-solid fa-file-excel"></i> Export Excel</button>
                <button class="btn-add" onclick="openSaleModal()"><i class="fa-solid fa-plus"></i> Record Sale</button>
            </div>
        </div>

        <div id="paginationInfo" style="padding: 10px 0; font-size: 14px; color: #707eae;"></div>

        <div class="table-container">
            <table>
                <thead><tr><th>Date</th><th>Ref #</th><th>Product</th><th>Method</th><th>Revenue</th><th>Cost</th><th>Profit</th><th>Status</th><th>Action</th></tr></thead>
                <tbody id="reportsTableBody"></tbody>
            </table>
        </div>

        <div id="paginationControls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
            <button class="btn-filter" id="prevBtn" onclick="changePage(-1)" disabled>Previous</button>
            <span id="pageNumbers" style="font-weight: 600;">Page 1 of 1</span>
            <button class="btn-filter" id="nextBtn" onclick="changePage(1)" disabled>Next</button>
        </div>
    </main>

    <div id="saleModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header"><h2 id="modalTitle">Record Sales</h2><i class="fa-solid fa-xmark close-btn" onclick="closeModal()"></i></div>
            <form id="saleForm">
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="margin-bottom: 20px; flex: 1;"><label>Sale Date</label><input type="date" id="recordDate" class="batch-input"></div>
                </div>
                <div class="scan-box-wrapper"><i class="fa-solid fa-magnifying-glass" style="color: #a3aed0;"></i><input type="text" id="masterScanInput" class="scan-input" placeholder="Type Product Name OR Scan Barcode..." autocomplete="off"></div>
                <div style="max-height: 250px; overflow-y: auto;">
                    <table class="batch-table">
                        <thead><tr><th style="width: 50%;">Product</th><th style="width: 15%;">Stock</th><th style="width: 15%;">Qty</th><th style="width: 15%;">Subtotal</th><th style="width: 5%;"></th></tr></thead>
                        <tbody id="batchTableBody"></tbody>
                    </table>
                </div>
                <button type="button" class="btn-add-row" onclick="addBatchRow()"><i class="fa-solid fa-plus"></i> Add Manual Row</button>

                <div class="payment-section">
                    <div class="payment-row"><span class="payment-label">Grand Total:</span><div class="total-display" id="grandTotalDisplay" style="margin-top:0;">₱0.00</div></div>
                    
                    <span class="payment-label" style="display:block; margin-bottom:8px;">Payment Method:</span>
                    <div class="payment-options">
                        <div class="pay-btn active" onclick="selectPayment('Cash')">
                            <i class="fa-solid fa-money-bill-wave"></i> Cash
                        </div>
                        <div class="pay-btn" onclick="selectPayment('GCash')">
                            <i class="fa-solid fa-mobile-screen"></i> GCash
                        </div>
                        <div class="pay-btn" onclick="selectPayment('Bank Transfer')">
                            <i class="fa-solid fa-building-columns"></i> Bank
                        </div>
                        <div class="pay-btn" onclick="selectPayment('Cheque')">
                            <i class="fa-solid fa-money-check-dollar"></i> Cheque
                        </div>
                    </div>
                    <input type="hidden" id="paymentMethod" value="Cash">

                    <div id="paymentRefGroup">
                        <input type="text" id="paymentRef" class="ref-input" placeholder="Enter Reference No. / Transaction ID">
                    </div>
                    
                    <div class="payment-row" style="margin-top:15px;">
                        <span class="payment-label">Amount Received:</span>
                        <input type="number" id="cashReceived" class="big-money" placeholder="0.00" oninput="calculateChange()">
                    </div>
                    <div class="payment-row"><span class="payment-label">Change:</span><div id="changeDisplay" class="change-text">₱0.00</div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button><button type="submit" class="btn-save" id="btnSaveSale">PAY & PRINT</button></div>
            </form>
        </div>
    </div>

    <div id="returnModal" class="modal"><div class="modal-content" style="max-width:400px;"><div class="modal-header"><h2>Process Return</h2><i class="fa-solid fa-xmark close-btn" onclick="closeReturnModal()"></i></div><div class="form-group"><label>Reason</label><select id="returnReason" style="width:100%; padding:10px;"><option value="Defective">Defective</option><option value="Wrong Item">Wrong Item</option><option value="Change of Mind">Change of Mind</option></select></div><div class="modal-footer"><button class="btn-cancel" onclick="closeReturnModal()">Cancel</button><button class="btn-danger" onclick="executeReturn()">Confirm Return</button></div></div></div>
    <div id="voidModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-red"><i class="fa-solid fa-ban"></i></div><div class="delete-title">Void Transaction?</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeVoidModal()">Cancel</button><button class="btn-danger" onclick="executeVoid()">Yes, Void It</button></div></div></div>
    <div id="successPopup" class="success-popup"><div class="success-icon"><i class="fa-solid fa-circle-check"></i></div><div class="success-title">Success!</div><div class="success-text" id="successMessage">Action completed.</div></div>

    <script>
        // --- 1. GLOBAL VARIABLES ---
        const SALES_API = "../api/reports_api.php";
        const INVENTORY_API = "../api/api.php"; 
        
        let inventoryList = [];
        let salesList = [];
        let chartDataCache = {}; 
        let targetId = null;
        
        let currentPage = 1;
        let totalRecords = 0;
        let itemsPerPage = 15;
        let dateStart = '';
        let dateEnd = '';
        let currentGrandTotal = 0; 

        // DOM Element References (Defined Global to avoid ReferenceError)
        let modal, returnModal, voidModal;

        // --- 2. INITIALIZATION ---
        document.addEventListener("DOMContentLoaded", function() {
            if(typeof applyPermissions === 'function') applyPermissions();
            
            // Assign DOM elements after load
            modal = document.getElementById("saleModal");
            returnModal = document.getElementById("returnModal");
            voidModal = document.getElementById("voidModal");

            flatpickr("#dateRange", {
                mode: "range", dateFormat: "Y-m-d",
                onChange: function(dates) {
                    if (dates.length === 2) {
                        dateStart = dates[0].toISOString().split('T')[0];
                        dateEnd = dates[1].toISOString().split('T')[0];
                        setDateFilter('custom');
                        resetAndFetch();
                    }
                }
            });
            
            // Scanner Listener
            const scanInput = document.getElementById("masterScanInput");
            if(scanInput) {
                scanInput.addEventListener("keydown", function(e) {
                    if (e.key === "Enter") {
                        e.preventDefault();
                        const val = this.value.trim().toLowerCase();
                        if (val === "") { document.getElementById("cashReceived").focus(); return; }
                        const item = inventoryList.find(i => (i.sku && i.sku.toLowerCase() === val) || (i.name && i.name.toLowerCase().includes(val)));
                        if (item && item.stock > 0) {
                            addBatchRow(item.id);
                            this.value = "";
                        } else {
                            alert("Product not found or out of stock");
                            this.select();
                        }
                    }
                });
            }

            // Keyboard Shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') return;
                    e.preventDefault();
                    if(modal.style.display !== 'block') openSaleModal();
                }
                if (e.key === 'Escape') { if (modal.style.display === 'block') closeModal(); }
            });

            // --- AUTO-OPEN MODAL FROM DASHBOARD ---
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('open_modal') === 'true') {
                setTimeout(() => { openSaleModal(); }, 500); 
            }

            loadData();
        });

        // --- 3. DATA LOADING ---
        async function loadData() { await fetchInventory(); fetchReports(); }
        async function fetchInventory() { try { const res = await fetch(INVENTORY_API + "?limit=-1", { credentials: 'include' }); const data = await res.json(); inventoryList = Array.isArray(data) ? data : []; } catch (err) { console.error(err); } }
        
        async function fetchReports() {
            const search = document.getElementById("searchInput").value;
            const status = document.getElementById("statusFilter").value;
            let url = `${SALES_API}?page=${currentPage}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
            if (dateStart && dateEnd) url += `&start_date=${dateStart}&end_date=${dateEnd}`;
            try {
                const res = await fetch(url, { credentials: 'include' });
                const data = await res.json();
                salesList = data.sales;
                totalRecords = data.pagination.total_records;
                itemsPerPage = data.pagination.limit;
                chartDataCache = data.chart;

                renderSales(salesList);
                renderPagination();
                updateKPIs(data.kpi); 
                renderChart();
            } catch (err) { console.error(err); }
        }

        function resetAndFetch() { currentPage = 1; fetchReports(); }

        // --- 4. RENDER FUNCTIONS ---
        function renderSales(data) {
            const tbody = document.getElementById("reportsTableBody");
            tbody.innerHTML = "";
            if (data.length === 0) { tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:30px; color:#a3aed0;">No records found.</td></tr>'; return; }
            const role = sessionStorage.getItem("userRole");
            data.forEach(s => {
                const isRet = s.status === 'Returned';
                const badgeClass = isRet ? 'badge-returned' : 'badge-sold';
                const rowClass = isRet ? 'returned-row' : '';
                const displayMethod = s.payment_method || 'Cash';
                let btns = `<div class="receipt-btn" onclick="printReceipt(${s.id})"><i class="fa-solid fa-receipt"></i></div>`;
                if (!isRet && role !== 'sales_manager') btns += `<div class="return-btn" onclick="openReturnModal(${s.id})"><i class="fa-solid fa-rotate-left"></i></div>`;
                if (!isRet && role === 'admin') btns += `<div class="receipt-btn" onclick="openVoidModal(${s.id})" style="color:#ee5d50; margin-left:5px;"><i class="fa-solid fa-trash"></i></div>`;

                tbody.innerHTML += `<tr class="${rowClass}"><td>${new Date(s.sale_date).toLocaleDateString()}</td><td style="font-weight:700;">${escapeHtml(s.transaction_id)}</td><td>${escapeHtml(s.product_name)} <span style="font-size:11px; color:#a3aed0;">(x${s.quantity})</span></td><td><span style="font-size:11px; background:#f4f7fe; padding:2px 6px; border-radius:4px; color:#476eef; font-weight:700;">${escapeHtml(displayMethod)}</span></td><td style="font-weight:700;">₱${Number(s.total_price).toLocaleString()}</td><td style="font-size:13px; color:#a3aed0;">₱${Number(s.cost_price * s.quantity).toLocaleString()}</td><td style="font-weight:700; color:${s.profit >= 0 ? '#05cd99' : '#ee5d50'};">₱${Number(s.profit).toLocaleString()}</td><td><span class="status-badge ${badgeClass}">${s.status}</span></td><td><div style="display:flex;">${btns}</div></td></tr>`;
            });
        }

        function updateKPIs(kpi) {
            const elRev = document.getElementById("kpiRevenue");
            const elCost = document.getElementById("kpiCost");
            const elProf = document.getElementById("kpiProfit");
            
            if(elRev && kpi) elRev.innerText = "₱" + Number(kpi.revenue).toLocaleString();
            if(elCost && kpi) elCost.innerText = "₱" + Number(kpi.total_cost).toLocaleString();
            if(elProf && kpi) elProf.innerText = "₱" + Number(kpi.gross_profit).toLocaleString();
        }

        let myChart = null;
        function renderChart() {
            if(!chartDataCache || !chartDataCache.labels) return;
            const ctx = document.getElementById('salesChart').getContext('2d');
            if (myChart) myChart.destroy();
            myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartDataCache.labels,
                    datasets: [
                        { label: 'Revenue', data: chartDataCache.revenue, backgroundColor: '#476eef', borderRadius: 4 },
                        { label: 'Profit', data: chartDataCache.profit, backgroundColor: '#05cd99', borderRadius: 4 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true } }, scales: { y: { beginAtZero: true } } }
            });
        }

        // --- 5. MODAL FUNCTIONS ---
        function openSaleModal() { 
            if(modal) modal.style.display = "block"; 
            document.getElementById("batchTableBody").innerHTML = "";
            currentGrandTotal = 0;
            document.getElementById("grandTotalDisplay").innerText = "₱0.00";
            document.getElementById("changeDisplay").innerText = "₱0.00";
            document.getElementById("cashReceived").value = "";
            document.getElementById("masterScanInput").focus();
        }

        function closeModal() { if(modal) modal.style.display="none"; }
        function openReturnModal(id) { targetId=id; if(returnModal) returnModal.style.display="block"; }
        function closeReturnModal() { if(returnModal) returnModal.style.display="none"; }
        function openVoidModal(id) { targetId=id; if(voidModal) voidModal.style.display="block"; }
        function closeVoidModal() { if(voidModal) voidModal.style.display="none"; }

        // --- 6. UTILITY FUNCTIONS ---
        
        // Function to handle button clicks for payments
        function selectPayment(method) {
            document.getElementById("paymentMethod").value = method;
            
            // Visual Update
            document.querySelectorAll('.pay-btn').forEach(btn => btn.classList.remove('active'));
            event.currentTarget.classList.add('active');

            // Show/Hide Reference Input
            const refGroup = document.getElementById("paymentRefGroup");
            const refInput = document.getElementById("paymentRef");
            
            if (method !== 'Cash') {
                refGroup.style.display = 'block';
                refInput.required = true;
                setTimeout(() => refInput.focus(), 100);
            } else {
                refGroup.style.display = 'none';
                refInput.required = false;
                refInput.value = "";
            }
        }

        function addBatchRow(pid=null) { 
            const tbody = document.getElementById("batchTableBody"); const rid = "row_" + Date.now();
            let opts = '<option value="">Select Product...</option>';
            inventoryList.forEach(i => { if(i.stock>0) opts+=`<option value="${i.id}" data-price="${i.price}" data-name="${i.name}" data-stock="${i.stock}" ${pid==i.id?'selected':''}>${i.name}</option>`; });
            tbody.insertAdjacentHTML('beforeend', `<tr id="${rid}"><td><select class="batch-select" onchange="updateBatchRow(this)">${opts}</select></td><td><input type="text" class="batch-input stock-display" readonly style="background:#f4f7fe; color:#a3aed0;" value="-"></td><td><input type="number" class="batch-input qty-input" value="1" min="1" oninput="calculateBatchTotal()"></td><td><input type="text" class="batch-input subtotal-display" readonly value="0.00"></td><td style="text-align:center;"><i class="fa-solid fa-times" style="color:#ee5d50; cursor:pointer;" onclick="removeBatchRow('${rid}')"></i></td></tr>`);
            if(pid) updateBatchRow(document.getElementById(rid).querySelector('select'));
        }

        function updateBatchRow(s) { const r=s.closest('tr'); const o=s.options[s.selectedIndex]; if(o.value){r.querySelector('.stock-display').value=o.getAttribute('data-stock');r.setAttribute('data-price',o.getAttribute('data-price'));} calculateBatchTotal(); }
        function calculateBatchTotal() { let t=0; document.querySelectorAll('#batchTableBody tr').forEach(r=>{ const p=parseFloat(r.getAttribute('data-price'))||0; const q=parseInt(r.querySelector('.qty-input').value)||0; r.querySelector('.subtotal-display').value=(p*q).toFixed(2); t+=p*q; }); currentGrandTotal=t; document.getElementById("grandTotalDisplay").innerText="₱"+t.toLocaleString(); calculateChange(); }
        function removeBatchRow(id) { document.getElementById(id).remove(); calculateBatchTotal(); }
        function calculateChange() { const c=parseFloat(document.getElementById("cashReceived").value)||0; const ch=c-currentGrandTotal; const d=document.getElementById("changeDisplay"); if(ch>=0){d.innerText="₱"+ch.toLocaleString(undefined,{minimumFractionDigits:2}); d.style.color="#05cd99"; document.getElementById("btnSaveSale").disabled=false;}else{d.innerText="Insufficient"; d.style.color="#ee5d50"; document.getElementById("btnSaveSale").disabled=true;} }

        // --- 7. ACTION EXECUTION ---
document.getElementById("saleForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            
            // 1. Collect Items
            const items = [];
            document.querySelectorAll('#batchTableBody tr').forEach(row => {
                const sel = row.querySelector('select');
                const qty = row.querySelector('.qty-input').value;
                const sub = row.querySelector('.subtotal-display').value;
                
                if(sel.value && qty > 0) {
                    items.push({ 
                        product_id: sel.value, 
                        product_name: sel.options[sel.selectedIndex].getAttribute('data-name'), 
                        quantity: qty, 
                        total_price: sub 
                    });
                }
            });

            if (items.length === 0) { alert("Please add at least one item!"); return; }
            
            // 2. Prepare Data
            let finalMethod = document.getElementById("paymentMethod").value;
            const refNo = document.getElementById("paymentRef").value;
            if (finalMethod !== 'Cash' && refNo) { finalMethod += ` (Ref: ${refNo})`; }

            const payload = { 
                items: items, 
                custom_date: document.getElementById("recordDate").value, 
                payment_method: finalMethod 
            };
            
            const cash = parseFloat(document.getElementById("cashReceived").value) || 0;

            // 3. Send to Server (WITH DEBUGGING & AUTO PRINT)
            try {
                const res = await fetch(SALES_API, { 
                    method: "POST", 
                    headers: { "Content-Type": "application/json", 'X-CSRF-Token': CSRF_TOKEN }, 
                    body: JSON.stringify(payload) 
                });
                
                // --- DEBUG STEP: Get Raw Text First ---
                const rawText = await res.text();
                console.log("Raw Server Response:", rawText); // Check Console (F12) if error persists

                try {
                    const result = JSON.parse(rawText); // Try parsing JSON
                    
                    if (result.success || result.transaction_id) { 
                        closeModal(); 
                        resetAndFetch(); 
                        showSuccess("Payment Successful!");
                        
                        // --- AUTO PRINT LOGIC ---
                        // Ensure result contains necessary receipt data
                        // If API doesn't return date/items, fallback to local data
                        if(!result.date) result.date = new Date().toLocaleDateString();
                        if(!result.items) result.items = items; 
                        
                        // Trigger Print
                        printBatchReceipt(result, cash, (cash - currentGrandTotal));
                        
                    } else { 
                        alert("System Error: " + (result.error || "Unknown error")); 
                    }
                } catch (jsonError) {
                    // This block catches the PHP HTML Errors and shows them to you
                    alert("PHP CRASH ERROR:\n\n" + rawText);
                }

            } catch (err) { 
                alert("Critical Network Error: " + err.message); 
            }
        });

        // --- IMPROVED PRINT FUNCTION (UPDATED) ---
        function openPrintWindow(ref, date, rows, total, cash, change, method) {
            const w = window.open('', '_blank', 'width=900,height=1100');
            w.document.write(`
                <html>
                <head>
                    <title>Receipt</title>
                    <style>
                        body{font-family:Arial;font-size:12px; padding:20px;}
                        .header{text-align:center;margin-bottom:20px;font-weight:bold;}
                        table{width:100%;border-collapse:collapse;margin-top:10px;}
                        th,td{border-bottom:1px solid #ccc;padding:5px;text-align:left;}
                        .totals{margin-top:20px;text-align:right;font-weight:bold; font-size:14px;}
                    </style>
                </head>
                <body>
                    <div class="header">
                        PC PROJECT<br>OFFICIAL RECEIPT<br>
                        Transaction: ${ref}<br>
                        Date: ${date}
                    </div>
                    <table>
                        <thead><tr><th>Qty</th><th>Item</th><th>Price</th></tr></thead>
                        <tbody>${rows}</tbody>
                    </table>
                    <div class="totals">
                        Total: ₱${Number(total).toLocaleString()}<br>
                        Cash: ₱${Number(cash).toLocaleString()}<br>
                        Change: ₱${Number(change).toLocaleString()}
                    </div>
                    <script>
                        // Wait for content to load, then print and close
                        window.onload = function() { window.print(); window.close(); }
                    <\/script>
                </body>
                </html>
            `);
            w.document.close(); // IMPORTANT: Finishes loading so print can start
        }

        async function executeReturn() { 
            // FIXED: Use PHP-injected CSRF_TOKEN
            await fetch(SALES_API+"?action=return_item", { 
                method: "POST", 
                headers: { "Content-Type": "application/json", 'X-CSRF-Token': CSRF_TOKEN }, 
                body: JSON.stringify({ sale_id: targetId, reason: document.getElementById("returnReason").value }) 
            }); 
            closeReturnModal(); resetAndFetch(); showSuccess("Returned!"); 
        }
        
        async function executeVoid() { 
            // FIXED: Use PHP-injected CSRF_TOKEN
            await fetch(SALES_API+"?id="+targetId, { 
                method: "DELETE", 
                headers: { 'X-CSRF-Token': CSRF_TOKEN } 
            }); 
            closeVoidModal(); resetAndFetch(); showSuccess("Voided!"); 
        }
        
// --- 8. PRINT & EXPORT ---
async function exportToCSV() {
            const search = document.getElementById("searchInput").value;
            const status = document.getElementById("statusFilter").value;
            
            // Prepare URL
            let url = `${SALES_API}?limit=-1&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
            if (dateStart && dateEnd) url += `&start_date=${dateStart}&end_date=${dateEnd}`;
            
            try {
                const res = await fetch(url, { credentials: 'include' });
                const data = await res.json();
                
                // --- FIX STARTS HERE ---
                // We must check where the array is. Usually it is inside data.sales
                const rows = data.sales || (Array.isArray(data) ? data : []);

                if (rows.length === 0) {
                    alert("No data to export.");
                    return;
                }

                let csv = "Date,Ref ID,Product,Qty,Payment Method,Revenue,Cost,Profit,Status\n";
                
                rows.forEach(row => {
                    const profit = row.total_price - (row.cost_price * row.quantity);
                    const cost = row.cost_price * row.quantity;
                    // Remove commas from name to prevent CSV breaking
                    const name = (row.product_name || "Item").replace(/,/g, " "); 
                    
                    csv += `${row.sale_date},${row.transaction_id},${name},${row.quantity},${row.payment_method},${row.total_price},${cost},${profit},${row.status}\n`;
                });
                // --- FIX ENDS HERE ---

                const blob = new Blob([csv], { type: 'text/csv' });
                const link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = `Report_${new Date().toISOString().slice(0,10)}.csv`;
                link.click();

            } catch (err) { 
                console.error(err);
                alert("Failed to export. Check console for details."); 
            }
        }

        function printBatchReceipt(data, cash, change) {
            let rowsHtml = ""; let grandTotal = 0;
            data.items.forEach(item => {
                const price = Number(item.total_price); const unitPrice = price / item.quantity; grandTotal += price;
                rowsHtml += `<tr><td class="col-qty" style="text-align:center;">${item.quantity}</td><td class="col-desc">${item.product_name}</td><td class="col-serial" style="text-align:center;">-</td><td class="col-price">₱${unitPrice.toLocaleString()}</td></tr>`;
            });
            openPrintWindow(data.transaction_id, data.date, rowsHtml, grandTotal, cash, change, "Cash");
        }

// --- 1. PREPARE RECEIPT DATA ---
async function printReceipt(id) { 
            const clickedSale = salesList.find(s => s.id == id); 
            if (!clickedSale) return;

            // Group items by Transaction ID
            let receiptItems = []; 
            if (clickedSale.transaction_id) { 
                receiptItems = salesList.filter(s => s.transaction_id === clickedSale.transaction_id); 
            } else { 
                receiptItems = [clickedSale]; 
            }

            // Generate HTML for Table Rows
            let rowsHtml = ""; 
            let grandTotal = 0;
            
            receiptItems.forEach(item => {
                const price = Number(item.total_price); 
                const unitPrice = price / item.quantity; 
                grandTotal += price;
                
                // Try to find Serial Number (if available in your data)
                // If not available, it leaves it blank as per image
                const serial = item.serial_number || ""; 

                rowsHtml += `
                    <tr>
                        <td class="col-qty">${item.quantity}</td>
                        <td class="col-desc">
                            <span style="font-weight:700;">${item.product_name}</span>
                        </td>
                        <td class="col-serial">${serial}</td>
                        <td class="col-price">₱${Number(item.total_price).toLocaleString()}</td>
                    </tr>
                `;
            });

            // Attempt to get Client Details (If available in your sales data)
            // Note: If your Sales API doesn't send client info, these will be defaults.
            const cName = clickedSale.customer_name || "Walk-in Customer"; 
            const cContact = clickedSale.contact_info || ""; 
            const cAddress = clickedSale.address || ""; 

            // Open the Print Window
            openPrintWindow(
                clickedSale.transaction_id || clickedSale.id, 
                clickedSale.sale_date, 
                rowsHtml, 
                grandTotal, 
                grandTotal, // Assuming full payment for report history
                0, 
                clickedSale.payment_method,
                cName, cContact, cAddress
            );
        }

        // --- 2. EXACT A4 LAYOUT (Front & Back) ---
        function openPrintWindow(ref, date, rows, total, cash, change, method) {
            // Calculate Balance for the display
            let balance = 0;
            if (cash < total) {
                balance = total - cash;
            }

            const w = window.open('', '', 'width=900,height=1100');
            w.document.write(`
                <html>
                <head>
                    <title>Acknowledgment Receipt - ${ref}</title>
                    <style>
                        /* PRINT SETTINGS: FORCE A4 PORTRAIT, NO MARGINS */
                        @media print { 
                            @page { size: A4 portrait; margin: 0; }
                            body { margin: 0; padding: 0; -webkit-print-color-adjust: exact; }
                        }

                        body {
                            font-family: Arial, sans-serif;
                            font-size: 11px;
                            color: #000;
                            background: #fff;
                            margin: 0;
                        }

                        /* PAGE CONTAINER: EXACT A4 HEIGHT */
                        .page-container {
                            width: 210mm;
                            height: 296mm; /* A4 Height */
                            padding: 15mm 20mm; /* Standard Margins */
                            box-sizing: border-box;
                            position: relative;
                            display: flex;
                            flex-direction: column;
                            overflow: hidden; /* Prevent spillover */
                        }

                        .page-break { page-break-after: always; }

                        /* --- HEADER --- */
                        .header { display: flex; align-items: flex-start; margin-bottom: 20px; }
                        .logo-box { width: 100px; margin-right: 20px; }
                        .logo-box img { width: 100%; height: auto; object-fit: contain; }
                        
                        .company-info { margin-top: 10px; text-align: center; }
                        .company-name { font-size: 16px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 5px 0; }
                        .company-details { font-size: 10px; font-weight: 700; margin: 2px 0; text-transform: uppercase; }

                        .title { text-align: center; font-weight: 900; font-size: 14px; text-transform: uppercase; margin: 30px 0; letter-spacing: 1px; }

                        /* --- CUSTOMER INFO --- */
                        .customer-info { display: flex; justify-content: space-between; font-weight: 700; font-size: 11px; margin-bottom: 20px; }
                        .info-left { width: 60%; }
                        .info-right { width: 30%; text-align: right; }
                        .info-line { margin-bottom: 5px; }

                        /* --- TABLE --- */
                        .flex-grow { flex: 1; } /* Pushes footer down */
                        
                        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 11px; }
                        th { text-align: left; font-weight: 900; padding: 5px 0; border-bottom: 1px solid #fff; } /* Invisible border to match look */
                        td { padding: 5px 0; vertical-align: top; font-weight: 500; }
                        
                        .col-qty { width: 10%; }
                        .col-desc { width: 40%; }
                        .col-serial { width: 35%; text-align: center; }
                        .col-price { width: 15%; text-align: right; }

                        /* --- FOOTER SECTION --- */
                        .footer-section { display: flex; justify-content: space-between; margin-top: 10px; padding-top: 10px; }
                        
                        .notes { width: 60%; font-size: 10px; color: #c0392b; font-weight: 700; line-height: 1.4; }
                        .note-label { color: #c0392b; font-weight: 900; margin-right: 5px; }

                        .totals { width: 35%; font-size: 11px; font-weight: 700; }
                        .total-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
                        
                        /* EXACT COLORS FROM IMAGE */
                        .val-total { color: #c0392b; font-weight: 900; } /* Red */
                        .val-paid { color: #27ae60; font-weight: 900; } /* Green */
                        .val-dp { color: #7f8c8d; font-weight: 900; } /* Gray */
                        .val-bal { color: #f39c12; font-weight: 900; } /* Orange */

                        /* --- SIGNATURES --- */
                        .signatures { display: flex; justify-content: space-between; margin-top: 50px; font-size: 10px; }
                        .sig-block { width: 40%; text-align: center; }
                        .sig-name { font-weight: 900; font-size: 11px; text-transform: uppercase; margin-bottom: 2px; }
                        .sig-role { font-style: italic; margin-bottom: 2px; font-weight: normal; }
                        .sig-line { border-top: 1px solid #000; margin-top: 2px; padding-top: 5px; font-weight: 700; font-size: 9px; }

                        /* --- BACK PAGE (WARRANTY) --- */
                        .w-header { text-align: center; margin-bottom: 20px; }
                        .w-logo { width: 60px; margin-bottom: 5px; }
                        .w-title { font-weight: 900; font-size: 14px; text-transform: uppercase; }

                        .policy-text { font-size: 10px; line-height: 1.4; margin-bottom: 8px; text-align: justify; }
                        .w-bold { font-weight: 800; }
                        .w-italic { font-style: italic; font-size: 9px; text-decoration: underline; }
                        
                        .w-list { margin: 5px 0 10px 15px; padding: 0; list-style: none; }
                        .w-list li { font-size: 10px; margin-bottom: 2px; position: relative; }
                        .w-list li:before { content: ">"; position: absolute; left: -10px; font-weight: bold; }

                        .w-table { width: 100%; border-collapse: collapse; margin: 15px 0; border: 2px solid #000; }
                        .w-table th { background: #fff; border: 1px solid #000; text-align: center; font-weight: 900; font-size: 9px; padding: 5px; }
                        .w-table td { border: 1px solid #000; height: 25px; }

                        .agreement { text-align: center; font-size: 9px; font-weight: 700; font-style: italic; margin: 20px 0; }
                        
                        .w-footer-row { display: flex; justify-content: space-between; align-items: flex-end; margin-top: auto; padding-bottom: 10px; }
                        .w-sig-line { border-top: 2px solid #000; width: 180px; text-align: center; padding-top: 5px; font-weight: 800; font-size: 10px; }
                        .w-warning { text-align: center; color: #c0392b; font-weight: 900; font-size: 10px; flex: 1; margin: 0 10px; }
                        .w-warning span { color: #000; font-weight: 500; font-size: 8px; display: block; margin-top: 2px; }

                    </style>
                </head>
                <body>

                    <div class="page-container page-break">
                        
                        <div class="header">
                            <div class="logo-box"><img src="../assets/img/logopc.png"></div>
                            <div class="company-info">
                                <p class="company-name">PC PROJECT I.T. SERVICES</p>
                                <p class="company-details">GF GOKING BUILDING, CORRALES AVENUE, CAGAYAN DE ORO CITY 9000</p>
                                <p class="company-details">CONTACT NO : 09171831487</p>
                            </div>
                        </div>

                        <div class="title">ACKNOWLEDGMENT</div>

                        <div class="customer-info">
                            <div class="info-left">
                                <div class="info-line">Name: </div>
                                <div class="info-line">Contact #: </div>
                                <div class="info-line">Address: </div>
                            </div>
                            <div class="info-right">
                                <div class="info-line" style="margin-bottom: 25px;">Date:</div> <div class="info-line"></div> </div>
                        </div>
                        
                        <div class="flex-grow">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="col-qty">Qty</th>
                                        <th class="col-desc">Product/Service</th>
                                        <th class="col-serial">Serial #</th>
                                        <th class="col-price">Price</th>
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>

                        <div class="footer-section">
                            <div class="notes">
                                <span class="note-label">Note:</span>
                                Please keep all the boxes for warranty purposes.<br>
                                Serial number/s recorded via store database.<br>
                                Any special warranty indicated in the official warranty policy.
                            </div>
                            <div class="totals">
                                <div class="total-row"><span>Total:</span> <span class="val-total">₱${total.toLocaleString(undefined, {minimumFractionDigits: 2})}</span></div>
                                <div class="total-row"><span>Paid via:</span> <span class="val-paid">${method || 'Cash'}</span></div>
                                <div class="total-row"><span>DP-Bank:</span> <span class="val-dp">₱${Number(cash).toLocaleString()}</span></div>
                                <div class="total-row"><span>Balance:</span> <span class="val-bal">₱${balance.toLocaleString(undefined, {minimumFractionDigits: 2})}</span></div>
                            </div>
                        </div>

                        <div class="signatures">
                            <div class="sig-block">
                                <div class="sig-name">Kyle Yee</div>
                                <div class="sig-line">Prepared by: SIGNATURE OVER PRINTED NAME</div>
                            </div>
                            <div class="sig-block">
                                <div class="sig-name"><br></div>
                                <div class="sig-line">Received by: SIGNATURE OVER PRINTED NAME</div>
                            </div>
                        </div>

                        <div class="signatures" style="margin-top: 40px; justify-content: flex-start;">
                            <div class="sig-block">
                                <div class="sig-name">Kyle Yee</div>
                                <div class="sig-role">Cashier</div>
                                <div class="sig-line">Approved & Released by: SIGNATURE OVER PRINTED NAME</div>
                            </div>
                        </div>

                    </div>

                    <div class="page-container">
                        <div class="w-header">
                            <img src="../assets/img/logopc.png" class="w-logo"><br>
                            <span class="company-name" style="font-size: 12px;">PC PROJECT I.T. SERVICES</span><br>
                            <span class="w-title">WARRANTY & REPLACEMENT POLICY</span>
                        </div>

                        <div class="flex-grow">
                            <p class="policy-text">We stand by the quality of our products and offer the following warranty coverage:</p>

                            <p class="policy-text"><span class="w-bold">1-Year Warranty:</span> Applies to major parts such as the motherboard, processor, memory, graphics card, storage, 80+ certified PSU, and monitor. Should any of these components malfunction due to manufacturing defects, they will be repaired or replaced within one year of the purchase date.</p>

                            <p class="policy-text"><span class="w-bold">6-Month Warranty:</span> Applies to ordinary types of power supply units (PSU). Any defects related to manufacturing or performance within this period will be addressed.</p>

                            <p class="policy-text"><span class="w-bold">1-Week Warranty:</span> Peripherals including the keyboard, mouse, and headset are covered for 1 week from the purchase date. If any issues arise due to manufacturing defects within this timeframe, a replacement or repair will be offered.</p>

                            <p class="policy-text"><span class="w-bold">1-Week Replacement Policy:</span> Purchased items (excluding custom-built systems) can be replaced within one week of purchase if they are found to be defective or damaged upon receipt.</p>

                            <p class="policy-text w-italic">Disclaimer: Warranty coverage is subject to evaluation for special warranty to specific items as identified by technicians and sales team.</p>

                            <p class="policy-text">During the warranty period, all defective items will be subject to inspection. PC Project will replace defective items at no charge, provided that the client meets the warranty policy requirements and returns the item within the specified period.</p>
                            <ul class="w-list">
                                <li>Limit on Replacements: Items can only be replaced twice within the warranty period.</li>
                                <li>Out-of-Stock Replacements: If the item is no longer available or has been phased out, PC Project reserves the right to offer an alternative brand/model based on the current market value or actual purchase price, whichever is lower. In the case of upgrades, additional payment may be required, subject to customer agreement.</li>
                            </ul>

                            <p class="policy-text">This warranty covers manufacturing defects and does not extend to damage caused by misuse, accidents, or unauthorized modifications. For any warranty-related claims, please contact our customer service team.</p>

                            <p class="policy-text w-bold">General Warranty Conditions</p>
                            <p class="policy-text">Warranty will be void under the following circumstances:</p>
                            <ul class="w-list">
                                <li>Damage caused by accidents, misuse, misapplication, or abnormal causes (e.g., animal bites, wrong voltage, floods, earthquakes, etc.).</li>
                                <li>Tampering with the warranty seal or any modification of the product.</li>
                                <li>Unauthorized repairs or services outside of PC Project.</li>
                                <li>Alteration, defacing, or removal of the product's serial number.</li>
                                <li>Loss of product serial number, box, or any related materials.</li>
                                <li>Confiscation of items by authorities due to unlicensed software.</li>
                            </ul>

                            <table class="w-table">
                                <thead>
                                    <tr>
                                        <th width="30%">SPECIAL WARRANTY ITEMS</th>
                                        <th width="30%">WARRANTY COVERAGE</th>
                                        <th width="20%">WARRANTY EXPIRY</th>
                                        <th width="20%">EVALUATOR:</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
                                    <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
                                </tbody>
                            </table>

                            <div class="agreement">
                                CUSTOMER AGREEMENT<br>
                                <span style="font-weight: 400; font-style: normal;">By signing this document, you acknowledge the warranty and replacement terms and agree to the outlined conditions.<br>All items are received complete and in good condition.</span>
                            </div>
                        </div>

                        <div class="w-footer-row">
                            <div class="w-sig-line">SIGNATURE:</div>
                            <div class="w-warning">
                                NO BOX, NO RECEIPT, NO WARRANTY POLICY
                                <span>All items valid for warranty as set out in this policy are indicated at the back of this document.</span>
                            </div>
                            <div class="w-sig-line" style="width: 100px;">DATE:</div>
                        </div>
                    </div>

                    <script>
                        setTimeout(() => { window.print(); }, 1000);
                    <\/script>
                </body>
                </html>
            `);
        }

        function showSuccess(msg) { document.getElementById("successMessage").innerText=msg; document.getElementById("successPopup").style.display="flex"; setTimeout(()=>document.getElementById("successPopup").style.display="none",2000); }
        function escapeHtml(text) { if(!text)return""; return String(text).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;"); }
        function setDateFilter(p, b) { document.querySelectorAll('.date-btn').forEach(x=>x.classList.remove('active')); if(b) b.classList.add('active'); const t=new Date(); const f=(d)=>d.toISOString().split('T')[0]; if(p==='today'){dateStart=f(t);dateEnd=f(t);}else if(p==='week'){const w=new Date();w.setDate(t.getDate()-6);dateStart=f(w);dateEnd=f(t);}else if(p==='all'){dateStart='';dateEnd='';const picker=document.getElementById("dateRange")._flatpickr;if(picker)picker.clear();} resetAndFetch(); }
        function renderPagination() { const t=Math.ceil(totalRecords/itemsPerPage); const c=document.getElementById('paginationControls'); if(t<=1){c.style.display='none';return;} c.style.display='flex'; document.getElementById('prevBtn').disabled=currentPage<=1; document.getElementById('nextBtn').disabled=currentPage>=t; document.getElementById('pageNumbers').innerText=`Page ${currentPage} of ${t}`; }
        function changePage(d) { currentPage+=d; fetchReports(); }
        
        window.onclick = function(e) { if(modal && e.target==modal)closeModal(); if(returnModal && e.target==returnModal)closeReturnModal(); if(voidModal && e.target==voidModal)closeVoidModal(); }
    </script>
</body>
</html>