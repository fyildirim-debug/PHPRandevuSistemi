<nav class="sb-topnav navbar navbar-expand navbar-dark">
    <!-- Navbar Brand-->
    <a class="navbar-brand ps-3" href="index.php">
        <!-- Mobil için menü butonu -->
        <button class="btn btn-link d-lg-none me-2" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        Randevu Sistemi
    </a>
    
    <!-- Navbar Search-->
    
    <!-- Navbar-->
    <ul class="navbar-nav ms-auto me-3">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="me-2"><?php echo $_SESSION['kullanici_adi']; ?></span>
                <i class="fas fa-user fa-fw"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="../cikis.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
            </ul>
        </li>
    </ul>
</nav> 