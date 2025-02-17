<?php
require_once __DIR__ . '/includes/functions.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Istanbul');

// Veritabanı bağlantısı
$db = new mysqli('localhost', 'root', '', '');
$db->set_charset("utf8mb4");

if ($db->connect_error) {
    die("Bağlantı hatası: " . $db->connect_error);
}

// Şifreleme anahtarı - güvenli bir yerde saklanmalı
define('ENCRYPTION_KEY', '');
define('ENCRYPTION_IV', str_pad('', 16, "\0")); // 16 byte'a tamamla

// Cloudflare Turnstile anahtarları
define('TURNSTILE_SITE_KEY', ''); // Cloudflare'dan alınan site anahtarı ile değiştirin
define('TURNSTILE_SECRET_KEY', ''); // Cloudflare'dan alınan gizli anahtar ile değiştirin

// Şifreleme fonksiyonu
function sifrele($data) {
    if (empty($data)) return '';
    
    $cipher = "AES-256-CBC";
    $encrypted = openssl_encrypt(
        $data, 
        $cipher, 
        ENCRYPTION_KEY, 
        0, 
        ENCRYPTION_IV
    );
    
    return base64_encode($encrypted);
}

// Şifre çözme fonksiyonu
function sifreCoz($data) {
    if (empty($data)) return '';
    
    try {
        $cipher = "AES-256-CBC";
        $decrypted = openssl_decrypt(
            base64_decode($data),
            $cipher, 
            ENCRYPTION_KEY, 
            0, 
            ENCRYPTION_IV
        );
        
        return $decrypted !== false ? $decrypted : '';
    } catch (Exception $e) {
        error_log('Şifre çözme hatası: ' . $e->getMessage());
        return '';
    }
}

// Güvenlik fonksiyonu
function guvenlik($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Admin kontrolü
function adminKontrol() {
    if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] !== 'admin') {
        header('Location: ../login.php');
        exit;
    }
}

// Kullanıcı kontrolü
function kullaniciKontrol() {
    if (!isset($_SESSION['kullanici_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Kullanıcı parmak izi oluştur
function kullaniciParmakIzi() {
    $browser = $_SERVER['HTTP_USER_AGENT'];
    $ip = $_SERVER['REMOTE_ADDR'];
    return hash('sha256', $browser . $ip . ENCRYPTION_KEY);
}

// Oturum güvenliği kontrolü
function oturumKontrol($db) {
    if (!isset($_SESSION['kullanici_id']) || !isset($_SESSION['parmak_izi'])) {
        return false;
    }

    // Veritabanından kullanıcı bilgilerini al
    $sql = "SELECT browser_hash, session_id, last_activity FROM kullanicilar WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $_SESSION['kullanici_id']);
    $stmt->execute();
    $kullanici = $stmt->get_result()->fetch_assoc();

    if (!$kullanici) {
        session_destroy();
        return false;
    }

    // Browser hash kontrolü
    if ($kullanici['browser_hash'] !== $_SESSION['browser_hash']) {
        session_destroy();
        return false;
    }

    // Session ID kontrolü
    if ($kullanici['session_id'] !== session_id()) {
        session_destroy();
        return false;
    }

    // Son aktivite kontrolü (30 dakika)
    $last_activity = strtotime($kullanici['last_activity']);
    if (time() - $last_activity > 1800) {
        session_destroy();
        return false;
    }

    // Son aktivite zamanını güncelle
    $sql = "UPDATE kullanicilar SET last_activity = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $_SESSION['kullanici_id']);
    $stmt->execute();

    // Parmak izi kontrolü
    $mevcut_parmak_izi = kullaniciParmakIzi();
    if ($_SESSION['parmak_izi'] !== $mevcut_parmak_izi) {
        session_destroy();
        return false;
    }

    return true;
}

// Güvenli oturum başlat
function guvenliOturumBaslat($kullanici, $db) {
    session_regenerate_id(true); // Oturum ID'sini yenile
    $browser_hash = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $session_id = session_id();
    
    // Kullanıcı RFC bilgilerini güncelle
    $sql = "UPDATE kullanicilar SET 
            browser_hash = ?,
            ip_address = ?,
            session_id = ?,
            last_activity = CURRENT_TIMESTAMP
            WHERE id = ?";
            
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sssi", $browser_hash, $ip_address, $session_id, $kullanici['id']);
    $stmt->execute();
    
    $_SESSION['kullanici_id'] = $kullanici['id'];
    $_SESSION['kullanici_adi'] = $kullanici['ad_soyad'];
    $_SESSION['tc_no'] = sifreCoz($kullanici['tc_no']);
    $_SESSION['rol'] = $kullanici['rol'];
    $_SESSION['parmak_izi'] = kullaniciParmakIzi();
    $_SESSION['browser_hash'] = $browser_hash;
    $_SESSION['session_id'] = $session_id;
    
    // Oturum süresini 30 dakika olarak ayarla
    session_set_cookie_params([
        'lifetime' => 1800,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Her sayfanın başında çağrılacak güvenlik kontrolü
function guvenlikKontrol($db) {
    if (!oturumKontrol($db)) {
        session_destroy();
        header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../login.php' : 'login.php'));
        exit;
    }
}
?> 