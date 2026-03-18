<?php
session_start();
// Security Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Manager') {
    // header("Location: login.php"); exit;
}

$conn = new mysqli("localhost", "root", "", "flowtrack_mes");

// Fetch available workflow templates
$templates = $conn->query("SELECT DISTINCT Project_Type FROM Workflow_Templates");

// Fetch active projects and their tasks
$tasks_sql = "
    SELECT t.*, p.Project_Name, w.Name as Worker_Name 
    FROM Project_Tasks t
    JOIN Projects p ON t.Project_ID = p.Project_ID
    LEFT JOIN Worker w ON t.Worker_ID = w.Worker_ID
    WHERE p.Status = 'Active'
    ORDER BY p.Project_ID DESC, t.Start_Date ASC
";
$tasks_res = $conn->query($tasks_sql);

// Fetch all available workers for the assignment dropdown
$workers_res = $conn->query("SELECT Worker_ID, Name, Role FROM Worker WHERE Availability = 'Available'");
$workers = [];
while($w = $workers_res->fetch_assoc()) {
    $workers[] = $w;
}
$workers_json = json_encode($workers);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Scheduling - FlowTrack</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --bg: #F5F5F7; --blue: #007AFF; --purple: #AF52DE; }
        body { background: var(--bg); font-family: -apple-system, sans-serif; padding: 40px; color: #1D1D1F; margin:0; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .title h1 { margin: 0; font-size: 28px; font-weight: 800; }
        .title p { margin: 5px 0 0 0; color: #86868B; font-size: 14px; }
        
        .d-card { background: rgba(255, 255, 255, 0.9); border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.6); margin-bottom: 30px; }
        
        /* Form Styles */
        .form-grid { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end; }
        .input-group label { display: block; font-size: 12px; font-weight: 600; color: #86868B; margin-bottom: 5px; text-transform: uppercase; }
        .mac-input { width: 100%; padding: 12px; border: 1px solid #d2d2d7; border-radius: 10px; font-size: 14px; box-sizing: border-box; transition: 0.2s; }
        .mac-input:focus { border-color: var(--blue); outline: none; box-shadow: 0 0 0 3px rgba(0,122,255,0.2); }
        .btn-primary { background: var(--blue); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s; height: 43px; }
        .btn-primary:hover { background: #005ecb; transform: translateY(-2px); }

        /* Table Styles */
        .mac-table { width: 100%; border-collapse: collapse; }
        .mac-table th { text-align: left; font-size: 11px; text-transform: uppercase; color: #86868B; padding: 10px; border-bottom: 1px solid #E5E5EA; }
        .mac-table td { font-size: 13px; color: #1D1D1F; padding: 15px 10px; border-bottom: 1px solid #F5F5F7; }
        
        .badge { padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-pending { background: #F2F2F7; color: #8E8E93; }
        .badge-progress { background: #E0F2FE; color: #007AFF; }
        
        .btn-assign { background: #E0F2FE; color: #007AFF; border: none; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-assign:hover { background: #007AFF; color: white; }

        .worker-pill { display: flex; align-items: center; gap: 8px; font-weight: 600; }
        .avatar-sm { width: 24px; height: 24px; border-radius: 50%; background: #E5E5EA; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="title">
            <a href="index.php" style="text-decoration:none; color:var(--blue); font-size:13px; font-weight:600;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <h1>Project & Smart Scheduling</h1>
            <p>Generate workflows and assign tasks using the AI workload engine.</p>
        </div>
    </div>

    <div class="d-card">
        <h3 style="margin-top:0; font-size:16px;">Create New Project (Auto-Generate Tasks)</h3>
        <form id="createProjectForm" class="form-grid">
            <div class="input-group">
                <label>Project Name</label>
                <input type="text" name="project_name" class="mac-input" placeholder="e.g., Winter Jackets Batch 2" required>
            </div>
            <div class="input-group">
                <label>Workflow Template</label>
                <select name="project_type" class="mac-input" required>
                    <?php while($t = $templates->fetch_assoc()): ?>
                        <option value="<?php echo $t['Project_Type']; ?>"><?php echo $t['Project_Type']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="input-group">
                <label>Target Start Date</label>
                <input type="date" name="start_date" class="mac-input" required>
            </div>
            <button type="submit" class="btn-primary"><i class="fas fa-magic"></i> Generate Workflow</button>
        </form>
    </div>

    <div class="d-card">
        <h3 style="margin-top:0; font-size:16px;">Active Tasks & Assignments</h3>
        <table class="mac-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Task Stage</th>
                    <th>Timeline</th>
                    <th>Assigned To</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if($tasks_res && $tasks_res->num_rows > 0): ?>
                    <?php while($task = $tasks_res->fetch_assoc()): 
                        $start = date('M d', strtotime($task['Start_Date']));
                        $end = date('M d', strtotime($task['Deadline']));
                        $is_assigned = !empty($task['Worker_ID']);
                    ?>
                    <tr>
                        <td><b><?php echo $task['Project_Name']; ?></b></td>
                        <td><?php echo $task['Task_Name']; ?></td>
                        <td style="color:#86868B; font-size:12px;"><i class="fas fa-calendar-alt"></i> <?php echo "$start - $end"; ?></td>
                        
                        <td>
                            <?php if($is_assigned): ?>
                                <div class="worker-pill">
                                    <img src="https://api.dicebear.com/9.x/notionists/svg?seed=<?php echo urlencode($task['Worker_Name']); ?>" class="avatar-sm">
                                    <?php echo $task['Worker_Name']; ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#FF3B30; font-style:italic; font-size:12px;">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <span class="badge <?php echo ($task['Status']=='Pending') ? 'badge-pending' : 'badge-progress'; ?>">
                                <?php echo $task['Status']; ?>
                            </span>
                        </td>
                        
                        <td>
                            <button class="btn-assign" onclick="openAssignModal(<?php echo $task['Task_ID']; ?>, '<?php echo $task['Task_Name']; ?>')">
                                <?php echo $is_assigned ? 'Reassign' : 'Assign Worker'; ?>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; padding:30px; color:#86868B;">No active tasks. Create a project above!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
    const workers = <?php echo $workers_json; ?>;

    // --- 1. HANDLE PROJECT CREATION (AJAX) ---
    document.getElementById('createProjectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('generate_workflow.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(data => {
            Swal.fire({
                title: 'Workflow Generated!',
                text: 'Tasks have been dynamically created based on the template.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => location.reload()); // Reload to show new tasks
        });
    });

    // --- 2. HANDLE SMART ASSIGNMENT MODAL ---
    function openAssignModal(taskId, taskName) {
        // Build the dropdown options dynamically from our PHP array
        let optionsHtml = '<option value="" disabled selected>Select a worker...</option>';
        workers.forEach(w => {
            optionsHtml += `<option value="${w.Worker_ID}">${w.Name} (${w.Role})</option>`;
        });

        Swal.fire({
            title: `Assign: ${taskName}`,
            html: `
                <div style="text-align:left; margin-top:15px;">
                    <label style="font-size:12px; color:#86868B; font-weight:600; text-transform:uppercase;">Select Worker</label>
                    <select id="swal-worker-select" class="mac-input" style="margin-top:5px; width:100%;">
                        ${optionsHtml}
                    </select>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Assign & Run Engine',
            confirmButtonColor: '#007AFF',
            preConfirm: () => {
                const workerId = document.getElementById('swal-worker-select').value;
                if (!workerId) { Swal.showValidationMessage('Please select a worker'); }
                return workerId;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                runAssignmentEngine(taskId, result.value);
            }
        });
    }

    // --- 3. THE MAGIC: TALKING TO THE ENGINE ---
    function runAssignmentEngine(taskId, workerId) {
        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('worker_id', workerId);

        fetch('assign_task_engine.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Success! The engine approved the assignment and fired the notification.
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: data.message, showConfirmButton: false, timer: 3000
                }).then(() => location.reload());
            } 
            else if (data.status === 'error') {
                // FAILURE! The engine caught a conflict or overload!
                Swal.fire({
                    icon: 'error',
                    title: 'Assignment Blocked',
                    html: `
                        <div style="color:#FF3B30; margin-bottom:15px;">${data.message}</div>
                        <div style="background:#F2F2F7; padding:15px; border-radius:10px; font-size:14px; text-align:left;">
                            ${data.suggestion}
                        </div>
                    `,
                    confirmButtonText: 'Understood',
                    confirmButtonColor: '#1D1D1F'
                });
            }
        });
    }
</script>

</body>
</html>