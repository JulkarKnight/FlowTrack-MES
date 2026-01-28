<?php
include 'db_connect.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $role = $_POST['role'];
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    $sql = "INSERT INTO Users (Username, Password, Role) VALUES ('$user', '$hashed_password', '$role')";

    if ($conn->query($sql) === TRUE) {
        $message = "<div class='alert success'>User Created Successfully</div>";
    } else {
        $message = "<div class='alert error'>Error: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register User</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="card">
        <h2>New User</h2>
        <?php echo $message; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="e.g. JohnDoe" required>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="Manager">Manager</option>
                    <option value="Admin">Admin</option>
                    <option value="QC_Inspector">QC Inspector</option>
                </select>
            </div>
            
            <input type="submit" value="Create Account">
        </form>
    </div>
</body>
</html>