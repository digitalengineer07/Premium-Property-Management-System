<?php
require_once "db.php";

$res = mysqli_query($conn, "SELECT id, agreement_document FROM users WHERE agreement_document IS NOT NULL AND agreement_document != ''");
while ($row = mysqli_fetch_assoc($res)) {
    $doc = $row['agreement_document'];
    if (strpos($doc, 'uploads/') !== 0) {
        $new_doc = 'uploads/agreements/' . ltrim($doc, '/');
        $id = (int)$row['id'];
        mysqli_query($conn, "UPDATE users SET agreement_document = '$new_doc' WHERE id = $id");
        echo "Updated user $id agreement_document to $new_doc\n";
    } else {
        echo "User " . $row['id'] . " already normalized: $doc\n";
    }
}
echo "Database normalization complete.\n";
?>
