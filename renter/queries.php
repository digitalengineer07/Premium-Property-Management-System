<?php
require_once "../db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$success = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_query'])) {
    $category = $_POST['category'] ?? 'Other';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO queries (user_id, category, subject, message) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $category, $subject, $message);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Your query has been submitted successfully. We'll get back to you soon.";
        } else {
            $error = "Failed to submit query. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch user queries
$stmt = mysqli_prepare($conn, "SELECT * FROM queries WHERE user_id = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$queries = [];
while ($row = mysqli_fetch_assoc($res)) {
    $queries[] = $row;
}
mysqli_stmt_close($stmt);

// Fetch recent announcements
$ann_res = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
$notices = [];
$dismissed_cookie = $_COOKIE['dismissed_notifs'] ?? '';
$dismissed_ids = $dismissed_cookie ? explode(',', $dismissed_cookie) : [];

while ($row = mysqli_fetch_assoc($ann_res)) {
    if (!in_array('ann_' . $row['id'], $dismissed_ids)) {
        $notices[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Support & Queries | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="manifest" href="../manifest.json">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
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
    <style>
        .header-renter { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .brand-renter { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .query-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 30px; }
        
        @media (max-width: 768px) {
            .header-renter {
                flex-direction: column;
                text-align: center;
                gap: 15px;
                margin-bottom: 24px;
            }
            .query-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            .card-form { padding: 24px 20px !important; }
            .notice-grid { grid-template-columns: 1fr !important; }
        }
        .brand-renter i { background: var(--primary-purple); color: white; padding: 10px; border-radius: 12px; font-size: 24px; }
        .brand-renter span { font-weight: 800; font-size: 22px; color: var(--text-dark); letter-spacing: -0.5px; }
        
        .query-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; }
        .card-form { background: var(--white); padding: 32px; border-radius: 24px; box-shadow: var(--card-shadow); height: fit-content; }
        .query-item { 
            background: var(--white); 
            padding: 24px; 
            border-radius: 20px; 
            margin-bottom: 20px; 
            box-shadow: var(--card-shadow); 
            border-left: 5px solid transparent;
            transition: transform 0.2s;
        }
        .query-item:hover { transform: translateX(5px); }
        .status-badge { padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pending, .status-normal { background: #FFF7ED; color: #EA580C; }
        .status-progress, .status-high { background: #EFF6FF; color: #2563EB; }
        .status-resolved, .status-low { background: #F0FDF4; color: #16A34A; }
        .status-urgent { background: #FEF2F2; color: #EF4444; }
        .dark-theme .status-urgent { background: rgba(239, 68, 68, 0.1); }
        .dark-theme .status-high { background: rgba(37, 99, 235, 0.1); }
        .dark-theme .status-normal { background: rgba(234, 88, 12, 0.1); }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-dark); font-size: 14px; }
        .form-control { 
            width: 100%; padding: 12px 16px; border-radius: 12px; border: 1.5px solid #E5E7EB; 
            background: #F9FAFB; transition: border-color 0.2s; font-family: inherit;
            color: #1A1A1A; /* Solid dark text for light mode */
        }
        .form-control:focus { outline: none; border-color: var(--primary-purple); background: white; }
        
        /* Dark Mode Overrides */
        .dark-theme .card-form, .dark-theme .query-item { background: var(--white); border-color: var(--border); }
        .dark-theme .form-control { background: var(--bg-main); border-color: var(--border); color: var(--text-dark); }
        .dark-theme .brand-renter span { color: var(--text-dark); }
        .dark-theme .query-list h2 { color: var(--text-dark); }
        .dark-theme .query-list > div[style*="background: white"] { background: var(--white) !important; color: var(--text-gray) !important; }
        .dark-theme .form-label { color: var(--text-dark); }
        
        .admin-response-box { background: #F8F7FF; padding: 12px 16px; border-radius: 12px; margin-top: 10px; border: 1px solid rgba(98, 75, 255, 0.1); }
        .dark-theme .admin-response-box { background: rgba(98, 75, 255, 0.1); border-color: rgba(98, 75, 255, 0.2); }
        
        @media (max-width: 768px) { 
            .query-grid { grid-template-columns: 1fr; } 
            .header-renter { 
                position: relative !important; 
                top: auto !important; 
                margin-bottom: 24px; 
                flex-direction: column; 
                align-items: center;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body style="display: block;">

<main class="main-renter">
    <header class="header-renter">
        <div class="brand-renter">
            <img src="../assets/img/logo.png" alt="Logo" style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover;">
            <span><?php echo HOUSE_NAME; ?></span>
        </div>
        <div class="user-profile" style="display: flex; gap: 12px; align-items: center;">
            <i class='bx bx-moon' id="themeToggle" style="font-size: 24px; cursor: pointer; color: var(--text-gray);"></i>
            <a href="dashboard.php" class="btn-outline">Back to Dashboard</a>
        </div>
    </header>

    <div class="welcome" style="margin-bottom: 30px;">
        <h1 style="font-size: 28px; font-weight: 800;">Support Center</h1>
        <p style="color: var(--text-gray);">Raise a ticket or track your existing complaints.</p>
    </div>

    <!-- Announcement Section -->
    <?php if (!empty($notices)): ?>
    <div class="animate-up" style="margin-bottom: 40px;" id="noticeBoardSection">
        <h2 style="font-size: 16px; font-weight: 700; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <i class='bx bx-bell'></i> Notice Board
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($notices as $n): 
                $border_color = '#E5E7EB'; 
                $icon = 'bx-info-circle';
                if ($n['priority'] == 'Urgent') { $border_color = '#EF4444'; $icon = 'bx-error'; }
                elseif ($n['priority'] == 'High') { $border_color = '#F59E0B'; $icon = 'bx-error-circle'; }
            ?>
            <div class="notice-card" data-id="ann_<?php echo $n['id']; ?>" style="background: var(--white); padding: 20px; border-radius: 20px; box-shadow: var(--card-shadow); border-left: 5px solid <?php echo $border_color; ?>; position: relative; overflow: hidden; transition: transform 0.3s, opacity 0.3s;">
                <div style="position: absolute; top: 12px; right: 12px;">
                    <i class='bx bx-x' style="font-size: 24px; color: var(--text-gray); cursor: pointer; opacity: 0.6; padding: 4px;" onmouseover="this.style.opacity='1'; this.style.color='#EF4444';" onmouseout="this.style.opacity='0.6'; this.style.color='var(--text-gray)';" onclick="dismissNotice('ann_<?php echo $n['id']; ?>', this)"></i>
                </div>
                <div style="display: flex; justify-content: flex-start; align-items: flex-start; margin-bottom: 10px; padding-right: 30px;">
                    <h3 style="font-size: 17px; font-weight: 700; color: var(--text-dark); margin: 0; margin-right: 12px;"><?php echo htmlspecialchars($n['title']); ?></h3>
                    <span class="status-badge <?php echo 'status-' . strtolower($n['priority']); ?>" style="font-size: 9px; flex-shrink: 0;"><?php echo $n['priority']; ?></span>
                </div>
                <p style="font-size: 14px; color: var(--text-gray); line-height: 1.6; margin-bottom: 15px;">
                    <?php echo nl2br(htmlspecialchars($n['message'])); ?>
                </p>
                <div style="font-size: 11px; color: var(--text-gray); display: flex; align-items: center; gap: 5px;">
                    <i class='bx bx-time'></i> <?php echo date('M d, H:i', strtotime($n['created_at'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="query-grid">
        <!-- Raise Query Form -->
        <div class="card-form animate-up">
            <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
                <i class='bx bx-plus-circle' style="color: var(--primary-purple);"></i> New Query
            </h2>
            
            <?php if($success): ?>
                <div style="padding: 12px; background: #DCFCE7; color: #166534; border-radius: 12px; margin-bottom: 20px; font-size: 14px;">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div style="padding: 12px; background: #FEE2E2; color: #991B1B; border-radius: 12px; margin-bottom: 20px; font-size: 14px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="Apartment">Apartment Maintenance</option>
                        <option value="Billing">Billing & Rent Issues</option>
                        <option value="Website">Website Support</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Brief summary" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Detailed Message</label>
                    <textarea name="message" class="form-control" rows="5" placeholder="Describe your issue..." required></textarea>
                </div>
                <button type="submit" name="submit_query" class="btn-primary" style="width: 100%; justify-content: center; padding: 14px;">
                    Submit Query
                </button>
            </form>
        </div>

        <!-- Query List -->
        <div class="query-list animate-up" style="animation-delay: 0.1s;">
            <h2 style="font-size: 18px; font-weight: 700; margin-bottom: 24px;">Your Recent Queries</h2>
            
            <?php if(empty($queries)): ?>
                <div style="text-align: center; padding: 60px; background: white; border-radius: 24px; color: var(--text-gray);">
                    <i class='bx bx-message-rounded-x' style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No queries found. Everything looking good!</p>
                </div>
            <?php else: ?>
                <?php foreach($queries as $q): 
                    $border_color = $q['status'] == 'Resolved' ? '#10B981' : ($q['status'] == 'In Progress' ? '#3B82F6' : '#F59E0B');
                    $status_class = strtolower(str_replace(' ', '-', $q['status']));
                ?>
                    <div class="query-item" style="border-left-color: <?php echo $border_color; ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div>
                                <span style="font-size: 11px; background: #F3F4F6; padding: 2px 8px; border-radius: 4px; color: #6B7280; font-weight: 600;">
                                    <?php echo htmlspecialchars($q['category']); ?>
                                </span>
                                <h3 style="font-size: 16px; font-weight: 700; margin-top: 6px; color: var(--text-dark);">
                                    <?php echo htmlspecialchars($q['subject']); ?>
                                </h3>
                            </div>
                            <span class="status-badge status-<?php echo $status_class; ?>">
                                <?php echo $q['status']; ?>
                            </span>
                        </div>
                        <p style="font-size: 14px; color: var(--text-gray); line-height: 1.6; margin-bottom: 16px;">
                            <?php echo nl2br(htmlspecialchars($q['message'])); ?>
                        </p>
                        
                        <?php if($q['admin_remark']): ?>
                            <div class="admin-response-box">
                                <div style="font-size: 11px; font-weight: 700; color: var(--primary-purple); text-transform: uppercase;">Admin Response</div>
                                <p style="font-size: 13px; color: var(--text-dark); margin-top: 4px;"><?php echo htmlspecialchars($q['admin_remark']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 16px; font-size: 11px; color: #9CA3AF; display: flex; align-items: center; gap: 4px;">
                            <i class='bx bx-calendar'></i> Submitted on <?php echo date('M d, Y at H:i', strtotime($q['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    const themeToggle = document.getElementById('themeToggle');
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark-theme');
        themeToggle?.classList.replace('bx-moon', 'bx-sun');
    }
    themeToggle?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        if (isDark) themeToggle.classList.replace('bx-moon', 'bx-sun');
        else themeToggle.classList.replace('bx-sun', 'bx-moon');
    });

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

    function dismissNotice(id, el) {
        let card = el.closest('.notice-card');
        if (card) {
            card.style.transform = 'scale(0.9)';
            card.style.opacity = '0';
            setTimeout(() => {
                card.style.display = 'none';
                let remaining = document.querySelectorAll('.notice-card').length;
                let visible = Array.from(document.querySelectorAll('.notice-card')).filter(c => c.style.display !== 'none').length;
                if (visible === 0) {
                    let section = document.getElementById('noticeBoardSection');
                    if (section) section.style.display = 'none';
                }
            }, 300);
        }
        
        let currentStr = getCookie('dismissed_notifs');
        let currentIds = currentStr ? currentStr.split(',') : [];
        if (!currentIds.includes(id)) {
            currentIds.push(id);
            setCookie('dismissed_notifs', currentIds.join(','), 30);
        }
    }
</script>

</body>
</html>
