<?php
// admin/visitor-logs.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Fetch all logs with user names
$logs = mysqli_query($conn, "
    SELECT l.*, u.name, u.room_no 
    FROM login_logs l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.login_time DESC 
    LIMIT 200
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
        /* Mobile Premium Timeline Design (Image Match) */
        @media (max-width: 768px) {
            .table-responsive {
                border: none !important;
                background: transparent !important;
                padding: 0 !important;
            }
            .table-responsive table, .table-responsive tbody, .table-responsive th, .table-responsive td, .table-responsive tr {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
            .table-responsive thead {
                display: none;
            }
            
            /* Timeline Vertical Line */
            .table-responsive tbody {
                position: relative;
            }
            .table-responsive tbody::before {
                content: '';
                position: absolute;
                top: 0;
                bottom: 0;
                left: 20px;
                width: 2px;
                background: rgba(255, 255, 255, 0.05);
            }

            /* Timeline Card Animation */
            @keyframes slideUpFade {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Timeline Card */
            .table-responsive tr {
                display: grid;
                grid-template-columns: 1fr auto;
                grid-template-areas: 
                    "name badge"
                    "time time"
                    "desc desc";
                gap: 8px 12px;
                position: relative;
                background: #0f172a;
                border: 1px solid rgba(255, 255, 255, 0.05);
                border-radius: 12px;
                margin-bottom: 24px;
                padding: 20px;
                margin-left: 54px;
                margin-right: 16px; /* Space on the right */
                width: auto;
                
                /* Animations */
                opacity: 0;
                animation: slideUpFade 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            }
            
            /* Staggering the animation for a cascading effect */
            .table-responsive tr:nth-child(1) { animation-delay: 0.05s; }
            .table-responsive tr:nth-child(2) { animation-delay: 0.1s; }
            .table-responsive tr:nth-child(3) { animation-delay: 0.15s; }
            .table-responsive tr:nth-child(4) { animation-delay: 0.2s; }
            .table-responsive tr:nth-child(5) { animation-delay: 0.25s; }
            .table-responsive tr:nth-child(6) { animation-delay: 0.3s; }
            .table-responsive tr:nth-child(7) { animation-delay: 0.35s; }
            .table-responsive tr:nth-child(8) { animation-delay: 0.4s; }
            .table-responsive tr:nth-child(n+9) { animation-delay: 0.45s; }
            
            /* Touch/Hover Interaction */
            .table-responsive tr:active {
                transform: scale(0.98);
                box-shadow: 0 4px 20px rgba(16, 185, 129, 0.1);
                border-color: rgba(16, 185, 129, 0.3);
            }

            /* Timeline Dot & Icon */
            .table-responsive tr::before {
                content: '';
                position: absolute;
                top: 18px;
                left: -47px;
                width: 28px;
                height: 28px;
                border-radius: 50%;
                background-color: #0f172a;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2310B981' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4'%3E%3C/path%3E%3Cpolyline points='10 17 15 12 10 7'%3E%3C/polyline%3E%3Cline x1='15' y1='12' x2='3' y2='12'%3E%3C/line%3E%3C/svg%3E");
                background-size: 14px;
                background-position: center;
                background-repeat: no-repeat;
                box-shadow: 0 0 0 4px #0f172a;
                z-index: 1;
                transition: transform 0.2s ease, background-color 0.2s ease;
            }
            
            /* Dot Interaction */
            .table-responsive tr:active::before {
                transform: scale(1.1);
                background-color: #1e293b;
            }

            /* Reset td styles */
            .table-responsive td {
                border: none !important;
                padding: 0 !important;
                display: flex;
                align-items: center;
                text-align: left;
            }
            .table-responsive td::before {
                content: none !important;
            }
            
            /* User Name */
            .table-responsive td:nth-of-type(1) {
                grid-area: name;
                font-size: 16px;
                font-weight: 700;
                color: #ffffff;
                justify-content: flex-start;
            }
            
            /* Badge */
            .table-responsive td:nth-of-type(2) {
                grid-area: badge;
                justify-content: flex-end;
            }
            .table-responsive td:nth-of-type(2) .badge {
                background: #D1FAE5 !important;
                color: #065F46 !important;
                font-weight: 700 !important;
                font-size: 11px !important;
                padding: 4px 10px !important;
                border-radius: 20px !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
                border: none !important;
            }
            
            /* Login Time */
            .table-responsive td:nth-of-type(3) {
                grid-area: time;
                font-size: 13px;
                color: #9ca3af !important;
                justify-content: flex-start;
                margin-bottom: 6px;
                display: flex;
                align-items: center;
            }
            .table-responsive td:nth-of-type(3)::before {
                content: '';
                display: inline-block;
                width: 14px;
                height: 14px;
                margin-right: 6px;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='10'%3E%3C/circle%3E%3Cpolyline points='12 6 12 12 16 14'%3E%3C/polyline%3E%3C/svg%3E");
                background-size: cover;
            }
            
            /* IP Address (Description) */
            .table-responsive td:nth-of-type(4) {
                grid-area: desc;
                font-size: 14px !important;
                font-family: inherit !important;
                color: #e5e7eb;
                justify-content: flex-start;
                line-height: 1.5;
            }
            .table-responsive td:nth-of-type(4)::before {
                content: "Logged in from IP: " !important;
                color: #e5e7eb;
                margin-right: 4px;
                font-weight: normal;
                text-transform: none;
                letter-spacing: normal;
                font-size: 14px;
            }
            
            /* Status */
            .table-responsive td:nth-of-type(5) {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <header class="header">
        <div class="header-content">
            <div class="search-bar">
                <i class='bx bx-search'></i>
                <input type="text" id="logFilter" placeholder="Filter by name or device...">
            </div>
            <div class="user-profile">
                <i class='bx bx-moon' id="themeToggle"></i>
            </div>
        </div>
    </header>

    <div class="welcome animate-up">
        <h1>Detailed Visitor Logs</h1>
        <p>Tracking the last 200 login events for transparency</p>
    </div>

    <div class="panel animate-up">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Type</th>
                        <th>Login Time</th>
                        <th>IP Address</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="logTable">
                    <?php while($row = mysqli_fetch_assoc($logs)): ?>
                    <tr>
                        <td style="font-weight: 600;">
                            <?php 
                            if ($row['user_type'] == 'admin') echo "Administrator";
                            else echo htmlspecialchars($row['name'] ?? 'Unknown User') . " (Room " . ($row['room_no'] ?? 'N/A') . ")";
                            ?>
                        </td>
                        <td>
                            <span class="badge" style="background: <?php echo $row['user_type'] == 'admin' ? '#EEF2FF' : '#ECFDF5'; ?>; color: <?php echo $row['user_type'] == 'admin' ? '#4F46E5' : '#10B981'; ?>;">
                                <?php echo ucfirst($row['user_type']); ?>
                            </span>
                        </td>
                        <td style="color: var(--text-gray);">
                            <?php echo date('M d, Y | H:i:s', strtotime($row['login_time'])); ?>
                        </td>
                        <td style="font-family: monospace; font-size: 13px;">
                            <?php echo $row['ip_address']; ?>
                        </td>
                        <td>
                            <span style="display: flex; align-items: center; gap: 6px; font-size: 13px; color: #10B981;">
                                <i class='bx bxs-circle' style='font-size: 8px;'></i> Success
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    document.getElementById('logFilter')?.addEventListener('keyup', function(e) {
        let term = e.target.value.toLowerCase();
        let rows = document.querySelectorAll('#logTable tr');
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
</script>

</body>
</html>
