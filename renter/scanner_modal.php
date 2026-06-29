<div id="scannerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 10000; align-items: center; justify-content: center; padding: 16px;">
    <div class="panel animate-up" style="max-width: 440px; width: 100%; background: white; text-align: center; padding: 32px 24px 24px 24px; border-radius: 24px; box-shadow: 0 24px 60px rgba(0,0,0,0.1);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h2 style="font-size: 22px; font-weight: 800; color: #1e293b; margin: 0; letter-spacing: -0.5px;">Owner's Scanner</h2>
            <i class='bx bx-x' onclick="closeScannerModal()" style="font-size: 26px; cursor: pointer; color: #64748b; padding: 4px; border-radius: 50%; transition: background 0.2s;"></i>
        </div>

        <div style="background: #f1f5f9; padding: 32px 20px; border-radius: 20px; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 24px;">
                <img src="../assets/img/nikhil.png" alt="Profile" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                <span style="font-size: 19px; color: #334155; font-weight: 500; font-family: sans-serif;">&Ntilde;&iacute;kh&igrave;l K&ucirc;m&atilde;r</span>
            </div>
            
            <div style="background: white; padding: 16px; border-radius: 20px; display: inline-block; margin-bottom: 24px; box-shadow: 0 8px 30px rgba(0,0,0,0.06);">
                <img src="../assets/img/gpay-qr.jpg" alt="Owner Scanner" style="width: 220px; height: 220px; display: block; border-radius: 12px; object-fit: contain;">
            </div>
            
            <p style="font-size: 15px; color: #475569; font-weight: 500; margin: 0 0 24px 0;">UPI ID: nikhil119124-1@oksbi</p>
            <p style="font-size: 15px; color: #64748b; margin: 0; font-weight: 400;">Scan to pay with any UPI app</p>
        </div>

        <p style="font-size: 13px; color: #64748b; margin: 0; font-weight: 500;">Fixed GPay scanner for manual amount entry.</p>
    </div>
</div>

<style>
    .bx-x:hover {
        background: #f1f5f9;
        color: #0f172a !important;
    }
</style>
