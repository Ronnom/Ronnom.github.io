<?php 
session_start();
define('ACCESS_ALLOWED', true); 
require_once '../config/db_connect.php';

date_default_timezone_set('Asia/Manila');

// 1. CHECK LOGIN
if (!isset($_SESSION['user_id'])) { header("Location: ../index.php"); exit; }

$userId = $_SESSION['user_id'];
$role = $_SESSION['role']; 
$today = date('Y-m-d');

// --- PART A: PERSONAL ATTENDANCE LOGIC ---
$logQ = $conn->query("SELECT * FROM attendance_logs WHERE user_id = $userId AND date = '$today'");
$log = $logQ->fetch_assoc();

$canTimeIn = !$log;
$canBreakOut = $log && $log['time_in'] && !$log['break_out'] && !$log['time_out'];
$canBreakIn = $log && $log['break_out'] && !$log['break_in'] && !$log['time_out'];
$canTimeOut = $log && $log['time_in'] && !$log['time_out'] && (!$log['break_out'] || ($log['break_out'] && $log['break_in']));

$statusMsg = "Please Time In";
if ($log) {
    if ($log['time_out']) $statusMsg = "Shift Ended. Good Job!";
    elseif ($log['break_out'] && !$log['break_in']) $statusMsg = "You are on Break.";
    else $statusMsg = "Working...";
}

// --- PART B: HR MANAGEMENT LOGIC ---
$showHistory = ($role === 'admin' || $role === 'hr' || $role === 'operation_manager');
$logs = null;
$empQ = null;

if ($showHistory) {
    $empQ = $conn->query("SELECT id, full_name FROM users ORDER BY full_name ASC");
    $dateFilter = $_GET['date'] ?? date('Y-m-d');
    $userFilter = $_GET['user_id'] ?? 'All';

    $whereSQL = "WHERE date = '$dateFilter'";
    if($userFilter !== 'All') { $whereSQL .= " AND user_id = $userFilter"; }

    $sql = "SELECT a.*, u.full_name FROM attendance_logs a JOIN users u ON a.user_id = u.id $whereSQL ORDER BY u.full_name ASC";
    $logs = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance</title>
    <link rel="icon" type="image/png" href="../assets/img/logopc.png">
    <link href="../fontawesome/css/all.min.css" rel="stylesheet">
    <script src="../assets/js/main.js?v=2"></script>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* --- CAMERA UI --- */
        .attendance-container { display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 40px; }
        .camera-wrapper {
            width: 250px; height: 250px; border-radius: 50%; overflow: hidden;
            border: 6px solid #476eef; box-shadow: 0 10px 30px rgba(71, 110, 239, 0.3);
            position: relative; background: #000; margin-bottom: 20px;
        }
        video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        #canvas { display: none; }
        .time-display { font-size: 32px; font-weight: 800; color: #2b3674; margin-bottom: 10px; font-family: monospace; }
        .btn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; width: 100%; max-width: 400px; }
        .att-btn { padding: 15px; border-radius: 12px; font-weight: 700; cursor: pointer; border: none; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s; }
        
        .btn-in { background: #e0e7ff; color: #476eef; } .btn-in:hover:not(:disabled) { background: #476eef; color: white; }
        .btn-break { background: #ffedd5; color: #ea580c; } .btn-break:hover:not(:disabled) { background: #ea580c; color: white; }
        .btn-out { background: #fee2e2; color: #ef4444; } .btn-out:hover:not(:disabled) { background: #ef4444; color: white; }
        .att-btn:disabled { opacity: 0.4; cursor: not-allowed; filter: grayscale(100%); }

        /* --- HR UI & CIRCLES --- */
        .hr-section { border-top: 2px dashed #e0e7ff; padding-top: 30px; margin-top: 20px; }
        .section-title { font-size: 20px; font-weight: 800; color: #2b3674; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        .filters { display: flex; gap: 15px; margin-bottom: 20px; background: white; padding: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .form-select, .form-input { padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px; }

        /* NEW CALENDAR STYLE */
        .styled-date {
            padding: 10px 15px; border: 1px solid #e0e7ff; border-radius: 50px; background: #f4f7fe;
            color: #2b3674; font-weight: 700; outline: none; cursor: pointer; transition: 0.2s;
        }
        .styled-date:focus { border-color: #476eef; background: #fff; box-shadow: 0 0 0 3px rgba(71, 110, 239, 0.1); }

        .log-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .employee-header { font-size: 16px; font-weight: 800; color: #2b3674; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        
        .circles-container { display: flex; justify-content: space-around; align-items: center; gap: 10px; }
        .circle-item { display: flex; flex-direction: column; align-items: center; width: 100px; }
        .photo-circle { 
            width: 80px; height: 80px; border-radius: 50%; border: 3px solid #e0e7ff; background: #f4f7fe;
            overflow: hidden; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; cursor: pointer; transition: transform 0.2s;
        }
        .photo-circle:hover { transform: scale(1.1); border-color: #476eef; }
        .photo-circle img { width: 100%; height: 100%; object-fit: cover; }
        .time-label { font-size: 10px; font-weight: 700; color: #a3aed0; text-transform: uppercase; }
        .time-value { font-size: 12px; font-weight: 800; color: #2b3674; }

        /* EDIT BUTTON */
        .btn-edit-icon { 
            background: #f4f7fe; color: #476eef; border: none; width: 30px; height: 30px; 
            border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; margin-left: 10px; 
        }
        .btn-edit-icon:hover { background: #476eef; color: white; }

        /* EDIT MODAL */
        .modal-edit-content {
            background: white; padding: 25px; border-radius: 15px; width: 100%; max-width: 400px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .edit-group { margin-bottom: 15px; text-align: left; }
        .edit-group label { display: block; font-size: 12px; font-weight: 700; color: #a3aed0; margin-bottom: 5px; }
        .edit-group input { width: 100%; padding: 10px; border: 1px solid #e0e0e0; border-radius: 8px; }
    </style>
</head>
<body>

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        
        <div class="attendance-container">
            <h2 style="color: #2b3674;">My Attendance</h2>
            <div class="time-display" id="liveClock">00:00:00 AM</div>
            <p style="color: #476eef; font-weight: 600; margin-bottom: 15px;"><?php echo $statusMsg; ?></p>

            <div class="camera-wrapper">
                <video id="video" autoplay playsinline></video>
            </div>
            <canvas id="canvas" width="640" height="480"></canvas>

            <div class="btn-grid">
                <button class="att-btn btn-in" onclick="captureLog('time_in')" <?php if(!$canTimeIn) echo 'disabled'; ?>><i class="fa-solid fa-right-to-bracket"></i> TIME IN</button>
                <button class="att-btn btn-break" onclick="captureLog('break_out')" <?php if(!$canBreakOut) echo 'disabled'; ?>><i class="fa-solid fa-mug-hot"></i> BREAK OUT</button>
                <button class="att-btn btn-break" onclick="captureLog('break_in')" <?php if(!$canBreakIn) echo 'disabled'; ?>><i class="fa-solid fa-person-walking-arrow-loop-left"></i> BREAK IN</button>
                <button class="att-btn btn-out" onclick="captureLog('time_out')" <?php if(!$canTimeOut) echo 'disabled'; ?>><i class="fa-solid fa-right-from-bracket"></i> TIME OUT</button>
            </div>
        </div>

        <?php if ($showHistory): ?>
        <div class="hr-section">
            <div class="section-title"><i class="fa-solid fa-users-viewfinder"></i> Employee Attendance History</div>

            <form class="filters">
                <input type="date" name="date" class="styled-date" value="<?php echo $dateFilter; ?>">
                
                <select name="user_id" class="form-select">
                    <option value="All">All Employees</option>
                    <?php while($row = $empQ->fetch_assoc()): ?>
                        <option value="<?php echo $row['id']; ?>" <?php if($userFilter == $row['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($row['full_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn-in" style="border:none; padding:0 20px; border-radius:8px; cursor:pointer;">Filter</button>
            </form>

            <?php if($logs && $logs->num_rows > 0): ?>
                <?php while($data = $logs->fetch_assoc()): ?>
                    <div class="log-card">
                        <div class="employee-header">
                            <div><?php echo htmlspecialchars($data['full_name']); ?></div>
                            <div style="display: flex; align-items: center;">
                                <span style="font-size:12px; font-weight:normal; color:#a3aed0;">Total: <?php echo $data['total_hours']; ?> hrs</span>
                                
                                <?php if($role === 'admin' || $role === 'hr'): ?>
                                    <button class="btn-edit-icon" type="button" onclick="openEditModal(
                                        '<?php echo $data['id']; ?>',
                                        '<?php echo $data['time_in']; ?>',
                                        '<?php echo $data['break_out']; ?>',
                                        '<?php echo $data['break_in']; ?>',
                                        '<?php echo $data['time_out']; ?>'
                                    )"><i class="fa-solid fa-pen"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="circles-container">
                            <div class="circle-item">
                                <div class="photo-circle" onclick="viewPhoto('<?php echo $data['time_in_img']; ?>')">
                                    <?php if($data['time_in_img']): ?><img src="../assets/attendance_uploads/<?php echo $data['time_in_img']; ?>"><?php else: ?><i class="fa-solid fa-user"></i><?php endif; ?>
                                </div>
                                <span class="time-label">Time In</span>
                                <span class="time-value"><?php echo $data['time_in'] ? date("h:i A", strtotime($data['time_in'])) : "--:--"; ?></span>
                            </div>
                            <div class="circle-item">
                                <div class="photo-circle" onclick="viewPhoto('<?php echo $data['break_out_img']; ?>')">
                                    <?php if($data['break_out_img']): ?><img src="../assets/attendance_uploads/<?php echo $data['break_out_img']; ?>"><?php else: ?><i class="fa-solid fa-mug-hot"></i><?php endif; ?>
                                </div>
                                <span class="time-label">Break Out</span>
                                <span class="time-value"><?php echo $data['break_out'] ? date("h:i A", strtotime($data['break_out'])) : "--:--"; ?></span>
                            </div>
                            <div class="circle-item">
                                <div class="photo-circle" onclick="viewPhoto('<?php echo $data['time_out_img']; ?>')">
                                    <?php if($data['time_out_img']): ?><img src="../assets/attendance_uploads/<?php echo $data['time_out_img']; ?>"><?php else: ?><i class="fa-solid fa-right-from-bracket"></i><?php endif; ?>
                                </div>
                                <span class="time-label">Time Out</span>
                                <span class="time-value"><?php echo $data['time_out'] ? date("h:i A", strtotime($data['time_out'])) : "--:--"; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px; border: 2px dashed #e0e0e0; border-radius: 12px; margin-top: 10px;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 40px; color: #a3aed0; margin-bottom: 15px;"></i>
                    <h3 style="color: #2b3674; margin-bottom: 5px;">No Records Found</h3>
                    <p style="color: #707eae;">Viewing: <strong><?php echo $dateFilter; ?></strong></p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </main>

    <div id="photoModal" class="modal" onclick="this.style.display='none'">
        <div class="modal-content" style="width:auto; max-width:500px; background:transparent; box-shadow:none; border:none; text-align:center;">
            <img id="modalImg" src="" style="border-radius:12px; border:5px solid white; max-width:100%;">
        </div>
    </div>

    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content modal-edit-content">
            <h3 style="color: #2b3674; margin-bottom: 20px;">Edit Time Record</h3>
            <input type="hidden" id="edit_log_id">
            
            <div class="edit-group">
                <label>Time In</label>
                <input type="datetime-local" id="edit_time_in">
            </div>
            <div class="edit-group">
                <label>Break Out</label>
                <input type="datetime-local" id="edit_break_out">
            </div>
            <div class="edit-group">
                <label>Break In (Return)</label>
                <input type="datetime-local" id="edit_break_in">
            </div>
            <div class="edit-group">
                <label>Time Out</label>
                <input type="datetime-local" id="edit_time_out">
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="att-btn btn-out" style="flex:1;" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                <button class="att-btn btn-in" style="flex:1;" onclick="saveChanges()">Save Changes</button>
            </div>
        </div>
    </div>

    <script>
        // 1. CLOCK & CAMERA LOGIC
        function updateClock() { document.getElementById('liveClock').innerText = new Date().toLocaleTimeString(); }
        setInterval(updateClock, 1000); updateClock();

        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');

        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => { video.srcObject = stream; })
            .catch(err => { alert("Camera Access Denied"); console.error(err); });

        async function captureLog(type) {
            if (!confirm("Confirm " + type.replace('_', ' ').toUpperCase() + "?")) return;
            video.style.opacity = "0"; setTimeout(() => video.style.opacity = "1", 100);
            context.drawImage(video, 0, 0, 640, 480);
            const imageBase64 = canvas.toDataURL('image/jpeg', 0.8);

            try {
                const res = await fetch('../api/attendance_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: type, image: imageBase64 })
                });
                const rawText = await res.text();
                try {
                    const data = JSON.parse(rawText); 
                    if (data.success) { alert(data.message); location.reload(); } 
                    else { alert("System Error: " + data.error); }
                } catch (jsonError) { alert("PHP CRASH: \n" + rawText); }
            } catch (e) { alert("Network Error"); }
        }

        // 2. MODAL LOGIC (PHOTO)
        function viewPhoto(filename) {
            if(!filename) return;
            document.getElementById('modalImg').src = "../assets/attendance_uploads/" + filename;
            document.getElementById('photoModal').style.display = 'flex';
        }

        // 3. EDIT MODAL LOGIC (ADMIN)
        function openEditModal(id, tIn, bOut, bIn, tOut) {
            document.getElementById('edit_log_id').value = id;
            // Format dates for input (replace space with T)
            document.getElementById('edit_time_in').value = tIn ? tIn.replace(' ', 'T') : '';
            document.getElementById('edit_break_out').value = bOut ? bOut.replace(' ', 'T') : '';
            document.getElementById('edit_break_in').value = bIn ? bIn.replace(' ', 'T') : '';
            document.getElementById('edit_time_out').value = tOut ? tOut.replace(' ', 'T') : '';
            document.getElementById('editModal').style.display = 'flex';
        }

        async function saveChanges() {
            const data = {
                action: 'manual_edit',
                log_id: document.getElementById('edit_log_id').value,
                time_in: document.getElementById('edit_time_in').value.replace('T', ' '),
                break_out: document.getElementById('edit_break_out').value.replace('T', ' '),
                break_in: document.getElementById('edit_break_in').value.replace('T', ' '),
                time_out: document.getElementById('edit_time_out').value.replace('T', ' ')
            };

            try {
                const res = await fetch('../api/attendance_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if(result.success) { alert(result.message); location.reload(); }
                else { alert(result.error); }
            } catch (e) { alert("Save failed"); }
        }
    </script>
</body>
</html>