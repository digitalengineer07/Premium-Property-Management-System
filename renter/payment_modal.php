      <div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
          <style>
              #paymentModalPanel::-webkit-scrollbar { display: none; }
              #paymentModalPanel { -ms-overflow-style: none; scrollbar-width: none; }
              
              .pm-layout { display: flex; flex-direction: column; gap: 0; }
              .pm-qr-section { background: #F8F9FA; padding: 16px; border-radius: 20px; margin-bottom: 16px; border: 1px solid rgba(0,0,0,0.03); }
              .pm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
              
              @media (min-width: 768px) {
                  #paymentModalPanel { max-width: 700px !important; padding: 24px 32px !important; }
                  .pm-layout { flex-direction: row; gap: 32px; text-align: left; align-items: flex-start; }
                  .pm-col-left, .pm-col-right { flex: 1; min-width: 0; }
                  .pm-col-left { display: flex; flex-direction: column; justify-content: flex-start; }
                  .pm-qr-section { margin-bottom: 0; display: flex; flex-direction: column; justify-content: flex-start; }
                  #paymentDetails { text-align: left; align-items: flex-start; }
                  #pmAmountContainer { justify-content: flex-start !important; }
                  .pm-timer-container { justify-content: flex-start !important; }
                  .pm-timer-text { text-align: left !important; }
              }
              @media (max-width: 767px) {
                  .pm-col-left { order: 2; }
                  .pm-col-right { order: 1; }
              }
          </style>
          <div id="paymentModalPanel" class="animate-up" style="max-width: 420px; width: 100%; background: white; text-align: center; padding: 24px; max-height: 90vh; overflow-y: auto; border-radius: 24px; box-shadow: 0 24px 60px rgba(0,0,0,0.1);">
              <div class="pm-header">
                  <h2 style="font-size: 26px; font-weight: 900; background: linear-gradient(135deg, var(--primary-purple), #FF4B6B); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; letter-spacing: -0.5px;">Make Payment</h2>
                  <div onclick="closePaymentModal()" style="width: 36px; height: 36px; border-radius: 50%; background: #F8F9FA; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;">
                      <i class='bx bx-x' style="font-size: 22px; color: var(--text-gray);"></i>
                  </div>
              </div>
              
              <div class="pm-layout">
                  <div class="pm-col-left">
                      <div id="paymentDetails" style="margin-bottom: 20px;">
                          <div id="paymentTitle" style="font-weight: 700; font-size: 15px; margin-bottom: 8px; color: var(--text-dark);">Total Outstanding Balance</div>
                          <div id="pmAmountContainer" style="font-size: 40px; font-weight: 800; color: var(--primary-purple); letter-spacing: -1px; display: flex; align-items: center; justify-content: center; gap: 4px;">&#8377;<span id="paymentAmountDisplay">0</span></div>
                      </div>

                      <div style="background: rgba(98, 75, 255, 0.04); padding: 12px 10px; border-radius: 12px; border: 1px dashed rgba(98, 75, 255, 0.2); margin-bottom: 20px;">
                          <p class="pm-timer-container" style="font-size: 13px; color: var(--primary-purple); font-weight: 800; text-transform: uppercase; margin: 0 0 6px 0; display: flex; align-items: center; justify-content: center; gap: 6px;">
                              <i class='bx bx-timer' style="font-size: 18px;"></i> Session Expires in <span id="paymentTimer" style="background: var(--primary-purple); color: white; padding: 3px 8px; border-radius: 6px;">05:00</span>
                          </p>
                          <p class="pm-timer-text" style="font-size: 11px; color: var(--text-gray); margin: 0; text-align: center; white-space: nowrap; letter-spacing: -0.2px;">Transfer within this time to ensure amount accuracy.</p>
                      </div>

                      <form method="POST" id="paymentNotifyForm" style="text-align: left;">
                          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                          <input type="hidden" name="bill_type" id="hiddenBillType">
                          <input type="hidden" name="bill_id" id="hiddenBillId">
                          <input type="hidden" name="amount" id="hiddenAmount">
                          
                          <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Enter Transaction ID / UTR</label>
                          <input type="text" name="transaction_id" placeholder="Enter 12-digit UTR No." required style="width: 100%; padding: 14px 16px; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 20px; background: #F8F9FA; color: var(--text-dark); outline: none; font-size: 15px;">
                          
                          <button type="submit" id="submitPaymentBtn" name="submit_payment_notif" class="btn-primary" style="width: 100%; justify-content: center; padding: 16px; font-size: 15px; border-radius: 12px; box-shadow: 0 6px 16px rgba(98, 75, 255, 0.25);">
                              <i class='bx bx-check-shield' style="font-size: 18px;"></i> Confirm Payment
                          </button>
                      </form>
                      
                      <script>
                      document.getElementById('paymentNotifyForm').addEventListener('submit', function(e) {
                          let btn = document.getElementById('submitPaymentBtn');
                          if (btn.disabled) {
                              e.preventDefault();
                              return;
                          }
                          setTimeout(() => {
                              btn.disabled = true;
                              btn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Submitting...";
                          }, 10);
                      });
                      </script>
                  </div>
                  
                  <div class="pm-col-right">
                      <div class="pm-qr-section">
                          <div style="background: white; padding: 10px; border-radius: 16px; display: inline-block; margin-bottom: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); align-self: center;">
                              <img id="dynamicQR" src="" alt="UPI QR Code" style="width: 140px; height: 140px; display: block; border-radius: 8px;">
                          </div>
                          <p id="pmQrText1" style="font-size: 12px; color: var(--text-gray); font-weight: 600; margin: 0 0 4px 0;">Scan with any UPI App</p>
                          <div id="pmQrText2" style="font-size: 14px; font-weight: 800; color: var(--text-dark); margin-bottom: 16px;">nikhil119124-1@oksbi</div>
                          
                          <a id="upiDeepLinkBtn" href="#" style="display: none; background: #10B981; color: white; border: none; font-size: 13px; font-weight: 700; padding: 10px; justify-content: center; width: 100%; border-radius: 10px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); text-decoration: none; align-items: center; gap: 8px; margin-bottom: 16px;">
                              <i class='bx bx-mobile-alt' style="font-size: 16px;"></i> Pay on phone
                          </a>
                          
                          <div style="border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 12px;">
                              <p style="font-size: 10px; color: var(--text-gray); margin: 0 0 8px 0;">Having issues? Use the permanent scanner:</p>
                              <button type="button" onclick="showOwnerScannerInline()" style="background: white; border: 1px solid var(--border); color: var(--text-dark); border-radius: 10px; width: 100%; justify-content: center; font-size: 11px; padding: 8px; display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 600; transition: 0.2s;">
                                  <i class='bx bx-qr-scan'></i> Show Owner's Scanner
                              </button>
                          </div>
                          <script>
                          function showOwnerScannerInline() {
                              let qr = document.getElementById('dynamicQR');
                              qr.src = '../assets/img/gpay-qr.jpg';
                              qr.style.width = '100%';
                              qr.style.height = 'auto';
                              qr.style.maxWidth = '250px';
                              
                              let container = qr.parentElement;
                              container.style.padding = '0';
                              container.style.overflow = 'hidden';
                              container.style.borderRadius = '16px';
                              
                              let deepLink = document.getElementById('upiDeepLinkBtn');
                              if (deepLink) deepLink.style.display = 'none';
                              
                              document.getElementById('pmQrText1').style.display = 'none';
                              document.getElementById('pmQrText2').style.display = 'none';
                          }
                          </script>
                      </div>
                  </div>
              </div>
          </div>
      </div>
