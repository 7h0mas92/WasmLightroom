<?php



function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}


function redirect(string $url): void
{
    header("Location: $url");
    exit;
}


function requireAuth(): void
{
    if (!isset($_SESSION['user_id'])) {
        redirect('/index.php?route=login');
    }
}


function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}


function currentUsername(): ?string
{
    return $_SESSION['username'] ?? null;
}


function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}


function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}


function generateSecureFilename(string $originalName): string
{
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return bin2hex(random_bytes(16)) . '.' . $ext;
}


function isValidImageMime(string $tmpPath): bool
{
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath);
    return in_array($mime, $allowed, true);
}


function createThumbnail(string $sourcePath, string $destPath, int $maxWidth = 400): bool
{
    $info = getimagesize($sourcePath);
    if (!$info) return false;

    $mime = $info['mime'];
    $origWidth = $info[0];
    $origHeight = $info[1];

    $ratio = $maxWidth / $origWidth;
    $newWidth = $maxWidth;
    $newHeight = (int)($origHeight * $ratio);

    if ($origWidth <= $maxWidth) {
        copy($sourcePath, $destPath);
        return true;
    }

    switch ($mime) {
        case 'image/jpeg':
            $src = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $src = imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            $src = imagecreatefromwebp($sourcePath);
            break;
        case 'image/gif':
            $src = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }

    $thumb = imagecreatetruecolor($newWidth, $newHeight);

    
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($thumb, $destPath, 85);
            break;
        case 'image/png':
            imagepng($thumb, $destPath, 8);
            break;
        case 'image/webp':
            imagewebp($thumb, $destPath, 85);
            break;
        case 'image/gif':
            imagegif($thumb, $destPath);
            break;
    }

    imagedestroy($src);
    imagedestroy($thumb);
    return true;
}


function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
