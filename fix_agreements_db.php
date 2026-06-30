<?php
require_once "db.php";
$res = mysqli_query($conn, "SELECT id, username, aadhaar_file, agreement_document, electricity_document FROM users WHERE agreement_document IS NOT NULL OR aadhaar_file IS NOT NULL OR electricity_document IS NOT NULL");
while ($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['id'] . "\n";
    echo "  Aadhaar: " . ($row['aadhaar_file'] ?? 'NULL') . "\n";
    echo "  Agreement: " . ($row['agreement_document'] ?? 'NULL') . "\n";
    echo "  Electricity: " . ($row['electricity_document'] ?? 'NULL') . "\n";
}
?>
