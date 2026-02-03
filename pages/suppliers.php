<?php 
session_start();
define('ACCESS_ALLOWED', true); 

// --- GENERATE CSRF TOKEN (Required for API Calls) ---
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
    <title>PC Project - Suppliers</title>
    <link rel="icon" type="image/png" href="../assets/img/logopc.png">
    <script>
        // PASS TOKEN TO JS
        const CSRF_TOKEN = "<?php echo $csrf_token; ?>";
        
        const role = sessionStorage.getItem("userRole");
        if (role === 'sales_manager') {
            alert("⛔ Access Denied: Sales Managers cannot access Suppliers.");
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
            <div><h2>Supplier Database</h2></div>
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
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search suppliers..." onkeyup="resetAndFetch()">
            </div>
            
            <div class="action-buttons">
                <select class="btn-filter" id="statusFilter" onchange="resetAndFetch()">
                    <option value="All">All Statuses</option>
                    <option value="Active">Active Only</option>
                    <option value="Inactive">Inactive Only</option>
                </select>
                
                <button class="btn-filter" id="toggleArchiveSuppliers" onclick="toggleArchiveView()">
                    <i class="fa-solid fa-box-archive"></i> Archives
                </button>

                <button class="btn-add" onclick="openModal()">
                    <i class="fa-solid fa-plus"></i> Add Supplier
                </button>
            </div>
        </div>

        <div id="paginationInfo" style="padding: 10px 0; font-size: 14px; color: #707eae;"></div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Contact Person</th>
                        <th>Products Supplied</th> 
                        <th>Status</th>
                        <th>Total Spend</th>
                        <th>Avg. Lead Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="suppliersTableBody"></tbody>
            </table>
        </div>

        <div id="paginationControls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
            <button class="btn-filter" id="prevBtn" onclick="changePage(-1)" disabled><i class="fa-solid fa-arrow-left"></i> Previous</button>
            <span id="pageNumbers" style="font-weight: 600;">Page 1 of 1</span>
            <button class="btn-filter" id="nextBtn" onclick="changePage(1)" disabled>Next <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </main>

    <div id="suppModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2 id="modalTitle">Add Supplier</h2><i class="fa-solid fa-xmark close-btn" onclick="closeModal()"></i></div>
            <form id="suppForm">
                <input type="hidden" id="suppId">
                <input type="hidden" id="hiddenSpend" value="0">
                <input type="hidden" id="hiddenDate" value="">
                <div class="form-group full-width"><label>Company Name</label><input type="text" id="compName" placeholder="e.g. PC Parts Trading Inc." required></div>
                <div class="form-group"><label>Contact Person</label><input type="text" id="contPerson" placeholder="e.g. Juan Dela Cruz" required></div>
                <div class="form-group"><label>Status</label><select id="compStatus"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
                <div class="form-grid">
                    <div class="form-group"><label>Email</label><input type="email" id="compEmail" placeholder="supplier@email.com" required></div>
                    <div class="form-group"><label>Phone</label><input type="text" id="compPhone" placeholder="e.g. 09171234567" required></div>
                </div>
                <div class="form-group full-width"><label>Products Supplied (Keywords)</label><input type="text" id="compProducts" placeholder='e.g. "Processor, Motherboard"'></div>
                <div class="form-group full-width"><label>Address</label><textarea id="compAddress" rows="2" placeholder="Street, City, Province"></textarea></div>
                <div class="modal-footer"><button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button><button type="submit" class="btn-save">Save Supplier</button></div>
            </form>
        </div>
    </div>

    <div id="restockModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="restockTitle">Smart Restock</h2>
                <i class="fa-solid fa-xmark close-btn" onclick="closeRestockModal()"></i>
            </div>
            <p style="font-size:13px; color:#a3aed0; margin-bottom:10px;">
                Below are <b>Low Stock</b> items matching this supplier's categories.
            </p>
            <div id="restockLoading" style="text-align:center; padding:20px; color:#476eef; display:none;">
                <i class="fa-solid fa-spinner fa-spin"></i> Scanning Inventory...
            </div>
            <div id="restockContainer" class="restock-list">
                </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeRestockModal()">Close</button>
            </div>
        </div>
    </div>

    <div id="confirmOrderModal" class="modal">
        <div class="modal-content" style="max-width: 400px; text-align: center;">
            <div class="icon-box icon-blue" style="margin: 0 auto 15px auto;"><i class="fa-solid fa-cart-plus"></i></div>
            <h2 style="margin-bottom: 10px;">Confirm Order</h2>
            <p style="color: #a3aed0; margin-bottom: 20px;">Are you sure you want to create this Purchase Order?</p>
            
            <div class="conf-summary">
                <div class="conf-row"><span>Item:</span> <strong id="confItemName">...</strong></div>
                <div class="conf-row"><span>Quantity:</span> <strong id="confQty">0</strong></div>
                <div class="conf-row"><span>Unit Cost:</span> <span id="confCost">0.00</span></div>
                <div class="conf-row conf-total"><span>Total:</span> <span id="confTotal">0.00</span></div>
            </div>

            <div class="modal-footer" style="justify-content: center;">
                <button class="btn-cancel" onclick="closeConfirmModal()">Cancel</button>
                <button class="btn-save" onclick="executeOrder()">Yes, Place Order</button>
            </div>
        </div>
    </div>

    <div id="historyModal" class="modal"><div class="modal-content" style="max-width: 700px;"><div class="modal-header"><h2 id="historyTitle">Order History</h2><i class="fa-solid fa-xmark close-btn" onclick="closeHistoryModal()"></i></div><div class="table-container" style="box-shadow:none; padding:0; border:1px solid #eee;"><table style="font-size:13px;"><thead><tr><th>Item</th><th>Qty</th><th>Cost</th><th>Date</th><th>Status</th></tr></thead><tbody id="historyTableBody"></tbody></table></div></div></div>
    <div id="archiveModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-orange"><i class="fa-solid fa-box-archive"></i></div><div class="delete-title">Archive Supplier?</div><div class="delete-text">Hides from main list.</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('archive')">Cancel</button><button class="btn-archive-confirm" onclick="executeArchive()">Yes, Archive</button></div></div></div>
    <div id="restoreModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-green"><i class="fa-solid fa-rotate-left"></i></div><div class="delete-title">Restore Supplier?</div><div class="delete-text">Moves back to active list.</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('restore')">Cancel</button><button class="btn-restore-confirm" onclick="executeRestore()">Yes, Restore</button></div></div></div>
    <div id="deleteModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-red"><i class="fa-solid fa-trash-can"></i></div><div class="delete-title">Permanent Delete?</div><div class="delete-text">WARNING: Cannot be undone.</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('delete')">Cancel</button><button class="btn-danger" onclick="executeDelete()">Yes, Delete it</button></div></div></div>
    <div id="successPopup" class="success-popup"><div class="success-icon"><i class="fa-solid fa-circle-check"></i></div><div class="success-title">Success!</div><div class="success-text" id="successMessage">Action completed successfully.</div></div>
    <div id="toast" class="toast">Copied to clipboard!</div>

    <script>
        // --- 1. CONFIG & STATE ---
        const API_URL = "../api/suppliers_api.php";
        const ORDERS_API = "../api/orders_api.php"; 
        const INVENTORY_API = "../api/api.php"; 

        let suppliers = [];
        let showArchived = false;
        let targetId = null;
        let activeRestockSupplier = ""; 
        let pendingOrder = null; 

        // Pagination
        let currentPage = 1;
        let totalSuppliersCount = 0;
        let suppliersPerPage = 15;

        // --- 2. DOM ELEMENTS ---
        const tableBody = document.getElementById("suppliersTableBody");
        const modal = document.getElementById("suppModal");
        const historyModal = document.getElementById("historyModal");
        const restockModal = document.getElementById("restockModal");
        const confirmModal = document.getElementById("confirmOrderModal");
        const archiveModal = document.getElementById("archiveModal");
        const restoreModal = document.getElementById("restoreModal");
        const deleteModal = document.getElementById("deleteModal");

        // --- 3. FETCH DATA ---
        async function fetchSuppliers() {
            try {
                const search = document.getElementById("searchInput").value;
                const status = document.getElementById("statusFilter").value;
                const archived = showArchived ? 1 : 0;
                const url = `${API_URL}?archived=${archived}&page=${currentPage}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
                
                const res = await fetch(url, { credentials: 'include' });
                const data = await res.json();
                
                if (data.suppliers) {
                    suppliers = data.suppliers;
                    totalSuppliersCount = data.total_suppliers;
                    suppliersPerPage = data.limit;
                } else {
                    suppliers = [];
                }
                renderTable();
                renderPaginationControls();
            } catch (err) { console.error(err); }
        }

        function resetAndFetch() { currentPage = 1; fetchSuppliers(); }

        // --- 4. RENDER TABLE ---
        function renderTable() {
            tableBody.innerHTML = "";
            if (suppliers.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px; color:#a3aed0;">No suppliers found.</td></tr>';
                return;
            }

            suppliers.forEach(s => {
                const statusClass = s.status === 'Active' ? 'active-supp' : 'inactive-supp';
                let productBadges = '-';
                if (s.products_supplied) {
                    productBadges = s.products_supplied.split(',')
                        .map(tag => `<span class="prod-badge" style="margin-right:4px;">${tag.trim()}</span>`)
                        .join('');
                }
                const leadTime = s.avg_lead_time_days ? `${Number(s.avg_lead_time_days).toFixed(1)} Days` : '-';

                const row = `
                    <tr class="data-row">
                        <td style="font-weight:700;">${escapeHtml(s.company_name)}</td>
                        <td>
                            <div style="font-size:14px; font-weight:600;">${escapeHtml(s.contact_person)}</div>
                            <div style="font-size:12px; margin-top:4px; display:flex; flex-direction:column; gap:2px;">
                                <div onclick="copyText('${escapeHtml(s.phone)}')" style="cursor:pointer; color:#476eef; display:flex; align-items:center; gap:5px;" title="Click to Copy Phone">
                                    <i class="fa-solid fa-phone"></i> ${escapeHtml(s.phone)}
                                </div>
                                <div onclick="copyText('${escapeHtml(s.email)}')" style="cursor:pointer; color:#a3aed0; display:flex; align-items:center; gap:5px;" title="Click to Copy Email">
                                    <i class="fa-solid fa-envelope"></i> ${escapeHtml(s.email)}
                                </div>
                            </div>
                        </td>
                        <td>${productBadges}</td>
                        <td><span class="status-badge ${statusClass}">${escapeHtml(s.status)}</span></td>
                        <td style="font-weight:700;">₱${Number(escapeHtml(s.total_spend)).toLocaleString()}</td>
                        <td style="font-size:14px; color:#476eef; font-weight:700;">${leadTime}</td>
                        <td>
                            <div class="action-group">
                                <div class="action-btn" style="background:#fff3cd; color:#ffb547;" onclick="openRestockModal('${escapeHtml(s.company_name)}', '${escapeHtml(s.products_supplied)}')" title="Smart Draft Restock"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                                <div class="action-btn history-btn" onclick="viewHistory('${escapeHtml(s.company_name)}')" title="View Order History"><i class="fa-solid fa-clock-rotate-left"></i></div>
                                <div class="action-btn edit-btn" onclick="editSupp(${escapeHtml(s.id)})"><i class="fa-solid fa-pen"></i></div>
                                ${ s.is_archived == 1 || showArchived
                                    ? `<div class="action-btn restore-btn" onclick="openActionModal('restore', ${escapeHtml(s.id)})"><i class="fa-solid fa-rotate-left"></i></div>`
                                    : `<div class="action-btn archive-btn" onclick="openActionModal('archive', ${escapeHtml(s.id)})"><i class="fa-solid fa-box-archive"></i></div>`
                                }
                                <div class="action-btn delete-btn" onclick="openActionModal('delete', ${escapeHtml(s.id)})"><i class="fa-solid fa-trash"></i></div>
                            </div>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        }

        // --- 5. MODAL & API LOGIC ---
        
        // GENERIC API ACTION (For Archive/Delete/Restore)
        async function apiAction(method, params, msg, body = null) {
            let opts = { method: method, credentials: 'include', headers: { 'X-CSRF-Token': CSRF_TOKEN } };
            if(body) { 
                opts.body = JSON.stringify(body); 
                opts.headers["Content-Type"] = "application/json"; 
            }
            
            try {
                const res = await fetch(API_URL + params, opts);
                const data = await res.json();
                
                if (data.error) {
                    alert("Error: " + data.error);
                } else {
                    closeActionModal('archive'); closeActionModal('restore'); closeActionModal('delete');
                    fetchSuppliers(); 
                    showSuccess(msg);
                }
            } catch(e) {
                console.error(e);
            }
        }

        async function executeArchive() { if(targetId) await apiAction('DELETE', `?id=${targetId}`, 'Archived'); }
        async function executeRestore() { if(targetId) await apiAction('POST', `?action=restore`, 'Restored', {id: targetId}); }
        async function executeDelete() { if(targetId) await apiAction('DELETE', `?id=${targetId}&action=hard`, 'Deleted'); }

        // ADD / EDIT FORM
        document.getElementById("suppForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            const id = document.getElementById("suppId").value;
            const payload = {
                id: id,
                company_name: document.getElementById("compName").value,
                contact_person: document.getElementById("contPerson").value,
                email: document.getElementById("compEmail").value,
                phone: document.getElementById("compPhone").value,
                address: document.getElementById("compAddress").value,
                products_supplied: document.getElementById("compProducts").value,
                status: document.getElementById("compStatus").value,
                total_spend: document.getElementById("hiddenSpend").value,
                last_order_date: document.getElementById("hiddenDate").value
            };
            
            const method = id ? "PUT" : "POST";
            
            await fetch(API_URL, {
                method: method,
                headers: { "Content-Type": "application/json", 'X-CSRF-Token': CSRF_TOKEN },
                credentials: 'include',
                body: JSON.stringify(payload)
            });
            
            closeModal(); 
            fetchSuppliers(); 
            showSuccess(id ? "Supplier Updated!" : "Supplier Added!");
        });

        // --- 6. OTHER FUNCTIONS ---
        function openRestockModal(suppName, tags) {
            if(!tags) { alert("This supplier has no 'Products Supplied' tags to match."); return; }
            activeRestockSupplier = suppName;
            document.getElementById("restockTitle").innerText = "Restock: " + suppName;
            const container = document.getElementById("restockContainer");
            const loading = document.getElementById("restockLoading");
            restockModal.style.display = "block";
            container.innerHTML = "";
            loading.style.display = "block";

            fetch(INVENTORY_API + "?limit=-1", { credentials: 'include' })
                .then(res => res.json())
                .then(inventory => {
                    const keywords = tags.toLowerCase().split(',').map(k => k.trim());
                    const lowStockLimit = 5; 
                    const matchedItems = inventory.filter(item => {
                        if (parseInt(item.stock) > lowStockLimit) return false; 
                        const itemCat = (item.category || "").toLowerCase();
                        const itemName = (item.name || "").toLowerCase();
                        return keywords.some(key => itemCat.includes(key) || itemName.includes(key));
                    });
                    loading.style.display = "none";
                    if (matchedItems.length === 0) {
                        container.innerHTML = `<div style="padding:20px; text-align:center; color:#a3aed0;">No low stock items found matching "${tags}".</div>`;
                        return;
                    }
                    matchedItems.forEach(item => {
                        const suggestedQty = (lowStockLimit * 2) - item.stock; 
                        const cost = item.cost_price ? parseFloat(item.cost_price) : 0;
                        container.innerHTML += `
                            <div class="restock-item">
                                <div class="restock-info">
                                    <h4>${escapeHtml(item.name)}</h4>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <p>Stock: ${item.stock} <span style="color:#a3aed0; font-weight:400;">(Target: ${lowStockLimit})</span></p>
                                        <span style="font-size:11px; background:#f4f7fe; color:#476eef; padding:2px 6px; border-radius:4px;">₱${Number(cost).toLocaleString()} / ea</span>
                                    </div>
                                </div>
                                <div class="restock-action">
                                    <input type="number" class="qty-input" id="qty_${item.id}" value="${suggestedQty}" min="1">
                                    <button class="btn-quick-order" onclick="createDraftOrder(${item.id}, '${escapeHtml(item.name)}', ${cost})">Order <i class="fa-solid fa-paper-plane"></i></button>
                                </div>
                            </div>`;
                    });
                });
        }

        function createDraftOrder(itemId, itemName, cost) {
            const qty = document.getElementById("qty_" + itemId).value;
            if (qty <= 0) { alert("Invalid Quantity"); return; }
            const total = qty * cost;
            pendingOrder = { itemId, itemName, qty, total };
            document.getElementById("confItemName").innerText = itemName;
            document.getElementById("confQty").innerText = qty;
            document.getElementById("confCost").innerText = "₱" + Number(cost).toLocaleString();
            document.getElementById("confTotal").innerText = "₱" + Number(total).toLocaleString();
            confirmModal.style.display = "block";
        }

        async function executeOrder() {
            if (!pendingOrder) return;
            const payload = {
                supplier_name: activeRestockSupplier,
                product_id: pendingOrder.itemId,
                product_name: pendingOrder.itemName,
                quantity: pendingOrder.qty,
                total_cost: pendingOrder.total,
                status: "Pending"
            };
            const btn = document.querySelector("#confirmOrderModal .btn-save");
            btn.innerText = "Ordering...";
            btn.disabled = true;
            try {
                const res = await fetch(ORDERS_API, { 
                    method: "POST", 
                    headers: { "Content-Type": "application/json", 'X-CSRF-Token': CSRF_TOKEN }, 
                    credentials: 'include',
                    body: JSON.stringify(payload)
                });
                const result = await res.json();
                if (result.message) {
                    closeConfirmModal();
                    showSuccess("Order Created!");
                    const btnRow = document.getElementById("qty_" + pendingOrder.itemId);
                    if(btnRow) {
                        const parent = btnRow.parentNode.parentNode;
                        parent.style.opacity = "0.5"; parent.style.pointerEvents = "none";
                        parent.querySelector(".restock-action").innerHTML = `<span style="color:#05cd99; font-weight:700;"><i class="fa-solid fa-check"></i> Ordered</span>`;
                    }
                } else { alert("Error: " + result.error); }
            } catch (err) { alert("Network Error"); } finally { btn.innerText = "Yes, Place Order"; btn.disabled = false; }
        }

        // View History
        async function viewHistory(supplierName) {
            document.getElementById("historyTitle").innerText = "Orders from " + supplierName;
            historyModal.style.display = "block";
            const tbody = document.getElementById("historyTableBody");
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Loading...</td></tr>';
            try {
                const res = await fetch(ORDERS_API + "?limit=-1", { credentials: 'include' }); 
                const data = await res.json();
                let allOrders = Array.isArray(data) ? data : (data.orders || []);
                const supplierOrders = allOrders.filter(o => o.supplier_name === supplierName);
                tbody.innerHTML = "";
                if (supplierOrders.length === 0) { tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:30px; color:#a3aed0;">No orders found.</td></tr>`; return; }
                supplierOrders.forEach(o => {
                    let badge = "#ffb547"; if(o.status === "Delivered") badge = "#05cd99";
                    tbody.innerHTML += `<tr><td>${o.product_name}</td><td>${o.quantity}</td><td>₱${Number(o.total_cost).toLocaleString()}</td><td style="font-size:12px; color:#a3aed0;">${o.order_date.split(' ')[0]}</td><td style="color:${badge}; font-weight:700; font-size:11px;">${o.status}</td></tr>`;
                });
            } catch (err) { console.error(err); }
        }

        // Pagination
        function renderPaginationControls() {
            const totalPages = Math.ceil(totalSuppliersCount / suppliersPerPage);
            const paginationControls = document.getElementById('paginationControls');
            const paginationInfo = document.getElementById('paginationInfo');
            if (totalPages <= 1) {
                if(paginationControls) paginationControls.style.display = 'none';
                if(paginationInfo) paginationInfo.innerText = `Total Suppliers: ${totalSuppliersCount}`;
                return;
            }
            if(paginationControls) paginationControls.style.display = 'flex';
            let endItem = (currentPage * suppliersPerPage);
            if(endItem > totalSuppliersCount) endItem = totalSuppliersCount;
            let startItem = ((currentPage - 1) * suppliersPerPage) + 1;
            if(paginationInfo) paginationInfo.innerText = `Displaying ${startItem} - ${endItem} of ${totalSuppliersCount} suppliers`;
            document.getElementById('prevBtn').disabled = currentPage <= 1;
            document.getElementById('nextBtn').disabled = currentPage >= totalPages;
            document.getElementById('pageNumbers').innerText = `Page ${currentPage} of ${totalPages}`;
        }
        function changePage(direction) {
            const totalPages = Math.ceil(totalSuppliersCount / suppliersPerPage);
            const newPage = currentPage + direction;
            if (newPage >= 1 && newPage <= totalPages) { currentPage = newPage; fetchSuppliers(); }
        }

        // Utils
        function openModal() { modal.style.display = "block"; document.getElementById("suppForm").reset(); document.getElementById("suppId").value = ""; document.getElementById("modalTitle").innerText = "Add New Supplier"; }
        function closeModal() { modal.style.display = "none"; }
        function openActionModal(type, id) { targetId = id; if (type === 'archive') archiveModal.style.display = "block"; if (type === 'restore') restoreModal.style.display = "block"; if (type === 'delete') deleteModal.style.display = "block"; }
        function closeActionModal(type) { if (type === 'archive') archiveModal.style.display = "none"; if (type === 'restore') restoreModal.style.display = "none"; if (type === 'delete') deleteModal.style.display = "none"; targetId = null; }
        function editSupp(id) { const s = suppliers.find(x => x.id == id); if(s) { openModal(); document.getElementById("suppId").value = s.id; document.getElementById("compName").value = s.company_name; document.getElementById("contPerson").value = s.contact_person; document.getElementById("compEmail").value = s.email; document.getElementById("compPhone").value = s.phone; document.getElementById("compProducts").value = s.products_supplied; document.getElementById("compAddress").value = s.address; document.getElementById("compStatus").value = s.status; document.getElementById("hiddenSpend").value = s.total_spend; document.getElementById("hiddenDate").value = s.last_order_date; document.getElementById("modalTitle").innerText = "Edit Supplier"; } }
        function closeHistoryModal() { historyModal.style.display = "none"; }
        function toggleArchiveView() { showArchived = !showArchived; currentPage = 1; const btn = document.getElementById("toggleArchiveSuppliers"); const title = document.querySelector("header h2"); const addBtn = document.querySelector(".btn-add"); if (showArchived) { btn.style.background = "#e0e7ff"; btn.style.color = "#476eef"; btn.innerHTML = '<i class="fa-solid fa-arrow-left"></i> Back to Active'; title.innerText = "Archived Suppliers"; addBtn.style.display = "none"; } else { btn.style.background = "white"; btn.style.color = "#2b3674"; btn.innerHTML = '<i class="fa-solid fa-box-archive"></i> Archives'; title.innerText = "Supplier Database"; if(sessionStorage.getItem("userRole") !== 'sales_manager') addBtn.style.display = "flex"; } fetchSuppliers(); }
        function showSuccess(msg) { document.getElementById("successMessage").innerText=msg; document.getElementById("successPopup").style.display="flex"; setTimeout(()=>document.getElementById("successPopup").style.display="none",2000); }
        function showToast(msg) { const t=document.getElementById("toast"); t.innerText=msg; t.className="toast show"; setTimeout(()=>t.className="toast",3000); }
        function copyText(text) { navigator.clipboard.writeText(text).then(() => { showToast("Copied: " + text); }); }
        function closeRestockModal() { restockModal.style.display = "none"; }
        function closeConfirmModal() { confirmModal.style.display = "none"; pendingOrder = null; }
        function escapeHtml(text) { if(text === null || text === undefined) return ""; return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;"); }

        window.onclick = function(e) { 
            if(e.target == modal) closeModal(); 
            if(e.target == historyModal) closeHistoryModal();
            if(e.target == archiveModal) closeActionModal('archive');
            if(e.target == restoreModal) closeActionModal('restore');
            if(e.target == deleteModal) closeActionModal('delete');
            if(e.target == restockModal) closeRestockModal();
            if(e.target == confirmModal) closeConfirmModal();
        }

        fetchSuppliers();
    </script>
</body>
</html>