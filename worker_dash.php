<?php 
// 1. Start Session
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 2. Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Worker') {
    header("Location: login.php");
    exit;
}

$w_id = $_SESSION['user_id'];

// 3. Database Connection
$servername = "127.0.0.1"; $username = "root"; $password = ""; $dbname = "flowtrack_mes"; $port = 3306; 
$conn = @new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) { $conn = new mysqli("localhost", "root", "", "flowtrack_mes"); }

// --- HANDLE ACTIONS ---
$msg_script = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // UPDATE STATUS
    if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
        $new_status = $_POST['status'];
        $stmt = $conn->prepare("UPDATE Worker SET Availability = ? WHERE Worker_ID = ?");
        $stmt->bind_param("si", $new_status, $w_id);
        if ($stmt->execute()) {
            $msg_script = "const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true}); Toast.fire({icon: 'success', title: 'Status updated to $new_status'});";
        }
    }

    // UPDATE PROFILE
    if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $em_name = $_POST['em_name'];
        $em_phone = $_POST['em_phone'];
        $pref = $_POST['pref'];
        $new_pass = $_POST['new_pass'];

        $sql = "UPDATE Worker SET Phone=?, Home_Address=?, Emergency_Contact_Name=?, Emergency_Contact=?, Preferences=?";
        $params = [$phone, $address, $em_name, $em_phone, $pref];
        $types = "sssss";

        if (!empty($new_pass)) {
            $sql .= ", Password=?";
            $params[] = $new_pass; 
            $types .= "s";
        }

        $sql .= " WHERE Worker_ID=?";
        $params[] = $w_id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $msg_script = "Swal.fire({icon: 'success', title: 'Saved', text: 'Profile updated successfully.', timer: 1500, showConfirmButton: false});";
        }
    }
}

// 4. Fetch Worker Data
$stmt = $conn->prepare("SELECT * FROM Worker WHERE Worker_ID = ?");
$stmt->bind_param("i", $w_id);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();

if (!$worker) {
    session_destroy();
    header("Location: login.php?error=not_found");
    exit;
}

// 5. FETCH HISTORY
$history_q = "SELECT wp.*, b.Product_Type 
              FROM Worker_Performance wp 
              JOIN Batch b ON wp.Batch_ID = b.Batch_ID 
              WHERE wp.Worker_ID = $w_id 
              ORDER BY wp.Logged_Date DESC, wp.Perf_ID DESC LIMIT 5";
$history_res = $conn->query($history_q);

// Defects Found/Assigned
$defect_q = "SELECT d.*, b.Product_Type FROM Defect_Log d 
             JOIN Batch b ON d.Batch_ID = b.Batch_ID 
             WHERE d.Found_By_Worker_ID = $w_id OR d.Assigned_To_Worker_ID = $w_id 
             ORDER BY d.Defect_ID DESC LIMIT 5";
$defect_res = $conn->query($defect_q);


$worker_json = json_encode($worker, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$status_bg = ($worker['Availability'] == 'Available') ? '#D1FAE5' : (($worker['Availability'] == 'Sick') ? '#FEE2E2' : '#FEF3C7');
$status_txt = ($worker['Availability'] == 'Available') ? '#065F46' : (($worker['Availability'] == 'Sick') ? '#991B1B' : '#92400E');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - FlowTrack</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    
    <style>
        :root { --bg: #F5F5F7; --card: #FFFFFF; --text: #1D1D1F; --blue: #007AFF; --green: #34C759; --red: #FF3B30; }
        body { background: url('https://4kwallpapers.com/images/wallpapers/macos-monterey-stock-light-layers-5k-6016x6016-5898.jpg') no-repeat center center fixed; background-size: cover; font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif; margin: 0; padding: 20px; color: var(--text); }
        .container { max-width: 1000px; margin: 0 auto; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); padding-bottom: 50px; }
        
        /* HEADER & ID CARD STYLES */
        .header { background: rgba(255, 255, 255, 0.85); padding: 15px 25px; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.4); }
        .h-title { font-size: 22px; font-weight: 700; color: #1D1D1F; }
        .h-sub { color: #86868B; font-size: 13px; font-weight: 500; }
        
        .header-actions { display: flex; align-items: center; gap: 20px; }
        
        /* NOTIFICATION BELL */
        .notif-bell { position: relative; cursor: pointer; color: #1D1D1F; font-size: 20px; transition: 0.2s; }
        .notif-bell:hover { color: var(--blue); transform: scale(1.1); }
        .notif-badge { position: absolute; top: -5px; right: -8px; background: var(--red); color: white; font-size: 10px; font-weight: bold; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white; display: none; }

        .status-form select { appearance: none; -webkit-appearance: none; padding: 8px 30px 8px 15px; border-radius: 20px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23000000%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E"); background-repeat: no-repeat; background-position: right 10px center; background-size: 10px; transition: all 0.2s; }
        .status-form select:focus { outline: none; box-shadow: 0 0 0 3px rgba(0,122,255,0.3); }
        .grid { display: grid; grid-template-columns: 350px 1fr; gap: 25px; margin-bottom: 25px; }
        .id-card { background: rgba(255, 255, 255, 0.9); border-radius: 24px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); text-align: center; border: 1px solid rgba(255,255,255,0.6); position: relative; }
        .id-top { background: linear-gradient(135deg, #FF9F0A, #FF375F); padding: 30px 20px; color: white; position: relative;} 
        .glass-btn { position: absolute; top: 15px; z-index: 10; background: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; text-decoration: none; cursor: pointer; backdrop-filter: blur(10px); transition: 0.2s; }
        .glass-btn:hover { background: rgba(255,255,255,0.4); transform: scale(1.05); }
        .edit-pos { left: 15px; } .logout-pos { right: 15px; }
        .avatar-lg { width: 100px; height: 100px; background: white; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 36px; color: var(--blue); border: 4px solid rgba(255,255,255,0.5); overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,0.15); }
        .avatar-lg img { width: 100%; height: 100%; object-fit: cover; }
        .id-role { background: rgba(0,0,0,0.2); padding: 4px 12px; border-radius: 12px; font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 600; backdrop-filter: blur(5px); }
        .id-body { padding: 25px; text-align: left; }
        .info-group { margin-bottom: 20px; }
        .info-label { display: block; font-size: 11px; font-weight: 700; color: #86868B; text-transform: uppercase; margin-bottom: 5px; }
        .info-val { font-size: 15px; font-weight: 500; color: #1D1D1F; border-bottom: 1px solid #E5E5EA; padding-bottom: 8px; display: block; }
        .info-val i { width: 20px; color: #FF9F0A; opacity: 0.8; }
        .stats-area { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .stat-box { background: rgba(255, 255, 255, 0.85); padding: 20px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; border: 1px solid rgba(255,255,255,0.4); }
        .sb-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05); }
        .sb-green { background: #D1FAE5; color: #34C759; } .sb-blue { background: #E0F2FE; color: #007AFF; }
        .shift-card { background: rgba(255, 255, 255, 0.85); padding: 25px; border-radius: 20px; margin-top: 20px; border: 1px solid rgba(255,255,255,0.4); box-shadow: 0 4px 15px rgba(0,0,0,0.05); }

        /* === NEW ACTIVITY HUB STYLES === */
        .hub-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .hub-card { background: rgba(255, 255, 255, 0.9); border-radius: 20px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.6); }
        .hub-title { font-size: 16px; font-weight: 700; color: #1D1D1F; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        
        /* MAC TABLE STYLE */
        .mac-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .mac-table th { text-align: left; font-size: 11px; text-transform: uppercase; color: #86868B; padding: 0 0 10px 5px; border-bottom: 1px solid #E5E5EA; }
        .mac-table td { font-size: 13px; color: #1D1D1F; padding: 12px 5px; border-bottom: 1px solid #F5F5F7; }
        .mac-table tr:last-child td { border-bottom: none; }
        .mac-badge { padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 600; display: inline-block; }
        
        .bd-green { background: #D1FAE5; color: #065F46; }
        .bd-red { background: #FEE2E2; color: #991B1B; }
        .bd-orange { background: #FFEDD5; color: #9A3412; }
        .bd-blue { background: #E0F2FE; color: #007AFF; }

        .empty-state { text-align: center; padding: 30px; color: #86868B; font-size: 13px; font-style: italic; }

        /* MAC MODAL OVERRIDES */
        .mac-modal { font-family: -apple-system, sans-serif; text-align: left; }
        .mac-form-row { margin-bottom: 15px; }
        .mac-label { font-size: 12px; color: #6e6e73; font-weight: 500; margin-bottom: 6px; display: block; }
        .mac-input { width: 100%; padding: 10px 12px; border: 1px solid #d2d2d7; border-radius: 8px; font-size: 14px; background: #fff; box-sizing: border-box; transition: all 0.2s; }
        .mac-input:focus { border-color: #007aff; outline: none; box-shadow: 0 0 0 3px rgba(0,122,255,0.2); }
        .mac-divider { margin: 20px 0; border: 0; border-top: 1px solid #d2d2d7; }
        .mac-btn-save { background: #007AFF !important; border-radius: 8px !important; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .mac-btn-cancel { background: #E5E5EA !important; color: #000 !important; border-radius: 8px !important; }
        .mac-popup-radius { border-radius: 18px !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important; }

        /* CALENDAR STYLE */
        .fc { font-size: 13px; }
        .fc-toolbar-title { font-size: 16px !important; font-weight: 700; color: #1D1D1F; }
        .fc-button { background: #007AFF !important; border: none !important; font-size: 12px !important; font-weight: 600 !important; border-radius: 8px !important; padding: 6px 12px !important; }
        .fc-button-active { background: #005bb5 !important; }
        .fc-daygrid-day-number { color: #1D1D1F; font-weight: 600; }
        .fc-col-header-cell-cushion { color: #86868B; text-transform: uppercase; font-size: 11px; }
        .fc-event { border-radius: 6px; border: none; font-size: 11px; font-weight: 600; padding: 2px 4px; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <div>
            <div class="h-title">Worker Dashboard</div>
            <div class="h-sub">
                Logged in as <?php echo $worker['Name']; ?> 
                <span style="background:#eee; padding:2px 6px; border-radius:4px; font-size:10px; margin-left:5px;">ID: <?php echo $worker['Worker_ID']; ?></span>
            </div>
        </div>
        
        <div class="header-actions">
            <div class="notif-bell" onclick="showNotifications()">
                <i class="fas fa-bell"></i>
                <span id="notif-badge" class="notif-badge">0</span>
            </div>

            <form method="post" class="status-form">
                <input type="hidden" name="action" value="update_status">
                <select name="status" onchange="this.form.submit()" style="background-color: <?php echo $status_bg; ?>; color: <?php echo $status_txt; ?>;">
                    <option value="Available" <?php if($worker['Availability']=='Available') echo 'selected'; ?>>● Available</option>
                    <option value="Sick" <?php if($worker['Availability']=='Sick') echo 'selected'; ?>>● Sick</option>
                    <option value="On Leave" <?php if($worker['Availability']=='On Leave') echo 'selected'; ?>>● On Leave</option>
                </select>
            </form>
        </div>
    </div>

    <div class="grid">
        <div class="id-card">
            <div class="id-top">
                <button type="button" onclick="openMacModal()" class="glass-btn edit-pos"><i class="fas fa-cog"></i> Edit</button>
                <a href="login.php" class="glass-btn logout-pos">Logout <i class="fas fa-sign-out-alt"></i></a>
                <div class="avatar-lg">
                    <img src="https://api.dicebear.com/9.x/notionists/svg?seed=<?php echo urlencode($worker['Name']); ?>&backgroundColor=ffdfbf,c0aede,d1d4f9" alt="Avatar">
                </div>
                <h2 style="margin:0; font-size:22px;"><?php echo $worker['Name']; ?></h2>
                <span class="id-role"><?php echo $worker['Role']; ?></span>
            </div>
            <div class="id-body">
                <div class="info-group">
                    <span class="info-label">Contact</span>
                    <span class="info-val"><i class="fas fa-phone"></i> <?php echo $worker['Phone']; ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Address</span>
                    <span class="info-val"><i class="fas fa-map-marker-alt"></i> <?php echo $worker['Home_Address']; ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">Emergency Contact</span>
                    <span class="info-val" style="border:none;">
                        <i class="fas fa-user-shield"></i> 
                        <?php echo $worker['Emergency_Contact_Name']; ?> <br>
                        <span style="font-size:12px; color:#86868B; margin-left:24px;"><?php echo $worker['Emergency_Contact']; ?></span>
                    </span>
                </div>
            </div>
        </div>

        <div>
            <div class="stats-area">
                <div class="stat-box">
                    <div class="sb-icon sb-green"><i class="fas fa-tachometer-alt"></i></div>
                    <div>
                        <div style="font-size:28px; font-weight:800; color:#1D1D1F;"><?php echo $worker['Efficiency_Rating']; ?>%</div>
                        <div style="font-size:12px; color:#86868B; font-weight:600;">EFFICIENCY SCORE</div>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="sb-icon sb-blue"><i class="fas fa-calendar-check"></i></div>
                    <div>
                        <div style="font-size:28px; font-weight:800; color:#1D1D1F;"><?php echo date('M d, Y', strtotime($worker['Joining_Date'])); ?></div>
                        <div style="font-size:12px; color:#86868B; font-weight:600;">JOINED TEAM</div>
                    </div>
                </div>
            </div>

            <div class="shift-card">
                <h3 style="margin-top:0; border-bottom:1px solid #E5E5EA; padding-bottom:15px; color:#1D1D1F;">Work Details</h3>
                <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                    <span style="color:#86868B;">Shift Timing</span>
                    <span style="font-weight:600;"><?php echo $worker['Shift_Timing']; ?></span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                    <span style="color:#86868B;">Gross Salary</span>
                    <span style="font-weight:600;">৳ <?php echo number_format($worker['Gross_Salary']); ?></span>
                </div>
                <div style="background:rgba(255,247,237,0.7); padding:15px; border-radius:10px; margin-top:20px; border:1px solid #FFEDD5;">
                    <span style="display:block; font-size:11px; font-weight:700; color:#C2410C; text-transform:uppercase;">My Preferences</span>
                    <span style="color:#9A3412; font-size:14px;"><?php echo $worker['Preferences']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="hub-card" style="margin-bottom: 25px;">
        <div class="hub-title"><i class="fas fa-calendar-alt" style="color:#FF9F0A;"></i> My Schedule</div>
        <div id="calendar"></div>
    </div>

    <div class="hub-grid">
        <div class="hub-card">
            <div class="hub-title"><i class="fas fa-history" style="color:#007AFF;"></i> Production History</div>
            
            <?php if ($history_res->num_rows > 0): ?>
            <table class="mac-table">
                <thead><tr><th>Batch / Item</th><th>Stage</th><th>Time (Act/Tgt)</th><th>Eff%</th></tr></thead>
                <tbody>
                    <?php while($h = $history_res->fetch_assoc()): 
                        $eff_color = ($h['Efficiency_Score'] >= 90) ? 'bd-green' : (($h['Efficiency_Score'] < 80) ? 'bd-red' : 'bd-orange');
                    ?>
                    <tr>
                        <td><b>#<?php echo $h['Batch_ID']; ?></b><br><span style="color:#86868B; font-size:11px;"><?php echo $h['Product_Type']; ?></span></td>
                        <td><?php echo $h['Stage_Name']; ?></td>
                        <td><?php echo $h['Actual_Time'] . "m / " . $h['Target_Time'] . "m"; ?></td>
                        <td><span class="mac-badge <?php echo $eff_color; ?>"><?php echo $h['Efficiency_Score']; ?>%</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty-state">No recent production history found for ID <?php echo $w_id; ?>.</div>
            <?php endif; ?>
        </div>

        <div class="hub-card">
            <div class="hub-title"><i class="fas fa-bug" style="color:#FF3B30;"></i> Defect Reports</div>
            
            <?php if ($defect_res->num_rows > 0): ?>
            <table class="mac-table">
                <thead><tr><th>Issue</th><th>Action</th><th>Role</th></tr></thead>
                <tbody>
                    <?php while($d = $defect_res->fetch_assoc()): 
                         $role_badge = ($d['Found_By_Worker_ID'] == $w_id) ? '<span class="mac-badge bd-blue">Found</span>' : '<span class="mac-badge bd-orange">Assigned</span>';
                    ?>
                    <tr>
                        <td>
                            <b><?php echo $d['Defect_Type']; ?></b>
                            <div style="font-size:11px; color:#86868B;">Batch #<?php echo $d['Batch_ID']; ?></div>
                        </td>
                        <td><?php echo $d['Action_Taken']; ?></td>
                        <td><?php echo $role_badge; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty-state">No defects reported recently. Great job!</div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    <?php echo $msg_script; ?>
    const w = <?php echo $worker_json; ?>;

    // --- NEW: LIVE NOTIFICATION POLLING ---
    let lastNotifId = 0;
    let currentNotifs = []; // Store the alerts globally so the modal can read them
    
    function pollNotifications() {
        fetch('check_notifications.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notif-badge');
            currentNotifs = data.all_unread || []; // Grab the full array of alerts
            
            if (data.unread_count > 0) {
                badge.style.display = 'flex';
                badge.innerText = data.unread_count;
                
                // If there's a new notification we haven't seen yet, trigger Toast
                if (data.latest && data.latest.Notif_ID > lastNotifId) {
                    lastNotifId = data.latest.Notif_ID;
                    
                    let toastIcon = 'info';
                    if (data.latest.Type === 'Warning' || data.latest.Type === 'Conflict') toastIcon = 'error';
                    if (data.latest.Type === 'Deadline') toastIcon = 'warning';
                    if (data.latest.Type === 'Assignment') toastIcon = 'success';

                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: toastIcon,
                        title: data.latest.Message,
                        showConfirmButton: false,
                        timer: 5000,
                        timerProgressBar: true
                    });
                    
                    // Refresh calendar automatically if assigned a new task
                    if (data.latest.Type === 'Assignment' && typeof calendarInstance !== 'undefined') {
                        calendarInstance.refetchEvents();
                    }
                }
            } else {
                badge.style.display = 'none';
            }
        }).catch(err => console.error("Notification Polling Error", err));
    }

    // Check immediately, then every 10 seconds
    pollNotifications();
    setInterval(pollNotifications, 10000); 

    function showNotifications() {
        if (currentNotifs.length === 0) {
            Swal.fire({ title: 'Notifications', text: 'You have no new alerts.', icon: 'info' });
            return;
        }

        // Build HTML list of messages
        let listHtml = '<div style="text-align:left; max-height: 250px; overflow-y:auto; padding-right:10px;">';
        currentNotifs.forEach(n => {
            // Pick a color based on type
            let color = '#007AFF';
            if(n.Type === 'Warning' || n.Type === 'Conflict') color = '#FF3B30';
            if(n.Type === 'Assignment') color = '#34C759';

            listHtml += `
                <div style="background:#F2F2F7; padding:12px; border-radius:10px; margin-bottom:10px; font-size:13px; color:#1D1D1F; border-left: 4px solid ${color};">
                    <b style="color:${color}; font-size:11px; text-transform:uppercase;">${n.Type}</b><br>
                    <div style="margin-top:4px;">${n.Message}</div>
                    <div style="font-size:10px; color:#8E8E93; margin-top:6px;"><i class="far fa-clock"></i> ${n.Created_At}</div>
                </div>`;
        });
        listHtml += '</div>';

        Swal.fire({
            title: 'Recent Alerts',
            html: listHtml,
            confirmButtonText: 'Mark All as Read',
            showCancelButton: true,
            cancelButtonText: 'Close',
            customClass: { confirmButton: 'mac-btn-save', popup: 'mac-popup-radius' }
        }).then((result) => {
            if (result.isConfirmed) {
                // Send AJAX request to mark notifications as read in the DB
                fetch('check_notifications.php?action=mark_read').then(() => {
                    document.getElementById('notif-badge').style.display = 'none';
                    currentNotifs = []; // Clear the list
                });
            }
        });
    }
    // --- END NOTIFICATION LOGIC ---


    // --- FULLCALENDAR INIT ---
    let calendarInstance; // Make it global so the notification polling can refresh it
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        calendarInstance = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek'
            },
            height: 400,
            events: 'fetch_tasks.php', // This file queries the new Project_Tasks/Production_Stage tables
            eventColor: '#007AFF'
        });
        calendarInstance.render();
    });

    function openMacModal() {
        Swal.fire({
            title: 'Edit Profile',
            html: `
                <div class="mac-modal">
                    <form id="editForm" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mac-form-row"><label class="mac-label">Mobile Number</label><input type="text" name="phone" class="mac-input" value="${w.Phone}" required></div>
                        <div class="mac-form-row"><label class="mac-label">Home Address</label><input type="text" name="address" class="mac-input" value="${w.Home_Address}" required></div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div class="mac-form-row"><label class="mac-label">Contact Name</label><input type="text" name="em_name" class="mac-input" value="${w.Emergency_Contact_Name}" required></div>
                            <div class="mac-form-row"><label class="mac-label">Contact No.</label><input type="text" name="em_phone" class="mac-input" value="${w.Emergency_Contact}" required></div>
                        </div>
                        <div class="mac-form-row"><label class="mac-label">Preferences</label><input type="text" name="pref" class="mac-input" value="${w.Preferences}"></div>
                        <hr class="mac-divider">
                        <div class="mac-form-row"><label class="mac-label" style="color:#FF3B30;">New Password (Optional)</label><input type="password" name="new_pass" class="mac-input" placeholder="••••••"></div>
                    </form>
                </div>
            `,
            showCancelButton: true, confirmButtonText: 'Save', cancelButtonText: 'Cancel',
            background: 'rgba(255, 255, 255, 0.95)', backdrop: `rgba(0,0,0,0.3) backdrop-filter: blur(10px)`,
            customClass: { popup: 'mac-popup-radius', confirmButton: 'mac-btn-save', cancelButton: 'mac-btn-cancel' },
            width: '450px', padding: '25px',
            preConfirm: () => { document.getElementById('editForm').submit(); }
        });
    }
</script>

<style>
    .swal2-title { font-size: 18px !important; color: #1d1d1f !important; font-weight: 600 !important; margin-bottom: 20px !important;}
    .swal2-actions { margin-top: 20px !important; width: 100%; display: flex; justify-content: flex-end; gap: 10px; }
    .swal2-confirm, .swal2-cancel { display: inline-block !important; margin: 0 !important; width: 80px !important; }
</style>

</body>
</html>