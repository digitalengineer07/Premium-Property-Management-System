import sys

with open('c:/xampp/htdocs/renter-system/admin/view-renter.php', 'r', encoding='utf-8') as f:
    text = f.read()

# Grab the whole content starting from the welcome down to </main>
start_idx = text.find('<div class="dashboard-grid-70 animate-up"')
end_idx = text.find('</main>')

if start_idx == -1 or end_idx == -1:
    print('Error finding bounds')
    sys.exit(1)

# We also need the raw tables.
utility_idx = text.find('<div class="panel">\n                <div class="panel-header">\n                    <h2 style="font-size: 18px; font-weight: 700;">Utility History</h2>')
rent_idx = text.find('<div class="panel">\n                <div class="panel-header">\n                    <h2 style="font-size: 18px; font-weight: 700;">Rent History</h2>')

if utility_idx == -1 or rent_idx == -1:
    print('Error finding tables')
    print(utility_idx, rent_idx)
    sys.exit(1)

utility_html = text[utility_idx:rent_idx].strip()
# Rent html ends at closing main. Wait, it currently ends where?
# We need to find the exact end of rent_html panel.
rent_end = text.find('</div>\n    </div>\n</main>', rent_idx)
# If we have wrapped it in a flex container previously:
rent_end_div = text.find('--bg-main', rent_idx) # wait, no, rent HTML is just the panel.
# Let's just find the closing tag of the Rent History table.
# It's a <div class="panel">...</div>
# We can regex or count divs. Let's just do a simple substring since we know it's a panel.
rent_html_raw = text[rent_idx:]
end_panel = rent_html_raw.find('</div>\n    </div>\n</main>')
if end_panel == -1:
    end_panel = rent_html_raw.find('</main>')

rent_html_panel = rent_html_raw[:end_panel].strip()

# wait, rent_html_panel might contain extra `</div>`s from the wrapper. Let's strictly cut the rent panel.
# rent table ends with `</tbody>\n                    </table>\n                </div>\n            </div>` (or `</div>\n    </div>`)
rent_panel_end = rent_html_raw.find('</table>\n                </div>')
rent_panel_end_full = rent_html_raw.find('</div>', rent_panel_end + len('</table>\n                </div>'))
rent_panel_end_full = rent_html_raw.find('</div>', rent_panel_end_full + 1) + 6
rent_html = rent_html_raw[:rent_panel_end_full].strip()

# Now craft the new Hero Profile Card
hero_html = """
    <div class="panel animate-up" style="padding: 0; overflow: hidden; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: none;">
        <div style="height: 160px; background: linear-gradient(135deg, var(--primary-purple), #93A5CF); position: relative;">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.1; background-image: radial-gradient(circle at right, #fff 0%, transparent 50%);"></div>
        </div>
        
        <div style="padding: 0 32px 32px 32px; position: relative;">
            <div style="display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 24px; margin-top: -60px; margin-bottom: 32px; border-bottom: 1px solid var(--border); padding-bottom: 24px;">
                <div style="display: flex; align-items: flex-end; gap: 24px;">
                    <div class="avatar" style="width: 130px; height: 130px; border: 6px solid var(--white); background-image: url('../<?php echo $user['profile_pic'] ?: 'assets/img/default-avatar.png'; ?>'); background-size: cover; background-position: center; border-radius: 24px; box-shadow: var(--card-shadow); flex-shrink: 0; background-color: #F8FAFC; z-index: 2;"></div>
                    <div style="padding-bottom: 4px;">
                        <h2 style="font-weight: 800; font-size: 32px; line-height: 1.1; margin-bottom: 4px; color: var(--text-dark);"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p style="color: var(--text-gray); font-size: 15px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                            <i class='bx bx-user-circle' style="font-size: 18px;"></i> @<?php echo htmlspecialchars($user['username']); ?> 
                            <span style="color: var(--border);">|</span> 
                            <span style="color: var(--primary-purple); background: rgba(98, 75, 255, 0.1); padding: 2px 10px; border-radius: 20px; font-weight: 700; font-size: 12px;"><i class='bx bx-door-open'></i> Room <?php echo htmlspecialchars($user['room_no'] ?: 'N/A'); ?></span>
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; padding-bottom: 6px; flex-wrap: wrap;">
                    <a href="bill-generator.php?user_id=<?php echo $user['id']; ?>" class="btn-primary" style="padding: 10px 18px; box-shadow: 0 4px 12px rgba(98, 75, 255, 0.2);"><i class='bx bx-plus'></i> New Bill</a>
                    <a href="edit-renter.php?id=<?php echo $user['id']; ?>" class="btn-outline" style="padding: 10px 18px; background: white;"><i class='bx bx-edit-alt'></i> Edit Profile</a>
                    <button onclick="openAgreementModal()" class="btn-outline" style="padding: 10px 18px; background: white;"><i class='bx bx-upload'></i> Agreement</button>
                    <button onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name']); ?>')" class="btn-outline" style="padding: 10px 18px; border-color: #FCA5A5; color: #EF4444; background: #FEF2F2;"><i class='bx bx-lock-alt'></i> Password</button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 32px;">
                
                <!-- Contact Info -->
                <div>
                    <h4 style="font-size: 13px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; font-weight: 700;">Contact Details</h4>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; color: var(--text-dark);">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(98,75,255,0.08); display: flex; align-items: center; justify-content: center; color: var(--primary-purple); font-size: 18px;"><i class='bx bx-phone'></i></div>
                            <?php echo htmlspecialchars($user['phone'] ?: 'No Phone Number'); ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; color: var(--text-dark);">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(239,68,68,0.08); display: flex; align-items: center; justify-content: center; color: #EF4444; font-size: 18px;"><i class='bx bx-envelope'></i></div>
                            <?php echo htmlspecialchars($user['email'] ?: 'No Email Address'); ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; color: var(--text-dark);">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(37,211,102,0.08); display: flex; align-items: center; justify-content: center; color: #25D366; font-size: 18px;"><i class='bx bxl-whatsapp'></i></div>
                            <?php echo htmlspecialchars($user['whatsapp'] ?: 'No WhatsApp'); ?>
                        </div>
                    </div>
                </div>

                <!-- Financial Status -->
                <div>
                    <h4 style="font-size: 13px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; font-weight: 700;">Financial Snapshot</h4>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if ($user['pending_adjustment'] != 0): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 12px; background: <?php echo $user['pending_adjustment'] > 0 ? 'rgba(16,185,129,0.08)' : 'rgba(239,68,68,0.08)'; ?>; border: 1px solid <?php echo $user['pending_adjustment'] > 0 ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)'; ?>;">
                            <span style="font-size: 14px; font-weight: 600; color: <?php echo $user['pending_adjustment'] > 0 ? '#059669' : '#DC2626'; ?>; display: flex; align-items: center; gap: 8px;"><i class='bx bx-wallet-alt' style="font-size: 18px;"></i> <?php echo $user['pending_adjustment'] > 0 ? 'Total Credit' : 'Pending Due'; ?></span>
                            <span style="font-weight: 800; font-size: 16px; color: <?php echo $user['pending_adjustment'] > 0 ? '#059669' : '#DC2626'; ?>;">₹<?php echo number_format(abs($user['pending_adjustment']), 2); ?></span>
                        </div>
                        <?php endif; ?>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 12px; background: #F8FAFC; border: 1px solid var(--border);">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 14px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 8px;"><i class='bx bx-money' style="color: var(--primary-purple); font-size: 18px;"></i> Security Deposit</span>
                            </div>
                            <span style="font-weight: 800; font-size: 15px; color: var(--text-dark);">₹<?php echo number_format($user['advance_payment'] ?? 0, 2); ?></span>
                        </div>
                        
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 12px; background: #F8FAFC; border: 1px solid var(--border);">
                            <div style="display: flex; flex-direction: column;">
                                <span style="font-size: 14px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 8px;"><i class='bx bx-home-circle' style="color: #10B981; font-size: 18px;"></i> Fixed Charges</span>
                                <span style="font-size: 12px; color: var(--text-gray); margin-left: 26px; font-weight: 500;">Rent: ₹<?php echo number_format($user['fixed_rent'] ?? 0); ?> &bull; Maint: ₹<?php echo number_format($user['fixed_maintenance'] ?? 0); ?></span>
                            </div>
                            <span style="font-weight: 800; font-size: 15px; color: var(--text-dark);">₹<?php echo number_format(($user['fixed_rent'] ?? 0) + ($user['fixed_maintenance'] ?? 0), 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Documents -->
                <div>
                    <h4 style="font-size: 13px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; font-weight: 700;">Documentation</h4>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php if (!empty($user['aadhaar_file'])): ?>
                            <a href="download.php?type=aadhaar&user_id=<?php echo (int)$user['id']; ?>" target="_blank" class="btn-outline" style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; background: white; border: 1px solid var(--primary-purple); color: var(--primary-purple);">
                                <span style="display: flex; align-items: center; gap: 10px; font-weight: 600;"><i class='bx bx-id-card' style="font-size: 20px;"></i> Identity Proof (Aadhaar)</span>
                                <i class='bx bx-link-external'></i>
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($user['agreement_document'])): ?>
                            <?php 
                                $expiry_status = "";
                                if ($user['agreement_expiry_date']) {
                                    $days = (strtotime($user['agreement_expiry_date']) - time()) / 86400;
                                    if ($days < 0) $expiry_status = "Expired";
                                    elseif ($days <= 30) $expiry_status = "Expiring Soon";
                                }
                            ?>
                            <a href="download-agreement.php?id=<?php echo (int)$user['id']; ?>" target="_blank" class="btn-outline" style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 14px 16px; position: relative; background: white; border: 1px solid #10B981; color: #10B981;">
                                <span style="display: flex; align-items: center; gap: 10px; font-weight: 600;"><i class='bx bx-file' style="font-size: 20px;"></i> Room Rental Agreement</span>
                                <i class='bx bx-link-external'></i>
                                <?php if ($expiry_status): ?>
                                    <span class="badge" style="position: absolute; top: -10px; right: 10px; font-size: 10px; padding: 4px 8px; background: <?php echo $expiry_status == 'Expired' ? '#EF4444' : '#F59E0B'; ?>; color: white; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                        <?php echo $expiry_status; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>

                        <?php if (empty($user['aadhaar_file']) && empty($user['agreement_document'])): ?>
                            <div style="background: #F8FAFC; padding: 24px; border-radius: 12px; text-align: center; font-size: 14px; font-weight: 500; color: var(--text-gray); border: 1px dashed var(--border);">
                                <i class='bx bx-folder-open' style="font-size: 32px; margin-bottom: 12px; display: block; color: var(--text-gray); opacity: 0.5;"></i>
                                No documents uploaded yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            
            <?php if(!empty($user['about'])): ?>
            <div style="margin-top: 32px; padding: 20px; background: #F8FAFC; border-radius: 16px; border: 1px solid var(--border);">
                <h4 style="font-size: 12px; color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 700; display: flex; align-items: center; gap: 6px;"><i class='bx bx-note'></i> Admin Notes / About</h4>
                <p style="font-size: 15px; line-height: 1.6; color: var(--text-dark); margin: 0; font-weight: 500;"><?php echo nl2br(htmlspecialchars($user['about'])); ?></p>
            </div>
            <?php endif; ?>

        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;" class="animate-up">
        {utility_html}
        {rent_html}
    </div>
"""

hero_html = hero_html.replace('{utility_html}', utility_html).replace('{rent_html}', rent_html)

new_content = text[:start_idx] + hero_html + "\n</main>" + text[text.find('<!-- Password Reset Modal -->') - 5:]

with open('c:/xampp/htdocs/renter-system/admin/view-renter.php', 'w', encoding='utf-8') as f:
    f.write(new_content)

print(f"Successfully rebuilt layout.")
