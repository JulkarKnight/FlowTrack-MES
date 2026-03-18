<?php 
// ==========================================
// 1. ENGINE FIRST: INIT SESSION & DB
// ==========================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$servername = "127.0.0.1"; 
$username = "root";
$password = "";
$dbname = "flowtrack_mes";
$port = 3306; 

$conn = @new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    $conn = @new mysqli("localhost", "root", "", "flowtrack_mes");
    if ($conn->connect_error) { die("Database connection failed."); }
}

// ==========================================
// 2. NOTIFICATION HELPER
// ==========================================
if (!function_exists('trigger_notification')) {
    function trigger_notification($conn, $worker_id, $message, $type) {
        $stmt = $conn->prepare("INSERT INTO Notifications (Worker_ID, Message, Type, Is_Read, Created_At) VALUES (?, ?, ?, FALSE, NOW())");
        if($stmt) { $stmt->bind_param("iss", $worker_id, $message, $type); $stmt->execute(); }
    }
}

// ==========================================
// 3. AJAX: SMART ASSIGNMENT ENGINE
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'smart_assign') {
    error_reporting(0); // Prevent PHP warnings from corrupting the JSON response
    ob_clean(); // Wipes any accidental HTML or spaces from the buffer
    header('Content-Type: application/json'); // Tell JS to expect clean JSON
    
    try {
        $stage_id = intval($_POST['stage_id']);
        $worker_id = intval($_POST['worker_id']);

        // Fetch Stage Details
        $stage_data = $conn->query("SELECT Stage_Name, Start_Time, Deadline, Batch_ID FROM Production_Stage WHERE Stage_ID = $stage_id")->fetch_assoc();
        $s_start = $stage_data['Start_Time'] ?: date('Y-m-d H:i:s');
        $s_end = $stage_data['Deadline'] ?: date('Y-m-d H:i:s', strtotime('+2 days'));
        $stage_name = $stage_data['Stage_Name'];

        // CHECK: Real-Time Conflict Detection
        $overlap_sql = "SELECT Stage_Name, Batch_ID FROM Production_Stage 
                        WHERE Assigned_Worker_ID = ? AND Status = 'In Progress' 
                        AND (Start_Time < ? AND Deadline > ?)";
        $stmt = $conn->prepare($overlap_sql);
        $stmt->bind_param("iss", $worker_id, $s_end, $s_start);
        $stmt->execute();
        $overlap_result = $stmt->get_result();

        // CHECK: Workload Balancing
        $workload_sql = "SELECT COUNT(*) as active_count FROM Production_Stage WHERE Assigned_Worker_ID = ? AND Status = 'In Progress'";
        $stmt = $conn->prepare($workload_sql);
        $stmt->bind_param("i", $worker_id);
        $stmt->execute();
        $active_tasks = $stmt->get_result()->fetch_assoc()['active_count'];

        // Smart Suggestion Logic
        $sugg_sql = "SELECT Name FROM Worker WHERE Availability = 'Available' AND Worker_ID NOT IN (
                        SELECT Assigned_Worker_ID FROM Production_Stage 
                        WHERE Status = 'In Progress' AND (Start_Time < '$s_end' AND Deadline > '$s_start') AND Assigned_Worker_ID IS NOT NULL
                     ) LIMIT 1";
        $sugg_res = $conn->query($sugg_sql);
        $alt_name = ($sugg_res && $sugg_res->num_rows > 0) ? $sugg_res->fetch_assoc()['Name'] : "another available worker";

        // Evaluate Checks & Stop Script if Failed
        if ($overlap_result->num_rows > 0) {
            $conflict = $overlap_result->fetch_assoc();
            echo json_encode([
                "status" => "error", 
                "message" => "Date Conflict: Worker is already scheduled for '{$conflict['Stage_Name']}' (Batch #{$conflict['Batch_ID']}) during this time.", 
                "suggestion" => "Try assigning this to <b>$alt_name</b> instead."
            ]);
            exit; 
        }
        if ($active_tasks >= 3) {
            echo json_encode([
                "status" => "error", 
                "message" => "Workload Overload: Worker currently has $active_tasks active tasks.", 
                "suggestion" => "Reassign to balance the factory workload. Try <b>$alt_name</b>."
            ]);
            exit; 
        }

        // SUCCESS: Assign Worker & Trigger Notification
        $conn->query("UPDATE Production_Stage SET Assigned_Worker_ID = $worker_id, Status = 'In Progress', Start_Time = '$s_start', Deadline = '$s_end' WHERE Stage_ID = $stage_id");
        trigger_notification($conn, $worker_id, "You have been assigned to Batch #{$stage_data['Batch_ID']} - $stage_name", "Assignment");
        
        echo json_encode(["status" => "success"]);
        exit; 

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Server Error.", "suggestion" => "Please try again."]);
        exit;
    }
}

// ==========================================
// 4. INCLUDE LAYOUT & RENDER PAGE
// ==========================================
include 'layout_top.php'; 

// FETCH WORKERS FOR SMART ASSIGNMENT DROPDOWN
$workers_res = $conn->query("SELECT Worker_ID, Name, Role FROM Worker WHERE Availability = 'Available'");
$workers = [];
if($workers_res) { while($w = $workers_res->fetch_assoc()) { $workers[] = $w; } }
$workers_json = json_encode($workers);

echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
$swal_script = "";

// --- HANDLE NEW BATCH (DYNAMIC WORKFLOW WITH DATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create_batch') {
    $order = $_POST['order_id']; $prod = $_POST['product']; $qty = $_POST['quantity']; 
    $var = $_POST['variant']; $fab_id = $_POST['fabric_id']; $fab_req = $_POST['fabric_qty']; 
    $trim_id = $_POST['trim_id']; $trim_req = $_POST['trim_qty'];
    $workflow_type = $_POST['workflow_type'];
    $start_date_input = $_POST['start_date']; // NEW: Get the chosen start date

    $fab = $conn->query("SELECT * FROM Material WHERE Material_ID = $fab_id")->fetch_assoc();
    $trim = $conn->query("SELECT * FROM Material WHERE Material_ID = $trim_id")->fetch_assoc();
    $need_fab = $qty * $fab_req; $need_trim = $qty * $trim_req;

    if ($fab['Current_Stock'] < $need_fab || $trim['Current_Stock'] < $need_trim) {
        $swal_script = "Swal.fire({icon:'error', title:'Stock Low', text:'Insufficient materials.', confirmButtonColor: '#007AFF'});";
    } else {
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE Material SET Current_Stock = Current_Stock - $need_fab WHERE Material_ID = $fab_id");
            $conn->query("UPDATE Material SET Current_Stock = Current_Stock - $need_trim WHERE Material_ID = $trim_id");
            
            $stmt = $conn->prepare("INSERT INTO Batch (Order_ID, Product_Type, Start_Time, Status, Quantity, Fabric, Trims, Variant_Options) VALUES (?, ?, NOW(), 'Running', ?, ?, ?, ?)");
            $f_n = $fab['Material_Name']; $t_n = $trim['Material_Name'];
            $stmt->bind_param("isisss", $order, $prod, $qty, $f_n, $t_n, $var);
            $stmt->execute();
            $bid = $stmt->insert_id;

            // Generate Stages Dynamically from Templates Starting on Custom Date
            $templates = $conn->query("SELECT * FROM Workflow_Templates WHERE Project_Type = '$workflow_type' ORDER BY Stage_Order ASC");
            if ($templates && $templates->num_rows > 0) {
                // Initialize the tracker with the user's chosen start date (Assume work starts at 9:00 AM)
                $current_date = new DateTime($start_date_input . " 09:00:00"); 
                
                while($t = $templates->fetch_assoc()) {
                    $stage_name = $t['Stage_Name'];
                    $days = $t['Estimated_Days'];
                    
                    $start_str = $current_date->format('Y-m-d H:i:s');
                    $current_date->modify("+$days days");
                    $end_str = $current_date->format('Y-m-d H:i:s');
                    $target_mins = $days * 8 * 60; 
                    
                    $conn->query("INSERT INTO Production_Stage (Batch_ID, Stage_Name, Target_Time, Start_Time, Deadline, Status) VALUES ($bid, '$stage_name', $target_mins, '$start_str', '$end_str', 'Pending')");
                }
            } else {
                // Fallback for no template
                $conn->query("INSERT INTO Production_Stage (Batch_ID, Stage_Name, Target_Time, Status) VALUES ($bid, 'Standard Stage', 60, 'Pending')");
            }

            $conn->commit();
            $swal_script = "Swal.fire({icon:'success', title:'Launched', text:'Batch #$bid generated.', timer:2000, showConfirmButton:false});";
        } catch (Exception $e) {
            $conn->rollback();
            $swal_script = "Swal.fire({icon:'error', title:'Error', text:'Could not create batch.'});";
        }
    }
}

// --- HANDLE STAGE COMPLETION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'complete_stage') {
    $sid = $_POST['stage_id']; $mins = $_POST['mins'];
    $check = $conn->query("SELECT * FROM Production_Stage WHERE Stage_ID = $sid")->fetch_assoc();
    $wid = $check['Assigned_Worker_ID'] ?? $_POST['worker_id']; 
    $eff = ($mins > 0) ? round(($check['Target_Time'] / $mins) * 100, 2) : 0;

    $conn->query("UPDATE Production_Stage SET Actual_Time = $mins, Status = 'Completed' WHERE Stage_ID = $sid");
    if ($wid) {
        $today = date("Y-m-d");
        $stmt = $conn->prepare("INSERT INTO Worker_Performance (Worker_ID, Batch_ID, Stage_Name, Actual_Time, Target_Time, Efficiency_Score, Logged_Date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssds", $wid, $check['Batch_ID'], $check['Stage_Name'], $mins, $check['Target_Time'], $eff, $today);
        
        if($stmt->execute()){
            $conn->query("UPDATE Worker SET Efficiency_Rating = (SELECT AVG(Efficiency_Score) FROM Worker_Performance WHERE Worker_ID = $wid) WHERE Worker_ID = $wid");
            trigger_notification($conn, 1, "Batch #{$check['Batch_ID']} {$check['Stage_Name']} completed by Worker #$wid.", "Status");
        }
    }
}

// --- HANDLE DEFECT ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'log_defect') {
    $bid = $_POST['batch_id']; $stage = $_POST['stage']; $type = $_POST['type']; $qty = $_POST['qty']; $act = $_POST['act']; $wid = $_POST['worker'];
    if (!is_numeric($wid)) {
        $w_res = $conn->query("SELECT Worker_ID FROM Worker WHERE Name = '$wid'");
        if ($w_res->num_rows > 0) $wid = $w_res->fetch_assoc()['Worker_ID'];
    }
    $stmt = $conn->prepare("INSERT INTO Defect_Log (Batch_ID, Stage_Name, Defect_Type, Found_By_Worker_ID, Assigned_To_Worker_ID, Quantity, Action_Taken) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiiis", $bid, $stage, $type, $wid, $wid, $qty, $act);
    $stmt->execute();
    if ($act == 'Rework') { echo "<script>window.location.href='batches.php?rework_alert=1&bid=$bid&w_lock=$wid&s_lock=".urlencode($stage)."';</script>"; exit; }
}

// --- HANDLE DELETE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_batch') {
    $bid = $_POST['batch_id'];
    $conn->query("DELETE FROM Production_Stage WHERE Batch_ID = $bid");
    $conn->query("DELETE FROM Defect_Log WHERE Batch_ID = $bid");
    $conn->query("DELETE FROM Batch WHERE Batch_ID = $bid");
    
    // Clear post data by redirecting cleanly
    echo "<script>window.location.href='batches.php';</script>"; 
    exit;
}
?>

<style>
    :root {
        --ios-bg: #F2F2F7; --ios-card: #FFFFFF; --ios-blue: #007AFF; --ios-purple: #AF52DE; 
        --ios-gray: #8E8E93; --ios-border: #E5E5EA; --text-primary: #1C1C1E; --shadow-sm: 0 4px 12px rgba(0,0,0,0.03);
    }
    body { background-color: var(--ios-bg); font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif; }
    
    @keyframes ios-spring { 0% { opacity: 0; transform: scale(0.95) translateY(20px); } 60% { transform: scale(1.01) translateY(-2px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }
    .animate-spring { opacity: 0; animation: ios-spring 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
    .delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; }

    .page-header { display: flex; justify-content: space-between; align-items: end; margin-bottom: 25px; }
    .page-title { font-size: 34px; font-weight: 800; letter-spacing: -1px; margin: 0; color: var(--text-primary); }
    .page-sub { font-size: 13px; font-weight: 600; color: var(--ios-gray); text-transform: uppercase; letter-spacing: 0.5px; }
    
    .dashboard-grid { display: grid; grid-template-columns: 360px 1fr; gap: 30px; align-items: start; }
    .ios-card { background: var(--ios-card); border-radius: 22px; padding: 24px; box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.02); }
    .ios-input { width: 100%; padding: 12px 14px; border-radius: 12px; background: #F2F2F7; border: none; font-size: 15px; color: var(--text-primary); box-sizing: border-box; margin-bottom: 15px; transition: 0.2s; }
    .ios-input:focus { background: #fff; box-shadow: 0 0 0 2px var(--ios-blue); outline: none; }
    .input-label { font-size: 12px; font-weight: 600; color: var(--ios-gray); margin-bottom: 6px; display: block; }
    .ios-btn-primary { background: var(--ios-blue); color: white; border: none; width: 100%; padding: 14px; border-radius: 14px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.1s; }
    
    .mac-table { width: 100%; border-collapse: collapse; }
    .mac-table th { text-align: left; font-size: 11px; text-transform: uppercase; color: #86868B; padding: 10px; border-bottom: 1px solid #E5E5EA; }
    .mac-table td { font-size: 13px; color: #1D1D1F; padding: 15px 10px; border-bottom: 1px solid #F5F5F7; }
    .badge { padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 600; display: inline-block; }
    .badge-pending { background: #F2F2F7; color: #8E8E93; } .badge-progress { background: #E0F2FE; color: #007AFF; }
    .btn-assign { background: #E0F2FE; color: #007AFF; border: none; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-assign:hover { background: #007AFF; color: white; }
    .worker-pill { display: flex; align-items: center; gap: 8px; font-weight: 600; }
    .avatar-sm { width: 24px; height: 24px; border-radius: 50%; background: #E5E5EA; }

    .batch-card { background: var(--ios-card); border-radius: 22px; padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 20px; transition: all 0.2s; border: 1px solid rgba(0,0,0,0.02); }
    .progress-bar-track { height: 6px; background: #F2F2F7; border-radius: 3px; overflow: hidden; margin: 15px 0 20px 0; }
    .progress-fill { height: 100%; background: #34C759; border-radius: 3px; transition: width 0.5s ease; }
    .stage-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--ios-border); transition: 0.2s; }
    .stage-row:last-child { border-bottom: none; }
    .worker-select { padding: 6px 12px; border-radius: 8px; border: 1px solid #E5E5EA; font-size: 13px; background: white; color: var(--text-primary); }
    .btn-small { padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; background: var(--ios-blue); color: white; }
    .btn-flag { background: #FFF7ED; color: #F59E0B; border: none; padding: 6px 10px; border-radius: 8px; cursor: pointer; }
    .ios-btn-magic { background: linear-gradient(135deg, #AF52DE, #8E2DE2); color: white; border: none; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 10px rgba(175, 82, 222, 0.3); transition: 0.2s; }
    .ios-btn-magic:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(175, 82, 222, 0.5); }
</style>

<div class="page-header animate-spring">
    <div>
        <div class="page-sub">Production Hub</div>
        <h1 class="page-title">Operations & Scheduling</h1>
    </div>
</div>

<div class="dashboard-grid">

    <div class="ios-card animate-spring delay-1" style="position: sticky; top: 20px;">
        <h3 style="margin:0 0 5px 0; font-size:18px;">Create Job Card</h3>
        <p style="font-size:13px; color:var(--ios-gray); margin:0 0 20px 0;">Initialize a dynamic production run</p>

        <form method="post">
            <input type="hidden" name="action" value="create_batch">
            
            <span class="input-label">ORDER REFERENCE</span>
            <input type="number" name="order_id" class="ios-input" value="<?php echo rand(1000,9999); ?>">
            
            <span class="input-label">PRODUCT NAME</span>
            <input type="text" name="product" class="ios-input" placeholder="e.g. Slim Fit Denim" required>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div>
                    <span class="input-label" style="color:var(--ios-purple);"><i class="fas fa-magic"></i> TEMPLATE</span>
                    <select name="workflow_type" class="ios-input" required>
                        <?php 
                        $temps = $conn->query("SELECT DISTINCT Project_Type FROM Workflow_Templates");
                        if ($temps && $temps->num_rows > 0) {
                            while($t = $temps->fetch_assoc()) echo "<option value='{$t['Project_Type']}'>{$t['Project_Type']}</option>";
                        } else {
                            echo "<option value='Standard'>Standard Production</option>"; 
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <span class="input-label" style="color:var(--ios-purple);"><i class="far fa-calendar-alt"></i> START DATE</span>
                    <input type="date" name="start_date" class="ios-input" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div>
                    <span class="input-label">QUANTITY</span>
                    <input type="number" name="quantity" class="ios-input" placeholder="Units" required>
                </div>
                <div>
                    <span class="input-label">VARIANT</span>
                    <input type="text" name="variant" class="ios-input" placeholder="Size/Color">
                </div>
            </div>

            <div style="background:#F0F9FF; padding:15px; border-radius:16px; margin-bottom:15px;">
                <span class="input-label" style="color:#007AFF;">FABRIC ALLOCATION</span>
                <div style="display:flex; gap:10px;">
                    <select name="fabric_id" class="ios-input" style="margin:0; flex:2;">
                        <?php $r = $conn->query("SELECT * FROM Material WHERE Category='Fabric'"); while($row=$r->fetch_assoc()) echo "<option value='{$row['Material_ID']}'>{$row['Material_Name']}</option>"; ?>
                    </select>
                    <input type="number" step="0.01" name="fabric_qty" class="ios-input" style="margin:0; flex:1;" placeholder="Qty" required>
                </div>
            </div>

            <div style="background:#FFF7ED; padding:15px; border-radius:16px; margin-bottom:20px;">
                <span class="input-label" style="color:#F59E0B;">TRIMS ALLOCATION</span>
                <div style="display:flex; gap:10px;">
                    <select name="trim_id" class="ios-input" style="margin:0; flex:2;">
                        <?php $r = $conn->query("SELECT * FROM Material WHERE Category='Trim'"); while($row=$r->fetch_assoc()) echo "<option value='{$row['Material_ID']}'>{$row['Material_Name']}</option>"; ?>
                    </select>
                    <input type="number" step="0.01" name="trim_qty" class="ios-input" style="margin:0; flex:1;" placeholder="Qty" required>
                </div>
            </div>

            <button type="submit" class="ios-btn-primary"><i class="fas fa-play"></i> Launch Order</button>
        </form>
    </div>

    <div class="animate-spring delay-2">
        
        <div class="ios-card" style="margin-bottom: 30px; overflow-x: auto;">
            <h3 style="margin-top:0; font-size:16px;"><i class="fas fa-tasks" style="color:#AF52DE;"></i> Smart Assignment Board</h3>
            <table class="mac-table">
                <thead>
                    <tr>
                        <th>Batch / Product</th>
                        <th>Task Stage</th>
                        <th>Timeline</th>
                        <th>Assigned To</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $table_sql = "
                        SELECT s.*, b.Product_Type, w.Name as Worker_Name 
                        FROM Production_Stage s
                        JOIN Batch b ON s.Batch_ID = b.Batch_ID
                        LEFT JOIN Worker w ON s.Assigned_Worker_ID = w.Worker_ID
                        WHERE b.Status = 'Running' AND s.Status != 'Completed'
                        ORDER BY b.Batch_ID DESC, s.Stage_ID ASC
                    ";
                    $table_res = $conn->query($table_sql);
                    if($table_res && $table_res->num_rows > 0):
                        while($task = $table_res->fetch_assoc()):
                            $start = $task['Start_Time'] ? date('M d', strtotime($task['Start_Time'])) : 'TBD';
                            $end = $task['Deadline'] ? date('M d', strtotime($task['Deadline'])) : 'TBD';
                            $is_assigned = !empty($task['Assigned_Worker_ID']);
                    ?>
                    <tr>
                        <td><b>#<?php echo $task['Batch_ID']; ?></b><br><span style="font-size:11px; color:#86868B;"><?php echo $task['Product_Type']; ?></span></td>
                        <td><?php echo $task['Stage_Name']; ?></td>
                        <td style="color:#86868B; font-size:12px;"><i class="far fa-calendar-alt"></i> <?php echo "$start - $end"; ?></td>
                        <td>
                            <?php if($is_assigned): ?>
                                <div class="worker-pill">
                                    <img src="https://api.dicebear.com/9.x/notionists/svg?seed=<?php echo urlencode($task['Worker_Name']); ?>" class="avatar-sm">
                                    <?php echo $task['Worker_Name']; ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#FF3B30; font-style:italic; font-size:12px;">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo ($task['Status']=='Pending') ? 'badge-pending' : 'badge-progress'; ?>">
                                <?php echo $task['Status']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-assign" onclick="openAssignModal(<?php echo $task['Stage_ID']; ?>, '<?php echo $task['Stage_Name']; ?> (Batch #<?php echo $task['Batch_ID']; ?>)')">
                                <?php echo $is_assigned ? 'Reassign' : 'Assign Worker'; ?>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:#86868B;">No pending tasks to assign.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-bottom:15px; font-size:14px; font-weight:700; color:var(--ios-gray); text-transform:uppercase; letter-spacing:1px;">Active Floor Progress</div>
        
        <?php
        $batches = $conn->query("SELECT * FROM Batch WHERE Status='Running' ORDER BY Start_Time DESC");
        
        if ($batches && $batches->num_rows > 0) {
            while($b = $batches->fetch_assoc()) {
                $bid = $b['Batch_ID'];
                
                $stages = $conn->query("
                    SELECT s.*, w.Name as Worker_Name 
                    FROM Production_Stage s
                    LEFT JOIN Worker w ON s.Assigned_Worker_ID = w.Worker_ID
                    WHERE s.Batch_ID=$bid ORDER BY s.Stage_ID ASC
                ");
                $s_data = []; $completed = 0;
                while($row = $stages->fetch_assoc()) { $s_data[] = $row; if($row['Status'] == 'Completed' || $row['Actual_Time'] > 0) $completed++; }
                $pct = count($s_data) > 0 ? round(($completed / count($s_data)) * 100) : 0;

                echo "<div class='batch-card'>
                    <div style='display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;'>
                        <div>
                            <span style='background:#E0F2FE; color:#007AFF; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700; margin-right:8px;'>#$bid</span> 
                            <span style='font-size:17px; font-weight:700; color:#1C1C1E;'>{$b['Product_Type']}</span>
                            <div style='font-size:13px; color:#8E8E93; margin-top:4px;'>Ref #{$b['Order_ID']} • {$b['Quantity']} Units</div>
                        </div>
                        <form method='post' onsubmit=\"return confirm('Delete this entire batch?');\" style='margin:0;'>
                            <input type='hidden' name='action' value='delete_batch'>
                            <input type='hidden' name='batch_id' value='$bid'>
                            <button style='background:none; border:none; cursor:pointer; color:#FF3B30;'><i class='fas fa-trash'></i></button>
                        </form>
                    </div>

                    <div class='progress-bar-track'>
                        <div class='progress-fill' style='width: $pct%;'></div>
                    </div>";

                $is_unlocked = true; 
                foreach($s_data as $s) {
                    $sid = $s['Stage_ID'];
                    $sname = $s['Stage_Name'];
                    $assigned = $s['Assigned_Worker_ID'];
                    
                    if($s['Status'] == 'Completed' || $s['Actual_Time'] > 0) {
                        echo "<div class='stage-row'>
                            <div style='font-weight:600; font-size:14px; color:#1C1C1E;'>$sname</div>
                            <div style='font-size:13px; color:#34C759; font-weight:600;'><i class='fas fa-check-circle'></i> Done by {$s['Worker_Name']}</div>
                        </div>";
                    } else if ($is_unlocked) {
                        if (empty($assigned)) {
                            echo "<div class='stage-row'>
                                <div><div style='font-weight:600; font-size:14px;'>$sname</div><div style='font-size:12px; color:#FF3B30;'><i class='fas fa-exclamation-circle'></i> Unassigned</div></div>
                                <div style='font-size:12px; color:#86868B;'>Assign in board above &uarr;</div>
                            </div>";
                        } else {
                            echo "<div class='stage-row' style='background: #F0F9FF; padding: 12px; border-radius: 12px;'>
                                <div>
                                    <div style='font-weight:600; font-size:14px; color:#007AFF;'>$sname</div>
                                    <div style='font-size:12px; color:#8E8E93;'><i class='fas fa-user'></i> {$s['Worker_Name']}</div>
                                </div>
                                <div style='display:flex; align-items:center; gap:8px;'>
                                    <form method='post' style='display:flex; gap:5px; margin:0;'>
                                        <input type='hidden' name='action' value='complete_stage'>
                                        <input type='hidden' name='stage_id' value='$sid'>
                                        <input type='hidden' name='worker_id' value='$assigned'>
                                        <input type='number' name='mins' class='worker-select' style='width:60px; text-align:center;' placeholder='Mins' required>
                                        <button class='btn-small'>Log Time</button>
                                    </form>
                                    <button onclick=\"flagDefect($bid, '$sname', '{$s['Worker_Name']}')\" class='btn-flag'><i class='fas fa-flag'></i></button>
                                </div>
                            </div>";
                        }
                        $is_unlocked = false; 
                    } else {
                        echo "<div class='stage-row' style='opacity:0.4;'>
                            <div style='font-weight:600; font-size:14px;'>$sname</div>
                            <div style='font-size:12px; font-weight:600; color:#8E8E93;'><i class='fas fa-lock'></i> Locked</div>
                        </div>";
                    }
                }
                echo "</div>";
            }
        } else {
            echo "<div style='text-align:center; padding:50px; color:#8E8E93; font-style:italic;'>No active batches found. Create a Job Card to begin.</div>";
        }
        ?>
    </div>
</div>

<script>
    <?php echo $swal_script; ?>
    const workers = <?php echo $workers_json; ?>;

    // --- MODAL FOR SMART ASSIGNMENT TABLE ---
    function openAssignModal(stageId, taskName) {
        let optionsHtml = '<option value="" disabled selected>Select a worker...</option>';
        workers.forEach(w => {
            optionsHtml += `<option value="${w.Worker_ID}">${w.Name} (${w.Role})</option>`;
        });

        Swal.fire({
            title: `Assign Worker`,
            html: `
                <div style="font-size:14px; margin-bottom:15px; color:#1C1C1E; font-weight:600;">${taskName}</div>
                <div style="text-align:left;">
                    <label style="font-size:12px; color:#86868B; font-weight:600; text-transform:uppercase;">Select Worker</label>
                    <select id="swal-worker-select" class="ios-input" style="margin-top:5px; width:100%;">
                        ${optionsHtml}
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Run Smart Engine',
            confirmButtonColor: '#AF52DE',
            preConfirm: () => {
                const workerId = document.getElementById('swal-worker-select').value;
                if (!workerId) { Swal.showValidationMessage('Please select a worker'); }
                return workerId;
            }
        }).then((result) => {
            if (result.isConfirmed) { runSmartAssign(stageId, result.value); }
        });
    }

    // --- SMART ASSIGNMENT AJAX CALL ---
    function runSmartAssign(stageId, workerId) {
        const formData = new FormData();
        formData.append('ajax_action', 'smart_assign');
        formData.append('stage_id', stageId);
        formData.append('worker_id', workerId);

        fetch('batches.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: 'Worker Assigned & Notified!', showConfirmButton: false, timer: 2000
                }).then(() => {
                    // FIX: Stop form resubmission duplicate bugs by cleanly redirecting
                    window.location.href = 'batches.php'; 
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Smart Engine Blocked Assignment',
                    html: `
                        <div style="color:#FF3B30; margin-bottom:15px; font-weight:600;">${data.message}</div>
                        <div style="background:#F2F2F7; padding:15px; border-radius:10px; font-size:14px; text-align:left;">
                            <i class="fas fa-lightbulb" style="color:#F59E0B;"></i> <b>Suggestion:</b> ${data.suggestion}
                        </div>
                    `,
                    confirmButtonText: 'Understood', confirmButtonColor: '#1D1D1F'
                });
            }
        }).catch(err => {
            Swal.fire({icon: 'error', title: 'Network Error', text: 'Failed to contact the smart engine.'});
        });
    }

    // --- DEFECT LOGGING ---
    function flagDefect(bid, stage, workerName) {
        Swal.fire({
            title: 'Report Defect',
            html: `
                <div style="font-size:12px; color:#8E8E93; margin-bottom:10px; text-align:left;"><b>Assigned Worker:</b> ${workerName}</div>
                <select id="swal-type" class="ios-input"><option>Wrong Pattern</option><option>Stain</option><option>Stitching Error</option></select>
                <input id="swal-qty" type="number" placeholder="Qty" class="ios-input">
                <select id="swal-act" class="ios-input"><option>Rework</option><option>Reject</option></select>
            `,
            showCancelButton: true, confirmButtonText: 'Log Defect', confirmButtonColor: '#FF3B30'
        }).then((res) => {
            if(res.isConfirmed) {
                const f = document.createElement('form'); f.method = 'POST';
                f.innerHTML = `
                    <input type="hidden" name="action" value="log_defect">
                    <input type="hidden" name="batch_id" value="${bid}">
                    <input type="hidden" name="stage" value="${stage}">
                    <input type="hidden" name="worker" value="${workerName}">
                    <input type="hidden" name="type" value="${document.getElementById('swal-type').value}">
                    <input type="hidden" name="qty" value="${document.getElementById('swal-qty').value}">
                    <input type="hidden" name="act" value="${document.getElementById('swal-act').value}">`;
                document.body.append(f); f.submit();
            }
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('rework_alert')) {
        Swal.fire({
            icon: 'warning', title: 'Rework Flagged!', text: 'Defect logged. Worker locked for rework.',
            showCancelButton: true, confirmButtonText: 'Continue', cancelButtonText: 'Pause',
            confirmButtonColor: '#34C759', cancelButtonColor: '#FF3B30'
        });
    }
</script>

<?php include 'layout_bottom.php'; ?>