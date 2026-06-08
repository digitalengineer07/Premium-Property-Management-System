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

$sql = "SELECT id, username, name, phone, room_no, profile_pic, last_login, agreement_expiry_date, fixed_rent, fixed_maintenance FROM users WHERE 1=1";
$params = []; $types = "";
if ($query !== '') {
    $sql .= " AND (username LIKE ? OR name LIKE ?)";
    $qlike = "%{$query}%"; $params[] = $qlike; $params[] = $qlike; $types .= "ss";
}
if ($room !== '') {
    $sql .= " AND room_no LIKE ?";
    $rlike = "%{$room}%"; $params[] = $rlike; $types .= "s";
}
$sql .= " ORDER BY name ASC";

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
    <header class="header">
        <div class="header-content">
            <div class="search-bar">
                <i class='bx bx-search'></i>
                <input type="text" id="renterFilter" placeholder="Live search residents...">
            </div>
            <div class="user-profile">
                <i class='bx bx-moon' id="themeToggle"></i>
            </div>
        </div>
    </header>

    <div class="welcome animate-up" style="text-align: center;">
        <h1>Manage Residents</h1>
        <div style="display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap; justify-content: center;">
            <a href="add-renter.php" class="btn-primary" style="background: transparent; border: 1.5px solid #10B981; color: #10B981; padding: 10px 24px; border-radius: 14px;">
                <i class='bx bx-plus' style='font-size: 18px;'></i> Add New
            </a>
            <a href="manage-renters.php" class="btn-outline" style="padding: 10px 24px; border-radius: 14px; border: 1.5px solid var(--border);">
                View All
            </a>
        </div>
    </div>

    <div class="panel animate-up">
        <div class="panel-header">
            <form method="GET" class="filter-form" style="width: 100%;">
                <div style="display: flex; gap: 12px; flex-wrap: wrap; width: 100%;">
                    <div style="position: relative; flex: 1; min-width: 200px;">
                        <i class='bx bx-search' style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-gray);"></i>
                        <input name="q" placeholder="Search by name..." value="<?php echo htmlspecialchars($query); ?>" class="btn-outline" style="width: 100%; text-align: left; cursor: text; padding-left: 45px;">
                    </div>
                    <input name="room" placeholder="Room..." value="<?php echo htmlspecialchars($room); ?>" class="btn-outline" style="width: 100px; text-align: left; cursor: text;">
                    <button type="submit" class="btn-primary">Search</button>
                </div>
            </form>
        </div>

        <div id="deleteAlert" style="display:none; background: #FEF2F2; color: #EF4444; padding: 12px; border-radius: 12px; margin-bottom: 16px; border: 1px solid #FEE2E2;"></div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="renterTable">
                    <?php if (empty($users)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-gray);">No residents found.</td></tr>
                    <?php else: foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <div class="avatar" style="width: 36px; height: 36px; background-image: url('../<?php echo $u['profile_pic'] ?: 'assets/img/default-avatar.png'; ?>'); background-size: cover;"></div>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($u['name']); ?></div>
                                        <div style="font-size: 11px; color: var(--text-gray); display: flex; flex-direction: column;">
                                            <span>@<?php echo htmlspecialchars($u['username']); ?></span>
                                            <?php if(!empty($u['email'])): ?>
                                                <span style="font-size: 10px; opacity: 0.8;"><?php echo htmlspecialchars($u['email']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($u['fixed_rent'] > 0 || $u['fixed_maintenance'] > 0): ?>
                                <div style="font-size: 11px; color: var(--text-gray); text-align: right; white-space: nowrap;">
                                    <span style="font-weight: 600; color: #10B981;">Rent:</span> ₹<?php echo number_format($u['fixed_rent'], 2); ?> <br>
                                    <span style="font-weight: 600; color: #F59E0B;">Maint:</span> ₹<?php echo number_format($u['fixed_maintenance'], 2); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="btn-outline" style="padding: 4px 10px; font-size: 13px; border-radius: 8px; border: none; background: rgba(0,0,0,0.03); color: var(--text-dark);">Room <?php echo htmlspecialchars($u['room_no']); ?></span>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px;">Active</span>
                                <?php 
                                    if (!empty($u['agreement_expiry_date'])) {
                                        $days = (strtotime($u['agreement_expiry_date']) - time()) / 86400;
                                        if ($days < 0) {
                                            echo '<span style="font-size: 9px; padding: 2px 6px; background: rgba(239, 68, 68, 0.1); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 6px; font-weight: 700;">Expired</span>';
                                        } elseif ($days <= 30) {
                                            echo '<span style="font-size: 9px; padding: 2px 6px; background: rgba(245, 158, 11, 0.1); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 6px; font-weight: 700;">Expiring</span>';
                                        }
                                    }
                                ?>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="view-renter.php?id=<?php echo $u['id']; ?>" class="btn-outline" style="padding: 8px 12px; font-size: 13px; border-radius: 12px; background: rgba(99, 102, 241, 0.1); color: #6366F1; border: 1px solid rgba(99, 102, 241, 0.2);" title="View Profile">
                                    <i class='bx bx-user'></i>
                                </a>
                                <a href="bill-generator.php?user_id=<?php echo $u['id']; ?>" class="btn-primary" style="padding: 8px 16px; font-size: 13px; border-radius: 12px; background: #6366F1; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);">Bill</a>
                                <button onclick="resetPassword(<?php echo $u['id']; ?>, '<?php echo addslashes($u['name']); ?>')" class="btn-outline" style="padding: 8px 12px; font-size: 13px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.2);" title="Change Password">
                                    <i class='bx bx-lock-alt'></i>
                                </button>
                                <button onclick="deleteRenter(<?php echo $u['id']; ?>, '<?php echo addslashes($u['name']); ?>')" class="btn-outline" style="padding: 8px 12px; font-size: 13px; border-radius: 12px; background: rgba(239, 68, 68, 0.1); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.2);" title="Delete Resident">
                                    <i class='bx bx-trash'></i>
                                </button>
                                <a href="../onboarding-guide.php?id=<?php echo $u['id']; ?>" target="_blank" class="btn-outline" style="padding: 8px 16px; font-size: 13px; border-radius: 12px; background: rgba(255,255,255,0.05); color: var(--text-gray);" title="Download Guide">Guide</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
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

<script>
    const CSRF_TOKEN = '<?php echo getCsrfToken(); ?>';
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
