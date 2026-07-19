import os
import re

filepath = r'c:\xampp\htdocs\renter-system\renter\views\desktop\payment-history_desktop.php'

with open(filepath, 'r') as f:
    content = f.read()

# Instead of direct string replacement, let's use regex to find all subtitle occurrences and replace them with a function or inline logic.

# In PHP, I can do:
# $tx_id = $row['transaction_id'] ?: 'N/A';
# $sys_id = $row['sys_tx_id'] ?: 'N/A';
# $subtitle = ($tx_id === $sys_id || $tx_id === 'N/A') ? 'ID: ' . $sys_id : 'Ref: ' . $tx_id . ' | ID: ' . $sys_id;

# Let's replace the direct 'subtitle' array assignment with a clean string.
# We have 3 occurrences:
# line 167: 'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'N/A') . ' | ID: ' . ($row['sys_tx_id'] ?: 'N/A'),
# line 201: 'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'N/A') . ' | ID: ' . ($row['sys_tx_id'] ?: 'N/A'),
# line 245: 'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'Offline') . ' | ID: ' . ($row['sys_tx_id'] ?: 'N/A'),

def replace_subtitle(match):
    # This replaces the exact string pattern with a conditional logic string
    return "'subtitle' => (($row['transaction_id'] ?: 'N/A') === ($row['sys_tx_id'] ?: 'N/A') || ($row['transaction_id'] ?: 'N/A') === 'Offline') ? 'ID: ' . ($row['sys_tx_id'] ?: 'N/A') : 'Ref: ' . ($row['transaction_id'] ?: 'N/A') . ' | ID: ' . ($row['sys_tx_id'] ?: 'N/A'),"

content = content.replace(
    "'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'N/A') . ' | ID: ' . ($row['sys_tx_id'] ?: 'N/A'),",
    "'subtitle' => (($row['transaction_id'] ?: '') === ($row['sys_tx_id'] ?: '') || !trim($row['transaction_id'])) ? 'ID: ' . ($row['sys_tx_id'] ?: 'N/A') : 'Ref: ' . $row['transaction_id'] . ' | ID: ' . ($row['sys_tx_id'] ?: 'N/A'),"
)

content = content.replace(
    "'subtitle' => 'Ref: ' . ($row['transaction_id'] ?: 'Offline') . ' | ID: ' . ($row['sys_tx_id'] ?: 'N/A'),",
    "'subtitle' => (($row['transaction_id'] ?: 'Offline') === ($row['sys_tx_id'] ?: '') || ($row['transaction_id'] ?: 'Offline') === 'Offline') ? 'ID: ' . ($row['sys_tx_id'] ?: 'N/A') : 'Ref: ' . ($row['transaction_id'] ?: 'Offline') . ' | ID: ' . ($row['sys_tx_id'] ?: 'N/A'),"
)

with open(filepath, 'w') as f:
    f.write(content)
print("Updated payment-history_desktop.php")
