<div id="layoutSidenav_nav">
    <nav class="sb-sidenav">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">Ana Menü</div>
                <a class="nav-link <?php echo $sayfa == 'panel' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Kontrol Paneli
                </a>

                <div class="sb-sidenav-menu-heading">Randevu İşlemleri</div>
                <a class="nav-link <?php echo $sayfa == 'randevular' ? 'active' : ''; ?>" href="randevular.php">
                    <i class="fas fa-calendar-alt"></i>
                    Randevu Al
                </a>
                <a class="nav-link <?php echo $sayfa == 'randevularim' ? 'active' : ''; ?>" href="randevularim.php">
                    <i class="fas fa-calendar-check"></i>
                    Randevularım
                </a>

                <div class="sb-sidenav-menu-heading">Hesap</div>
                <a class="nav-link <?php echo $sayfa == 'profil' ? 'active' : ''; ?>" href="profil.php">
                    <i class="fas fa-user"></i>
                    Profil Bilgileri
                </a>
                <a class="nav-link" href="cikis.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Çıkış Yap
                </a>
            </div>
        </div>
    </nav>
</div> 