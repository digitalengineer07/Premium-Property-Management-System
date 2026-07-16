<?php
$content = file_get_contents('renter/profile.php');

$search = <<<EOT
        .dark-theme .avatar-card {
            background: rgba(98, 75, 255, 0.05) !important;
            border: 1px solid var(--border);
        }
            <a href="documents.php" class="nav-item">
                <i class='bx bx-folder'></i>
                <span>Documents</span>
            </a>
            <a href="profile.php" class="nav-item active">
                <i class='bx bx-user-circle'></i>
                <span>Profile Settings</span>
            </a>
            <a href="../logout.php" class="nav-item" style="color: #FF4B6B; margin-top: 20px;">
                <i class='bx bx-log-out'></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

<main class="main-content">
        <!-- 1. EXCLUSIVE MOBILE VIEW CODE (Isolated in views/mobile/profile_mobile.php) -->
        <div class="mobile-view-wrapper">
EOT;

$replace = <<<EOT
        .dark-theme .avatar-card {
            background: rgba(98, 75, 255, 0.05) !important;
            border: 1px solid var(--border);
        }
        .dark-theme .btn-edit-avatar {
            background: var(--white) !important;
            border-color: var(--border) !important;
            color: var(--primary-purple) !important;
        }
        .dark-theme .doc-actions a {
            background: rgba(255, 255, 255, 0.05) !important;
            border-color: var(--border) !important;
            color: var(--text-gray) !important;
        }
    </style>
</head>
<body class="<?php echo \$is_dark ? 'dark-theme' : ''; ?>">

<div class="app-container">
    <!-- Desktop Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class='bx bx-home-heart'></i>
            </div>
            <div class="sidebar-brand">
                <h2>Resident Dashboard</h2>
                <p>Premium Renter Portal</p>
            </div>
        </div>
        
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item">
                <i class='bx bx-home'></i>
                <span>Dashboard</span>
            </a>
            <a href="my-payments.php" class="nav-item">
                <i class='bx bx-credit-card'></i>
                <span>My Payments</span>
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
            <a href="profile.php" class="nav-item active">
                <i class='bx bx-user-circle'></i>
                <span>Profile Settings</span>
            </a>
            <a href="../logout.php" class="nav-item" style="color: #FF4B6B; margin-top: 20px;">
                <i class='bx bx-log-out'></i>
                <span>Logout</span>
            </a>
        </nav>
    </aside>

<main class="main-content">
        <!-- 1. EXCLUSIVE MOBILE VIEW CODE (Isolated in views/mobile/profile_mobile.php) -->
        <div class="mobile-view-wrapper">
EOT;

if (strpos($content, $search) !== false) {
    $content = str_replace($search, $replace, $content);
    file_put_contents('renter/profile.php', $content);
    echo "Fixed profile.php successfully.\n";
} else {
    echo "Could not find the target string. The file might have been modified in an unexpected way.\n";
}
?>
