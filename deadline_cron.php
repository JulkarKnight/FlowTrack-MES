<?php
// FILE: deadline_cron.php
// This script finds tasks due in the next 24 hours and warns the worker.

$conn = new mysqli("localhost", "root", "", "flowtrack_mes");
require_once 'notification_engine.php';

// Find tasks that are 'In Progress', due within 24 hours, and haven't been warned yet.
// (We add a 'Warned' flag to Project_Tasks so we don't spam them every hour)
$sql = "SELECT Task_ID, Worker_ID, Task_Name, Deadline 
        FROM Project_Tasks 
        WHERE Status = 'In Progress' 
        AND Warned_24h = FALSE 
        AND Deadline <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
        AND Deadline >= NOW()";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $w_id = $row['Worker_ID'];
        $t_name = $row['Task_Name'];
        
        // 1. Create the Notification
        $msg = "URGENT: Task '$t_name' is due in less than 24 hours!";
        trigger_notification($conn, $w_id, $msg, "Deadline");
        
        // 2. Mark as warned so we don't duplicate
        $conn->query("UPDATE Project_Tasks SET Warned_24h = TRUE WHERE Task_ID = " . $row['Task_ID']);
    }
    echo "Deadline checks complete. Notifications sent.";
} else {
    echo "No imminent deadlines.";
}
?>