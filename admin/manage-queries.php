<?php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$success = "";
$error = "";

// Handle status update & remark
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_query'])) {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $error = "Security validation failed.";
    } else {
        $qid = (int) $_POST['query_id'];
        $status = $_POST['status'];
        $remark = trim($_POST['admin_remark'] ?? '');

        $stmt = mysqli_prepare($conn, "UPDATE queries SET status = ?, admin_remark = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $status, $remark, $qid);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Query status updated successfully.";
        } else {
            $error = "Failed to update query.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch all queries with resident names
$queries_res = mysqli_query($conn, "
    SELECT q.*, u.name as renter_name, u.room_no 
    FROM queries q 
    JOIN users u ON q.user_id = u.id 
    ORDER BY q.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Support & Queries | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css?v=<?php echo time(); ?>">
    <style>
        @media (max-width: 768px) {
            .dashboard-grid-70 { display: flex; flex-direction: column; gap: 20px; }
            .query-card { padding: 20px; border-radius: 16px; }
            .query-card > div:first-child { flex-direction: column; gap: 15px; }
            .query-card h2 { font-size: 18px; }
            .query-action-form { grid-template-columns: 1fr !important; gap: 12px !important; }
            .query-action-form button { width: 100%; justify-content: center; }
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
                <input type="text" placeholder="Search queries...">
            </div>
            <div class="user-profile">
                <i class='bx bx-moon' id="themeToggle"></i>
            </div>
        </div>
    </header>

    <div class="welcome animate-up">
        <h1>Manage Support Tickets</h1>
        <p>Respond to renter complaints and inquiries.</p>
    </div>

    <?php if($success): ?>
        <div style="padding: 16px; background: #DCFCE7; color: #166534; border-radius: 16px; margin-bottom: 24px;" class="animate-up">
            <i class='bx bx-check-circle'></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="queries-container animate-up" style="animation-delay: 0.1s;">
        <?php if(mysqli_num_rows($queries_res) == 0): ?>
            <div class="panel" style="text-align: center; padding: 80px;">
                <i class='bx bx-coffee' style="font-size: 64px; color: var(--text-gray); opacity: 0.2;"></i>
                <p style="margin-top: 16px; color: var(--text-gray);">No support queries at the moment. Relax!</p>
            </div>
        <?php else: ?>
            <?php while($q = mysqli_fetch_assoc($queries_res)): 
                $border_color = $q['status'] == 'Resolved' ? '#10B981' : ($q['status'] == 'In Progress' ? '#3B82F6' : '#F59E0B');
                $status_clean = str_replace(' ', '-', $q['status']);
            ?>
                <div class="query-card" style="border-left-color: <?php echo $border_color; ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;" class="query-header">
                        <div>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap;">
                                <span class="badge" style="background: #F3F4F6; color: #4B5563;"><?php echo $q['category']; ?></span>
                                <span style="font-size: 13px; color: var(--text-gray);"><?php echo date('M d, Y at H:i', strtotime($q['created_at'])); ?></span>
                            </div>
                            <h2 style="font-size: 20px; font-weight: 800; color: var(--text-dark);"><?php echo htmlspecialchars($q['subject']); ?></h2>
                            <p style="font-size: 14px; color: var(--primary-purple); font-weight: 600; margin-top: 4px;">
                                From: <?php echo htmlspecialchars($q['renter_name']); ?> (Room <?php echo $q['room_no']; ?>)
                            </p>
                        </div>
                        <span class="status-badge status-<?php echo $status_clean; ?>">
                            <?php echo $q['status']; ?>
                        </span>
                    </div>

                    <div style="background: var(--bg-main); padding: 20px; border-radius: 16px; margin-bottom: 24px; border: 1px dashed var(--border);">
                        <p style="line-height: 1.6; color: var(--text-dark);"><?php echo nl2br(htmlspecialchars($q['message'])); ?></p>
                    </div>

                    <form method="POST" class="query-action-form" style="display: grid; grid-template-columns: 200px 1fr auto; gap: 16px; align-items: end;">
                        <input type="hidden" name="csrf" value="<?php echo getCsrfToken(); ?>">
                        <input type="hidden" name="query_id" value="<?php echo $q['id']; ?>">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 8px;">Update Status</label>
                            <select name="status" class="btn-outline" style="width: 100%; padding: 10px; height: 44px;">
                                <option value="Pending" <?php echo $q['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo $q['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Resolved" <?php echo $q['status'] == 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 8px;">Admin Response / Remark</label>
                            <input type="text" name="admin_remark" value="<?php echo htmlspecialchars($q['admin_remark'] ?? ''); ?>" class="btn-outline" style="width: 100%; border-style: solid; text-align: left; padding: 10px; height: 44px;" placeholder="Type your response here...">
                        </div>
                        <button type="submit" name="update_query" class="btn-primary" style="height: 44px; padding: 0 24px;">
                            Save Changes
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</main>

<script>
    document.querySelector('.search-bar input')?.addEventListener('keyup', function(e) {
        let term = e.target.value.toLowerCase();
        let cards = document.querySelectorAll('.query-card');
        cards.forEach(card => {
            let text = card.innerText.toLowerCase();
            card.style.display = text.includes(term) ? '' : 'none';
        });
    });
</script>

</body>
</html>
