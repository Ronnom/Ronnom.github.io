<?php 
session_start();
define('ACCESS_ALLOWED', true); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Project - Clients</title>
    <link rel="icon" type="image/png" href="../assets/img/logopc.png">
    <link href="../fontawesome/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/main.js?v=2"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div><h2>Client Database</h2></div>
            <div class="user-badge">
    <?php 
        // Check session for avatar
        $hasAvatar = isset($_SESSION['avatar']) && !empty($_SESSION['avatar']);
        $avatarPath = $hasAvatar ? "../assets/uploads/" . $_SESSION['avatar'] : "../assets/img/logopc.png";
        $displayImg = $hasAvatar ? "block" : "none";
        $displayIcon = $hasAvatar ? "none" : "inline-block";
    ?>
    
    <img id="headerAvatar" src="<?php echo $avatarPath; ?>" 
         style="width:32px; height:32px; border-radius:50%; object-fit:cover; margin-right:8px; display:<?php echo $displayImg; ?>;">   
    
    <i id="headerIcon" class="fa-solid fa-user-circle fa-lg" style="display:<?php echo $displayIcon; ?>;"></i>
    
    <span id="headerUserName"><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?></span>
</div>
        </header>

        <div class="controls-container">
            <div class="search-wrapper"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="searchInput" placeholder="Search by name or company..." onkeyup="fetchClients()"></div>
            <div class="action-buttons">
                <button class="btn-filter" id="toggleArchiveClients" onclick="toggleArchiveView()"><i class="fa-solid fa-box-archive"></i> Archives</button>
                <button class="btn-add" onclick="openModal()"><i class="fa-solid fa-plus"></i> Add New Client</button>
            </div>
        </div>

        <div id="paginationInfo" style="padding: 10px 0; font-size: 14px; color: #707eae;"></div>

        <div class="table-container">
            <table>
                <thead><tr><th>ID</th><th>Client Name</th><th>Contact Info</th><th>Type</th><th>Address</th><th>Actions</th></tr></thead>
                <tbody id="clientsTableBody"></tbody>
            </table>
        </div>

        <div id="paginationControls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
            <button class="btn-filter" id="prevBtn" onclick="changePage(-1)" disabled><i class="fa-solid fa-arrow-left"></i> Previous</button>
            <span id="pageNumbers" style="font-weight: 600;">Page 1 of 1</span>
            <button class="btn-filter" id="nextBtn" onclick="changePage(1)" disabled>Next <i class="fa-solid fa-arrow-right"></i></button>
        </div>
    </main>

    <div id="clientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h2 id="modalTitle">Add New Client</h2><i class="fa-solid fa-xmark close-btn" onclick="closeModal()"></i></div>
            <form id="clientForm">
                <input type="hidden" id="clientId">
                <div class="form-group full-width"><label>Client Name</label><input type="text" id="clientName" placeholder="Full Name" required></div>
                <div class="form-grid">
                    <div class="form-group"><label>Email Address</label><input type="email" id="clientEmail" placeholder="name@email.com" required></div>
                    <div class="form-group"><label>Phone Number</label><input type="text" id="clientPhone" placeholder="0912..." required></div>
                </div>
                <div class="form-group full-width"><label>Company (Optional)</label><input type="text" id="clientCompany" placeholder="Leave blank if individual"></div>
                <div class="form-group full-width"><label>Address</label><textarea id="clientAddress" rows="3" placeholder="Street, City, Province"></textarea></div>
                <div class="form-group full-width"><label>Internal Notes</label><textarea id="clientNotes" rows="2" placeholder="e.g. VIP, Bad Payer..."></textarea></div>
                <div class="modal-footer"><button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button><button type="submit" class="btn-save">Save Client</button></div>
            </form>
        </div>
    </div>

    <div id="historyModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header"><h2 id="histTitle">Client History</h2><i class="fa-solid fa-xmark close-btn" onclick="closeHistoryModal()"></i></div>
            <div style="max-height:400px; overflow-y:auto; margin-bottom: 20px;">
                <table style="width:100%; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; background: white;">
                        <tr style="border-bottom: 2px solid #f4f7fe;">
                            <th style="padding:10px; font-size:12px; color:#a3aed0; text-align:left; width:15%;">Date</th>
                            <th style="padding:10px; font-size:12px; color:#a3aed0; text-align:left; width:20%;">Build Type</th>
                            <th style="padding:10px; font-size:12px; color:#a3aed0; text-align:left; width:40%;">Specs / Parts</th> 
                            <th style="padding:10px; font-size:12px; color:#a3aed0; text-align:left; width:10%;">Status</th>
                            <th style="padding:10px; font-size:12px; color:#a3aed0; text-align:left; width:15%;">Price</th>
                        </tr>
                    </thead>
                    <tbody id="historyBody"></tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn-cancel" onclick="closeHistoryModal()">Close</button></div>
        </div>
    </div>

    <div id="archiveModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-orange"><i class="fa-solid fa-box-archive"></i></div><div class="delete-title">Archive Client?</div><div class="delete-text">Hides from active list.</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('archive')">Cancel</button><button class="btn-archive-confirm" onclick="executeArchive()">Archive</button></div></div></div>
    <div id="restoreModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-green"><i class="fa-solid fa-rotate-left"></i></div><div class="delete-title">Restore Client?</div><div class="delete-text">Moves back to active list.</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('restore')">Cancel</button><button class="btn-restore-confirm" onclick="executeRestore()">Restore</button></div></div></div>
    <div id="deleteModal" class="modal"><div class="modal-content delete-content"><div class="icon-box icon-red"><i class="fa-solid fa-trash-can"></i></div><div class="delete-title">Permanent Delete?</div><div class="delete-text">WARNING: Cannot be undone.</div><div class="modal-footer" style="justify-content:center;"><button class="btn-cancel" onclick="closeActionModal('delete')">Cancel</button><button class="btn-danger" onclick="executeDelete()">Delete</button></div></div></div>

    <div id="successPopup" class="success-popup"><div class="success-icon"><i class="fa-solid fa-circle-check"></i></div><div class="success-title">Success!</div><div class="success-text" id="successMessage">Completed.</div></div>
    <div id="toast" class="toast">Copied to clipboard!</div>

    <script>
        const API_URL = "../api/clients_api.php";
        const HISTORY_API = "../api/builds_api.php"; 
        let clients = [];
        let showArchived = false;
        let targetId = null;

        const tableBody = document.getElementById("clientsTableBody");
        const modal = document.getElementById("clientModal");
        const historyModal = document.getElementById("historyModal");
        
        // --- PAGINATION STATE ---
        let currentPage = 1;
        let totalClientCount = 0;
        let clientsPerPage = 15; // Set by the API but used here for calculation
        // --------------------------

        function showSuccess(message) {
            const popup = document.getElementById("successPopup");
            document.getElementById("successMessage").innerText = message;
            popup.style.display = "flex"; 
            setTimeout(() => { popup.style.display = "none"; }, 2000);
        }

        function showToast(message) {
            const toast = document.getElementById("toast");
            toast.innerText = message;
            toast.className = "toast show"; 
            setTimeout(function(){ toast.className = "toast"; }, 3000);
        }

        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => { showToast("Copied: " + text); });
        }

        async function fetchClients() {
            try {
                // Get search term without pagination state change
                const search = document.getElementById("searchInput").value.toLowerCase();
                
                // Fetch the current page of clients
                const url = showArchived 
                    ? `${API_URL}?action=get_clients&archived=1&page=${currentPage}&search=${search}` 
                    : `${API_URL}?action=get_clients&page=${currentPage}&search=${search}`;
                
                const res = await fetch(url, { credentials: 'include' });
                const data = await res.json();
                
                // The API now returns a structured object
                clients = data.clients;
                totalClientCount = data.total_clients;
                clientsPerPage = data.limit; 
                
                renderTable();
                renderPaginationControls();

            } catch (err) { console.error(err); alert("Error connecting to database"); }
        }

        function renderTable() {
            tableBody.innerHTML = "";
            
            if (clients.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 20px; color:#a3aed0;">No clients found.</td></tr>';
                return;
            }

            clients.forEach(c => {
                const companyBadge = c.company 
                    ? `<span class="type-badge corporate"><i class="fa-solid fa-building"></i> ${escapeHtml(c.company)}</span>` 
                    : `<span class="type-badge personal"><i class="fa-solid fa-user"></i> Individual</span>`;
                
                const noteIcon = c.notes ? `<i class="fa-regular fa-note-sticky" title="${c.notes}" style="color:#f59e0b; margin-left:8px; cursor:help;"></i>` : '';

                const role = sessionStorage.getItem("userRole"); 
                let btns = '';
                btns += `<div class="action-btn hist-btn" onclick="viewHistory(${c.id}, '${escapeHtml(c.name)}')" title="View History"><i class="fa-solid fa-list"></i></div>`;

                if(showArchived) {
                    if(role !== 'sales_manager') btns += `<div class="action-btn restore-btn" onclick="openActionModal('restore', ${c.id})"><i class="fa-solid fa-rotate-left"></i></div>`;
                    if(role === 'admin') btns += `<div class="action-btn delete-btn" onclick="openActionModal('delete', ${c.id})"><i class="fa-solid fa-trash"></i></div>`;
                } else {
                    if(role !== 'sales_manager') {
                        btns += `<div class="action-btn edit-btn" onclick="editClient(${c.id})"><i class="fa-solid fa-pen"></i></div>`;
                        btns += `<div class="action-btn archive-btn" onclick="openActionModal('archive', ${c.id})"><i class="fa-solid fa-box-archive"></i></div>`;
                    }
                    if(role === 'admin') {
                        btns += `<div class="action-btn delete-btn" onclick="openActionModal('delete', ${c.id})"><i class="fa-solid fa-trash"></i></div>`;
                    }
                }

                const row = `
                    <tr class="data-row">
                        <td style="color:#a3aed0; font-size:12px;">#${escapeHtml(c.id)}</td>
                        <td style="font-weight:700;">${escapeHtml(c.name)} ${noteIcon}</td>
                        <td>
                            <div style="font-size:12px; display:flex; flex-direction:column; gap:2px;">
                                <div onclick="copyText('${escapeHtml(c.phone)}')" style="cursor:pointer; color:#476eef; display:flex; align-items:center; gap:5px;" title="Copy Phone">
                                    <i class="fa-solid fa-phone"></i> ${escapeHtml(c.phone)}
                                </div>
                                <div onclick="copyText('${escapeHtml(c.email)}')" style="cursor:pointer; color:#a3aed0; display:flex; align-items:center; gap:5px;" title="Copy Email">
                                    <i class="fa-solid fa-envelope"></i> ${escapeHtml(c.email)}
                                </div>
                            </div>
                        </td>
                        <td>${companyBadge}</td>
                        <td style="font-size:13px;">${escapeHtml(c.address)}</td>
                        <td><div class="action-group">${btns}</div></td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
            // Permissions for buttons are already applied via role check in loop
        }

        async function viewHistory(id, name) {
            document.getElementById("histTitle").innerText = "History: " + name;
            const tbody = document.getElementById("historyBody");
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px; color:#a3aed0;">Loading...</td></tr>';
            historyModal.style.display = "block";

            try {
                const res = await fetch(`${API_URL}?action=get_history&id=${id}`, { credentials: 'include' });
                const history = await res.json();
                
                tbody.innerHTML = "";
                if(!history || history.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px; color:#a3aed0;">No custom builds found for this client.</td></tr>';
                } else {
                    history.forEach(h => {
                        let statusColor = '#a3aed0';
                        if(h.status === 'Completed') statusColor = '#05cd99';
                        if(h.status === 'In Progress') statusColor = '#476eef';
                        const date = h.date_added ? h.date_added.split(" ")[0] : "N/A";
                        const specs = h.parts_list ? h.parts_list : '<span style="color:#ccc; font-style:italic;">Manual Entry</span>';
                        
                        tbody.innerHTML += `
                            <tr style="border-bottom: 1px solid #f4f7fe;">
                                <td style="padding:12px; font-size:13px;">${date}</td>
                                <td style="padding:12px; font-weight:700; font-size:13px;">${escapeHtml(h.build_type)}</td>
                                <td style="padding:12px; font-size:12px; line-height:1.4;">${specs}</td>
                                <td style="padding:12px; font-size:12px;"><span style="color:${statusColor}; font-weight:600;">${escapeHtml(h.status)}</span></td>
                                <td style="padding:12px; color:#05cd99; font-weight:700; font-size:13px;">₱${Number(h.total_price).toLocaleString()}</td>
                            </tr>
                        `;
                    });
                }
            } catch(e) { 
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px; color:#ee5d50;">Error loading history.</td></tr>';
            }
        }

        function closeHistoryModal() { historyModal.style.display = "none"; }
        document.getElementById("searchInput").addEventListener("input", function() {
            // Reset to page 1 when searching
            currentPage = 1;
            fetchClients();
        });

        // --- PAGINATION FUNCTIONS ---
        function renderPaginationControls() {
            const totalPages = Math.ceil(totalClientCount / clientsPerPage);
            const paginationControls = document.getElementById('paginationControls');
            const paginationInfo = document.getElementById('paginationInfo');
            
            if (totalPages <= 1) {
                if(paginationControls) paginationControls.style.display = 'none';
                if(paginationInfo) paginationInfo.innerText = `Total Clients: ${totalClientCount}`;
                return;
            }
            
            if(paginationControls) paginationControls.style.display = 'flex'; 
            
            let infoText = `${currentPage} of ${totalPages} (Total: ${totalClientCount})`;
            if(paginationInfo) paginationInfo.innerText = infoText;

            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const pageNumbersSpan = document.getElementById('pageNumbers');

            if(prevBtn) prevBtn.disabled = currentPage <= 1;
            if(nextBtn) nextBtn.disabled = currentPage >= totalPages;
            if(pageNumbersSpan) pageNumbersSpan.innerText = `Page ${currentPage} of ${totalPages}`;
        }

        function changePage(direction) {
            const totalPages = Math.ceil(totalClientCount / clientsPerPage);
            const newPage = currentPage + direction;

            if (newPage >= 1 && newPage <= totalPages) {
                currentPage = newPage;
                fetchClients();
            }
        }


        // --- CRUD / MODAL LOGIC ---
        function openModal() {
            modal.style.display = "block";
            document.getElementById("clientForm").reset();
            document.getElementById("clientId").value = "";
            document.getElementById("modalTitle").innerText = "Add New Client";
        }
        function closeModal() { modal.style.display = "none"; }

        const archiveModal = document.getElementById("archiveModal");
        const restoreModal = document.getElementById("restoreModal");
        const deleteModal = document.getElementById("deleteModal");

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

        async function executeArchive() { if(targetId) await apiAction('DELETE', `&id=${targetId}`, 'Client Archived'); }
        async function executeRestore() { if(targetId) await apiAction('POST', `&action=restore`, 'Client Restored', {id: targetId}); }
        async function executeDelete() { if(targetId) await apiAction('DELETE', `&action=hard&id=${targetId}`, 'Client Deleted'); }

        // --- UPDATED API ACTION WITH CSRF HEADERS ---
        async function apiAction(method, params, msg, body = null) {
            let opts = { 
                method: method, 
                credentials: 'include',
                headers: {
                    'X-CSRF-Token': sessionStorage.getItem('csrfToken') // SECURE TOKEN
                }
            };
            if(body) { 
                opts.body = JSON.stringify(body); 
                opts.headers["Content-Type"] = "application/json"; 
            }
            await fetch(API_URL + (method === 'DELETE' ? `?${params.substring(1)}` : `?action=restore`), opts);
            closeActionModal('archive'); closeActionModal('restore'); closeActionModal('delete');
            fetchClients();
            showSuccess(msg);
        }

        document.getElementById("clientForm").addEventListener("submit", async function(e) {
            e.preventDefault();
            
            const idVal = document.getElementById("clientId").value;
            
            const payload = {
                id: idVal, 
                name: document.getElementById("clientName").value,
                email: document.getElementById("clientEmail").value,
                phone: document.getElementById("clientPhone").value,
                company: document.getElementById("clientCompany").value,
                address: document.getElementById("clientAddress").value,
                notes: document.getElementById("clientNotes").value 
            };

            const btn = document.querySelector("#clientForm .btn-save");
            const originalText = btn.innerText;
            btn.innerText = "Saving...";
            btn.disabled = true;

            try {
                const res = await fetch(API_URL, { 
                    method: "POST", 
                    headers: { 
                        "Content-Type": "application/json",
                        'X-CSRF-Token': sessionStorage.getItem('csrfToken') 
                    }, 
                    credentials: 'include', 
                    body: JSON.stringify(payload) 
                });
                
                const text = await res.text();
                
                try {
                    const data = JSON.parse(text);

                    if (data.error) {
                        alert("Error: " + data.error);
                    } else {
                        closeModal(); 
                        fetchClients(); 
                        showSuccess(idVal ? "Client Updated!" : "Client Added!");
                    }
                } catch (jsonErr) {
                    console.error("Server Response was not JSON:", text);
                    alert("Server Error. Check Console (F12) for details.");
                }

            } catch (err) {
                console.error(err);
                alert("Request Failed. Is the server running?");
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        });

        function editClient(id) {
            const c = clients.find(x => x.id == id);
            if(c) {
                document.getElementById("clientId").value = c.id;
                document.getElementById("clientName").value = c.name;
                document.getElementById("clientEmail").value = c.email;
                document.getElementById("clientPhone").value = c.phone;
                document.getElementById("clientCompany").value = c.company;
                document.getElementById("clientAddress").value = c.address;
                document.getElementById("clientNotes").value = c.notes; 
                document.getElementById("modalTitle").innerText = "Edit Client";
                modal.style.display = "block";
            } else {
                console.error("Client not found in array", id);
            }
        }

        function toggleArchiveView() {
            showArchived = !showArchived;
            currentPage = 1; // Reset page on view change
            const btn = document.getElementById("toggleArchiveClients");
            const title = document.querySelector("header h2");
            const addBtn = document.querySelector(".btn-add");

            if (showArchived) {
                btn.style.background = "#e0e7ff"; btn.style.color = "#476eef"; btn.innerHTML = '<i class="fa-solid fa-arrow-left"></i> Back to Active';
                title.innerText = "Archived Clients";
                addBtn.style.display = "none";
            } else {
                btn.style.background = "white"; btn.style.color = "#2b3674"; btn.innerHTML = '<i class="fa-solid fa-box-archive"></i> Archives';
                title.innerText = "Client Database";
                if(sessionStorage.getItem("userRole") !== 'sales_manager') addBtn.style.display = "flex";
            }
            fetchClients();
        }

        window.onclick = function(e) { 
            if(e.target == modal) closeModal(); 
            if(e.target == historyModal) closeHistoryModal();
            if(e.target == archiveModal) closeActionModal('archive');
            if(e.target == restoreModal) closeActionModal('restore');
            if(e.target == deleteModal) closeActionModal('delete');
        }

        fetchClients();
    </script>
</body>
</html>