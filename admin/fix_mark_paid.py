import os

filepath = r'c:\xampp\htdocs\renter-system\admin\mark-paid.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = "$paid_amount = $remaining_amount; // Default"
replacement = """$paid_amount = $remaining_amount; // Default
if (isset($_POST['paid_amount']) && is_numeric($_POST['paid_amount'])) {
    $paid_amount = (float)$_POST['paid_amount'];
}

if ($paid_amount <= 0) {
    $_SESSION['error'] = "Payment amount must be greater than zero.";
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
    exit;
}
"""

if target in content:
    # First remove the old block
    content = content.replace("""$paid_amount = $remaining_amount; // Default
if (isset($_POST['paid_amount']) && is_numeric($_POST['paid_amount'])) {
    $paid_amount = (float)$_POST['paid_amount'];
}""", replacement)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Injected negative validation in mark-paid.php")
else:
    print("Target not found")
