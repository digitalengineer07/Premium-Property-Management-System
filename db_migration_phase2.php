<?php
require 'db.php';

$sql1 = "ALTER TABLE `rent` ADD COLUMN `due_date` DATE DEFAULT NULL AFTER `month`";
$sql2 = "ALTER TABLE `electricity` ADD COLUMN `due_date` DATE DEFAULT NULL AFTER `month`";

if (mysqli_query($conn, $sql1)) {
    echo "Added due_date to rent table.\n";
} else {
    echo "Error on rent: " . mysqli_error($conn) . "\n";
}

if (mysqli_query($conn, $sql2)) {
    echo "Added due_date to electricity table.\n";
} else {
    echo "Error on electricity: " . mysqli_error($conn) . "\n";
}

// Optional: Backfill existing records with a due_date based on current date to avoid breaking auto-reminders immediately if they are old.
// Let's set overdue for everything that is already Due so that they will be picked up immediately if needed, or we can just set them to 10 days from now.
// For safety, let's set them to 10 days from their creation if we have created_at, else 10 days from now.
mysqli_query($conn, "UPDATE electricity SET due_date = DATE_ADD(created_at, INTERVAL 10 DAY) WHERE due_date IS NULL");
// rent doesn't have created_at, so we just set due_date to 10 days from now for existing ones to give them a grace period.
mysqli_query($conn, "UPDATE rent SET due_date = DATE_ADD(CURDATE(), INTERVAL 10 DAY) WHERE due_date IS NULL");
echo "Backfilled due_date values.\n";
?>
