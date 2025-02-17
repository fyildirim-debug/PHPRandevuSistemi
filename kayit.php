<?php
require_once 'config.php';

$sayfa_basligi = 'Kayıt Ol';

// Zaten giriş yapmışsa yönlendir
if (isset($_SESSION['kullanici_id'])) {
    if ($_SESSION['rol'] == 'admin') {
        header('Location: admin/');
    } else {
        header('Location: index.php');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tc_no = trim($_POST['tc_no']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    $sifre = trim($_POST['sifre']);
    $sifre_tekrar = trim($_POST['sifre_tekrar']);
    $ad_soyad = trim($_POST['ad_soyad']);
    
    // Cloudflare Turnstile doğrulama
    $token = $_POST['cf-turnstile-response'];
    $verify_url = "https://challenges.cloudflare.com/turnstile/v0/siteverify";
    $data = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $verify_response = file_get_contents($verify_url, false, $context);
    $turnstile_success = json_decode($verify_response);
    
    if (!$turnstile_success->success) {
        $hatalar[] = "Lütfen doğrulamayı tamamlayın.";
    }
    
    $hatalar = [];
    
    // TC No kontrolü
    if (!preg_match('/^[0-9]{11}$/', $tc_no)) {
        $hatalar[] = "TC Kimlik No 11 haneli olmalıdır.";
    }
    
    // Email kontrolü
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hatalar[] = "Geçerli bir email adresi giriniz.";
    }
    
    // Telefon kontrolü
    if (!preg_match('/^[0-9]{10}$/', $telefon)) {
        $hatalar[] = "Telefon numarası 10 haneli olmalıdır. Başında 0 olmadan giriniz.";
    }
    
    // Şifre kontrolü
    if (strlen($sifre) < 6) {
        $hatalar[] = "Şifre en az 6 karakter olmalıdır.";
    }
    if ($sifre !== $sifre_tekrar) {
        $hatalar[] = "Şifreler eşleşmiyor.";
    }
    
    // TC No ve Email benzersiz olmalı
    $check_sql = "SELECT id FROM kullanicilar WHERE tc_no = ? OR email = ?";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bind_param("ss", $tc_no, $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $hatalar[] = "Bu TC No veya Email adresi zaten kayıtlı.";
    }
    
    if (empty($hatalar)) {
        // TC'yi şifrele
        $tc_sifrelenmis = sifrele($tc_no);
        $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);
        $telefon = '5' . $telefon;
        
        $sql = "INSERT INTO kullanicilar (tc_no, ad_soyad, email, telefon, sifre, rol, durum) 
                VALUES (?, ?, ?, ?, ?, 'kullanici', 'beklemede')";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("sssss", $tc_sifrelenmis, $ad_soyad, $email, $telefon, $sifre_hash);
        
        if ($stmt->execute()) {
            $_SESSION['kayit_basarili'] = "Kayıt başarılı! Hesabınız onaylandıktan sonra giriş yapabilirsiniz.";
            header('Location: login.php');
            exit;
        } else {
            $hatalar[] = "Kayıt sırasında bir hata oluştu.";
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title text-center mb-0">Kayıt Ol</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($hatalar)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($hatalar as $hata): ?>
                                    <li><?php echo $hata; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($basarili)): ?>
                        <div class="alert alert-success"><?php echo $basarili; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="tc_no" class="form-label">TC Kimlik No</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent text-light">
                                    <i class="fas fa-id-card"></i>
                                </span>
                                <input type="text" class="form-control" id="tc_no" name="tc_no" required 
                                       pattern="[0-9]{11}" maxlength="11">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ad_soyad" class="form-label">Ad Soyad</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent text-light">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control" id="ad_soyad" name="ad_soyad" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent text-light">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefon" class="form-label">Telefon (5XX...)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent text-light">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input type="text" class="form-control" id="telefon" name="telefon" required 
                                       pattern="[0-9]{10}" maxlength="10" placeholder="5XXXXXXXXX">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sifre" class="form-label">Şifre</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent text-light">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="sifre" name="sifre" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="sifre_tekrar" class="form-label">Şifre Tekrar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent text-light">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="sifre_tekrar" name="sifre_tekrar" required>
                            </div>
                        </div>
                        
                        <div class="mb-4 d-flex justify-content-center">
                            <div class="cf-turnstile" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" data-theme="dark"></div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Kayıt Ol
                            </button>
                            <a href="login.php" class="btn btn-outline-light">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 