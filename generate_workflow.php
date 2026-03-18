<?php
// DB Connection
$conn = new mysqli("localhost", "root", "", "flowtrack_mes");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_name = $_POST['project_name']; // e.g., "Summer T-Shirts 2026"
    $project_type = $_POST['project_type']; // e.g., "Garment Production"
    $start_date_input = $_POST['start_date']; // e.g., "2026-02-20"

    // 1. Create the Main Project Record
    $stmt = $conn->prepare("INSERT INTO Projects (Project_Name, Project_Type, Start_Date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $project_name, $project_type, $start_date_input);
    $stmt->execute();
    $project_id = $conn->insert_id; // Get the newly created Project ID

    // 2. Fetch the Workflow Template for this Project Type
    $template_stmt = $conn->prepare("SELECT Stage_Name, Estimated_Days FROM Workflow_Templates WHERE Project_Type = ? ORDER BY Stage_Order ASC");
    $template_stmt->bind_param("s", $project_type);
    $template_stmt->execute();
    $templates = $template_stmt->get_result();

    // 3. Initialize Date Tracker (This handles the dependencies)
    $current_date = new DateTime($start_date_input);
    
    // Prepare the Task Insert Statement
    $task_stmt = $conn->prepare("INSERT INTO Project_Tasks (Project_ID, Task_Name, Start_Date, Deadline) VALUES (?, ?, ?, ?)");

    // 4. The Generation Loop
    while ($step = $templates->fetch_assoc()) {
        $task_name = $step['Stage_Name'];
        $days_needed = $step['Estimated_Days'];
        
        // Task starts on the current tracked date
        $task_start = $current_date->format('Y-m-d H:i:s');
        
        // Add the estimated days to calculate the deadline
        $current_date->modify("+$days_needed days");
        $task_deadline = $current_date->format('Y-m-d H:i:s');
        
        // Insert the dynamic task into the database
        $task_stmt->bind_param("isss", $project_id, $task_name, $task_start, $task_deadline);
        $task_stmt->execute();
        
        // Notice: We DO NOT reset $current_date. 
        // This ensures Task 2 starts exactly when Task 1 finishes (Linear Dependency).
    }

    echo "Success! The workflow has been generated dynamically.";
}
?>