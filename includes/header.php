<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title><?php echo isset($sayfa_basligi) ? $sayfa_basligi . ' - ' : ''; ?>Randevu Sistemi</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/your-code.js"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script defer src="https://stats.furkanyildirim.com/script.js" data-website-id="732f974a-a493-43b2-acb2-b185c7213636"></script>
</head>
<body class="sb-nav-fixed">
    <!-- Normal Menü (Masaüstü) -->
    <nav class="navbar navbar-expand-lg navbar-dark desktop-nav">
        <div class="container-fluid px-3">
            
            <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['kullanici_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profil.php">
                                <i class="fas fa-user me-2"></i><?php echo $_SESSION['kullanici_adi']; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="cikis.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Çıkış
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-2"></i>Giriş
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="kayit.php">
                                <i class="fas fa-user-plus me-2"></i>Kayıt
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Basit Mobil Menü -->
    <nav class="navbar navbar-dark mobile-nav">
        <div class="container-fluid px-3">
            <a class="navbar-brand" href="index.php">Randevu Sistemi</a>
            <button class="mobile-menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="Menüyü aç">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Mobil Menü İçeriği -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menü</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="mobile-menu-list">
                <li><a href="index.php"><i class="fas fa-home me-2"></i>Ana Sayfa</a></li>
                <?php if (isset($_SESSION['kullanici_id'])): ?>
                    <li><a href="randevularim.php"><i class="fas fa-calendar-check me-2"></i>Randevularım</a></li>
                    <li><a href="profil.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                    <li><a href="cikis.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış</a></li>
                <?php else: ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt me-2"></i>Giriş</a></li>
                    <li><a href="kayit.php"><i class="fas fa-user-plus me-2"></i>Kayıt</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 