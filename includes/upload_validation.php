<?php
function validated_upload_extension(array $file, bool $allowPdf = false): ?string {
    $path = $file['tmp_name'] ?? null;
    if (($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($path) || !is_uploaded_file($path)) return null;
    $size = filesize($path);
    if ($size === false || $size < 1 || $size > 5 * 1024 * 1024) return null;

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    return match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => $allowPdf ? 'pdf' : null,
        default => null,
    };
}
