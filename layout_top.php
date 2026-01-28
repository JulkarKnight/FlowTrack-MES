<?php
session_start();

// --- 1. SECURITY GATEKEEPER ---
// If the user is NOT logged in, kick them to login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FlowTrack - Manufacturing Execution System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-layer-group"></i> <span>FlowTrack</span>
        </div>
        
        <nav style="flex: 1; display: flex; flex-direction: column;">
            
            <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
            </a>
            
            <a href="batches.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'batches.php' ? 'active' : ''; ?>">
                <i class="fas fa-tshirt"></i> <span>Production</span>
            </a>
            
            <a href="workers.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'workers.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> <span>Workers</span>
            </a>
            
            <a href="inventory.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i> <span>Inventory</span>
            </a>
            
            <a href="qc.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'qc.php' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-check"></i> <span>Quality Control</span>
            </a>

            <a href="rework.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rework.php' ? 'active' : ''; ?>">
                <i class="fas fa-hammer"></i> <span>Repair Bay</span>
            </a>

            <a href="logout.php" class="nav-link" style="margin-top: auto; color: var(--danger-red); background: rgba(255, 59, 48, 0.05); border: 1px solid rgba(255, 59, 48, 0.1);">
                <i class="fas fa-sign-out-alt"></i> <span>Log Out</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        
        <?php
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "flowtrack_mes";

        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            die("<div class='alert' style='background:var(--danger-red); color:white;'>
                <i class='fas fa-exclamation-triangle'></i> 
                <b>Database Connection Failed:</b> " . $conn->connect_error . "
            </div>");
        }
        ?>