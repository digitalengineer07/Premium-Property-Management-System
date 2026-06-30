<?php
// admin/upload-electricity-doc.php - Secure Medium for uploading Electricity Bill Copy
require_once "../db.php";
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];

    if (isset($_FILES['electricity_file']) && $_FILES['electricity_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['electricity_file']['tmp_name'];
        $file_name = $_FILES['electricity_file']['name'];
        $file_size = $_FILES['electricity_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['pdf', 'png', 'jpg', 'jpeg'];
        
        // Strict security check for MIME type via fileinfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);
        
        $allowed_mimes = ['application/pdf', 'image/png', 'image/jpeg', 'image/pjpeg'];
        
        if (!in_array($file_ext, $allowed_exts) || !in_array($mime, $allowed_mimes)) {
            die("Security Error: Invalid file type or corrupted format. Only verified PDF, PNG, or JPG files are permitted.");
        }

        // Size limit check (up to 200MB)
        if ($file_size > 200 * 1024 * 1024) {
            die("File size exceeds the 200MB maximum limit.");
        }

        $new_name = $user_id . "_electricity_" . time() . "." . $file_ext;
        $upload_dir = "../uploads/documents/";
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Fetch existing file to clean up storage securely
        $stmt = mysqli_prepare($conn, "SELECT electricity_document FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user_data = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($user_data && !empty($user_data['electricity_document'])) {
            $old_file = "../" . $user_data['electricity_document'];
            if (file_exists($old_file)) {
                @unlink($old_file);
            }
        }

        if (move_uploaded_file($file_tmp, $upload_dir . $new_name)) {
            chmod($upload_dir . $new_name, 0644);
            $full_path_for_db = "uploads/documents/" . $new_name;
            $stmt = mysqli_prepare($conn, "UPDATE users SET electricity_document = ?, electricity_upload_date = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $full_path_for_db, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            header("Location: view-renter.php?id=" . $user_id . "&success=electricity_uploaded");
            exit;
        } else {
            die("Failed to securely store uploaded file.");
        }
    } else {
        die("Upload error code: " . ($_FILES['electricity_file']['error'] ?? 'No file provided'));
    }
}
header("Location: manage-renters.php");
exit;
?>
