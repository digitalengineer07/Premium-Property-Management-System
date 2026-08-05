import os

filepath = r'c:\xampp\htdocs\renter-system\admin\save-bill.php'

with open(filepath, 'r', encoding='utf-8') as f:
    content = f.read()

injection_target = "    // Clean buffer and send success"

injection_code = """    // --- NEW: Enterprise Auto-Credit Application ---
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

"""

if injection_target in content:
    content = content.replace(injection_target, injection_code + injection_target)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print("Injected auto-credit into save-bill.php")
else:
    print("Could not find injection target!")
