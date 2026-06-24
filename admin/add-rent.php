<?php
// admin/add-rent.php - Unified SaaS UI
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    header("Location: manage-renters.php");
    exit;
}

// Fetch renter info
$u_stmt = mysqli_prepare($conn, "SELECT name, room_no FROM users WHERE id = ?");
mysqli_stmt_bind_param($u_stmt, "i", $user_id);
mysqli_stmt_execute($u_stmt);
$u_res = mysqli_stmt_get_result($u_stmt);
$user = mysqli_fetch_assoc($u_res);
mysqli_stmt_close($u_stmt);

if (!$user) {
    header("Location: manage-renters.php");
    exit;
}

if (isset($_POST['save'])) {
    $month = $_POST['month'];
    $amount = $_POST['rent_amount'];
    $status = $_POST['status'];

    $i_stmt = mysqli_prepare($conn, "INSERT INTO rent (user_id, month, rent_amount, status) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($i_stmt, "isss", $user_id, $month, $amount, $status);
    mysqli_stmt_execute($i_stmt);
    mysqli_stmt_close($i_stmt);

    header("Location: view-renter.php?id=$user_id");
    exit;
}

$admin_user = htmlspecialchars($_SESSION['admin'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Add Rent Record | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="welcome animate-up">
        <h1>New Rent Record</h1>
        <p>Record monthly rent for <?php echo htmlspecialchars($user['name']); ?></p>
    </div>

    <div class="dashboard-grid-70 animate-up" style="margin-top: 30px; grid-template-columns: 1fr;">
        <div style="max-width: 600px; margin: 0 auto; width: 100%;">
            <div class="panel">
                <div class="panel-header">
                    <h2 style="font-size: 18px; font-weight: 700;">Rent Transaction</h2>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Month (e.g. March 2026)</label>
                        <input type="text" name="month" required placeholder="March 2026">
                    </div>

                    <div class="form-group">
                        <label>Rent Amount (₹)</label>
                        <input type="number" name="rent_amount" required placeholder="0">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="Due">Due</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>

                    <button type="submit" name="save" class="btn-primary" style="width: 100%; justify-content: center; padding: 15px; margin-top: 10px;">
                        <i class='bx bx-save'></i> Save Rent Record
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

</body>
</html>
