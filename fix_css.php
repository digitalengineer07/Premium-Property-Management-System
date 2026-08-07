<?php
$file = 'assets/css/admin-design-system.css';
$css = "\n\n/* Dark mode fix for Edit Profile Modal Inputs */\n.dark-theme #editProfileModal input, .dark-theme #editProfileModal select {\n    background: var(--bg-main) !important;\n    border-color: var(--border) !important;\n    color: var(--text-dark) !important;\n}\n.dark-theme #editProfileModal input:focus, .dark-theme #editProfileModal select:focus {\n    border-color: var(--primary-purple) !important;\n}\n";
file_put_contents($file, $css, FILE_APPEND);
echo "Appended CSS successfully.";
?>
