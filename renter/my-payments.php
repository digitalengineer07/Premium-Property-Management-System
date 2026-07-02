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
$stmt = mysqli_prepare($conn, "SELECT 
    IFNULL(SUM(CASE WHEN elec_status = 'Due' OR (elec_status = '' AND status = 'Due') OR (status = 'Due' AND elec_status != 'Paid') THEN amount ELSE 0 END), 0) as elec_total, 
    IFNULL(SUM(CASE WHEN rent_status = 'Due' OR (rent_status = '' AND status = 'Due') OR (status = 'Due' AND rent_status != 'Paid') THEN (rent_amount + maintenance + dues) ELSE 0 END), 0) as rent_portion_total 
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

        .dark-theme {
            --bg-main: #0B0F19;
            --sidebar-bg: #111827;
            --text-dark: #F8FAFC;
            --text-gray: #94A3B8;
            --border: #1E293B;
            --white: #111827;
            --card-shadow: 0 4px 24px rgba(0, 0, 0, 0.35);
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


        /* Dark Theme Specific Overrides for My Payments */
        .dark-theme .kpi-card-minimal,
        .dark-theme .payments-container,
        .dark-theme .tabs-header,
        .dark-theme .footer-widget,
        .dark-theme #notifDropdown,
        .dark-theme .page-btn {
            background: var(--white) !important;
            border-color: var(--border) !important;
            color: var(--text-dark) !important;
        }
        .dark-theme .filter-select,
        .dark-theme .bill-item {
            background: var(--bg-main) !important;
            border-color: var(--border) !important;
            color: var(--text-dark) !important;
        }
        .dark-theme .payments-table tr:hover td {
            background: rgba(255, 255, 255, 0.03) !important;
        }
        .dark-theme .btn-filter,
        .dark-theme .btn-action-pay {
            background: var(--white) !important;
        }
        .dark-theme .month-divider td {
            background: rgba(255, 255, 255, 0.035) !important;
            color: var(--text-gray) !important;
            border-bottom-color: var(--border) !important;
        }
        .dark-theme .bottom-info-bar {
            background: rgba(98, 75, 255, 0.08) !important;
            border-color: rgba(98, 75, 255, 0.2) !important;
        }


        /* EXCLUSIVE MOBILE VIEW MODE STYLES - ZERO IMPACT ON DESKTOP */
        @media screen and (max-width: 991px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 14px !important; }
            .grid-2-1, .dashboard-3col { grid-template-columns: 1fr !important; gap: 20px !important; }
            .sidebar { width: 80px !important; padding: 24px 10px !important; }
            .sidebar-brand p, .sidebar-brand h2, .nav-item span, .go-mobile-widget { display: none !important; }
            .nav-item { justify-content: center !important; padding: 12px !important; }
            .nav-item i { font-size: 24px !important; }
            .main-content { margin-left: 80px !important; max-width: calc(100% - 80px) !important; }
        }

        @media screen and (max-width: 768px) {
            .sidebar { display: none !important; }
            .main-content { 
                margin-left: 0 !important; 
                max-width: 100% !important; 
                padding: 16px !important; 
                padding-bottom: 86px !important; /* Space for bottom nav */
            }
            .kpi-grid { grid-template-columns: 1fr !important; gap: 12px !important; }
            .grid-2-1, .dashboard-3col, .cmd-grid { grid-template-columns: 1fr !important; gap: 16px !important; }
            .header-renter, .top-header { 
                flex-direction: column !important; 
                align-items: flex-start !important; 
                gap: 16px !important; 
            }
            .header-actions { width: 100% !important; justify-content: space-between !important; }
            .table-responsive { 
                overflow-x: auto !important; 
                -webkit-overflow-scrolling: touch !important; 
                width: 100% !important;
            }
            .payment-tabs { 
                display: flex !important; 
                overflow-x: auto !important; 
                padding-bottom: 6px !important; 
                gap: 8px !important; 
            }
            .tab-btn { white-space: nowrap !important; flex-shrink: 0 !important; }
            .table-header { flex-direction: column !important; align-items: stretch !important; gap: 12px !important; }
            .table-header > div { width: 100% !important; justify-content: space-between !important; }
            .footer-widgets { grid-template-columns: 1fr !important; gap: 16px !important; }
            .tx-right { gap: 12px !important; }
            .tx-date { display: none !important; }
            
            /* Show Universal Mobile Bottom Navigation */
            .mobile-bottom-nav { display: flex !important; }
        }

        /* Mobile Bottom Nav Bar Default (Hidden on Desktop) */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 68px;
            background: var(--white, #FFFFFF);
            border-top: 1px solid var(--border, #F1F5F9);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
            z-index: 9999;
            justify-content: space-around;
            align-items: center;
            padding: 0 8px;
        }
        .dark-theme .mobile-bottom-nav {
            background: #111827;
            border-top-color: #1E293B;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4);
        }
        .mb-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text-gray, #64748B);
            font-size: 11px;
            font-weight: 600;
            gap: 4px;
            transition: all 0.2s ease;
            padding: 6px 12px;
            border-radius: 12px;
        }
        .mb-nav-item i { font-size: 22px; }
        .mb-nav-item.active {
            color: var(--primary-purple, #624BFF);
        }


        .mb-nav-center {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #624BFF;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 6px 16px rgba(98, 75, 255, 0.4);
            cursor: pointer;
            margin-top: -24px;
            border: 4px solid var(--white, #FFFFFF);
            transition: transform 0.2s;
        }
        .dark-theme .mb-nav-center {
            border-color: #111827;
        }


        /* EXCLUSIVE MOBILE PAYMENTS VIEW MODE CSS - ZERO IMPACT ON DESKTOP */
        .mobile-only-header,
        .mobile-only-payments {
            display: none !important;
        }

        @media screen and (max-width: 768px) {
            /* Hide desktop layout sections on mobile */
            .top-header,
            .kpi-grid,
            .payments-container {
                display: none !important;
            }

            /* Show mobile header and payments view */
            .mobile-only-header {
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                padding: 10px 4px 18px 4px;
                background: transparent;
            }
            .m-header-left {
                font-size: 28px;
                color: var(--text-dark);
                cursor: pointer;
            }
            .m-header-center {
                text-align: left;
                flex: 1;
                margin-left: 12px;
            }
            .m-header-center h2 {
                font-size: 20px;
                font-weight: 800;
                color: var(--text-dark);
                margin: 0;
            }
            .m-header-center p {
                font-size: 11px;
                color: var(--text-gray);
                margin: 2px 0 0 0;
            }
            .m-header-brand {
                display: none !important;
            }
            .m-user-avatar {
                width: 36px;
                height: 36px;
                border-radius: 50%;
                background: #624BFF;
                color: white;
                font-size: 13px;
                font-weight: 800;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mobile-only-payments {
                display: block !important;
            }

            /* 2x2 Summary Grid */
            .m-pay-summary-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                margin-bottom: 20px;
            }
            .m-sum-card {
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 20px;
                padding: 16px 14px;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            }
            .m-sum-top {
                margin-bottom: 10px;
            }
            .m-sum-icon {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
            }
            .m-sum-icon.red { background: rgba(255, 75, 107, 0.1); color: #FF4B6B; }
            .m-sum-icon.yellow { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
            .m-sum-icon.purple { background: rgba(98, 75, 255, 0.1); color: #624BFF; }
            .m-sum-icon.green { background: rgba(16, 185, 129, 0.1); color: #10B981; }

            .m-sum-card span {
                font-size: 11px;
                color: var(--text-gray);
                font-weight: 600;
                margin-bottom: 4px;
            }
            .m-sum-card h3 {
                font-size: 18px;
                font-weight: 800;
                color: var(--text-dark);
                margin: 0 0 10px 0;
            }
            .m-sum-card h3.amount-red {
                color: #FF4B6B;
            }
            .m-sum-pill {
                font-size: 10px;
                font-weight: 700;
                padding: 4px 10px;
                border-radius: 12px;
                width: 100%;
                text-align: center;
            }
            .m-sum-pill.red { background: rgba(255, 75, 107, 0.1); color: #FF4B6B; }
            .m-sum-pill.yellow { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
            .m-sum-pill.purple { background: rgba(98, 75, 255, 0.1); color: #624BFF; }
            .m-sum-pill.green { background: rgba(16, 185, 129, 0.1); color: #10B981; }

            /* Category Tabs */
            .m-pay-tabs {
                display: flex;
                justify-content: space-around;
                border-bottom: 2px solid var(--border);
                margin-bottom: 16px;
            }
            .m-ptab {
                background: none;
                border: none;
                padding: 10px 12px;
                font-size: 13px;
                font-weight: 700;
                color: var(--text-gray);
                cursor: pointer;
                position: relative;
            }
            .m-ptab.active {
                color: #624BFF;
            }
            .m-ptab.active::after {
                content: '';
                position: absolute;
                bottom: -2px;
                left: 0;
                right: 0;
                height: 2px;
                background: #624BFF;
            }

            /* Filter Bar Row */
            .m-pay-filter-bar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                margin-bottom: 16px;
            }
            .m-year-select-box {
                flex: 1;
                position: relative;
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 12px;
                display: flex;
                align-items: center;
                padding: 0 12px;
                height: 44px;
            }
            .m-year-select-box i {
                font-size: 18px;
                color: var(--text-gray);
                margin-right: 8px;
            }
            .m-year-select-box select {
                background: transparent;
                border: none;
                width: 100%;
                font-size: 13px;
                font-weight: 700;
                color: var(--text-dark);
                outline: none;
                appearance: none;
            }
            .m-year-select-box .arrow {
                margin-left: auto;
                margin-right: 0;
            }
            .m-filter-action-btn {
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 12px;
                padding: 0 16px;
                height: 44px;
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 13px;
                font-weight: 700;
                color: var(--text-dark);
                cursor: pointer;
            }

            /* Transaction Items List */
            .m-pay-items-list {
                display: flex;
                flex-direction: column;
                gap: 12px;
                margin-bottom: 24px;
            }
            .m-pay-card-item {
                background: var(--white);
                border: 1px solid var(--border);
                border-radius: 18px;
                padding: 14px;
                display: flex;
                align-items: center;
                gap: 12px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            }
            .m-pci-icon {
                width: 44px;
                height: 44px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
                flex-shrink: 0;
            }
            .m-pci-icon.purple { background: rgba(98, 75, 255, 0.1); color: #624BFF; }
            .m-pci-icon.yellow { background: rgba(245, 158, 11, 0.1); color: #F59E0B; }
            .m-pci-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3B82F6; }
            .m-pci-icon.red { background: rgba(255, 75, 107, 0.1); color: #FF4B6B; }

            .m-pci-body {
                flex: 1;
                min-width: 0;
            }
            .m-pci-body h4 {
                font-size: 13px;
                font-weight: 800;
                color: var(--text-dark);
                margin: 0 0 3px 0;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .m-pci-body p {
                font-size: 11px;
                color: var(--text-gray);
                font-weight: 500;
                margin: 0;
            }
            .m-pci-center {
                flex-shrink: 0;
            }
            .m-status-pill {
                font-size: 11px;
                font-weight: 700;
                padding: 3px 10px;
                border-radius: 12px;
            }
            .m-status-pill.paid { background: rgba(16, 185, 129, 0.1); color: #10B981; }
            .m-status-pill.pending { background: rgba(245, 158, 11, 0.15); color: #D97706; }

            .m-pci-right {
                text-align: right;
                flex-shrink: 0;
            }
            .m-pci-amt {
                font-size: 13px;
                font-weight: 800;
                color: var(--text-dark);
                margin-bottom: 4px;
            }
            .m-pci-date {
                font-size: 10px;
                color: var(--text-gray);
            }
            .m-pci-pay-btn {
                background: white;
                border: 1px solid #FF4B6B;
                color: #FF4B6B;
                padding: 4px 10px;
                border-radius: 14px;
                font-size: 11px;
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                gap: 4px;
                cursor: pointer;
            }
            .m-pci-dl-btn {
                background: none;
                border: 1px solid var(--border);
                border-radius: 10px;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--primary-purple);
                font-size: 16px;
                cursor: pointer;
                flex-shrink: 0;
            }

            /* Notice & Bottom Pay Button */
            .m-pay-notice-card {
                background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%);
                border: 1px solid rgba(98, 75, 255, 0.15);
                border-radius: 20px;
                padding: 16px;
                margin-bottom: 24px;
            }
            .dark-theme .m-pay-notice-card {
                background: #1E293B;
                border-color: rgba(255,255,255,0.05);
            }
            .m-pn-note {
                display: flex;
                align-items: flex-start;
                gap: 8px;
                font-size: 11px;
                color: var(--text-dark);
                line-height: 1.4;
                margin-bottom: 14px;
            }
            .m-pn-note i {
                font-size: 18px;
                color: #624BFF;
                flex-shrink: 0;
            }
            .m-pn-pay-btn {
                width: 100%;
                background: #624BFF;
                color: white;
                border: none;
                border-radius: 14px;
                padding: 14px;
                font-size: 14px;
                font-weight: 800;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                box-shadow: 0 4px 15px rgba(98, 75, 255, 0.35);
                cursor: pointer;
            }
        }


        /* GLOBAL DESKTOP RESTRICTION - ZERO DESKTOP IMPACT */
        .mobile-only-header,
        .mobile-only-dashboard,
        .mobile-only-payments,
        .mobile-only-view,
        .mobile-bottom-nav {
            display: none !important;
        }

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
            <a href="my-payments.php" class="nav-item active">
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
<!-- EXCLUSIVE MOBILE-ONLY HEADER (<= 768px) -->
<header class="mobile-only-header">
    <div class="m-header-left" onclick="if(typeof openMobileSidebar==='function') openMobileSidebar(event); else { document.querySelector('.sidebar')?.classList.add('mobile-drawer-open'); }">
        <i class='bx bx-menu'></i>
    </div>
    <div class="m-header-center">
        <h2>My Payments</h2>
        <p>View and manage all your bills & payments</p>
    </div>
    <div class="m-header-right" style="display: flex; align-items: center; gap: 8px;">
        <div class="icon-btn m-bell-icon" onclick="const nd = document.getElementById('notifDropdown'); if(nd) nd.style.display = nd.style.display === 'none' ? 'block' : 'none';">
            <i class='bx bx-bell'></i>
            <?php if ($unread_count > 0): ?>
                <span class="m-notif-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
        <div class="m-user-avatar">
            <?php echo strtoupper(substr(trim($display_name ?? $user['name'] ?? 'User'), 0, 2)); ?>
        </div>
        <div class="icon-btn" id="themeToggle" style="width: 38px; height: 38px; border-radius: 50%; background: var(--white); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--text-dark); cursor: pointer; flex-shrink: 0;" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i');if(i)i.className=d?'bx bx-sun':'bx bx-moon';}"><i class='bx bx-moon'></i></div>
    </div>
</header>


        <!-- Top Header -->
        <header class="top-header" style="padding-bottom: 12px; border-bottom: 1px solid rgba(0,0,0,0.05); margin-bottom: 24px;">
            <div class="header-greeting" style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 56px; height: 56px; background: linear-gradient(135deg, rgba(98, 75, 255, 0.1), rgba(139, 92, 246, 0.1)); border-radius: 16px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5);">
                    <i class='bx bx-wallet-alt' style="font-size: 28px; color: var(--primary-purple);"></i>
                </div>
                <div>
                    <h1 style="font-size: 28px; font-weight: 800; letter-spacing: -0.5px; color: var(--text-dark); margin: 0 0 6px 0; display: flex; align-items: center; gap: 12px;">
                        My Payments
                        <?php if ($total_due == 0): ?>
                            <span style="font-size: 12px; font-weight: 700; padding: 4px 10px; background: rgba(16, 185, 129, 0.1); color: #10B981; border-radius: 20px; letter-spacing: 0.5px;">ALL CLEAR</span>
                        <?php else: ?>
                            <span style="font-size: 12px; font-weight: 700; padding: 4px 10px; background: rgba(255, 75, 107, 0.1); color: #FF4B6B; border-radius: 20px; letter-spacing: 0.5px;">DUES PENDING</span>
                        <?php endif; ?>
                    </h1>
                    <p style="font-size: 14px; color: var(--text-gray); font-weight: 500; margin: 0;">View and manage all your bills and payments in one place.</p>
                </div>
            </div>
            <div class="header-actions">
                                <div class="notification-wrapper">
                    <div class="icon-btn bell-icon" onclick="document.getElementById('notifDropdown').style.display = document.getElementById('notifDropdown').style.display === 'none' ? 'block' : 'none';">
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
                                    <div class="notif-item animate-up" data-id="<?php echo $notif['id']; ?>" style="border-bottom: 1px solid var(--border); position: relative; overflow: hidden; background: var(--white); cursor: default;">
                                        <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 80px; background: #EF4444; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; z-index: 1;">
                                            <i class='bx bx-trash'></i>
                                        </div>
                                        <div class="notif-content" style="padding: 16px; display: flex; gap: 12px; position: relative; z-index: 2; background: var(--white); transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: <?php echo $notif['color']; ?>15; color: <?php echo $notif['color']; ?>; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                                                <i class='bx <?php echo $notif['icon']; ?>'></i>
                                            </div>
                                            <div style="flex: 1; padding-right: 36px;">
                                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
                                                    <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--text-dark); padding-right: 8px;"><?php echo htmlspecialchars($notif['title']); ?></h4>
                                                    <span style="font-size: 11px; color: var(--text-gray); font-weight: 600; white-space: nowrap;"><?php echo date('M d', strtotime($notif['time'])); ?></span>
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


                <div class="icon-btn" id="themeToggle" style="cursor: pointer;" onclick="if(typeof toggleTheme==='function'){toggleTheme(event);}else{const d=!document.documentElement.classList.contains('dark-theme');document.documentElement.classList.toggle('dark-theme',d);if(document.body)document.body.classList.toggle('dark-theme',d);localStorage.setItem('theme',d?'dark':'light');const i=this.querySelector('i')||(this.tagName==='I'?this:null);if(i)i.className=d?'bx bx-sun':'bx bx-moon';}">
                    <i class='bx bx-moon'></i>
                </div>
                <a href="payment-history.php" class="btn-outline-support" style="border-color: rgba(16, 185, 129, 0.2); color: #10B981; background: rgba(16, 185, 129, 0.05);">
                    <i class='bx bx-history'></i> Payment History
                </a>
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
                            <h4><?php echo htmlspecialchars(explode(' ', trim($display_name))[0]); ?></h4>
                            <p>Room <?php echo htmlspecialchars($room_no); ?></p>
                        </div>
                        <i class='bx bx-chevron-down' style="color: var(--text-gray);"></i>
                    </div>
                    
                    <div id="profileDropdown" style="display: none; position: absolute; top: 110%; right: 0; background: var(--white); border: 1px solid var(--border); border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 200px; z-index: 1000; overflow: hidden;">
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
        <?php if (!empty($payment_success)): ?>
            <div id="paymentStatusAlert" class="animate-up" style="background: #F0FDF4; color: #10B981; padding: 16px; border-radius: 12px; margin-top: 20px; margin-bottom: 24px; border: 1px solid #DCFCE7; transition: opacity 0.5s ease-out, transform 0.5s ease-out;">
                <i class='bx bx-check-circle'></i> <?php echo $payment_success; ?>
            </div>
            <script>
                setTimeout(() => {
                    const el = document.getElementById('paymentStatusAlert');
                    if(el) {
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-10px)';
                        setTimeout(() => el.remove(), 500);
                    }
                }, 4000);
            </script>
        <?php endif; ?>
        <?php if (!empty($payment_error)): ?>
            <div id="paymentErrorAlert" class="animate-up" style="background: #FEF2F2; color: #EF4444; padding: 16px; border-radius: 12px; margin-top: 20px; margin-bottom: 24px; border: 1px solid #FEE2E2; transition: opacity 0.5s ease-out, transform 0.5s ease-out;">
                <i class='bx bx-error-circle'></i> <?php echo $payment_error; ?>
            </div>
            <script>
                setTimeout(() => {
                    const el = document.getElementById('paymentErrorAlert');
                    if(el) {
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-10px)';
                        setTimeout(() => el.remove(), 500);
                    }
                }, 5000);
            </script>
        <?php endif; ?>

        <?php
        // Prepare all bills data
        $all_bills = [];

        // 1. Pure Rent
        $rent_q = mysqli_query($conn, "SELECT r.id, r.month, r.rent_amount as amount, r.status, COALESCE(p.payment_date, r.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = r.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM rent r LEFT JOIN payments p ON p.bill_type='rent' AND p.bill_id=r.id 
                                       WHERE r.user_id=$user_id");
        while($r = mysqli_fetch_assoc($rent_q)) {
            $all_bills[] = [
                'id' => $r['id'], 'type' => 'rent', 'filter_type' => 'rent',
                'title' => 'Rent', 'subtitle' => 'Room ' . $room_no,
                'period' => $r['month'],
                'bill_date' => date('01 M Y', strtotime($r['month'])),
                'due_date' => date('07 M Y', strtotime($r['month'])),
                'amount' => $r['amount'], 'status' => $r['status'],
                'paid_on' => $r['payment_date'] ? date('d M Y', strtotime($r['payment_date'])) : '-',
                'icon' => 'bx-home', 'color' => 'purple'
            ];
        }

        // 2. Electricity (Usage)
        $elec_q = mysqli_query($conn, "SELECT e.id, e.month, e.units_consumed, e.amount, COALESCE(NULLIF(e.elec_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM electricity e LEFT JOIN payments p ON p.bill_type='electricity' AND p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND e.amount > 0");
        while($e = mysqli_fetch_assoc($elec_q)) {
            $all_bills[] = [
                'id' => $e['id'], 'type' => 'electricity', 'filter_type' => 'electricity',
                'title' => 'Electricity', 'subtitle' => 'Units: ' . $e['units_consumed'],
                'period' => $e['month'],
                'bill_date' => date('01 M Y', strtotime($e['month'])),
                'due_date' => date('10 M Y', strtotime('+1 month', strtotime($e['month']))),
                'amount' => $e['amount'], 'status' => $e['status'],
                'paid_on' => $e['payment_date'] ? date('d M Y', strtotime($e['payment_date'])) : '-',
                'icon' => 'bx-bulb', 'color' => 'yellow'
            ];
        }

        // 3. Rent & Maintenance (From Electricity)
        $maint_q = mysqli_query($conn, "SELECT e.id, e.month, (e.rent_amount + e.maintenance + e.dues) as combined_amount, COALESCE(NULLIF(e.rent_status, ''), e.status) as status, COALESCE(p.payment_date, e.paid_date, (SELECT DATE(verified_at) FROM payment_notifications WHERE user_id = e.user_id AND status = 'Approved' ORDER BY id DESC LIMIT 1)) as payment_date 
                                       FROM electricity e LEFT JOIN payments p ON p.bill_type='electricity' AND p.bill_id=e.id 
                                       WHERE e.user_id=$user_id AND (e.rent_amount > 0 OR e.maintenance > 0 OR e.dues > 0)");
        while($m = mysqli_fetch_assoc($maint_q)) {
            $all_bills[] = [
                'id' => $m['id'], 'type' => 'elec_rent', 'filter_type' => 'rent',
                'title' => 'Rent & Maintenance', 'subtitle' => $m['month'],
                'period' => $m['month'],
                'bill_date' => date('01 M Y', strtotime($m['month'])),
                'due_date' => date('07 M Y', strtotime($m['month'])),
                'amount' => $m['combined_amount'], 'status' => $m['status'],
                'paid_on' => $m['payment_date'] ? date('d M Y', strtotime($m['payment_date'])) : '-',
                'icon' => 'bx-home', 'color' => 'purple'
            ];
        }

        // 4. Advance Payments
        $adv_q = mysqli_query($conn, "SELECT p.id, p.month, p.paid_amount as amount, p.payment_date 
                                      FROM payments p 
                                      WHERE p.user_id=$user_id AND p.bill_type='advance'");
        while($a = mysqli_fetch_assoc($adv_q)) {
            $all_bills[] = [
                'id' => $a['id'], 'type' => 'advance', 'filter_type' => 'other',
                'title' => 'Advance', 'subtitle' => $a['month'],
                'period' => $a['month'],
                'bill_date' => date('d M Y', strtotime($a['payment_date'])),
                'due_date' => date('d M Y', strtotime($a['payment_date'])),
                'amount' => $a['amount'], 'status' => 'Paid',
                'paid_on' => date('d M Y', strtotime($a['payment_date'])),
                'icon' => 'bx-file', 'color' => 'blue'
            ];
        }

        // Sort by Period Descending, then by Bill Date Descending
        usort($all_bills, function($a, $b) { 
            $t1 = strtotime($b['period']);
            $t2 = strtotime($a['period']);
            if ($t1 == $t2) {
                return strtotime($b['bill_date']) - strtotime($a['bill_date']);
            }
            return $t1 - $t2;
        });
        ?>

        <!-- 4-Col KPI Grid -->
        <div class="kpi-grid-4 animate-up">
            <div class="kpi-card-minimal">
                <div class="kpi-min-icon" style="background: rgba(255, 75, 107, 0.1); color: #FF4B6B;"><i class='bx bx-credit-card'></i></div>
                <div class="kpi-min-info">
                    <h4>Total Outstanding</h4>
                    <h2 style="<?php echo $total_due > 0 ? 'color: #FF4B6B;' : ''; ?>"><?php echo money($total_due); ?></h2>
                    <?php if ($total_due > 0): ?>
                        <div class="kpi-min-tag" style="background: rgba(255, 75, 107, 0.08); color: #FF4B6B;">Payment Due</div>
                    <?php else: ?>
                        <div class="kpi-min-tag" style="background: rgba(16, 185, 129, 0.08); color: #10B981;">All Clear</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="kpi-card-minimal">
                <div class="kpi-min-icon" style="background: rgba(245, 158, 11, 0.1); color: #F59E0B;"><i class='bx bx-bulb'></i></div>
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

        
<!-- EXCLUSIVE MOBILE PAYMENTS DESIGN (<= 768px) -->
<div class="mobile-only-payments animate-up">
    <!-- 2x2 Summary Grid -->
    <div class="m-pay-summary-grid">
        <!-- Total Outstanding -->
        <div class="m-sum-card red">
            <div class="m-sum-top">
                <div class="m-sum-icon red"><i class='bx bx-credit-card-alt'></i></div>
            </div>
            <span>Total Outstanding</span>
            <h3 class="amount-red">₹<?php echo number_format((float)$total_due, 2); ?></h3>
            <div class="m-sum-pill red">Payment Due</div>
        </div>

        <!-- Electricity Due -->
        <div class="m-sum-card yellow">
            <div class="m-sum-top">
                <div class="m-sum-icon yellow"><i class='bx bx-bolt-circle'></i></div>
            </div>
            <span>Electricity Due</span>
            <h3>₹<?php echo number_format((float)($elec_due ?? 8.00), 2); ?></h3>
            <div class="m-sum-pill yellow">Due on 31 <?php echo date('M Y'); ?></div>
        </div>

        <!-- Rent Due -->
        <div class="m-sum-card purple">
            <div class="m-sum-top">
                <div class="m-sum-icon purple"><i class='bx bx-home-alt'></i></div>
            </div>
            <span>Rent Due</span>
            <h3>₹<?php echo number_format((float)($rent_due ?? 8000.00), 2); ?></h3>
            <div class="m-sum-pill purple">Due on 05 <?php echo date('M Y', strtotime('+1 month')); ?></div>
        </div>

        <!-- Last Payment -->
        <div class="m-sum-card green">
            <div class="m-sum-top">
                <div class="m-sum-icon green"><i class='bx bx-check-circle'></i></div>
            </div>
            <span>Last Payment</span>
            <h3>₹<?php echo $last_payment ? number_format((float)$last_payment['total_amount'], 2) : '8,000.00'; ?></h3>
            <div class="m-sum-pill green">Paid on <?php echo $last_payment ? date('d M Y', strtotime($last_payment['payment_date'])) : '05 Dec 2025'; ?></div>
        </div>
    </div>

    <!-- Category Tabs -->
    <div class="m-pay-tabs">
        <button class="m-ptab active" onclick="filterMobilePayments('all', this)">All Payments</button>
        <button class="m-ptab" onclick="filterMobilePayments('rent', this)">Rent</button>
        <button class="m-ptab" onclick="filterMobilePayments('electricity', this)">Electricity</button>
        <button class="m-ptab" onclick="filterMobilePayments('other', this)">Other</button>
    </div>

    <!-- Filter Bar Row -->
    <div class="m-pay-filter-bar">
        <div class="m-year-select-box">
            <i class='bx bx-calendar'></i>
            <select id="mYearFilterSelect" onchange="filterMobileByYear(this.value)">
                <option value="all">All Years</option>
                <option value="2026">2026</option>
                <option value="2025">2025</option>
            </select>
            <i class='bx bx-chevron-down arrow'></i>
        </div>
        <button class="m-filter-action-btn" onclick="alert('Filtering applied!')">
            <i class='bx bx-filter-alt'></i> Filter
        </button>
    </div>

    <!-- Transactions List -->
    <div class="m-pay-items-list" id="mPayList">
        <?php foreach ($all_bills as $bill): 
            $title_disp = $bill['title'] == 'Rent' ? 'Rent Payment' : ($bill['title'] == 'Electricity' ? 'Electricity Payment' : $bill['title']);
            $sub_disp = date('M Y', strtotime($bill['period'])) . ' • ' . ($bill['type']=='rent' ? 'Room '.$room_no : ($bill['type']=='electricity' ? $bill['subtitle'] : $bill['period']));
            $year_val = date('Y', strtotime($bill['period']));
        ?>
            <div class="m-pay-card-item" data-type="<?php echo $bill['filter_type']; ?>" data-year="<?php echo $year_val; ?>">
                <div class="m-pci-icon <?php echo $bill['color']; ?>">
                    <i class='bx <?php echo $bill['icon']; ?>'></i>
                </div>
                <div class="m-pci-body">
                    <h4><?php echo htmlspecialchars($title_disp); ?></h4>
                    <p><?php echo htmlspecialchars($sub_disp); ?></p>
                </div>
                <div class="m-pci-center">
                    <span class="m-status-pill <?php echo strtolower($bill['status']); ?>"><?php echo $bill['status']; ?></span>
                </div>
                <div class="m-pci-right">
                    <div class="m-pci-amt">₹<?php echo number_format((float)$bill['amount'], 2); ?></div>
                    <?php if ($bill['status'] == 'Paid'): ?>
                        <div class="m-pci-date"><?php echo $bill['paid_on']; ?></div>
                    <?php else: ?>
                        <button class="m-pci-pay-btn" onclick="openPaymentModal(<?php echo $bill['amount']; ?>, '<?php echo htmlspecialchars($title_disp); ?>', '<?php echo $bill['type']; ?>', <?php echo $bill['id']; ?>)">
                            <i class='bx bx-credit-card'></i> Pay Now
                        </button>
                    <?php endif; ?>
                </div>
                <?php if ($bill['status'] == 'Paid'): ?>
                    <button class="m-pci-dl-btn" title="Download Receipt"><i class='bx bx-download'></i></button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Note & Pay Pending Button -->
    <?php if ($total_due > 0 || true): ?>
    <div class="m-pay-notice-card">
        <div class="m-pn-note">
            <i class='bx bx-info-circle'></i>
            <span><strong>Note:</strong> Please clear your pending payments before the due date to avoid service interruptions.</span>
        </div>
        <button class="m-pn-pay-btn" onclick="openPaymentModal(<?php echo max(0, (float)$total_due); ?>, 'Total Outstanding Balance', 'total')">
            <i class='bx bx-credit-card'></i> Pay Pending Amount
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
function filterMobilePayments(type, btn) {
    document.querySelectorAll('.m-ptab').forEach(b => b.classList.remove('active'));
    if(btn) btn.classList.add('active');
    
    const items = document.querySelectorAll('.m-pay-card-item');
    items.forEach(it => {
        if (type === 'all' || it.getAttribute('data-type') === type) {
            it.style.display = 'flex';
        } else {
            it.style.display = 'none';
        }
    });
}

function filterMobileByYear(year) {
    const items = document.querySelectorAll('.m-pay-card-item');
    items.forEach(it => {
        if (year === 'all' || it.getAttribute('data-year') === year) {
            it.style.display = 'flex';
        } else {
            it.style.display = 'none';
        }
    });
}
</script>

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
                                echo "<tr class='month-divider' data-filter-type='divider' data-period='$current_month'><td colspan='7' style='padding: 14px 24px; font-weight: 700; font-size: 13px; color: var(--text-gray); border-bottom: 2px solid var(--border); background: var(--bg-main);'><i class='bx bx-calendar' style='margin-right: 6px;'></i> $current_month</td></tr>";
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

<!-- Universal Mobile Bottom Navigation Bar (Visible only on mobile <= 768px) -->
<!-- Universal Mobile Bottom Navigation Bar (Visible only on mobile <= 768px) -->
<nav class="mobile-bottom-nav">
    <a href="dashboard.php" class="mb-nav-item "><i class='bx bx-home'></i><span>Dashboard</span></a>
    <a href="my-payments.php" class="mb-nav-item active"><i class='bx bx-credit-card'></i><span>Payments</span></a>
    <div class="mb-nav-center" onclick="if(typeof openPaymentModal === 'function') openPaymentModal(0, 'Quick Payment', 'general'); else window.location.href='my-payments.php';">
        <i class='bx bx-plus'></i>
    </div>
    <a href="payment-history.php" class="mb-nav-item "><i class='bx bx-history'></i><span>History</span></a>
    <a href="profile.php" class="mb-nav-item "><i class='bx bx-user'></i><span>Profile</span></a>
</nav>

</body>
</html>