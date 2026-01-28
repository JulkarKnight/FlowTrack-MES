<?php 
include 'layout_top.php'; 

// --- DATABASE CONNECT ---
$conn = @new mysqli("127.0.0.1", "root", "", "flowtrack_mes", 3306);
if ($conn->connect_error) { $conn = new mysqli("localhost", "root", "", "flowtrack_mes"); }

// --- SWEETALERT TRIGGER ---
$swal_script = "";

// --- HANDLERS ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. ASSIGN WORKER
    if (isset($_POST['assign_worker'])) {
        $did = $_POST['defect_id'];
        $wid = $_POST['worker_id'];
        if($wid) {
            $conn->query("UPDATE Defect_Log SET Assigned_To_Worker_ID = $wid WHERE Defect_ID = $did");
            $swal_script = "Swal.fire({icon:'success', title:'Assigned', text:'Worker assigned to repair.', timer:1500, showConfirmButton:false});";
        }
    }

    // 2. FIX REWORK
    if (isset($_POST['action']) && $_POST['action'] == 'fix_item') {
        $did = $_POST['defect_id'];
        $conn->query("UPDATE Defect_Log SET Status = 'Fixed' WHERE Defect_ID = $did");
        $swal_script = "Swal.fire({icon:'success', title:'Repaired', text:'Item marked as Fixed.', timer:1500, showConfirmButton:false});";
    }

    // 3. CONFIRM SCRAP
    if (isset($_POST['action']) && $_POST['action'] == 'scrap_item') {
        $did = $_POST['defect_id'];
        $conn->query("UPDATE Defect_Log SET Status = 'Scrapped' WHERE Defect_ID = $did");
        $swal_script = "Swal.fire({icon:'warning', title:'Scrapped', text:'Item moved to waste.', timer:1500, showConfirmButton:false});";
    }
}
?>

<style>
    :root { --bg: #F5F5F7; --card: #FFFFFF; --text: #1D1D1F; --blue: #007AFF; --green: #34C759; --orange: #F59E0B; --red: #FF3B30; }
    body { background-color: var(--bg); font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif; }
    
    .page-title { font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 20px; }
    
    /* CARDS */
    .mac-card { background: var(--card); border-radius: 18px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); margin-bottom: 25px; }
    .card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .card-title { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }

    /* TABLES */
    .mac-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .mac-table th { text-align: left; font-size: 11px; text-transform: uppercase; color: #86868B; padding: 10px; border-bottom: 1px solid #eee; }
    .mac-table td { padding: 15px 10px; border-bottom: 1px solid #f5f5f7; vertical-align: middle; font-size: 13px; }
    .mac-table tr:last-child td { border-bottom: none; }

    /* BADGES & BUTTONS */
    .badge { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .bg-orange { background: #FFF7ED; color: #C2410C; }
    .bg-red { background: #FEF2F2; color: #991B1B; }

    .btn-action { border: none; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-fix { background: var(--green); color: white; }
    .btn-scrap { background: var(--red); color: white; }
    .btn-assign { background: none; border: 1px solid #ddd; color: var(--blue); padding: 5px 10px; }
    
    .std-select { padding: 6px; border-radius: 6px; border: 1px solid #ddd; font-size: 12px; }
</style>

<div class="container" style="max-width:1200px; margin:0 auto; padding:20px;">
    
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1 class="page-title">Repair Bay & Scrap Yard</h1>
        
        <div style="display:flex; gap:15px;">
            <?php 
            $c_fix = $conn->query("SELECT COUNT(*) as c FROM Defect_Log WHERE Action_Taken='Rework' AND (Status IS NULL OR Status != 'Fixed')")->fetch_assoc()['c'];
            $c_scr = $conn->query("SELECT COUNT(*) as c FROM Defect_Log WHERE Action_Taken='Reject' AND (Status IS NULL OR Status != 'Scrapped')")->fetch_assoc()['c'];
            ?>
            <div style="background:white; padding:10px 20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                <span style="font-size:12px; color:#888;">To Fix</span>
                <div style="font-size:20px; font-weight:800; color:var(--orange);"><?php echo $c_fix; ?></div>
            </div>
            <div style="background:white; padding:10px 20px; border-radius:12px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                <span style="font-size:12px; color:#888;">To Scrap</span>
                <div style="font-size:20px; font-weight:800; color:var(--red);"><?php echo $c_scr; ?></div>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:25px;">
        
        <div class="mac-card" style="border-top: 4px solid var(--orange);">
            <div class="card-head">
                <div class="card-title" style="color:var(--orange);"><i class="fas fa-tools"></i> Pending Reworks</div>
            </div>

            <?php
            $sql_rework = "SELECT d.*, b.Product_Type, w.Name as WorkerName 
                           FROM Defect_Log d 
                           JOIN Batch b ON d.Batch_ID = b.Batch_ID 
                           LEFT JOIN Worker w ON d.Assigned_To_Worker_ID = w.Worker_ID
                           WHERE d.Action_Taken = 'Rework' AND (d.Status IS NULL OR d.Status != 'Fixed')
                           ORDER BY d.Log_Date DESC";
            $res_rework = $conn->query($sql_rework);

            if ($res_rework->num_rows > 0) {
                echo "<table class='mac-table'>
                        <thead><tr><th>Batch</th><th>Defect</th><th>Assigned To</th><th>Action</th></tr></thead>
                        <tbody>";
                while($row = $res_rework->fetch_assoc()) {
                    echo "<tr>
                        <td><b>#{$row['Batch_ID']}</b><br><span style='color:#888; font-size:11px;'>{$row['Product_Type']}</span></td>
                        <td><span class='badge bg-orange'>{$row['Defect_Type']}</span><br><span style='font-size:11px;'>Qty: {$row['Quantity']}</span></td>
                        <td>";
                            
                        // ASSIGNMENT LOGIC
                        if($row['WorkerName']) {
                            echo "<div style='display:flex; align-items:center; gap:5px; font-weight:600; color:#333;'>
                                    <div style='width:24px; height:24px; background:#eee; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px;'>".substr($row['WorkerName'],0,1)."</div>
                                    {$row['WorkerName']}
                                  </div>";
                        } else {
                            echo "<form method='post' style='display:flex; gap:5px;'>
                                    <input type='hidden' name='assign_worker' value='1'>
                                    <input type='hidden' name='defect_id' value='{$row['Defect_ID']}'>
                                    <select name='worker_id' class='std-select' required>
                                        <option value='' disabled selected>Assign...</option>";
                                        $ws = $conn->query("SELECT * FROM Worker WHERE Status='Active'");
                                        while($w = $ws->fetch_assoc()) echo "<option value='{$w['Worker_ID']}'>{$w['Name']}</option>";
                            echo "  </select>
                                    <button class='btn-assign'><i class='fas fa-check'></i></button>
                                  </form>";
                        }
                    echo "</td>
                        <td>
                            <form method='post'>
                                <input type='hidden' name='action' value='fix_item'>
                                <input type='hidden' name='defect_id' value='{$row['Defect_ID']}'>
                                <button class='btn-action btn-fix'><i class='fas fa-check-double'></i> Fixed</button>
                            </form>
                        </td>
                    </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<div style='text-align:center; padding:30px; color:#aaa;'>No pending repairs.</div>";
            }
            ?>
        </div>

        <div class="mac-card" style="border-top: 4px solid var(--red);">
            <div class="card-head">
                <div class="card-title" style="color:var(--red);"><i class="fas fa-trash-alt"></i> Pending Scraps</div>
            </div>

            <?php
            $sql_scrap = "SELECT d.*, b.Product_Type 
                          FROM Defect_Log d 
                          JOIN Batch b ON d.Batch_ID = b.Batch_ID 
                          WHERE d.Action_Taken = 'Reject' AND (d.Status IS NULL OR d.Status != 'Scrapped')
                          ORDER BY d.Log_Date DESC";
            $res_scrap = $conn->query($sql_scrap);

            if ($res_scrap->num_rows > 0) {
                echo "<table class='mac-table'>
                        <thead><tr><th>Batch</th><th>Defect</th><th>Qty</th><th>Confirm</th></tr></thead>
                        <tbody>";
                while($row = $res_scrap->fetch_assoc()) {
                    echo "<tr>
                        <td><b>#{$row['Batch_ID']}</b><br><span style='color:#888; font-size:11px;'>{$row['Product_Type']}</span></td>
                        <td><span class='badge bg-red'>{$row['Defect_Type']}</span></td>
                        <td style='font-weight:bold; font-size:14px;'>{$row['Quantity']}</td>
                        <td>
                            <form method='post' onsubmit=\"return confirm('Permanently scrap this item?');\">
                                <input type='hidden' name='action' value='scrap_item'>
                                <input type='hidden' name='defect_id' value='{$row['Defect_ID']}'>
                                <button class='btn-action btn-scrap'><i class='fas fa-times'></i> Scrap</button>
                            </form>
                        </td>
                    </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<div style='text-align:center; padding:30px; color:#aaa;'>No items to scrap.</div>";
            }
            ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php echo $swal_script; ?>
</script>

<?php include 'layout_bottom.php'; ?>