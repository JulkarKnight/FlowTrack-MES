<?php
session_start();
// Security: Only Managers can see this
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Manager') {
    // header("Location: login.php"); // Uncomment to enforce security
    // exit;
}

// DB Connection
$conn = new mysqli("localhost", "root", "", "flowtrack_mes");

// Handle Calculation
$prediction_results = [];
$target_batches = 100; // Default

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $target_batches = intval($_POST['target_batches']);
    
    // 1. Calculate Average Usage Per Batch from History
    $sql = "SELECT m.Material_Name, m.Unit, m.Current_Stock,
            SUM(mu.Quantity_Used) / COUNT(DISTINCT mu.Batch_ID) as avg_rate 
            FROM material_usage mu
            JOIN material m ON mu.Material_ID = m.Material_ID
            GROUP BY m.Material_ID";
            
    $result = $conn->query($sql);
    
    if($result) {
        while($row = $result->fetch_assoc()) {
            $needed = ceil($row['avg_rate'] * $target_batches); // Round up
            $prediction_results[] = [
                'name' => $row['Material_Name'],
                'unit' => $row['Unit'],
                'avg' => round($row['avg_rate'], 2),
                'total' => $needed,
                'stock' => $row['Current_Stock']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Forecast - FlowTrack</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #F5F5F7;
            --magic-grad: linear-gradient(135deg, #8E2DE2 0%, #4A00E0 100%);
            --glass: rgba(255, 255, 255, 0.95);
        }
        body { background: var(--bg); font-family: -apple-system, sans-serif; padding: 40px; color: #1D1D1F; margin: 0; min-height: 100vh; }
        
        .container { max-width: 900px; margin: 0 auto; }
        
        /* HEADER CARD */
        .magic-header {
            background: var(--magic-grad);
            border-radius: 24px;
            padding: 40px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(74, 0, 224, 0.3);
            margin-bottom: 30px;
            text-align: center;
        }
        .magic-header h1 { margin: 0 0 10px 0; font-size: 32px; font-weight: 800; }
        .magic-header p { margin: 0; opacity: 0.9; font-size: 16px; }
        .back-btn {
            position: absolute; top: 20px; left: 20px;
            color: white; text-decoration: none; font-weight: 600;
            background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px;
            font-size: 13px; backdrop-filter: blur(5px); transition: 0.2s;
        }
        .back-btn:hover { background: rgba(255,255,255,0.3); transform: scale(1.05); }

        /* INPUT AREA */
        .input-card {
            background: white; border-radius: 20px; padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            display: flex; align-items: center; gap: 20px;
            margin-bottom: 30px; border: 1px solid rgba(0,0,0,0.05);
        }
        .magic-input {
            flex-grow: 1; padding: 15px; border: 2px solid #E5E5EA;
            border-radius: 12px; font-size: 18px; font-weight: 600; color: #1D1D1F;
            transition: all 0.3s;
        }
        .magic-input:focus {
            border-color: #8E2DE2; outline: none;
            box-shadow: 0 0 0 4px rgba(142, 45, 226, 0.1);
        }
        .magic-btn {
            background: var(--magic-grad); color: white; border: none;
            padding: 15px 30px; border-radius: 12px; font-size: 16px; font-weight: 700;
            cursor: pointer; transition: 0.2s; box-shadow: 0 5px 15px rgba(74, 0, 224, 0.3);
        }
        .magic-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(74, 0, 224, 0.4); }

        /* RESULTS GRID */
        .results-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;
        }
        .result-card {
            background: white; border-radius: 18px; padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05);
            transition: 0.2s; position: relative; overflow: hidden;
        }
        .result-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
        
        .res-label { font-size: 11px; text-transform: uppercase; color: #86868B; font-weight: 700; letter-spacing: 0.5px; }
        .res-val { font-size: 24px; font-weight: 800; color: #1D1D1F; margin: 5px 0; }
        .res-unit { font-size: 14px; color: #86868B; font-weight: 500; }
        
        .stock-pill {
            display: inline-block; padding: 4px 10px; border-radius: 8px;
            font-size: 11px; font-weight: 600; margin-top: 10px;
        }
        .stock-ok { background: #D1FAE5; color: #065F46; } /* Green */
        .stock-low { background: #FEE2E2; color: #991B1B; } /* Red */

        /* ANIMATION */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-in { animation: fadeUp 0.5s ease forwards; opacity: 0; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="magic-header animate-in">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
        <i class="fas fa-wand-magic-sparkles" style="font-size: 40px; margin-bottom: 15px; opacity: 0.8;"></i>
        <h1>AI Production Forecast</h1>
        <p>Predict your raw material needs for upcoming seasons instantly.</p>
    </div>

    <form method="POST" class="input-card animate-in" style="animation-delay: 0.1s;">
        <div style="flex-grow: 1;">
            <label style="display:block; font-size:12px; font-weight:700; color:#86868B; margin-bottom:8px; text-transform:uppercase;">Target Production Volume</label>
            <input type="number" name="target_batches" class="magic-input" value="<?php echo $target_batches; ?>" min="1" placeholder="e.g., 500 Batches">
        </div>
        <button type="submit" class="magic-btn">
            <i class="fas fa-bolt"></i> Generate
        </button>
    </form>

    <?php if (!empty($prediction_results)): ?>
        <div class="results-grid animate-in" style="animation-delay: 0.2s;">
            <?php foreach($prediction_results as $row): 
                $is_shortage = $row['stock'] < $row['total'];
            ?>
            <div class="result-card">
                <div class="res-label"><?php echo $row['name']; ?></div>
                <div class="res-val">
                    <?php echo number_format($row['total']); ?> 
                    <span class="res-unit"><?php echo $row['unit']; ?></span>
                </div>
                
                <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #F5F5F7; padding-top:10px; margin-top:10px;">
                    <div>
                        <div style="font-size:10px; color:#86868B;">Current Stock</div>
                        <div style="font-size:13px; font-weight:600;"><?php echo number_format($row['stock']); ?></div>
                    </div>
                    <?php if($is_shortage): ?>
                        <div class="stock-pill stock-low">Shortage</div>
                    <?php else: ?>
                        <div class="stock-pill stock-ok">Sufficient</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php elseif($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <p style="text-align:center; color:#86868B; padding:20px;">No historical usage data found to generate predictions.</p>
    <?php endif; ?>

</div>

</body>
</html>