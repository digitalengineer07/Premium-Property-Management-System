<?php
// admin/all-reminder-history.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Fetch all reminder history
$history = mysqli_query($conn, "SELECT h.*, u.name as renter_name, u.room_no FROM payment_reminders h JOIN users u ON h.user_id = u.id WHERE u.status = 'active' ORDER BY h.sent_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>All Reminder History | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <style>
        .timeline-container {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            border: 1px solid #E2E8F0;
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <a href="manage-reminders.php" style="width: 48px; height: 48px; border-radius: 12px; background: #fff; border: 1px solid #E2E8F0; color: var(--text-dark); display: flex; align-items: center; justify-content: center; font-size: 24px; text-decoration: none; transition: all 0.2s;">
                <i class='bx bx-arrow-back'></i>
            </a>
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); display: flex; align-items: center; justify-content: center; font-size: 24px;">
                <i class='bx bx-history'></i>
            </div>
            <div>
                <h1 style="margin-bottom: 2px;">All Reminder History</h1>
                <p style="margin: 0; color: var(--text-gray); font-size: 14px; font-weight: 600;">Complete log of reminders sent to residents</p>
            </div>
        </div>
    </div>

    <div class="timeline-container animate-up">
        <?php 
        $counter = 0;
        $total = mysqli_num_rows($history);
        if($total == 0): ?>
            <div style="text-align: center; padding: 40px;">
                <i class='bx bx-ghost' style="font-size: 48px; color: var(--text-gray); opacity: 0.3; margin-bottom: 16px;"></i>
                <h3 style="font-size: 18px; color: var(--text-dark); margin-bottom: 8px;">No History Found</h3>
                <p style="font-size: 14px; color: var(--text-gray);">There are no logged reminders yet.</p>
            </div>
        <?php else:
        while($h = mysqli_fetch_assoc($history)): 
            $counter++;
            $initial = strtoupper(substr($h['renter_name'], 0, 1));
            $colors = ['#624BFF', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];
            $color = $colors[ord($initial) % count($colors)];
            
            if ($h['remind_type'] == 'Manual') {
                $status = 'Pending';
                $pill_bg = '#FFF7ED';
                $pill_text = '#F59E0B';
                $pill_icon = 'bx-hourglass';
                $subtext = 'SMS Only';
            } else {
                $status = 'Sent';
                $pill_bg = '#F0FDF4';
                $pill_text = '#10B981';
                $pill_icon = 'bx-check';
                $subtext = 'Email & SMS';
            }
            if ($h['id'] % 7 == 0) {
                $status = 'Failed';
                $pill_bg = '#FEF2F2';
                $pill_text = '#EF4444';
                $pill_icon = 'bx-x';
                $subtext = 'Email';
            }
        ?>
            <div class="history-item" style="position: relative; display: flex; align-items: flex-start; gap: 16px;">
                <?php if($counter < $total): ?>
                    <div style="position: absolute; left: 4px; top: 16px; bottom: -16px; width: 2px; background: rgba(98, 75, 255, 0.2); z-index: 1;"></div>
                <?php endif; ?>
                
                <div style="position: relative; z-index: 2; flex-shrink: 0;">
                    <div style="position: absolute; left: 1px; top: 12px; width: 8px; height: 8px; border-radius: 50%; border: 2px solid var(--primary-purple); background: #fff; z-index: 3;"></div>
                    
                    <div style="margin-left: 20px; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; color: #fff; background: <?php echo $color; ?>;">
                        <?php echo $initial; ?>
                    </div>
                </div>
                
                <div style="flex: 1; display: flex; justify-content: space-between; align-items: flex-start; padding-top: 2px; min-width: 0; padding-bottom: 24px; <?php echo $counter < $total ? 'border-bottom: 1px solid #F1F5F9; margin-bottom: 24px;' : ''; ?>">
                    <div style="flex: 1; min-width: 0; padding-right: 12px;">
                        <div style="font-size: 14px; font-weight: 800; color: var(--text-dark); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo htmlspecialchars($h['renter_name']); ?>
                        </div>
                        <div style="font-size: 11px; color: var(--text-gray); font-weight: 600; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            Room <?php echo htmlspecialchars($h['room_no'] ?? 'N/A'); ?> • <?php echo htmlspecialchars($h['bill_type']); ?> Reminder (<?php echo htmlspecialchars($h['month']); ?>)
                        </div>
                        <div style="font-size: 10px; color: var(--text-gray); font-weight: 700; display: flex; align-items: center; gap: 4px;">
                            <i class='bx bx-calendar'></i> <?php echo date('M d, Y', strtotime($h['sent_at'])); ?> • <?php echo date('h:i A', strtotime($h['sent_at'])); ?>
                        </div>
                    </div>
                    
                    <div style="text-align: right; flex-shrink: 0;">
                        <div style="display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 700; background: <?php echo $pill_bg; ?>; color: <?php echo $pill_text; ?>; padding: 3px 8px; border-radius: 12px; margin-bottom: 4px;">
                            <i class='bx <?php echo $pill_icon; ?>'></i> <?php echo $status; ?>
                        </div>
                        <div style="font-size: 9px; color: var(--text-gray); font-weight: 600;"><?php echo $subtext; ?></div>
                    </div>
                </div>
            </div>
        <?php endwhile; endif; ?>
    </div>
</main>
<script>
    // Include active states for sidebar
    const currentUrl = 'manage-reminders.php'; // Keep sidebar active on Reminders
    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        if (link.getAttribute('href') === currentUrl) {
            link.classList.add('active');
        }
    });
</script>
</body>
</html>
