<?php
// FILE: check_notifications.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(["unread_count" => 0, "all_unread" => []]); exit; }

$w_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "flowtrack_mes");

// Mark as read
if (isset($_GET['action']) && $_GET['action'] == 'mark_read') {
    $conn->query("UPDATE Notifications SET Is_Read = TRUE WHERE Worker_ID = $w_id");
    echo json_encode(["status" => "success"]); exit;
}

// Fetch all unread notifications
$res = $conn->query("SELECT * FROM Notifications WHERE Worker_ID = $w_id AND Is_Read = FALSE ORDER BY Created_At DESC");
$notifs = [];
while($row = $res->fetch_assoc()) {
    $notifs[] = $row;
}

echo json_encode([
    "unread_count" => count($notifs),
    "latest" => count($notifs) > 0 ? $notifs[0] : null,
    "all_unread" => $notifs // Send the array to JS
]);
?>