const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({ headless: true });
    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 800 });
    await page.goto('file:///c:/xampp/htdocs/renter-system/renter/profile_output.html', { waitUntil: 'networkidle0' });
    await page.screenshot({ path: 'profile_desktop_screenshot.png' });
    
    // Check elements
    const checkVisibility = await page.evaluate(() => {
        const desktopWrapper = document.querySelector('.desktop-view-wrapper');
        const mainContent = document.querySelector('.main-content');
        
        let res = "";
        if (desktopWrapper) {
            const rect = desktopWrapper.getBoundingClientRect();
            const style = window.getComputedStyle(desktopWrapper);
            res += "Desktop Wrapper: " + style.display + " " + rect.width + "x" + rect.height + " @ " + rect.left + "," + rect.top + "\n";
            res += "Desktop innerHTML length: " + desktopWrapper.innerHTML.length + "\n";
        } else {
            res += "Desktop Wrapper NOT FOUND\n";
        }
        
        if (mainContent) {
            const rect = mainContent.getBoundingClientRect();
            const style = window.getComputedStyle(mainContent);
            res += "Main Content: " + style.display + " " + rect.width + "x" + rect.height + " @ " + rect.left + "," + rect.top + "\n";
        } else {
            res += "Main Content NOT FOUND\n";
        }
        
        return res;
    });
    
    console.log(checkVisibility);
    await browser.close();
})();
