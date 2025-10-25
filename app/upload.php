<?php
// универсальная функция загрузки
// $type: 'image' or 'audio'
// возвращает относительный путь от /public  (например: uploads/images/2025/09/uniq.webp)
function upload_file(string $field, string $type): ?string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;

    $allowed = $type === 'image'
        ? ['png','jpg','jpeg','webp']
        : ['mp3','wav'];

    $subdir = $type === 'image' ? 'uploads/images' : 'uploads/audio';

    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) return null;

    $y = date('Y'); $m = date('m');
    $targetDir = __DIR__ . '/../public/' . $subdir . "/$y/$m";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $absPath = "$targetDir/$name";
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $absPath)) return null;

    // относительный путь для хранения в БД
    return $subdir . "/$y/$m/$name";
}
