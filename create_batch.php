<?php
include 'db_connect.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $order_id = $_POST['order_id'];
    $product = $_POST['product_type'];
    $start_time = date("Y-m-d H:i:s");
    $status = 'Running';

    $sql = "INSERT INTO Batch (Order_ID, Product_Type, Start_Time, Status) 
            VALUES ('$order_id', '$product', '$start_time', '$status')";

    if ($conn->query($sql) === TRUE) {
        $message = "<div class='alert success'>Batch Started</div>";
    } else {
        $message = "<div class='alert error'>Error: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Start Batch</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="card">
        <h2>Start Production</h2>
        <?php echo $message; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label>Order ID</label>
                <input type="number" name="order_id" placeholder="e.g. 1045" required>
            </div>
            
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="product_type" placeholder="e.g. Cotton Polo" required>
            </div>
            
            <input type="submit" value="Start Batch">
        </form>

        <div class="data-list">
            <h3>Recent Activity</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Status</th>
                </tr>
                <?php
                $result = $conn->query("SELECT * FROM Batch ORDER BY Batch_ID DESC LIMIT 3");
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>#" . $row["Batch_ID"]. "</td>
                                <td>" . $row["Product_Type"]. "</td>
                                <td><span class='status-badge'>" . $row["Status"]. "</span></td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center; color:#999;'>No active batches</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>
</body>
</html>