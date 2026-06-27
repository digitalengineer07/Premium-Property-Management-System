<?php
// renter/dashboard.php - Redesigned with Unified SaaS UI
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

/* Fetch profile */
$stmt = mysqli_prepare($conn, "SELECT username, name, phone, whatsapp, room_no, profile_pic, must_change_password, pending_adjustment, advance_payment, advance_updated_at, fixed_rent, fixed_maintenance, rent_maint_updated_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$display_name = $user['name'] ?: $user['username'];
$profile_pic = $user['profile_pic'] ?: "assets/img/default-avatar.png";
$room_no = $user['room_no'] ?? 'N/A';

/* Calculate totals */
// 1. Rent from pure 'rent' table
$stmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(rent_amount),0) as total FROM rent WHERE user_id = ? AND status = 'Due'");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$r1 = mysqli_stmt_get_result($stmt);
$r1a = mysqli_fetch_assoc($r1);
$pure_rent_due = (float)($r1a['total'] ?? 0);
mysqli_stmt_close($stmt);

// 2. Electricity and Rent components from 'electricity' table
$stmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(amount),0) as elec_total, IFNULL(SUM(rent_amount + maintenance + dues),0) as rent_portion_total FROM electricity WHERE user_id = ? AND status = 'Due'");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$r2 = mysqli_stmt_get_result($stmt);
$r2a = mysqli_fetch_assoc($r2);
$elec_due = (float)($r2a['elec_total'] ?? 0);
$rent_portion_due = (float)($r2a['rent_portion_total'] ?? 0);
mysqli_stmt_close($stmt);

$rent_due = $pure_rent_due + $rent_portion_due;
$unbilled_adj = (float)($user['pending_adjustment'] ?? 0);
$total_due = $elec_due + $rent_due - $unbilled_adj; // If adj is negative (remaining), it adds to total. If positive, subtracts.


/* Last payment */
$stmt = mysqli_prepare($conn, "SELECT payment_date, total_amount, month FROM payments WHERE user_id = ? ORDER BY id DESC LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$lastp = mysqli_stmt_get_result($stmt);
$last_payment = mysqli_fetch_assoc($lastp);
mysqli_stmt_close($stmt);

/* Fetch Billing Lists */
// Get pure rents
$stmt = mysqli_prepare($conn, "
    SELECT r.id, r.month, r.rent_amount as amount, r.status, p.adjustment_amount, p.adjustment_type 
    FROM rent r 
    LEFT JOIN payments p ON p.bill_type = 'rent' AND p.bill_id = r.id 
    WHERE r.user_id = ? 
    ORDER BY r.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$rent_res = mysqli_stmt_get_result($stmt);
$merged_rents = []; 
while ($row = mysqli_fetch_assoc($rent_res)) {
    $row['source'] = 'rent_table';
    $merged_rents[] = $row;
}
mysqli_stmt_close($stmt);

// Get rent portions from electricity bills (slips)
$stmt = mysqli_prepare($conn, "
    SELECT e.id, e.month, (e.rent_amount + e.maintenance + e.dues) as amount, e.status, p.adjustment_amount, p.adjustment_type 
    FROM electricity e 
    LEFT JOIN payments p ON p.bill_type = 'electricity' AND p.bill_id = e.id 
    WHERE e.user_id = ? AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0) 
    ORDER BY e.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_rent_res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($elec_rent_res)) {
    $row['source'] = 'elec_table';
    $merged_rents[] = $row;
}
mysqli_stmt_close($stmt);

// Get advance payments 
$stmt = mysqli_prepare($conn, "
    SELECT p.id, p.month, p.paid_amount as amount, 'Paid' as status, p.adjustment_amount, p.adjustment_type 
    FROM payments p 
    WHERE p.user_id = ? AND p.bill_type = 'advance'
    ORDER BY p.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$adv_res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($adv_res)) {
    $row['source'] = 'advance';
    $merged_rents[] = $row;
}
mysqli_stmt_close($stmt);

// Sort merged_rents by ID descending to show latest first
usort($merged_rents, function($a, $b) {
    return $b['id'] - $a['id'];
});
// Limit to top 10 after merge
$merged_rents = array_slice($merged_rents, 0, 10);

// Electricity list (only the usage part)
$stmt = mysqli_prepare($conn, "
    SELECT e.id, e.month, e.units_consumed, e.amount, e.total_amount, e.status, p.adjustment_amount, p.adjustment_type 
    FROM electricity e 
    LEFT JOIN payments p ON p.bill_type = 'electricity' AND p.bill_id = e.id 
    WHERE e.user_id = ? 
    ORDER BY e.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$elec_res = mysqli_stmt_get_result($stmt);
$elecs = []; while ($row = mysqli_fetch_assoc($elec_res)) $elecs[] = $row;
mysqli_stmt_close($stmt);

// Calculate advance paid
$stmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(paid_amount), 0) as adv_paid FROM payments WHERE user_id = ? AND bill_type = 'advance'");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$adv_paid_res = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$adv_paid = (float)$adv_paid_res['adv_paid'];
mysqli_stmt_close($stmt);

$advance_due = max(0, ($user['advance_payment'] ?? 0) - $adv_paid);

// Check for recent announcements (last 24h)
$dismissed_cookie_val = $_COOKIE['dismissed_notifs'] ?? '';
$dismissed_ids_arr = $dismissed_cookie_val ? explode(',', $dismissed_cookie_val) : [];
$has_new_notice = false;
$ann_check_q = mysqli_query($conn, "SELECT id FROM announcements WHERE created_at >= NOW() - INTERVAL 1 DAY");
if ($ann_check_q) {
    while($ac = mysqli_fetch_assoc($ann_check_q)) {
        if (!in_array('ann_' . $ac['id'], $dismissed_ids_arr)) {
            $has_new_notice = true;
            break;
        }
    }
}

// Handle Rejection Dismissal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dismiss_rejection'])) {
    $dismiss_id = (int)$_POST['dismiss_id'];
    @mysqli_query($conn, "ALTER TABLE payment_notifications ADD COLUMN is_dismissed TINYINT(1) DEFAULT 0");
    mysqli_query($conn, "UPDATE payment_notifications SET is_dismissed = 1 WHERE id = $dismiss_id AND user_id = $user_id");
    header("Location: dashboard.php");
    exit;
}

// Payment Notification Handling
$payment_success = "";
$payment_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_payment_notif'])) {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $payment_error = "Invalid CSRF token.";
    } else {
        $b_type = $_POST['bill_type'] ?? 'total';
        $b_id = !empty($_POST['bill_id']) ? (int)$_POST['bill_id'] : null;
        $amt = (float)$_POST['amount'];
        $tr_id = trim($_POST['transaction_id'] ?? '');

        if (empty($tr_id)) {
            $payment_error = "Please enter the Transaction ID / UTR.";
        } else {
            // Check for duplicate UTR
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM payment_notifications WHERE transaction_id = ?");
            mysqli_stmt_bind_param($check_stmt, "s", $tr_id);
            mysqli_stmt_execute($check_stmt);
            $check_res = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_res) > 0) {
                $payment_error = "This UTR number has already been submitted. Please check your transaction ID.";
            } else {
                // Ensure table exists (safeguard)
            mysqli_query($conn, "CREATE TABLE IF NOT EXISTS payment_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                bill_type ENUM('rent', 'electricity', 'total', 'advance') NOT NULL,
                bill_id INT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                transaction_id VARCHAR(50) NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'UPI',
                status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
                admin_note TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = mysqli_prepare($conn, "INSERT INTO payment_notifications (user_id, bill_type, bill_id, amount, transaction_id) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "isids", $user_id, $b_type, $b_id, $amt, $tr_id);
            if (mysqli_stmt_execute($stmt)) {
                $payment_success = "Payment notification sent to Admin for verification!";
            } else {
                $payment_error = "Failed to send notification: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
}

function money($v) { return '₹' . number_format((float)$v, 2); }

// Reminder System Logic
$current_day = (int)date('d');
$is_late = ($current_day >= 20);
$overdue_list = [];

if ($total_due > 0) {
    // Collect months that are unpaid
    $due_q = mysqli_query($conn, "SELECT month FROM rent WHERE user_id = $user_id AND status = 'Due' UNION SELECT month FROM electricity WHERE user_id = $user_id AND status = 'Due'");
    while($dq = mysqli_fetch_assoc($due_q)) {
        $overdue_list[] = $dq['month'];
    }
}
$show_banner = ($is_late && !empty($overdue_list));
// Notification System Logic
$unread_notifications = [];
$dismissed_cookie = $_COOKIE['dismissed_notifs'] ?? '';
$dismissed_ids = $dismissed_cookie ? explode(',', $dismissed_cookie) : [];

// 1. Unread Announcements
$ann_q = @mysqli_query($conn, "SELECT id, title, created_at FROM announcements WHERE created_at >= NOW() - INTERVAL 7 DAY ORDER BY created_at DESC");
if ($ann_q) {
    while($a = mysqli_fetch_assoc($ann_q)) {
        $nid = 'ann_' . $a['id'];
        if (in_array($nid, $dismissed_ids)) continue;
        $unread_notifications[] = [
            'id' => $nid,
            'type' => 'announcement',
            'title' => 'New Announcement',
            'message' => $a['title'],
            'time' => $a['created_at'],
            'icon' => 'bx-speaker',
            'color' => '#3B82F6'
        ];
    }
}

// 2. Rejected Payments
@mysqli_query($conn, "ALTER TABLE payment_notifications ADD COLUMN is_dismissed TINYINT(1) DEFAULT 0");
$rej_notif_q = @mysqli_query($conn, "SELECT id, transaction_id, admin_note, amount, created_at FROM payment_notifications WHERE user_id = $user_id AND status = 'Rejected' AND IFNULL(is_dismissed, 0) = 0 ORDER BY id DESC");
if ($rej_notif_q) {
    while($r = mysqli_fetch_assoc($rej_notif_q)) {
        $nid = 'rej_' . $r['id'];
        if (in_array($nid, $dismissed_ids)) continue;
        $unread_notifications[] = [
            'id' => $nid,
            'type' => 'rejection',
            'title' => 'Payment Rejected',
            'message' => '₹' . number_format($r['amount'], 2) . ' (UTR: ' . $r['transaction_id'] . ') ' . (!empty($r['admin_note']) ? '- ' . $r['admin_note'] : ''),
            'time' => $r['created_at'],
            'icon' => 'bx-x-circle',
            'color' => '#EF4444'
        ];
    }
}

// 3. Outstanding Balance
if ($total_due > 0) {
    $nid = 'due_' . date('Y_m');
    if (!in_array($nid, $dismissed_ids)) {
        $unread_notifications[] = [
            'id' => $nid,
            'type' => 'due',
            'title' => 'Payment Due',
            'message' => 'You have an outstanding balance of ₹' . number_format($total_due, 2),
            'time' => date('Y-m-d H:i:s'),
            'icon' => 'bx-wallet',
            'color' => '#F59E0B'
        ];
    }
}

// Sort by time descending
usort($unread_notifications, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

$unread_count = count($unread_notifications);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dashboard | <?php echo HOUSE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- Immediate Theme Setter to prevent flashes -->
    <script>
        window.HOUSE_NAME = <?php echo json_encode(HOUSE_NAME); ?>;
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
        })();
    </script>
    
    <!-- Fonts & Icons -->
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="manifest" href="../manifest.json">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('../sw.js').then(reg => {
            console.log('SW registered');
          }).catch(err => {
            console.log('SW failed', err);
          });
        });
      }
    </script>
    <script src="../assets/js/pwa.js" defer></script>
    
    <style>
        /* New Sidebar Dashboard CSS */
        :root {
            --bg-main: #F8FAFC;
            --sidebar-bg: #FFFFFF;
            --text-dark: #1E293B;
            --text-gray: #64748B;
            --primary-purple: #624BFF;
            --primary-hover: #5039E6;
            --border: #E2E8F0;
            --white: #FFFFFF;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
            
            /* Neons/Accents */
            --accent-red: #FF4B6B;
            --accent-yellow: #F59E0B;
            --accent-purple: #8B5CF6;
            --accent-green: #10B981;
        }

        body {
            font-family: 'Outfit', sans-serif !important;
            background-color: var(--bg-main);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: var(--text-dark);
            display: block !important;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 24px 20px;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }
        .sidebar-logo {
            width: 40px; height: 40px;
            background: #1E293B; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 20px; font-weight: 800;
        }
        .sidebar-brand h2 { font-size: 18px; font-weight: 800; margin: 0; line-height: 1.2; letter-spacing: -0.5px; }
        .sidebar-brand p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }

        .nav-menu { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: 12px;
            color: var(--text-gray); text-decoration: none; font-weight: 600; font-size: 14px;
            transition: all 0.2s ease;
        }
        .nav-item i { font-size: 18px; opacity: 0.8; }
        .nav-item:hover { background: rgba(98, 75, 255, 0.05); color: var(--primary-purple); }
        .nav-item.active { background: var(--primary-purple); color: white; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.3); }
        .nav-item.active i { opacity: 1; }

        .go-mobile-widget {
            background: rgba(98, 75, 255, 0.05);
            border-radius: 16px; padding: 16px; text-align: left;
            margin-top: auto;
        }
        .go-mobile-widget h4 { font-size: 15px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); }
        .go-mobile-widget p { font-size: 12px; color: var(--text-gray); margin-bottom: 12px; line-height: 1.4; }
        .go-mobile-imgs { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .go-mobile-imgs .mock-phone { width: 50px; height: 80px; background: #333; border-radius: 8px; border: 2px solid #111; display: flex; align-items: center; justify-content: center; }
        .go-mobile-imgs .mock-qr { width: 60px; height: 60px; background: white; padding: 4px; border-radius: 6px; }
        .btn-download {
            width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--primary-purple); color: white; border: none; padding: 10px;
            border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 32px 40px;
            max-width: calc(100% - 260px);
            box-sizing: border-box;
        }

        /* Top Header */
        .top-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;
        }
        .header-greeting h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); display: flex; align-items: center; gap: 8px; letter-spacing: -1px; }
        .header-greeting p { font-size: 14px; color: var(--text-gray); font-weight: 500; margin: 0;}
        .header-greeting p span { background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); padding: 2px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; }

        .header-actions { display: flex; align-items: center; gap: 16px; }
        .header-actions .icon-btn {
            width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: white;
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 20px;
            position: relative; cursor: pointer; text-decoration: none; transition: 0.2s;
        }
        .header-actions .icon-btn:hover { background: #f1f5f9; }
        .btn-outline-support {
            border: 1px solid rgba(98, 75, 255, 0.2); background: rgba(98, 75, 255, 0.05); color: var(--primary-purple);
            padding: 10px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; text-decoration: none;
        }
        .user-profile-pill {
            display: flex; align-items: center; gap: 10px; cursor: pointer; padding-left: 8px;
        }
        .user-avatar { width: 36px; height: 36px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; }
        .user-info p { font-size: 11px; color: var(--text-gray); margin: 0; }

        /* Red Reminder Banner */
        .reminder-banner {
            background: linear-gradient(135deg, #FF6B6B, #FF4B6B);
            border-radius: 16px; padding: 24px 32px; color: white;
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px;
            position: relative; overflow: hidden; box-shadow: 0 10px 20px rgba(255, 75, 107, 0.2);
        }
        .reminder-content { display: flex; align-items: center; gap: 20px; z-index: 2; }
        .reminder-icon { width: 56px; height: 56px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #FF4B6B; font-size: 28px; flex-shrink: 0; }
        .reminder-text h3 { font-size: 18px; font-weight: 800; margin: 0 0 4px 0; }
        .reminder-text p { font-size: 14px; opacity: 0.9; margin: 0; font-weight: 500; }
        .reminder-banner .btn-pay-now { background: white; color: #FF4B6B; padding: 12px 24px; border-radius: 12px; font-weight: 700; font-size: 15px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; z-index: 2; transition: transform 0.2s; text-decoration: none;}
        .reminder-banner .btn-pay-now:hover { transform: translateY(-2px); }
        .reminder-bg-art { position: absolute; right: 15%; top: 50%; transform: translateY(-50%); opacity: 0.15; font-size: 140px; z-index: 1; pointer-events: none; }

        /* 3-Col KPI Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
        .kpi-card { background: white; border-radius: 16px; padding: 24px; border: 1px solid var(--border); box-shadow: var(--card-shadow); display: flex; flex-direction: column; position: relative; overflow: hidden; transition: all 0.2s; }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(0,0,0,0.06); }
        .kpi-top { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .kpi-icon-box { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .kpi-icon-box.red { background: rgba(255, 75, 107, 0.1); color: #FF4B6B; }
        .kpi-icon-box.yellow { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .kpi-icon-box.purple { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; }
        .kpi-icon-box.green { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .kpi-title { font-size: 14px; font-weight: 600; color: var(--text-gray); }
        .kpi-amount { font-size: 32px; font-weight: 800; color: var(--text-dark); letter-spacing: -1px; margin-bottom: 16px; }
        .kpi-bottom { display: flex; align-items: center; justify-content: space-between; margin-top: auto; z-index: 2; }
        .kpi-tag { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: flex; align-items: center; gap: 4px; }
        .kpi-tag.alert { background: rgba(255, 75, 107, 0.1); color: #FF4B6B; }
        .kpi-tag.success { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .kpi-due-date { font-size: 12px; font-weight: 600; color: var(--text-gray); display: flex; align-items: center; gap: 4px; }
        .kpi-sparkline { position: absolute; right: 0; bottom: 0; width: 60%; height: 60%; opacity: 0.1; z-index: 1; pointer-events: none; }
        .kpi-sparkline.red { fill: none; stroke: #FF4B6B; stroke-width: 4; stroke-linecap: round; stroke-linejoin: round; }
        .kpi-sparkline.yellow { fill: none; stroke: #F59E0B; stroke-width: 4; stroke-linecap: round; stroke-linejoin: round; }
        .kpi-sparkline.purple { fill: none; stroke: #8B5CF6; stroke-width: 4; stroke-linecap: round; stroke-linejoin: round; }

        /* 3-Col Main Grid */
        .dashboard-3col { display: grid; grid-template-columns: 1.2fr 1.1fr 1.5fr; gap: 24px; margin-bottom: 32px; align-items: stretch; }
        .dash-panel { background: white; border-radius: 16px; padding: 24px; border: 1px solid var(--border); box-shadow: var(--card-shadow); display: flex; flex-direction: column; }
        .panel-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .panel-title { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 800; margin: 0; }
        .panel-link { font-size: 13px; font-weight: 700; color: var(--primary-purple); text-decoration: none; transition: 0.2s; }
        .panel-link:hover { text-decoration: underline; }

        /* Upcoming Bills List */
        .bill-item { border: 1px solid var(--border); border-radius: 12px; padding: 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; }
        .bill-left { display: flex; align-items: center; gap: 12px; }
        .bill-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(255, 75, 107, 0.1); color: #FF4B6B; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink:0; }
        .bill-icon.yellow { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .bill-icon.green { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .bill-info h4 { font-size: 14px; font-weight: 700; margin: 0 0 4px 0; }
        .bill-info p { font-size: 11px; color: var(--text-gray); margin: 0; font-weight: 500; }
        .bill-right { text-align: right; }
        .bill-right h4 { font-size: 15px; font-weight: 800; color: #FF4B6B; margin: 0 0 4px 0; }
        .bill-right p { font-size: 11px; font-weight: 700; color: #FF4B6B; margin: 0; }
        .btn-view-all { width: 100%; padding: 12px; background: white; border: 1px solid var(--primary-purple); color: var(--primary-purple); border-radius: 10px; font-weight: 700; font-size: 13px; cursor: pointer; margin-top: auto; text-decoration: none; display: flex; justify-content: center; transition: 0.2s;}
        .btn-view-all:hover { background: var(--primary-purple); color: white; }

        /* Quick Actions */
        .quick-actions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; flex: 1; }
        .action-card { border: 1px solid var(--border); border-radius: 12px; padding: 16px; text-align: center; text-decoration: none; color: var(--text-dark); transition: all 0.2s; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .action-card:hover { border-color: var(--primary-purple); background: rgba(98, 75, 255, 0.02); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .action-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 12px; }
        .action-card:nth-child(1) .action-icon { background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); }
        .action-card:nth-child(2) .action-icon { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .action-card:nth-child(3) .action-icon { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
        .action-card:nth-child(4) .action-icon { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .action-card h4 { font-size: 13px; font-weight: 700; margin: 0 0 4px 0; }
        .action-card p { font-size: 10px; color: var(--text-gray); margin: 0; }

        /* Recent Transactions */
        .transaction-list { display: flex; flex-direction: column; gap: 16px; }
        .transaction-item { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px dashed var(--border); padding-bottom: 16px; }
        .transaction-item:last-child { border-bottom: none; padding-bottom: 0; }
        .tx-left { display: flex; align-items: center; gap: 12px; }
        .tx-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .tx-icon.up { transform: rotate(45deg); }
        .tx-icon.elec { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .tx-icon.adv { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
        .tx-icon.maint { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; }
        .tx-info h4 { font-size: 14px; font-weight: 700; margin: 0 0 4px 0; }
        .tx-info p { font-size: 11px; color: var(--text-gray); margin: 0; font-weight: 500; }
        .tx-right { display: flex; align-items: center; justify-content: flex-end; gap: 20px; }
        .tx-amount { font-size: 14px; font-weight: 800; color: #10B981; width: 70px; text-align: right; }
        .tx-amount.pending { color: #FF4B6B; }
        .tx-status { font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 6px; width: 60px; text-align: center; }
        .tx-status.paid { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .tx-status.pending { background: rgba(255, 75, 107, 0.1); color: #FF4B6B; }
        .tx-date { font-size: 11px; color: var(--text-gray); font-weight: 500; width: 80px; text-align: right; }

        /* Footer Widgets */
        .footer-widgets { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        .footer-widget { background: white; border: 1px solid var(--border); border-radius: 16px; padding: 20px; display: flex; align-items: center; justify-content: space-between; }
        .fw-left { display: flex; align-items: center; gap: 16px; }
        .fw-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: rgba(98, 75, 255, 0.05); }
        .fw-icon.help { color: var(--primary-purple); }
        .fw-icon.bell { color: var(--primary-purple); }
        .fw-info h4 { font-size: 15px; font-weight: 800; margin: 0 0 4px 0; }
        .fw-info p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }
        .btn-fw { border: 1px solid rgba(98, 75, 255, 0.2); background: transparent; color: var(--primary-purple); padding: 10px 16px; border-radius: 10px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s;}
        .btn-fw:hover { background: rgba(98, 75, 255, 0.1); }

        /* App Footer */
        .app-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 24px; border-top: 1px solid var(--border); margin-top: 20px; }
        .app-footer p { font-size: 12px; color: var(--text-gray); font-weight: 500; margin: 0; }

        /* Mobile overrides */
        @media (max-width: 1400px) {
            .dashboard-3col { grid-template-columns: 1fr 1fr; }
            .dash-panel:nth-child(3) { grid-column: 1 / -1; }
        }
        @media (max-width: 992px) {
            .kpi-grid { grid-template-columns: 1fr 1fr; }
            .sidebar { width: 80px; padding: 24px 10px; }
            .sidebar-brand p, .sidebar-brand h2, .nav-item span, .go-mobile-widget { display: none; }
            .nav-item { justify-content: center; padding: 12px; }
            .nav-item i { font-size: 24px; }
            .main-content { margin-left: 80px; max-width: calc(100% - 80px); }
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; max-width: 100%; padding: 20px; }
            .kpi-grid { grid-template-columns: 1fr; }
            .dashboard-3col { grid-template-columns: 1fr; }
            .dash-panel:nth-child(3) { grid-column: auto; }
            .footer-widgets { grid-template-columns: 1fr; }
            .tx-right { gap: 12px; }
            .tx-date { display: none; }
            .top-header { flex-direction: column; align-items: flex-start; gap: 16px; }
            .header-actions { width: 100%; justify-content: space-between; }
        }
        
        .notification-wrapper { position: relative; }
        #notifDropdown { position: absolute; top: 50px; right: 0; width: 340px; background: white; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid var(--border); z-index: 1000; overflow: hidden; }
    </style>
</head>
<body style="display: block;"> <!-- Overriding body:flex from design-system -->

<main class="main-renter">
    <header class="header-renter">
        <div class="brand-renter">
            <img src="../assets/img/logo.png" alt="Logo" style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover;">
            <span><?php echo HOUSE_NAME; ?></span>
        </div>
        <div class="user-profile" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <!-- Notification Bell -->
            <div class="notification-wrapper" style="position: relative;">
                <div class="bell-icon" onclick="document.getElementById('notifDropdown').style.display = document.getElementById('notifDropdown').style.display === 'none' ? 'flex' : 'none';" style="cursor: pointer; position: relative; padding: 8px;">
                    <i class='bx bxs-bell' style="font-size: 26px; color: var(--text-dark);"></i>
                    <?php if ($unread_count > 0): ?>
                        <span style="position: absolute; top: 4px; right: 4px; background: #EF4444; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 2px var(--bg-main);"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Dropdown -->
                <div id="notifDropdown" style="display: none; position: absolute; top: 100%; right: 0; width: 320px; background: var(--white); border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid var(--border); z-index: 1000; overflow: hidden; max-height: 400px; flex-direction: column;">
                    <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 16px; font-weight: 700; margin: 0;">Notifications</h3>
                        <?php if ($unread_count > 0): ?>
                            <span style="background: rgba(239, 68, 68, 0.1); color: #EF4444; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 600;"><?php echo $unread_count; ?> New</span>
                        <?php endif; ?>
                    </div>
                    <div style="overflow-y: auto; flex: 1; max-height: 300px;">
                        <?php if ($unread_count === 0): ?>
                            <div style="padding: 30px; text-align: center; color: var(--text-gray);">
                                <i class='bx bx-bell-off' style="font-size: 40px; opacity: 0.5; margin-bottom: 10px;"></i>
                                <p style="margin: 0; font-size: 14px;">You're all caught up!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($unread_notifications as $notif): ?>
                                <div class="notif-item" data-id="<?php echo $notif['id']; ?>" style="position: relative; overflow: hidden; border-bottom: 1px solid var(--border);">
                                    <div class="notif-bg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #EF4444; display: flex; align-items: center; justify-content: flex-end; padding-right: 20px; color: white; z-index: 1;">
                                        <i class='bx bx-trash' style="font-size: 24px;"></i>
                                    </div>
                                    <div class="notif-content" style="padding: 16px; display: flex; gap: 12px; transition: transform 0.2s ease-out, background 0.2s; background: var(--white); position: relative; z-index: 2;" onmouseover="this.style.background='var(--bg-main)'" onmouseout="this.style.background='var(--white)'">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $notif['color']; ?>15; color: <?php echo $notif['color']; ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 20px;">
                                            <i class='bx <?php echo $notif['icon']; ?>'></i>
                                        </div>
                                        <div style="flex: 1;">
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                                <div style="font-size: 14px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px;"><?php echo htmlspecialchars($notif['title']); ?></div>
                                                <i class='bx bx-x dismiss-btn' style="font-size: 20px; color: var(--text-gray); cursor: pointer; opacity: 0.6;" onmouseover="this.style.opacity='1'; this.style.color='#EF4444';" onmouseout="this.style.opacity='0.6'; this.style.color='var(--text-gray)';" onclick="event.stopPropagation(); dismissNotification('<?php echo $notif['id']; ?>', this)"></i>
                                            </div>
                                            <div style="font-size: 13px; color: var(--text-gray); line-height: 1.4; margin-bottom: 6px; padding-right: 12px;"><?php echo htmlspecialchars($notif['message']); ?></div>
                                            <div style="font-size: 11px; color: var(--text-gray); opacity: 0.8;">
                                                <i class='bx bx-time-five'></i> <?php 
                                                $time_diff = max(1, time() - strtotime($notif['time']));
                                                if ($time_diff < 3600) echo floor($time_diff/60) . ' mins ago';
                                                elseif ($time_diff < 86400) echo floor($time_diff/3600) . ' hrs ago';
                                                else echo floor($time_diff/86400) . ' days ago';
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <i class='bx bx-moon' id="themeToggle" style="font-size: 24px; cursor: pointer; color: var(--text-gray);"></i>
            <a href="queries.php" class="btn-outline" style="padding: 10px 16px; font-size: 14px; border-color: #FCD34D; color: #B45309; position: relative;">
                <i class='bx bx-help-circle'></i> Help & Support
                <?php if ($has_new_notice): ?>
                    <span id="helpSupportRedDot" style="position: absolute; top: -5px; right: -5px; width: 10px; height: 10px; background: #EF4444; border-radius: 50%; border: 2px solid var(--white); animation: pulse 2s infinite;"></span>
                <?php endif; ?>
            </a>
            <a href="about-dev.php" class="btn-outline" style="padding: 10px 16px; font-size: 14px;"><i class='bx bx-help-circle'></i> About Dev</a>
            <a href="profile.php" class="btn-outline" style="padding: 10px 16px; font-size: 14px;">My Profile</a>
            <a href="../logout.php" class="btn-primary" style="background: #EF4444; padding: 10px 16px; font-size: 14px;">Logout</a>
        </div>
    </header>

    <?php
    // Check if welcome card should be shown (created within 7 days)
    $show_welcome_card = false;
    // ensure table exists logic doesn't crash here if it doesn't exist yet, we suppress error
    $welcome_q = @mysqli_query($conn, "SELECT sent_at FROM welcome_logs WHERE user_id = $user_id AND sent_at >= NOW() - INTERVAL 7 DAY");
    if ($welcome_q && mysqli_num_rows($welcome_q) > 0) {
        $show_welcome_card = true;
    }
    ?>
    <div class="welcome animate-up">
        <h1>Hi, <?php echo htmlspecialchars($display_name); ?></h1>
        <p><?php echo $show_welcome_card ? "Welcome!" : "Welcome back!"; ?> You are assigned to <strong style="color: var(--primary-purple);">Room <?php echo htmlspecialchars($room_no); ?></strong></p>
    </div>

    <?php if ($show_welcome_card): ?>
        <div class="animate-up" style="background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(16, 185, 129, 0.4); color: var(--text-dark); padding: 24px; border-radius: 24px; margin-bottom: 24px; box-shadow: 0 15px 35px rgba(16, 185, 129, 0.15); position: relative; overflow: hidden;">
            <div style="display: flex; align-items: flex-start; gap: 24px; z-index: 2; position: relative;">
                <div style="background: linear-gradient(135deg, #10B981, #34D399); color: white; width: 64px; height: 64px; border-radius: 18px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);">
                    <i class='bx bx-party' style="font-size: 36px;"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="font-size: 22px; font-weight: 800; margin-bottom: 8px; color: #059669;">Welcome to <?php echo htmlspecialchars(HOUSE_NAME); ?>!</h3>
                    <p style="font-size: 15px; font-weight: 500; opacity: 0.9; margin: 0 0 16px 0;">We are thrilled to have you here. Your resident dashboard is your one-stop place to manage your tenancy digitally.</p>
                    <div style="font-size: 14px; background: rgba(255,255,255,0.6); padding: 16px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.8);">
                        <strong style="color: var(--primary-purple); font-size: 15px; display: block; margin-bottom: 8px;">Quick Tips:</strong>
                        <ul style="margin: 0; padding-left: 20px; line-height: 1.6;">
                            <li>Check your outstanding rent & electricity bills in the panels below.</li>
                            <li>You can click the "Pay Now Amount" to settle any pending dues.</li>
                            <li>After making a payment via UPI, enter the Transaction ID/UTR to notify the admin.</li>
                            <li>Need help? Use the "Help & Support" link in the top menu.</li>
                        </ul>
                    </div>
                </div>
                <i class='bx bx-x' style="cursor: pointer; font-size: 28px; opacity: 0.4;" onmouseover="this.style.opacity='1'; this.style.color='#EF4444';" onmouseout="this.style.opacity='0.4'; this.style.color='var(--text-dark)';" onclick="this.parentElement.parentElement.style.display='none'" title="Dismiss"></i>
            </div>
            <i class='bx bxs-heart' style="position: absolute; right: -20px; bottom: -20px; font-size: 160px; color: #10B981; opacity: 0.05; z-index: 1;"></i>
        </div>
    <?php endif; ?>

    <?php if ($payment_success): ?>
        <div class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #DCFCE7;">
            <i class='bx bx-check-circle'></i> <?php echo $payment_success; ?>
        </div>
    <?php endif; ?>
    <?php if ($payment_error): ?>
        <div class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #FEE2E2;">
            <i class='bx bx-error-circle'></i> <?php echo $payment_error; ?>
        </div>
    <?php endif; ?>

    <?php 
    // Ensure column exists silently before querying
    @mysqli_query($conn, "ALTER TABLE payment_notifications ADD COLUMN is_dismissed TINYINT(1) DEFAULT 0");
    
    // Show recently rejected payment notifications
    $rej_q = mysqli_query($conn, "SELECT id, transaction_id, admin_note, amount, DATE_FORMAT(created_at, '%b %d, %Y') as d_date FROM payment_notifications WHERE user_id = $user_id AND status = 'Rejected' AND IFNULL(is_dismissed, 0) = 0 ORDER BY id DESC LIMIT 3");
    if ($rej_q && mysqli_num_rows($rej_q) > 0):
        while ($rej = mysqli_fetch_assoc($rej_q)):
    ?>
        <div class="animate-up" style="background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(239, 68, 68, 0.4); color: var(--text-dark); padding: 16px 20px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 25px rgba(239, 68, 68, 0.1); position: relative; display: flex; gap: 16px; align-items: flex-start;">
            <div style="background: rgba(239, 68, 68, 0.15); width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #DC2626; flex-shrink: 0;">
                <i class='bx bx-x-circle' style="font-size: 24px;"></i>
            </div>
            <div style="flex: 1;">
                <div style="position: absolute; top: 12px; right: 12px;">
                    <form method="POST" style="margin: 0; padding: 0;">
                        <input type="hidden" name="dismiss_id" value="<?php echo $rej['id']; ?>">
                        <button type="submit" name="dismiss_rejection" style="background: none; border: none; cursor: pointer; color: #EF4444; opacity: 0.6; padding: 4px; display: flex; align-items: center; justify-content: center; border-radius: 50%;" onmouseover="this.style.opacity='1'; this.style.background='rgba(239, 68, 68, 0.1)'" onmouseout="this.style.opacity='0.6'; this.style.background='none'">
                            <i class='bx bx-x' style="font-size: 22px;" title="Dismiss Message"></i>
                        </button>
                    </form>
                </div>
                <div style="font-weight: 800; font-size: 16px; margin-bottom: 4px; color: #DC2626; padding-right: 30px;">
                    Payment Rejected (₹<?php echo number_format($rej['amount'], 2); ?> on <?php echo $rej['d_date']; ?>)
                </div>
                <div style="font-size: 14px; font-weight: 500; opacity: 0.9;"><strong>UTR:</strong> <?php echo htmlspecialchars($rej['transaction_id']); ?></div>
                <?php if (!empty($rej['admin_note'])): ?>
                <div style="font-size: 14px; margin-top: 6px; color: #B91C1C; background: rgba(239, 68, 68, 0.05); padding: 8px 12px; border-radius: 8px; border: 1px dashed rgba(239, 68, 68, 0.3);">
                    <strong>Reason:</strong> <?php echo htmlspecialchars($rej['admin_note']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php 
        endwhile;
    endif; 
    ?>

    <?php if ($show_banner): ?>
        <div class="animate-up" style="background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); border: 1px solid rgba(239, 68, 68, 0.4); color: var(--text-dark); padding: 24px; border-radius: 24px; margin-bottom: 32px; box-shadow: 0 15px 35px rgba(239, 68, 68, 0.15); display: flex; align-items: center; gap: 20px; position: relative; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #EF4444, #F87171); width: 60px; height: 60px; border-radius: 16px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 8px 16px rgba(239, 68, 68, 0.3); color: white;">
                <i class='bx bxs-bell-ring bx-tada' style="font-size: 32px;"></i>
            </div>
            <div style="flex: 1; z-index: 2;">
                <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 6px; color: #DC2626;">Payment Reminder!</h3>
                <p style="font-size: 15px; font-weight: 500; opacity: 0.9; margin: 0;">It's the <?php echo date('jS'); ?> of the month. Your bills for <strong><?php echo implode(', ', array_unique($overdue_list)); ?></strong> are still pending. Please clear them to avoid service interruptions.</p>
            </div>
            <button onclick="document.querySelector('.kpi-card .btn-primary').click()" class="btn-primary" style="background: #EF4444; color: white; border: none; border-radius: 16px; padding: 14px 24px; font-weight: 700; font-size: 15px; white-space: nowrap; box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3); z-index: 2; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                Pay Now
            </button>
            <i class='bx bxs-error-circle' style="position: absolute; right: -20px; bottom: -20px; font-size: 140px; color: #EF4444; opacity: 0.05; z-index: 1;"></i>
        </div>
    <?php endif; ?>

    <div class="renter-kpi-grid animate-up">
        <div class="kpi-card hover-lift" style="border-top: 4px solid <?php echo $total_due > 0 ? '#EF4444' : '#10B981'; ?>;">
            <div class="kpi-header" style="display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-credit-card kpi-icon' style="color: #624BFF !important; background: rgba(98, 75, 255, 0.15) !important;"></i>
                <div class="trend <?php echo $total_due > 0 ? 'trend-down' : 'trend-up'; ?>">
                    <i class='bx bx-info-circle'></i> <?php echo $total_due > 0 ? 'Payment Due' : 'All Clear'; ?>
                </div>
            </div>
            <div class="kpi-value" style="<?php echo $total_due > 0 ? 'color: #EF4444;' : ''; ?>"><?php echo money($total_due); ?></div>
            <div class="kpi-label">Total Outstanding</div>
            <?php if ($unbilled_adj != 0): ?>
                <div style="font-size: 11px; margin-top: 2px; font-weight: 600; color: <?php echo $unbilled_adj > 0 ? '#10B981' : '#EF4444'; ?>;">
                    <?php echo $unbilled_adj > 0 ? 'Credit: ' : 'Remaining: '; ?><?php echo money(abs($unbilled_adj)); ?> (Unbilled)
                </div>
            <?php endif; ?>
            
            <?php if($total_due > 0): ?>
                <div style="margin-top: auto; padding-top: 12px;">
                    <button onclick="openPaymentModal(<?php echo $total_due; ?>, 'Total Outstanding Balance', 'total')" class="btn-primary" style="width: 100%; justify-content: center; font-size: 13px; background: #10B981; border: none; border-radius: 12px;">
                        <i class='bx bx-qr-scan'></i> Pay Now Amount
                    </button>
                </div>
            <?php else: ?>
                <div style="margin-top: auto;"></div> <!-- For flex alignment -->
            <?php endif; ?>
        </div>
        
        <div class="kpi-card hover-lift">
            <div class="kpi-header" style="display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-bolt kpi-icon' style="color: #F59E0B !important; background: rgba(245, 158, 11, 0.15) !important;"></i>
            </div>
            <div class="kpi-value"><?php echo money($elec_due); ?></div>
            <div class="kpi-label">Electricity Due</div>
            <div style="margin-top: auto;"></div>
        </div>
        
        <div class="kpi-card hover-lift">
            <div class="kpi-header" style="display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-home kpi-icon' style="color: #EC4899 !important; background: rgba(236, 72, 153, 0.15) !important;"></i>
            </div>
            <div class="kpi-value"><?php echo money($rent_due); ?></div>
            <div class="kpi-label">Rent Due</div>
            <div style="margin-top: auto;"></div>
        </div>
        
        <div class="kpi-card hover-lift">
            <div class="kpi-header" style="display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-check-double kpi-icon' style="color: #10B981 !important; background: rgba(16, 185, 129, 0.15) !important;"></i>
            </div>
            <div class="kpi-value" style="font-size: 18px; margin-top: 5px;"><?php echo $last_payment ? money($last_payment['total_amount']) : 'No history'; ?></div>
            <div class="kpi-label">Last Payment Made</div>
            <div style="margin-top: auto;"></div>
        </div>
        
        <div class="kpi-card hover-lift">
            <div class="kpi-header" style="display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-wallet kpi-icon' style="color: #3B82F6 !important; background: rgba(59, 130, 246, 0.15) !important;"></i>
            </div>
            <div class="kpi-value" style="font-size: 24px;">
                <?php if ($advance_due > 0 && $adv_paid == 0): ?>
                    <?php echo money($user['advance_payment'] ?? 0); ?>
                <?php else: ?>
                    <?php echo money($adv_paid); ?> <span style="font-size: 14px; color: var(--text-gray); font-weight: 500;">/ <?php echo money($user['advance_payment'] ?? 0); ?></span>
                <?php endif; ?>
            </div>
            <div class="kpi-label">Advance Security</div>
            
            <?php if ($advance_due > 0): ?>
                <div style="margin-top: auto; padding-top: 12px;">
                    <button onclick="openPaymentModal(<?php echo $advance_due; ?>, 'Advance Security Deposit', 'advance')" class="btn-primary" style="width: 100%; justify-content: center; font-size: 13px; background: #3B82F6; border: none; border-radius: 12px; height: 36px; padding: 0;">
                        <i class='bx bx-qr-scan'></i> Pay Advance
                    </button>
                </div>
            <?php else: ?>
                <?php if (!empty($user['advance_updated_at'])): ?>
                    <div style="font-size: 11px; margin-top: auto; padding-top: 4px; color: var(--text-gray);">
                        Updated: <?php echo date("M d, Y", strtotime($user['advance_updated_at'])); ?>
                    </div>
                <?php else: ?>
                    <div style="margin-top: auto;"></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($user['fixed_rent'] > 0 || $user['fixed_maintenance'] > 0): ?>
        <div class="kpi-card hover-lift">
            <div class="kpi-header" style="display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-building-house kpi-icon' style="color: #8B5CF6 !important; background: rgba(139, 92, 246, 0.15) !important;"></i>
            </div>
            <div class="kpi-value" style="font-size: 18px; line-height: 1.4;">
                <span style="font-size: 13px; color: var(--text-gray); font-weight: 500;">Rent:</span> <?php echo money($user['fixed_rent'] ?? 0); ?><br>
                <span style="font-size: 13px; color: var(--text-gray); font-weight: 500;">Maint:</span> <?php echo money($user['fixed_maintenance'] ?? 0); ?>
            </div>
            <div class="kpi-label" style="margin-top: 5px;">Fixed Monthly Charges</div>
            <?php if (!empty($user['rent_maint_updated_at'])): ?>
                <div style="font-size: 11px; margin-top: auto; color: var(--text-gray); padding-top: 4px;">
                    Updated: <?php echo date("M d, Y", strtotime($user['rent_maint_updated_at'])); ?>
                </div>
            <?php else: ?>
                <div style="margin-top: auto;"></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-grid-70 animate-up">
        <div class="left-col">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">⚡ Electricity Bill History</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Usage</th>
                                <th>Elec. Bill</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($elecs)): ?>
                                <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-gray);">No bills found.</td></tr>
                            <?php else: foreach ($elecs as $e): ?>
                            <tr>
                                <td data-label="Month" style="font-weight: 600; white-space: nowrap;"><?php echo htmlspecialchars($e['month']); ?></td>
                                <td data-label="Usage"><?php echo (int)$e['units_consumed']; ?> Units</td>
                                <td data-label="Elec. Bill" style="font-weight: 700;">
                                    ₹<?php echo number_format($e['amount'], 2); ?>
                                    <?php if (!empty($e['adjustment_type'])): ?>
                                        <div style="font-size: 10px; font-weight: 600; color: <?php echo $e['adjustment_type'] == 'extra' ? '#10B981' : '#EF4444'; ?>;">
                                            <?php echo $e['adjustment_type'] == 'extra' ? 'Extra' : 'Rem.'; ?>: ₹<?php echo number_format(abs($e['adjustment_amount']), 0); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status"><span class="badge <?php echo $e['status'] == 'Paid' ? 'badge-paid' : 'badge-due'; ?>"><?php echo $e['status']; ?></span></td>
                                <td data-label="Actions">
                                    <div style="display: flex; gap: 8px; flex-wrap: nowrap; min-width: max-content;">
                                        <a href="../admin/slip.php?elec_id=<?php echo $e['id']; ?>" class="btn-outline" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 16px;" target="_blank" title="Receipt"><i class='bx bx-receipt'></i></a>
                                        <?php if($e['status'] != 'Paid'): ?>
                                            <button onclick="openPaymentModal(<?php echo $e['total_amount']; ?>, 'Full Bill (Incl. Rent) - <?php echo $e['month']; ?>', 'electricity', <?php echo $e['id']; ?>)" class="btn-outline" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 16px;" title="Pay Now"><i class='bx bx-credit-card'></i></button>
                                        <?php endif; ?>
                                    </div>
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
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">🏠 Rent History</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($merged_rents)): ?>
                                <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-gray);">No records.</td></tr>
                            <?php else: foreach ($merged_rents as $r): ?>
                            <tr>
                                <td data-label="Month" style="font-weight: 600; white-space: nowrap;"><?php echo htmlspecialchars($r['month']); ?></td>
                                <td data-label="Amount" style="font-weight: 700;">
                                    ₹<?php echo number_format($r['amount'], 2); ?>
                                    <?php if (!empty($r['adjustment_type'])): ?>
                                        <div style="font-size: 10px; font-weight: 600; color: <?php echo $r['adjustment_type'] == 'extra' ? '#10B981' : '#EF4444'; ?>;">
                                            <?php echo $r['adjustment_type'] == 'extra' ? 'Extra' : 'Rem.'; ?>: ₹<?php echo number_format(abs($r['adjustment_amount']), 0); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Status"><span class="badge <?php echo $r['status'] == 'Paid' ? 'badge-paid' : 'badge-due'; ?>"><?php echo $r['status']; ?></span></td>
                                <td data-label="Actions">
                                    <div style="display: flex; gap: 8px; flex-wrap: nowrap; min-width: max-content;">
                                        <?php if($r['source'] == 'elec_table'): ?>
                                            <a href="../admin/slip.php?elec_id=<?php echo $r['id']; ?>" class="btn-outline" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 16px;" target="_blank" title="Receipt"><i class='bx bx-receipt'></i></a>
                                        <?php elseif($r['source'] == 'advance'): ?>
                                            <span style="font-size: 11px; color: var(--text-gray); font-weight: 600; background: rgba(0,0,0,0.05); padding: 4px 8px; border-radius: 8px;">Adv. Pmt</span>
                                        <?php endif; ?>
                                        
                                        <?php if($r['status'] != 'Paid' && $r['source'] != 'advance'): ?>
                                            <?php 
                                            // Determine display and payment amounts
                                            $pay_amt = $r['amount'];
                                            $pay_title = "Rent Bill - " . $r['month'];
                                            $pay_type = "rent";
                                            if ($r['source'] == 'elec_table') {
                                                // If from a slip, we must find the total_amount of that slip to pay properly
                                                $sid = $r['id'];
                                                $sq = mysqli_query($conn, "SELECT total_amount FROM electricity WHERE id = $sid");
                                                $sr = mysqli_fetch_assoc($sq);
                                                $pay_amt = $sr['total_amount'];
                                                $pay_title = "Full Bill (Incl. Elec) - " . $r['month'];
                                                $pay_type = "electricity";
                                            }
                                            ?>
                                            <button onclick="openPaymentModal(<?php echo $pay_amt; ?>, '<?php echo $pay_title; ?>', '<?php echo $pay_type; ?>', <?php echo $r['id']; ?>)" class="btn-outline" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; font-size: 16px;" title="Pay Now"><i class='bx bx-credit-card'></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

    <!-- Intro.js for the guide -->
    <link rel="stylesheet" href="https://unpkg.com/intro.js/minified/introjs.min.css">
    <script src="https://unpkg.com/intro.js/minified/intro.min.js"></script>
    <style>
        .introjs-tooltip { 
            border-radius: 20px; 
            padding: 24px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.15); 
            border: 1px solid rgba(255,255,255,0.6);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            color: #1E293B;
            font-family: 'Inter', system-ui, sans-serif;
            animation: introjs-pulse 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes introjs-pulse {
            0% { transform: scale(0.9) translateY(10px); opacity: 0; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        .dark-theme .introjs-tooltip {
            background: rgba(30, 41, 59, 0.85);
            color: #F8FAFC;
            border-color: rgba(255,255,255,0.1);
        }
        .introjs-tooltiptext { color: inherit; font-size: 14px; line-height: 1.6; opacity: 0.9; }
        .introjs-tooltip-header { padding-bottom: 12px; }
        .introjs-tooltip-title { color: #624BFF; font-weight: 800; font-size: 18px; letter-spacing: -0.5px; }
        .dark-theme .introjs-tooltip-title { color: #A5B4FC; }
        
        .introjs-button { border-radius: 12px; text-shadow: none; box-shadow: none; font-weight: 600; padding: 10px 20px; transition: all 0.2s ease; cursor: pointer; font-size: 13px; }
        .introjs-nextbutton { background: linear-gradient(135deg, #624BFF, #5039E6); color: white; border: none; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.25); }
        .introjs-nextbutton:hover { background: linear-gradient(135deg, #5039E6, #412bd4); box-shadow: 0 6px 16px rgba(98, 75, 255, 0.35); transform: translateY(-1px); color: white; }
        .introjs-prevbutton { background: transparent; color: #64748B; border: 1px solid #E2E8F0; }
        .introjs-prevbutton:hover { background: #F8FAFC; color: #1E293B; }
        .introjs-skipbutton { color: #94A3B8; font-weight: 500; }
        .dark-theme .introjs-button { border: none; }
        .dark-theme .introjs-prevbutton { background: rgba(255,255,255,0.05); color: #CBD5E1; border: 1px solid rgba(255,255,255,0.1); }
        .dark-theme .introjs-prevbutton:hover { background: rgba(255,255,255,0.1); color: #FFF; }
        .dark-theme .introjs-nextbutton { background: linear-gradient(135deg, #624BFF, #412bd4); color: white; }
        
        .introjs-bullets ul li a { background: #CBD5E1; border-radius: 6px; width: 8px; height: 8px; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        .introjs-bullets ul li a.active { background: #624BFF; width: 24px; }
        .dark-theme .introjs-bullets ul li a { background: #475569; }
        .dark-theme .introjs-bullets ul li a.active { background: #818CF8; }
        
        .introjs-helperLayer { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px); border: 2px solid #624BFF; border-radius: 20px; box-shadow: 0 0 0 9999px rgba(15, 23, 42, 0.4); }
        .dark-theme .introjs-helperLayer { background: rgba(0, 0, 0, 0.1); border-color: #818CF8; box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.7); }
    </style>

    <!-- Payment Modal -->
    <div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center; padding: 10px;">
        <div class="panel animate-up" style="max-width: 400px; width: 100%; text-align: center; padding: 20px; max-height: 85vh; overflow-y: auto; border-radius: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h2 style="font-size: 16px; font-weight: 800; color: var(--text-dark);">Make Payment</h2>
                <i class='bx bx-x' onclick="closePaymentModal()" style="font-size: 24px; cursor: pointer; color: var(--text-gray);"></i>
            </div>
            
            <div id="paymentDetails" style="margin-bottom: 16px;">
                <div id="paymentTitle" style="font-weight: 700; font-size: 14px; margin-bottom: 4px; color: var(--text-gray);">Total Outstanding Balance</div>
                <div style="font-size: 26px; font-weight: 800; color: var(--primary-purple); letter-spacing: -0.5px;">₹<span id="paymentAmountDisplay">0</span></div>
            </div>

            <div style="background: white; padding: 15px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 16px; border: 1px solid var(--border);">
                <div style="background: #fff; padding: 8px; border-radius: 12px; display: inline-block; margin-bottom: 10px; border: 1px solid #f0f0f0;">
                    <img id="dynamicQR" src="" alt="UPI QR Code" style="width: 150px; height: 150px; display: block;">
                </div>
                <p style="font-size: 11px; color: #64748b; font-weight: 600;">Scan with any UPI App</p>
                <div style="font-size: 12px; font-weight: 700; color: #1e293b; margin-top: 4px; margin-bottom: 12px;">nikhil119124-1@oksbi</div>
                
                <a id="upiDeepLinkBtn" href="#" class="btn-primary" style="display: none; background: linear-gradient(135deg, #10B981, #059669); border: none; font-size: 13px; padding: 12px; justify-content: center; width: 100%; border-radius: 12px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                    <i class='bx bx-mobile-alt' style="font-size: 16px;"></i> Pay directly on your phone
                </a>
            </div>

            <div style="background: rgba(98, 75, 255, 0.04); padding: 10px; border-radius: 12px; border: 1px solid rgba(98, 75, 255, 0.1); margin-bottom: 16px;">
                <p style="font-size: 10px; color: var(--primary-purple); font-weight: 700; text-transform: uppercase; margin-bottom: 2px;">
                    <i class='bx bx-timer'></i> Session Expires in <span id="paymentTimer">05:00</span>
                </p>
                <p style="font-size: 9px; color: var(--text-gray); line-height: 1.4;">Transfer within this time to ensure amount accuracy.</p>
            </div>

            <form method="POST" id="paymentNotifyForm" style="text-align: left; border-top: 1px solid var(--border); padding-top: 16px;">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                <input type="hidden" name="bill_type" id="hiddenBillType">
                <input type="hidden" name="bill_id" id="hiddenBillId">
                <input type="hidden" name="amount" id="hiddenAmount">
                
                <label style="font-size: 11px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 6px;">Enter Transaction ID / UTR</label>
                <input type="text" name="transaction_id" placeholder="Enter 12-digit UTR No." required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 12px; background: var(--bg-main); color: var(--text-dark); outline: none; font-size: 13px;">
                
                <button type="submit" id="submitPaymentBtn" name="submit_payment_notif" class="btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 13px;">
                    <i class='bx bx-bell'></i> Notify Admin
                </button>
            </form>

            <script>
            document.getElementById('paymentNotifyForm').addEventListener('submit', function(e) {
                let btn = document.getElementById('submitPaymentBtn');
                if (btn.disabled) {
                    e.preventDefault();
                    return;
                }
                // Don't prevent default, we want the form to submit
                setTimeout(() => {
                    btn.disabled = true;
                    btn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Submitting...";
                }, 10);
            });
            </script>

            <div style="border-top: 1px solid var(--border); padding-top: 15px; margin-top: 16px;">
                <p style="font-size: 11px; color: var(--text-gray); margin-bottom: 10px;">Having issues? Use the permanent scanner:</p>
                <button onclick="openScannerModal()" class="btn-outline" style="width: 100%; justify-content: center; font-size: 11px; padding: 8px;">
                    <i class='bx bx-qr-scan'></i> Open Owner's Scanner
                </button>
            </div>
        </div>
    </div>

    <!-- Owner Scanner Modal -->
    <div id="scannerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; align-items: center; justify-content: center; padding: 20px;">
        <div class="panel animate-up" style="max-width: 400px; width: 100%; text-align: center; padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="font-size: 18px; font-weight: 800; color: var(--text-dark);">Owner's Scanner</h2>
                <i class='bx bx-x' onclick="closeScannerModal()" style="font-size: 26px; cursor: pointer; color: var(--text-gray);"></i>
            </div>
            <div style="background: white; padding: 10px; border-radius: 20px; margin-bottom: 15px;">
                <img src="../assets/img/gpay-qr.jpg" alt="Owner Scanner" style="width: 100%; border-radius: 12px; display: block;">
            </div>
            <p style="font-size: 12px; color: var(--text-gray);">Fixed GPay scanner for manual amount entry.</p>
        </div>
    </div>
</main>

    <script>
        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notifDropdown');
            const bell = document.querySelector('.bell-icon');
            if (dropdown && dropdown.style.display === 'flex') {
                if (!dropdown.contains(event.target) && !bell.contains(event.target)) {
                    dropdown.style.display = 'none';
                }
            }
        });

        // Notification Dismissal & Swipe Logic
        function setCookie(name, value, days) {
            let expires = "";
            if (days) {
                let date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "")  + expires + "; path=/";
        }

        function getCookie(name) {
            let nameEQ = name + "=";
            let ca = document.cookie.split(';');
            for(let i=0;i < ca.length;i++) {
                let c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
            }
            return null;
        }

        function dismissNotification(id, el) {
            let item = el.closest('.notif-item');
            if (item) {
                item.style.height = item.offsetHeight + 'px';
                item.style.transition = 'all 0.3s';
                item.style.transform = 'translateX(-100%)';
                
                setTimeout(() => {
                    item.style.height = '0px';
                    item.style.padding = '0px';
                    item.style.border = 'none';
                    setTimeout(() => item.remove(), 300);
                }, 300);
            }
            
            // Save to cookie
            let currentStr = getCookie('dismissed_notifs');
            let currentIds = currentStr ? currentStr.split(',') : [];
            if (!currentIds.includes(id)) {
                currentIds.push(id);
                setCookie('dismissed_notifs', currentIds.join(','), 30);
            }
            
            // Update badge
            let badge = document.querySelector('.bell-icon span');
            if (badge) {
                let count = parseInt(badge.innerText) - 1;
                if (count <= 0) {
                    badge.remove();
                    // show empty state if all removed
                    let container = document.querySelector('#notifDropdown > div:nth-child(2)');
                    if (container && document.querySelectorAll('.notif-item').length <= 1) {
                        setTimeout(() => {
                            container.innerHTML = `<div style="padding: 30px; text-align: center; color: var(--text-gray);">
                                <i class='bx bx-bell-off' style="font-size: 40px; opacity: 0.5; margin-bottom: 10px;"></i>
                                <p style="margin: 0; font-size: 14px;">You're all caught up!</p>
                            </div>`;
                        }, 600);
                    }
                } else {
                    badge.innerText = count;
                }
            }
            
            let countLabel = document.querySelector('#notifDropdown span[style*="background: rgba(239, 68, 68, 0.1)"]');
            if (countLabel) {
                let count = parseInt(countLabel.innerText) - 1;
                if (count <= 0) countLabel.remove();
                else countLabel.innerText = count + ' New';
            }
            
            // Hide Help & Support red dot if all announcements are dismissed
            if (id.startsWith('ann_')) {
                let remainingAnns = Array.from(document.querySelectorAll('.notif-item')).filter(el => el.getAttribute('data-id') && el.getAttribute('data-id').startsWith('ann_'));
                if (remainingAnns.length <= 1) {
                    let redDot = document.getElementById('helpSupportRedDot');
                    if (redDot) redDot.style.display = 'none';
                }
            }
        }

        // Swipe to delete logic
        document.querySelectorAll('.notif-item').forEach(item => {
            let startX = 0;
            let currentX = 0;
            let content = item.querySelector('.notif-content');
            
            item.addEventListener('touchstart', e => {
                startX = e.touches[0].clientX;
                content.style.transition = 'none';
            }, {passive: true});
            
            item.addEventListener('touchmove', e => {
                currentX = e.touches[0].clientX;
                let diff = currentX - startX;
                if (diff < 0) { // Only allow swiping left
                    content.style.transform = `translateX(${diff}px)`;
                }
            }, {passive: true});
            
            item.addEventListener('touchend', e => {
                let diff = currentX - startX;
                content.style.transition = 'transform 0.2s ease-out';
                if (diff < -80) { // threshold
                    content.style.transform = `translateX(-100%)`;
                    setTimeout(() => {
                        dismissNotification(item.getAttribute('data-id'), item);
                    }, 200);
                } else {
                    content.style.transform = `translateX(0)`;
                }
            });
        });
    </script>
    <script src="../assets/js/renter.js?v=<?php echo time(); ?>"></script>

</body>
</html>