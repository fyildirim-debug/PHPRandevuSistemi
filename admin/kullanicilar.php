<?php
require_once '../config.php';
adminKontrol();

$sayfa = 'kullanicilar';
$sayfa_basligi = 'Kullanıcılar';

// Tüm kullanıcıları getir
$sql = "SELECT * FROM kullanicilar WHERE rol = 'kullanici' ORDER BY created_at DESC";
$kullanicilar = $db->query($sql);

include 'includes/header.php';
include 'includes/topbar.php';
?>

<div id="layoutSidenav">
    <?php include 'includes/sidebar.php'; ?>
    
    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 text-white mb-0">Kullanıcılar</h1>
                        <p class="text-gray mb-0">Kayıtlı kullanıcı listesi</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users me-2"></i>
                        Kayıtlı Kullanıcılar
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="kullanicilarTable">
                                <thead>
                                    <tr>
                                        <th>T.C. No</th>
                                        <th>Ad Soyad</th>
                                        <th>E-posta</th>
                                        <th>Telefon</th>
                                        <th>Durum</th>
                                        <th>Kayıt Tarihi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($kullanici = $kullanicilar->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo sifreCoz($kullanici['tc_no']); ?></td>
                                        <td><?php echo $kullanici['ad_soyad']; ?></td>
                                        <td><?php echo $kullanici['email']; ?></td>
                                        <td><?php echo $kullanici['telefon']; ?></td>
                                        <td>
                                            <?php
                                            $durum_class = [
                                                'beklemede' => 'warning',
                                                'onayli' => 'success',
                                                'reddedildi' => 'danger'
                                            ][$kullanici['durum']];
                                            ?>
                                            <span class="badge bg-<?php echo $durum_class; ?>">
                                                <?php echo ucfirst($kullanici['durum']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($kullanici['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#kullanicilarTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json'
        },
        order: [[5, 'desc']],
        pageLength: 25
    });
});
</script> 