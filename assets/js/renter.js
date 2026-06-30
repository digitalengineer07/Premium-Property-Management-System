/**
 * Resident Dashboard functionalities
 */
document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('themeToggle');

    // Sync initial state
    if (localStorage.getItem('theme') !== 'light') {
        document.documentElement.classList.add('dark-theme');
        themeToggle?.classList.replace('bx-moon', 'bx-sun');
    }

    themeToggle?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        if (isDark) themeToggle.classList.replace('bx-moon', 'bx-sun');
        else themeToggle.classList.replace('bx-sun', 'bx-moon');
    });

    // Intro.js Tour logic (only if element exists)
    const startTour = () => {
        if (typeof introJs !== 'undefined' && !localStorage.getItem('renter_tour_seen_v2')) {
            const tour = introJs().setOptions({
                steps: [
                    { title: 'Welcome! 👋', intro: 'Welcome to your Resident Dashboard. Let’s take a quick 1-minute tour.' },
                    { element: '.kpi-grid', intro: 'Keep an eye on your **Total Outstanding** balance here.', position: 'bottom' },
                    { element: '.left-col .panel', intro: 'Track your **Electricity Usage** and print slips here.', position: 'top' },
                    { element: '.header-renter div', intro: 'Support, Profile, and Dark Mode toggles are right here.', position: 'left' }
                ],
                showProgress: false,
                showBullets: true,
                dontShowAgain: true,
                dontShowAgainCookie: 'renter_tour_seen_cookie'
            });

            tour.oncomplete(() => localStorage.setItem('renter_tour_seen_v2', 'true'));
            tour.onexit(() => localStorage.setItem('renter_tour_seen_v2', 'true'));
            tour.start();
        }
    };
    startTour();
});

const paymentModal = document.getElementById('paymentModal');
const scannerModal = document.getElementById('scannerModal');
const dynamicQR = document.getElementById('dynamicQR');
const amountSpan = document.getElementById('paymentAmountDisplay');
const paymentTitle = document.getElementById('paymentTitle');
const hiddenBillType = document.getElementById('hiddenBillType');
const hiddenBillId = document.getElementById('hiddenBillId');
const hiddenAmount = document.getElementById('hiddenAmount');
const paymentTimer = document.getElementById('paymentTimer');
let timerInterval = null;

function openPaymentModal(amount, title = "Rent + Main. + Electricity", type = "total", id = null) {
    if (!amountSpan) return;
    
    // Format amount securely (strip commas, ensure 2 decimal places)
    let numericAmount = parseFloat(amount.toString().replace(/,/g, ''));
    if (isNaN(numericAmount) || numericAmount <= 0) {
        if (typeof showToast === 'function') {
            showToast("No pending dues to pay at this moment!", "success");
        } else {
            alert("No pending dues to pay at this moment!");
        }
        return;
    }
    const formattedAmount = numericAmount.toFixed(2);
    
    amountSpan.textContent = numericAmount.toLocaleString('en-IN');
    paymentTitle.textContent = title;
    hiddenAmount.value = numericAmount;
    hiddenBillType.value = type;
    hiddenBillId.value = id;

    const upiId = "nikhil119124-1@oksbi";
    const name = "Nikhil Kumar";
    
    // Sanitize title for the transaction note (some apps fail on special chars)
    const cleanTitle = title.replace(/[^a-zA-Z0-9 ]/g, "").substring(0, 40);
    // Unique transaction reference for secure tracking
    const trRef = "TXN" + Date.now() + Math.floor(Math.random() * 1000);
    
    const upiUrl = `upi://pay?pa=${upiId}&pn=${encodeURIComponent(name)}&tr=${trRef}&am=${formattedAmount}&cu=INR&tn=${encodeURIComponent(cleanTitle)}`;
    dynamicQR.src = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(upiUrl)}`;
    
    const deepLinkBtn = document.getElementById('upiDeepLinkBtn');
    if (deepLinkBtn) {
        deepLinkBtn.href = upiUrl;
        deepLinkBtn.style.display = 'flex';
    }
    
    paymentModal.style.display = 'flex';
    startTimer(300);
}

function startTimer(duration) {
    if (timerInterval) clearInterval(timerInterval);
    let timer = duration;
    timerInterval = setInterval(function () {
        const min = parseInt(timer / 60, 10);
        const sec = parseInt(timer % 60, 10);
        paymentTimer.textContent = (min < 10 ? "0" + min : min) + ":" + (sec < 10 ? "0" + sec : sec);
        if (--timer < 0) {
            clearInterval(timerInterval);
            paymentTimer.textContent = "EXPIRED";
            paymentTimer.style.color = "#EF4444";
            document.getElementById('paymentNotifyForm').querySelectorAll('input, button').forEach(el => el.disabled = true);
        }
    }, 1000);
}

function closePaymentModal() { paymentModal.style.display = 'none'; if (timerInterval) clearInterval(timerInterval); }
function openScannerModal() { scannerModal.style.display = 'flex'; }
function closeScannerModal() { scannerModal.style.display = 'none'; }

window.addEventListener('click', function (event) {
    if (event.target == paymentModal) closePaymentModal();
    if (event.target == scannerModal) closeScannerModal();
    
    const notifDropdown = document.getElementById('notifDropdown');
    if (notifDropdown && notifDropdown.style.display === 'block') {
        if (!notifDropdown.contains(event.target) && !event.target.closest('.bell-icon')) {
            notifDropdown.style.display = 'none';
        }
    }
});

// Universal Notification Dismissal Logic
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        let date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

function getCookie(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for(let i=0;i < ca.length;i++) {
        let c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function dismissNotification(id, el) {
    let item = el.closest('.notif-item');
    if (item) {
        item.style.height = item.offsetHeight + 'px';
        item.style.transition = 'all 0.3s';
        item.style.transform = 'translateX(-100%)';
        
        setTimeout(() => {
            item.style.height = '0px';
            item.style.padding = '0px';
            item.style.border = 'none';
            setTimeout(() => item.remove(), 300);
        }, 300);
    }
    
    let currentStr = getCookie('dismissed_notifs');
    let currentIds = currentStr ? currentStr.split(',') : [];
    if (!currentIds.includes(id)) {
        currentIds.push(id);
        setCookie('dismissed_notifs', currentIds.join(','), 30);
    }
    
    let badge = document.querySelector('.bell-icon span');
    if (badge) {
        let count = parseInt(badge.innerText) - 1;
        if (count <= 0) {
            badge.remove();
            let container = document.querySelector('#notifDropdown > div:nth-child(2)');
            if (container && document.querySelectorAll('.notif-item').length <= 1) {
                setTimeout(() => {
                    container.innerHTML = `<div style="padding: 30px; text-align: center; color: var(--text-gray);">
                        <i class='bx bx-bell-off' style="font-size: 40px; opacity: 0.5; margin-bottom: 10px;"></i>
                        <p style="margin: 0; font-size: 14px;">You're all caught up!</p>
                    </div>`;
                }, 600);
            }
        } else {
            badge.innerText = count;
        }
    }
    
    let countLabel = document.querySelector('#notifDropdown span[style*="background: rgba(239, 68, 68, 0.1)"]');
    if (countLabel) {
        let count = parseInt(countLabel.innerText) - 1;
        if (count <= 0) countLabel.remove();
        else countLabel.innerText = count + ' New';
    }
    
    if (id.startsWith('ann_')) {
        let remainingAnns = Array.from(document.querySelectorAll('.notif-item')).filter(el => el.getAttribute('data-id') && el.getAttribute('data-id').startsWith('ann_'));
        if (remainingAnns.length <= 1) {
            let redDot = document.getElementById('helpSupportRedDot');
            if (redDot) redDot.style.display = 'none';
        }
    }
}
