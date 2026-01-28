<?php 
include 'layout_top.php'; 

// --- DB CONNECT ---
$conn = @new mysqli("127.0.0.1", "root", "", "flowtrack_mes", 3306);
if ($conn->connect_error) { $conn = new mysqli("localhost", "root", "", "flowtrack_mes"); }

// --- FILTER LOGIC ---
$filter_mode = isset($_GET['filter']) && $_GET['filter'] == 'today' ? 'Today' : 'All Time';
$where_clause = "";

if ($filter_mode == 'Today') {
    $today = date('Y-m-d');
    $where_clause = "WHERE f.Completed_Date = '$today'";
}

// --- FETCH DATA (FIXED QUERY) ---
// We changed the ORDER BY to use 'Batch_ID' instead of 'Finished_ID'
$sql = "SELECT f.*, b.Product_Type, b.Order_ID 
        FROM Finished_Goods f 
        JOIN Batch b ON f.Batch_ID = b.Batch_ID 
        $where_clause
        ORDER BY f.Completed_Date DESC, f.Batch_ID DESC";

$result = $conn->query($sql);
?>

<style>
    :root { --bg: #F2F2F7; --card: #FFFFFF; --text: #1C1C1E; --blue: #007AFF; --green: #34C759; }
    body { background-color: var(--bg); font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", sans-serif; }

    /* ANIMATION */
    @keyframes slide-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate { animation: slide-up 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }

    /* HEADER */
    .page-header { display: flex; justify-content: space-between; align-items: end; margin-bottom: 25px; }
    .page-title { font-size: 34px; font-weight: 800; margin: 0; letter-spacing: -1px; }
    
    /* TABLE CARD */
    .ios-table-card {
        background: var(--card); border-radius: 20px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.03); 
        overflow: hidden; border: 1px solid rgba(0,0,0,0.02);
    }

    .ios-table { width: 100%; border-collapse: collapse; }
    .ios-table th { 
        text-align: left; font-size: 11px; font-weight: 600; 
        color: #8E8E93; text-transform: uppercase; padding: 15px 20px; 
        border-bottom: 1px solid #E5E5EA; background: #FAFAFA;
    }
    .ios-table td { padding: 18px 20px; border-bottom: 1px solid #F2F2F7; font-size: 14px; color: var(--text); }
    .ios-table tr:last-child td { border-bottom: none; }
    .ios-table tr:hover { background: #F9F9F9; }

    /* BADGES */
    .grade-badge { 
        padding: 4px 12px; border-radius: 12px; font-weight: 700; font-size: 12px; 
        display: inline-block; min-width: 30px; text-align: center;
    }
    .grade-A { background: #E4FCE8; color: #10B981; }
    .grade-B { background: #FFF7ED; color: #F59E0B; }
    .grade-C { background: #FEF2F2; color: #EF4444; }

    .back-btn {
        text-decoration: none; color: var(--blue); font-weight: 600; font-size: 14px;
        display: flex; align-items: center; gap: 5px; margin-bottom: 5px;
    }
</style>

<div style="max-width:1000px; margin:0 auto; padding:20px;">
    
    <a href="index.php" class="back-btn animate"><i class="fas fa-chevron-left"></i> Dashboard</a>

    <div class="page-header animate">
        <div>
            <h1 class="page-title">Finished Goods</h1>
            <div style="color:#8E8E93; font-weight:500; font-size:14px; margin-top:5px;">
                Showing: <span style="color:#007AFF;"><?php echo $filter_mode; ?></span>
            </div>
        </div>
        <div style="background:white; padding:8px 16px; border-radius:20px; font-weight:600; font-size:13px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <?php echo $result ? $result->num_rows : 0; ?> Records
        </div>
    </div>

    <div class="ios-table-card animate" style="animation-delay: 0.1s;">
        <?php if ($result && $result->num_rows > 0): ?>
        <table class="ios-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Batch ID</th>
                    <th>Product</th>
                    <th>Total Qty</th>
                    <th>Quality Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): 
                    $g_class = 'grade-' . $row['Grade'];
                ?>
                <tr>
                    <td style="color:#8E8E93;"><?php echo date('M d, Y', strtotime($row['Completed_Date'])); ?></td>
                    <td style="font-weight:600;">#<?php echo $row['Batch_ID']; ?></td>
                    <td>
                        <div style="font-weight:600;"><?php echo $row['Product_Type']; ?></div>
                        <div style="font-size:12px; color:#8E8E93;">Ref: <?php echo $row['Order_ID']; ?></div>
                    </td>
                    <td style="font-size:15px; font-weight:600;"><?php echo number_format($row['Quantity']); ?> pcs</td>
                    <td><span class="grade-badge <?php echo $g_class; ?>">Grade <?php echo $row['Grade']; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div style="padding:40px; text-align:center; color:#8E8E93;">
                <i class="fas fa-box-open" style="font-size:40px; margin-bottom:15px; opacity:0.3;"></i><br>
                No finished goods found for this filter.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'layout_bottom.php'; ?>