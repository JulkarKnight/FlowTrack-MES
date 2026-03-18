<?php
// FILE: notification_engine.php

function trigger_notification($conn, $worker_id, $message, $type) {
    // $type should be: 'Warning', 'Assignment', 'Deadline', or 'Conflict'
    $sql = "INSERT INTO Notifications (Worker_ID, Message, Type, Is_Read, Created_At) 
            VALUES (?, ?, ?, FALSE, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $worker_id, $message, $type);
    $stmt->execute();
}
?>