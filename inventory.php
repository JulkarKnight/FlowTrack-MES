<?php include 'layout_top.php'; 

$message = "";

// --- HANDLER: RESTOCK MATERIAL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['restock_material'])) {
    $m_id = $_POST['material_id'];
    $amount = $_POST['amount_added'];
    
    $stmt = $conn->prepare("UPDATE Material SET Current_Stock = Current_Stock + ? WHERE Material_ID = ?");
    $stmt->bind_param("di", $amount, $m_id);
    
    if ($stmt->execute()) {
        $message = "<div class='alert' style='background:rgba(52,199,89,0.15); color:var(--success-green); border:1px solid rgba(52,199,89,0.3);'>
            ✅ <b>Stock Updated</b><br>Added $amount units successfully.
        </div>";
    }
}
?>

<h1>Inventory Management</h1>

<?php if($message) echo $message; ?>

<div class="grid-container">
    
    <div class="card" style="grid-column: span 3;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h3><i class="fas fa-truck-loading"></i> Incoming Shipment</h3>
                <p style="font-size:13px; color:var(--text-secondary); margin:0;">
                    Scan or log new materials entering the warehouse.
                </p>
            </div>
            
            <form method="post" style="display:flex; gap:15px; align-items:flex-end; width:60%;">
                <input type="hidden" name="restock_material" value="1">
                
                <div style="flex:2;">
                    <label style="margin-bottom:5px; display:block; font-size:11px;">Select Item</label>
                    <select name="material_id" required style="margin:0; padding:10px;">
                        <option value="" disabled selected>Choose Material...</option>
                        <optgroup label="Primary Fabrics">
                            <?php
                            $fabs = $conn->query("SELECT * FROM Material WHERE Category='Fabric'");
                            while($f = $fabs->fetch_assoc()) echo "<option value='{$f['Material_ID']}'>{$f['Material_Name']}</option>";
                            ?>
                        </optgroup>
                        <optgroup label="Trims & Accessories">
                            <?php
                            $trims = $conn->query("SELECT * FROM Material WHERE Category='Trim'");
                            while($t = $trims->fetch_assoc()) echo "<option value='{$t['Material_ID']}'>{$t['Material_Name']}</option>";
                            ?>
                        </optgroup>
                    </select>
                </div>
                
                <div style="flex:1;">
                    <label style="margin-bottom:5px; display:block; font-size:11px;">Qty Received</label>
                    <input type="number" step="0.01" name="amount_added" required placeholder="0.00" style="margin:0; padding:10px;">
                </div>
                
                <button type="submit" class="btn-primary" style="margin:0; height:46px; padding:0 25px;">Update Stock</button>
            </form>
        </div>
    </div>

    <div class="card" style="grid-column: span 1.5;">
        <div style="display:flex; align-items:center; margin-bottom:20px; border-bottom:1px solid rgba(0,0,0,0.05); padding-bottom:15px;">
            <div style="background:rgba(0,122,255,0.1); padding:10px; border-radius:12px; margin-right:15px;">
                <i class="fas fa-scroll" style="color:var(--primary-blue); font-size:20px;"></i>
            </div>
            <div>
                <h3 style="margin:0; font-size:18px;">Primary Fabrics</h3>
                <span style="font-size:12px; color:var(--text-secondary);">Rolls, Yards, and Meters</span>
            </div>
        </div>

        <table style="margin:0;">
            <thead>
                <tr style="font-size:11px; color:var(--text-secondary);">
                    <th>Material Name</th>
                    <th>Stock Level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT * FROM Material WHERE Category='Fabric'");
                if($res->num_rows > 0) {
                    while($row = $res->fetch_assoc()) {
                        $stock = $row['Current_Stock'];
                        // Visual Logic for Stock Status
                        $color = 'var(--text-primary)';
                        $bg = 'transparent';
                        if($stock < 500) { $color = 'var(--danger-red)'; $bg = 'rgba(255,59,48,0.05)'; }
                        
                        echo "<tr style='background:$bg;'>
                            <td style='font-weight:600;'>{$row['Material_Name']}</td>
                            <td>
                                <div style='font-size:16px; font-weight:700; color:$color;'>".number_format($stock)."</div>
                                <div style='font-size:10px; color:var(--text-secondary);'>{$row['Unit']}</div>
                            </td>
                            <td>";
                            if($stock < 500) echo "<span class='badge' style='background:var(--danger-red); color:white;'>Low</span>";
                            else echo "<span class='badge' style='background:#E5E5EA; color:var(--text-secondary);'>OK</span>";
                        echo "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center; padding:20px; color:#999;'>No fabrics found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="grid-column: span 1.5;">
        <div style="display:flex; align-items:center; margin-bottom:20px; border-bottom:1px solid rgba(0,0,0,0.05); padding-bottom:15px;">
            <div style="background:rgba(255,149,0,0.1); padding:10px; border-radius:12px; margin-right:15px;">
                <i class="fas fa-puzzle-piece" style="color:#FF9500; font-size:20px;"></i>
            </div>
            <div>
                <h3 style="margin:0; font-size:18px;">Trims & Accessories</h3>
                <span style="font-size:12px; color:var(--text-secondary);">Buttons, Zippers, Threads</span>
            </div>
        </div>

        <table style="margin:0;">
            <thead>
                <tr style="font-size:11px; color:var(--text-secondary);">
                    <th>Item Name</th>
                    <th>Stock Level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT * FROM Material WHERE Category='Trim'");
                if($res->num_rows > 0) {
                    while($row = $res->fetch_assoc()) {
                        $stock = $row['Current_Stock'];
                        $color = 'var(--text-primary)';
                        if($stock < 1000) { $color = '#FF9500'; } // Orange for trims

                        echo "<tr>
                            <td style='font-weight:600;'>{$row['Material_Name']}</td>
                            <td>
                                <div style='font-size:16px; font-weight:700; color:$color;'>".number_format($stock)."</div>
                                <div style='font-size:10px; color:var(--text-secondary);'>{$row['Unit']}</div>
                            </td>
                            <td>";
                            if($stock < 1000) echo "<span class='badge' style='background:#FF9500; color:white;'>Reorder</span>";
                            else echo "<span class='badge' style='background:#E5E5EA; color:var(--text-secondary);'>OK</span>";
                        echo "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center; padding:20px; color:#999;'>No trims found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>