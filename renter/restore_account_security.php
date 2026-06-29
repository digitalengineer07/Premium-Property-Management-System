<?php
$c = file_get_contents("profile.php");
$pattern = '/<!-- RIGHT COLUMN -->\s*<div class="grid-col-right">\s*<h3><i class=\'bx bx-user-circle\'><\/i> Emergency Contact<\/h3>/s';
$replacement = '<!-- RIGHT COLUMN -->
        <div class="grid-col-right">
            <!-- Account & Security -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class=\'bx bx-check-shield\'></i> Account & Security</h3>
                </div>
                <div class="info-list">
                    <div class="info-row">
                        <div class="info-label"><i class=\'bx bx-lock-alt\'></i> Password</div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="info-value">••••••••</div>
                            <button class="btn-outline" style="padding: 4px 12px; flex-shrink: 0;" onclick="alert(\'Password change modal\')">Change</button>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class=\'bx bx-envelope\'></i> Login Email</div>
                        <?php
                        $login_email = $user[\'email\'] ?: \'user@example.com\';
                        $len_login = strlen($login_email);
                        $fs_login = $len_login > 30 ? \'11px\' : ($len_login > 24 ? \'12px\' : ($len_login > 18 ? \'13px\' : \'14px\'));
                        ?>
                        <div style="display: flex; align-items: center; gap: 12px; max-width: 70%;">
                            <div class="info-value" style="font-size: <?php echo $fs_login; ?>; max-width: 100%; white-space: nowrap;"><?php echo htmlspecialchars($login_email); ?></div>
                            <button class="btn-outline" style="padding: 4px 12px; flex-shrink: 0;">Change</button>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class=\'bx bx-shield-quarter\'></i> Two-Factor Auth</div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="info-value value-green">Enabled</div>
                            <button class="btn-outline" style="padding: 4px 12px; flex-shrink: 0;">Manage</button>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class=\'bx bx-info-circle\'></i> Account Status</div>
                        <div class="info-value value-green">Active</div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="panel">
                  <div class="panel-header">
                      <h3><i class=\'bx bx-user-circle\'></i> Emergency Contact</h3>';

$c = preg_replace($pattern, $replacement, $c, 1);
file_put_contents("profile.php", $c);
echo "Restored Account & Security successfully\n";
?>
