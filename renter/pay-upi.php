<?php
require_once "../db.php";
session_start();
require_once "../utils/upi_qr.php";

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$type = $_GET['type'];     // rent | electricity
$id   = (int)$_GET['id'];

$config = require "../config/payment.php";

/* Fetch bill details */
if ($type === 'rent') {
    $q = mysqli_query($conn, "SELECT * FROM rent WHERE id=$id");
    $bill = mysqli_fetch_assoc($q);
    $amount = $bill['rent_amount'];
} else {
    $q = mysqli_query($conn, "SELECT * FROM electricity WHERE id=$id");
    $bill = mysqli_fetch_assoc($q);
    $amount = $bill['total_amount'];
}

$upiLink = generateUPILink(
    $config['upi_id'],
    $config['upi_name'],
    $amount,
    ucfirst($type) . " payment"
);
?>
<!doctype html>
<html>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pay via UPI</title>
  <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>

<div class="container-app">
  <div class="card" style="max-width:420px;margin:auto;text-align:center">

    <h3>Pay ₹<?php echo number_format($amount,2); ?></h3>
    <p class="small-muted">Scan QR using any UPI app</p>

    <!-- QR Code -->
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?php echo urlencode($upiLink); ?>">

    <p class="small-muted" style="margin-top:10px">
      UPI ID: <strong><?php echo htmlspecialchars($config['upi_id']); ?></strong>
    </p>

    <!-- Direct UPI App -->
    <a href="<?php echo $upiLink; ?>" class="btn-primary-strong" style="display:block;margin:10px 0">
      Pay using UPI App
    </a>

    <!-- Submit payment proof -->
    <form method="POST" action="submit-payment.php" enctype="multipart/form-data">
      <input type="hidden" name="type" value="<?php echo $type; ?>">
      <input type="hidden" name="bill_id" value="<?php echo $id; ?>">
      <input type="hidden" name="amount" value="<?php echo $amount; ?>">

      <input type="text" name="upi_txn_ref" placeholder="UPI Reference ID (optional)"
             class="form-control" style="margin-bottom:8px">

      <input type="file" name="screenshot" required class="form-control">

      <button type="submit" class="btn-primary-strong" style="margin-top:10px">
        I Have Paid
      </button>
    </form>

  </div>
</div>

<nav class="mobile-bottom-nav">
    <a href="dashboard.php" class="mb-nav-item "><i class='bx bx-home-alt'></i><span>Dashboard</span></a>
    <a href="my-payments.php" class="mb-nav-item "><i class='bx bx-credit-card'></i><span>Payments</span></a>
    <a href="queries.php" class="mb-nav-item-center ">
        <div class="mb-nav-center">
            <i class='bx bx-plus'></i>
        </div>
        <span>Raise Query</span>
    </a>
    <a href="payment-history.php" class="mb-nav-item "><i class='bx bx-history'></i><span>History</span></a>
    <a href="profile.php" class="mb-nav-item "><i class='bx bx-user'></i><span>Profile</span></a>
</nav>
</body>
</html>
