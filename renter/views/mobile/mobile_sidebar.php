<?php
// Unified Mobile Sidebar Drawer
// This file is included at the bottom of mobile views to provide the overlay and Javascript 
// that slides in the original desktop sidebar (`.sidebar`) when the hamburger menu is clicked.
?>

<!-- Overlay for mobile sidebar -->
<div id="unified-mobile-overlay" class="mobile-sidebar-overlay" onclick="closeMobileSidebar(event)"></div>

<script>
// Open the original desktop sidebar as a mobile drawer
function openMobileSidebar(e) {
    if(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const msb = document.querySelector('.sidebar');
    const overlay = document.getElementById('unified-mobile-overlay');
    if(msb && overlay) {
        msb.classList.add('mobile-drawer-open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
}

function closeMobileSidebar(e) {
    if(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const msb = document.querySelector('.sidebar');
    const overlay = document.getElementById('unified-mobile-overlay');
    if(msb && overlay) {
        msb.classList.remove('mobile-drawer-open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }
}
</script>
