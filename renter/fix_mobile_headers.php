<?php
$dir = __DIR__ . '/views/mobile';
$files = scandir($dir);

$oldStr = '<a href="profile.php" class="header-profile-btn" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.2); display: block; text-decoration: none;">';
$newStr = '<a href="#" class="header-profile-btn" onclick="document.getElementById(\'profilePicInputMobile\').click(); return false;" style="width: 38px; height: 38px; border-radius: 50%; overflow: hidden; border: 2px solid rgba(255,255,255,0.2); display: block; text-decoration: none;">';

foreach ($files as $f) {
    if (str_ends_with($f, '_mobile.php')) {
        if ($f === 'profile_mobile.php') continue; // Already has it
        
        $path = $dir . '/' . $f;
        $content = file_get_contents($path);
        
        if (strpos($content, $oldStr) !== false) {
            $content = str_replace($oldStr, $newStr, $content);
            file_put_contents($path, $content);
            echo "Updated $f\n";
        } else {
            echo "String not found in $f\n";
        }
    }
}
?>
