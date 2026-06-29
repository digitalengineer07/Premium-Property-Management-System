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

function openPaymentModal(amount, title = "Total Outstanding Balance", type = "total", id = null) {
    if (!amountSpan) return;
    
    // Format amount securely (strip commas, ensure 2 decimal places)
    let numericAmount = parseFloat(amount.toString().replace(/,/g, ''));
    if (isNaN(numericAmount)) numericAmount = 0;
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

window.onclick = function (event) {
    if (event.target == paymentModal) closePaymentModal();
    if (event.target == scannerModal) closeScannerModal();
}
