<?php 
include 'layout_top.php'; 

// --- SAFETY & CONNECTION ---
$servername = "127.0.0.1"; $username = "root"; $password = ""; $dbname = "flowtrack_mes"; $port = 3306; 
$conn = @new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) { $conn = new mysqli("localhost", "root", "", "flowtrack_mes"); }
if ($conn->connect_error) { die('<div style="padding:20px; color:red;">Database connection failed.</div>'); }

// --- HANDLE AVAILABILITY UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_availability'])) {
    $wid = $_POST['worker_id'];
    $status = $_POST['availability_status'];
    
    $stmt = $conn->prepare("UPDATE Worker SET Availability = ? WHERE Worker_ID = ?");
    $stmt->bind_param("si", $status, $wid);
    
    if ($stmt->execute()) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({icon: 'success', title: 'Updated', text: 'Status changed to $status', timer: 1500, showConfirmButton: false});
            });
        </script>";
    }
}
?>

<style>
    :root {
        --ios-bg: #F2F2F7;
        --ios-card: #FFFFFF;
        --ios-blue: #007AFF;
        --ios-gray: #8E8E93;
        --ios-separator: #E5E5EA;
        --text-primary: #000000;
    }

    body { background-color: var(--ios-bg); font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif; }

    /* GRID */
    .worker-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }

    /* CARD */
    .worker-card {
        background: var(--ios-card); border-radius: 20px; padding: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05);
        transition: transform 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative; overflow: hidden;
    }
    .worker-card:hover { transform: scale(1.02); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }

    /* CARD HEADER */
    .w-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
    
    /* ANIMATED AVATAR STYLE */
    .w-avatar-img { 
        width: 60px; height: 60px; border-radius: 50%; 
        background: #F2F2F7; border: 2px solid #FFFFFF;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        object-fit: cover;
    }

    .w-info h3 { margin: 0; font-size: 19px; font-weight: 600; letter-spacing: -0.5px; }
    .w-role { font-size: 14px; color: var(--ios-gray); margin-top: 2px; }

    /* ROWS */
    .w-row { display: flex; justify-content: space-between; font-size: 14px; padding: 10px 0; border-bottom: 1px solid var(--ios-separator); }
    .w-label { color: var(--ios-gray); }
    .w-val { font-weight: 500; color: var(--text-primary); }

    /* CONTROLS */
    .status-select {
        width: 100%; padding: 10px; border-radius: 10px; border: none; background: #F2F2F7;
        font-size: 14px; font-weight: 600; margin-top: 15px; cursor: pointer; color: var(--text-primary);
        text-align-last: center;
    }
    .status-select:focus { outline: none; box-shadow: 0 0 0 3px rgba(0,122,255,0.2); }

    .btn-view {
        width: 100%; margin-top: 10px; padding: 12px; border-radius: 12px;
        background: var(--ios-blue); color: white; border: none; font-size: 15px; font-weight: 600;
        cursor: pointer; transition: opacity 0.2s;
    }
    .btn-view:hover { opacity: 0.9; }

    /* === DESKTOP MODAL STYLES === */
    .ios-modal-container { font-family: -apple-system, sans-serif; text-align: left; background: #F2F2F7; padding-bottom: 20px; }
    
    /* Header */
    .ios-profile-header {
        background: white; padding: 30px; border-bottom: 1px solid #E5E5EA;
        margin-bottom: 25px;
    }
    .header-content { display: flex; align-items: center; gap: 20px; }
    
    /* BIG AVATAR IN MODAL */
    .ios-big-avatar-img {
        width: 90px; height: 90px; border-radius: 50%; 
        background: #F2F2F7; border: 4px solid #FFFFFF;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        object-fit: cover;
    }

    .ios-header-text { display: flex; flex-direction: column; justify-content: center; }
    .ios-name { font-size: 26px; font-weight: 800; color: #1C1C1E; line-height: 1.2; }
    .ios-role-badge { 
        background: #E5E5EA; color: #636366; padding: 4px 10px; border-radius: 6px; 
        font-size: 13px; font-weight: 600; text-transform: uppercase; align-self: flex-start; margin-top: 5px;
    }
    .ios-id { font-size: 13px; color: #AEAEB2; margin-top: 4px; font-weight: 500; }

    /* Grid System */
    .ios-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 25px; padding: 0 30px;
    }
    .ios-section-title {
        font-size: 13px; color: #8E8E93; text-transform: uppercase; margin-bottom: 8px; font-weight: 600; padding-left: 10px;
    }

    /* Inset Groups */
    .ios-col { display: flex; flex-direction: column; gap: 10px; }
    .ios-group { background: white; border-radius: 14px; overflow: hidden; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
    .ios-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-bottom: 1px solid #E5E5EA; font-size: 14px; }
    .ios-row:last-child { border-bottom: none; }
    
    .ios-label { color: #1C1C1E; font-weight: 500; }
    .ios-val { color: #8E8E93; text-align: right; }
    .ios-val-accent a { color: #007AFF; text-decoration: none; font-weight: 500; }

    /* SweetAlert Overrides */
    .ios-popup-radius { border-radius: 24px !important; overflow: hidden; }
    .ios-confirm-btn { 
        width: 200px !important; border-radius: 12px !important; 
        font-weight: 600 !important; font-size: 15px !important; margin: 10px auto 20px !important;
    }
</style>

<div style="display:flex; justify-content:space-between; align-items:end; margin-bottom:25px;">
    <div>
        <h1 style="font-size:32px; font-weight:800; margin:0; letter-spacing:-1px;">Team</h1>
        <div style="color:var(--ios-gray); font-size:15px; font-weight:500;">Manage availability & profiles</div>
    </div>
    <div style="background:white; padding:8px 16px; border-radius:20px; font-weight:600; font-size:14px; box-shadow:0 2px 10px rgba(0,0,0,0.05);">
        <span style="color:var(--ios-blue);"><?php echo $conn->query("SELECT COUNT(*) as c FROM Worker")->fetch_assoc()['c']; ?></span> Workers
    </div>
</div>

<div class="worker-grid">
    <?php
    $sql = "SELECT * FROM Worker ORDER BY FIELD(Role, 'Supervisor', 'QC Inspector', 'Store Keeper', 'Cutter', 'Sewer', 'Finisher'), Name ASC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            
            // GENERATE UNIQUE ANIMATED AVATAR URL
            // Using "Notionists" style which is clean and corporate-friendly
            $avatar_url = "https://api.dicebear.com/9.x/notionists/svg?seed=" . urlencode($row['Name']) . "&backgroundColor=b6e3f4,c0aede,d1d4f9,ffdfbf";

            // Add avatar to row data for JS
            $row['Avatar'] = $avatar_url;

            // Status Logic
            $avail = $row['Availability'];
            $st_bg = ($avail == 'Available') ? '#E4FCE8' : (($avail == 'Sick') ? '#FFEBEB' : '#FFF8E1');
            $st_col = ($avail == 'Available') ? '#10B981' : (($avail == 'Sick') ? '#EF4444' : '#F59E0B');
            
            // Safe JSON
            $safe_json = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

            echo "<div class='worker-card'>
                <div class='w-header'>
                    <img src='$avatar_url' class='w-avatar-img' alt='Avatar'>
                    <div class='w-info'>
                        <h3>{$row['Name']}</h3>
                        <div class='w-role'>{$row['Role']}</div>
                    </div>
                </div>

                <div class='w-row'>
                    <span class='w-label'>Rating</span>
                    <span class='w-val' style='color:$st_col'>{$row['Efficiency_Rating']}%</span>
                </div>
                <div class='w-row'>
                    <span class='w-label'>Phone</span>
                    <span class='w-val'>{$row['Phone']}</span>
                </div>

                <form method='post'>
                    <input type='hidden' name='worker_id' value='{$row['Worker_ID']}'>
                    <input type='hidden' name='update_availability' value='1'>
                    <select name='availability_status' class='status-select' 
                        style='background:$st_bg; color:$st_col;' 
                        onchange='this.form.submit()'>
                        <option value='Available' ".($avail=='Available'?'selected':'').">Available</option>
                        <option value='Sick' ".($avail=='Sick'?'selected':'').">Sick</option>
                        <option value='On Leave' ".($avail=='On Leave'?'selected':'').">On Leave</option>
                    </select>
                </form>

                <button class='btn-view' 
                        onclick='openIOSProfile(this)' 
                        data-info='$safe_json'>
                    View Profile
                </button>
            </div>";
        }
    } else {
        echo "<p style='padding:20px; color:#888;'>No workers found.</p>";
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function openIOSProfile(btn) {
        // Retrieve data safely
        const w = JSON.parse(btn.getAttribute('data-info'));

        // Format currency
        const salary = Number(w.Gross_Salary).toLocaleString();

        Swal.fire({
            html: `
                <div class="ios-modal-container">
                    
                    <div class="ios-profile-header">
                        <div class="header-content">
                            <img src="${w.Avatar}" class="ios-big-avatar-img" alt="Avatar">
                            
                            <div class="ios-header-text">
                                <div class="ios-name">${w.Name}</div>
                                <span class="ios-role-badge">${w.Role}</span>
                                <div class="ios-id">ID: ${w.Worker_ID}</div>
                            </div>
                        </div>
                    </div>

                    <div class="ios-grid">
                        
                        <div class="ios-col">
                            <div class="ios-section-title">Personal Information</div>
                            <div class="ios-group">
                                <div class="ios-row">
                                    <span class="ios-label">Phone</span>
                                    <span class="ios-val-accent"><a href="tel:${w.Phone}">${w.Phone}</a></span>
                                </div>
                                <div class="ios-row">
                                    <span class="ios-label">NID</span>
                                    <span class="ios-val">${w.NID_No}</span>
                                </div>
                                <div class="ios-row">
                                    <span class="ios-label">Blood Group</span>
                                    <span class="ios-val" style="color:#FF3B30; font-weight:700;">${w.Blood_Group || 'N/A'}</span>
                                </div>
                                <div class="ios-row" style="flex-direction:column; align-items:flex-start; gap:5px;">
                                    <span class="ios-label">Address</span>
                                    <span class="ios-val" style="text-align:left; max-width:100%; color:#333;">${w.Home_Address}</span>
                                </div>
                            </div>

                            <div class="ios-section-title">Emergency Contact</div>
                            <div class="ios-group">
                                <div class="ios-row">
                                    <span class="ios-label">Name</span>
                                    <span class="ios-val">${w.Emergency_Contact_Name}</span>
                                </div>
                                <div class="ios-row">
                                    <span class="ios-label">Relation</span>
                                    <span class="ios-val" style="font-size:12px; color:#8E8E93;">(Primary Contact)</span>
                                </div>
                                <div class="ios-row">
                                    <span class="ios-label">Mobile</span>
                                    <span class="ios-val-accent"><a href="tel:${w.Emergency_Contact}">${w.Emergency_Contact}</a></span>
                                </div>
                            </div>
                        </div>

                        <div class="ios-col">
                            <div class="ios-section-title">Employment Details</div>
                            <div class="ios-group">
                                <div class="ios-row">
                                    <span class="ios-label">Secondary Skill</span>
                                    <span class="ios-val">${w.Secondary_Role || 'None'}</span>
                                </div>
                                <div class="ios-row">
                                    <span class="ios-label">Shift Timing</span>
                                    <span class="ios-val">${w.Shift_Timing}</span>
                                </div>
                                <div class="ios-row">
                                    <span class="ios-label">Joining Date</span>
                                    <span class="ios-val">${w.Joining_Date}</span>
                                </div>
                                <div class="ios-row">
                                    <span class="ios-label">Gross Salary</span>
                                    <span class="ios-val" style="font-weight:600; color:#34C759;">৳${salary}</span>
                                </div>
                            </div>

                            <div class="ios-section-title">Work Preferences</div>
                            <div class="ios-group">
                                <div class="ios-row">
                                    <span class="ios-val" style="text-align:left; max-width:100%; color:#000;">${w.Preferences}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Close Profile',
            confirmButtonColor: '#007AFF',
            showCloseButton: true,
            width: '750px',
            padding: '0',
            background: '#F2F2F7',
            backdrop: `rgba(0,0,0,0.5) backdrop-filter: blur(4px)`,
            customClass: {
                popup: 'ios-popup-radius',
                confirmButton: 'ios-confirm-btn',
                closeButton: 'ios-close-btn'
            }
        });
    }
</script>

<?php include 'layout_bottom.php'; ?>