import os

filepath = r'c:\xampp\htdocs\renter-system\admin\add-rent.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

target = "    header(\"Location: view-renter.php?id=$user_id\");"
replacement = """    // --- NEW: Enterprise Auto-Credit Application ---
    $qAdv = mysqli_query($conn, "SELECT advance_payment FROM users WHERE id = $user_id");
    if ($qAdv && $rowAdv = mysqli_fetch_assoc($qAdv)) {
        $adv = (float)$rowAdv['advance_payment'];
        if ($adv > 0) {
            // Temporarily zero the advance so the allocator can redistribute it without doubling
            mysqli_query($conn, "UPDATE users SET advance_payment = 0 WHERE id = $user_id");
            require_once "allocate_payment.php";
            $sys_id = 'SYS-CREDIT-' . time() . '-' . rand(100,999);
            allocate_bulk_payment($conn, $user_id, $adv, 'Advance Credit', $sys_id, $sys_id, null);
        }
    }
    // -----------------------------------------------

    header("Location: view-renter.php?id=$user_id");"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Injected auto-credit logic in add-rent.php")
else:
    print("Target not found")
