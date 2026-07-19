import os

filepath = r'c:\xampp\htdocs\renter-system\renter\my-bills.php'

with open(filepath, 'r') as f:
    content = f.read()

target = """    $all_bills[] = [
        'id' => $t['id'],
        'type' => 'electricity',
        'filter_type' => $filter_type,
        'period' => $period,
        'title' => $title,
        'subtitle' => $subtitle,
        'due_date' => date('d M Y', $due_timestamp),
        'amount' => (float)$t['amount'],
        'status' => $status,
        'summary' => $summary
    ];"""

replacement = """    $all_bills[] = [
        'id' => $t['id'],
        'type' => 'electricity',
        'filter_type' => $filter_type,
        'period' => $period,
        'title' => $title,
        'subtitle' => $subtitle,
        'due_date' => date('d M Y', $due_timestamp),
        'amount' => (float)$t['amount'],
        'remaining_amount' => isset($t['remaining_amount']) ? (float)$t['remaining_amount'] : (float)$t['amount'],
        'status' => $status,
        'summary' => $summary
    ];"""

if target in content:
    content = content.replace(target, replacement)
    with open(filepath, 'w') as f:
        f.write(content)
    print("Successfully replaced in my-bills.php")
else:
    print("Target not found.")
