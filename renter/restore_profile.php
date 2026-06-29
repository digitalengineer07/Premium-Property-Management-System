<?php
$c = file_get_contents("profile.php");
$pos = strpos($c, "\$user = mysqli_fetch_assoc(\$res);\nmysqli_stmt_close(\$stmt);");
if ($pos !== false) {
    $pos_end = strpos($c, "(function() {", $pos);
    if ($pos_end !== false) {
        $replacement = "\$user = mysqli_fetch_assoc(\$res);
mysqli_stmt_close(\$stmt);

/* Fetch electricity records for this user, recent first */
\$elec_q = mysqli_prepare(\$conn, \"SELECT * FROM electricity WHERE user_id = ? ORDER BY id DESC\");
mysqli_stmt_bind_param(\$elec_q, \"i\", \$user_id);
mysqli_stmt_execute(\$elec_q);
\$elec_res = mysqli_stmt_get_result(\$elec_q);
\$elec_rows = [];
while (\$r = mysqli_fetch_assoc(\$elec_res)) \$elec_rows[] = \$r;
mysqli_stmt_close(\$elec_q);

\$display_name = \$user['name'] ?: \$user['username'];
\$profile_pic = \$user['profile_pic'] ?: \"assets/img/default-avatar.png\";
\$aadhaar_file = \$user['aadhaar_file'] ?? null;
?>
<!doctype html>
<html lang=\"en\">
<head>
    <meta charset=\"utf-8\">
    <title>My Profile | <?php echo HOUSE_NAME; ?></title>
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no\">
    
    <!-- Immediate Theme Setter to prevent flashes -->
    <script>
        window.HOUSE_NAME = <?php echo json_encode(HOUSE_NAME); ?>;
        ";
        $c = substr_replace($c, $replacement, $pos, $pos_end - $pos);
        file_put_contents("profile.php", $c);
        echo "Successfully restored profile.php\n";
    }
}
?>
