/**
 * main.js - Centralized Logic for PC Project
 * Handles Authentication, Permissions, and Global UI updates.
 */

// 1. GLOBAL VARIABLES
const savedName = sessionStorage.getItem("userName");
const userRole = sessionStorage.getItem("userRole");
const savedAvatar = sessionStorage.getItem("userAvatar"); // NEW: Get saved avatar

// 2. ON PAGE LOAD
document.addEventListener("DOMContentLoaded", function() {
    
    // A. Apply Role-Based Permissions
    applyPermissions();

    // B. Restore User Name in Header
    const headerNameEl = document.getElementById("headerUserName");
    if (headerNameEl && savedName) {
        headerNameEl.innerText = savedName;
    }

    // C. Restore User Avatar (NEW FUNCTION)
    loadUserAvatar();

    // D. Restore Dark Mode
    if (sessionStorage.getItem("darkMode") === "true") {
        document.body.classList.add("dark-mode");
        const toggle = document.getElementById("darkModeToggle");
        if(toggle) toggle.checked = true;
    }
});

// 3. COMMON FUNCTIONS

// --- NEW: Load Avatar from Session ---
function loadUserAvatar() {
    const avatarPath = sessionStorage.getItem("userAvatar");
    const headerImg = document.getElementById('headerAvatar');
    const headerIcon = document.getElementById('headerIcon');

    // Only run if we have an image element in the HTML
    if (headerImg && avatarPath) {
        // Assume we are in 'pages/', so go up one level
        headerImg.src = "../assets/uploads/" + avatarPath;
        headerImg.style.display = "block"; // Show the image
        
        if(headerIcon) headerIcon.style.display = "none"; // Hide the default icon
    }
}

async function logout() {
    try {
        await fetch('../logout.php', { 
            method: 'POST',
            credentials: 'include' 
        });
    } catch (error) {
        console.error("Logout Log Failed", error);
    }
    sessionStorage.clear();
    window.location.href = "../index.php";
}

function escapeHtml(text) {
    if (text === null || text === undefined) return "";
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function applyPermissions() {
    // 1. Sidebar Links
    const links = document.querySelectorAll('.nav-item');
    links.forEach(link => {
        const text = link.innerText.trim();
        
        if (userRole !== 'admin' && text.includes('Audit Logs')) {
            link.style.display = 'none';
        }
        if (userRole === 'sales_manager') {
            if (text.includes('Suppliers') || text.includes('Orders')) {
                link.style.display = 'none';
            }
        }
    });

    // 2. Action Buttons (Delete is Admin only)
    if (userRole !== 'admin') {
        document.querySelectorAll('.delete-btn').forEach(btn => btn.style.display = 'none');
    }

    // 3. Sales Manager Restrictions
    if (userRole === 'sales_manager') {
        const path = window.location.href; 

        // Block Add/Edit on Inventory & Settings
        if (path.includes('inventory.php') || path.includes('settings.php')) {
            const addBtn = document.querySelector('.btn-add');
            if (addBtn) addBtn.style.display = 'none';
            document.querySelectorAll('.edit-btn').forEach(btn => btn.style.display = 'none');
            document.querySelectorAll('.archive-btn').forEach(btn => btn.style.display = 'none');
            const archiveToggle = document.getElementById('archiveToggleBtn');
            if (archiveToggle) archiveToggle.style.display = 'none';
        } 
        // Allow Add/Edit on Clients & Builds
        else if (path.includes('clients.php') || path.includes('custom_builds.php')) {
            const addBtn = document.querySelector('.btn-add');
            if (addBtn) addBtn.style.display = 'flex';
            document.querySelectorAll('.edit-btn').forEach(btn => btn.style.display = 'flex');
            document.querySelectorAll('.archive-btn').forEach(btn => btn.style.display = 'none');
        }
    }

    // 4. Settings Page Tabs
    if (document.getElementById('teamTab') && userRole !== 'admin') {
        document.getElementById("teamTab").style.display = 'none';
        document.getElementById("companyTab").style.display = 'none';
        document.getElementById("dataTab").style.display = 'none';
    }
}