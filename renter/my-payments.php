<?php
// renter/dashboard.php - Redesigned with Unified SaaS UI
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
require_once "fetch_notifications.php";
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

/* Fetch profile */
$stmt = mysqli_prepare($conn, "SELECT username, name, phone, whatsapp, room_no, profile_pic, must_change_password, pending_adjustment, advance_payment, advance_updated_at, fixed_rent, fixed_maintenance, rent_maint_updated_at FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);
require_once "fetch_notifications.php";
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
$stmt = mysqli_prepare($conn, "SELECT IFNULL(SUM(rent_amount),0) as total FROM rent WHERE user_id = ? AND status != 'Paid'");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$r1 = mysqli_stmt_get_result($stmt);
$r1a = mysqli_fetch_assoc($r1);
$pure_rent_due = (float)($r1a['total'] ?? 0);
mysqli_stmt_close($stmt);

// 2. Electricity and Rent components from 'electricity' table
$stmt = mysqli_prepare($conn, "SELECT 
    IFNULL(SUM(CASE WHEN COALESCE(NULLIF(elec_status, ''), status) != 'Paid' THEN amount ELSE 0 END), 0) as elec_total, 
    IFNULL(SUM(CASE WHEN COALESCE(NULLIF(rent_status, ''), status) != 'Paid' THEN (rent_amount + maintenance + dues) ELSE 0 END), 0) as rent_portion_total 
    FROM electricity WHERE user_id = ?");
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
$is_all_clear = ($total_due <= 0 && $elec_due <= 0 && $rent_due <= 0);


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


?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Payments | <?php echo HOUSE_NAME; ?></title>
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
            padding: 8px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; display: flex; align-items: center; gap: 6px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            white-space: nowrap;
        }
        .btn-outline-support:hover { background: rgba(98, 75, 255, 0.02); }
        .user-profile-pill {
            display: flex; align-items: center; gap: 8px; cursor: pointer; padding-left: 8px;
        }
        .user-avatar { width: 34px; height: 34px; background: var(--primary-purple); color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; box-shadow: 0 4px 10px rgba(98,75,255,0.2); }
        .user-info h4 { font-size: 13px; font-weight: 700; margin: 0; white-space: nowrap; }
        .user-info p { font-size: 11px; color: var(--text-gray); margin: 0; white-space: nowrap; }

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
        
        .payments-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; margin-top: -10px; white-space: nowrap; }
        .payments-table th { text-align: left; padding: 16px 12px; font-size: 11px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: none; white-space: nowrap; }
        .payments-table td { padding: 16px 12px; font-size: 13px; font-weight: 600; color: var(--text-dark); border-bottom: none; vertical-align: middle; white-space: nowrap; }
        .payments-table tr { background: transparent; }
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

    
    /* Standardized Notification Dropdown CSS */
    .notification-wrapper { position: relative; }
    #notifDropdown { 
        position: absolute; 
        top: 110%; 
        right: 0; 
        width: 340px; 
        background: white; 
        border-radius: 16px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.15); 
        border: 1px solid var(--border); 
        z-index: 99999; 
        overflow: hidden; 
        text-align: left;
    }

                <div class="kpi-min-info">
                    <h4>Electricity Due</h4>
                    <h2><?php echo money($elec_due); ?></h2>
                    <div class="kpi-min-tag" style="background: rgba(245, 158, 11, 0.08); color: #F59E0B;">Due on 10 <?php echo date('M Y', strtotime('+1 month')); ?></div>
                </div>
            </div>

            <div class="kpi-card-minimal">
                <div class="kpi-min-icon" style="background: rgba(139, 92, 246, 0.1); color: #8B5CF6;"><i class='bx bx-home'></i></div>
                <div class="kpi-min-info">
                    <h4>Rent Due</h4>
                    <h2><?php echo money($rent_due); ?></h2>
                    <div class="kpi-min-tag" style="background: rgba(139, 92, 246, 0.08); color: #8B5CF6;">Due on 07 <?php echo date('M Y'); ?></div>
                </div>
            </div>
            
            <div class="kpi-card-minimal">
                <div class="kpi-min-icon" style="background: rgba(16, 185, 129, 0.1); color: #10B981;"><i class='bx bx-check-circle'></i></div>
                <div class="kpi-min-info">
                    <h4>Last Payment</h4>
                    <h2><?php echo $last_payment ? money($last_payment['total_amount']) : '₹0.00'; ?></h2>
                    <div class="kpi-min-tag" style="background: rgba(16, 185, 129, 0.08); color: #10B981;">Paid on <?php echo $last_payment ? date('d M Y', strtotime($last_payment['payment_date'])) : '-'; ?></div>
                </div>
            </div>
        </div>

        <!-- Payments Table Section -->
        <div class="payments-container animate-up" style="animation-delay: 0.1s;">
            <div class="tabs-header">
                <button type="button" class="tab-btn active" data-filter="all">All Payments</button>
                <button type="button" class="tab-btn" data-filter="rent">Rent Payments</button>
                <button type="button" class="tab-btn" data-filter="electricity">Electricity Payments</button>
                <button type="button" class="tab-btn" data-filter="other">Other Charges</button>
                
                <div class="tab-actions">
                    <select class="filter-select">
                        <option>All Years</option>
                        <option>2025</option>
                        <option>2026</option>
                    </select>
                    <button class="btn-filter"><i class='bx bx-filter'></i> Filter</button>
                </div>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="payments-table">
                    <thead>
                        <tr>
                            <th>BILL TYPE</th>
                            <th>FOR PERIOD</th>
                            <th>DUE DATE</th>
                            <th>AMOUNT</th>
                            <th>STATUS</th>
                            <th>PAID ON</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        <?php 
                        $current_month = '';
                        foreach($all_bills as $bill): 
                            if ($bill['period'] != $current_month) {
                                $current_month = $bill['period'];
                                echo "<tr class='month-divider' data-filter-type='divider' data-period='$current_month' style='background: #f8fafc;'><td colspan='7' style='padding: 12px 24px; font-weight: 700; font-size: 13px; color: var(--text-gray); border-bottom: 2px solid var(--border);'><i class='bx bx-calendar' style='margin-right: 6px;'></i> $current_month</td></tr>";
                            }
                        ?>
                            <tr data-filter-type="<?php echo $bill['filter_type']; ?>" data-period="<?php echo htmlspecialchars($bill['period']); ?>" class="data-row">
                                <td>
                                    <div class="td-bill-type">
                                        <div class="td-icon <?php echo $bill['color']; ?>"><i class='bx <?php echo $bill['icon']; ?>'></i></div>
                                        <div class="td-info">
                                            <h4><?php echo htmlspecialchars($bill['title']); ?></h4>
                                            <p><?php echo htmlspecialchars($bill['subtitle']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($bill['period']); ?></td>
                                <td><?php echo $bill['due_date']; ?></td>
                                <td style="font-weight: 800;"><?php echo money($bill['amount']); ?></td>
                                <td><span class="td-status <?php echo strtolower($bill['status']); ?>"><?php echo $bill['status']; ?></span></td>
                                <td><?php echo $bill['paid_on']; ?></td>
                                <td>
                                    <?php if ($bill['status'] == 'Paid'): ?>
                                        <a href="#" class="btn-view-receipt"><i class='bx bx-download'></i> View Receipt</a>
                                    <?php else: ?>
                                        <button class="btn-action-pay" onclick="openPaymentModal(<?php echo $bill['amount']; ?>, '<?php echo htmlspecialchars($bill['title']); ?> for <?php echo htmlspecialchars($bill['period']); ?>', '<?php echo $bill['type']; ?>', <?php echo $bill['id']; ?>)">
                                            <i class='bx bx-credit-card-alt'></i> Pay Now
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination" id="paginationControls">
                <!-- JS will inject pagination buttons here -->
            </div>
        </div>

        <div class="bottom-info-bar animate-up" style="animation-delay: 0.2s;">
            <div class="info-text">
                <i class='bx bx-info-circle'></i>
                Note: Please make sure to clear your pending payments before the due date to avoid any service interruptions.
            </div>
            <?php if ($total_due > 0): ?>
                <button class="btn-pay-pending" onclick="openPaymentModal(<?php echo $total_due; ?>, 'Total Outstanding Balance', 'total', 0)">
                    <i class='bx bx-wallet'></i> Pay Pending Amount
                </button>
            <?php endif; ?>
        </div>

        <script>
            let currentTab = 'all';
            let currentPage = 1;
            const monthsPerPage = 3;

            function renderTable() {
                const allDataRows = Array.from(document.querySelectorAll('#paymentsTableBody tr.data-row'));
                const allDividers = Array.from(document.querySelectorAll('#paymentsTableBody tr.month-divider'));
                
                // 1. Filter rows by tab
                const filteredRows = allDataRows.filter(row => currentTab === 'all' || row.getAttribute('data-filter-type') === currentTab);
                
                // 2. Extract unique periods from filtered rows
                const uniquePeriods = [...new Set(filteredRows.map(row => row.getAttribute('data-period')))];
                
                // 3. Paginate periods
                const totalPages = Math.ceil(uniquePeriods.length / monthsPerPage) || 1;
                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;
                
                const offset = (currentPage - 1) * monthsPerPage;
                const periodsToShow = uniquePeriods.slice(offset, offset + monthsPerPage);
                
                // 4. Show/Hide data rows based on pagination and filter
                allDataRows.forEach(row => {
                    if (filteredRows.includes(row) && periodsToShow.includes(row.getAttribute('data-period'))) {
                        row.style.display = 'table-row';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // 5. Show/Hide dividers
                allDividers.forEach(divider => {
                    const period = divider.getAttribute('data-period');
                    // Check if there are any visible data rows for this period
                    const hasVisibleRow = allDataRows.some(row => row.getAttribute('data-period') === period && row.style.display === 'table-row');
                    divider.style.display = hasVisibleRow ? 'table-row' : 'none';
                });
                
                // 6. Render Pagination controls
                renderPaginationControls(totalPages);
            }
            
            function renderPaginationControls(totalPages) {
                const container = document.getElementById('paginationControls');
                if (totalPages <= 1) {
                    container.innerHTML = '';
                    container.style.display = 'none';
                    return;
                }
                
                container.style.display = 'flex';
                let html = '';
                
                // Prev btn
                if (currentPage > 1) {
                    html += `<a href="#" class="page-btn prev-btn" data-page="${currentPage - 1}"><i class='bx bx-chevron-left'></i></a>`;
                } else {
                    html += `<span class="page-btn" style="opacity: 0.5; cursor: not-allowed;"><i class='bx bx-chevron-left'></i></span>`;
                }
                
                // Pages
                for (let i = 1; i <= totalPages; i++) {
                    html += `<a href="#" class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</a>`;
                }
                
                // Next btn
                if (currentPage < totalPages) {
                    html += `<a href="#" class="page-btn next-btn" data-page="${currentPage + 1}"><i class='bx bx-chevron-right'></i></a>`;
                } else {
                    html += `<span class="page-btn" style="opacity: 0.5; cursor: not-allowed;"><i class='bx bx-chevron-right'></i></span>`;
                }
                
                container.innerHTML = html;
                
                // Attach events to dynamically created buttons
                container.querySelectorAll('a.page-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        currentPage = parseInt(this.getAttribute('data-page'));
                        renderTable();
                    });
                });
            }

            // Tab Filtering Logic
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    currentTab = this.getAttribute('data-filter');
                    currentPage = 1; // Reset to page 1 on tab change
                    renderTable();
                });
            });
            
            // Initial render
            document.addEventListener('DOMContentLoaded', () => {
                renderTable();
            });
        </script>

    <?php include 'payment_modal.php'; ?>


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