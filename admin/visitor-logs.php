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
        .premium-timeline {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
        }
        
        .pt-item {
            display: flex;
            position: relative;
            gap: 24px;
            margin-bottom: 24px;
        }

        /* The Continuous Vertical Line */
        .pt-item::before {
            content: '';
            position: absolute;
            left: 25px; /* Center of the 52px dot */
            top: 52px;  /* Start below the dot */
            bottom: -24px; /* Reach the next item */
            width: 2px;
            background: linear-gradient(to bottom, #E2E8F0, #F1F5F9);
            z-index: 0;
            border-radius: 2px;
        }
        /* Hide line on last item */
        .pt-item:last-child::before {
            display: none;
        }

        /* The Premium Dot/Icon */
        .pt-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px; /* Squircle */
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            z-index: 1;
            flex-shrink: 0;
            background: #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.04);
            border: 2px solid #ffffff;
            transition: transform 0.3s ease;
        }
        .pt-item:hover .pt-icon {
            transform: scale(1.05);
        }

        .pt-icon.admin {
            background: rgba(98, 75, 255, 0.1);
            color: #624BFF;
            border-color: rgba(98, 75, 255, 0.05);
        }
        .pt-icon.resident {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
            border-color: rgba(16, 185, 129, 0.05);
        }

        /* The Content Card (Horizontal Layout) */
        .pt-card {
            flex: 1;
            background: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 20px 24px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            position: relative;
            overflow: hidden;
            
            /* Inner layout */
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pt-card:hover {
            box-shadow: 0 12px 30px rgba(0,0,0,0.06);
            transform: translateY(-2px);
            border-color: #CBD5E1;
        }

        .pt-left {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .pt-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .log-user {
            font-size: 16px;
            font-weight: 800;
            color: #0F172A;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .log-role {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .log-role.admin { background: #EEF2FF; color: #4F46E5; }
        .log-role.resident { background: #ECFDF5; color: #10B981; }

        .log-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #64748B;
        }
        .log-meta .bx { font-size: 16px; opacity: 0.7; }
        .log-time {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #F8FAFC;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #F1F5F9;
        }
        .log-time.active { 
            color: #10B981; 
            background: #ECFDF5;
            border-color: #D1FAE5;
        }
        .pulse {
            width: 8px; height: 8px; background: #10B981; border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
            animation: pulse 1.5s infinite;
            margin-right: 2px;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .log-ip {
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            color: #475569;
            background: #F1F5F9;
            padding: 6px 12px;
            border-radius: 8px;
        }
        .log-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 700;
            color: #10B981;
        }

        /* Mobile */
        @media (max-width: 768px) {
            .pt-item {
                gap: 16px;
            }
            .pt-item::before {
                left: 20px;
                top: 40px;
            }
            .pt-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
                border-radius: 12px;
            }
            .pt-card {
                flex-direction: column;
                align-items: flex-start;
                padding: 16px;
            }
            .pt-right {
                align-items: flex-start;
                margin-top: 16px;
                padding-top: 16px;
                border-top: 1px dashed #E2E8F0;
                width: 100%;
                flex-direction: row;
                justify-content: space-between;
            }
            .log-meta {
                flex-wrap: wrap;
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

    <div class="premium-timeline animate-up" id="logTable">
        <?php while($row = mysqli_fetch_assoc($logs)): ?>
        <?php 
            $isAdmin = ($row['user_type'] == 'admin');
            $iconClass = $isAdmin ? 'bx-shield-quarter' : 'bx-user';
            $bgClass = $isAdmin ? 'admin' : 'resident';
        ?>
        <div class="pt-item">
            <!-- Timeline Icon -->
            <div class="pt-icon <?php echo $bgClass; ?>">
                <i class='bx <?php echo $iconClass; ?>'></i>
            </div>
            
            <!-- Content Card -->
            <div class="pt-card">
                <div class="pt-left">
                    <div class="log-user">
                        <?php 
                        if ($isAdmin) echo "Administrator";
                        else echo htmlspecialchars($row['name'] ?? 'Unknown User') . " <span style='color: #94A3B8; font-weight: 500; font-size: 14px;'>— Room " . ($row['room_no'] ?? 'N/A') . "</span>";
                        ?>
                        <span class="log-role <?php echo $isAdmin ? 'admin' : 'resident'; ?>">
                            <?php echo ucfirst($row['user_type']); ?>
                        </span>
                    </div>
                    <div class="log-meta">
                        <span class="log-time">
                            <i class='bx bx-log-in-circle' style="color: #64748B;"></i> <?php echo date('M d, Y • g:i A', strtotime($row['login_time'])); ?>
                        </span>
                        <i class='bx bx-right-arrow-alt' style="color: #CBD5E1;"></i>
                        <?php if ($row['logout_time']): ?>
                            <span class="log-time">
                                <i class='bx bx-log-out-circle' style="color: #64748B;"></i> <?php echo date('M d, Y • g:i A', strtotime($row['logout_time'])); ?>
                            </span>
                        <?php else: ?>
                            <span class="log-time active">
                                <span class="pulse"></span> Active Now
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="pt-right">
                    <div class="log-status">
                        <i class='bx bxs-check-circle'></i> Success
                    </div>
                    <div class="log-ip">
                        <i class='bx bx-laptop'></i> <?php echo $row['ip_address']; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Pagination -->
    <div style="display: flex; justify-content: center; align-items: center; gap: 12px; margin-bottom: 40px; margin-top: 40px; position: relative; z-index: 10;">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 14px; border: 1px solid var(--border); color: var(--text-gray); text-decoration: none; background: #fff; transition: 0.2s;" onmouseover="this.style.borderColor='var(--text-gray)'" onmouseout="this.style.borderColor='var(--border)'">
                <i class='bx bx-chevron-left' style="font-size: 24px;"></i>
            </a>
        <?php endif; ?>
        
        <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 14px; background: var(--primary-purple); color: #fff; font-weight: 700; font-size: 16px; box-shadow: 0 8px 20px rgba(98, 75, 255, 0.3);">
            <?php echo $page; ?>
        </div>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border-radius: 14px; border: 1px solid var(--border); color: var(--text-gray); text-decoration: none; background: #fff; transition: 0.2s;" onmouseover="this.style.borderColor='var(--text-gray)'" onmouseout="this.style.borderColor='var(--border)'">
                <i class='bx bx-chevron-right' style="font-size: 24px;"></i>
            </a>
        <?php endif; ?>
    </div>
</main>

<script>
    document.getElementById('logFilter')?.addEventListener('keyup', function(e) {
        let term = e.target.value.toLowerCase();
        let rows = document.querySelectorAll('.pt-item');
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(term) ? 'flex' : 'none';
        });
    });
</script>

</body>
</html>
