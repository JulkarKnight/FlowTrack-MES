<?php 
include 'layout_top.php'; 

// --- 1. DATA GATHERING FOR CHART ---
// Get last 7 days of production data
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D', strtotime($date)); // Mon, Tue...
    
    // Sum quantity from Finished_Goods for this date
    $sql = "SELECT SUM(Quantity) as total FROM Finished_Goods WHERE Completed_Date = '$date'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    $chart_data[] = $row['total'] ?? 0;
}
?>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.85);
        --glass-border: 1px solid rgba(255, 255, 255, 0.5);
        --shadow-soft: 0 15px 35px rgba(0,0,0,0.1);
        --primary-gradient: linear-gradient(135deg, #007AFF, #0055B3);
        --success-gradient: linear-gradient(135deg, #34C759, #248A3D);
        --orange-gradient: linear-gradient(135deg, #FF9500, #E68600);
        --purple-gradient: linear-gradient(135deg, #AF52DE, #8E44AD);
    }

    body { background-color: #F2F2F7; perspective: 1000px; }

    /* === MACOS POP-UP ANIMATION === */
    @keyframes macos-pop {
        0% { 
            opacity: 0;
            transform: scale(0.92) translateY(15px);
            filter: blur(8px);
        }
        100% { 
            opacity: 1;
            transform: scale(1) translateY(0);
            filter: blur(0px);
        }
    }

    /* Staggered Classes */
    .pop-in { 
        opacity: 0; /* Start hidden */
        animation: macos-pop 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; 
    }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }

    /* CARDS */
    .d-card {
        background: var(--glass-bg);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border-radius: 22px;
        padding: 25px;
        box-shadow: var(--shadow-soft);
        border: var(--glass-border);
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s ease;
        display: block; /* For anchor tags */
        text-decoration: none;
    }
    .d-card:hover { 
        transform: translateY(-6px) scale(1.01); 
        box-shadow: 0 20px 40px rgba(0,0,0,0.12);
    }

    /* KPI ICONS */
    .kpi-icon {
        width: 48px; height: 48px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; color: white; margin-bottom: 15px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }

    /* ACTION BUTTONS */
    .action-btn {
        display: flex; align-items: center; padding: 15px;
        border-radius: 18px; background: white; text-decoration: none;
        color: #1D1D1F; transition: 0.2s; border: 1px solid rgba(0,0,0,0.05);
    }
    .action-btn:hover { background: #F2F2F7; transform: scale(1.03); }
    .ab-icon {
        width: 40px; height: 40px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        color: white; margin-right: 15px; font-size: 18px;
    }

    /* PULSE DOT */
    @keyframes pulse-ring {
        0% { transform: scale(0.8); opacity: 0.5; }
        100% { transform: scale(2.2); opacity: 0; }
    }
    .status-dot {
        width: 10px; height: 10px; background: #34C759; border-radius: 50%;
        position: relative;
    }
    .status-dot::before {
        content: ''; position: absolute; left: -5px; top: -5px;
        width: 20px; height: 20px; border-radius: 50%;
        background: rgba(52, 199, 89, 0.4);
        animation: pulse-ring 2s infinite;
    }
</style>

<div style="display:flex; justify-content:space-between; align-items:end; margin-bottom:30px;" class="pop-in">
    <div>
        <div style="font-size:13px; font-weight:700; color:#8E8E93; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Factory Overview</div>
        <h1 style="margin:0; font-size:36px; letter-spacing:-1px;">
            Good <?php echo (date('H') < 12) ? 'Morning' : ((date('H') < 18) ? 'Afternoon' : 'Evening'); ?>, <span style="background: -webkit-linear-gradient(45deg, #007AFF, #5856D6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Manager</span>
        </h1>
    </div>
    <div style="background:white; padding:8px 16px; border-radius:20px; display:flex; align-items:center; gap:10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <div class="status-dot"></div>
        <span style="font-size:13px; font-weight:600; color:#1D1D1F;">Systems Operational</span>
    </div>
</div>

<div class="grid-container pop-in delay-1" style="grid-template-columns: repeat(4, 1fr); gap:20px; margin-bottom:30px;">
    <?php
    $active = $conn->query("SELECT COUNT(*) as c FROM Batch WHERE Status='Running'")->fetch_assoc()['c'];
    $low_stock = $conn->query("SELECT COUNT(*) as c FROM Material WHERE Current_Stock < 500")->fetch_assoc()['c'];
    $today_q = date('Y-m-d');
    $done_today = $conn->query("SELECT COUNT(*) as c FROM Finished_Goods WHERE Completed_Date = '$today_q'")->fetch_assoc()['c'];
    $total_qty = $conn->query("SELECT SUM(Quantity) as s FROM Batch")->fetch_assoc()['s'];
    ?>

    <a href="batches.php" class="d-card">
        <div class="kpi-icon" style="background: var(--primary-gradient);"><i class="fas fa-industry"></i></div>
        <div style="font-size:32px; font-weight:800; color:#1D1D1F;"><?php echo $active; ?></div>
        <div style="font-size:13px; color:#8E8E93; font-weight:600;">Active Lines</div>
    </a>

    <a href="inventory.php" class="d-card">
        <div class="kpi-icon" style="background: <?php echo ($low_stock>0)?'var(--orange-gradient)':'var(--success-gradient)'; ?>;">
            <i class="fas fa-boxes"></i>
        </div>
        <div style="font-size:32px; font-weight:800; color:#1D1D1F;"><?php echo $low_stock; ?></div>
        <div style="font-size:13px; color:#8E8E93; font-weight:600;">Low Stock Items</div>
    </a>

    <a href="finished_goods.php?filter=today" class="d-card">
        <div class="kpi-icon" style="background: var(--success-gradient);"><i class="fas fa-check-circle"></i></div>
        <div style="font-size:32px; font-weight:800; color:#1D1D1F;"><?php echo $done_today; ?></div>
        <div style="font-size:13px; color:#8E8E93; font-weight:600;">Batches Finished Today</div>
    </a>

    <a href="finished_goods.php" class="d-card">
        <div class="kpi-icon" style="background: var(--purple-gradient);"><i class="fas fa-chart-line"></i></div>
        <div style="font-size:32px; font-weight:800; color:#1D1D1F;"><?php echo number_format($total_qty); ?></div>
        <div style="font-size:13px; color:#8E8E93; font-weight:600;">Total Lifetime Units</div>
    </a>
</div>

<div class="grid-container pop-in delay-2" style="grid-template-columns: 2fr 1fr; gap:30px; margin-bottom:30px;">
    
    <div class="d-card" style="cursor: default;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0; font-size:18px;">Weekly Production Output</h3>
            <span style="font-size:12px; color:#8E8E93; background:#F2F2F7; padding:4px 10px; border-radius:10px;">Last 7 Days</span>
        </div>
        <div style="height:250px; width:100%;">
            <canvas id="productionChart"></canvas>
        </div>
    </div>

    <div class="d-card" style="display:flex; flex-direction:column; justify-content:center; gap:15px; cursor: default;">
        <h3 style="margin:0 0 10px 0; font-size:18px;">Quick Actions</h3>
        
        <a href="batches.php" class="action-btn">
            <div class="ab-icon" style="background: #007AFF;"><i class="fas fa-plus"></i></div>
            <div>
                <div style="font-weight:700; font-size:14px;">New Batch</div>
                <div style="font-size:11px; color:#8E8E93;">Start Production</div>
            </div>
        </a>

        <a href="inventory.php" class="action-btn">
            <div class="ab-icon" style="background: #34C759;"><i class="fas fa-box-open"></i></div>
            <div>
                <div style="font-weight:700; font-size:14px;">Restock</div>
                <div style="font-size:11px; color:#8E8E93;">Add Materials</div>
            </div>
        </a>

        <a href="qc.php" class="action-btn">
            <div class="ab-icon" style="background: #FF9500;"><i class="fas fa-microscope"></i></div>
            <div>
                <div style="font-weight:700; font-size:14px;">QC Check</div>
                <div style="font-size:11px; color:#8E8E93;">Inspect Goods</div>
            </div>
        </a>
    </div>
</div>

<div class="d-card pop-in delay-3" style="cursor: default;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h3 style="margin:0; font-size:18px;">Active Production Floor</h3>
        <a href="batches.php" style="font-size:13px; font-weight:600; color:#007AFF; text-decoration:none;">View All &rarr;</a>
    </div>

    <?php
    $running = $conn->query("SELECT * FROM Batch WHERE Status='Running' ORDER BY Start_Time DESC LIMIT 3");
    if($running->num_rows > 0) {
        while($row = $running->fetch_assoc()) {
            $pid = $row['Batch_ID'];
            // Calc Progress
            $stages_done = $conn->query("SELECT COUNT(*) as c FROM Production_Stage WHERE Batch_ID=$pid AND Actual_Time > 0")->fetch_assoc()['c'];
            $pct = ($stages_done / 3) * 100; 
            
            echo "<div style='margin-bottom:20px;'>
                <div style='display:flex; justify-content:space-between; margin-bottom:8px; align-items:center;'>
                    <div style='display:flex; align-items:center; gap:10px;'>
                        <span style='background:#E0F2FE; color:#007AFF; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;'>#{$pid}</span>
                        <span style='font-weight:600; font-size:14px;'>{$row['Product_Type']}</span>
                    </div>
                    <div style='font-size:12px; color:#8E8E93; font-weight:600;'>{$row['Quantity']} Units</div>
                </div>
                <div style='height:8px; background:#F2F2F7; border-radius:10px; overflow:hidden;'>
                    <div style='height:100%; width:{$pct}%; background:var(--primary-gradient); border-radius:10px; transition: width 1s ease-in-out;'></div>
                </div>
            </div>";
        }
    } else {
        echo "<div style='text-align:center; padding:30px; color:#AEAEB2;'>Production floor is currently quiet.</div>";
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('productionChart').getContext('2d');
    
    // Gradient for the chart area
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(0, 122, 255, 0.3)');
    gradient.addColorStop(1, 'rgba(0, 122, 255, 0.0)');

    const productionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Finished Goods',
                data: <?php echo json_encode($chart_data); ?>,
                backgroundColor: gradient,
                borderColor: '#007AFF',
                borderWidth: 3,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#007AFF',
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4 // Smooth curves
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#F2F2F7', borderDash: [5, 5] },
                    ticks: { color: '#8E8E93', font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#8E8E93', font: { size: 11 } }
                }
            }
        }
    });
</script>

<?php include 'layout_bottom.php'; ?>