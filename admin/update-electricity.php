<?php
// admin/update-electricity.php
require_once "../db.php";   // include DB first
session_start();
require_once "../audit.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* CSRF */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$errmsg = "";
$success = "";

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$elec_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ===================== HANDLE FORM SUBMIT ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_elec'])) {

    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $errmsg = "Invalid request (CSRF).";
    } else {

        $user_id = (int)($_POST['user_id'] ?? 0);
        $month = trim($_POST['month'] ?? '');

        $prev = (int)($_POST['previous_reading'] ?? 0);
        $curr = (int)($_POST['current_reading'] ?? 0);
        $rate = (float)($_POST['rate_per_unit'] ?? 0);

        if ($user_id <= 0 || $month === '') {
            $errmsg = "User and month are required.";
        } elseif ($curr < $prev) {
            $errmsg = "Current reading cannot be less than previous reading.";
        } else {

            $units = $curr - $prev;
            $amount = round($units * $rate, 2);

            /* ---------- Optional rent / maintenance / extra ---------- */
            $rent = (float)($_POST['rent_amount'] ?? 0);
            $maintenance = (float)($_POST['maintenance'] ?? 0);
            $extra_charges = (float)($_POST['extra_charges'] ?? 0);
            $extra_charges_desc = trim($_POST['extra_charges_desc'] ?? '');
            $total_amount = round($amount + $rent + $maintenance + $extra_charges, 2);

            /* ---------- Handle scanned bill upload ---------- */
            $billPath = null;
            if (!empty($_FILES['bill_file']) && $_FILES['bill_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $f = $_FILES['bill_file'];
                if ($f['error'] !== UPLOAD_ERR_OK) {
                    $errmsg = "Bill upload error.";
                } else {
                    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $f['tmp_name']);
                    finfo_close($finfo);

                    if (!isset($allowed[$mime])) {
                        $errmsg = "Bill must be JPG, PNG or PDF.";
                    } elseif ($f['size'] > 10*1024*1024) {
                        $errmsg = "Bill file must be under 10MB.";
                    } else {
                        $ext = $allowed[$mime];
                        $safe = $user_id . "_bill_" . time() . "." . $ext;
                        $destDir = __DIR__ . "/../uploads/bills/";
                        if (!is_dir($destDir)) mkdir($destDir, 0777, true);

                        if (!move_uploaded_file($f['tmp_name'], $destDir . $safe)) {
                            $errmsg = "Failed to save bill file.";
                        } else {
                            chmod($destDir . $safe, 0644);
                            $billPath = "uploads/bills/" . $safe;
                        }
                    }
                }
            }

            /* ---------- INSERT or UPDATE ---------- */
            if ($errmsg === "") {

                if ($elec_id > 0) {
                    // UPDATE
                    if ($billPath) {
                        $stmt = mysqli_prepare($conn,
                            "UPDATE electricity 
                             SET month=?, previous_reading=?, current_reading=?, 
                                 units_consumed=?, rate_per_unit=?, amount=?, rent_amount=?, maintenance=?,
                                 extra_charges=?, extra_charges_desc=?, total_amount=?, 
                                 bill_file=?, status='Due'
                             WHERE id=?"
                        );
                        mysqli_stmt_bind_param(
                            $stmt,
                            "siiiiddddsssi",
                            $month, $prev, $curr, $units,
                            $rate, $amount, $rent, $maintenance,
                            $extra_charges, $extra_charges_desc, $total_amount,
                            $billPath, $elec_id
                        );
                    } else {
                        $stmt = mysqli_prepare($conn,
                            "UPDATE electricity 
                             SET month=?, previous_reading=?, current_reading=?, 
                                 units_consumed=?, rate_per_unit=?, amount=?, rent_amount=?, maintenance=?,
                                 extra_charges=?, extra_charges_desc=?, total_amount=?, 
                                 status='Due'
                             WHERE id=?"
                        );
                        mysqli_stmt_bind_param(
                            $stmt,
                            "siiiiddddssi",
                            $month, $prev, $curr, $units,
                            $rate, $amount, $rent, $maintenance,
                            $extra_charges, $extra_charges_desc, $total_amount,
                            $elec_id
                        );
                    }
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    logAction($conn, "admin", $_SESSION['admin_id'], "Updated electricity ID $elec_id");

                } else {
                    // INSERT
                    $stmt = mysqli_prepare($conn,
                        "INSERT INTO electricity
                         (user_id, month, previous_reading, current_reading, units_consumed,
                          rate_per_unit, amount, rent_amount, maintenance, extra_charges, extra_charges_desc, total_amount, bill_file, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Due')"
                    );
                    mysqli_stmt_bind_param(
                        $stmt,
                        "isiiiiddddsss",
                        $user_id, $month, $prev, $curr, $units,
                        $rate, $amount, $rent, $maintenance, $extra_charges, $extra_charges_desc, $total_amount, $billPath
                    );
                    mysqli_stmt_execute($stmt);
                    $elec_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);

                    logAction($conn, "admin", $_SESSION['admin_id'], "Created electricity ID $elec_id");
                }

                /* ---------- AUTO GENERATE PDF ---------- */
                require_once __DIR__ . "/generate_pdf.php";
                $pdf = generateSlipPdf($conn, $elec_id);

                if ($pdf !== false) {
                    logAction($conn, "admin", $_SESSION['admin_id'], "Generated PDF slip for electricity ID $elec_id");
                }

                header("Location: update-electricity.php?user_id={$user_id}");
                exit;
            }
        }
    }
}

/* ===================== FETCH USERS ===================== */
$users_q = mysqli_query($conn, "SELECT id, username, name, room_no FROM users ORDER BY name ASC");

/* ===================== FETCH EXISTING RECORD ===================== */
$elec_row = null;
if ($elec_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM electricity WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $elec_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $elec_row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <title>Edit Bill | <?php echo HOUSE_NAME; ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-design-system.css">
    <style>
        .edit-page {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 24px 30px;
            box-sizing: border-box;
        }
        .aesthetic-card {
            background: var(--white);
            border-radius: 20px;
            padding: 24px 32px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            position: relative;
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 11.5px;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            background: var(--bg-main);
            color: var(--text-dark);
            outline: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 14px;
            font-weight: 600;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary-purple);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(98, 75, 255, 0.12);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
        }
        .welcome { text-align: left; margin-bottom: 20px !important; border-bottom: 2px dashed rgba(0,0,0,0.06); padding-bottom: 16px; }
        .welcome h1 { 
            font-size: 32px; 
            font-weight: 900; 
            letter-spacing: -1.2px; 
            margin-bottom: 4px; 
            background: linear-gradient(135deg, var(--text-dark) 0%, var(--primary-purple) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .welcome p { color: var(--text-gray); font-size: 14px; font-weight: 500; }
        .section-divider {
            display: inline-block;
            padding: 6px 16px;
            background: #F8FAFC;
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            color: var(--primary-purple);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 10px 0 14px 0;
        }
        @media (max-width: 768px) {
            .form-grid, .form-grid-3 { grid-template-columns: 1fr; }
            .edit-page { padding: 0 16px 30px; margin-top: 15px; }
            .aesthetic-card { padding: 20px; }
            .welcome h1 { font-size: 26px; }
        }
    </style>
</head>
<body>

<?php include "sidebar.php"; ?>

<main class="main">
    <?php include 'header.php'; ?>

    <div class="edit-page animate-up">
        <?php if ($errmsg): ?>
            <div style="background: #FEE2E2; color: #EF4444; padding: 15px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-error-circle' style="font-size: 20px;"></i> <?php echo htmlspecialchars($errmsg); ?>
            </div>
        <?php endif; ?>

        <div class="aesthetic-card">
            <div class="welcome">
                <h1>Edit Bill</h1>
                <p>Modify readings, rates, or update fixed charges for Bill #<?php echo $elec_id; ?></p>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($elec_row['user_id'] ?? $user_id); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Resident</label>
                        <select disabled style="background: var(--bg-main); border-color: transparent; cursor: not-allowed; color: var(--text-gray); font-weight: 600;">
                            <?php 
                            mysqli_data_seek($users_q, 0);
                            while ($u = mysqli_fetch_assoc($users_q)):
                                $sel = (($elec_row['user_id'] ?? $user_id) == $u['id']) ? "selected" : "";
                                $label = ($u['name'] ?: $u['username']) . " — Room " . ($u['room_no'] ?: 'N/A');
                            ?>
                            <option value="<?php echo $u['id']; ?>" <?php echo $sel; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Billing Month <span style="color:#EF4444">*</span></label>
                        <input name="month" value="<?php echo htmlspecialchars($elec_row['month'] ?? ''); ?>" required placeholder="e.g. 2024-02">
                    </div>
                </div>

                <div class="form-group" style="text-align: center; border-top: 1px dashed rgba(0,0,0,0.08); margin-top: 30px;">
                    <div class="section-divider"><i class='bx bx-tachometer'></i> Readings & Rates</div>
                </div>

                <div class="form-grid-3">
                    <div class="form-group">
                        <label>Previous Reading</label>
                        <input name="previous_reading" type="number" value="<?php echo htmlspecialchars($elec_row['previous_reading'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Current Reading <span style="color:#EF4444">*</span></label>
                        <input name="current_reading" type="number" value="<?php echo htmlspecialchars($elec_row['current_reading'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Rate per Unit (₹)</label>
                        <input name="rate_per_unit" type="number" step="0.01" value="<?php echo htmlspecialchars($elec_row['rate_per_unit'] ?? '8'); ?>">
                    </div>
                </div>

                <div class="form-group" style="text-align: center; border-top: 1px dashed rgba(0,0,0,0.08); margin-top: 30px;">
                    <div class="section-divider"><i class='bx bx-building-house'></i> Fixed Charges</div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Rent Amount (₹)</label>
                        <input name="rent_amount" type="number" step="0.01" value="<?php echo htmlspecialchars($elec_row['rent_amount'] ?? '0'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Maintenance (₹)</label>
                        <input name="maintenance" type="number" step="0.01" value="<?php echo htmlspecialchars($elec_row['maintenance'] ?? '0'); ?>">
                    </div>
                </div>

                <div class="form-group" style="text-align: center; border-top: 1px dashed rgba(0,0,0,0.08); margin-top: 30px;">
                    <div class="section-divider"><i class='bx bx-plus-circle'></i> Extra Adjustments</div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Extra Charges (₹)</label>
                        <input name="extra_charges" type="number" step="0.01" value="<?php echo htmlspecialchars($elec_row['extra_charges'] ?? '0'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Extra Detail</label>
                        <input name="extra_charges_desc" type="text" value="<?php echo htmlspecialchars($elec_row['extra_charges_desc'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group" style="text-align: center; border-top: 1px dashed rgba(0,0,0,0.08); margin-top: 30px;">
                    <div class="section-divider"><i class='bx bx-file'></i> Backup / External Bill</div>
                </div>

                <div class="form-group">
                    <label>Upload Manual PDF Scan (Optional)</label>
                    <input type="file" name="bill_file" accept=".jpg,.png,.jpeg,.pdf" style="padding: 10px; cursor: pointer; background: transparent; border: 1px dashed var(--border);">
                    
                    <?php if (!empty($elec_row['bill_file'])): ?>
                        <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <a href="../<?php echo htmlspecialchars($elec_row['bill_file']); ?>" target="_blank" class="btn-outline" style="font-size: 12px; padding: 6px 14px;"><i class='bx bx-download'></i> View Current Scan</a>
                            <a href="slip.php?elec_id=<?php echo (int)$elec_row['id']; ?>" target="_blank" class="btn-primary" style="font-size: 12px; padding: 6px 14px; background: var(--primary-purple);"><i class='bx bx-receipt'></i> View Gen PDF</a>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" name="save_elec" class="btn-primary" style="width: 100%; justify-content: center; padding: 14px; margin-top: 10px; font-size: 15px; background: #10B981;">
                    <i class='bx bx-save'></i> Save Changes & Generate
                </button>
            </form>
        </div>
    </div>
</main>
</body>
</html>
