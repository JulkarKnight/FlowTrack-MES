<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// --- 1. SECURITY GATEKEEPER ---
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">

    <style>
        /* Top Navigation Bar Styling */
        .top-nav {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 0 20px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        /* NOTIFICATION BELL CSS */
        .notif-wrapper {
            position: relative;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: 0.2s;
        }
        .notif-wrapper:hover {
            transform: scale(1.05);
            color: #007AFF;
        }
        .notif-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #FF3B30;
            color: white;
            font-size: 10px;
            font-weight: 800;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #F5F5F7;
            display: none; /* Hidden by default until JS finds alerts */
        }
    </style>
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
                <i class="fas fa-tshirt"></i> <span>Production & Ops</span>
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
        
        <div class="top-nav">
            <div style="display:flex; align-items:center; gap:20px;">
                <span style="font-size: 14px; font-weight: 600; color: #86868B;">
                    <i class="fas fa-user-circle"></i> <?php echo $_SESSION['user_role'] ?? 'User'; ?>
                </span>
                
                <div class="notif-wrapper" onclick="openNotifications()">
                    <i class="fas fa-bell" style="font-size: 18px;"></i>
                    <span id="notif-badge" class="notif-badge">0</span>
                </div>
            </div>
        </div>

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

        <script>
            function openNotifications() {
                // Fetch the latest unread alerts from the engine
                fetch('check_notifications.php')
                .then(res => res.json())
                .then(data => {
                    let notifs = data.all_unread || [];
                    
                    // If no notifications exist
                    if (notifs.length === 0) {
                        Swal.fire({ 
                            title: 'Notifications', 
                            text: 'You have no new alerts.', 
                            icon: 'info',
                            confirmButtonColor: '#007AFF'
                        });
                        return;
                    }

                    // Build the HTML list
                    let listHtml = '<div style="text-align:left; max-height: 250px; overflow-y:auto; padding-right:10px;">';
                    notifs.forEach(n => {
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

                    // Show the popup
                    Swal.fire({
                        title: 'Recent Alerts',
                        html: listHtml,
                        showCancelButton: true,
                        confirmButtonText: 'Mark All as Read',
                        cancelButtonText: 'Close',
                        confirmButtonColor: '#007AFF'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Tell database to mark them all as read
                            fetch('check_notifications.php?action=mark_read').then(() => {
                                const badge = document.getElementById('notif-badge');
                                if(badge) badge.style.display = 'none'; // Hide the red dot
                            });
                        }
                    });
                })
                .catch(err => console.error("Failed to load notifications", err));
            }
        </script>