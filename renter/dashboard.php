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
    SELECT r.id, r.month, r.rent_amount as amount, r.status, p.adjustment_amount, p.adjustment_type, p.payment_date 
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
    SELECT e.id, e.month, (e.rent_amount + e.maintenance + e.dues) as amount, e.status, p.adjustment_amount, p.adjustment_type, p.payment_date 
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
    SELECT p.id, p.month, p.paid_amount as amount, 'Paid' as status, p.adjustment_amount, p.adjustment_type, p.payment_date 
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
    SELECT e.id, e.month, e.units_consumed, e.amount, e.total_amount, e.status, p.adjustment_amount, p.adjustment_type, p.payment_date 
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
            --bg-main: #FAFBFC;
            --sidebar-bg: #FFFFFF;
            --text-dark: #0F172A;
            --text-gray: #64748B;
            --primary-purple: #624BFF;
            --primary-hover: #5039E6;
            --border: #F1F5F9;
            --white: #FFFFFF;
            --card-shadow: 0 4px 24px rgba(0, 0, 0, 0.03);
            
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
            width: 230px;
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
            background: #1E293B; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 20px; font-weight: 800;
        }
        .sidebar-brand h2 { font-size: 18px; font-weight: 800; margin: 0; line-height: 1.2; letter-spacing: -0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
        .sidebar-brand p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }

        .nav-menu { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: 12px;
            color: var(--text-gray); text-decoration: none; font-weight: 600; font-size: 14px;
            transition: all 0.2s ease;
        }
        .nav-item i { font-size: 18px; opacity: 0.8; }
        .nav-item:hover { background: rgba(98, 75, 255, 0.03); color: var(--primary-purple); }
        .nav-item.active { background: var(--primary-purple); color: white; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.25); }
        .nav-item.active i { opacity: 1; }

        .go-mobile-widget {
            background: rgba(98, 75, 255, 0.03); border: 1px solid rgba(98, 75, 255, 0.05);
            border-radius: 16px; padding: 16px; text-align: left;
            margin-top: auto;
        }
        .go-mobile-widget h4 { font-size: 15px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); }
        .go-mobile-widget p { font-size: 12px; color: var(--text-gray); margin-bottom: 12px; line-height: 1.4; }
        .go-mobile-imgs { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .go-mobile-imgs .mock-phone { width: 50px; height: 80px; background: #333; border-radius: 8px; border: 2px solid #111; display: flex; align-items: center; justify-content: center; }
        .go-mobile-imgs .mock-qr { width: 60px; height: 60px; background: white; padding: 4px; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .btn-download {
            width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--primary-purple); color: white; border: none; padding: 10px;
            border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; transition: 0.2s;
        }
        .btn-download:hover { background: var(--primary-hover); transform: translateY(-1px); }

        .main-content {
            flex: 1;
            margin-left: 230px;
            padding: 32px 40px;
            max-width: calc(100% - 230px);
            box-sizing: border-box;
        }

        /* Top Header */
        .top-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;
        }
        .header-greeting h1 { font-size: 28px; font-weight: 800; margin-bottom: 4px; color: var(--text-dark); display: flex; align-items: center; gap: 8px; letter-spacing: -1px; }
        .header-greeting p { font-size: 14px; color: var(--text-gray); font-weight: 500; margin: 0;}
        .header-greeting p span { background: rgba(98, 75, 255, 0.08); color: var(--primary-purple); padding: 2px 8px; border-radius: 6px; font-weight: 600; font-size: 12px; border: 1px solid rgba(98,75,255,0.1); }

        .header-actions { display: flex; align-items: center; gap: 16px; }
        .header-actions .icon-btn {
            width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--border); background: white;
            display: flex; align-items: center; justify-content: center; color: var(--text-dark); font-size: 20px;
            position: relative; cursor: pointer; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .header-actions .icon-btn:hover { background: #f8fafc; transform: translateY(-1px); }
        .btn-outline-support {
            border: 1px solid rgba(98, 75, 255, 0.15); background: white; color: var(--primary-purple);
            padding: 10px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            white-space: nowrap;
        }
        .btn-outline-support:hover { background: rgba(98, 75, 255, 0.02); }

        /* Red Reminder Banner */
        .reminder-banner {
            background: linear-gradient(135deg, #FF6B6B, #FF4B6B);
            border-radius: 20px; padding: 24px 32px; color: white;
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px;
            position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(255, 75, 107, 0.2); border: 1px solid rgba(255,255,255,0.2);
        }
        .reminder-content { display: flex; align-items: center; gap: 20px; z-index: 2; }
        .reminder-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.2); backdrop-filter: blur(4px); border-radius: 16px; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; flex-shrink: 0; border: 1px solid rgba(255,255,255,0.3); }
        .reminder-text h3 { font-size: 18px; font-weight: 800; margin: 0 0 4px 0; }
        .reminder-text p { font-size: 14px; opacity: 0.95; margin: 0; font-weight: 500; }
        .reminder-banner .btn-pay-now { background: white; color: #FF4B6B; padding: 12px 24px; border-radius: 14px; font-weight: 700; font-size: 15px; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; z-index: 2; transition: all 0.2s; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1);}
        .reminder-banner .btn-pay-now:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .reminder-bg-art { position: absolute; right: 15%; top: 50%; transform: translateY(-50%); opacity: 0.1; font-size: 160px; z-index: 1; pointer-events: none; }

        /* 3-Col KPI Cards */
        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
        .kpi-card { background: white; border-radius: 20px; padding: 24px; border: 1px solid var(--border); box-shadow: var(--card-shadow); display: flex; flex-direction: column; position: relative; overflow: hidden; transition: all 0.3s ease; }
        .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.06); }
        .kpi-top { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .kpi-icon-box { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .kpi-icon-box.red { background: rgba(255, 75, 107, 0.08); color: #FF4B6B; }
        .kpi-icon-box.yellow { background: rgba(245, 158, 11, 0.08); color: #F59E0B; }
        .kpi-icon-box.purple { background: rgba(139, 92, 246, 0.08); color: #8B5CF6; }
        .kpi-icon-box.green { background: rgba(16, 185, 129, 0.08); color: #10B981; }
        .kpi-title { font-size: 13px; font-weight: 600; color: var(--text-gray); }
        .kpi-amount { font-size: 32px; font-weight: 800; color: var(--text-dark); letter-spacing: -1px; margin-bottom: 24px; }
        .kpi-bottom { display: flex; align-items: center; justify-content: space-between; margin-top: auto; z-index: 2; }
        .kpi-tag { font-size: 11px; font-weight: 700; padding: 6px 12px; border-radius: 20px; display: flex; align-items: center; gap: 6px; }
        .kpi-tag.alert { background: rgba(255, 75, 107, 0.08); color: #FF4B6B; }
        .kpi-tag.success { background: rgba(16, 185, 129, 0.08); color: #10B981; }
        .kpi-due-date { font-size: 12px; font-weight: 600; color: var(--text-gray); display: flex; align-items: center; gap: 6px; }
        
        /* New Sparkline Styling */
        .kpi-sparkline { position: absolute; right: -5%; bottom: -5%; width: 70%; height: 60%; opacity: 0.9; z-index: 1; pointer-events: none; }

        /* 3-Col Main Grid */
        .dashboard-3col { display: grid; grid-template-columns: 1.2fr 1.1fr 1.5fr; gap: 24px; margin-bottom: 32px; align-items: stretch; }
        .dash-panel { background: white; border-radius: 20px; padding: 24px; border: 1px solid var(--border); box-shadow: var(--card-shadow); display: flex; flex-direction: column; }
        .panel-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .panel-title { display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 800; margin: 0; color: var(--text-dark); }
        .panel-link { font-size: 13px; font-weight: 700; color: var(--primary-purple); text-decoration: none; transition: 0.2s; }
        .panel-link:hover { text-decoration: underline; }

        /* Upcoming Bills List */
        .bill-item { border: 1px solid var(--border); border-radius: 16px; padding: 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; background: #FAFBFC; transition: 0.2s; }
        .bill-item:hover { border-color: rgba(98,75,255,0.2); background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .bill-left { display: flex; align-items: center; gap: 14px; }
        .bill-icon { width: 42px; height: 42px; border-radius: 12px; background: rgba(255, 75, 107, 0.08); color: #FF4B6B; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink:0; }
        .bill-icon.yellow { background: rgba(245, 158, 11, 0.08); color: #F59E0B; }
        .bill-icon.green { background: rgba(16, 185, 129, 0.08); color: #10B981; }
        .bill-info h4 { font-size: 14px; font-weight: 700; margin: 0 0 4px 0; color: var(--text-dark); }
        .bill-info p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }
        .bill-right { text-align: right; }
        .bill-right h4 { font-size: 15px; font-weight: 800; color: #FF4B6B; margin: 0 0 6px 0; }
        .bill-right p { font-size: 11px; font-weight: 700; color: #FF4B6B; margin: 0; background: rgba(255,75,107,0.08); padding: 4px 8px; border-radius: 10px; display: inline-block;}
        .btn-view-all { width: 100%; padding: 12px; background: white; border: 1px solid rgba(98,75,255,0.3); color: var(--primary-purple); border-radius: 12px; font-weight: 700; font-size: 13px; cursor: pointer; margin-top: auto; text-decoration: none; display: flex; justify-content: center; transition: 0.2s; box-shadow: 0 2px 8px rgba(98,75,255,0.05); }
        .btn-view-all:hover { background: rgba(98,75,255,0.02); border-color: var(--primary-purple); }

        /* Quick Actions */
        .quick-actions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; flex: 1; }
        .action-card { border: 1px solid var(--border); border-radius: 16px; padding: 20px 16px; text-align: center; text-decoration: none; color: var(--text-dark); transition: all 0.2s; display: flex; flex-direction: column; justify-content: center; align-items: center; background: white; }
        .action-card:hover { border-color: rgba(98,75,255,0.2); transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.03); }
        .action-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px; }
        .action-card:nth-child(1) .action-icon { background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); }
        .action-card:nth-child(2) .action-icon { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .action-card:nth-child(3) .action-icon { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
        .action-card:nth-child(4) .action-icon { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .action-card h4 { font-size: 13px; font-weight: 800; margin: 0 0 4px 0; }
        .action-card p { font-size: 11px; color: var(--text-gray); margin: 0; font-weight: 500; }

        /* Recent Transactions */
        .transaction-list { display: flex; flex-direction: column; gap: 16px; -ms-overflow-style: none; scrollbar-width: none; }
        .transaction-list::-webkit-scrollbar { display: none; }
        .transaction-item { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border); padding-bottom: 16px; }
        .transaction-item:last-child { border-bottom: none; padding-bottom: 0; }
        .tx-left { display: flex; align-items: center; gap: 14px; }
        .tx-icon { width: 38px; height: 38px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); color: #10B981; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .tx-icon.up { transform: rotate(45deg); }
        .tx-icon.elec { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .tx-icon.adv { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
        .tx-icon.maint { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; }
        .tx-info h4 { font-size: 14px; font-weight: 700; margin: 0 0 4px 0; color: var(--text-dark); }
        .tx-info p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }
        .tx-right { display: flex; align-items: center; justify-content: flex-end; gap: 16px; }
        .tx-amount { font-size: 14px; font-weight: 800; color: #10B981; width: 75px; text-align: right; }
        .tx-amount.pending { color: #FF4B6B; }
        .tx-status { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 8px; width: 60px; text-align: center; }
        .tx-status.paid { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .tx-status.pending { background: rgba(255, 75, 107, 0.1); color: #FF4B6B; }
        .tx-date { font-size: 12px; color: var(--text-gray); font-weight: 600; width: 85px; text-align: right; }

        /* Footer Widgets */
        .footer-widgets { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        .footer-widget { background: white; border: 1px solid var(--border); border-radius: 20px; padding: 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: var(--card-shadow); transition: 0.2s; }
        .footer-widget:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.05); }
        .fw-left { display: flex; align-items: center; gap: 16px; }
        .fw-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; background: rgba(98, 75, 255, 0.08); }
        .fw-icon.help { color: var(--primary-purple); }
        .fw-icon.bell { color: var(--primary-purple); }
        .fw-info h4 { font-size: 15px; font-weight: 800; margin: 0 0 4px 0; color: var(--text-dark); }
        .fw-info p { font-size: 12px; color: var(--text-gray); margin: 0; font-weight: 500; }
        .btn-fw { border: 1px solid rgba(98, 75, 255, 0.2); background: transparent; color: var(--primary-purple); padding: 10px 16px; border-radius: 12px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s; box-shadow: 0 2px 8px rgba(98,75,255,0.03); white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-fw:hover { background: rgba(98, 75, 255, 0.03); border-color: var(--primary-purple); }

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
                    .user-profile-pill { display: flex; align-items: center; gap: 12px; cursor: pointer; padding-left: 12px; border-left: 1px solid var(--border); white-space: nowrap; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info p { font-size: 12px; color: var(--text-gray); margin: 0; }
    </style>
</head>
<body style="display: block;"> <!-- Overriding body:flex from design-system -->

<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class='bx bx-home-heart'></i>
            </div>
            <div class="sidebar-brand">
                <h2><?php echo htmlspecialchars(HOUSE_NAME); ?></h2>
                <p>Resident Dashboard</p>
            </div>
        </div>
        
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item active">
                <i class='bx bx-grid-alt'></i>
                <span>Dashboard</span>
            </a>
            <a href="my-payments.php" class="nav-item">
                <i class='bx bx-wallet'></i>
                <span>My Payments</span>
            </a>
            <a href="electricity-record.php" class="nav-item">
                <i class='bx bx-bolt-circle'></i>
                <span>Electricity Record</span>
            </a>
            <a href="my-bills.php" class="nav-item">
                <i class='bx bx-receipt'></i>
                <span>My Bills</span>
            </a>
            <a href="queries.php" class="nav-item">
                <i class='bx bx-message-square-dots'></i>
                <span>Raise Query</span>
            </a>
            <a href="notices.php" class="nav-item">
                <i class='bx bx-bell'></i>
                <span>Notices</span>
            </a>
            <a href="documents.php" class="nav-item">
                <i class='bx bx-folder'></i>
                <span>Documents</span>
            </a>
            <a href="profile.php" class="nav-item">
                <i class='bx bx-user-circle'></i>
                <span>Profile Settings</span>
            </a>
            <a href="../logout.php" class="nav-item" style="color: #FF4B6B; margin-top: 20px;">
                <i class='bx bx-log-out'></i>
                <span>Logout</span>
            </a>
        </nav>
        
        <div class="go-mobile-widget">
            <h4>Go Mobile!</h4>
            <p>Manage your payments on the go.</p>
            <div class="go-mobile-imgs">
                <div class="mock-phone">
                    <i class='bx bx-wallet' style="color: white; font-size: 20px;"></i>
                </div>
                <div class="mock-qr">
                    <img src="../assets/img/qr-placeholder.png" alt="QR" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9IiNlMGUwZTAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTBweCIgZmlsbD0iIzY2NiIgZG1pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5RUjwvdGV4dD48L3N2Zz4='">
                </div>
            </div>
            <a href="#" class="btn-download"><i class='bx bx-download'></i> Download App</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="top-header">
            <div class="header-greeting">
                <h1>Hello, <?php echo htmlspecialchars(explode(' ', trim($display_name ?? $user['name'] ?? 'User'))[0]); ?> 👋</h1>
                <p>Welcome back! You're assigned to <span>Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? 'N/A'); ?></span></p>
            </div>
            <div class="header-actions">
                <div class="notification-wrapper">
                    <div class="icon-btn bell-icon" onclick="document.getElementById('notifDropdown').style.display = document.getElementById('notifDropdown').style.display === 'none' ? 'flex' : 'none';">
                        <i class='bx bx-bell'></i>
                        <?php if ($unread_count > 0): ?>
                            <span style="position: absolute; top: -5px; right: -5px; background: #EF4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border: 2px solid white; animation: pulse 2s infinite;">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Notification Dropdown -->
                    <div id="notifDropdown" style="display: none;">
                        <div style="padding: 16px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                            <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-dark);">Notifications</h3>
                            <?php if($unread_count > 0): ?>
                                <span style="font-size: 11px; background: rgba(239, 68, 68, 0.1); color: #EF4444; padding: 4px 8px; border-radius: 10px; font-weight: 600;"><?php echo $unread_count; ?> New</span>
                            <?php endif; ?>
                        </div>
                        <div style="max-height: 350px; overflow-y: auto;">
                            <?php if (empty($unread_notifications)): ?>
                                <div style="padding: 30px; text-align: center; color: var(--text-gray);">
                                    <i class='bx bx-bell-off' style="font-size: 40px; opacity: 0.5; margin-bottom: 10px;"></i>
                                    <p style="margin: 0; font-size: 14px;">You're all caught up!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($unread_notifications as $notif): ?>
                                    <div class="notif-item animate-up" data-id="<?php echo $notif['id']; ?>" style="border-bottom: 1px solid var(--border); position: relative; overflow: hidden; background: white; cursor: default;">
                                        <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 80px; background: #EF4444; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; z-index: 1;">
                                            <i class='bx bx-trash'></i>
                                        </div>
                                        <div class="notif-content" style="padding: 16px; display: flex; gap: 12px; position: relative; z-index: 2; background: white; transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $notif['color']; ?>15; color: <?php echo $notif['color']; ?>; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                                <i class='bx <?php echo $notif['icon']; ?>'></i>
                                            </div>
                                            <div style="flex: 1; padding-right: 20px;">
                                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                                    <h4 style="margin: 0; font-size: 13px; font-weight: 700; color: var(--text-dark);"><?php echo htmlspecialchars($notif['title']); ?></h4>
                                                    <span style="font-size: 11px; color: var(--text-gray); font-weight: 500; white-space: nowrap;"><?php echo date('M d', strtotime($notif['time'])); ?></span>
                                                </div>
                                                <p style="margin: 0; font-size: 13px; color: var(--text-gray); line-height: 1.4;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                            </div>
                                            <button onclick="dismissNotification('<?php echo $notif['id']; ?>', this)" style="position: absolute; right: 12px; top: 16px; background: none; border: none; font-size: 18px; color: var(--text-gray); opacity: 0.5; cursor: pointer; padding: 4px; border-radius: 50%; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(0,0,0,0.05)'; this.style.opacity='1'" onmouseout="this.style.background='none'; this.style.opacity='0.5'" title="Dismiss">
                                                <i class='bx bx-x'></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="icon-btn" id="themeToggle" onclick="document.body.classList.toggle('dark-theme')">
                    <i class='bx bx-moon'></i>
                </div>
                <a href="queries.php" class="btn-outline-support">
                    <i class='bx bx-help-circle'></i> Help & Support
                </a>
                <div style="position: relative;">
                    <div class="user-profile-pill" onclick="document.getElementById('profileDropdown').style.display = document.getElementById('profileDropdown').style.display === 'none' ? 'block' : 'none'; event.stopPropagation();">
                        <div class="user-avatar" style="overflow: hidden; background: #E0E7FF; color: var(--primary-purple); display: flex; align-items: center; justify-content: center;">
<?php 
    $real_pic = '';
    if (isset($user['profile_pic']) && !empty($user['profile_pic'])) $real_pic = $user['profile_pic'];
    elseif (isset($usr['profile_pic']) && !empty($usr['profile_pic'])) $real_pic = $usr['profile_pic'];
    elseif (isset($profile_pic) && $profile_pic !== 'assets/img/default-avatar.png' && !empty($profile_pic)) $real_pic = $profile_pic;
    
    $d_name = $display_name ?? $user['name'] ?? $usr['name'] ?? 'User';
?>
<?php if (!empty($real_pic)): ?>
    <img src="../<?php echo htmlspecialchars($real_pic); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
<?php else: ?>
    <span style="color: var(--primary-purple); font-weight: 700;"><?php echo strtoupper(substr(trim($d_name), 0, 2)); ?></span>
<?php endif; ?>
</div>
                        <div class="user-info">
                            <h4><?php echo htmlspecialchars(explode(' ', trim($display_name ?? $user['name'] ?? 'User'))[0]); ?></h4>
                            <p>Room <?php echo htmlspecialchars($room_no ?? $user['room_no'] ?? $_SESSION['room_no'] ?? 'N/A'); ?></p>
                        </div>
                        <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
                    </div>
                    
                    <div id="profileDropdown" style="display: none; position: absolute; top: 110%; right: 0; background: white; border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 200px; z-index: 1000; overflow: hidden;">
                        <a href="profile.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: var(--text-dark); font-size: 14px; font-weight: 500; border-bottom: 1px solid var(--border); transition: 0.2s;">
                            <i class='bx bx-user' style="font-size: 18px; color: var(--primary-purple);"></i> Profile Settings
                        </a>
                        <a href="../logout.php" style="display: flex; align-items: center; gap: 10px; padding: 14px 16px; text-decoration: none; color: #FF4B6B; font-size: 14px; font-weight: 500; transition: 0.2s;">
                            <i class='bx bx-log-out' style="font-size: 18px;"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Alerts -->
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

        <!-- Payment Reminder Banner -->
        <?php if ($show_banner): ?>
        <div class="reminder-banner animate-up">
            <div class="reminder-content">
                <div class="reminder-icon">
                    <i class='bx bxs-bell-ring bx-tada'></i>
                </div>
                <div class="reminder-text">
                    <h3>Payment Reminder!</h3>
                    <p>It's the <?php echo date('jS'); ?> of the month. Your bills for <?php echo implode(', ', array_unique($overdue_list)); ?> are still pending.<br>Please clear them to avoid service interruptions.</p>
                </div>
            </div>
            <button onclick="document.querySelector('.btn-pay-now-trigger')?.click()" class="btn-pay-now">
                Pay Now <i class='bx bx-right-arrow-alt'></i>
            </button>
            <i class='bx bxs-calendar reminder-bg-art'></i>
        </div>
        <?php endif; ?>

        <!-- 3-Col KPI Cards -->
        <div class="kpi-grid animate-up">
            <!-- Total Outstanding -->
            <div class="kpi-card">
                <div class="kpi-top" style="align-items: center; gap: 16px; margin-bottom: 24px;">
                    <div class="kpi-icon-box <?php echo $total_due > 0 ? 'red' : 'green'; ?>" style="width: 56px; height: 56px; font-size: 28px; flex-shrink: 0;"><i class='bx bx-credit-card'></i></div>
                    <div>
                        <div class="kpi-title" style="margin-bottom: 4px;">Total Outstanding</div>
                        <div class="kpi-amount" style="margin-bottom: 0; <?php echo $total_due > 0 ? 'color: #FF4B6B;' : ''; ?>"><?php echo money($total_due); ?></div>
                    </div>
                </div>
                <div class="kpi-bottom">
                    <?php if ($total_due > 0): ?>
                        <div class="kpi-tag alert"><i class='bx bx-error-circle'></i> Payment Due</div>
                        <button class="btn-pay-now-trigger" onclick="openPaymentModal(<?php echo $total_due; ?>, 'Total Outstanding Balance', 'total')" style="display:none;"></button>
                    <?php else: ?>
                        <div class="kpi-tag success"><i class='bx bx-check-circle'></i> All Clear</div>
                    <?php endif; ?>
                </div>
                <svg class="kpi-sparkline <?php echo $total_due > 0 ? 'red' : 'green'; ?>" viewBox="0 0 100 40" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="gradRed" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#FF4B6B;stop-opacity:0.25" />
                            <stop offset="100%" style="stop-color:#FF4B6B;stop-opacity:0" />
                        </linearGradient>
                        <linearGradient id="gradGreen" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#10B981;stop-opacity:0.25" />
                            <stop offset="100%" style="stop-color:#10B981;stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <path d="M0,35 L10,30 L20,33 L30,25 L40,30 L50,20 L60,23 L70,15 L80,17 L90,10 L100,5 L100,40 L0,40 Z" fill="url(#<?php echo $total_due > 0 ? 'gradRed' : 'gradGreen'; ?>)" />
                    <path d="M0,35 L10,30 L20,33 L30,25 L40,30 L50,20 L60,23 L70,15 L80,17 L90,10 L100,5" fill="none" stroke="<?php echo $total_due > 0 ? '#FF4B6B' : '#10B981'; ?>" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Electricity Due -->
            <div class="kpi-card">
                <div class="kpi-top" style="align-items: center; gap: 16px; margin-bottom: 24px;">
                    <div class="kpi-icon-box yellow" style="width: 56px; height: 56px; font-size: 28px; flex-shrink: 0;"><i class='bx bx-bolt-circle'></i></div>
                    <div>
                        <div class="kpi-title" style="margin-bottom: 4px;">Electricity Due</div>
                        <div class="kpi-amount" style="margin-bottom: 0;"><?php echo money($elec_due); ?></div>
                    </div>
                </div>
                <div class="kpi-bottom">
                    <div class="kpi-due-date"><i class='bx bx-calendar'></i> Due Date: <?php echo date('t M Y'); ?></div>
                </div>
                <svg class="kpi-sparkline yellow" viewBox="0 0 100 40" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="gradYellow" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#F59E0B;stop-opacity:0.25" />
                            <stop offset="100%" style="stop-color:#F59E0B;stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <path d="M0,35 L15,33 L30,27 L45,30 L60,20 L75,23 L90,13 L100,5 L100,40 L0,40 Z" fill="url(#gradYellow)" />
                    <path d="M0,35 L15,33 L30,27 L45,30 L60,20 L75,23 L90,13 L100,5" fill="none" stroke="#F59E0B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <!-- Rent Due -->
            <div class="kpi-card">
                <div class="kpi-top" style="align-items: center; gap: 16px; margin-bottom: 24px;">
                    <div class="kpi-icon-box purple" style="width: 56px; height: 56px; font-size: 28px; flex-shrink: 0;"><i class='bx bx-home'></i></div>
                    <div>
                        <div class="kpi-title" style="margin-bottom: 4px;">Rent Due</div>
                        <div class="kpi-amount" style="margin-bottom: 0;"><?php echo money($rent_due); ?></div>
                    </div>
                </div>
                <div class="kpi-bottom">
                    <div class="kpi-due-date"><i class='bx bx-calendar'></i> Due Date: 05 <?php echo date('M Y', strtotime('+1 month')); ?></div>
                </div>
                <svg class="kpi-sparkline purple" viewBox="0 0 100 40" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="gradPurple" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:#8B5CF6;stop-opacity:0.25" />
                            <stop offset="100%" style="stop-color:#8B5CF6;stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <path d="M0,35 L20,30 L40,33 L60,20 L80,23 L100,5 L100,40 L0,40 Z" fill="url(#gradPurple)" />
                    <path d="M0,35 L20,30 L40,33 L60,20 L80,23 L100,5" fill="none" stroke="#8B5CF6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
        </div>

        <!-- 3-Col Main Dashboard Grid -->
        <div class="dashboard-3col animate-up">
            <!-- Col 1: Upcoming Bills -->
            <div class="dash-panel">
                <div class="panel-head">
                    <h3 class="panel-title"><i class='bx bx-calendar-event'></i> Upcoming Bills</h3>
                    <a href="#" class="panel-link">View All</a>
                </div>
                
                <div style="display: flex; flex-direction: column; flex: 1;">
                    <?php 
                    $pending_bills_display = [];
                    foreach ($merged_rents as $r) {
                        if (isset($r['status']) && $r['status'] == 'Due') {
                            $pending_bills_display[] = ['type' => 'rent', 'month' => $r['month'], 'amount' => $r['amount']];
                        }
                    }
                    foreach ($elecs as $e) {
                        if (isset($e['status']) && $e['status'] == 'Due') {
                            $pending_bills_display[] = ['type' => 'elec', 'month' => $e['month'], 'amount' => $e['total_amount']];
                        }
                    }
                    $pending_bills_display = array_slice($pending_bills_display, 0, 3);
                    ?>

                    <?php if (empty($pending_bills_display)): ?>
                    <div style="text-align: center; padding: 20px; color: var(--text-gray); font-size: 13px; margin: auto;">
                        <i class='bx bx-check-circle' style="font-size: 32px; color: #10B981; margin-bottom: 8px;"></i><br>
                        No upcoming bills! You're all caught up.
                    </div>
                    <?php else: ?>
                        <?php foreach($pending_bills_display as $pb): ?>
                        <div class="bill-item">
                            <div class="bill-left">
                                <?php if ($pb['type'] == 'rent'): ?>
                                    <div class="bill-icon"><i class='bx bx-home'></i></div>
                                <?php else: ?>
                                    <div class="bill-icon yellow"><i class='bx bx-bolt-circle'></i></div>
                                <?php endif; ?>
                                <div class="bill-info">
                                    <h4><?php echo $pb['type'] == 'rent' ? 'Rent' : 'Electricity'; ?> for <?php echo htmlspecialchars($pb['month']); ?></h4>
                                    <p>Due Date: <?php 
                                        $ts = strtotime($pb['month']);
                                        if ($pb['type'] == 'rent') {
                                            echo '05 ' . date('M Y', strtotime('+1 month', $ts));
                                        } else {
                                            echo date('t M Y', $ts);
                                        }
                                    ?></p>
                                </div>
                            </div>
                            <div class="bill-right">
                                <h4 <?php echo $pb['type'] == 'elec' ? 'style="color: #F59E0B;"' : ''; ?>><?php echo money($pb['amount']); ?></h4>
                                <p <?php echo $pb['type'] == 'elec' ? 'style="color: #F59E0B;"' : ''; ?>>Pending</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <a href="#" class="btn-view-all">View All Bills</a>
                </div>
            </div>

            <!-- Col 2: Quick Actions -->
            <div class="dash-panel">
                <div class="panel-head">
                    <h3 class="panel-title"><i class='bx bx-zap'></i> Quick Actions</h3>
                </div>
                <div class="quick-actions-grid">
                    <a href="#" class="action-card" onclick="document.querySelector('.btn-pay-now-trigger')?.click(); return false;">
                        <div class="action-icon"><i class='bx bx-credit-card-alt'></i></div>
                        <h4>Pay Dues</h4>
                        <p>Make secure payments</p>
                    </a>
                    <a href="#" class="action-card">
                        <div class="action-icon"><i class='bx bx-history'></i></div>
                        <h4>Payment History</h4>
                        <p>View all transactions</p>
                    </a>
                    <a href="#" class="action-card">
                        <div class="action-icon"><i class='bx bx-bolt-circle'></i></div>
                        <h4>Electricity Record</h4>
                        <p>View meter readings</p>
                    </a>
                    <a href="queries.php" class="action-card">
                        <div class="action-icon"><i class='bx bx-message-square-dots'></i></div>
                        <h4>Raise Query</h4>
                        <p>Ask or report issue</p>
                    </a>
                </div>
            </div>

            <!-- Col 3: Recent Transactions -->
            <div class="dash-panel">
                <div class="panel-head">
                    <h3 class="panel-title"><i class='bx bx-receipt'></i> Recent Transactions</h3>
                    <a href="#" class="panel-link">View All</a>
                </div>
                <div class="transaction-list" style="overflow-y: auto; max-height: 250px;">
                    <?php if (empty($merged_rents) && empty($elecs)): ?>
                        <div style="text-align: center; padding: 30px; color: var(--text-gray); font-size: 13px; margin: auto;">No recent transactions found.</div>
                    <?php else: ?>
                        <?php 
                        // Combine and filter to get only Paid transactions
                        $all_tx = array_filter(array_merge($merged_rents, $elecs), function($tx) {
                            return isset($tx['status']) && $tx['status'] === 'Paid';
                        });
                        
                        // Sort by payment_date descending, fallback to id descending
                        usort($all_tx, function($a, $b) {
                            $timeA = !empty($a['payment_date']) ? strtotime($a['payment_date']) : 0;
                            $timeB = !empty($b['payment_date']) ? strtotime($b['payment_date']) : 0;
                            if ($timeA == $timeB) {
                                return $b['id'] - $a['id'];
                            }
                            return $timeB - $timeA;
                        });
                        
                        $display_tx = array_slice($all_tx, 0, 5); 
                        foreach($display_tx as $tx):
                            $is_paid = ($tx['status'] == 'Paid');
                            $is_elec = (isset($tx['source']) && $tx['source'] == 'elec_table');
                            $is_adv = (isset($tx['source']) && $tx['source'] == 'advance');
                            
                            $icon_class = 'up';
                            $icon_bx = 'bx-up-arrow-alt';
                            if ($is_elec) { $icon_class = 'elec'; $icon_bx = 'bx-bolt-circle'; }
                            else if ($is_adv) { $icon_class = 'adv'; $icon_bx = 'bx-wallet'; }
                            else { $icon_class = 'up'; $icon_bx = 'bx-up-arrow-alt'; }
                            
                            $title = 'Rent Payment';
                            if ($is_elec) $title = 'Electricity Payment';
                            if ($is_adv) $title = 'Advance Payment';
                            if (!isset($tx['source'])) $title = 'Electricity Payment'; // from $elecs array
                        ?>
                        <div class="transaction-item">
                            <div class="tx-left">
                                <div class="tx-icon <?php echo $icon_class; ?>"><i class='bx <?php echo $icon_bx; ?>'></i></div>
                                <div class="tx-info">
                                    <h4><?php echo $title; ?></h4>
                                    <p>For <?php echo htmlspecialchars($tx['month']); ?></p>
                                </div>
                            </div>
                            <div class="tx-right">
                                <div class="tx-amount <?php echo $is_paid ? '' : 'pending'; ?>"><?php echo money($tx['amount']); ?></div>
                                <div class="tx-status <?php echo $is_paid ? 'paid' : 'pending'; ?>"><?php echo htmlspecialchars($tx['status']); ?></div>
                                <div class="tx-date"><?php echo !empty($tx['payment_date']) ? date('d M Y', strtotime($tx['payment_date'])) : '-'; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer Widgets -->
        <div class="footer-widgets animate-up">
            <div class="footer-widget">
                <div class="fw-left">
                    <div class="fw-icon help"><i class='bx bx-headphone'></i></div>
                    <div class="fw-info">
                        <h4>Need Help?</h4>
                        <p>Our support team is available 24/7 to assist you.</p>
                    </div>
                </div>
                <button class="btn-fw" onclick="window.location.href='queries.php'"><i class='bx bx-message-rounded-dots'></i> Contact Support</button>
            </div>
            
            <div class="footer-widget">
                <div class="fw-left">
                    <div class="fw-icon bell"><i class='bx bx-bell'></i></div>
                    <div class="fw-info">
                        <h4>Stay Updated</h4>
                        <p>Enable notifications to never miss any updates.</p>
                    </div>
                </div>
                <button class="btn-fw">Enable Notifications</button>
            </div>
        </div>

        <!-- App Footer -->
        <div class="app-footer">
            <p>© 2026 <?php echo htmlspecialchars(HOUSE_NAME); ?>. All rights reserved.</p>
            <p>Last updated: <?php echo date('d M Y, h:i A'); ?> <i class='bx bx-refresh' style="cursor:pointer;" onclick="location.reload()"></i></p>
        </div>

    </main>
</div>

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
                    .user-profile-pill { display: flex; align-items: center; gap: 12px; cursor: pointer; padding-left: 12px; border-left: 1px solid var(--border); white-space: nowrap; }
        .user-avatar { width: 40px; height: 40px; background: var(--primary-purple); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; color: var(--text-dark); }
        .user-info p { font-size: 12px; color: var(--text-gray); margin: 0; }
    </style>

    <?php include 'payment_modal.php'; ?>

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

<script>
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            let profileDropdown = document.getElementById('profileDropdown');
            if (profileDropdown && profileDropdown.style.display === 'block') {
                profileDropdown.style.display = 'none';
            }
        });
</script>
</body>
</html>