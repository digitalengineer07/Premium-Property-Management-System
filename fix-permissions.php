<?php
// fix-permissions.php
// A simple script to automatically fix the 403 Forbidden permissions on Hostinger

$directories = [
    'uploads/meter_readings',
    'uploads/bills',
    'uploads/profiles',
    'uploads/aadhaar',
    'uploads/agreements',
    'uploads/payments'
];

$fixedCount = 0;
$log = [];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        // Change folder permission to 0755
        @chmod($dir, 0755);
        $log[] = "Set 755 permission for directory: $dir";
        
        $files = new DirectoryIterator($dir);
        foreach ($files as $file) {
            if (!$file->isDot() && $file->isFile()) {
                // Check if file is an image/pdf
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'gif'])) {
                    $path = $file->getPathname();
                    // Change file permission to 0644
                    if (@chmod($path, 0644)) {
                        $fixedCount++;
                    }
                }
            }
        }
    }
}

echo "<h3>Hostinger Permission Fixer</h3>";
echo "<b>Total files patched: </b> $fixedCount files<br><br>";
foreach ($log as $l) {
    echo $l . "<br>";
}
echo "<br><b>All old images should now be readable! You can safely delete this file (fix-permissions.php).</b>";
?>
