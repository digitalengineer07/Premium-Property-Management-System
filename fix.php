<?php
$file = 'renter/profile.php';
$content = file_get_contents($file);

$old = <<<EOF
        .dark-theme .doc-actions a {
            background: rgba(255, 255, 255, 0.05) !important;
            border-color: var(--border) !important;
            color: var(--text-gray) !important;
        }
                <span>Raise Query</span>
EOF;

$new = <<<EOF
        .dark-theme .doc-actions a {
            background: rgba(255, 255, 255, 0.05) !important;
            border-color: var(--border) !important;
            color: var(--text-gray) !important;
        }
        .dark-theme .doc-actions a:hover {
            background: rgba(98, 75, 255, 0.15) !important;
            color: var(--primary-purple) !important;
            border-color: var(--primary-purple) !important;
        }
        .dark-theme .panel {
            background: var(--white) !important;
            border-color: var(--border) !important;
        }
        .dark-theme input, .dark-theme select, .dark-theme textarea {
            background-color: var(--bg-main) !important;
            color: var(--text-dark) !important;
            border-color: var(--border) !important;
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
</style>
</head>
<body style="display: block;">

<div class="app-container">
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
            <a href="my-bills.php" class="nav-item">
                <i class='bx bx-receipt'></i>
                <span>My Bills</span>
            </a>
            <a href="queries.php" class="nav-item">
                <i class='bx bx-message-square-dots'></i>
                <span>Raise Query</span>
EOF;

$content = str_replace($old, $new, $content);
file_put_contents($file, $content);
echo "Replaced properly.\n";
