import re

with open('documents.php', 'r', encoding='utf-8') as f:
    content = f.read()

old_query = r"""\$stmt = mysqli_prepare\(\$conn, "SELECT aadhaar_file, agreement_document, agreement_upload_date FROM users WHERE id = \?"\);"""
new_query = """$stmt = mysqli_prepare($conn, "SELECT aadhaar_file, agreement_document, agreement_upload_date, electricity_document, electricity_upload_date FROM users WHERE id = ?");"""
content = re.sub(old_query, new_query, content)

old_php = r"""\$verified_count = \(!empty\(\$user_docs\['aadhaar_file'\]\) \? 1 : 0\) \+ \(!empty\(\$user_docs\['agreement_document'\]\) \? 1 : 0\);
\$pending_count = 2 - \$verified_count;"""
new_php = """if (!empty($user_docs['electricity_document'])) {
    $date_str = date('d M Y', strtotime($user_docs['electricity_upload_date'] ?? 'now'));
    $documents[] = [
        'name' => 'Electricity Copy', 'desc' => 'Utility Document', 'category' => 'Utility', 'cat_color' => '#10B981', 'cat_bg' => 'rgba(16, 185, 129, 0.1)',
        'date' => $date_str, 'time' => '', 'status' => 'Verified', 'size' => 'Available', 'icon' => 'bx-bolt-circle', 'url' => '../' . $user_docs['electricity_document']
    ];
} else {
    $documents[] = [
        'name' => 'Electricity Copy', 'desc' => 'Utility Document', 'category' => 'Utility', 'cat_color' => '#10B981', 'cat_bg' => 'rgba(16, 185, 129, 0.1)',
        'date' => '-', 'time' => '-', 'status' => 'Pending', 'size' => '-', 'icon' => 'bx-bolt-circle', 'url' => ''
    ];
}

$verified_count = (!empty($user_docs['aadhaar_file']) ? 1 : 0) + (!empty($user_docs['agreement_document']) ? 1 : 0) + (!empty($user_docs['electricity_document']) ? 1 : 0);
$pending_count = 3 - $verified_count;"""
content = re.sub(old_php, new_php, content)

with open('documents.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated documents.php successfully")
