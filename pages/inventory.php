<?php 
session_start();
define('ACCESS_ALLOWED', true); 

// --- GENERATE CSRF TOKEN IF MISSING ---
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
    <title>PC Project - Inventory</title>
    <link rel="icon" type="image/png" href="../assets/img/logopc.png">
    <link href="../fontawesome/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/JsBarcode.all.min.js"></script>
    <script src="../assets/js/main.js?v=2"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <script>
        // PASS PHP TOKEN TO JAVASCRIPT
        const CSRF_TOKEN = "<?php echo $csrf_token; ?>";
    </script>
    <style>
        /* --- STYLES FOR PRODUCT DETAILS & SUPPLIER CARD --- */
        .details-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 25px; }
        .details-img-box { 
            background: #f4f7fe; border-radius: 12px; display: flex; 
            align-items: center; justify-content: center; height: 100%; min-height: 250px; 
            border: 1px solid #e0e0e0; overflow: hidden;
        }
        .details-img-box img { width: 100%; height: 100%; object-fit: contain; }
        
        .info-group { margin-bottom: 15px; }
        .info-label { font-size: 12px; color: #a3aed0; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 15px; color: #2b3674; font-weight: 700; margin-top: 4px; }
        .info-value.price { color: #476eef; font-size: 20px; }
        
        /* Supplier Box Styles */
        .supplier-box { 
            background: #f8f9fc; border: 1px dashed #476eef; border-radius: 10px; 
            padding: 15px; margin-top: 20px; 
        }
        .supplier-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        
        /* Buttons Container */
        .supplier-actions { display: flex; gap: 8px; }

        /* Order Button */
        .btn-contact { 
            background: #476eef; color: white; border: none; padding: 8px 15px; 
            border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; 
            text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
        }
        .btn-contact:hover { background: #3b5bdb; }

        /* Copy Button (New) */
        .btn-copy-email {
            background: #e0e7ff; color: #476eef; border: none; padding: 8px 12px;
            border-radius: 6px; font-size: 12px; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-copy-email:hover { background: #d0d7f7; }

        .clickable-name { color: #2b3674; cursor: pointer; text-decoration: underline; text-decoration-color: transparent; transition: 0.2s; }
        .clickable-name:hover { color: #476eef; text-decoration-color: #476eef; }
        
        /* Bulk Checkbox Style */
        .bulk-option {
            background: #fff8e1; border: 1px solid #ffecb3; border-radius: 8px; padding: 10px;
            margin-bottom: 15px; display: flex; align-items: center; gap: 10px;
        }
        .bulk-option input { width: 18px; height: 18px; cursor: pointer; }
        .bulk-option label { margin: 0; font-size: 13px; font-weight: 600; color: #b45309; cursor: pointer; }

        /* Error Popup Specifics (In case not in main CSS) */
        .error-popup { 
            display: none; position: fixed; z-index: 3000; left: 50%; top: 50%; transform: translate(-50%, -50%);
            background: white; padding: 30px 50px; border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.15); text-align: center; animation: popIn 0.4s;
            flex-direction: column; align-items: center; gap: 15px; min-width: 300px;
            border: 2px solid #ee5d50;
        }
        .error-icon { font-size: 50px; margin-bottom: 10px; color: #ee5d50; }
        .error-title { font-size: 18px; font-weight: 700; color: #2b3674; }
        .error-text { font-size: 14px; color: #a3aed0; }
    </style>
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div><h2>Inventory Management</h2></div>
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
            <div class="search-wrapper"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="searchInput" placeholder="Search products..." onkeyup="resetAndFetch()"></div>
            <div class="action-buttons">
                <select class="btn-filter" id="categoryFilter" onchange="resetAndFetch()">
                    <option value="All">All Categories</option>
                    <option value="Processor">Processor</option>
                    <option value="Motherboard">Motherboard</option>
                    <option value="Graphics Card">Graphics Card</option>
                    <option value="Memory">Memory</option>
                    <option value="Storage">Storage</option>
                    <option value="Power Supply">Power Supply</option>
                    <option value="Case">Case</option>
                    <option value="Cooling System">Cooling System</option>
                    <option value="Peripherals">Peripherals</option> 
                </select>
                <button class="btn-filter" id="archiveToggleBtn" onclick="toggleArchives()" style="margin-right: 10px;"><i class="fa-solid fa-box-archive"></i> Archives</button>
                <button class="btn-add" onclick="openModal()"><i class="fa-solid fa-plus"></i> Add Item</button>
            </div>
        </div>

        <div id="paginationInfo" style="padding: 10px 0; font-size: 14px; color: #707eae;"></div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Batch / Lot No.</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Supplier</th> 
                        <th>Cost</th>   <th>Price</th>
                        <th>Profit</th> <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryTableBody"></tbody>
            </table>
        </div>

        <div id="paginationControls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
            <button class="btn-filter" id="prevBtn" onclick="changePage(-1)" disabled><i class="fa-solid fa-arrow-left"></i> Previous</button>
            <span id="pageNumbers" style="font-weight: 600;">Page 1 of 1</span>
            <button class="btn-filter" id="nextBtn" onclick="changePage(1)" disabled>Next <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </main>

    <div id="detailsModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h2 id="detailTitle">Product Details</h2>
                <i class="fa-solid fa-xmark close-btn" onclick="closeDetailsModal()"></i>
            </div>
            
            <div class="details-grid">
                <div class="details-img-box">
                    <img id="detailImg" src="" onerror="this.src='../assets/img/logopc.png'">
                </div>

                <div class="details-info">
                    <div class="info-group">
                        <div class="info-label">Product Name</div>
                        <div class="info-value" id="detailName" style="font-size:18px;">-</div>
                    </div>
                    
                    <div style="display:flex; gap:20px;">
                        <div class="info-group">
                            <div class="info-label">Category</div>
                            <div class="info-value" id="detailCat">-</div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Batch / Lot No.</div>
                            <div class="info-value" id="detailSku" style="font-family:monospace;">-</div>
                        </div>
                    </div>

                    <div style="display:flex; gap:20px;">
                        <div class="info-group">
                            <div class="info-label">Selling Price</div>
                            <div class="info-value price" id="detailPrice">-</div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Current Stock</div>
                            <div class="info-value" id="detailStock">-</div>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Specifications</div>
                        <div class="info-value" id="detailSpecs" style="font-weight:400; font-size:13px; line-height:1.4;">-</div>
                    </div>

                    <div class="supplier-box">
                        <div class="supplier-header">
                            <div class="info-label" style="color:#476eef;">Supplier Information</div>
                            
                            <div class="supplier-actions">
                                <button id="btnCopyEmail" class="btn-copy-email" title="Copy Email Address"><i class="fa-regular fa-copy"></i></button>
                                <a href="#" id="btnEmailSupplier" class="btn-contact"><i class="fa-solid fa-envelope"></i> Email Order</a>
                            </div>
                        </div>
                        
                        <div class="info-value" id="detailSupplierName">No Supplier Linked</div>
                        <div style="font-size:12px; color:#707eae; margin-top:5px;">
                            <span id="detailSupplierContact"></span> <br>
                            <span id="detailSupplierPhone"></span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div id="itemModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2 id="modalTitle">Add New Product</h2><i class="fa-solid fa-xmark close-btn" onclick="closeModal()"></i></div>
            <form id="inventoryForm">
                <input type="hidden" id="itemId"> 
                <div class="form-group full-width" style="text-align:center; margin-bottom:20px;">
                    <label for="itemImage" style="cursor:pointer; display:block;">
                        <div style="width:120px; height:120px; background:#f4f7fe; border-radius:16px; border:2px dashed #a3aed0; display:flex; align-items:center; justify-content:center; margin:0 auto; overflow:hidden; position:relative;">
                            <img id="previewImage" src="" style="width:100%; height:100%; object-fit:cover; display:none;">
                            <div id="uploadText" style="text-align:center;"><i class="fa-solid fa-cloud-arrow-up" style="font-size:24px; color:#a3aed0; margin-bottom:5px;"></i><br><span style="color:#a3aed0; font-size:12px; font-weight:600;">Upload Image</span></div>
                        </div>
                    </label>
                    <input type="file" id="itemImage" accept="image/*" style="display:none;" onchange="previewFile()">
                </div>
                
                <div class="bulk-option">
                    <input type="checkbox" id="isBulkItem">
                    <label for="isBulkItem" title="Check this if the item doesn't have unique serial numbers per unit.">Bulk Item (Quantity Only, No Unique Serials)</label>
                </div>

                <div class="form-group full-width">
                    <label>Batch / Lot Number <span style="color:red">*</span></label>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="text" id="itemSku" placeholder="Scan Batch/Lot No..." style="font-family:monospace; letter-spacing:1px; font-weight:700; color:#476eef;">
                        <button type="button" onclick="generateSku()" style="padding:12px; background:#e0e7ff; border:none; border-radius:10px; cursor:pointer; color:#476eef;"><i class="fa-solid fa-wand-magic-sparkles"></i></button>
                    </div>
                </div>
                <div class="form-group full-width">
                    <label>Supplier <span style="color:red">*</span></label>
                    <select id="itemSupplier" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:8px;">
                        <option value="">Loading Suppliers...</option>
                    </select>
                </div>
                <div class="form-group full-width"><label>Product Name <span style="color:red">*</span></label><input type="text" id="itemName" required></div>
                <div class="form-group"><label>Category <span style="color:red">*</span></label>
                    <select id="itemCategory">
                        <option value="">-- Select --</option>
                        <option value="Processor">Processor</option><option value="Motherboard">Motherboard</option><option value="Graphics Card">Graphics Card</option><option value="Memory">Memory</option><option value="Storage">Storage</option><option value="Power Supply">Power Supply</option><option value="Case">Case</option><option value="Cooling System">Cooling System</option><option value="Peripherals">Peripherals</option> 
                    </select>
                </div>
                <div class="form-group full-width"><label>Specs</label><textarea id="itemSpecs" rows="2" placeholder="e.g. 3.5GHz, 8GB RAM..."></textarea></div>
                <div class="form-grid">
                    <div class="form-group"><label>Cost (₱) <span style="color:red">*</span></label><input type="number" id="itemCost" step="0.01" min="0" required></div>
                    <div class="form-group"><label>Price (₱) <span style="color:red">*</span></label><input type="number" id="itemPrice" step="0.01" required></div>
                </div>
                <div class="form-group full-width"><label>Stock Quantity <span style="color:red">*</span></label><input type="number" id="itemStock" required></div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="button" class="btn-save" onclick="preSubmitCheck()">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <div id="serialModal" class="modal" style="z-index: 2000;">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header"><h2>Scan Serial Numbers</h2><i class="fa-solid fa-xmark close-btn" onclick="closeSerialModal()"></i></div>
            <p style="font-size:13px; color:#a3aed0; margin-bottom:15px;">Scan unique S/N for each unit.</p>
            <form id="serialForm">
                <div id="serialInputsContainer" style="display: grid; gap: 10px; max-height: 300px; overflow-y: auto;"></div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeSerialModal()">Back</button>
                    <button type="submit" class="btn-save">Save All</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewSerialsModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header"><h2 id="viewSerialTitle">Serial Numbers</h2><i class="fa-solid fa-xmark close-btn" onclick="closeViewSerials()"></i></div>
            <div id="serialListContainer" style="max-height: 300px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px;"></div>
            <div class="modal-footer"><button class="btn-cancel" onclick="closeViewSerials()">Close</button></div>
        </div>
    </div>

    <div id="archiveModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-orange"><i class="fa-solid fa-box-archive"></i></div><div class="delete-title">Archive Item?</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('archive')">Cancel</button><button class="btn-archive-confirm" onclick="executeArchive()">Yes, Archive</button></div></div></div>
    <div id="restoreModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-green"><i class="fa-solid fa-rotate-left"></i></div><div class="delete-title">Restore Item?</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('restore')">Cancel</button><button class="btn-restore-confirm" onclick="executeRestore()">Yes, Restore</button></div></div></div>
    <div id="deleteModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-red"><i class="fa-solid fa-trash-can"></i></div><div class="delete-title">Permanent Delete?</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('delete')">Cancel</button><button class="btn-danger" onclick="executeDelete()">Yes, Delete</button></div></div></div>
    
    <div id="errorPopup" class="error-popup">
        <div class="error-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
        <div class="error-title">Attention</div>
        <div class="error-text" id="errorMessage">Error details here.</div>
        <button class="btn-cancel" style="margin-top:10px; width:100px;" onclick="closeErrorPopup()">OK</button>
    </div>

    <div id="successPopup" class="success-popup"><div class="success-icon"><i class="fa-solid fa-circle-check"></i></div><div class="success-title">Success!</div><div class="success-text" id="successMessage">Action completed.</div></div>
    
    <div id="toast" class="toast" style="visibility:hidden; min-width:250px; background-color:#333; color:#fff; text-align:center; border-radius:8px; padding:16px; position:fixed; z-index:9999; bottom:30px; left:50%; transform:translateX(-50%); font-size:14px;"></div>

    <script>
        const API_URL = "../api/api.php";
        const SUPPLIER_API = "../api/suppliers_api.php";
        const IMG_PATH = "../assets/uploads/";

        let inventory = [];
        let isViewingArchived = false;
        let targetId = null;
        let lowStockLimit = 5;
        let currentPage = 1;
        let totalItemsCount = 0;
        let itemsPerPage = 15;
        
        let originalStock = 0;

        // DOM
        const tableBody = document.getElementById("inventoryTableBody");
        const modal = document.getElementById("itemModal");
        const serialModal = document.getElementById("serialModal");
        const viewSerialsModal = document.getElementById("viewSerialsModal");
        const detailsModal = document.getElementById("detailsModal");
        
        // MODALS
        const archiveModal = document.getElementById("archiveModal");
        const restoreModal = document.getElementById("restoreModal");
        const deleteModal = document.getElementById("deleteModal");

        // --- 1. DATA FETCHING ---
        async function fetchSettings() {
            try {
                const res = await fetch('../api/settings_api.php?action=get_settings', { credentials: 'include' });
                const data = await res.json();
                if(data.low_stock_threshold) lowStockLimit = parseInt(data.low_stock_threshold);
            } catch(e) {}
        }

        async function fetchSuppliers() {
            try {
                const res = await fetch(SUPPLIER_API + "?limit=-1", { credentials: 'include' });
                const data = await res.json();
                const suppliers = Array.isArray(data) ? data : (data.suppliers || []);
                const select = document.getElementById("itemSupplier");
                select.innerHTML = '<option value="">-- Select Supplier --</option>';
                suppliers.forEach(s => { if(s.status === 'Active') select.innerHTML += `<option value="${s.id}">${s.company_name}</option>`; });
            } catch(e) {}
        }

        async function fetchInventory() {
            const search = document.getElementById("searchInput").value;
            const cat = document.getElementById("categoryFilter").value;
            const archived = isViewingArchived ? 1 : 0;
            const url = `${API_URL}?archived=${archived}&page=${currentPage}&search=${encodeURIComponent(search)}&category=${encodeURIComponent(cat)}`;

            try {
                const response = await fetch(url, { credentials: 'include' });
                const data = await response.json();
                inventory = data.inventory;
                totalItemsCount = data.total_items;
                itemsPerPage = data.limit;
                renderTable();
                renderPaginationControls();
            } catch (e) { console.error(e); }
        }
        function resetAndFetch() { currentPage = 1; fetchInventory(); }

        // --- 2. RENDER TABLE ---
        function renderTable() {
            tableBody.innerHTML = "";
            if (inventory.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="12" style="text-align:center; padding:20px; color:#a3aed0;">No items found.</td></tr>'; 
                return;
            }

            inventory.forEach(item => {
                const cost = Number(item.cost_price || 0);
                const profit = Number(item.price) - cost;
                const profitColor = profit >= 0 ? '#05cd99' : '#ee5d50';
                
                let statusBadge = '';
                if (item.stock == 0) statusBadge = '<span class="stock-badge out-stock">Out</span>';
                else if (item.stock <= lowStockLimit) statusBadge = '<span class="stock-badge low-stock">Low</span>';
                else statusBadge = '<span class="stock-badge in-stock">Good</span>';

                const imgUrl = item.image ? IMG_PATH + item.image : IMG_PATH + 'default.png';
                const role = sessionStorage.getItem("userRole"); 
                
                let btns = `<div class="action-btn print-btn" onclick="printTag(${item.id})" title="Print"><i class="fa-solid fa-print"></i></div>`;
                if(role !== 'sales_manager') {
                    if (!isViewingArchived) {
                        btns += `<div class="action-btn edit-btn" onclick="editItem(${item.id})" title="Edit"><i class="fa-solid fa-pen"></i></div>`;
                        btns += `<div class="action-btn archive-btn" onclick="openActionModal('archive', ${item.id})" title="Archive"><i class="fa-solid fa-box-archive"></i></div>`;
                    } else {
                        btns += `<div class="action-btn edit-btn" onclick="openActionModal('restore', ${item.id})" title="Restore"><i class="fa-solid fa-rotate-left"></i></div>`;
                    }
                }
                if (role === 'admin') btns += `<div class="action-btn delete-btn" onclick="openActionModal('delete', ${item.id})" title="Delete"><i class="fa-solid fa-trash"></i></div>`;

                const suppName = item.supplier_name || '-';

                const stockDisplay = `
                    <div style="display:flex; align-items:center; gap:5px;">
                        <span>${item.stock}</span>
                        <span class="badge-serial" style="background:#e0e7ff; color:#476eef; padding:2px 6px; border-radius:4px; font-size:10px; cursor:pointer;" onclick="viewSerials(${item.id}, '${escapeHtml(item.name)}')" title="View Serials"><i class="fa-solid fa-barcode"></i></span>
                    </div>`;

                const row = `
                    <tr class="data-row">
                        <td><div style="width:40px; height:40px; border-radius:8px; overflow:hidden; border:1px solid #eee;"><img src="${imgUrl}" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='../img/logopc.png'"></div></td>
                        <td style="font-size:12px; color:#a3aed0;">${item.sku || '-'}</td>
                        <td class="clickable-name" style="font-weight:700;" onclick="viewDetails(${item.id})">${item.name}</td>
                        <td>${item.category}</td>
                        <td style="font-size:13px; color:#476eef;">${suppName}</td> 
                        
                        <td>₱${cost.toLocaleString()}</td>
                        <td>₱${Number(item.price).toLocaleString()}</td>
                        <td style="color:${profitColor}; font-weight:700;">₱${profit.toLocaleString()}</td>
                        <td>${stockDisplay}</td>
                        <td>${statusBadge}</td>
                        <td><div class="action-group">${btns}</div></td>
                    </tr>`;
                tableBody.innerHTML += row;
            });
        }

        // --- 3. VIEW DETAILS LOGIC ---
        function viewDetails(id) {
            const item = inventory.find(i => i.id == id);
            if (!item) return;

            document.getElementById("detailName").innerText = item.name;
            document.getElementById("detailCat").innerText = item.category;
            document.getElementById("detailSku").innerText = item.sku || "N/A";
            document.getElementById("detailPrice").innerText = "₱" + Number(item.price).toLocaleString();
            document.getElementById("detailStock").innerText = item.stock;
            document.getElementById("detailSpecs").innerText = item.specs || "No specifications listed.";
            
            const imgUrl = item.image ? IMG_PATH + item.image : IMG_PATH + "default.png";
            document.getElementById("detailImg").src = imgUrl;

            const suppName = document.getElementById("detailSupplierName");
            const btn = document.getElementById("btnEmailSupplier");
            const btnCopy = document.getElementById("btnCopyEmail");
            const contact = document.getElementById("detailSupplierContact");
            const phone = document.getElementById("detailSupplierPhone");

            if (item.supplier_id && item.supplier_name) {
                suppName.innerText = item.supplier_name;
                contact.innerText = "Contact: " + (item.supplier_contact || "N/A");
                phone.innerText = "Phone: " + (item.supplier_phone || "N/A");
                
                if (item.supplier_email) {
                    btn.style.display = "inline-flex";
                    btnCopy.style.display = "inline-flex";
                    
                    const subject = encodeURIComponent(`Restock Order: ${item.name}`);
                    const body = encodeURIComponent(`Hi ${item.supplier_contact || 'Team'},\n\nI would like to order more units of:\nProduct: ${item.name}\nSKU: ${item.sku}\n\nPlease let me know the availability and current price.\n\nThanks,\nPC Project Admin`);
                    btn.href = `mailto:${item.supplier_email}?subject=${subject}&body=${body}`;
                    
                    btnCopy.onclick = function() {
                        navigator.clipboard.writeText(item.supplier_email).then(() => { showToast("Email Copied: " + item.supplier_email); });
                    };
                } else {
                    btn.style.display = "none"; btnCopy.style.display = "none";
                }
            } else {
                suppName.innerText = "No Supplier Linked";
                contact.innerText = "";
                phone.innerText = "";
                btn.style.display = "none"; btnCopy.style.display = "none";
            }
            detailsModal.style.display = "block";
        }
        function closeDetailsModal() { detailsModal.style.display = "none"; }
        function showToast(message) { const x = document.getElementById("toast"); x.innerText = message; x.style.visibility = "visible"; setTimeout(function(){ x.style.visibility = "hidden"; }, 3000); }

        // --- 4. VIEW SERIALS LOGIC ---
        async function viewSerials(id, name) {
            document.getElementById("viewSerialTitle").innerText = "Serials: " + name;
            const container = document.getElementById("serialListContainer");
            container.innerHTML = '<div style="padding:20px; text-align:center; color:#a3aed0;">Loading...</div>';
            viewSerialsModal.style.display = "block";
            try {
                const res = await fetch(`${API_URL}?action=get_serials&id=${id}`, { credentials: 'include' });
                const serials = await res.json();
                container.innerHTML = "";
                if (serials.length === 0) {
                    container.innerHTML = '<div style="padding:20px; text-align:center; color:#a3aed0;">No serial numbers found.</div>';
                } else {
                    serials.forEach(s => {
                        container.innerHTML += `
                            <div style="padding:10px; border-bottom:1px solid #f4f7fe; display:flex; justify-content:space-between; font-family:monospace; font-size:13px;">
                                <span style="font-weight:700; color:#2b3674;">${s.serial_number}</span>
                                <span style="color:#a3aed0;">${s.date_added.split(' ')[0]}</span>
                            </div>`;
                    });
                }
            } catch (err) { container.innerHTML = '<div style="color:red; padding:20px;">Error loading data.</div>'; }
        }
        function closeViewSerials() { viewSerialsModal.style.display = "none"; }

        // --- 5. MODALS & SUBMISSION ---
        function openModal() {
            modal.style.display = "block";
            document.getElementById("inventoryForm").reset();
            document.getElementById("itemId").value = ""; 
            document.getElementById('previewImage').style.display = "none";
            document.getElementById('uploadText').style.display = "block";
            document.getElementById("modalTitle").innerText = "Add New Product";
            
            document.getElementById("isBulkItem").checked = false;
            
            originalStock = 0; 
            fetchSuppliers();
        }
        function closeModal() { modal.style.display = "none"; }

        // --- UPDATED PRE-SUBMIT CHECK (ALL FIELDS + POPUP) ---
        function preSubmitCheck() {
            // 1. Get Values
            const sku = document.getElementById("itemSku").value.trim();
            const supplier = document.getElementById("itemSupplier").value;
            const name = document.getElementById("itemName").value.trim();
            const category = document.getElementById("itemCategory").value;
            const cost = document.getElementById("itemCost").value;
            const price = document.getElementById("itemPrice").value;
            const stock = document.getElementById("itemStock").value;
            
            const isEdit = document.getElementById("itemId").value !== "";
            const isBulk = document.getElementById("isBulkItem").checked;
            const newStock = parseInt(stock) || 0;

            // 2. VALIDATION (Must have values)
            if(!sku) { showError("Please enter a Batch / Lot Number."); return; }
            if(!supplier) { showError("Please select a Supplier."); return; }
            if(!name) { showError("Please enter a Product Name."); return; }
            if(!category) { showError("Please select a Category."); return; }
            if(!cost || cost < 0) { showError("Please enter a valid Cost."); return; }
            if(!price || price < 0) { showError("Please enter a valid Selling Price."); return; }
            if(!stock || stock < 0) { showError("Please enter Stock Quantity."); return; }

            // 3. DECISION: Bulk vs Serialized
            if (isBulk) {
                // If Bulk is checked, WE SKIP THE SERIAL SCANNING
                // Pass an empty array [] as serials
                submitInventoryForm([]); 
            } else {
                // If NOT Bulk (Serialized Item):
                // Only ask for serials if it's a NEW item or if STOCK INCREASED
                if (!isEdit && newStock > 0) {
                    openSerialModal(newStock);
                } else if (isEdit && newStock > originalStock) {
                    const diff = newStock - originalStock;
                    openSerialModal(diff);
                } else {
                    // No new stock added, just save details
                    submitInventoryForm();
                }
            }
        }

        // --- ERROR POPUP LOGIC ---
        function showError(msg) {
            document.getElementById("errorMessage").innerText = msg;
            document.getElementById("errorPopup").style.display = "flex";
        }
        function closeErrorPopup() {
            document.getElementById("errorPopup").style.display = "none";
        }

        function openSerialModal(qty) {
            serialModal.style.display = "block";
            const container = document.getElementById("serialInputsContainer");
            container.innerHTML = "";
            for(let i=1; i<=qty; i++) {
                container.innerHTML += `
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="font-size:12px; font-weight:700; color:#a3aed0; width:30px;">#${i}</span>
                        <input type="text" class="serial-input" name="serial[]" placeholder="Scan S/N for Unit ${i}" required style="flex:1; padding:10px; border:1px solid #ccc; border-radius:6px; font-family:monospace;">
                    </div>`;
            }
            setTimeout(() => { const first=container.querySelector("input"); if(first) first.focus(); }, 100);
        }
        function closeSerialModal() { serialModal.style.display = "none"; }

        document.getElementById("serialForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const inputs = document.querySelectorAll("input[name='serial[]']");
            const serials = [];
            let duplicates = false;
            inputs.forEach(input => {
                const val = input.value.trim();
                if(serials.includes(val)) duplicates = true;
                serials.push(val);
            });
            if(duplicates) { showError("Duplicate Serial Numbers detected!"); return; }
            submitInventoryForm(serials);
            closeSerialModal();
        });

        async function submitInventoryForm(serials = []) {
            const formData = new FormData();
            const id = document.getElementById("itemId").value;
            if(id) formData.append('id', id);
            formData.append('sku', document.getElementById("itemSku").value);
            formData.append('supplier_id', document.getElementById("itemSupplier").value);
            formData.append('name', document.getElementById("itemName").value);
            formData.append('category', document.getElementById("itemCategory").value);
            formData.append('cost_price', document.getElementById("itemCost").value);
            formData.append('price', document.getElementById("itemPrice").value);
            formData.append('stock', document.getElementById("itemStock").value);
            formData.append('specs', document.getElementById("itemSpecs").value);
            const fileInput = document.getElementById("itemImage");
            if (fileInput.files[0]) formData.append('image', fileInput.files[0]);
            
            if(serials.length > 0) formData.append('serials', JSON.stringify(serials));

            await fetch(API_URL, { method: "POST", headers: { 'X-CSRF-Token': CSRF_TOKEN }, credentials: 'include', body: formData });
            
            closeModal(); fetchInventory(); showSuccess(id ? "Updated!" : "Added!");
        }

        // --- UPDATED EDIT ITEM ---
        function editItem(id) {
            const item = inventory.find(i => i.id == id);
            if (item) {
                document.getElementById("itemId").value = item.id;
                document.getElementById("itemSku").value = item.sku;
                document.getElementById("itemName").value = item.name;
                document.getElementById("itemCategory").value = item.category;
                document.getElementById("itemCost").value = item.cost_price;
                document.getElementById("itemPrice").value = item.price;
                document.getElementById("itemStock").value = item.stock;
                document.getElementById("itemSpecs").value = item.specs || "";
                
                document.getElementById("isBulkItem").checked = false;

                originalStock = parseInt(item.stock);

                const suppSelect = document.getElementById("itemSupplier");
                if(item.supplier_id) suppSelect.value = item.supplier_id;
                const imgUrl = item.image ? IMG_PATH + item.image : "";
                if(imgUrl) {
                    document.getElementById('previewImage').src = imgUrl;
                    document.getElementById('previewImage').style.display = "block";
                    document.getElementById('uploadText').style.display = "none";
                }
                document.getElementById("modalTitle").innerText = "Edit Product";
                modal.style.display = "block";
            }
        }

        function printTag(id) {
            const item = inventory.find(i => i.id == id);
            if(!item) return;
            const barcodeValue = item.sku ? item.sku : "ITEM-" + item.id;
            const w = window.open('', '', 'width=400,height=400');
            w.document.write(`<html><head><title>Print</title><style>body{text-align:center;font-family:sans-serif;}</style></head><body><h2>${item.name}</h2><svg id="b"></svg><p>Price: ₱${item.price}</p><script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script><script>JsBarcode("#b", "${barcodeValue}", {format:"CODE128"});window.print();<\/script></body></html>`);
        }

        function openActionModal(type, id) { targetId=id; if(type==='archive')archiveModal.style.display="block"; if(type==='restore')restoreModal.style.display="block"; if(type==='delete')deleteModal.style.display="block"; }
        function closeActionModal(type) { if(type==='archive')archiveModal.style.display="none"; if(type==='restore')restoreModal.style.display="none"; if(type==='delete')deleteModal.style.display="none"; targetId=null; }
        
        async function executeArchive() { 
            if(targetId) await fetch(`${API_URL}?id=${targetId}&type=soft`, {method:"DELETE",headers:{'X-CSRF-Token': CSRF_TOKEN},credentials:'include'}); 
            closeActionModal('archive'); fetchInventory(); showSuccess("Archived"); 
        }
        async function executeRestore() { 
            if(targetId) await fetch(`${API_URL}?action=restore`, {method:"POST",headers:{"Content-Type":"application/json",'X-CSRF-Token': CSRF_TOKEN},credentials:'include',body:JSON.stringify({id:targetId})}); 
            closeActionModal('restore'); fetchInventory(); showSuccess("Restored"); 
        }
        async function executeDelete() { 
            if(targetId) await fetch(`${API_URL}?id=${targetId}&type=hard`, {method:"DELETE",headers:{'X-CSRF-Token': CSRF_TOKEN},credentials:'include'}); 
            closeActionModal('delete'); fetchInventory(); showSuccess("Deleted"); 
        }
        
        function showSuccess(msg) { const p=document.getElementById("successPopup"); document.getElementById("successMessage").innerText=msg; p.style.display="flex"; setTimeout(()=>{p.style.display="none"},2000); }
        function previewFile() { const p=document.getElementById('previewImage'); const f=document.getElementById('itemImage').files[0]; const t=document.getElementById('uploadText'); if(f){const r=new FileReader();r.onloadend=function(){p.src=r.result;p.style.display="block";t.style.display="none"};r.readAsDataURL(f);} }
        function generateSku() { document.getElementById('itemSku').value = "BATCH-" + Math.floor(10000000 + Math.random() * 90000000); }
        function toggleArchives() { isViewingArchived = !isViewingArchived; document.getElementById("archiveToggleBtn").innerHTML = isViewingArchived ? '<i class="fa-solid fa-arrow-left"></i> Active Items' : '<i class="fa-solid fa-box-archive"></i> Archives'; currentPage = 1; fetchInventory(); }
        function changePage(dir) { currentPage += dir; fetchInventory(); }
        function renderPaginationControls() {
            const totalPages = Math.ceil(totalItemsCount / itemsPerPage);
            const c = document.getElementById('paginationControls');
            if (totalPages <= 1) { c.style.display = 'none'; return; }
            c.style.display = 'flex'; 
            document.getElementById('prevBtn').disabled = currentPage <= 1;
            document.getElementById('nextBtn').disabled = currentPage >= totalPages;
            document.getElementById('pageNumbers').innerText = `Page ${currentPage} of ${totalPages}`;
        }
        function escapeHtml(text) { if(text === null || text === undefined) return ""; return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;"); }

        window.onclick = function(e) { 
            if(e.target == modal) closeModal(); 
            if(e.target == serialModal) closeSerialModal(); 
            if(e.target == viewSerialsModal) closeViewSerials();
            if(e.target == detailsModal) closeDetailsModal();
            if(e.target == archiveModal) closeActionModal('archive');
            if(e.target == restoreModal) closeActionModal('restore');
            if(e.target == deleteModal) closeActionModal('delete');
            if(e.target == document.getElementById("errorPopup")) closeErrorPopup();
        }

        document.addEventListener("DOMContentLoaded", function() {
            fetchSettings();
            fetchInventory();
            fetchSuppliers();
        });
    </script>
</body>
</html>