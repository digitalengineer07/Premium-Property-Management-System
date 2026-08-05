<?php
// renter/payment-approvals.php
session_start();
require_once "../db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
require_once "fetch_notifications.php";

// User Profile for Header
$stmt = mysqli_prepare($conn, "SELECT username, name, profile_pic, room_no FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$display_name = $user['name'] ?: $user['username'];
$profile_pic = $user['profile_pic'] ?: "assets/img/default-avatar.png";
mysqli_stmt_close($stmt);




// Fetch Approvals from DB
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$total_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM payment_notifications WHERE user_id = $user_id");
$total_row = mysqli_fetch_assoc($total_res);
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

$approvals = [];
$res = mysqli_query($conn, "SELECT * FROM payment_notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $approvals[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Payment Approvals - <?php echo htmlspecialchars(HOUSE_NAME ?? 'Premium Renter'); ?></title>
    
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    
    <!-- Immediate Theme Setter to prevent flashes -->
    <script>
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-theme');
            }
        })();
    </script>
</head>
<body style="display: block;">

<div class="app-container">
    <!-- Sidebar -->
    <?php include_once __DIR__ . '/shared_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- 1. EXCLUSIVE MOBILE VIEW CODE (Isolated in views/mobile/payment-approvals_mobile.php) -->
        <div class="mobile-view-wrapper">
            <?php include __DIR__ . '/views/mobile/payment-approvals_mobile.php'; ?>
        </div>

        <!-- 2. EXCLUSIVE DESKTOP VIEW CODE (Isolated in views/desktop/payment-approvals_desktop.php) -->
        <div class="desktop-view-wrapper">
            <?php include __DIR__ . '/views/desktop/payment-approvals_desktop.php'; ?>
        </div>
    </main>
</div>

<script>
    // Include notification scripts here if needed, or they are loaded elsewhere
    function closeModals() {
        // Implementation based on other pages
    }
</script>

</body>
</html>
