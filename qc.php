<?php 
include 'layout_top.php'; 

// --- DB CONNECTION ---
$conn = @new mysqli("127.0.0.1", "root", "", "flowtrack_mes", 3306);
if ($conn->connect_error) { $conn = new mysqli("localhost", "root", "", "flowtrack_mes"); }

$swal_script = "";

// --- HANDLER: SUBMIT FINAL QC ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_qc'])) {
    $bid = $_POST['batch_id'];
    $passed = $_POST['qty_passed'];
    $failed = $_POST['qty_failed'];
    
    // 1. SECURITY CHECK: Are there unresolved defects?
    $check_issues = $conn->query("SELECT COUNT(*) as cnt FROM Defect_Log WHERE Batch_ID = $bid AND (Status IS NULL OR Status NOT IN ('Fixed', 'Scrapped'))");
    $issue_count = $check_issues->fetch_assoc()['cnt'];

    if ($issue_count > 0) {
        // BLOCK ACTION
        $swal_script = "Swal.fire({
            icon: 'error',
            title: 'Grading Blocked',
            html: 'This batch has <b>$issue_count unresolved defects</b>.<br>Please visit the Repair Bay first.',
            footer: '<a href=\"rework.php\">Go to Repair Bay</a>'
        });";
    } else {
        // 2. PROCEED WITH GRADING (AQL)
        $total = $passed + $failed;
        $fail_rate = ($total > 0) ? ($failed / $total) * 100 : 0;
        
        $grade = 'A'; 
        if ($fail_rate > 2.5) $grade = 'B';
        if ($fail_rate > 5.0) $grade = 'C'; 
        
        $today = date("Y-m-d");
        
        // Use Prepared Statement
        $stmt = $conn->prepare("INSERT INTO Finished_Goods (Batch_ID, Quantity, Grade, Completed_Date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $bid, $passed, $grade, $today);
        
        if ($stmt->execute()) {
            // Update Batch Status
            $conn->query("UPDATE Batch SET Status='Completed', Quality_Grade='$grade' WHERE Batch_ID=$bid");
            
            $swal_script = "Swal.fire({
                icon: 'success',
                title: 'QC Completed',
                html: 'Batch #$bid graded as <b style=\"font-size:20px\">$grade</b>',
                confirmButtonColor: '#34C759'
            });";
        } else {
            $swal_script = "Swal.fire({icon: 'error', title: 'Error', text: 'Database error occurred.'});";
        }
    }
}
?>

<style>
    :root { --bg: #F5F5F7; --card: #FFFFFF; --text: #1D1D1F; --blue: #007AFF; --green: #34C759; --orange: #F59E0B; --red: #FF3B30; }
    body { background-color: var(--bg); font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif; }
    
    .page-title { font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 25px; }
    
    .mac-card { background: var(--card); border-radius: 20px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); }
    
    .std-input, .std-select { width: 100%; padding: 12px; border: 1px solid #d2d2d7; border-radius: 10px; font-size: 14px; margin-bottom: 15px; box-sizing: border-box; transition:0.2s; }
    .std-input:focus, .std-select:focus { border-color: var(--blue); outline: none; box-shadow: 0 0 0 3px rgba(0,122,255,0.1); }
    
    .btn-primary { width: 100%; background: var(--blue); color: white; border: none; padding: 14px; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition:0.2s; }
    .btn-primary:hover { opacity: 0.9; transform: scale(1.01); }

    .insight-box { background: #FAFAFA; border: 1px dashed #D2D2D7; border-radius: 12px; padding: 20px; text-align: center; color: #86868B; min-height: 150px; display:flex; align-items:center; justify-content:center; flex-direction:column; }
    
    .mac-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 20px; }
    .mac-table th { text-align: left; font-size: 11px; text-transform: uppercase; color: #86868B; padding: 10px 15px; border-bottom: 1px solid #eee; }
    .mac-table td { padding: 15px; border-bottom: 1px solid #f5f5f7; font-size: 14px; }
    
    .badge-grade { padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 12px; }
</style>

<div class="container" style="max-width:1100px; margin:0 auto; padding:20px;">
    
    <h1 class="page-title">Final Quality Inspection (AQL)</h1>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px; align-items:start;">
        
        <div class="mac-card">
            <h3 style="margin-top:0; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-clipboard-check" style="color:var(--blue);"></i> New Inspection
            </h3>
            <p style="color:#86868B; font-size:13px; margin-bottom:20px;">Select a running batch to calculate final grade.</p>

            <form method="post">
                <input type="hidden" name="submit_qc" value="1">
                
                <label style="font-size:12px; font-weight:600; color:#6e6e73;">SELECT BATCH</label>
                <select name="batch_id" id="batch_select" class="std-select" onchange="fetchBatchInsights()" required>
                    <option value="" disabled selected>Choose a batch...</option>
                    <?php
                    $b_res = $conn->query("SELECT * FROM Batch WHERE Status='Running' ORDER BY Batch_ID DESC");
                    while($b = $b_res->fetch_assoc()) {
                        echo "<option value='{$b['Batch_ID']}'>#{$b['Batch_ID']} - {$b['Product_Type']} ({$b['Quantity']} pcs)</option>";
                    }
                    ?>
                </select>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:var(--green);">PASSED QTY</label>
                        <input type="number" name="qty_passed" class="std-input" placeholder="0" required>
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:var(--red);">REJECT/FAIL QTY</label>
                        <input type="number" name="qty_failed" class="std-input" placeholder="0" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Submit & Grade Batch</button>
            </form>
        </div>

        <div class="mac-card" style="background: linear-gradient(180deg, #FFFFFF 0%, #F9F9F9 100%);">
            <h3 style="margin-top:0; color:#1d1d1f;">Batch Analysis</h3>
            <div id="insight_box" class="insight_box">
                <i class="fas fa-search" style="font-size:24px; margin-bottom:10px; opacity:0.3;"></i>
                <span>Select a batch on the left to analyze defect history.</span>
            </div>
        </div>

    </div>

    <div style="margin-top:40px;">
        <h3 style="margin-bottom:15px; color:#1d1d1f;">Recent Completed Inspections</h3>
        <div class="mac-card" style="padding:0; overflow:hidden;">
            <table class="mac-table">
                <thead>
                    <tr><th>Date</th><th>Batch</th><th>Product</th><th>Grade</th><th>Packed Qty</th></tr>
                </thead>
                <tbody>
                    <?php
                    $hist = $conn->query("SELECT f.*, b.Product_Type FROM Finished_Goods f JOIN Batch b ON f.Batch_ID = b.Batch_ID ORDER BY f.Completed_Date DESC LIMIT 5");
                    
                    if ($hist && $hist->num_rows > 0) {
                        while($h = $hist->fetch_assoc()) {
                            $g_bg = ($h['Grade']=='A')?'#E4FCE8':(($h['Grade']=='B')?'#FFF7ED':'#FEF2F2');
                            $g_col = ($h['Grade']=='A')?'#10B981':(($h['Grade']=='B')?'#F59E0B':'#EF4444');
                            
                            echo "<tr>
                                <td><span style='color:#86868B;'>{$h['Completed_Date']}</span></td>
                                <td><b>#{$h['Batch_ID']}</b></td>
                                <td>{$h['Product_Type']}</td>
                                <td><span class='badge-grade' style='background:$g_bg; color:$g_col;'>Grade {$h['Grade']}</span></td>
                                <td style='font-weight:600;'>{$h['Quantity']} pcs</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#ccc;'>No finished goods yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    const defectHistory = {
        <?php
        $i_res = $conn->query("SELECT Batch_ID, Stage_Name, Defect_Type, SUM(Quantity) as Qty FROM Defect_Log GROUP BY Batch_ID, Defect_Type");
        $data = [];
        if($i_res) {
            while($r = $i_res->fetch_assoc()) {
                $bid = $r['Batch_ID'];
                if(!isset($data[$bid])) $data[$bid] = [];
                $data[$bid][] = "<div style='display:flex; justify-content:space-between; border-bottom:1px solid #eee; padding:5px 0;'><span style='color:#333;'>{$r['Defect_Type']}</span> <b style='color:var(--red);'>{$r['Qty']}</b></div>";
            }
        }
        foreach($data as $bid => $items) echo "'$bid': `<div style='text-align:left; width:100%;'>" . implode("", $items) . "</div>`,";
        ?>
    };

    function fetchBatchInsights() {
        const batchId = document.getElementById('batch_select').value;
        const box = document.getElementById('insight_box');
        
        // Remove previous styles
        box.className = 'insight-box';
        box.style = '';

        if(defectHistory[batchId]) {
            box.style.background = '#FFF5F5';
            box.style.border = '1px solid #FEB2B2';
            box.style.color = '#1d1d1f';
            box.style.alignItems = 'flex-start';
            box.style.justifyContent = 'flex-start';
            box.innerHTML = `
                <div style="width:100%; display:flex; align-items:center; gap:8px; margin-bottom:10px; color:#C53030;">
                    <i class="fas fa-exclamation-circle"></i> <b>Previous Defects Found</b>
                </div>
                ${defectHistory[batchId]}
                <div style="margin-top:15px; font-size:12px; color:#666; width:100%; text-align:left;">
                    <i class="fas fa-info-circle"></i> Ensure these are resolved in Repair Bay before grading.
                </div>
            `;
        } else {
            box.style.background = '#F0FFF4';
            box.style.border = '1px solid #9AE6B4';
            box.innerHTML = `
                <i class="fas fa-check-circle" style="font-size:40px; color:#38A169; margin-bottom:15px;"></i>
                <h4 style="margin:0; color:#2F855A;">Clean History</h4>
                <p style="font-size:13px; color:#48BB78;">No defects reported during production.</p>
            `;
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php echo $swal_script; ?>
</script>

<?php include 'layout_bottom.php'; ?>