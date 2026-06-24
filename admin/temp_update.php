<?php
$file = 'C:/xampp/htdocs/renter-system/admin/view-renter.php';
$content = file_get_contents($file);

// We want to replace from `<div class="welcome animate-up">` to `</main>`
$startStr = '<div class="welcome animate-up">';
$endStr = '</main>';

$startPos = strpos($content, $startStr);
$endPos = strpos($content, $endStr, $startPos) + strlen($endStr);

$newContent = <<<'HTML'
    <?php 
        $initials = '';
        $nameParts = explode(' ', $user['name']);
        if (isset($nameParts[0])) $initials .= strtoupper(substr($nameParts[0], 0, 1));
        if (isset($nameParts[1])) $initials .= strtoupper(substr($nameParts[1], 0, 1));
    ?>

    <!-- 1. Top Header Card -->
    <div class="panel animate-up" style="margin-bottom: 24px; padding: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 24px;">
            <div style="display: flex; gap: 24px; align-items: center;">
                <?php if ($user['profile_pic']): ?>
                    <div style="width: 80px; height: 80px; border-radius: 50%; background-image: url('../<?php echo htmlspecialchars($user['profile_pic']); ?>'); background-size: cover; background-position: center; border: 2px solid #F8FAFC;"></div>
                <?php else: ?>
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: #F4F7FF; color: #624BFF; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 28px; border: 2px solid #FFFFFF; box-shadow: 0 4px 10px rgba(98, 75, 255, 0.1);"><?php echo $initials ?: '?'; ?></div>
                <?php endif; ?>
                
                <div>
                    <h2 style="font-weight: 800; font-size: 24px; margin: 0 0 8px 0; color: var(--text-dark); display: flex; align-items: center; gap: 12px;">
                        <?php echo htmlspecialchars($user['name']); ?>
                    </h2>
                    <div style="display: flex; align-items: center; gap: 12px; color: var(--text-gray); font-size: 13px; font-weight: 500;">
                        <span><i class='bx bx-user-circle'></i> @<?php echo htmlspecialchars($user['username']); ?></span>
                        <span style="color: var(--border);">|</span> 
                        <span style="color: var(--primary-purple); background: rgba(98, 75, 255, 0.1); padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 12px;"><i class='bx bx-door-open'></i> Room <?php echo htmlspecialchars($user['room_no'] ?: 'N/A'); ?></span>
                        <?php if (($user['status'] ?? 'active') == 'active'): ?>
                            <span style="color: var(--border);">|</span>
                            <span style="color: #10B981; font-weight: 600;"><i class='bx bxs-circle' style="font-size: 8px;"></i> Active</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="bill-generator.php?user_id=<?php echo $user['id']; ?>" class="btn-primary" style="padding: 10px 20px; border-radius: 8px;"><i class='bx bx-plus'></i> New Bill</a>
                <a href="edit-renter.php?id=<?php echo $user['id']; ?>" class="btn-outline" style="padding: 10px 20px; border-radius: 8px; background: transparent;"><i class='bx bx-edit-alt'></i> Edit Profile</a>
                <button onclick="openAgreementModal()" class="btn-outline" style="padding: 10px 20px; border-radius: 8px; background: transparent;"><i class='bx bx-upload'></i> Agreement</button>
                
                <div style="position: relative; display: inline-block;">
                    <button onclick="document.getElementById('moreDropdown').style.display = document.getElementById('moreDropdown').style.display === 'flex' ? 'none' : 'flex'" class="btn-outline" style="padding: 10px 20px; border-radius: 8px; background: transparent;"><i class='bx bx-dots-horizontal-rounded'></i> More</button>
                    <div id="moreDropdown" style="display: none; position: absolute; right: 0; top: calc(100% + 8px); background: #FFFFFF; border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 180px; z-index: 100; flex-direction: column; overflow: hidden;">
                        <button onclick="openAadhaarModal(); document.getElementById('moreDropdown').style.display='none';" style="padding: 12px 16px; text-align: left; background: none; border: none; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--text-dark); cursor: pointer; display: flex; align-items: center; gap: 8px;"><i class='bx bx-id-card' style="font-size: 16px; color: #3B82F6;"></i> Aadhaar</button>
                        <button onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name']); ?>'); document.getElementById('moreDropdown').style.display='none';" style="padding: 12px 16px; text-align: left; background: none; border: none; font-size: 13px; color: #EF4444; cursor: pointer; display: flex; align-items: center; gap: 8px;"><i class='bx bx-lock-alt' style="font-size: 16px;"></i> Password</button>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 32px; align-items: center; border-top: 1px solid var(--border); padding-top: 24px; margin-top: 24px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(98,75,255,0.08); display: flex; align-items: center; justify-content: center; color: var(--primary-purple); font-size: 18px;"><i class='bx bx-phone'></i></div>
                <div>
                    <div style="font-weight: 600; color: var(--text-dark); font-size: 13px;"><?php echo htmlspecialchars($user['phone'] ?: 'No Phone Number'); ?></div>
                    <div style="color: var(--text-gray); font-size: 11px;">Phone</div>
                </div>
            </div>
            
            <div style="width: 1px; height: 32px; background: var(--border);"></div>
            
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(98,75,255,0.08); display: flex; align-items: center; justify-content: center; color: var(--primary-purple); font-size: 18px;"><i class='bx bx-envelope'></i></div>
                <div>
                    <div style="font-weight: 600; color: var(--text-dark); font-size: 13px;"><?php echo htmlspecialchars($user['email'] ?: 'No Email Address'); ?></div>
                    <div style="color: var(--text-gray); font-size: 11px;">Email</div>
                </div>
            </div>

            <div style="width: 1px; height: 32px; background: var(--border);"></div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(37,211,102,0.08); display: flex; align-items: center; justify-content: center; color: #25D366; font-size: 18px;"><i class='bx bxl-whatsapp'></i></div>
                <div>
                    <div style="font-weight: 600; color: var(--text-dark); font-size: 13px;"><?php echo htmlspecialchars($user['whatsapp'] ?: 'No WhatsApp'); ?></div>
                    <div style="color: var(--text-gray); font-size: 11px;">WhatsApp</div>
                </div>
            </div>

            <?php if(!empty($user['joining_date'])): ?>
            <div style="width: 1px; height: 32px; background: var(--border);"></div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(59,130,246,0.08); display: flex; align-items: center; justify-content: center; color: #3B82F6; font-size: 18px;"><i class='bx bx-calendar'></i></div>
                <div>
                    <div style="font-weight: 600; color: var(--text-dark); font-size: 13px;"><?php echo date('M d, Y', strtotime($user['joining_date'])); ?></div>
                    <div style="color: var(--text-gray); font-size: 11px;">Member Since</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Middle Section -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;" class="animate-up dashboard-grid-70">
        <!-- Contact Details -->
        <div class="panel">
            <h4 style="font-size: 14px; color: var(--text-dark); margin-bottom: 20px; font-weight: 700; display: flex; align-items: center; gap: 8px;"><div style="width: 32px; height: 32px; background: rgba(98,75,255,0.1); color: var(--primary-purple); border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class='bx bx-user-circle'></i></div> Contact Details</h4>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 16px; border-bottom: 1px solid var(--border); padding-bottom: 16px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(98,75,255,0.08); display: flex; align-items: center; justify-content: center; color: var(--primary-purple); font-size: 20px;"><i class='bx bx-phone'></i></div>
                    <div style="flex: 1;">
                        <div style="color: var(--text-gray); font-size: 12px; margin-bottom: 2px;">Phone</div>
                        <div style="font-weight: 600; color: var(--text-dark); font-size: 14px;"><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></div>
                    </div>
                    <div style="color: var(--text-gray);"><i class='bx bx-phone-call'></i></div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 16px; border-bottom: 1px solid var(--border); padding-bottom: 16px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(98,75,255,0.08); display: flex; align-items: center; justify-content: center; color: var(--primary-purple); font-size: 20px;"><i class='bx bx-envelope'></i></div>
                    <div style="flex: 1;">
                        <div style="color: var(--text-gray); font-size: 12px; margin-bottom: 2px;">Email</div>
                        <div style="font-weight: 600; color: var(--text-dark); font-size: 14px;"><?php echo htmlspecialchars($user['email'] ?: 'N/A'); ?></div>
                    </div>
                    <div style="color: #EF4444;"><i class='bx bx-envelope'></i></div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 16px; border-bottom: 1px solid var(--border); padding-bottom: 16px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(37,211,102,0.08); display: flex; align-items: center; justify-content: center; color: #25D366; font-size: 20px;"><i class='bx bxl-whatsapp'></i></div>
                    <div style="flex: 1;">
                        <div style="color: var(--text-gray); font-size: 12px; margin-bottom: 2px;">WhatsApp</div>
                        <div style="font-weight: 600; color: var(--text-dark); font-size: 14px;"><?php echo htmlspecialchars($user['whatsapp'] ?: 'N/A'); ?></div>
                    </div>
                    <div style="color: #25D366;"><i class='bx bxl-whatsapp'></i></div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(98,75,255,0.08); display: flex; align-items: center; justify-content: center; color: var(--primary-purple); font-size: 20px;"><i class='bx bx-calendar'></i></div>
                    <div style="flex: 1;">
                        <div style="color: var(--text-gray); font-size: 12px; margin-bottom: 2px;">Member Since</div>
                        <div style="font-weight: 600; color: var(--text-dark); font-size: 14px;"><?php echo !empty($user['joining_date']) ? date('M d, Y', strtotime($user['joining_date'])) : 'N/A'; ?></div>
                    </div>
                </div>
            </div>
            
            <?php if(!empty($user['about'])): ?>
            <div style="margin-top: 24px; padding: 16px; background: #F8FAFC; border-radius: 12px; border: 1px solid var(--border);">
                <h4 style="font-size: 12px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; font-weight: 700;"><i class='bx bx-note'></i> Admin Notes</h4>
                <p style="font-size: 13px; line-height: 1.5; color: var(--text-dark); margin: 0;"><?php echo nl2br(htmlspecialchars($user['about'])); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Financial Snapshot -->
        <div class="panel">
            <h4 style="font-size: 14px; color: var(--text-dark); margin-bottom: 20px; font-weight: 700; display: flex; align-items: center; gap: 8px;"><div style="width: 32px; height: 32px; background: rgba(98,75,255,0.1); color: var(--primary-purple); border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class='bx bx-wallet'></i></div> Financial Snapshot</h4>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid var(--border); border-radius: 12px;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(16,185,129,0.1); display: flex; align-items: center; justify-content: center; color: #10B981; font-size: 20px;"><i class='bx bx-check-shield'></i></div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-dark); font-size: 14px;">Security Deposit</div>
                            <?php if ($advance_due > 0): ?>
                                <div style="color: #EF4444; font-size: 12px; font-weight: 600; margin-top: 2px;">Due: ₹<?php echo number_format($advance_due, 2); ?></div>
                                <button onclick="openPaymentModal('advance', <?php echo $user['id']; ?>, <?php echo $advance_due; ?>, 'Advance Security')" class="btn-primary" style="margin-top: 6px; font-size: 11px; padding: 4px 10px; width: max-content;">Mark Paid</button>
                            <?php else: ?>
                                <div style="color: #10B981; font-size: 12px; font-weight: 500; margin-top: 2px;">Fully Paid</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="font-weight: 700; font-size: 16px; color: #10B981;">₹<?php echo number_format($user['advance_payment'] ?? 0, 2); ?></div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid var(--border); border-radius: 12px;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(59,130,246,0.1); display: flex; align-items: center; justify-content: center; color: #3B82F6; font-size: 20px;"><i class='bx bx-home'></i></div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-dark); font-size: 14px;">Fixed Charges</div>
                            <div style="color: var(--text-gray); font-size: 12px; font-weight: 500; margin-top: 2px;">Rent: ₹<?php echo number_format($user['fixed_rent'] ?? 0); ?> &bull; Maint: ₹<?php echo number_format($user['fixed_maintenance'] ?? 0); ?></div>
                        </div>
                    </div>
                    <div style="font-weight: 700; font-size: 16px; color: #3B82F6;">₹<?php echo number_format(($user['fixed_rent'] ?? 0) + ($user['fixed_maintenance'] ?? 0), 2); ?></div>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px; border: 1px solid <?php echo $user['pending_adjustment'] > 0 ? 'rgba(16,185,129,0.2)' : ($user['pending_adjustment'] < 0 ? 'rgba(239,68,68,0.2)' : 'var(--border)'); ?>; border-radius: 12px; background: <?php echo $user['pending_adjustment'] > 0 ? 'rgba(16,185,129,0.05)' : ($user['pending_adjustment'] < 0 ? 'rgba(239,68,68,0.05)' : 'transparent'); ?>;">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(239,68,68,0.1); display: flex; align-items: center; justify-content: center; color: #EF4444; font-size: 20px;"><i class='bx bx-file'></i></div>
                        <div>
                            <div style="font-weight: 700; color: var(--text-dark); font-size: 14px;"><?php echo $user['pending_adjustment'] > 0 ? 'Total Credit' : 'Total Outstanding'; ?></div>
                            <div style="color: var(--text-gray); font-size: 12px; font-weight: 500; margin-top: 2px;"><?php echo $user['pending_adjustment'] == 0 ? 'All pending dues cleared' : ($user['pending_adjustment'] > 0 ? 'Credit balance available' : 'Pending dues to be paid'); ?></div>
                        </div>
                    </div>
                    <div style="font-weight: 700; font-size: 16px; color: <?php echo $user['pending_adjustment'] > 0 ? '#10B981' : '#94A3B8'; ?>;"><?php echo $user['pending_adjustment'] < 0 ? '<span style="color:#EF4444;">₹'.number_format(abs($user['pending_adjustment']), 2).'</span>' : '₹'.number_format($user['pending_adjustment'], 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Bottom Section (3 columns) -->
    <div style="display: grid; grid-template-columns: 1fr 1.5fr 1.5fr; gap: 24px; margin-bottom: 24px;" class="animate-up dashboard-grid-70">
        <!-- Documents -->
        <div class="panel">
            <h4 style="font-size: 14px; color: var(--text-dark); margin-bottom: 20px; font-weight: 700; display: flex; align-items: center; gap: 8px;"><div style="width: 32px; height: 32px; background: rgba(98,75,255,0.1); color: var(--primary-purple); border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class='bx bx-file'></i></div> Documents</h4>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php if (!empty($user['aadhaar_file'])): ?>
                <div style="padding: 16px; border: 1px solid var(--border); border-radius: 12px; background: #FFFFFF;">
                    <div style="font-weight: 600; color: var(--text-dark); font-size: 13px; margin-bottom: 12px;">Identity Proof (Aadhaar)</div>
                    <div style="display: flex; gap: 12px; justify-content: space-between;">
                        <a href="download.php?type=aadhaar&user_id=<?php echo (int)$user['id']; ?>" target="_blank" style="padding: 8px 16px; border: 1px solid var(--primary-purple); color: var(--primary-purple); border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; justify-content: center; flex: 1;">View Document</a>
                        <a href="delete-doc.php?type=aadhaar&user_id=<?php echo (int)$user['id']; ?>" onclick="return confirm('Delete this Aadhaar document?');" style="width: 36px; height: 36px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); color: #EF4444; display: flex; align-items: center; justify-content: center; text-decoration: none;"><i class='bx bx-trash'></i></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($user['agreement_document'])): ?>
                <div style="padding: 16px; border: 1px solid var(--border); border-radius: 12px; background: #FFFFFF;">
                    <div style="font-weight: 600; color: var(--text-dark); font-size: 13px; margin-bottom: 12px;">Rental Agreement</div>
                    <div style="display: flex; gap: 12px; justify-content: space-between;">
                        <a href="download-agreement.php?id=<?php echo (int)$user['id']; ?>" target="_blank" style="padding: 8px 16px; border: 1px solid #10B981; color: #10B981; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; justify-content: center; flex: 1;">View Document</a>
                        <a href="delete-doc.php?type=agreement&user_id=<?php echo (int)$user['id']; ?>" onclick="return confirm('Delete this Agreement?');" style="width: 36px; height: 36px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); color: #EF4444; display: flex; align-items: center; justify-content: center; text-decoration: none;"><i class='bx bx-trash'></i></a>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($user['aadhaar_file']) && empty($user['agreement_document'])): ?>
                <div style="padding: 32px 16px; text-align: center; border: 1px dashed var(--border); border-radius: 12px; color: var(--text-gray); font-size: 13px;">
                    <i class='bx bx-folder-open' style="font-size: 24px; margin-bottom: 8px; color: #CBD5E1;"></i><br>
                    No documents uploaded
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Utility History -->
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="font-size: 14px; color: var(--text-dark); margin: 0; font-weight: 700;">Utility History</h4>
                <span style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;"><?php echo count($elecs); ?> Records</span>
            </div>
            
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Month</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Units</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Amount</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Status</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($elecs)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--text-gray); font-size: 13px;">No utility records.</td></tr>
                        <?php else: ?>
                            <?php $shown_elecs = array_slice($elecs, 0, 3); foreach ($shown_elecs as $e): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 8px; font-weight: 600; font-size: 12px; color: var(--text-dark);"><?php echo htmlspecialchars($e['month']); ?></td>
                                <td style="padding: 12px 8px; font-size: 12px; color: var(--text-gray);"><?php echo htmlspecialchars($e['units_consumed'] ?? ($e['current_reading'] - $e['previous_reading'])); ?> Units</td>
                                <td style="padding: 12px 8px; font-weight: 700; font-size: 12px; color: var(--text-dark);">₹<?php echo number_format($e['total_amount'], 2); ?></td>
                                <td style="padding: 12px 8px;"><span style="font-size: 10px; font-weight: 600; padding: 4px 8px; border-radius: 4px; <?php echo $e['status'] == 'Paid' ? 'color: #10B981; background: rgba(16,185,129,0.1);' : ($e['status'] == 'Partial' ? 'color: #F59E0B; background: rgba(245,158,11,0.1);' : 'color: #EF4444; background: rgba(239,68,68,0.1);'); ?>"><?php echo $e['status']; ?></span></td>
                                <td style="padding: 12px 8px;">
                                    <?php if($e['status'] != 'Paid'): $remaining = max(0, $e['total_amount'] - $e['total_paid']); ?>
                                        <button onclick="openPaymentModal('electricity', <?php echo $e['id']; ?>, <?php echo $remaining; ?>, '<?php echo addslashes($e['month']); ?>')" style="background: var(--primary-purple); color: #FFF; border: none; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">Pay</button>
                                    <?php else: ?>
                                        <a href="slip.php?elec_id=<?php echo $e['id']; ?>" target="_blank" style="color: var(--text-gray); text-decoration: none; border: 1px solid var(--border); padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600;">Slip</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 16px; padding-top: 16px;">
                <a href="electricity-list.php?search=<?php echo urlencode($user['name']); ?>" style="color: var(--primary-purple); font-size: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px;">View All Utility History <i class='bx bx-right-arrow-alt'></i></a>
            </div>
        </div>

        <!-- Rent History -->
        <div class="panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="font-size: 14px; color: var(--text-dark); margin: 0; font-weight: 700;">Rent History</h4>
                <span style="background: rgba(98, 75, 255, 0.1); color: var(--primary-purple); padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;"><?php echo count($rents); ?> Records</span>
            </div>
            
            <div class="table-responsive">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Month</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Details</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Total</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Status</th>
                            <th style="padding: 12px 8px; text-align: left; font-size: 11px; color: var(--text-gray); text-transform: uppercase;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rents)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--text-gray); font-size: 13px;">No rent records.</td></tr>
                        <?php else: ?>
                            <?php $shown_rents = array_slice($rents, 0, 3); foreach ($shown_rents as $r): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 8px; font-weight: 600; font-size: 12px; color: var(--text-dark);"><?php echo htmlspecialchars($r['month']); ?></td>
                                <td style="padding: 12px 8px; font-size: 10px; color: var(--text-gray);">Rent: ₹<?php echo number_format($r['rent_amount']); ?><br>Maint: ₹<?php echo number_format($r['maintenance']); ?></td>
                                <td style="padding: 12px 8px; font-weight: 700; font-size: 12px; color: var(--text-dark);">₹<?php echo number_format($r['rent_amount'] + $r['maintenance'], 2); ?></td>
                                <td style="padding: 12px 8px;"><span style="font-size: 10px; font-weight: 600; padding: 4px 8px; border-radius: 4px; <?php echo $r['status'] == 'Paid' ? 'color: #10B981; background: rgba(16,185,129,0.1);' : ($r['status'] == 'Partial' ? 'color: #F59E0B; background: rgba(245,158,11,0.1);' : 'color: #EF4444; background: rgba(239,68,68,0.1);'); ?>"><?php echo $r['status']; ?></span></td>
                                <td style="padding: 12px 8px;">
                                    <?php if($r['status'] != 'Paid'): $remaining = max(0, ($r['rent_amount'] + $r['maintenance']) - $r['total_paid']); ?>
                                        <button onclick="openPaymentModal('electricity', <?php echo $r['id']; ?>, <?php echo $remaining; ?>, '<?php echo addslashes($r['month']); ?>')" style="background: var(--primary-purple); color: #FFF; border: none; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">Pay</button>
                                    <?php else: ?>
                                        <span style="color: var(--text-gray); padding: 4px 12px; font-size: 11px; font-weight: 600;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 16px; padding-top: 16px;">
                <a href="electricity-list.php?search=<?php echo urlencode($user['name']); ?>" style="color: var(--primary-purple); font-size: 12px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 4px;">View All Rent History <i class='bx bx-right-arrow-alt'></i></a>
            </div>
        </div>
    </div>
</main>
HTML;

$content = substr($content, 0, $startPos) . $newContent . substr($content, $endPos);
file_put_contents($file, $content);
echo "Successfully updated view-renter.php";
?>
