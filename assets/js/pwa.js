let deferredPrompt;

// Create the floating install button
const installBtn = document.createElement('button');
installBtn.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: -2px;">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
        <polyline points="7 10 12 15 17 10"></polyline>
        <line x1="12" y1="15" x2="12" y2="3"></line>
    </svg>
    Install App
`;
installBtn.style.cssText = `
    
    display: none;
    position: fixed;
    bottom: ${window.innerWidth <= 768 ? '88px' : '20px'};
    right: 20px;

    z-index: 9999;
    background: #624BFF;
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(98, 75, 255, 0.4);
    cursor: pointer;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
`;
// Add hover effect
installBtn.onmouseover = () => { installBtn.style.transform = 'translateY(-2px)'; installBtn.style.boxShadow = '0 6px 20px rgba(98, 75, 255, 0.6)'; };
installBtn.onmouseout = () => { installBtn.style.transform = 'translateY(0)'; installBtn.style.boxShadow = '0 4px 15px rgba(98, 75, 255, 0.4)'; };

document.body.appendChild(installBtn);

window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    // Stash the event so it can be triggered later.
    deferredPrompt = e;
    // Update UI notify the user they can install the PWA
    installBtn.style.display = 'flex';
});

installBtn.addEventListener('click', async () => {
    // Hide the app provided install promotion
    installBtn.style.display = 'none';
    // Show the install prompt
    if (deferredPrompt) {
        deferredPrompt.prompt();
        // Wait for the user to respond to the prompt
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`User response to the install prompt: ${outcome}`);
        // We've used the prompt, and can't use it again, throw it away
        deferredPrompt = null;
    }
});

window.addEventListener('appinstalled', () => {
    // Hide the app-provided install promotion
    installBtn.style.display = 'none';
    // Clear the deferredPrompt so it can be garbage collected
    deferredPrompt = null;
    console.log('PWA was installed');
});


window.addEventListener('resize', () => {
    installBtn.style.bottom = window.innerWidth <= 768 ? '88px' : '20px';
});
