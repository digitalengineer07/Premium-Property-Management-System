import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\my-payments_desktop.php'

target = """                                <td><?php echo $bill['due_date']; ?></td>
                                <td style="font-weight: 800;"><?php echo money($bill['amount']); ?></td>
                                <td><span class="td-status <?php echo strtolower($bill['status']); ?>"><?php echo $bill['status']; ?></span></td>"""

replacement = """                                <td><?php echo $bill['due_date']; ?></td>
                                <td style="font-weight: 800;">
                                    <?php 
                                    if ($bill['status'] === 'Paid') {
                                        echo money($bill['amount']);
                                    } else {
                                        $rem = isset($bill['remaining_amount']) ? $bill['remaining_amount'] : $bill['amount'];
                                        echo money($rem);
                                        if ($bill['status'] === 'Partial' && $rem < $bill['amount']) {
                                            echo "<div style='font-size: 11px; color: var(--text-gray); font-weight: 500;'>Orig: " . money($bill['amount']) . "</div>";
                                        }
                                    }
                                    ?>
                                </td>
                                <td><span class="td-status <?php echo strtolower($bill['status']); ?>"><?php echo $bill['status']; ?></span></td>"""

if os.path.exists(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if target in content:
        content = content.replace(target, replacement)
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Updated {filepath} with dynamic amount logic")
    else:
        print(f"Target not found in {filepath}")
else:
    print(f"File not found: {filepath}")
