<?php
$token = base64_encode(json_encode([
    "id" => 1,
    "username" => "vijay201",
    "role" => "resident",
    "time" => time()
]));

$opts = [
    "http" => [
        "method" => "GET",
        "header" => "Authorization: Bearer " . $token . "\r\n" .
                    "Accept: application/json\r\n"
    ]
];

$context = stream_context_create($opts);
$result = @file_get_contents('http://localhost/renter-system/api/resident/dashboard_stats.php', false, $context);
$status_line = $http_response_header[0] ?? '';

echo "Status: " . $status_line . "\n";
echo "Response: " . $result . "\n";
?>
