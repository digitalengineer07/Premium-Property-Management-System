import os

filepath = r'c:\xampp\htdocs\renter-system\renter\views\mobile\my-payments_mobile.php'

with open(filepath, 'r') as f:
    content = f.read()

target1 = """<button onclick="openPaymentModal(<?php echo max(0, min((float)$amount, (float)$total_due)); ?>, 'Total Payment', 'monthly', 0, '<?php echo addslashes(date('F Y', strtotime($t['month'].'-01'))); ?>')" """
replacement1 = """<button onclick="openPaymentModal(<?php echo max(0, min((float)$displayAmount, (float)$total_due)); ?>, 'Total Payment', 'monthly', 0, '<?php echo addslashes(date('F Y', strtotime($t['month'].'-01'))); ?>')" """

target2 = """<button onclick="openPaymentModal(<?php echo max(0, min((float)$amount, (float)$total_due)); ?>, '<?php echo addslashes($title); ?>', '<?php echo $t['source'] === 'advance' ? 'advance' : 'rent'; ?>', <?php echo $t['id']; ?>, '<?php echo addslashes(date('F Y', strtotime($t['month'].'-01'))); ?>')" """
replacement2 = """<button onclick="openPaymentModal(<?php echo max(0, min((float)$displayAmount, (float)$total_due)); ?>, '<?php echo addslashes($title); ?>', '<?php echo $t['source'] === 'advance' ? 'advance' : 'rent'; ?>', <?php echo $t['id']; ?>, '<?php echo addslashes(date('F Y', strtotime($t['month'].'-01'))); ?>')" """

target3 = """<button onclick="openPaymentModal(<?php echo max(0, min((float)$amount, (float)$total_due)); ?>, 'Electricity Bill', 'electricity', <?php echo $t['id']; ?>, '<?php echo addslashes(date('F Y', strtotime($t['month'].'-01'))); ?>')" """
replacement3 = """<button onclick="openPaymentModal(<?php echo max(0, min((float)$displayAmount, (float)$total_due)); ?>, 'Electricity Bill', 'electricity', <?php echo $t['id']; ?>, '<?php echo addslashes(date('F Y', strtotime($t['month'].'-01'))); ?>')" """


if target1 in content:
    content = content.replace(target1, replacement1)
    
if target2 in content:
    content = content.replace(target2, replacement2)
    
if target3 in content:
    content = content.replace(target3, replacement3)

with open(filepath, 'w') as f:
    f.write(content)
print("Updated my-payments_mobile.php")
