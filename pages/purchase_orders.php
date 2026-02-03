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
    <title>PC Project - Orders</title>
    <link rel="icon" type="image/png" href="../assets/img/logopc.png">
    <style> body { display: none; } </style>
    <script>
        const CSRF_TOKEN = "<?php echo $csrf_token; ?>";
        const role = sessionStorage.getItem("userRole");
        const userName = sessionStorage.getItem("name") || 'Admin';

        if (role === 'sales_manager') {
            alert("⛔ Access Denied: Sales Managers cannot access Purchase Orders.");
            window.location.href = "dashboard.php"; 
        } else {
            document.addEventListener("DOMContentLoaded", function() {
                document.body.style.display = "flex"; 
            });
        }
    </script>
    <link href="../fontawesome/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/main.js?v=2"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .print-btn { background: rgba(147, 51, 234, 0.1); color: #9333ea; }
        .print-btn:hover { background: rgba(147, 51, 234, 0.2); color: #7e22ce; }
        .serial-group { max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px; border-radius: 8px; margin: 10px 0; background: #f9f9f9; }
        .serial-row { display: flex; gap: 10px; margin-bottom: 5px; align-items: center; }
        .serial-row span { font-size: 12px; color: #888; width: 30px; }
        .serial-row input { flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div><h2>Incoming Orders</h2></div>
            <div class="user-badge">
                <?php 
                    $hasAvatar = isset($_SESSION['avatar']) && !empty($_SESSION['avatar']);
                    $avatarPath = $hasAvatar ? "../assets/uploads/" . $_SESSION['avatar'] : "../assets/img/logopc.png";
                    $displayImg = $hasAvatar ? "block" : "none";
                ?>
                <img id="headerAvatar" src="<?php echo $avatarPath; ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover; margin-right:8px; display:<?php echo $displayImg; ?>;">   
                <i id="headerIcon" class="fa-solid fa-user-circle fa-lg" style="display:<?php echo $displayIcon; ?>;"></i>
                <span id="headerUserName"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?></span>
            </div>
        </header>

        <div class="controls-container">
            <div style="font-size:14px; color:#a3aed0;">Track orders from your suppliers.</div>
            <div class="action-buttons">
                <button class="btn-filter" id="toggleArchiveOrders" onclick="toggleArchiveView()"><i class="fa-solid fa-box-archive"></i> Archives</button>
                <button class="btn-add" onclick="openModal()"><i class="fa-solid fa-plus"></i> Create Order</button>
            </div>
        </div>

        <div id="paginationInfo" style="padding: 10px 0; font-size: 14px; color: #707eae;"></div>

        <div class="table-container">
            <table>
                <thead><tr><th>Supplier</th><th>Item Ordered</th><th>Qty</th><th>Cost</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                <tbody id="ordersTableBody"></tbody>
            </table>
        </div>

        <div id="paginationControls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
            <button class="btn-filter" id="prevBtn" onclick="changePage(-1)" disabled><i class="fa-solid fa-arrow-left"></i> Previous</button>
            <span id="pageNumbers" style="font-weight: 600;">Page 1 of 1</span>
            <button class="btn-filter" id="nextBtn" onclick="changePage(1)" disabled>Next <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </main>

    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2 id="modalTitle">New Purchase Order</h2><i class="fa-solid fa-xmark close-btn" onclick="closeModal()"></i></div>
            <form id="orderForm">
                <input type="hidden" id="orderId">
                <div class="form-group"><label>Supplier Name</label><select id="suppName" required><option value="">Loading suppliers...</option></select></div>
                <div class="form-group">
                    <label>Select Product (Auto-Restock)</label>
                    <select id="prodSelect" required onchange="updateProductField()"><option value="">Loading inventory...</option></select>
                    <input type="hidden" id="prodName">
                </div>
                <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div><label>Quantity</label><input type="number" id="ordQty" required></div>
                    <div><label>Total Cost (₱)</label><input type="number" id="ordCost" step="0.01" min="0" required></div>
                </div>
                <div class="form-group"><label>Status</label><select id="ordStatus"><option value="Pending">Pending</option><option value="Cancelled">Cancelled</option></select></div>
                <div class="modal-footer"><button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button><button type="submit" class="btn-save">Save Order</button></div>
            </form>
        </div>
    </div>

    <div id="receiveModal" class="modal" style="z-index: 2000;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-box-open"></i> Receive Stock</h2>
                <i class="fa-solid fa-xmark close-btn" onclick="closeReceiveModal()"></i>
            </div>
            <p style="font-size:13px; color:#a3aed0;">Enter Serial Numbers for the <b><span id="recvQtyDisplay">0</span></b> items received.</p>
            
            <form id="receiveForm">
                <div id="serialInputsContainer" class="serial-group"></div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeReceiveModal()">Cancel</button>
                    <button type="submit" class="btn-save">Confirm & Add to Stock</button>
                </div>
            </form>
        </div>
    </div>

    <div id="archiveModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-orange"><i class="fa-solid fa-box-archive"></i></div><div class="delete-title">Archive Order?</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('archive')">Cancel</button><button class="btn-archive-confirm" onclick="executeArchive()">Archive</button></div></div></div>
    <div id="restoreModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-green"><i class="fa-solid fa-rotate-left"></i></div><div class="delete-title">Restore Order?</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('restore')">Cancel</button><button class="btn-restore-confirm" onclick="executeRestore()">Restore</button></div></div></div>
    <div id="deleteModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-red"><i class="fa-solid fa-trash-can"></i></div><div class="delete-title">Permanent Delete?</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('delete')">Cancel</button><button class="btn-danger" onclick="executeDelete()">Delete</button></div></div></div>
    <div id="successPopup" class="success-popup"><div class="success-icon"><i class="fa-solid fa-circle-check"></i></div><div class="success-title">Success!</div><div class="success-text" id="successMessage">Completed.</div></div>

    <script>
        const API_URL = "../api/orders_api.php";
        const SUPPLIERS_API = "../api/suppliers_api.php";
        const INVENTORY_API = "../api/api.php";
        
        let orders = [];
        let inventoryList = [];
        let showArchived = false;
        let targetId = null;
        let receiveTargetId = null;
        let receiveQty = 0;
        let currentPage = 1;
        let totalOrdersCount = 0;
        let ordersPerPage = 15;

        // DOM ELEMENTS
        const tableBody = document.getElementById("ordersTableBody");
        const modal = document.getElementById("orderModal");
        const archiveModal = document.getElementById("archiveModal");
        const restoreModal = document.getElementById("restoreModal");
        const deleteModal = document.getElementById("deleteModal");
        const receiveModal = document.getElementById("receiveModal");

        function showSuccess(message) {
            const popup = document.getElementById("successPopup");
            document.getElementById("successMessage").innerText = message;
            popup.style.display = "flex"; 
            setTimeout(() => { popup.style.display = "none"; }, 2000);
        }

        async function init() {
            await Promise.all([fetchOrders(), loadSupplierDropdown(), loadInventoryDropdown()]);
            applyPermissions();
        }

        async function fetchOrders() {
            try {
                const url = showArchived 
                    ? `${API_URL}?archived=1&page=${currentPage}&_t=${Date.now()}` 
                    : `${API_URL}?page=${currentPage}&_t=${Date.now()}`;
                
                const res = await fetch(url, { credentials: 'include' });
                const data = await res.json();
                orders = data.orders;
                totalOrdersCount = data.total_orders;
                ordersPerPage = data.limit;
                renderTable();
                renderPaginationControls();
            } catch (err) { console.error(err); }
        }

        function renderTable() {
            tableBody.innerHTML = "";
            if (orders.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px; color:#a3aed0;">No orders found.</td></tr>';
                return;
            }

            orders.forEach(o => {
                let badge = "badge-pending";
                let actionBtn = "";
                if(o.status === "Delivered") badge = "badge-delivered";
                if(o.status === "Cancelled") badge = "badge-cancelled";

                const printBtn = `<button class="action-btn print-btn" onclick="printPO(${o.id})" title="Print PO Document"><i class="fa-solid fa-print"></i></button>`;

                if (showArchived) {
                    actionBtn = `
                        <div class="action-group">
                            <button class="action-btn restore-btn" onclick="openActionModal('restore', ${o.id})" title="Restore"><i class="fa-solid fa-rotate-left"></i></button>
                            ${role === 'admin' ? `<button class="action-btn delete-btn" onclick="openActionModal('delete', ${o.id})" title="Delete"><i class="fa-solid fa-trash"></i></button>` : ''}
                        </div>`;
                } else {
                    if (o.status === 'Delivered') {
                        actionBtn = `
                            <div class="action-group" style="justify-content: flex-start;">
                                ${printBtn}
                                <span style="color:#05cd99; font-size:12px; font-weight:700; margin-left:5px;"><i class="fa-solid fa-check-circle"></i> Received</span>
                                ${role === 'admin' ? `<button class="action-btn delete-btn" onclick="openActionModal('delete', ${o.id})" title="Void/Delete"><i class="fa-solid fa-trash"></i></button>` : ''}
                                ${role !== 'sales_manager' ? `<button class="action-btn archive-btn" onclick="openActionModal('archive', ${o.id})" title="Archive Order"><i class="fa-solid fa-box-archive"></i></button>` : ''}
                            </div>`;
                    } else if (o.status === 'Cancelled') {
                         actionBtn = `<span style="color:#ee5d50; font-size:12px; font-weight:700;">Cancelled</span>`;
                    } else {
                        actionBtn = `
                            <div class="action-group">
                                ${printBtn}
                                <button class="action-btn edit-btn" onclick="editOrder(${o.id})" title="Edit Order"><i class="fa-solid fa-pen"></i></button>
                                <button class="action-btn mark-btn" onclick="openReceiveModal(${o.id}, ${o.quantity})" title="Mark Received & Add Serials"><i class="fa-solid fa-box-open"></i></button>
                                <button class="action-btn archive-btn" onclick="openActionModal('archive', ${o.id})" title="Archive Order"><i class="fa-solid fa-box-archive"></i></button>
                                ${role === 'admin' ? `<button class="action-btn delete-btn" onclick="openActionModal('delete', ${o.id})" title="Delete"><i class="fa-solid fa-trash"></i></button>` : ''}
                            </div>`;
                    }
                }
                
                const row = `
                    <tr class="data-row">
                        <td style="font-weight:700;">${escapeHtml(o.supplier_name)}</td>
                        <td>${escapeHtml(o.product_name)}</td>
                        <td>${escapeHtml(o.quantity)}</td>
                        <td>₱${Number(o.total_cost).toLocaleString()}</td>
                        <td style="font-size:12px; color:#a3aed0;">${escapeHtml(o.order_date).split(' ')[0]}</td>
                        <td><span class="status-badge ${badge}">${escapeHtml(o.status)}</span></td>
                        <td>${actionBtn}</td>
                    </tr>`;
                tableBody.innerHTML += row;
            });
            applyPermissions();
        }

        // --- RECEIVE MODAL LOGIC (UPDATED) ---
        function openReceiveModal(id, qty) {
            receiveTargetId = id;
            receiveQty = qty;
            document.getElementById("recvQtyDisplay").innerText = qty;
            
            const container = document.getElementById("serialInputsContainer");
            container.innerHTML = "";
            
            for(let i = 1; i <= qty; i++) {
                container.innerHTML += `
                    <div class="serial-row">
                        <span>#${i}</span>
                        <input type="text" name="serial[]" placeholder="Scan Serial Number" required>
                    </div>
                `;
            }
            
            receiveModal.style.display = "block";
            // Focus on first input
            setTimeout(() => { 
                const firstInput = container.querySelector('input');
                if(firstInput) firstInput.focus();
            }, 100);
        }

        function closeReceiveModal() { 
            receiveModal.style.display = "none"; 
            receiveTargetId = null; 
        }

        document.getElementById("receiveForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            if (!receiveTargetId) return;

            // Gather Serial Numbers
            const inputs = document.querySelectorAll('input[name="serial[]"]');
            const serials = [];
            inputs.forEach(input => {
                if(input.value.trim() !== "") serials.push(input.value.trim());
            });

            if (serials.length !== receiveQty) {
                alert(`Please enter all ${receiveQty} serial numbers.`);
                return;
            }

            try {
                const payload = { 
                    id: receiveTargetId, 
                    status: 'Delivered',
                    serials: serials // Send Serials to API
                };

                await fetch(API_URL, {
                    method: "PUT", 
                    credentials: 'include',
                    headers: { "Content-Type": "application/json", 'X-CSRF-Token': CSRF_TOKEN },
                    body: JSON.stringify(payload)
                });
                
                closeReceiveModal(); 
                fetchOrders(); 
                showSuccess("Stock & Serials Added Successfully!");
            } catch (e) { console.error(e); }
        });

        // --- OTHER FUNCTIONS ---
        function printPO(id) { /* Same print logic as before */ 
             const order = orders.find(o => o.id == id);
            if (!order) return;
            const unitCost = order.total_cost / order.quantity;
            const date = order.order_date.split(' ')[0];
            const w = window.open('', '', 'width=800,height=600');
            w.document.write(`<html><head><title>Purchase Order #${order.id}</title><style>body{font-family:'Helvetica',sans-serif;padding:40px;color:#333;max-width:800px;margin:auto;}.header{display:flex;justify-content:space-between;margin-bottom:40px;border-bottom:2px solid #476eef;padding-bottom:20px;}.logo{font-size:28px;font-weight:bold;color:#2b3674;line-height:1;}.logo span{font-size:12px;color:#999;font-weight:400;text-transform:uppercase;letter-spacing:1px;}.po-title{text-align:right;}.po-title h1{margin:0;font-size:24px;color:#476eef;}.po-title p{margin:5px 0 0;color:#666;font-weight:bold;}.info-grid{display:flex;justify-content:space-between;margin-bottom:40px;gap:40px;}.box{flex:1;}h3{font-size:11px;text-transform:uppercase;color:#a3aed0;margin:0 0 8px 0;letter-spacing:1px;}p{margin:3px 0;font-size:14px;color:#333;}table{width:100%;border-collapse:collapse;margin-bottom:30px;}th{text-align:left;padding:12px;background:#f8f9fc;color:#2b3674;font-size:12px;text-transform:uppercase;border-bottom:2px solid #eee;}td{padding:15px 12px;border-bottom:1px solid #eee;font-size:14px;}.total-section{display:flex;justify-content:flex-end;}.total-box{width:250px;background:#f8f9fc;padding:20px;border-radius:8px;}.total-row{display:flex;justify-content:space-between;margin-bottom:5px;font-size:14px;}.grand-total{font-size:18px;font-weight:bold;color:#476eef;border-top:1px solid #ddd;padding-top:10px;margin-top:10px;}.footer{margin-top:80px;display:flex;justify-content:space-between;}.signature-line{border-top:1px solid #333;width:200px;padding-top:10px;text-align:center;font-size:12px;font-weight:600;}</style></head><body><div class="header"><div class="logo">PC Project <br><span>Technology Solutions</span></div><div class="po-title"><h1>PURCHASE ORDER</h1><p>#PO-${order.id}</p></div></div><div class="info-grid"><div class="box"><h3>Vendor (Supplier)</h3><p style="font-weight:bold; font-size:16px;">${order.supplier_name}</p><p>Philippines</p></div><div class="box" style="text-align:right;"><h3>Order Details</h3><p><b>Date:</b> ${date}</p><p><b>Status:</b> ${order.status.toUpperCase()}</p><p><b>Prepared By:</b> ${userName}</p></div></div><table><thead><tr><th>Item Description</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Total</th></tr></thead><tbody><tr><td><b>${order.product_name}</b></td><td style="text-align:center;">${order.quantity}</td><td style="text-align:right;">₱${Number(unitCost).toLocaleString(undefined,{minimumFractionDigits:2})}</td><td style="text-align:right;">₱${Number(order.total_cost).toLocaleString(undefined,{minimumFractionDigits:2})}</td></tr></tbody></table><div class="total-section"><div class="total-box"><div class="total-row"><span>Subtotal:</span> <span>₱${Number(order.total_cost).toLocaleString()}</span></div><div class="total-row"><span>Tax (0%):</span> <span>₱0.00</span></div><div class="total-row grand-total"><span>Total:</span> <span>₱${Number(order.total_cost).toLocaleString()}</span></div></div></div><div class="footer"><div class="signature-line">Authorized Signature</div><div class="signature-line">Date Accepted</div></div><script>window.print();<\/script></body></html>`);
            w.document.close();
        }

        function renderPaginationControls() {
            const totalPages = Math.ceil(totalOrdersCount / ordersPerPage);
            const paginationControls = document.getElementById('paginationControls');
            const paginationInfo = document.getElementById('paginationInfo');
            if (totalPages <= 1) {
                if(paginationControls) paginationControls.style.display = 'none';
                if(paginationInfo) paginationInfo.innerText = `Total Orders: ${totalOrdersCount}`;
                return;
            }
            if(paginationControls) paginationControls.style.display = 'flex';
            let endItem = (currentPage * ordersPerPage);
            if(endItem > totalOrdersCount) endItem = totalOrdersCount;
            let startItem = ((currentPage - 1) * ordersPerPage) + 1;
            if(paginationInfo) paginationInfo.innerText = `Displaying ${startItem} - ${endItem} of ${totalOrdersCount} orders`;
            document.getElementById('prevBtn').disabled = currentPage <= 1;
            document.getElementById('nextBtn').disabled = currentPage >= totalPages;
            document.getElementById('pageNumbers').innerText = `Page ${currentPage} of ${totalPages}`;
        }

        function changePage(direction) {
            const totalPages = Math.ceil(totalOrdersCount / ordersPerPage);
            const newPage = currentPage + direction;
            if (newPage >= 1 && newPage <= totalPages) { currentPage = newPage; fetchOrders(); }
        }

        async function loadSupplierDropdown() {
            try {
                const res = await fetch(SUPPLIERS_API + "?limit=-1", { credentials: 'include' });
                const data = await res.json();
                const suppliers = Array.isArray(data) ? data : (data.suppliers || []);
                const select = document.getElementById("suppName");
                select.innerHTML = '<option value="">-- Select Supplier --</option>';
                suppliers.forEach(s => { if (s.status === 'Active') select.innerHTML += `<option value="${s.company_name}">${s.company_name}</option>`; });
            } catch (err) { console.error(err); }
        }

        async function loadInventoryDropdown() {
            try {
                const res = await fetch(INVENTORY_API + "?limit=-1", { credentials: 'include' });
                inventoryList = await res.json();
                const select = document.getElementById("prodSelect");
                select.innerHTML = '<option value="">-- Select Inventory Item --</option>';
                inventoryList.forEach(item => { if(item.is_archived == 0) select.innerHTML += `<option value="${item.id}" data-name="${item.name}">${item.name}</option>`; });
            } catch(e) { console.error(e); }
        }

        function updateProductField() {
            const sel = document.getElementById("prodSelect");
            const opt = sel.options[sel.selectedIndex];
            document.getElementById("prodName").value = opt.getAttribute("data-name");
        }

        function openModal() { 
            modal.style.display = "block"; 
            document.getElementById("orderForm").reset();
            document.getElementById("orderId").value = ""; 
            document.getElementById("modalTitle").innerText = "New Purchase Order";
        }
        function closeModal() { modal.style.display = "none"; }

        function openActionModal(type, id) {
            targetId = id;
            if (type === 'archive') archiveModal.style.display = "block";
            if (type === 'restore') restoreModal.style.display = "block";
            if (type === 'delete') deleteModal.style.display = "block";
        }
        function closeActionModal(type) {
            if (type === 'archive') archiveModal.style.display = "none";
            if (type === 'restore') restoreModal.style.display = "none";
            if (type === 'delete') deleteModal.style.display = "none";
            targetId = null;
        }

        async function executeArchive() { if(targetId) await apiAction('DELETE', `&id=${targetId}`, 'Order Archived'); }
        async function executeRestore() { if(targetId) await apiAction('POST', `&action=restore`, 'Order Restored', {id: targetId}); }
        async function executeDelete() { if(targetId) await apiAction('DELETE', `&action=hard&id=${targetId}`, 'Order Deleted'); }

        async function apiAction(method, params, msg, body = null) {
            let opts = { method: method, credentials: 'include', headers: { 'X-CSRF-Token': CSRF_TOKEN } };
            if(body) { opts.body = JSON.stringify(body); opts.headers["Content-Type"] = "application/json"; }
            await fetch(API_URL + (method === 'DELETE' ? `?${params.substring(1)}` : `?action=restore`), opts);
            closeActionModal('archive'); closeActionModal('restore'); closeActionModal('delete');
            fetchOrders(); showSuccess(msg);
        }

        function toggleArchiveView() {
            showArchived = !showArchived;
            currentPage = 1;
            const btn = document.getElementById("toggleArchiveOrders");
            const addBtn = document.querySelector(".btn-add");
            if (showArchived) {
                btn.style.background = "#e0e7ff"; btn.style.color = "#476eef"; btn.innerHTML = '<i class="fa-solid fa-arrow-left"></i> Back to Active';
                addBtn.style.display = "none";
            } else {
                btn.style.background = "white"; btn.style.color = "#2b3674"; btn.innerHTML = '<i class="fa-solid fa-box-archive"></i> Archives';
                addBtn.style.display = "flex";
            }
            fetchOrders();
        }

        window.onclick = function(e) { 
            if(e.target == modal) closeModal(); 
            if(e.target == archiveModal) closeActionModal('archive'); 
            if(e.target == restoreModal) closeActionModal('restore'); 
            if(e.target == deleteModal) closeActionModal('delete');
            if(e.target == receiveModal) closeReceiveModal();
        }

        function editOrder(id) {
            const order = orders.find(o => o.id == id);
            if (order) {
                document.getElementById("orderId").value = order.id;
                document.getElementById("suppName").value = order.supplier_name;
                document.getElementById("prodSelect").value = order.product_id;
                document.getElementById("prodName").value = order.product_name;
                document.getElementById("ordQty").value = order.quantity;
                document.getElementById("ordCost").value = order.total_cost;
                document.getElementById("ordStatus").value = order.status;
                document.getElementById("modalTitle").innerText = "Edit Purchase Order";
                modal.style.display = "block";
            }
        }

        document.getElementById("orderForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            const id = document.getElementById("orderId").value;
            const payload = {
                id: id,
                supplier_name: document.getElementById("suppName").value,
                product_id: document.getElementById("prodSelect").value,
                product_name: document.getElementById("prodName").value,
                quantity: document.getElementById("ordQty").value,
                total_cost: document.getElementById("ordCost").value,
                status: document.getElementById("ordStatus").value
            };
            const method = id ? "PUT" : "POST";
            
            await fetch(API_URL, { 
                method: method, 
                credentials: 'include', 
                headers: { "Content-Type": "application/json", 'X-CSRF-Token': CSRF_TOKEN }, 
                body: JSON.stringify(payload) 
            });
            closeModal(); fetchOrders(); showSuccess(id ? "Order Updated!" : "Order Created!");
        });

        function escapeHtml(text) { if(text === null || text === undefined) return ""; return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;"); }
        function applyPermissions() { if(role === 'staff') { document.querySelectorAll('.delete-btn').forEach(el => el.style.display = 'none'); } }

        init();
    </script>
</body>
</html>