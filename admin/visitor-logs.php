<?php
// admin/visitor-logs.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$total_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM login_logs");
$total_row = mysqli_fetch_assoc($total_q);
$total_records = $total_row['cnt'];
$total_pages = ceil($total_records / $limit);

// Fetch paginated logs
$logs = mysqli_query($conn, "
    SELECT l.*, u.name, u.room_no 
    FROM login_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.login_time DESC 
    LIMIT $limit OFFSET $offset
");

$admin_user = s($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visitor Logs | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <style>
        .timeline-container {
            position: relative;
            max-width: 800px;
            margin: 0 auto 40px auto;
            padding: 20px 0;
        }
        /* Vertical line */
        .timeline-container::before {
            content: '';
            position: absolute;
            top: 20px;
            bottom: 20px;
            left: 24px;
            width: 2px;
            background: #E2E8F0;
            border-radius: 2px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 24px;
            padding-left: 70px;
            display: block; /* for search filtering */
        }
        .timeline-icon {
            position: absolute;
            left: 5px;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 12px; /* modern squircle */
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            z-index: 1;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            background: #ffffff;
            border: 2px solid #ffffff;
        }
        .timeline-content {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #F1F5F9;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .timeline-content:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border-color: #E2E8F0;
        }
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px dashed #E2E8F0;
        }
        .timeline-user {
            font-weight: 800;
            font-size: 16px;
            color: #0F172A;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .timeline-time {
            font-size: 13px;
            color: #64748B;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            background: #F8FAFC;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #F1F5F9;
        }
        .timeline-body {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .timeline-ip {
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: monospace;
            font-size: 13px;
            color: #475569;
            background: #F1F5F9;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 500;
        }
        .timeline-status {
            color: #10B981; 
            font-size: 13px; 
            font-weight: 600; 
            display: flex; 
            align-items: center; 
            gap: 6px; 
            margin-left: auto;
            background: #ECFDF5;
            padding: 6px 12px;
            border-radius: 8px;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .timeline-container::before {
                left: 14px;
            }
            .timeline-icon {
                left: -6px;
            }
            .timeline-item {
                padding-left: 50px;
            }
            .timeline-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .timeline-time {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up" style="margin-bottom: 40px; text-align: center;">
        <h1 style="font-size: 36px; font-weight: 900; color: #0F172A; margin-bottom: 8px;">Visitor Logs</h1>
        <p style="color: #64748B; font-size: 16px;">Tracking the last 200 login events for transparency</p>
    </div>

    <div class="timeline-container animate-up" id="logTable">
        <?php while($row = mysqli_fetch_assoc($logs)): ?>
        <?php 
            $isAdmin = ($row['user_type'] == 'admin');
            $icon = $isAdmin ? 'bx-shield-quarter' : 'bx-user';
            $color = $isAdmin ? '#624BFF' : '#10B981';
            $bg = $isAdmin ? 'rgba(98,75,255,0.1)' : 'rgba(16,185,129,0.1)';
        ?>
        <div class="timeline-item">
            <div class="timeline-icon" style="color: <?php echo $color; ?>; background: <?php echo $bg; ?>; border-color: <?php echo $bg; ?>;">
                <i class='bx <?php echo $icon; ?>'></i>
            </div>
            <div class="timeline-content">
                <div class="timeline-header">
                    <div class="timeline-user">
                        <?php 
                        if ($isAdmin) echo "Administrator";
                        else echo htmlspecialchars($row['name'] ?? 'Unknown User') . " <span style='color: #94A3B8; font-weight: 500; font-size: 14px;'>— Room " . ($row['room_no'] ?? 'N/A') . "</span>";
                        ?>
                    </div>
                    <div class="timeline-time">
                        <i class='bx bx-log-in-circle' style="font-size: 16px; color: #10B981;"></i> <?php echo date('M d, Y • g:i A', strtotime($row['login_time'])); ?>
                        <i class='bx bx-right-arrow-alt' style="color: #CBD5E1; margin: 0 4px;"></i>
                        <?php if ($row['logout_time']): ?>
                            <i class='bx bx-log-out-circle' style="font-size: 16px; color: #EF4444;"></i> <?php echo date('g:i A', strtotime($row['logout_time'])); ?>
                        <?php else: ?>
                            <span style="color: #10B981; font-weight: 700; font-size: 12px; display: flex; align-items: center; gap: 4px;"><span style="display: inline-block; width: 6px; height: 6px; background: #10B981; border-radius: 50%; box-shadow: 0 0 0 2px rgba(16,185,129,0.2);"></span> Active Now</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="timeline-body">
                    <span class="badge" style="background: <?php echo $isAdmin ? '#EEF2FF' : '#ECFDF5'; ?>; color: <?php echo $isAdmin ? '#4F46E5' : '#10B981'; ?>; padding: 6px 12px; font-size: 12px; border-radius: 8px; font-weight: 700; display: flex; align-items: center; justify-content: center; border: none;">
                        <?php echo ucfirst($row['user_type']); ?>
                    </span>
                    <div class="timeline-ip">
                        <i class='bx bx-laptop' style="font-size: 15px; color: #94A3B8;"></i> <?php echo $row['ip_address']; ?>
                    </div>
                    <div class="timeline-status">
                        <i class='bx bxs-check-circle' style="font-size: 16px;"></i> Success
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <div style="display: flex; justify-content: center; align-items: center; gap: 16px; margin-bottom: 40px;">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" class="btn-outline" style="padding: 10px 24px; border-radius: 12px; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-left-arrow-alt' style="font-size: 20px;"></i> Previous
            </a>
        <?php endif; ?>
        
        <div style="font-weight: 600; color: #64748B; font-size: 14px;">Page <?php echo $page; ?> of <?php echo $total_pages ?: 1; ?></div>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="btn-primary" style="padding: 10px 24px; border-radius: 12px; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                Next <i class='bx bx-right-arrow-alt' style="font-size: 20px;"></i>
            </a>
        <?php endif; ?>
    </div>
</main>

<script>
    document.getElementById('logFilter')?.addEventListener('keyup', function(e) {
        let term = e.target.value.toLowerCase();
        let rows = document.querySelectorAll('.timeline-item');
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(term) ? 'block' : 'none';
        });
    });
</script>

</body>
</html>
