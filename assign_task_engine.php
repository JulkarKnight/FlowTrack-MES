<?php
// FILE: assign_task_engine.php
session_start();
header('Content-Type: application/json');

// 1. Database Connection
$servername = "localhost"; $username = "root"; $password = ""; $dbname = "flowtrack_mes";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}

// 2. Include the Notification Engine (From Phase 3)
// If you haven't made this file yet, I have provided it below this code block.
require_once 'notification_engine.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = intval($_POST['task_id']);
    $worker_id = intval($_POST['worker_id']);

    // --- STEP A: Fetch Task & Worker Details ---
    $task_stmt = $conn->query("SELECT Task_Name, Start_Date, Deadline FROM Project_Tasks WHERE Task_ID = $task_id");
    if ($task_stmt->num_rows == 0) {
        echo json_encode(["status" => "error", "message" => "Task not found."]); exit;
    }
    $task_data = $task_stmt->fetch_assoc();
    $new_start = $task_data['Start_Date'];
    $new_end = $task_data['Deadline'];
    $task_name = $task_data['Task_Name'];

    // Get the requested worker's role so we know who to recommend if they fail the checks
    $worker_stmt = $conn->query("SELECT Role FROM Worker WHERE Worker_ID = $worker_id");
    $req_role = ($worker_stmt->num_rows > 0) ? $worker_stmt->fetch_assoc()['Role'] : '';

    // --- STEP B: CHECK 1 - Real-Time Conflict Detection ---
    // Does this worker have a task that overlaps with these dates?
    $overlap_sql = "SELECT Task_Name FROM Project_Tasks 
                    WHERE Worker_ID = ? AND Status != 'Completed' 
                    AND (Start_Date < ? AND Deadline > ?)";
    
    $stmt = $conn->prepare($overlap_sql);
    $stmt->bind_param("iss", $worker_id, $new_end, $new_start);
    $stmt->execute();
    $overlap_result = $stmt->get_result();

    if ($overlap_result->num_rows > 0) {
        $conflict = $overlap_result->fetch_assoc();
        return_error_with_suggestion($conn, "Schedule Conflict: Worker is already assigned to '" . $conflict['Task_Name'] . "' during this time.", $req_role, $new_start, $new_end);
        exit;
    }

    // --- STEP C: CHECK 2 - Workload Balancing ---
    // Does this worker already have too many active tasks?
    $max_active_tasks = 3; 
    
    $workload_sql = "SELECT COUNT(*) as active_count FROM Project_Tasks 
                     WHERE Worker_ID = ? AND Status != 'Completed'";
    $stmt = $conn->prepare($workload_sql);
    $stmt->bind_param("i", $worker_id);
    $stmt->execute();
    $workload_data = $stmt->get_result()->fetch_assoc();

    if ($workload_data['active_count'] >= $max_active_tasks) {
        return_error_with_suggestion($conn, "Workload Overload: Worker already has {$max_active_tasks} active tasks.", $req_role, $new_start, $new_end);
        exit;
    }

    // --- STEP D: SUCCESS - Assign and Notify ---
    $assign_sql = "UPDATE Project_Tasks SET Worker_ID = ?, Status = 'In Progress' WHERE Task_ID = ?";
    $stmt = $conn->prepare($assign_sql);
    $stmt->bind_param("ii", $worker_id, $task_id);
    
    if ($stmt->execute()) {
        // Trigger the Event-Driven Notification!
        $msg = "New Assignment: You have been assigned to '$task_name'.";
        trigger_notification($conn, $worker_id, $msg, "Assignment");

        echo json_encode(["status" => "success", "message" => "Task assigned and worker notified successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database error during assignment."]);
    }
}

// ==========================================
// THE RECOMMENDATION ALGORITHM
// ==========================================
function return_error_with_suggestion($conn, $error_msg, $role, $start, $end) {
    // Find a worker of the same role, who is available, has NO date conflicts, ordered by fewest active tasks.
    $suggest_sql = "
        SELECT w.Worker_ID, w.Name, 
               (SELECT COUNT(*) FROM Project_Tasks pt WHERE pt.Worker_ID = w.Worker_ID AND pt.Status != 'Completed') as Current_Workload
        FROM Worker w
        WHERE w.Role = '$role' AND w.Availability = 'Available'
        AND w.Worker_ID NOT IN (
            SELECT Worker_ID FROM Project_Tasks 
            WHERE Status != 'Completed' AND (Start_Date < '$end' AND Deadline > '$start')
            AND Worker_ID IS NOT NULL
        )
        ORDER BY Current_Workload ASC 
        LIMIT 1
    ";
    
    $suggest_res = $conn->query($suggest_sql);
    
    if ($suggest_res && $suggest_res->num_rows > 0) {
        $alt = $suggest_res->fetch_assoc();
        $suggestion_html = "<b>Smart Suggestion:</b> Reassign to <b>" . $alt['Name'] . "</b> (Current Tasks: " . $alt['Current_Workload'] . ")";
    } else {
        $suggestion_html = "<i>No available alternative workers found for this timeframe.</i>";
    }

    echo json_encode([
        "status" => "error", 
        "message" => $error_msg, 
        "suggestion" => $suggestion_html
    ]);
}
?>