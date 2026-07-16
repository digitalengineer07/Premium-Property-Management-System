<?php
$lines = file('c:/xampp/htdocs/renter-system/renter/my-payments.php');
$start = -1;
$end = -1;
for($i=0; $i<count($lines); $i++) {
    if(strpos($lines[$i], '/* Transaction Items List */') !== false) {
        $start = $i;
    }
    if(strpos($lines[$i], '/* Notice & Bottom Pay Button */') !== false) {
        $end = $i;
        break;
    }
}
if($start !== -1 && $end !== -1) {
    $before = array_slice($lines, 0, $start);
    $after = array_slice($lines, $end);
    $css = <<<EOD
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
                padding: 16px 14px;
                display: flex;
                align-items: center;
                gap: 12px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.02);
                position: relative;
                overflow: hidden;
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
                position: absolute;
                top: 0;
                right: 0;
            }
            .m-status-pill {
                font-size: 10px;
                font-weight: 800;
                padding: 4px 12px;
                border-radius: 0 18px 0 12px;
                display: inline-block;
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

EOD;
    // Explode by newline but retain \n for each line except the last
    $cssLines = explode("\n", $css);
    foreach($cssLines as &$l) {
        $l = $l . "\n";
    }
    $newLines = array_merge($before, $cssLines, $after);
    file_put_contents('c:/xampp/htdocs/renter-system/renter/my-payments.php', implode("", $newLines));
    echo "Success!";
} else {
    echo "Markers not found!";
}
?>
