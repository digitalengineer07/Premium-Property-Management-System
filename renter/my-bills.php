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
    <title>My Bills | <?php echo HOUSE_NAME; ?></title>
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
        .btn-fw { border: 1px solid rgba(98, 75, 255, 0.2); background: transparent; color: var(--primary-purple); padding: 10px 16px; border-radius: 12px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s; box-shadow: 0 2px 8px rgba(98,75,255,0.03); }
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
    
        /* My Payments V2 CSS */
        .kpi-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px; }
        .kpi-card-minimal { background: white; border-radius: 16px; padding: 20px 16px; border: 1px solid var(--border); box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 12px; transition: 0.2s; }
        .kpi-card-minimal:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.04); }
        .kpi-min-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .kpi-min-info { overflow: hidden; }
        .kpi-min-info h4 { font-size: 12px; color: var(--text-gray); font-weight: 600; margin: 0 0 4px 0; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; }
        .kpi-min-info h2 { font-size: 22px; font-weight: 800; color: var(--text-dark); margin: 0 0 6px 0; letter-spacing: -0.5px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; }
        .kpi-min-tag { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; white-space: nowrap; }
        
        .payments-container { background: white; border: 1px solid var(--border); border-radius: 20px; box-shadow: var(--card-shadow); overflow: hidden; margin-bottom: 24px; }
        
        .tabs-header { display: flex; align-items: center; padding: 0 24px; border-bottom: 1px solid var(--border); gap: 32px; background: white; }
        .tab-btn { background: none; border: none; border-bottom: 2px solid transparent; padding: 20px 0; font-size: 14px; font-weight: 600; color: var(--text-gray); cursor: pointer; transition: 0.2s; }
        .tab-btn:hover { color: var(--primary-purple); }
        .tab-btn.active { color: var(--primary-purple); border-bottom-color: var(--primary-purple); }
        
        .tab-actions { margin-left: auto; display: flex; gap: 12px; align-items: center; }
        .filter-select { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; font-weight: 600; color: var(--text-dark); outline: none; background: #FAFBFC; font-family: 'Outfit', sans-serif; cursor: pointer; }
        .btn-filter { background: white; border: 1px solid var(--border); padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; color: var(--text-dark); cursor: pointer; display: flex; align-items: center; gap: 6px; }
        
        .payments-table { width: 100%; border-collapse: collapse; white-space: nowrap; }
        .payments-table th { text-align: left; padding: 16px 12px; font-size: 11px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); white-space: nowrap; }
        .payments-table td { padding: 16px 12px; font-size: 13px; font-weight: 600; color: var(--text-dark); border-bottom: 1px solid var(--border); vertical-align: middle; white-space: nowrap; }
        .payments-table tr:last-child td { border-bottom: none; }
        .payments-table tr:hover td { background: #FAFBFC; }
        
        .td-bill-type { display: flex; align-items: center; gap: 12px; }
        .td-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .td-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; }
        .td-icon.yellow { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
        .td-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
        .td-icon.red { background: rgba(255, 75, 107, 0.1); color: #FF4B6B; }
        .td-info h4 { margin: 0 0 4px 0; font-size: 14px; font-weight: 700; white-space: nowrap; }
        .td-info p { margin: 0; font-size: 11px; color: var(--text-gray); font-weight: 500; white-space: nowrap; }
        
        .td-status { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .td-status.paid { background: rgba(16, 185, 129, 0.1); color: #10B981; }
        .td-status.pending { background: rgba(255, 152, 0, 0.1); color: #F57C00; }
        
        .btn-view-receipt { background: none; border: none; color: var(--primary-purple); font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 8px; transition: 0.2s; text-decoration: none;}
        .btn-view-receipt:hover { background: rgba(98, 75, 255, 0.05); }
        .btn-action-pay { background: white; border: 1px solid #FF4B6B; color: #FF4B6B; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 6px 16px; border-radius: 8px; transition: 0.2s; }
        .btn-action-pay:hover { background: rgba(255, 75, 107, 0.05); }
        
        .bottom-info-bar { background: rgba(98, 75, 255, 0.04); border: 1px solid rgba(98, 75, 255, 0.1); border-radius: 16px; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; }
        .info-text { font-size: 13px; color: var(--text-gray); font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .info-text i { font-size: 18px; color: var(--primary-purple); }
        .btn-pay-pending { background: var(--primary-purple); color: white; border: none; padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-pay-pending:hover { background: var(--primary-hover); transform: translateY(-1px); }
        
        .pagination { display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 24px; padding: 24px; border-top: 1px solid var(--border); }
        .page-btn { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: white; border: 1px solid var(--border); color: var(--text-gray); font-size: 14px; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .page-btn:hover { background: #FAFBFC; color: var(--text-dark); border-color: #E2E8F0; }
        .page-btn.active { background: var(--primary-purple); color: white; border-color: var(--primary-purple); box-shadow: 0 4px 12px rgba(98, 75, 255, 0.3); }

            .user-profile-pill {
            display: flex; align-items: center; gap: 10px; cursor: pointer; padding-left: 8px;
            white-space: nowrap;
        }
        .user-avatar { width: 38px; height: 38px; background: var(--primary-purple); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; }
        .user-info p { font-size: 11px; color: var(--text-gray); margin: 0; }
    </style>

        <style>
            .bill-row td { padding: 12px 10px; transition: 0.2s; border-bottom: 1px solid rgba(0,0,0,0.05); }
            .bill-row:last-child td { border-bottom: none; }
            .bill-row td:first-child { border-top-left-radius: 16px; border-bottom-left-radius: 16px; }
            .bill-row td:last-child { border-top-right-radius: 16px; border-bottom-right-radius: 16px; }
            .bill-row { background: transparent; cursor: pointer; }
            .bill-row:hover td { background: #FAFBFC; }
            .bill-row.active td { background: #F4F0FF; border-bottom: 1px solid transparent; }
            .pagination-purple { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; border: 1px solid var(--border); background: white; color: var(--text-gray); }
            .pagination-purple.active { background: var(--primary-purple); color: white; border-color: var(--primary-purple); }
            .pagination-purple:hover:not(.active) { background: #FAFBFC; }
                .user-profile-pill {
            display: flex; align-items: center; gap: 10px; cursor: pointer; padding-left: 8px;
            white-space: nowrap;
        }
        .user-avatar { width: 38px; height: 38px; background: var(--primary-purple); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 14px; font-weight: 700; margin: 0; }
        .user-info p { font-size: 11px; color: var(--text-gray); margin: 0; }
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
            <a href="dashboard.php" class="nav-item">
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
            <a href="my-bills.php" class="nav-item active">
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
            <div class="header-greeting" style="display: flex; align-items: center; gap: 20px;">
                <div>
                    <h1 style="font-size: 32px; font-weight: 800; letter-spacing: -1px; color: var(--text-dark); margin: 0 0 8px 0; display: flex; align-items: center; gap: 16px;">
                        <span style="display: flex; align-items: center; justify-content: center; width: 48px; height: 48px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1) 0%, rgba(98, 75, 255, 0.2) 100%); color: var(--primary-purple); border-radius: 14px; font-size: 24px; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.15);">
                            <i class='bx bx-wallet-alt'></i>
                        </span>
                        My Bills
                    </h1>
                    <p style="font-size: 15px; color: var(--text-gray); font-weight: 500; margin: 0 0 0 64px;">View and manage all your bills in one place.</p>
                </div>
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
                </div>
                <div class="icon-btn" id="themeToggle" onclick="document.body.classList.toggle('dark-theme')">
                    <i class='bx bx-moon'></i>
                </div>
                <a href="queries.php" class="btn-outline-support">
                    <i class='bx bx-help-circle'></i> Help & Support
                </a>
                <div style="position: relative;">
                    <div class="user-profile-pill" onclick="document.getElementById('profileDropdown').style.display = document.getElementById('profileDropdown').style.display === 'none' ? 'block' : 'none'; event.stopPropagation();">
                        <div class="user-avatar"><?php echo strtoupper(substr($display_name ?? $user['name'] ?? 'User', 0, 2)); ?></div>
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

        <?php
        // Prepare all bills data
        $all_bills = [];

        // 1. Pure Rent
        $rent_q = mysqli_query($conn, "SELECT r.id, r.month, r.rent_amount as amount, r.status, p.payment_date 
                                       FROM rent r LEFT JOIN payments p ON p.bill_type='rent' AND p.bill_id=r.id 
                                       WHERE r.user_id=$user_id");
        while($r = mysqli_fetch_assoc($rent_q)) {
            $all_bills[] = [
                'id' => $r['id'], 'type' => 'rent', 'filter_type' => ($r['status'] == 'Paid' ? 'paid' : ($r['status'] == 'Due' ? 'unpaid' : 'unpaid')),
                'title' => 'Rent for ' . $r['month'], 'subtitle' => 'Room ' . $room_no,
                'period' => $r['month'],
                'bill_date' => date('01 M Y', strtotime($r['month'])),
                'due_date' => date('07 M Y', strtotime($r['month'])),
                'amount' => $r['amount'], 'status' => $r['status'] == 'Due' ? 'Unpaid' : $r['status'],
                'paid_on' => $r['payment_date'] ? date('d M Y', strtotime($r['payment_date'])) : '-',
                'icon' => 'bx-home', 'color' => 'purple',
                'summary' => [
                    'Monthly Rent' => $r['amount'],
                    'Maintenance Charge' => 0,
                    'Other Charges' => 0
                ]
            ];
        }

        // 2. Electricity (Usage)
        $elec_q = mysqli_query($conn, "SELECT e.id, e.month, e.units_consumed, e.amount, e.status, p.payment_date 
                                       FROM electricity e LEFT JOIN payments p ON p.bill_type='electricity' AND p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND e.amount > 0");
        while($e = mysqli_fetch_assoc($elec_q)) {
            $all_bills[] = [
                'id' => $e['id'], 'type' => 'electricity', 'filter_type' => ($e['status'] == 'Paid' ? 'paid' : ($e['status'] == 'Due' ? 'unpaid' : 'unpaid')),
                'title' => 'Electricity for ' . $e['month'], 'subtitle' => 'Room ' . $room_no,
                'period' => $e['month'],
                'bill_date' => date('01 M Y', strtotime($e['month'])),
                'due_date' => date('10 M Y', strtotime('+1 month', strtotime($e['month']))),
                'amount' => $e['amount'], 'status' => $e['status'] == 'Due' ? 'Unpaid' : $e['status'],
                'paid_on' => $e['payment_date'] ? date('d M Y', strtotime($e['payment_date'])) : '-',
                'icon' => 'bx-bulb', 'color' => 'yellow',
                'summary' => [
                    'Electricity Usage' => $e['amount'],
                    'Maintenance Charge' => 0,
                    'Other Charges' => 0
                ]
            ];
        }

        // 3. Rent & Maintenance (From Electricity)
        $maint_q = mysqli_query($conn, "SELECT e.id, e.month, e.rent_amount, e.maintenance, e.dues, (e.rent_amount + e.maintenance + e.dues) as combined_amount, e.status, p.payment_date 
                                       FROM electricity e LEFT JOIN payments p ON p.bill_type='electricity' AND p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0)");
        while($m = mysqli_fetch_assoc($maint_q)) {
            $all_bills[] = [
                'id' => $m['id'], 'type' => 'rent', 'filter_type' => ($m['status'] == 'Paid' ? 'paid' : ($m['status'] == 'Due' ? 'unpaid' : 'unpaid')),
                'title' => 'Rent for ' . $m['month'], 'subtitle' => 'Room ' . $room_no,
                'period' => $m['month'],
                'bill_date' => date('01 M Y', strtotime($m['month'])),
                'due_date' => date('07 M Y', strtotime($m['month'])),
                'amount' => $m['combined_amount'], 'status' => $m['status'] == 'Due' ? 'Unpaid' : $m['status'],
                'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                'icon' => 'bx-home', 'color' => 'purple',
                'summary' => [
                    'Monthly Rent' => $m['rent_amount'],
                    'Maintenance Charge' => $m['maintenance'],
                    'Other Charges' => $m['dues']
                ]
            ];
        }
        
        // Sort by Period Descending
        usort($all_bills, function($a, $b) { 
            return strtotime($b['bill_date']) - strtotime($a['bill_date']);
        });
        
        // Compute KPIs
        $paid_this_year = 0;
        $bills_paid_count = 0;
        foreach($all_bills as $b) {
            if ($b['status'] == 'Paid') {
                $paid_this_year += $b['amount'];
                $bills_paid_count++;
            }
        }
        $due_this_month = $total_due; 
        ?>

        <!-- 4-Col KPI Grid -->
        <div class="kpi-grid-4 animate-up" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px;">
            <div class="kpi-card-minimal" style="background: white; border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 16px;">
                <div class="kpi-min-icon" style="background: rgba(255, 75, 107, 0.1); color: #FF4B6B; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class='bx bx-receipt'></i></div>
                <div class="kpi-min-info">
                    <h4 style="font-size: 13px; color: var(--text-gray); margin: 0 0 4px 0;">Total Outstanding</h4>
                    <h2 style="font-size: 24px; color: #FF4B6B; margin: 0 0 6px 0; font-weight: 800;"><?php echo money($total_due); ?></h2>
                    <div style="font-size: 11px; font-weight: 700; color: #FF4B6B; background: rgba(255,75,107,0.1); padding: 4px 8px; border-radius: 8px; display: inline-block; white-space: nowrap;">Payment Due</div>
                </div>
            </div>
            
            <div class="kpi-card-minimal" style="background: white; border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 16px;">
                <div class="kpi-min-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class='bx bx-calendar-event'></i></div>
                <div class="kpi-min-info">
                    <h4 style="font-size: 13px; color: var(--text-gray); margin: 0 0 4px 0;">Due This Month</h4>
                    <h2 style="font-size: 24px; color: var(--text-dark); margin: 0 0 6px 0; font-weight: 800;"><?php echo money($due_this_month); ?></h2>
                    <div style="font-size: 11px; font-weight: 700; color: #F59E0B; background: rgba(245,158,11,0.1); padding: 4px 8px; border-radius: 8px; display: inline-block; white-space: nowrap;">Due on 05 <?php echo date('M Y'); ?></div>
                </div>
            </div>

            <div class="kpi-card-minimal" style="background: white; border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 16px;">
                <div class="kpi-min-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class='bx bx-check-circle'></i></div>
                <div class="kpi-min-info">
                    <h4 style="font-size: 13px; color: var(--text-gray); margin: 0 0 4px 0;">Paid This Year</h4>
                    <h2 style="font-size: 24px; color: var(--text-dark); margin: 0 0 6px 0; font-weight: 800;"><?php echo money($paid_this_year); ?></h2>
                    <div style="font-size: 11px; font-weight: 700; color: #10B981; background: rgba(16,185,129,0.1); padding: 4px 8px; border-radius: 8px; display: inline-block;"><?php echo $bills_paid_count; ?> Bills Paid</div>
                </div>
            </div>
            
            <div class="kpi-card-minimal" style="background: white; border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: var(--card-shadow); display: flex; align-items: center; gap: 16px;">
                <div class="kpi-min-icon" style="background: rgba(139, 92, 246, 0.1); color: #8B5CF6; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;"><i class='bx bx-receipt'></i></div>
                <div class="kpi-min-info">
                    <h4 style="font-size: 13px; color: var(--text-gray); margin: 0 0 4px 0;">Total Bills</h4>
                    <h2 style="font-size: 24px; color: var(--text-dark); margin: 0 0 6px 0; font-weight: 800;"><?php echo count($all_bills); ?></h2>
                    <div style="font-size: 11px; font-weight: 700; color: #8B5CF6; background: rgba(139,92,246,0.1); padding: 4px 8px; border-radius: 8px; display: inline-block;">All Time</div>
                </div>
            </div>
        </div>

        <div class="my-bills-container animate-up" style="animation-delay: 0.1s; display: grid; grid-template-columns: minmax(0, 1fr) 380px; gap: 24px; align-items: stretch;">
            <!-- Left Column: Bills List -->
            <div class="bills-list-panel" style="display: flex; flex-direction: column; gap: 0; background: white; border: 1px solid var(--border); border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                <div class="tabs-header" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: transparent; border-bottom: 1px solid var(--border);">
                    <div style="display: flex; gap: 24px;">
                        <button type="button" class="tab-btn active" data-filter="all" style="background: none; border: none; border-bottom: 2px solid var(--primary-purple); color: var(--primary-purple); font-weight: 700; padding-bottom: 8px; cursor: pointer; font-size: 14px;">All Bills</button>
                        <button type="button" class="tab-btn" data-filter="unpaid" style="background: none; border: none; color: var(--text-gray); font-weight: 600; padding-bottom: 8px; cursor: pointer; font-size: 14px;">Unpaid</button>
                        <button type="button" class="tab-btn" data-filter="paid" style="background: none; border: none; color: var(--text-gray); font-weight: 600; padding-bottom: 8px; cursor: pointer; font-size: 14px;">Paid</button>
                        <button type="button" class="tab-btn" data-filter="overdue" style="background: none; border: none; color: var(--text-gray); font-weight: 600; padding-bottom: 8px; cursor: pointer; font-size: 14px;">Overdue</button>
                    </div>
                    <div class="tab-actions" style="display: flex; gap: 12px;">
                        <select class="filter-select" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; color: var(--text-dark); outline: none;">
                            <option>All Years</option>
                            <option>2025</option>
                            <option>2026</option>
                        </select>
                        <button class="btn-filter" style="padding: 8px 16px; border: 1px solid var(--border); border-radius: 8px; font-weight: 600; color: var(--primary-purple); background: white; cursor: pointer; display: flex; align-items: center; gap: 6px;"><i class='bx bx-filter'></i> Filter</button>
                    </div>
                </div>
                
                <div style="padding: 12px 24px;"><table style="width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 16px 12px; font-size: 11px; color: var(--text-gray); text-transform: uppercase; font-weight: 700;">BILL FOR</th>
                            <th style="text-align: left; padding: 16px 8px; font-size: 11px; color: var(--text-gray); text-transform: uppercase; font-weight: 700;">BILL TYPE</th>
                            <th style="text-align: left; padding: 16px 8px; font-size: 11px; color: var(--text-gray); text-transform: uppercase; font-weight: 700;">DUE DATE</th>
                            <th style="text-align: right; padding: 16px 8px; font-size: 11px; color: var(--text-gray); text-transform: uppercase; font-weight: 700;">AMOUNT</th>
                            <th style="text-align: center; padding: 16px 8px; font-size: 11px; color: var(--text-gray); text-transform: uppercase; font-weight: 700;">STATUS</th>
                            <th style="text-align: center; padding: 16px 12px; font-size: 11px; color: var(--text-gray); text-transform: uppercase; font-weight: 700;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="billsTableBody">
                        <!-- Rendered by JS -->
                    </tbody>
                </table>
                </div><div style="margin-top: auto; padding: 16px 24px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; color: var(--text-gray); font-size: 13px;">
                    <span id="showingText">Showing 1 to 6 of 14 bills</span>
                    <div id="paginationControls" style="display: flex; gap: 4px;"></div>
                </div>
            </div>

            <!-- Right Column: Bill Details -->
            <div class="bill-details-panel" style="background: white; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 10px 40px rgba(0,0,0,0.04); padding: 32px; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--text-dark);">Bill Details</h3>
                    <span id="bdStatus" style="font-size: 11px; font-weight: 700; padding: 6px 16px; border-radius: 20px; background: rgba(255, 75, 107, 0.1); color: #FF4B6B;">Unpaid</span>
                </div>

                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
                    <div id="bdIcon" style="width: 48px; height: 48px; background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <i class='bx bx-home'></i>
                    </div>
                    <div>
                        <h4 id="bdTitle" style="margin: 0 0 4px 0; font-size: 14px; font-weight: 700; color: var(--text-dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">Rent for February 2026</h4>
                        <p id="bdSubtitle" style="margin: 0; font-size: 12px; color: var(--text-gray); font-weight: 500; white-space: nowrap;">Room 201</p>
                    </div>
                    <div style="margin-left: auto; text-align: right;">
                        <p style="margin: 0 0 4px 0; font-size: 11px; color: var(--text-gray);">Due Date</p>
                        <h4 id="bdDueDate" style="margin: 0; font-size: 13px; font-weight: 700; color: #FF4B6B;">05 Feb 2026</h4>
                    </div>
                </div>

                <div style="background: #F8F9FA; border-radius: 16px; padding: 20px; margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; gap: 16px; border: 1px solid rgba(0,0,0,0.03);">
                    <div style="min-width: 0;">
                        <p style="margin: 0 0 4px 0; font-size: 12px; color: var(--text-gray); font-weight: 500;">Total Amount</p>
                        <h2 id="bdAmount" style="margin: 0; font-size: 24px; font-weight: 800; color: #FF4B6B; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">₹8,000.00</h2>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px; flex-shrink: 0;">
                        <button id="bdBtnPay" onclick="" style="background: var(--primary-purple); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.2); white-space: nowrap;"><i class='bx bx-credit-card'></i> Pay Now</button>
                        <button id="bdBtnDownload" style="background: white; color: var(--primary-purple); border: 1px solid rgba(98, 75, 255, 0.2); padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; white-space: nowrap;"><i class='bx bx-download'></i> Download Bill</button>
                    </div>
                </div>

                <h4 style="margin: 0 0 16px 0; font-size: 14px; font-weight: 700; color: var(--text-dark);">Bill Summary</h4>
                <div id="bdSummaryList" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                    <!-- Rendered by JS -->
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px dashed var(--border); margin-bottom: 24px;">
                    <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: var(--text-dark);">Total Amount</h4>
                    <h4 id="bdTotalAmount2" style="margin: 0; font-size: 15px; font-weight: 800; color: #FF4B6B;">₹8,000.00</h4>
                </div>

                <div id="bdWarning" style="background: #FFF7ED; border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px; padding: 16px; display: flex; gap: 12px; align-items: center; margin-top: auto;">
                    <i class='bx bx-error-circle' style="color: #F59E0B; font-size: 20px;"></i>
                    <p style="margin: 0; font-size: 13px; color: #B45309; line-height: 1.6; font-weight: 600;">Please clear your dues before the due date<br>to avoid late fees.</p>
                </div>
            </div>
        </div>

        <script>
            const allBills = <?php echo json_encode($all_bills); ?>;
            let currentFilter = 'all';
            let currentPage = 1;
            const itemsPerPage = 5;
            let activeBillId = null;

            function formatMoney(amount) {
                return '₹' + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            function selectBill(index) {
                const bill = allBills[index];
                if (!bill) return;
                activeBillId = index;

                // Update UI rows
                document.querySelectorAll('.bill-row').forEach(row => {
                    row.classList.remove('active');
                });
                const activeRow = document.getElementById('bill-row-' + index);
                if (activeRow) {
                    activeRow.classList.add('active');
                }

                // Update Bill Details Panel
                const statusColor = bill.status === 'Unpaid' ? '#FF4B6B' : '#10B981';
                const statusBg = bill.status === 'Unpaid' ? 'rgba(255, 75, 107, 0.1)' : 'rgba(16, 185, 129, 0.1)';
                
                document.getElementById('bdStatus').textContent = bill.status;
                document.getElementById('bdStatus').style.color = statusColor;
                document.getElementById('bdStatus').style.background = statusBg;

                document.getElementById('bdTitle').textContent = bill.title;
                document.getElementById('bdSubtitle').textContent = bill.subtitle;
                document.getElementById('bdDueDate').textContent = bill.due_date;
                document.getElementById('bdDueDate').style.color = bill.status === 'Unpaid' ? '#FF4B6B' : 'var(--text-gray)';

                const iconMap = {'rent': 'bx-home', 'electricity': 'bx-bulb', 'maintenance': 'bx-wrench'};
                const colorMap = {'rent': ['rgba(255, 75, 107, 0.1)', '#FF4B6B'], 'electricity': ['rgba(245, 158, 11, 0.1)', '#F59E0B']};
                
                let iconClass = iconMap[bill.type] || 'bx-receipt';
                let colors = colorMap[bill.type] || ['rgba(98, 75, 255, 0.1)', 'var(--primary-purple)'];
                
                document.getElementById('bdIcon').innerHTML = `<i class='bx ${iconClass}'></i>`;
                document.getElementById('bdIcon').style.background = colors[0];
                document.getElementById('bdIcon').style.color = colors[1];

                document.getElementById('bdAmount').textContent = formatMoney(bill.amount);
                document.getElementById('bdAmount').style.color = statusColor;
                document.getElementById('bdTotalAmount2').textContent = formatMoney(bill.amount);
                document.getElementById('bdTotalAmount2').style.color = statusColor;

                // Summary List
                let summaryHtml = '';
                for (const [key, val] of Object.entries(bill.summary)) {
                    summaryHtml += `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 13px; color: var(--text-gray); font-weight: 500;">${key}</span>
                            <span style="font-size: 13px; color: var(--text-dark); font-weight: 700;">${formatMoney(val)}</span>
                        </div>
                    `;
                }
                document.getElementById('bdSummaryList').innerHTML = summaryHtml;

                // Buttons
                const btnPay = document.getElementById('bdBtnPay');
                if (bill.status === 'Unpaid') {
                    btnPay.style.display = 'flex';
                    btnPay.onclick = () => openPaymentModal(bill.amount, bill.title, bill.type, bill.id);
                } else {
                    btnPay.style.display = 'none';
                }
            }

            function goToPage(page, e) {
                if(e) e.preventDefault();
                currentPage = page;
                renderTable();
            }

            function renderTable() {
                const tbody = document.getElementById('billsTableBody');
                tbody.innerHTML = '';
                
                // Filter bills
                const filteredBills = allBills.filter(bill => currentFilter === 'all' || bill.filter_type === currentFilter);
                const totalItems = filteredBills.length;
                const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
                
                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;
                
                const startIndex = (currentPage - 1) * itemsPerPage;
                const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
                const currentBills = filteredBills.slice(startIndex, endIndex);
                
                let count = totalItems;
                
                currentBills.forEach((bill) => {
                    const idx = allBills.indexOf(bill);
                    
                    const statusColor = bill.status === 'Unpaid' ? '#FF4B6B' : '#10B981';
                    const statusBg = bill.status === 'Unpaid' ? 'rgba(255, 75, 107, 0.1)' : 'rgba(16, 185, 129, 0.1)';
                    
                    const typeColor = bill.type === 'rent' ? '#FF4B6B' : (bill.type === 'electricity' ? '#F59E0B' : '#3B82F6');
                    const typeBg = bill.type === 'rent' ? 'rgba(255, 75, 107, 0.1)' : (bill.type === 'electricity' ? 'rgba(245, 158, 11, 0.1)' : 'rgba(59, 130, 246, 0.1)');
                    
                    let iconHtml = '';
                    if (bill.type === 'rent') iconHtml = `<div style="width:36px;height:36px;border-radius:10px;background:rgba(255, 75, 107, 0.1);color:#FF4B6B;display:flex;align-items:center;justify-content:center;font-size:18px;"><i class='bx bx-home'></i></div>`;
                    else if (bill.type === 'electricity') iconHtml = `<div style="width:36px;height:36px;border-radius:10px;background:rgba(245,158,11,0.1);color:#F59E0B;display:flex;align-items:center;justify-content:center;font-size:18px;"><i class='bx bx-bulb'></i></div>`;
                    else iconHtml = `<div style="width:36px;height:36px;border-radius:10px;background:rgba(59,130,246,0.1);color:#3B82F6;display:flex;align-items:center;justify-content:center;font-size:18px;"><i class='bx bx-wrench'></i></div>`;

                    
                    let actionBtn = '';
                    if (bill.status === 'Unpaid') {
                        actionBtn = `<button style="background:white; border:1px solid rgba(98,75,255,0.2); color:var(--primary-purple); font-weight:700; font-size:11px; padding:6px 16px; border-radius:8px; cursor:pointer; transition:0.2s;">View Bill</button>`;
                    } else {
                        actionBtn = `<button style="background:white; border:1px solid rgba(98,75,255,0.2); color:var(--primary-purple); font-weight:700; font-size:15px; width: 32px; height: 32px; display:inline-flex; align-items:center; justify-content:center; border-radius:8px; cursor:pointer; transition:0.2s;"><i class='bx bx-download'></i></button>`;
                    }

                    const rowHtml = `
                        <tr id="bill-row-${idx}" class="bill-row" onclick="selectBill(${idx})">
                            <td>
                                <div style="display:flex; align-items:center; gap:16px;">
                                    ${iconHtml}
                                    <div>
                                        <h4 style="margin:0 0 4px 0; font-size:13px; font-weight:700; color:var(--text-dark);">${bill.period}</h4>
                                        <p style="margin:0; font-size:11px; color:var(--text-gray); font-weight:500;">${bill.subtitle}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-size:11px; font-weight:700; color:${typeColor}; background:${typeBg}; padding:6px 10px; border-radius:20px; text-transform:capitalize;">${bill.type}</span>
                            </td>
                            <td>
                                <p style="margin:0; font-size:12px; font-weight:600; color:var(--text-dark);">${bill.due_date}</p>
                                ${bill.status === 'Unpaid' ? `<p style="margin:4px 0 0 0; font-size:10px; font-weight:700; color:#FF4B6B;">Due Today</p>` : ''}
                            </td>
                            <td style="text-align:right;">
                                <span style="font-size:13px; font-weight:800; color:var(--text-dark);">${formatMoney(bill.amount)}</span>
                            </td>
                            <td style="text-align:center;">
                                <span style="font-size:11px; font-weight:700; color:${statusColor}; background:${statusBg}; padding:6px 12px; border-radius:20px; display:inline-block; min-width: 60px;">${bill.status}</span>
                            </td>
                            <td style="text-align:center;">
                                ${actionBtn}
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += rowHtml;
                });
                
                document.getElementById('showingText').textContent = totalItems > 0 ? `Showing ${startIndex + 1} to ${endIndex} of ${totalItems} bills` : `Showing 0 bills`;
                
                let pagHtml = '';
                if (totalPages > 1) {
                    pagHtml += `<a href="#" onclick="goToPage(${currentPage > 1 ? currentPage - 1 : 1}, event)" class="pagination-purple"><i class='bx bx-chevron-left'></i></a>`;
                    for (let i = 1; i <= totalPages; i++) {
                        pagHtml += `<a href="#" onclick="goToPage(${i}, event)" class="pagination-purple ${i === currentPage ? 'active' : ''}">${i}</a>`;
                    }
                    pagHtml += `<a href="#" onclick="goToPage(${currentPage < totalPages ? currentPage + 1 : totalPages}, event)" class="pagination-purple"><i class='bx bx-chevron-right'></i></a>`;
                }
                document.getElementById('paginationControls').innerHTML = pagHtml;
                
                if (totalItems > 0 && activeBillId === null) {
                    selectBill(allBills.indexOf(currentBills[0]));
                } else if (totalItems > 0) {
                     selectBill(activeBillId); 
                }
            }

            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.onclick = (e) => {
                    document.querySelectorAll('.tab-btn').forEach(b => {
                        b.style.borderBottom = 'none';
                        b.style.color = 'var(--text-gray)';
                    });
                    e.target.style.borderBottom = '2px solid var(--primary-purple)';
                    e.target.style.color = 'var(--primary-purple)';
                    currentFilter = e.target.getAttribute('data-filter');
                    activeBillId = null; 
                    currentPage = 1;
                    renderTable();
                };
            });

            // Initial render
            document.addEventListener('DOMContentLoaded', () => {
                renderTable();
            });
            
            // Re-using the payment modal logic from the bottom of the file
            function openPaymentModal(amount, title, type, id) {
                const pm = document.getElementById('paymentModal');
                if(pm) {
                    pm.style.display = 'flex';
                    document.getElementById('pmTitle').textContent = 'Pay ' + title;
                    document.getElementById('pmAmount').textContent = formatMoney(amount);
                    document.getElementById('pay_amount_hidden').value = amount;
                    document.getElementById('pay_bill_type').value = type;
                    document.getElementById('pay_bill_id').value = id;
                }
            }
            function closePaymentModal() {
                const pm = document.getElementById('paymentModal');
                if(pm) {
                    pm.style.display = 'none';
                }
            }
        </script>
        
        <!-- Note: We are keeping the existing payment modal div and logic intact below -->
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