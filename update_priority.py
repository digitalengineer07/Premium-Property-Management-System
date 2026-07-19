import os

filepath = r'c:\xampp\htdocs\renter-system\admin\allocate_payment.php'

with open(filepath, 'r') as f:
    content = f.read()

target = """    // Sort chronologically
    usort($pending_bills, function($a, $b) {
        $da = strtotime($a['month'] . '-01');
        $db = strtotime($b['month'] . '-01');
        if ($da == $db) {
            return ($a['type'] == 'elec_rent' || $a['type'] == 'rent') ? -1 : 1; // Prioritize rent over elec for same month
        }
        return $da - $db;
    });"""

replacement = """    // Sort chronologically
    usort($pending_bills, function($a, $b) {
        $da = strtotime($a['month'] . '-01');
        $db = strtotime($b['month'] . '-01');
        if ($da == $db) {
            // User requested electricity to be paid first if there are advance credits
            return ($a['type'] == 'electricity') ? -1 : 1; 
        }
        return $da - $db;
    });"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Updated priority in allocate_payment.php")
else:
    print("Target not found!")
