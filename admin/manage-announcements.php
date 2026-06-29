<?php
// admin/manage-announcements.php
require_once "../db.php";
session_start();
require_once "utils_mailer.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$success = "";
$error = "";

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM announcements WHERE id = $id");
    $success = "Announcement deleted successfully.";
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $error = "Security validation failed.";
    } else {
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $priority = s($_POST['priority'] ?? 'Normal');
        $notify_email = isset($_POST['notify_email']);
        
        if ($title === '' || $message === '') {
            $error = "Title and message are required.";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO announcements (title, message, priority) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $title, $message, $priority);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Announcement posted successfully!";
                
                // Blast Email if requested
                if ($notify_email) {
                    $qUsers = mysqli_query($conn, "SELECT name, email FROM users WHERE email IS NOT NULL AND email != '' AND status = 'active'");
                    $emails_sent = 0;
                    while ($user = mysqli_fetch_assoc($qUsers)) {
                        send_announcement_email($user['email'], $user['name'], $title, $message, $priority);
                        $emails_sent++;
                    }
                    if ($emails_sent > 0) {
                        $success .= " Also emailed to $emails_sent resident(s).";
                    }
                }

            } else {
                $error = "Database error. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all announcements
$res = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = [];
while ($row = mysqli_fetch_assoc($res)) $announcements[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manage Announcements | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
    <style>
        .symmetric-grid {
            display: grid;
            grid-template-columns: 1.8fr 1.2fr;
            gap: 24px;
            align-items: stretch;
        }
        .symmetric-grid .left-col, .symmetric-grid .right-col {
            display: flex;
            flex-direction: column;
        }
        .symmetric-grid .panel {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 1024px) {
            .symmetric-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up">
        <h1>Official Announcements</h1>
        <p>Post notices, maintenance alerts, or general news for all renters.</p>
    </div>

    <?php if ($success): ?>
        <div class="animate-up" id="successMsgAlert" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #DCFCE7; transition: opacity 0.5s ease-out;">
            <i class='bx bx-check-circle'></i> <?php echo $success; ?>
        </div>
        <script>
            setTimeout(() => {
                const el = document.getElementById('successMsgAlert');
                if(el) {
                    el.style.opacity = '0';
                    setTimeout(() => el.style.display = 'none', 500);
                }
            }, 3000);
        </script>
    <?php endif; ?>

    <div class="symmetric-grid animate-up">
        <div class="left-col">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Active Notices</h2>
                </div>
                <div class="table-responsive" style="flex: 1; margin: 0; padding-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th>Notice</th>
                                <th>Priority</th>
                                <th>Posted On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($announcements)): ?>
                                <tr><td colspan="4" style="text-align: center; vertical-align: middle; padding: 60px; color: var(--text-gray); font-style: italic;">No announcements posted yet.</td></tr>
                            <?php else: foreach ($announcements as $a): ?>
                            <tr>
                                <td style="padding: 18px 16px;">
                                    <div style="font-weight: 700; color: var(--text-dark); margin-bottom: 4px;"><?php echo htmlspecialchars($a['title']); ?></div>
                                    <div style="font-size: 13px; color: var(--text-gray); max-width: 320px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; white-space: normal; line-height: 1.4;"><?php echo htmlspecialchars($a['message']); ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $p_class = 'status-normal';
                                    if($a['priority'] == 'High') $p_class = 'status-high';
                                    if($a['priority'] == 'Urgent') $p_class = 'status-urgent';
                                    if($a['priority'] == 'Low') $p_class = 'status-low';
                                    ?>
                                    <span class="status-badge <?php echo $p_class; ?>"><?php echo $a['priority']; ?></span>
                                </td>
                                <td style="font-size: 13px; color: var(--text-gray); font-weight: 500;"><?php echo date('M d, Y', strtotime($a['created_at'])); ?><br><small><?php echo date('h:i A', strtotime($a['created_at'])); ?></small></td>
                                <td>
                                    <a href="?delete=<?php echo $a['id']; ?>" class="btn-outline" style="color: #EF4444; border-color: rgba(239, 68, 68, 0.1); padding: 8px; border-radius: 10px; width: 36px; height: 36px; justify-content: center;" onclick="return confirm('Note: This will remove the notice for all renters. Continue?')">
                                        <i class='bx bx-trash' style="font-size: 18px;"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="right-col">
            <div class="panel">
                <div class="panel-header" style="margin-bottom: 24px;">
                    <h2 style="font-size: 18px; font-weight: 700;">New Announcement</h2>
                </div>
                <form method="POST" style="flex: 1; display: flex; flex-direction: column;">
                    <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                    <div class="form-group">
                        <label>Notice Title</label>
                        <input type="text" name="title" placeholder="e.g. Water Supply Update" required>
                    </div>
                    <div class="form-group">
                        <label>Priority Level</label>
                        <select name="priority">
                            <option value="Low">Low (General)</option>
                            <option value="Normal" selected>Normal</option>
                            <option value="High">High (Important)</option>
                            <option value="Urgent">Urgent (Immediate)</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1; display: flex; flex-direction: column;">
                        <label>Message Content</label>
                        <textarea name="message" placeholder="Type your announcement here..." required style="flex: 1; min-height: 150px; resize: none;"></textarea>
                    </div>
                    <div class="form-group" style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" name="notify_email" id="notify_email" value="yes" style="width: auto; height: auto;" checked>
                        <label for="notify_email" style="margin-bottom:0; font-size: 13px;">Email this announcement to all residents immediately</label>
                    </div>
                    <button type="submit" name="add_announcement" class="btn-primary" style="width: 100%; justify-content: center; padding: 14px; margin-top: auto;">
                        <i class='bx bx-megaphone'></i> Post Announcement
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

</body>
</html>
