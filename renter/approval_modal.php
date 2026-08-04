<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
<div id="approvalModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
    <div id="approvalModalPanel" class="no-scrollbar" style="background: var(--white); border-radius: 24px; padding: 24px 32px; width: 100%; max-width: 450px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: relative; max-height: 90vh; overflow-y: auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-check-shield' style="color: var(--primary-purple); font-size: 24px;"></i> Apply for Approval
            </h2>
            <button onclick="closeApprovalModal()" style="background: rgba(0,0,0,0.05); border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-gray); transition: 0.2s;">
                <i class='bx bx-x' style="font-size: 20px;"></i>
            </button>
        </div>

        <form method="POST" id="approvalForm" action="process_payment.php">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
            <input type="hidden" name="return_url" value="payment-approvals.php">
            
            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Amount (&#8377;)</label>
                <div style="position: relative;">
                    <i class='bx bx-rupee' style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 20px; color: var(--text-gray);"></i>
                    <input type="number" name="amount" id="approvalAmount" step="0.01" required placeholder="Enter amount" style="width: 100%; box-sizing: border-box; padding: 14px 14px 14px 40px; border: 1px solid var(--border); border-radius: 12px; font-size: 15px; font-weight: 600; outline: none; background: #f8fafc; color: var(--text-dark);">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Payment Mode</label>
                <div style="display: flex; gap: 12px;">
                    <label style="flex: 1; border: 1px solid var(--primary-purple); background: rgba(98, 75, 255, 0.05); color: var(--primary-purple); padding: 12px; border-radius: 12px; text-align: center; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; font-size: 14px;" class="app-mode-label" id="app-label-upi">
                        <input type="radio" name="payment_method" value="UPI" checked style="display: none;" onchange="toggleApprovalMode('UPI')">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e1/UPI-Logo-vector.svg" alt="UPI" style="height: 14px;">
                    </label>
                    <label style="flex: 1; border: 1px solid var(--border); background: var(--white); color: var(--text-gray); padding: 12px; border-radius: 12px; text-align: center; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; font-size: 14px;" class="app-mode-label" id="app-label-cash">
                        <input type="radio" name="payment_method" value="Cash" style="display: none;" onchange="toggleApprovalMode('Cash')">
                        <i class='bx bx-money' style="font-size: 18px;"></i> Cash
                    </label>
                </div>
            </div>

            <div id="approvalRefContainer" style="margin-bottom: 24px;">
                <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Transaction ID / UTR No.</label>
                <input type="text" name="transaction_id" id="approvalRef" placeholder="Enter 12-digit UTR number" style="width: 100%; box-sizing: border-box; padding: 14px; border: 1px solid var(--border); border-radius: 12px; font-size: 14px; outline: none; background: #f8fafc; color: var(--text-dark);">
                <p style="font-size: 11px; color: var(--text-gray); margin: 6px 0 0 0;"><i class='bx bx-info-circle'></i> Required for UPI payments to verify transaction.</p>
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Bill Month (Optional)</label>
                <input type="month" name="bill_month" id="approvalMonth" style="width: 100%; box-sizing: border-box; padding: 14px; border: 1px solid var(--border); border-radius: 12px; font-size: 14px; outline: none; background: #f8fafc; color: var(--text-dark);">
            </div>

            <button type="submit" name="submit_payment_notif" class="btn-primary" style="width: 100%; justify-content: center; padding: 16px; font-size: 15px; border-radius: 12px; box-shadow: 0 6px 16px rgba(98, 75, 255, 0.25); background: var(--primary-purple); color: white; border: none; cursor: pointer; font-weight: 700;">
                <i class='bx bx-check-shield' style="font-size: 18px;"></i> Submit for Approval
            </button>
        </form>
    </div>
</div>

<script>
function openApprovalModal() {
    document.getElementById('approvalModal').style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeApprovalModal() {
    document.getElementById('approvalModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function toggleApprovalMode(mode) {
    const labelUpi = document.getElementById('app-label-upi');
    const labelCash = document.getElementById('app-label-cash');
    const refContainer = document.getElementById('approvalRefContainer');
    const refInput = document.getElementById('approvalRef');

    if (mode === 'UPI') {
        labelUpi.style.border = '1px solid var(--primary-purple)';
        labelUpi.style.background = 'rgba(98, 75, 255, 0.05)';
        labelUpi.style.color = 'var(--primary-purple)';
        
        labelCash.style.border = '1px solid var(--border)';
        labelCash.style.background = 'var(--white)';
        labelCash.style.color = 'var(--text-gray)';
        
        refContainer.style.display = 'block';
        refInput.setAttribute('required', 'required');
    } else {
        labelCash.style.border = '1px solid var(--primary-purple)';
        labelCash.style.background = 'rgba(98, 75, 255, 0.05)';
        labelCash.style.color = 'var(--primary-purple)';
        
        labelUpi.style.border = '1px solid var(--border)';
        labelUpi.style.background = 'var(--white)';
        labelUpi.style.color = 'var(--text-gray)';
        
        refContainer.style.display = 'none';
        refInput.removeAttribute('required');
        refInput.value = ''; // Clear value when switching to cash
    }
}
</script>
