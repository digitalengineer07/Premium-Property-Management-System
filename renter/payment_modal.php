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
                  .pm-col-left { order: 2; width: 100%; }
                  .pm-col-right { order: 1; width: 100%; }
                  #paymentModalPanel { 
                      padding: 16px 16px 20px 16px !important; 
                      width: 95% !important; 
                      max-width: 400px !important; 
                      max-height: 92vh !important; 
                      border-radius: 24px !important; 
                      display: flex;
                      flex-direction: column;
                  }
                  .pm-layout { flex: 1; padding-right: 2px; }
                  
                  .pm-header { margin-bottom: 12px !important; flex-shrink: 0; }
                  .pm-header h2 { font-size: 20px !important; }
                  
                  /* Premium Horizontal QR Layout */
                  .pm-qr-section { 
                      display: grid;
                      grid-template-columns: 140px 1fr;
                      gap: 8px 12px;
                      padding: 12px !important; 
                      margin-bottom: 12px !important; 
                      border-radius: 16px !important; 
                      text-align: left;
                      align-items: center;
                  }
                  .pm-qr-section > div:first-child {
                      grid-column: 1;
                      grid-row: 1 / 5;
                      margin-bottom: 0 !important;
                      padding: 10px !important;
                      border-radius: 14px !important;
                      box-shadow: 0 4px 16px rgba(0,0,0,0.06) !important;
                      align-self: center;
                  }
                  .pm-qr-section img { max-width: 100% !important; height: auto !important; }
                  
                  #pmQrText1 { grid-column: 2; grid-row: 1; margin: 0 !important; font-size: 11px !important; align-self: end; }
                  #pmQrText2 { grid-column: 2; grid-row: 2; margin: 0 !important; font-size: 13px !important; }
                  #upiDeepLinkBtn { grid-column: 2; grid-row: 3; margin: 0 !important; padding: 8px 12px !important; font-size: 12px !important; border-radius: 8px !important; width: fit-content !important; align-self: start; }
                  
                  .pm-qr-section > div:nth-of-type(2) {
                      grid-column: 2;
                      grid-row: 4;
                      margin-top: 4px;
                      padding-top: 10px !important;
                      border-top: 1px dashed rgba(0,0,0,0.1) !important;
                      display: flex;
                      flex-direction: column;
                      gap: 6px;
                      align-items: flex-start;
                  }
                  .pm-qr-section > div:nth-of-type(2) p { 
                      display: block !important; 
                      font-size: 10px !important; 
                      line-height: 1.2 !important; 
                      margin: 0 !important; 
                  }
                  .pm-qr-section button { padding: 6px 12px !important; font-size: 11px !important; width: fit-content !important; border-radius: 8px !important; }
                  
                  /* Amounts & Details */
                  #pmAmountContainer { font-size: 32px !important; margin-bottom: 4px !important; }
                  #paymentDetails { margin-bottom: 12px !important; text-align: center; }
                  #paymentTitle { font-size: 12px !important; margin-bottom: 4px !important; }
                  
                  .pm-col-left > div:nth-child(2) { padding: 10px !important; margin-bottom: 16px !important; border-radius: 10px !important; }
                  .pm-timer-container { font-size: 12px !important; margin-bottom: 4px !important; }
                  .pm-timer-container i { font-size: 16px !important; }
                  .pm-timer-text { font-size: 10px !important; }
                  
                  #paymentNotifyForm label { font-size: 12px !important; margin-bottom: 6px !important; }
                  #paymentNotifyForm input { padding: 12px 16px !important; margin-bottom: 12px !important; border-radius: 12px !important; font-size: 14px !important; }
                  #submitPaymentBtn { padding: 14px !important; font-size: 15px !important; border-radius: 12px !important; }
              }
          
              /* Dark Mode Overrides for Payment Modal */
              .dark-theme #paymentModalPanel { background: var(--white, #111827) !important; color: var(--text-dark, #F8FAFC) !important; border: 1px solid var(--border, #1E293B) !important; }
              .dark-theme .pm-qr-section { background: rgba(255, 255, 255, 0.03) !important; border-color: var(--border, #1E293B) !important; }
              .dark-theme .pm-header div { background: rgba(255, 255, 255, 0.05) !important; }
              .dark-theme #paymentNotifyForm input { background: var(--bg-main, #0B0F19) !important; color: var(--text-dark, #F8FAFC) !important; border-color: var(--border, #1E293B) !important; }
              .dark-theme .pm-qr-section button { background: rgba(255, 255, 255, 0.05) !important; color: var(--text-dark, #F8FAFC) !important; border-color: var(--border, #1E293B) !important; }

          
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
          <div id="paymentModalPanel" class="animate-up" style="max-width: 420px; width: 100%; background: white; text-align: center; padding: 24px; max-height: 90vh; border-radius: 24px; box-shadow: 0 24px 60px rgba(0,0,0,0.1);">
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
                          <div id="pmAmountContainer" style="font-size: 40px; font-weight: 800; color: var(--primary-purple); letter-spacing: -1px; display: flex; align-items: center; justify-content: center; gap: 2px;">&#8377;<span id="paymentAmountDisplay">0</span></div>
                      </div>

                      <div style="background: rgba(98, 75, 255, 0.04); padding: 12px 10px; border-radius: 12px; border: 1px dashed rgba(98, 75, 255, 0.2); margin-bottom: 20px;">
                          <p class="pm-timer-container" style="font-size: 13px; color: var(--primary-purple); font-weight: 800; text-transform: uppercase; margin: 0 0 6px 0; display: flex; align-items: center; justify-content: center; gap: 6px;">
                              <i class='bx bx-timer' style="font-size: 18px;"></i> Session Expires in <span id="paymentTimer" style="background: var(--primary-purple); color: white; padding: 3px 8px; border-radius: 6px;">05:00</span>
                          </p>
                          <p class="pm-timer-text" style="font-size: 11px; color: var(--text-gray); margin: 0; text-align: center; white-space: nowrap; letter-spacing: -0.2px;">Transfer within this time to ensure amount accuracy.</p>
                      </div>

                      <form method="POST" action="process_payment.php" id="paymentNotifyForm" style="text-align: left;">
                          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                          <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                          <input type="hidden" name="bill_type" id="hiddenBillType">
                          <input type="hidden" name="bill_id" id="hiddenBillId">
                          <input type="hidden" name="amount" id="hiddenAmount">
                          <input type="hidden" name="bill_month" id="hiddenMonth">
                          
                          <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Payment Mode</label>
                          <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                              <label style="flex: 1; border: 1px solid var(--primary-purple); background: rgba(98, 75, 255, 0.05); color: var(--primary-purple); padding: 12px; border-radius: 12px; text-align: center; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 2px; font-weight: 600; font-size: 13px;" class="pm-mode-label" id="label-upi">
                                  <input type="radio" name="payment_method" value="UPI" checked onchange="toggleRefField(true)" style="display: none;">
                                  <i class='bx bx-mobile'></i> UPI
                              </label>
                              <label style="flex: 1; border: 1px solid var(--border); padding: 12px; border-radius: 12px; text-align: center; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 2px; font-weight: 600; font-size: 13px;" class="pm-mode-label" id="label-cash">
                                  <input type="radio" name="payment_method" value="Cash" onchange="toggleRefField(false)" style="display: none;">
                                  <i class='bx bx-money'></i> Cash
                              </label>
                          </div>

                          <div id="refNoContainer">
                              <label style="font-size: 13px; font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 8px;">Enter Transaction ID / UTR</label>
                              <input type="text" name="transaction_id" id="transaction_id_input" placeholder="Enter 12-digit UTR No." required pattern="\d{12}" minlength="12" maxlength="12" title="Please enter exactly 12 digits for the UTR." oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,12)" style="width: 100%; padding: 14px 16px; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 20px; background: #F8F9FA; color: var(--text-dark); outline: none; font-size: 15px;">
                          </div>
                          
                          <button type="submit" id="submitPaymentBtn" name="submit_payment_notif" class="btn-primary" style="width: 100%; justify-content: center; padding: 16px; font-size: 15px; border-radius: 12px; box-shadow: 0 6px 16px rgba(98, 75, 255, 0.25);">
                              <i class='bx bx-check-shield' style="font-size: 18px;"></i> Confirm Payment
                          </button>
                      </form>
                      
                      <script>
                      function toggleRefField(isUpi) {
                          const refContainer = document.getElementById('refNoContainer');
                          const input = document.getElementById('transaction_id_input');
                          const labelUpi = document.getElementById('label-upi');
                          const labelCash = document.getElementById('label-cash');
                          
                          if (isUpi) {
                              refContainer.style.display = 'block';
                              input.required = true;
                              labelUpi.style.borderColor = 'var(--primary-purple)';
                              labelUpi.style.backgroundColor = 'rgba(98, 75, 255, 0.05)';
                              labelUpi.style.color = 'var(--primary-purple)';
                              labelCash.style.borderColor = 'var(--border)';
                              labelCash.style.backgroundColor = 'transparent';
                              labelCash.style.color = 'var(--text-dark)';
                          } else {
                              refContainer.style.display = 'none';
                              input.required = false;
                              input.value = '';
                              labelCash.style.borderColor = '#10B981';
                              labelCash.style.backgroundColor = 'rgba(16, 185, 129, 0.05)';
                              labelCash.style.color = '#10B981';
                              labelUpi.style.borderColor = 'var(--border)';
                              labelUpi.style.backgroundColor = 'transparent';
                              labelUpi.style.color = 'var(--text-dark)';
                          }
                      }
                      document.getElementById('paymentNotifyForm').addEventListener('submit', function(e) {
                          // Level 3 Security: Strict Rate Limiting
                          const rateLimitKey = 'lastPaymentSubmitTime';
                          const lastSubmit = localStorage.getItem(rateLimitKey);
                          const cooldownMs = 3 * 60 * 1000; // 3 minutes
                          
                          if (lastSubmit && (Date.now() - parseInt(lastSubmit)) < cooldownMs) {
                              e.preventDefault();
                              const remainingSecs = Math.ceil((cooldownMs - (Date.now() - parseInt(lastSubmit))) / 1000);
                              const remainingMins = Math.ceil(remainingSecs / 60);
                              
                              if (typeof showToast === 'function') {
                                  showToast(`Security Alert: Please wait ${remainingMins} minute(s) before submitting another payment.`, "error");
                              } else {
                                  alert(`Security Alert: Please wait ${remainingMins} minute(s) before submitting another payment.`);
                              }
                              return;
                          }
                          
                          // Register submission time for rate limiting
                          localStorage.setItem(rateLimitKey, Date.now());
                          
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
                          <div id="pmQrText2" style="font-size: 13px; font-weight: 800; color: var(--text-dark); margin-bottom: 16px;">nikhil119124-1@oksbi</div>
                          
                          <a id="upiDeepLinkBtn" href="#" style="display: none; background: #10B981; color: white; border: none; font-size: 13px; font-weight: 700; padding: 10px; justify-content: center; width: 100%; border-radius: 10px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); text-decoration: none; align-items: center; gap: 2px; margin-bottom: 16px;">
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
      <script>
      let paymentTimerInterval;
      function openPaymentModal(amount, title, type, id = 0, month = '') {
          if (amount <= 0) {
              if (typeof showToast === 'function') {
                  showToast("No outstanding balance to pay for this item.", "info");
              } else {
                  alert("No outstanding balance to pay for this item.");
              }
              return;
          }
          
          document.getElementById('paymentAmountDisplay').innerText = Math.round(amount).toString();
          document.getElementById('paymentTitle').innerText = title;
          document.getElementById('hiddenBillType').value = type;
          document.getElementById('hiddenBillId').value = id;
          document.getElementById('hiddenAmount').value = amount;
          if (month && month !== 'undefined' && month !== '') {
              document.getElementById('hiddenMonth').value = month;
          } else {
              document.getElementById('hiddenMonth').value = '';
          }
          
          const upiId = "nikhil119124-1@oksbi";
          const payeeName = "Premium Property Management";
          const upiUrl = `upi://pay?pa=${upiId}&pn=${encodeURIComponent(payeeName)}&am=${amount}&cu=INR&tn=${encodeURIComponent(title)}`;
          
          const qrImage = document.getElementById('dynamicQR');
          if (qrImage) {
              qrImage.src = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" + encodeURIComponent(upiUrl);
          }
          
          const deepLinkBtn = document.getElementById('upiDeepLinkBtn');
          if (deepLinkBtn) {
              deepLinkBtn.href = upiUrl;
              if (window.innerWidth <= 767) {
                  deepLinkBtn.style.display = 'flex';
              }
          }
          
          startPaymentTimer();
          document.getElementById('paymentModal').style.display = 'flex';
      }

      function closePaymentModal() {
          document.getElementById('paymentModal').style.display = 'none';
          if (paymentTimerInterval) clearInterval(paymentTimerInterval);
      }

      function startPaymentTimer() {
          if (paymentTimerInterval) clearInterval(paymentTimerInterval);
          let timeLeft = 300;
          const timerDisplay = document.getElementById('paymentTimer');
          
          paymentTimerInterval = setInterval(() => {
              timeLeft--;
              let m = Math.floor(timeLeft / 60);
              let s = Math.floor(timeLeft % 60);
              if (timerDisplay) timerDisplay.innerText = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
              
              if (timeLeft <= 0) {
                  clearInterval(paymentTimerInterval);
                  closePaymentModal();
                  if (typeof showToast === 'function') {
                      showToast("Payment session expired.", "warning");
                  } else {
                      alert("Payment session expired. Please try again.");
                  }
              }
          }, 1000);
      }
      </script>
