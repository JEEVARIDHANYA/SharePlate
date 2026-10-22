<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_start();
}
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function flash(string $type, string $message): void { $_SESSION['flash'] = ['type'=>$type, 'message'=>$message]; }
function take_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void {
    $user = current_user();
    if (!$user) { flash('info', 'Please sign in to continue.'); $prefix = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../' : ''; redirect($prefix . 'login.php'); }
    $stmt = $GLOBALS['pdo']->prepare('SELECT role, is_active FROM users WHERE id=?');
    $stmt->execute([$user['id']]);
    $account = $stmt->fetch();
    if (!$account || !$account['is_active']) { $_SESSION = []; session_regenerate_id(true); flash('error', 'This account is no longer active.'); $prefix = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../' : ''; redirect($prefix . 'login.php'); }
    if ($user['role'] !== $account['role']) { $_SESSION['user']['role'] = $account['role']; }
}
function require_role(array $roles): void { require_login(); if (!in_array(current_user()['role'], $roles, true)) { http_response_code(403); exit('You do not have permission to access this page.'); } }
function expire_donations(PDO $pdo): void {
    $pdo->exec("UPDATE food_donations SET status='Expired', updated_at=NOW() WHERE status IN ('Active','Partially Collected') AND expiry_at <= NOW()");
}
function donation_status_label(array $d): string {
    if (in_array($d['status'], ['Cancelled','Deactivated','Expired','Fully Collected'], true)) return $d['status'];
    return ((float)$d['remaining_quantity'] < (float)$d['quantity']) ? 'Partially Collected' : 'Active';
}
function image_url(?string $filename): string { return $filename ? 'uploads/food/' . rawurlencode(basename($filename)) : 'assets/images/food-placeholder.svg'; }
function safe_upload(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 4 * 1024 * 1024) throw new RuntimeException('Image upload failed or exceeds 4 MB.');
    $info = @getimagesize($file['tmp_name']);
    $mime = $info['mime'] ?? '';
    $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Upload a valid JPG, PNG, or WebP image.');
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $dir = __DIR__ . '/../uploads/food';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Unable to prepare image storage.');
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Unable to save the uploaded image.');
    return $name;
}
function format_datetime(string $datetime): string { return date('M j, Y g:i A', strtotime($datetime)); }
