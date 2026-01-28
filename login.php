<?php
session_start();
$message = "";

// --- LOGIN HANDLER ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "flowtrack_mes");
    
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $role_mode = $_POST['role']; // 'manager' or 'worker'

    if ($role_mode == 'manager') {
        // 1. MANAGER LOGIN (Check 'Users' Table)
        $stmt = $conn->prepare("SELECT * FROM Users WHERE Username=? AND Password=?");
        $md5_pass = md5($pass);
        $stmt->bind_param("ss", $user, $md5_pass);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['user_id'] = $row['Username'];
            $_SESSION['user_role'] = 'Manager';
            header("Location: index.php");
            exit;
        } else {
            $message = "❌ Manager not found or wrong password.";
        }

    } else {
        // 2. WORKER LOGIN (Check 'Worker' Table)
        // Note: In real production, hash these passwords too!
        $stmt = $conn->prepare("SELECT * FROM Worker WHERE Name=? AND Password=? AND Status='Active'");
        $stmt->bind_param("ss", $user, $pass);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $w = $result->fetch_assoc();
            $_SESSION['user_id'] = $w['Worker_ID'];
            $_SESSION['user_name'] = $w['Name'];
            $_SESSION['user_role'] = 'Worker';
            header("Location: worker_dash.php");
            exit;
        } else {
            $message = "❌ Worker ID/Name or Password incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign In - FlowTrack</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: #F2F2F7;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 40px 50px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            width: 360px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: var(--primary-blue);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px auto;
            box-shadow: 0 10px 20px rgba(0,122,255,0.3);
            transition: background 0.3s ease;
        }
        input {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 15px;
            background: white;
            border: 1px solid #d1d1d6;
            border-radius: 12px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(0,122,255,0.1);
        }
        .toggle-link {
            display: block;
            margin-top: 25px;
            font-size: 14px;
            color: var(--primary-blue);
            text-decoration: none;
            cursor: pointer;
            font-weight: 500;
        }
        .toggle-link:hover {
            text-decoration: underline;
        }
        /* Worker Mode Styles */
        body.worker-mode .icon-circle {
            background: var(--warning-orange);
            box-shadow: 0 10px 20px rgba(255,149,0,0.3);
        }
        body.worker-mode .btn-primary {
            background: var(--warning-orange);
        }
        body.worker-mode .toggle-link {
            color: var(--warning-orange);
        }
    </style>
</head>
<body id="main-body">

    <div class="login-card">
        <div class="icon-circle" id="role-icon">
            <i class="fas fa-user-tie"></i>
        </div>

        <h2 id="login-title" style="margin: 0 0 10px 0; color:#333;">Manager Login</h2>
        <p id="login-desc" style="color: #888; margin-bottom: 30px; font-size: 14px;">Access production controls</p>

        <?php if($message): ?>
            <div style="background:rgba(255,59,48,0.1); color:#FF3B30; padding:12px; border-radius:10px; font-size:13px; margin-bottom:20px; text-align:left;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="role" id="role-input" value="manager">

            <div style="text-align:left; font-size:12px; font-weight:bold; color:#888; margin-bottom:5px; margin-left:5px;" id="label-user">USERNAME</div>
            <input type="text" name="username" id="input-user" placeholder="admin" required>

            <div style="text-align:left; font-size:12px; font-weight:bold; color:#888; margin-bottom:5px; margin-left:5px;" id="label-pass">PASSWORD</div>
            <input type="password" name="password" id="input-pass" placeholder="••••••••" required>
            
            <button type="submit" class="btn-primary" style="width:100%; justify-content:center; font-size:16px; padding:14px;">Sign In</button>
        </form>

        <div style="border-top: 1px solid #eee; margin-top: 25px;"></div>
        <a onclick="toggleRole()" class="toggle-link" id="toggle-text">Not a Manager? <b>Sign in as Worker</b></a>
    </div>

    <script>
        let isManager = true;

        function toggleRole() {
            isManager = !isManager;
            const body = document.getElementById('main-body');
            const roleInput = document.getElementById('role-input');
            const title = document.getElementById('login-title');
            const desc = document.getElementById('login-desc');
            const icon = document.getElementById('role-icon');
            const toggleText = document.getElementById('toggle-text');
            const labelUser = document.getElementById('label-user');
            const inputUser = document.getElementById('input-user');

            if (isManager) {
                // Switch to Manager
                body.classList.remove('worker-mode');
                roleInput.value = 'manager';
                title.innerText = "Manager Login";
                desc.innerText = "Access production controls";
                icon.innerHTML = '<i class="fas fa-user-tie"></i>';
                toggleText.innerHTML = "Not a Manager? <b>Sign in as Worker</b>";
                labelUser.innerText = "USERNAME";
                inputUser.placeholder = "admin";
            } else {
                // Switch to Worker
                body.classList.add('worker-mode');
                roleInput.value = 'worker';
                title.innerText = "Worker Portal";
                desc.innerText = "Check your shifts and tasks";
                icon.innerHTML = '<i class="fas fa-hard-hat"></i>';
                toggleText.innerHTML = "Not a Worker? <b>Sign in as Manager</b>";
                labelUser.innerText = "WORKER NAME";
                inputUser.placeholder = "John Doe";
            }
        }
    </script>
</body>
</html>