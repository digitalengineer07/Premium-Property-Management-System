<?php
require_once "../../db.php";

$sql = "SELECT id, username, name FROM users";
$res = mysqli_query($conn, $sql);
$users = [];
while ($row = mysqli_fetch_assoc($res)) {
    $users[] = $row;
}

// Reset the first user's password to '123456' for testing
if (count($users) > 0) {
    $first_user_id = $users[0]['id'];
    $new_hash = password_hash('123456', PASSWORD_DEFAULT);
    mysqli_query($conn, "UPDATE users SET password = '$new_hash' WHERE id = $first_user_id");
    echo "Reset password for user: " . $users[0]['username'] . " to '123456'<br><br>";
}

echo "<pre>";
print_r($users);
echo "</pre>";
?>
