/**
 * Resident Dashboard functionalities
 */
window.toggleTheme = function(e) {
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
    const isDark = !document.documentElement.classList.contains('dark-theme');
    document.documentElement.classList.toggle('dark-theme', isDark);
    if (document.body) document.body.classList.toggle('dark-theme', isDark);
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    document.querySelectorAll('.bx-moon, .bx-sun').forEach(icon => {
        if (icon.closest('#themeToggle') || icon.closest('.icon-btn') || icon.id === 'themeToggle') {
            icon.className = isDark ? 'bx bx-sun' : 'bx bx-moon';
        }
    });
};

document.addEventListener('DOMContentLoaded', function () {
    const isDark = localStorage.getItem('theme') === 'dark';
    document.documentElement.classList.toggle('dark-theme', isDark);
    if (document.body) document.body.classList.toggle('dark-theme', isDark);
    document.querySelectorAll('.bx-moon, .bx-sun').forEach(icon => {
        if (icon.closest('#themeToggle') || icon.closest('.icon-btn') || icon.id === 'themeToggle') {
            icon.className = isDark ? 'bx bx-sun' : 'bx bx-moon';
        }
    });

    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle && !themeToggle.getAttribute('onclick')) {
        themeToggle.addEventListener('click', window.toggleTheme);
    }

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
// Toast Notification System
window.showToast = function(message, type = 'error') {
    const toast = document.createElement('div');
    toast.style.position = 'fixed';
    toast.style.bottom = '30px'; // Move to bottom for better UX on mobile
    toast.style.left = '50%';
    toast.style.transform = 'translateX(-50%) translateY(20px)';
    toast.style.backgroundColor = type === 'error' ? '#EF4444' : '#10B981';
    toast.style.color = '#fff';
    toast.style.padding = '12px 16px';
    toast.style.borderRadius = '12px'; // Square-ish rounded corners look better for wrapped text
    toast.style.boxShadow = '0 8px 24px rgba(0,0,0,0.15)';
    toast.style.zIndex = '999999';
    toast.style.fontSize = '13px';
    toast.style.fontWeight = '500'; // Less bold
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s ease, transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
    toast.style.pointerEvents = 'none';
    toast.style.textAlign = 'left'; // Align text left when wrapping
    toast.style.width = 'max-content';
    toast.style.maxWidth = '90vw';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.gap = '10px';
    
    const iconClass = type === 'error' ? 'bx-error-circle' : 'bx-check-circle';
    toast.innerHTML = `<i class='bx ${iconClass}' style='font-size: 20px; flex-shrink: 0;'></i> <span>${message}</span>`;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';
    }, 10);
    
    // Animate out
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
};

const paymentTitle = document.getElementById('paymentTitle');
const hiddenBillType = document.getElementById('hiddenBillType');
const hiddenBillId = document.getElementById('hiddenBillId');
const hiddenMonth = document.getElementById('hiddenMonth');
const hiddenAmount = document.getElementById('hiddenAmount');
const paymentTimer = document.getElementById('paymentTimer');
let timerInterval = null;

function openPaymentModal(amount, title = "Rent + Main.", type = "total", id = null, month = '') {
    // Level 3 Security: Rate Limiting to prevent modal opening spam
    const rateLimitKey = 'lastModalOpenTime';
    const lastOpen = localStorage.getItem(rateLimitKey);
    const cooldownMs = 2 * 60 * 1000; // 2 minutes cooldown
    
    if (lastOpen && (Date.now() - parseInt(lastOpen)) < cooldownMs) {
        const remainingSecs = Math.ceil((cooldownMs - (Date.now() - parseInt(lastOpen))) / 1000);
        const remainingMins = Math.ceil(remainingSecs / 60);
        
        if (typeof showToast === 'function') {
            showToast(`Security Limit: Please wait ${remainingMins} min(s) to open scanner.`, "error");
        } else {
            alert(`Security Limit: Please wait ${remainingMins} min(s) to open scanner.`);
        }
        return;
    }
    
    // Set timestamp for next modal open rate limit check
    localStorage.setItem(rateLimitKey, Date.now());

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
    if (hiddenMonth) hiddenMonth.value = month;

    const upiId = "nikhil119124-1@oksbi";
    const name = "Nikhil Kumar";
    
    // Sanitize title for the transaction note (some apps fail on special chars)
    const cleanTitle = title.replace(/[^a-zA-Z0-9 ]/g, "").substring(0, 40);
    // Unique transaction reference for secure tracking
    const trRef = "TXN" + Date.now() + Math.floor(Math.random() * 1000);
    
    // Updated robust deep link format with merchant code and intent mode
    const upiUrl = `upi://pay?pa=${upiId}&pn=${encodeURIComponent(name)}&tr=${trRef}&mc=0000&mode=02&am=${formattedAmount}&cu=INR&tn=${encodeURIComponent(cleanTitle)}`;
    dynamicQR.src = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(upiUrl)}`;
    
    const deepLinkBtn = document.getElementById('upiDeepLinkBtn');
    if (deepLinkBtn) {
        deepLinkBtn.href = upiUrl;
        deepLinkBtn.target = "_top"; // Ensure it breaks out of any constrained context
    }
    
    paymentModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
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

function closePaymentModal() { paymentModal.style.display = 'none'; document.body.style.overflow = ''; if (timerInterval) clearInterval(timerInterval); }
function openScannerModal() { scannerModal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
function closeScannerModal() { scannerModal.style.display = 'none'; document.body.style.overflow = ''; }

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


window.openMobileSidebar = function(e) {
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;
    
    let overlay = document.getElementById('mobileSidebarOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'mobileSidebarOverlay';
        overlay.className = 'mobile-sidebar-overlay';
        overlay.addEventListener('click', window.closeMobileSidebar);
        document.body.appendChild(overlay);
    }
    
    overlay.style.display = 'block';
    setTimeout(() => { overlay.style.opacity = '1'; }, 10);
    
    sidebar.classList.add('mobile-drawer-open');
    
    // Hide mobile bottom nav to prevent overlap
    document.querySelectorAll('.mobile-bottom-nav').forEach(nav => {
        nav.dataset.originalDisplay = nav.style.getPropertyValue('display');
        nav.dataset.originalDisplayPriority = nav.style.getPropertyPriority('display');
        nav.style.setProperty('display', 'none', 'important');
    });
    
    // Auto-close when any nav link is clicked inside the drawer
    sidebar.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 768) window.closeMobileSidebar();
        });
    });
};

window.closeMobileSidebar = function(e) {
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) sidebar.classList.remove('mobile-drawer-open');
    const overlay = document.getElementById('mobileSidebarOverlay');
    if (overlay) {
        overlay.style.opacity = '0';
        setTimeout(() => { overlay.style.display = 'none'; }, 300);
    }
    
    // Restore mobile bottom nav visibility
    document.querySelectorAll('.mobile-bottom-nav').forEach(nav => {
        if (nav.dataset.originalDisplayPriority) {
            nav.style.setProperty('display', nav.dataset.originalDisplay || '', nav.dataset.originalDisplayPriority);
        } else {
            nav.style.setProperty('display', nav.dataset.originalDisplay || '');
        }
    });
};
