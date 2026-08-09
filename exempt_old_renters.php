<?php
require 'db.php';
// Run this file once to exempt all existing renters from the new onboarding rule
mysqli_query($conn, "UPDATE users SET onboarding_completed = 1");
echo "<h3>Success! All existing renters have been exempted from the onboarding dues. The banner will no longer show for them.</h3>";
?>
