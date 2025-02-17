<?php
require_once 'config.php';

// Kullanıcı ID'sini al
$kullanici_id = isset($_SESSION['kullanici_id']) ? $_SESSION['kullanici_id'] : null;

// Session ID'yi veritabanından temizle
if ($kullanici_id) {
    $sql = "UPDATE kullanicilar SET session_id = NULL, last_activity = NULL WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
}

// Çıkış yapılan sayfayı al
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$is_admin = strpos($referer, '/admin/') !== false;

// Session'ı temizle
session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');

// Admin panelinden çıkış yapıldıysa ana sayfaya, değilse login sayfasına yönlendir
if ($is_admin) {
    header('Location: index.php');
} else {
    header('Location: login.php');
}
exit;
?> 