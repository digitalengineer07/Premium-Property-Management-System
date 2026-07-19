import os
import re

filepath = r'c:\xampp\htdocs\renter-system\renter\views\mobile\my-payments_mobile.php'

with open(filepath, 'r') as f:
    content = f.read()

target = """                if (isset($t['split_type']) && $t['split_type'] === 'dues_only') {
                    $icon = "<i class='bx bx-history'></i>";
                    $iconStyle = "background: rgba(245, 158, 11, 0.1); color: #F59E0B;";
                    $title = "Arrears / Remaining";
                    $subtitle = 'Carried forward';
                    $dataType = 'other';
                }"""

replacement = """                if (isset($t['split_type']) && $t['split_type'] === 'dues_only') {
                    $icon = "<i class='bx bx-receipt'></i>";
                    $iconStyle = "background: rgba(245, 158, 11, 0.1); color: #F59E0B;";
                    $title = "Other Charges";
                    $subtitle = isset($t['extra_charges_desc']) && !empty($t['extra_charges_desc']) ? $t['extra_charges_desc'] : 'Miscellaneous';
                    $dataType = 'other';
                }"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Updated my-payments_mobile.php")
else:
    print("Target not found")
