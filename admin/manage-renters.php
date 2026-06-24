<?php
// admin/manage-renters.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$query = trim($_GET['q'] ?? '');
$room = trim($_GET['room'] ?? '');

$filter_status = $_GET['status'] ?? 'active';

// KPI Queries
$kpi_res = mysqli_query($conn, "SELECT 
    COUNT(id) as total_residents, 
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_residents,
    SUM(CASE WHEN status = 'moved_out' THEN 1 ELSE 0 END) as inactive_residents,
    COUNT(DISTINCT room_no) as total_rooms
    FROM users");
$kpi = mysqli_fetch_assoc($kpi_res);
$total_residents = $kpi['total_residents'] ?: 0;
$active_residents = $kpi['active_residents'] ?: 0;
$inactive_residents = $kpi['inactive_residents'] ?: 0;
$total_rooms = $kpi['total_rooms'] ?: 0;

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Count total rows for pagination
$count_sql = "SELECT COUNT(id) as total FROM users WHERE status = ?";
$count_params = [$filter_status]; $count_types = "s";
if ($query !== '') {
    $count_sql .= " AND (username LIKE ? OR name LIKE ?)";
    $qlike = "%{$query}%"; $count_params[] = $qlike; $count_params[] = $qlike; $count_types .= "ss";
}
if ($room !== '') {
    $count_sql .= " AND room_no LIKE ?";
    $rlike = "%{$room}%"; $count_params[] = $rlike; $count_types .= "s";
}
$count_stmt = mysqli_prepare($conn, $count_sql);
if ($count_params) mysqli_stmt_bind_param($count_stmt, $count_types, ...$count_params);
mysqli_stmt_execute($count_stmt);
$total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt))['total'];
mysqli_stmt_close($count_stmt);

$total_pages = ceil($total_rows / $limit);

$sql = "SELECT id, username, name, phone, email, room_no, profile_pic, last_login, agreement_expiry_date, fixed_rent, fixed_maintenance, joining_date, status FROM users WHERE status = ?";
$params = [$filter_status]; $types = "s";
if ($query !== '') {
    $sql .= " AND (username LIKE ? OR name LIKE ?)";
    $params[] = $qlike; $params[] = $qlike; $types .= "ss";
}
if ($room !== '') {
    $sql .= " AND room_no LIKE ?";
    $params[] = $rlike; $types .= "s";
}
$sql .= " ORDER BY name ASC LIMIT ? OFFSET ?";
$params[] = $limit; $params[] = $offset; $types .= "ii";

$stmt = mysqli_prepare($conn, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$users = []; while ($row = mysqli_fetch_assoc($res)) $users[] = $row;
mysqli_stmt_close($stmt);

$admin_user = htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Manage Residents | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <style>
        /* Card View for Residents is specific to this page list */
        @media (max-width: 1024px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
        @media (max-width: 480px) {
            .kpi-grid { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 768px) {
            .panel { background: transparent !important; box-shadow: none !important; padding: 0 !important; border: none !important; margin: 0 !important; width: 100% !important; }
            .panel-header { background: var(--white); border-radius: 16px; padding: 16px !important; margin-bottom: 16px; border: 1px solid var(--border); box-shadow: var(--card-shadow); width: 100% !important; }
            
            .table-responsive { overflow: visible !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }

            /* Card View for Residents */
            table, thead, tbody, th, td, tr { display: block !important; width: 100% !important; }
            thead { display: none !important; }
            
            #renterTable tr {
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 18px;
                padding: 16px;
                margin-bottom: 16px;
                box-shadow: var(--card-shadow);
                width: 100% !important;
            }

            #renterTable td {
                padding: 0 !important;
                border: none !important;
                margin-bottom: 12px;
                width: 100% !important;
            }

            #renterTable td:nth-child(1) { 
                margin-bottom: 16px;
                border-bottom: 1px dotted var(--border) !important;
                padding-bottom: 12px !important;
            }

            #renterTable td:nth-child(2) { 
                display: inline-block !important;
                width: auto !important;
                margin-bottom: 12px;
            }

            #renterTable td:nth-child(3) { 
                display: inline-block !important;
                float: right !important;
                width: auto !important;
                margin-bottom: 12px;
                margin-top: 2px;
            }

            #renterTable td:last-child { 
                clear: both;
                margin-top: 8px; 
            }
            #renterTable td:last-child div {
                display: grid !important;
                grid-auto-flow: row !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 8px !important;
                width: 100% !important;
            }

            #renterTable td:last-child div a, 
            #renterTable td:last-child div button {
                padding: 12px 8px !important;
                font-size: 13px !important;
                justify-content: center !important;
                width: 100% !important;
                margin: 0 !important;
                display: flex !important;
                align-items: center !important;
            }

            #renterTable td:last-child div a:last-child {
                grid-column: span 2;
                background: var(--bg-main) !important;
                border: 1px solid var(--border) !important;
            }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="page-header-container animate-up" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 20px;">
        <div class="welcome" style="margin: 0;">
            <h1 style="font-size: 32px; font-weight: 800; letter-spacing: -1px; color: var(--text-dark); margin: 0 0 8px 0;">
                Manage Residents
            </h1>
            <p style="font-size: 15px; color: var(--text-gray); margin: 0;">View, manage and organize all residents in your property</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="add-renter.php" class="btn-primary" style="padding: 10px 20px; border-radius: 10px; font-weight: 600;">
                <i class='bx bx-plus'></i> Add New Resident
            </a>
            <a href="manage-renters.php" class="btn-outline" style="padding: 10px 20px; border-radius: 10px; border: 1px solid var(--border); font-weight: 600;">
                <i class='bx bx-list-ul'></i> View All Residents
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid animate-up" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
        <div class="aesthetic-card" style="padding: 20px; display: flex; align-items: center; gap: 20px;">
            <div class="kpi-icon" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(98, 75, 255, 0.1); color: #624BFF; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                <i class='bx bx-user'></i>
            </div>
            <div>
                <p style="margin: 0 0 4px 0; font-size: 13px; color: var(--text-gray); font-weight: 600;">Total Residents</p>
                <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: var(--text-dark); line-height: 1;"><?php echo $total_residents; ?></h3>
                <p style="margin: 6px 0 0 0; font-size: 11px; color: var(--text-gray);">All registered residents</p>
            </div>
        </div>

        <div class="aesthetic-card" style="padding: 20px; display: flex; align-items: center; gap: 20px;">
            <div class="kpi-icon" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                <i class='bx bx-check-circle'></i>
            </div>
            <div>
                <p style="margin: 0 0 4px 0; font-size: 13px; color: var(--text-gray); font-weight: 600;">Active Residents</p>
                <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: var(--text-dark); line-height: 1;"><?php echo $active_residents; ?></h3>
                <p style="margin: 6px 0 0 0; font-size: 11px; color: var(--text-gray);">Currently staying</p>
            </div>
        </div>

        <div class="aesthetic-card" style="padding: 20px; display: flex; align-items: center; gap: 20px;">
            <div class="kpi-icon" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); color: #F59E0B; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                <i class='bx bx-pause-circle'></i>
            </div>
            <div>
                <p style="margin: 0 0 4px 0; font-size: 13px; color: var(--text-gray); font-weight: 600;">Inactive Residents</p>
                <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: var(--text-dark); line-height: 1;"><?php echo $inactive_residents; ?></h3>
                <p style="margin: 6px 0 0 0; font-size: 11px; color: var(--text-gray);">Not active</p>
            </div>
        </div>

        <div class="aesthetic-card" style="padding: 20px; display: flex; align-items: center; gap: 20px;">
            <div class="kpi-icon" style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59, 130, 246, 0.1); color: #3B82F6; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
                <i class='bx bx-door-open'></i>
            </div>
            <div>
                <p style="margin: 0 0 4px 0; font-size: 13px; color: var(--text-gray); font-weight: 600;">Total Rooms</p>
                <h3 style="margin: 0; font-size: 24px; font-weight: 800; color: var(--text-dark); line-height: 1;"><?php echo $total_rooms; ?></h3>
                <p style="margin: 6px 0 0 0; font-size: 11px; color: var(--text-gray);">In all blocks</p>
            </div>
        </div>
    </div>

    <div class="panel animate-up">
        <div class="panel-header" style="padding-bottom: 0; display: flex; flex-wrap: nowrap; gap: 24px; justify-content: space-between; align-items: flex-end; border-bottom: 1px solid var(--border); margin-bottom: 20px; overflow-x: auto;">
            <div style="display: flex; gap: 24px; white-space: nowrap; flex-shrink: 0;">
                <a href="?status=active" style="padding: 12px 0; color: <?php echo $filter_status === 'active' ? '#624BFF' : 'var(--text-gray)'; ?>; border-bottom: 2px solid <?php echo $filter_status === 'active' ? '#624BFF' : 'transparent'; ?>; text-decoration: none; font-weight: 600; white-space: nowrap;">Active Residents</a>
                <a href="?status=moved_out" style="padding: 12px 0; color: <?php echo $filter_status === 'moved_out' ? '#624BFF' : 'var(--text-gray)'; ?>; border-bottom: 2px solid <?php echo $filter_status === 'moved_out' ? '#624BFF' : 'transparent'; ?>; text-decoration: none; font-weight: 600; white-space: nowrap;">Past Residents</a>
            </div>
            
            <form method="GET" class="filter-form" style="display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: nowrap; flex: 1; justify-content: flex-end; min-width: 400px;">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                
                <div style="position: relative; flex: 1; max-width: 350px;">
                    <i class='bx bx-search' style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94A3B8;"></i>
                    <input name="q" placeholder="Search by name, phone or email..." value="<?php echo htmlspecialchars($query); ?>" style="width: 100%; padding: 10px 16px 10px 42px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; outline: none; background: #F8FAFC; min-width: 150px;">
                </div>
                
                <div style="position: relative; width: 110px;">
                    <input name="room" placeholder="Room" value="<?php echo htmlspecialchars($room); ?>" style="width: 100%; padding: 10px 30px 10px 16px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; outline: none; background: #F8FAFC;">
                    <i class='bx bx-chevron-down' style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8;"></i>
                </div>

                <button type="button" class="btn-outline" style="padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); font-size: 13px; color: var(--text-dark); background: transparent; display: flex; align-items: center; gap: 6px;">
                    <i class='bx bx-filter-alt'></i> Filter
                </button>
                
                <button type="submit" class="btn-primary" style="padding: 10px 24px; border-radius: 10px; font-size: 13px; background: #624BFF;">Search</button>
            </form>
        </div>

        <div id="deleteAlert" style="display:none; background: #FEF2F2; color: #EF4444; padding: 12px; border-radius: 12px; margin-bottom: 16px; border: 1px solid #FEE2E2;"></div>

        <div class="table-responsive">
            <table style="border-collapse: separate; border-spacing: 0 12px; width: 100%;">
                <thead>
                    <tr>
                        <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94A3B8; font-weight: 700; border-bottom: none; padding-bottom: 0; text-align: left;">Resident <i class='bx bx-sort-alt-2'></i></th>
                        <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94A3B8; font-weight: 700; border-bottom: none; padding-bottom: 0; text-align: left;">Room</th>
                        <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94A3B8; font-weight: 700; border-bottom: none; padding-bottom: 0; text-align: left;">Rent (₹)</th>
                        <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94A3B8; font-weight: 700; border-bottom: none; padding-bottom: 0; text-align: left;">Status</th>
                        <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94A3B8; font-weight: 700; border-bottom: none; padding-bottom: 0; text-align: left;">Added On</th>
                        <th style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #94A3B8; font-weight: 700; border-bottom: none; padding-bottom: 0; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody id="renterTable">
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-gray); background: #FFFFFF; border-radius: 12px;">No residents found.</td></tr>
                    <?php else: foreach ($users as $u): ?>
                    <tr style="background: #FFFFFF; box-shadow: 0 2px 10px rgba(0,0,0,0.02); transition: all 0.2s ease;">
                        <td style="padding: 16px; border-radius: 12px 0 0 12px;">
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <?php if ($u['profile_pic']): ?>
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background-image: url('../<?php echo htmlspecialchars($u['profile_pic']); ?>'); background-size: cover; background-position: center; border: 2px solid #F8FAFC;"></div>
                                <?php else: ?>
                                    <?php 
                                        $initials = '';
                                        $nameParts = explode(' ', $u['name']);
                                        if (isset($nameParts[0])) $initials .= strtoupper(substr($nameParts[0], 0, 1));
                                        if (isset($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));
                                    ?>
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #F4F7FF; color: #624BFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; border: 2px solid #FFFFFF; box-shadow: 0 2px 5px rgba(98, 75, 255, 0.1);"><?php echo $initials ?: '?'; ?></div>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-dark); font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;" title="<?php echo htmlspecialchars($u['name']); ?>"><?php echo htmlspecialchars($u['name']); ?></div>
                                    <div style="font-size: 12px; color: #64748B; margin-top: 2px; display: flex; align-items: center; gap: 6px;">
                                        <?php echo htmlspecialchars($u['phone']); ?>
                                        <i class='bx bx-envelope' style="font-size: 14px; color: #94A3B8; cursor: pointer;" title="<?php echo htmlspecialchars($u['email']); ?>"></i>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 16px; font-weight: 700; color: var(--text-dark); font-size: 14px;">
                            <?php echo htmlspecialchars($u['room_no']); ?>
                        </td>
                        <td style="padding: 16px;">
                            <div style="font-size: 12px;">
                                <span style="color: #10B981; font-weight: 600;">Rent:</span> ₹<?php echo number_format($u['fixed_rent'], 2); ?> <br>
                                <span style="color: #F59E0B; font-weight: 600;">Maint:</span> ₹<?php echo number_format($u['fixed_maintenance'], 2); ?>
                            </div>
                        </td>
                        <td style="padding: 16px;">
                            <?php if ($u['status'] === 'active'): ?>
                                <span style="padding: 6px 12px; background: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    <div style="width: 6px; height: 6px; background: #10B981; border-radius: 50%;"></div> Active
                                </span>
                            <?php else: ?>
                                <span style="padding: 6px 12px; background: #FEF2F2; color: #EF4444; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    <div style="width: 6px; height: 6px; background: #EF4444; border-radius: 50%;"></div> Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 16px; color: #64748B; font-size: 13px; font-weight: 500;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <i class='bx bx-calendar' style="font-size: 16px; color: #94A3B8;"></i>
                                <?php echo $u['joining_date'] ? date('d M Y', strtotime($u['joining_date'])) : 'N/A'; ?>
                            </div>
                        </td>
                        <td style="padding: 16px; border-radius: 0 12px 12px 0;">
                            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <a href="view-renter.php?id=<?php echo $u['id']; ?>" style="width: 32px; height: 32px; border-radius: 8px; background: rgba(98, 75, 255, 0.1); color: #624BFF; display: flex; align-items: center; justify-content: center; font-size: 16px; text-decoration: none;" title="View Profile">
                                    <i class='bx bx-user'></i>
                                </a>
                                <?php if ($u['status'] === 'active'): ?>
                                <a href="bill-generator.php?user_id=<?php echo $u['id']; ?>" style="height: 32px; padding: 0 12px; border-radius: 8px; background: rgba(98, 75, 255, 0.1); color: #624BFF; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; text-decoration: none; gap: 4px;" title="Generate Bill">
                                    <i class='bx bx-receipt'></i> Bill
                                </a>
                                <button onclick="resetPassword(<?php echo $u['id']; ?>, '<?php echo addslashes($u['name']); ?>')" style="width: 32px; height: 32px; border-radius: 8px; background: rgba(245, 158, 11, 0.1); color: #F59E0B; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px;" title="Change Password">
                                    <i class='bx bx-lock-alt'></i>
                                </button>
                                <button onclick="moveOutRenter(<?php echo $u['id']; ?>, '<?php echo addslashes($u['name']); ?>')" style="width: 32px; height: 32px; border-radius: 8px; background: #F8FAFC; color: #64748B; border: 1px solid var(--border); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px;" title="Move Out Renter">
                                    <i class='bx bx-exit'></i>
                                </button>
                                <?php endif; ?>
                                <button onclick="deleteRenter(<?php echo $u['id']; ?>, '<?php echo addslashes($u['name']); ?>')" style="width: 32px; height: 32px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); color: #EF4444; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px;" title="Delete Resident">
                                    <i class='bx bx-trash'></i>
                                </button>
                                <?php if ($u['status'] === 'active'): ?>
                                <a href="../onboarding-guide.php?id=<?php echo $u['id']; ?>" target="_blank" style="height: 32px; padding: 0 12px; border-radius: 8px; border: 1px solid var(--border); color: #64748B; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; text-decoration: none;" title="Download Guide">
                                    Guide
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; flex-wrap: wrap; gap: 16px;">
            <div style="color: var(--text-gray); font-size: 13px;">
                Showing <?php echo $total_rows > 0 ? $offset + 1 : 0; ?> to <?php echo min($offset + $limit, $total_rows); ?> of <?php echo $total_rows; ?> residents
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div style="display: flex; gap: 8px;">
                <?php
                // Preserve query string
                $qs = $_GET;
                
                // Prev button
                if ($page > 1) {
                    $qs['page'] = $page - 1;
                    echo '<a href="?' . http_build_query($qs) . '" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--border); color: var(--text-dark); text-decoration: none; font-size: 14px;"><i class="bx bx-chevron-left"></i></a>';
                } else {
                    echo '<div style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--border); color: #CBD5E1; font-size: 14px; opacity: 0.5;"><i class="bx bx-chevron-left"></i></div>';
                }

                // Page numbers
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i == $page) {
                        echo '<div style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: #624BFF; color: #FFFFFF; font-weight: 600; font-size: 14px; box-shadow: 0 4px 10px rgba(98,75,255,0.2);">' . $i . '</div>';
                    } else {
                        if ($total_pages > 5 && $i > 3 && $i < $total_pages && abs($i - $page) > 1) {
                            if ($i == 4 && $page > 4) echo '<div style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; color: var(--text-gray);">...</div>';
                            if ($i == $total_pages - 1 && $page < $total_pages - 3) echo '<div style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; color: var(--text-gray);">...</div>';
                            continue;
                        }
                        
                        $qs['page'] = $i;
                        echo '<a href="?' . http_build_query($qs) . '" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: #F8FAFC; color: var(--text-dark); text-decoration: none; font-size: 14px; font-weight: 500;">' . $i . '</a>';
                    }
                }

                // Next button
                if ($page < $total_pages) {
                    $qs['page'] = $page + 1;
                    echo '<a href="?' . http_build_query($qs) . '" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--border); color: var(--text-dark); text-decoration: none; font-size: 14px;"><i class="bx bx-chevron-right"></i></a>';
                } else {
                    echo '<div style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 10px; border: 1px solid var(--border); color: #CBD5E1; font-size: 14px; opacity: 0.5;"><i class="bx bx-chevron-right"></i></div>';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

    <!-- Password Reset Modal -->
    <div id="passwordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
        <div class="panel animate-up" style="max-width: 400px; width: 100%; padding: 32px; background: var(--white);">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 64px; height: 64px; background: rgba(245, 158, 11, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class='bx bx-lock-alt' style="font-size: 32px; color: #F59E0B;"></i>
                </div>
                <h3 style="font-size: 20px; font-weight: 800; color: var(--text-dark);">Reset Password</h3>
                <p id="resetUsername" style="color: var(--text-gray); font-size: 14px; margin-top: 4px;">Set a new password for resident.</p>
            </div>
            
            <input type="hidden" id="resetUserId">
            <div class="form-group" style="margin-bottom: 24px;">
                <label>New Password</label>
                <div style="position: relative;">
                    <input type="password" id="newPasswordInput" placeholder="Min 6 characters" value="123456" class="pwd-input" style="width: 100%; padding-right: 40px;">
                    <i class='bx bx-hide pwd-toggle' style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--text-gray); cursor: pointer; font-size: 20px;"></i>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px;">
                <button onclick="closePasswordModal()" class="btn-outline" style="justify-content: center;">Cancel</button>
                <button onclick="submitPasswordReset()" class="btn-primary" style="justify-content: center; background: #6366F1;">Update Password</button>
            </div>
        </div>
    </div>

    <!-- Move Out Modal -->
    <div id="moveOutModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
        <div class="panel animate-up" style="max-width: 400px; width: 100%; padding: 32px; background: var(--white);">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 64px; height: 64px; background: rgba(100, 116, 139, 0.1); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <i class='bx bx-exit' style="font-size: 32px; color: #64748B;"></i>
                </div>
                <h3 style="font-size: 20px; font-weight: 800; color: var(--text-dark);">Move Out Resident</h3>
                <p id="moveOutUsername" style="color: var(--text-gray); font-size: 14px; margin-top: 4px;">They will be archived but history is saved.</p>
            </div>
            
            <input type="hidden" id="moveOutUserId">
            <div class="form-group" style="margin-bottom: 24px;">
                <label>Move Out Date</label>
                <input type="date" id="moveOutDateInput" class="btn-outline" style="width: 100%; text-align: left; cursor: text;" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px;">
                <button onclick="closeMoveOutModal()" class="btn-outline" style="justify-content: center;">Cancel</button>
                <button onclick="submitMoveOut()" class="btn-primary" style="justify-content: center; background: #64748B;">Confirm Move Out</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center; padding: 20px; opacity: 0; transition: opacity 0.3s ease;">
        <div class="panel" style="max-width: 360px; width: 100%; padding: 32px; background: var(--white); text-align: center; transform: scale(0.8); transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
            <div style="width: 72px; height: 72px; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class='bx bx-check' style="font-size: 40px; color: #10B981;"></i>
            </div>
            <h3 style="font-size: 22px; font-weight: 800; color: var(--text-dark); margin-bottom: 12px;">Success!</h3>
            <p id="successMessageText" style="color: var(--text-gray); font-size: 15px; line-height: 1.5; margin-bottom: 24px;"></p>
            <button onclick="closeSuccessModal()" class="btn-primary" style="width: 100%; justify-content: center; background: #10B981; border: none; padding: 12px; font-size: 15px;">Continue</button>
        </div>
    </div>

<script>
    const CSRF_TOKEN = '<?php echo getCsrfToken(); ?>';
    
    // Custom Success Modal Logic
    let successCallback = null;
    function showSuccessMessage(msg, callback) {
        document.getElementById('successMessageText').textContent = msg;
        const modal = document.getElementById('successModal');
        const panel = modal.querySelector('.panel');
        modal.style.display = 'flex';
        // Trigger reflow
        void modal.offsetWidth;
        modal.style.opacity = '1';
        panel.style.transform = 'scale(1)';
        successCallback = callback;
    }

    function closeSuccessModal() {
        const modal = document.getElementById('successModal');
        const panel = modal.querySelector('.panel');
        modal.style.opacity = '0';
        panel.style.transform = 'scale(0.8)';
        setTimeout(() => {
            modal.style.display = 'none';
            if (successCallback) successCallback();
        }, 300);
    }

    document.getElementById('renterFilter')?.addEventListener('keyup', function(e) {
        let term = e.target.value.toLowerCase();
        let rows = document.querySelectorAll('#renterTable tr');
        rows.forEach(row => {
            let name = row.innerText.toLowerCase();
            row.style.display = name.includes(term) ? '' : 'none';
        });
    });

    let currentResetId = null;

    function resetPassword(id, name) {
        currentResetId = id;
        document.getElementById('resetUserId').value = id;
        document.getElementById('resetUsername').textContent = `Set a new password for ${name}`;
        document.getElementById('newPasswordInput').value = "123456";
        document.getElementById('passwordModal').style.display = 'flex';
    }

    function closePasswordModal() {
        document.getElementById('passwordModal').style.display = 'none';
        currentResetId = null;
    }

    async function submitPasswordReset() {
        const id = document.getElementById('resetUserId').value;
        const newPass = document.getElementById('newPasswordInput').value;
        
        if (!newPass) {
            alert("Password cannot be empty");
            return;
        }

        try {
            const res = await fetch('reset-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&new_password=${encodeURIComponent(newPass)}&csrf=${CSRF_TOKEN}`
            });
            const data = await res.json();
            if (data.success) {
                alert("Password updated successfully!");
                closePasswordModal();
            } else {
                alert(data.message);
            }
        } catch (e) {
            alert("Network error occurred.");
        }
    }

    function moveOutRenter(id, name) {
        document.getElementById('moveOutUserId').value = id;
        document.getElementById('moveOutUsername').textContent = `Archive resident ${name}?`;
        document.getElementById('moveOutDateInput').value = new Date().toISOString().split('T')[0];
        document.getElementById('moveOutModal').style.display = 'flex';
    }

    function closeMoveOutModal() {
        document.getElementById('moveOutModal').style.display = 'none';
    }

    async function submitMoveOut() {
        const id = document.getElementById('moveOutUserId').value;
        const moveDate = document.getElementById('moveOutDateInput').value;
        
        if (!moveDate) {
            alert("Move Out Date is required");
            return;
        }

        try {
            const res = await fetch('ajax_move_out.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${id}&move_out_date=${encodeURIComponent(moveDate)}`
            });
            const data = await res.json();
            if (data.success) {
                closeMoveOutModal();
                showSuccessMessage(data.message, () => {
                    location.reload();
                });
            } else {
                alert(data.error);
            }
        } catch (e) {
            alert("Network error occurred.");
        }
    }

    async function deleteRenter(id, name) {
        if (!confirm(`Are you sure you want to PERMANENTLY delete ${name}? This will also delete all their bills and cannot be undone.`)) return;
        
        try {
            const res = await fetch('delete-renter.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&csrf=${CSRF_TOKEN}`
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                const alert = document.getElementById('deleteAlert');
                alert.textContent = data.message;
                alert.style.display = 'block';
            }
        } catch (e) {
            alert("Network error occurred.");
        }
    }

    document.querySelectorAll('.pwd-toggle').forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if(input) {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('bx-show');
                this.classList.toggle('bx-hide');
            }
        });
    });
</script>

</body>
</html>
