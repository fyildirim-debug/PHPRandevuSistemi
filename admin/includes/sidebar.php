<div id="layoutSidenav_nav">
    <nav class="sb-sidenav">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Ana Menü</div>
                <a class="nav-link <?php echo $sayfa == 'panel' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Kontrol Paneli
                </a>

                <div class="sb-sidenav-menu-heading">Program Yönetimi</div>
                <a class="nav-link <?php echo $sayfa == 'program' ? 'active' : ''; ?>" href="program.php">
                    <i class="fas fa-calendar-alt"></i>
                    Program Şablonu
                </a>
                <a class="nav-link <?php echo $sayfa == 'randevular' ? 'active' : ''; ?>" href="randevular.php">
                    <i class="fas fa-calendar-check"></i>
                    Randevular
                </a>

                <div class="sb-sidenav-menu-heading">Kullanıcı Yönetimi</div>
                <a class="nav-link <?php echo $sayfa == 'kullanici_onay' ? 'active' : ''; ?>" href="kullanici_onay.php">
                    <i class="fas fa-user-clock"></i>
                    Kullanıcı Onay
                    <?php
                    // Bekleyen kullanıcı sayısını göster
                    $bekleyen_sql = "SELECT COUNT(*) as sayi FROM kullanicilar WHERE durum = 'beklemede'";
                    $bekleyen = $db->query($bekleyen_sql)->fetch_assoc();
                    if ($bekleyen['sayi'] > 0):
                    ?>
                    <span class="badge bg-danger ms-2"><?php echo $bekleyen['sayi']; ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link <?php echo $sayfa == 'kullanicilar' ? 'active' : ''; ?>" href="kullanicilar.php">
                    <i class="fas fa-users"></i>
                    Kullanıcılar
                </a>

                <div class="sb-sidenav-menu-heading">AYARLAR</div>
                <a class="nav-link <?php echo $sayfa == 'ayarlar' ? 'active' : ''; ?>" href="ayarlar.php">
                    <i class="fas fa-cog"></i>
                    <div class="ms-3">Sistem Ayarları</div>
                </a>

                <a class="nav-link" href="../cikis.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Çıkış Yap
                </a>
            </div>
        </div>
    </nav>
</div> 