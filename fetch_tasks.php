<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$w_id = $_SESSION['user_id'];

// DB Connection
$servername = "127.0.0.1"; $username = "root"; $password = ""; $dbname = "flowtrack_mes";
$conn = @new mysqli($servername, $username, $password, $dbname, 3306);
if ($conn->connect_error) { $conn = new mysqli("localhost", "root", "", "flowtrack_mes"); }

// Fetch Tasks
$sql = "SELECT Task_ID, Batch_ID, Stage_Name, Start_Time, End_Time, Status 
        FROM schedule_tasks 
        WHERE Worker_ID = $w_id";

$result = $conn->query($sql);

$events = [];
while($row = $result->fetch_assoc()) {
    $color = '#007AFF'; // Default Blue
    if ($row['Status'] == 'Completed') { $color = '#34C759'; } // Green
    if ($row['Status'] == 'In Progress') { $color = '#FF9F0A'; } // Orange

    $events[] = [
        'id' => $row['Task_ID'],
        'title' => '#' . $row['Batch_ID'] . ' - ' . $row['Stage_Name'],
        'start' => $row['Start_Time'],
        'end' => $row['End_Time'],
        'backgroundColor' => $color,
        'borderColor' => $color
    ];
}

echo json_encode($events);
?>