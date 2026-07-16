<?php
$content = file_get_contents('renter/profile.php');

$pattern = '/\.dark-theme \.avatar-card \{[\s\S]*?background: rgba\(98, 75, 255, 0\.05\) !important;[\s\S]*?border: 1px solid var\(--border\);[\s\S]*?\}[\s\S]*?<a href="documents\.php" class="nav-item">[\s\S]*?<i class=\'bx bx-folder\'><\/i>[\s\S]*?<span>Documents<\/span>[\s\S]*?<\/a>[\s\S]*?<a href="profile\.php" class="nav-item active">[\s\S]*?<i class=\'bx bx-user-circle\'><\/i>[\s\S]*?<span>Profile Settings<\/span>[\s\S]*?<\/a>[\s\S]*?<a href="\.\.\/logout\.php" class="nav-item" style="color: #FF4B6B; margin-top: 20px;">[\s\S]*?<i class=\'bx bx-log-out\'><\/i>[\s\S]*?<span>Logout<\/span>[\s\S]*?<\/a>[\s\S]*?<\/nav>[\s\S]*?<\/aside>[\s\S]*?<main class="main-content">[\s\S]*?<!-- 1\. EXCLUSIVE MOBILE VIEW CODE \(Isolated in views\/mobile\/profile_mobile\.php\) -->[\s\S]*?<div class="mobile-view-wrapper">/';

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

$new_content = preg_replace($pattern, $replace, $content);

if ($new_content !== $content && $new_content !== null) {
    file_put_contents('renter/profile.php', $new_content);
    echo "Fixed profile.php using regex successfully.\n";
} else {
    echo "Regex failed to match.\n";
}
?>
