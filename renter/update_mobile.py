import os

files_to_update = [
    r'c:\xampp\htdocs\renter-system\renter\views\mobile\my-payments_mobile.php',
    r'c:\xampp\htdocs\renter-system\renter\views\mobile\my-bills_mobile.php'
]

target = """        <?php elseif ($t['item_type'] === 'rent_or_other'):
                // Determine icons based on source
                if ($t['source'] === 'rent_table' || $t['source'] === 'elec_table' && $amount > 0) {
                    $icon = "<i class='bx bx-home-alt'></i>";
                    $iconStyle = "background: rgba(98, 75, 255, 0.1); color: #624BFF;";
                    $title = "Rent Payment";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                } else if ($t['source'] === 'advance') {
                    $icon = "<i class='bx bx-receipt'></i>";
                    $iconStyle = "background: rgba(59, 130, 246, 0.1); color: #3B82F6;";
                    $title = "Advance Payment";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                } else {
                    $icon = "<i class='bx bx-wrench'></i>";
                    $iconStyle = "background: rgba(255, 75, 107, 0.1); color: #FF4B6B;";
                    $title = "Maintenance Charge";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                }
                $dataType = ($t['source'] === 'rent_table' || $t['source'] === 'elec_table' && $amount > 0) ? 'rent' : (($t['source'] === 'advance') ? 'other' : 'other');
        ?>"""

replacement = """        <?php elseif ($t['item_type'] === 'rent_or_other'):
                // Determine icons based on source
                if (isset($t['split_type']) && $t['split_type'] === 'dues_only') {
                    $icon = "<i class='bx bx-history'></i>";
                    $iconStyle = "background: rgba(245, 158, 11, 0.1); color: #F59E0B;";
                    $title = "Arrears / Remaining";
                    $subtitle = 'Carried forward';
                    $dataType = 'other';
                } else if ($t['source'] === 'rent_table' || $t['source'] === 'elec_table' && $amount > 0) {
                    $icon = "<i class='bx bx-home-alt'></i>";
                    $iconStyle = "background: rgba(98, 75, 255, 0.1); color: #624BFF;";
                    $title = "Rent & Maintenance";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                    $dataType = 'rent';
                } else if ($t['source'] === 'advance') {
                    $icon = "<i class='bx bx-receipt'></i>";
                    $iconStyle = "background: rgba(59, 130, 246, 0.1); color: #3B82F6;";
                    $title = "Advance Payment";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                    $dataType = 'other';
                } else {
                    $icon = "<i class='bx bx-wrench'></i>";
                    $iconStyle = "background: rgba(255, 75, 107, 0.1); color: #FF4B6B;";
                    $title = "Maintenance Charge";
                    $subtitle = date('M Y', strtotime($t['month'] . '-01'));
                    $dataType = 'other';
                }
        ?>"""

for filepath in files_to_update:
    if os.path.exists(filepath):
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        if target in content:
            content = content.replace(target, replacement)
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Updated {filepath}")
        else:
            print(f"Target not found in {filepath}")
    else:
        print(f"File not found: {filepath}")
