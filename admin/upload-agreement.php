<?php
// admin/upload-agreement.php
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

    if (isset($_FILES['agreement_file']) && $_FILES['agreement_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['agreement_file']['tmp_name'];
        $file_name = $_FILES['agreement_file']['name'];
        $file_size = $_FILES['agreement_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['pdf', 'png', 'jpg', 'jpeg'];
        
        // Final security check for executable content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        
        $allowed_mimes = ['application/pdf', 'image/png', 'image/jpeg', 'image/pjpeg'];
        
        if (!in_array($file_ext, $allowed_exts) || !in_array($mime, $allowed_mimes)) {
            die("Invalid file type. Only PDF, PNG, JPG allowed.");
        }

        // 200MB limit
        if ($file_size > 200 * 1024 * 1024) {
            die("File size exceeds 200MB.");
        }

        // New name: renterID_agreement_timestamp.ext
        $new_name = $user_id . "_agreement_" . time() . "." . $file_ext;
        $upload_dir = "../uploads/agreements/";
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Fetch old file to replace automatically
        $stmt = mysqli_prepare($conn, "SELECT agreement_document FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user_data = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($user_data && !empty($user_data['agreement_document'])) {
            $old_rel = $user_data['agreement_document'];
            $old_file = (strpos($old_rel, 'uploads/') === 0) ? "../" . $old_rel : $upload_dir . $old_rel;
            if (file_exists($old_file)) {
                @unlink($old_file);
            }
        }

        if (move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
            chmod($upload_dir . $new_name, 0644);
            $full_path_for_db = "uploads/agreements/" . $new_name;
            $stmt = mysqli_prepare($conn, "UPDATE users SET agreement_document = ?, agreement_upload_date = NOW(), agreement_expiry_date = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $full_path_for_db, $expiry_date, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            header("Location: view-renter.php?id=" . $user_id . "&success=agreement_uploaded");
            exit;
        } else {
            die("Failed to move uploaded file.");
        }
    } else {
        die("Upload error code: " . ($_FILES['agreement_file']['error'] ?? 'No file'));
    }
}
header("Location: manage-renters.php");
exit;
?>
