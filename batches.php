<?php 
include 'layout_top.php'; 

// --- SAFETY & CONFIG ---
$servername = "127.0.0.1"; 
$username = "root";
$password = "";
$dbname = "flowtrack_mes";
$port = 3306; 

$conn = @new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    $conn = @new mysqli("localhost", "root", "", "flowtrack_mes");
    if ($conn->connect_error) { die('<div style="padding:20px; color:red;">Database connection failed.</div>'); }
}

echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

$swal_script = "";

// --- 1. HANDLE NEW BATCH ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create_batch') {
    $order = $_POST['order_id']; $prod = $_POST['product']; $qty = $_POST['quantity']; 
    $var = $_POST['variant']; $fab_id = $_POST['fabric_id']; $fab_req = $_POST['fabric_qty']; 
    $trim_id = $_POST['trim_id']; $trim_req = $_POST['trim_qty'];

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

            $mult = ($qty > 500) ? 1.5 : 1;
            $conn->query("INSERT INTO Production_Stage (Batch_ID, Stage_Name, Target_Time) VALUES ($bid, 'Cutting', ".(60*$mult).")");
            $conn->query("INSERT INTO Production_Stage (Batch_ID, Stage_Name, Target_Time) VALUES ($bid, 'Sewing', ".(120*$mult).")");
            $conn->query("INSERT INTO Production_Stage (Batch_ID, Stage_Name, Target_Time) VALUES ($bid, 'Finishing', ".(45*$mult).")");

            $conn->commit();
            $swal_script = "Swal.fire({icon:'success', title:'Launched', text:'Batch #$bid started.', timer:1500, showConfirmButton:false});";
        } catch (Exception $e) {
            $conn->rollback();
            $swal_script = "Swal.fire({icon:'error', title:'Error', text:'Could not create batch.'});";
        }
    }
}

// --- 2. HANDLE STAGE UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'complete_stage') {
    $sid = $_POST['stage_id']; $wid = $_POST['worker_id']; $mins = $_POST['mins'];
    
    $check = $conn->query("SELECT Target_Time, Batch_ID, Stage_Name FROM Production_Stage WHERE Stage_ID = $sid")->fetch_assoc();
    $eff = ($mins > 0) ? round(($check['Target_Time'] / $mins) * 100, 2) : 0;

    $conn->query("UPDATE Production_Stage SET Actual_Time = $mins WHERE Stage_ID = $sid");
    
    $today = date("Y-m-d");
    $stmt = $conn->prepare("INSERT INTO Worker_Performance (Worker_ID, Batch_ID, Stage_Name, Actual_Time, Target_Time, Efficiency_Score, Logged_Date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssds", $wid, $check['Batch_ID'], $check['Stage_Name'], $mins, $check['Target_Time'], $eff, $today);
    
    if ($stmt->execute()) {
        $conn->query("UPDATE Worker SET Efficiency_Rating = (SELECT AVG(Efficiency_Score) FROM Worker_Performance WHERE Worker_ID = $wid) WHERE Worker_ID = $wid");
    }
}

// --- 3. HANDLE DEFECT ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'log_defect') {
    $bid = $_POST['batch_id']; $stage = $_POST['stage']; $type = $_POST['type']; $qty = $_POST['qty']; $act = $_POST['act']; $wid = $_POST['worker'];
    
    $stmt = $conn->prepare("INSERT INTO Defect_Log (Batch_ID, Stage_Name, Defect_Type, Found_By_Worker_ID, Assigned_To_Worker_ID, Quantity, Action_Taken) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiiis", $bid, $stage, $type, $wid, $wid, $qty, $act);
    $stmt->execute();
    
    if ($act == 'Rework') {
        echo "<script>window.location.href='batches.php?rework_alert=1&bid=$bid&w_lock=$wid&s_lock=".urlencode($stage)."';</script>";
        exit;
    } else {
        $swal_script = "Swal.fire({icon:'warning', title:'Flagged', text:'Defect recorded.', timer:1500, showConfirmButton:false});";
    }
}

// --- 4. HANDLE DELETE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_batch') {
    $bid = $_POST['batch_id'];
    $conn->query("DELETE FROM Production_Stage WHERE Batch_ID = $bid");
    $conn->query("DELETE FROM Defect_Log WHERE Batch_ID = $bid");
    $conn->query("DELETE FROM Batch WHERE Batch_ID = $bid");
}
?>

<style>
    :root {
        --ios-bg: #F2F2F7;
        --ios-card: #FFFFFF;
        --ios-blue: #007AFF;
        --ios-gray: #8E8E93;
        --ios-border: #E5E5EA;
        --text-primary: #1C1C1E;
        --shadow-sm: 0 4px 12px rgba(0,0,0,0.03);
    }

    body { background-color: var(--ios-bg); font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif; }

    /* === ANIMATIONS === */
    @keyframes ios-spring {
        0% { opacity: 0; transform: scale(0.95) translateY(20px); }
        60% { transform: scale(1.01) translateY(-2px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }

    .animate-spring { opacity: 0; animation: ios-spring 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }

    .page-header { display: flex; justify-content: space-between; align-items: end; margin-bottom: 25px; }
    .page-title { font-size: 34px; font-weight: 800; letter-spacing: -1px; margin: 0; color: var(--text-primary); }
    .page-sub { font-size: 13px; font-weight: 600; color: var(--ios-gray); text-transform: uppercase; letter-spacing: 0.5px; }

    .dashboard-grid { display: grid; grid-template-columns: 360px 1fr; gap: 30px; align-items: start; }

    /* CARD STYLE */
    .ios-card {
        background: var(--ios-card); border-radius: 22px; padding: 24px;
        box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.02);
    }

    /* INPUTS */
    .ios-input {
        width: 100%; padding: 12px 14px; border-radius: 12px;
        background: #F2F2F7; border: none; font-size: 15px; color: var(--text-primary);
        box-sizing: border-box; margin-bottom: 15px; transition: 0.2s;
    }
    .ios-input:focus { background: #fff; box-shadow: 0 0 0 2px var(--ios-blue); outline: none; }
    
    .input-label { font-size: 12px; font-weight: 600; color: var(--ios-gray); margin-bottom: 6px; display: block; }

    /* BUTTONS */
    .ios-btn-primary {
        background: var(--ios-blue); color: white; border: none; width: 100%;
        padding: 14px; border-radius: 14px; font-size: 16px; font-weight: 600; cursor: pointer;
        transition: transform 0.1s;
    }
    .ios-btn-primary:active { transform: scale(0.98); }

    /* BATCH LIST ITEM */
    .batch-card {
        background: var(--ios-card); border-radius: 22px; padding: 20px;
        box-shadow: var(--shadow-sm); margin-bottom: 20px;
        transition: all 0.2s; border: 1px solid rgba(0,0,0,0.02);
    }
    .batch-card:hover { transform: scale(1.01); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }

    .progress-bar-track { height: 6px; background: #F2F2F7; border-radius: 3px; overflow: hidden; margin: 15px 0 20px 0; }
    .progress-fill { height: 100%; background: #34C759; border-radius: 3px; transition: width 0.5s ease; }

    /* ROW STYLES */
    .stage-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--ios-border); }
    .stage-row:last-child { border-bottom: none; }
    
    .worker-select {
        padding: 6px 12px; border-radius: 8px; border: 1px solid #E5E5EA; 
        font-size: 13px; background: white; color: var(--text-primary);
    }
    
    .btn-small { 
        padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; 
        border: none; cursor: pointer; background: var(--ios-blue); color: white;
    }
    
    .btn-flag {
        background: #FFF7ED; color: #F59E0B; border: none; 
        padding: 6px 10px; border-radius: 8px; cursor: pointer;
    }
</style>

<div class="page-header animate-spring">
    <div>
        <div class="page-sub">Production</div>
        <h1 class="page-title">Manufacturing Orders</h1>
    </div>
</div>

<div class="dashboard-grid">

    <div class="ios-card animate-spring delay-1">
        <h3 style="margin:0 0 5px 0; font-size:18px;">Create Job Card</h3>
        <p style="font-size:13px; color:var(--ios-gray); margin:0 0 20px 0;">Configure new production run</p>

        <form method="post">
            <input type="hidden" name="action" value="create_batch">
            
            <span class="input-label">ORDER REFERENCE</span>
            <input type="number" name="order_id" class="ios-input" value="<?php echo rand(1000,9999); ?>">
            
            <span class="input-label">PRODUCT NAME</span>
            <input type="text" name="product" class="ios-input" placeholder="e.g. Slim Fit Denim">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div>
                    <span class="input-label">QUANTITY</span>
                    <input type="number" name="quantity" class="ios-input" placeholder="Units">
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
                    <input type="number" step="0.01" name="fabric_qty" class="ios-input" style="margin:0; flex:1;" placeholder="Qty">
                </div>
            </div>

            <div style="background:#FFF7ED; padding:15px; border-radius:16px; margin-bottom:20px;">
                <span class="input-label" style="color:#F59E0B;">TRIMS ALLOCATION</span>
                <div style="display:flex; gap:10px;">
                    <select name="trim_id" class="ios-input" style="margin:0; flex:2;">
                        <?php $r = $conn->query("SELECT * FROM Material WHERE Category='Trim'"); while($row=$r->fetch_assoc()) echo "<option value='{$row['Material_ID']}'>{$row['Material_Name']}</option>"; ?>
                    </select>
                    <input type="number" step="0.01" name="trim_qty" class="ios-input" style="margin:0; flex:1;" placeholder="Qty">
                </div>
            </div>

            <button type="submit" class="ios-btn-primary">Launch Order</button>
        </form>
    </div>

    <div class="animate-spring delay-2">
        
        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; margin-bottom:30px;">
            <?php 
            $stocks = $conn->query("SELECT * FROM Material ORDER BY Material_ID ASC LIMIT 4");
            if($stocks) {
                while($m = $stocks->fetch_assoc()) {
                    echo "<div class='ios-card' style='padding:15px; text-align:center;'>
                        <div style='font-size:10px; font-weight:700; color:#8E8E93; text-transform:uppercase; margin-bottom:5px;'>{$m['Material_Name']}</div>
                        <div style='font-size:18px; font-weight:800; color:#1C1C1E;'>".number_format($m['Current_Stock'])."</div>
                    </div>";
                }
            }
            ?>
        </div>

        <div style="margin-bottom:15px; font-size:14px; font-weight:700; color:var(--ios-gray); text-transform:uppercase; letter-spacing:1px;">Production Floor</div>
        
        <?php
        $batches = $conn->query("SELECT * FROM Batch WHERE Status='Running' ORDER BY Start_Time DESC");
        
        if ($batches && $batches->num_rows > 0) {
            while($b = $batches->fetch_assoc()) {
                $bid = $b['Batch_ID'];
                
                // Get Progress & Stages (Ordered for sequence)
                $stages = $conn->query("SELECT * FROM Production_Stage WHERE Batch_ID=$bid ORDER BY Stage_ID ASC");
                $s_data = []; $completed = 0;
                while($row = $stages->fetch_assoc()) { $s_data[] = $row; if($row['Actual_Time'] > 0) $completed++; }
                $pct = count($s_data) > 0 ? round(($completed / count($s_data)) * 100) : 0;
                $b_json = htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8');

                echo "<div class='batch-card'>
                    <div style='display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;'>
                        <div>
                            <span style='background:#E0F2FE; color:#007AFF; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700; margin-right:8px;'>#$bid</span> 
                            <span style='font-size:17px; font-weight:700; color:#1C1C1E;'>{$b['Product_Type']}</span>
                            <div style='font-size:13px; color:#8E8E93; margin-top:4px;'>Ref #{$b['Order_ID']} • {$b['Quantity']} Units</div>
                        </div>
                        <div style='display:flex; gap:10px;'>
                            <button onclick='showDetails($b_json)' style='background:none; border:none; cursor:pointer; color:#007AFF;'><i class='fas fa-info-circle'></i></button>
                            <form method='post' onsubmit=\"return confirm('Delete?');\" style='margin:0;'>
                                <input type='hidden' name='action' value='delete_batch'>
                                <input type='hidden' name='batch_id' value='$bid'>
                                <button style='background:none; border:none; cursor:pointer; color:#FF3B30;'><i class='fas fa-trash'></i></button>
                            </form>
                        </div>
                    </div>

                    <div class='progress-bar-track'>
                        <div class='progress-fill' style='width: $pct%;'></div>
                    </div>";

                // === SEQUENTIAL LOGIC ===
                $is_unlocked = true; 

                foreach($s_data as $s) {
                    $sid = $s['Stage_ID'];
                    $sname = $s['Stage_Name'];
                    
                    if($s['Actual_Time'] > 0) {
                        echo "<div class='stage-row'>
                            <div style='font-weight:600; font-size:14px; color:#1C1C1E;'>$sname</div>
                            <div style='font-size:13px; color:#34C759; font-weight:600;'><i class='fas fa-check-circle'></i> Done ({$s['Actual_Time']}m)</div>
                        </div>";
                    } else {
                        if ($is_unlocked) {
                            // NEXT STAGE (Active)
                            $role = ($sname=='Cutting')?'Cutter':(($sname=='Finishing')?'Finisher':'Sewer');
                            $locked_wid = (isset($_GET['w_lock']) && isset($_GET['s_lock']) && $_GET['s_lock'] == $sname && $_GET['bid'] == $bid) ? $_GET['w_lock'] : null;

                            // Worker Priority
                            $ws = $conn->query("SELECT * FROM Worker WHERE Role='$role' AND Availability='Available' AND Status='Active' ORDER BY Name ASC");
                            $mode = "Primary";
                            if ($ws->num_rows == 0) {
                                $ws = $conn->query("SELECT * FROM Worker WHERE Secondary_Role='$role' AND Availability='Available' AND Status='Active' ORDER BY Name ASC");
                                $mode = "Backup";
                            }
                            if ($ws->num_rows == 0) {
                                $ws = $conn->query("SELECT * FROM Worker WHERE Status='Active' ORDER BY Availability ASC, Name ASC");
                                $mode = "All";
                            }

                            $w_opts = "";
                            while($w = $ws->fetch_assoc()) {
                                $lbl = $w['Name'];
                                $dis = "";
                                $is_selected = ($locked_wid && $w['Worker_ID'] == $locked_wid) ? "selected" : "";
                                
                                if ($mode == "Backup") $lbl .= " (Backup)";
                                if ($mode == "All") {
                                    $lbl .= " (" . $w['Availability'] . ")";
                                    if($w['Availability'] != 'Available' && !$is_selected) $dis = "disabled";
                                }
                                $w_opts .= "<option value='{$w['Worker_ID']}' $dis $is_selected>$lbl</option>";
                            }
                            
                            $dd_id = "dd_$sid";

                            echo "<div class='stage-row'>
                                <div style='font-weight:600; font-size:14px;'>$sname</div>
                                <div style='font-size:12px; color:#8E8E93;'>{$s['Target_Time']}m Target</div>
                                <div style='display:flex; align-items:center; gap:8px;'>
                                    <form method='post' style='display:flex; gap:5px; margin:0;'>
                                        <input type='hidden' name='action' value='complete_stage'>
                                        <input type='hidden' name='stage_id' value='$sid'>
                                        <select name='worker_id' id='$dd_id' class='worker-select' required>
                                            <option value='' disabled selected>Assign...</option>$w_opts
                                        </select>
                                        <input type='number' name='mins' class='worker-select' style='width:50px; text-align:center;' placeholder='Min'>
                                        <button class='btn-small'>Save</button>
                                    </form>
                                    <button onclick=\"flagDefect($bid, '$sname', '$dd_id')\" class='btn-flag'><i class='fas fa-flag'></i></button>
                                </div>
                            </div>";

                            $is_unlocked = false; // Lock next stages

                        } else {
                            // LOCKED STAGE
                            echo "<div class='stage-row' style='opacity:0.4;'>
                                <div style='font-weight:600; font-size:14px;'>$sname</div>
                                <div style='font-size:12px; color:#8E8E93;'>Pending Previous</div>
                                <div style='font-size:12px; font-weight:600; color:#8E8E93;'><i class='fas fa-lock'></i> Locked</div>
                            </div>";
                        }
                    }
                }
                echo "</div>";
            }
        } else {
            echo "<div style='text-align:center; padding:50px; color:#8E8E93; font-style:italic;'>No active batches found.</div>";
        }
        ?>
    </div>
</div>

<script>
    <?php echo $swal_script; ?>

    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('rework_alert')) {
        Swal.fire({
            icon: 'warning',
            title: 'Rework Flagged!',
            text: 'Defect logged. Worker locked for rework.',
            showCancelButton: true,
            confirmButtonText: 'Continue',
            cancelButtonText: 'Pause',
            confirmButtonColor: '#34C759',
            cancelButtonColor: '#FF3B30'
        });
    }

    function showDetails(batch) {
        Swal.fire({
            title: 'Batch Details',
            html: `<div style="text-align:left;"><b>Product:</b> ${batch.Product_Type}<br><b>Started:</b> ${batch.Start_Time}<br><b>Status:</b> ${batch.Status}</div>`,
            confirmButtonColor: '#007AFF'
        });
    }

    function flagDefect(bid, stage, dd_id) {
        const worker = document.getElementById(dd_id).value;
        if(!worker) {
            Swal.fire({icon:'warning', text:'Select a worker first!'});
            return;
        }
        Swal.fire({
            title: 'Report Defect',
            html: `
                <select id="swal-type" class="ios-input"><option>Wrong Pattern</option><option>Stain</option><option>Stitching Error</option></select>
                <input id="swal-qty" type="number" placeholder="Qty" class="ios-input">
                <select id="swal-act" class="ios-input"><option>Rework</option><option>Reject</option></select>
            `,
            showCancelButton: true, confirmButtonText: 'Log Defect'
        }).then((res) => {
            if(res.isConfirmed) {
                const f = document.createElement('form'); f.method = 'POST';
                f.innerHTML = `
                    <input type="hidden" name="action" value="log_defect">
                    <input type="hidden" name="batch_id" value="${bid}">
                    <input type="hidden" name="stage" value="${stage}">
                    <input type="hidden" name="worker" value="${worker}">
                    <input type="hidden" name="type" value="${document.getElementById('swal-type').value}">
                    <input type="hidden" name="qty" value="${document.getElementById('swal-qty').value}">
                    <input type="hidden" name="act" value="${document.getElementById('swal-act').value}">`;
                document.body.append(f); f.submit();
            }
        });
    }
</script>

<?php include 'layout_bottom.php'; ?>