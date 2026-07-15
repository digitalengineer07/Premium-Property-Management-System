<?php
include "db.php";

$columns = [
    'aadhaar_file',
    'agreement_document',
    'agreement_upload_date',
    'electricity_document',
    'electricity_upload_date'
];

$res = mysqli_query($conn, "DESCRIBE users");
$existing_columns = [];
while ($row = mysqli_fetch_assoc($res)) {
    $existing_columns[] = $row['Field'];
}

$missing = array_diff($columns, $existing_columns);

if (empty($missing)) {
    echo "All columns exist.\n";
} else {
    echo "Missing columns in users table:\n";
    foreach ($missing as $col) {
        echo "- $col\n";
    }
}
