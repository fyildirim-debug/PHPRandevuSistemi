<?php
require_once '../config.php';
adminKontrol();

$sayfa = 'ayarlar';
$sayfa_basligi = 'Sistem Ayarları';

// Ayarları getir
$ayarlar = $db->query("SELECT * FROM ayarlar LIMIT 1")->fetch_assoc();

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $haftalik_gorunum = (int)$_POST['haftalik_gorunum'];
    $haftalik_randevu = (int)$_POST['haftalik_randevu'];
    $sistem_aktif = isset($_POST['sistem_aktif']) ? 1 : 0;
    
    $sql = "UPDATE ayarlar SET 
            haftalik_gorunum_limiti = ?,
            haftalik_randevu_limiti = ?,
            sistem_aktif = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iii", $haftalik_gorunum, $haftalik_randevu, $sistem_aktif);
    
    if ($stmt->execute()) {
        $basarili = "Ayarlar başarıyla güncellendi.";
        $ayarlar = $db->query("SELECT * FROM ayarlar LIMIT 1")->fetch_assoc();
    } else {
        $hata = "Ayarlar güncellenirken bir hata oluştu.";
    }
}

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
                        <h1 class="h3 text-white mb-0">
                            <i class="fas fa-cog me-2"></i>Sistem Ayarları
                        </h1>
                        <p class="text-gray mb-0">Randevu sistemi genel ayarları ve raporları</p>
                    </div>
                    <div class="system-status">
                        <span class="badge <?php echo $ayarlar['sistem_aktif'] ? 'bg-success' : 'bg-danger'; ?> p-2">
                            <i class="fas <?php echo $ayarlar['sistem_aktif'] ? 'fa-check-circle' : 'fa-times-circle'; ?> me-2"></i>
                            Sistem <?php echo $ayarlar['sistem_aktif'] ? 'Aktif' : 'Kapalı'; ?>
                        </span>
                    </div>
                </div>

                <?php if (isset($basarili)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $basarili; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($hata)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $hata; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Ayarlar Kartı -->
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-sliders-h me-2"></i>Genel Ayarlar
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="setting-item">
                                                <label class="form-label">
                                                    <i class="fas fa-calendar-week me-2"></i>
                                                    Haftalık Görüntüleme Limiti
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" name="haftalik_gorunum" 
                                                           value="<?php echo $ayarlar['haftalik_gorunum_limiti']; ?>" min="0">
                                                    <span class="input-group-text">hafta</span>
                                                </div>
                                                <small class="text-gray mt-2">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    0: Sadece bu hafta, 1: Bu hafta ve gelecek hafta...
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="setting-item">
                                                <label class="form-label">
                                                    <i class="fas fa-clock me-2"></i>
                                                    Haftalık Randevu Limiti
                                                </label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" name="haftalik_randevu" 
                                                           value="<?php echo $ayarlar['haftalik_randevu_limiti']; ?>" min="1">
                                                    <span class="input-group-text">randevu</span>
                                                </div>
                                                <small class="text-gray mt-2">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Bir kullanıcının haftalık alabileceği maksimum randevu sayısı
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="setting-item h-100">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="sistem_aktif" 
                                                               id="sistem_aktif" <?php echo $ayarlar['sistem_aktif'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="sistem_aktif">
                                                            <i class="fas fa-power-off me-2"></i>
                                                            Randevu Sistemi Aktif
                                                        </label>
                                                        <small class="text-gray d-block mt-2">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Sistem kapalıyken kullanıcılar randevu alamaz
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="setting-item h-100">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <label class="form-label mb-0">
                                                            <i class="fas fa-trash-alt me-2"></i>
                                                            Sistem Temizleme
                                                        </label>
                                                        <small class="text-gray d-block mt-2">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Program şablonunu ve randevu kayıtlarını temizler
                                                        </small>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#sistemTemizleModal"
                                                                data-bs-tooltip="tooltip" 
                                                                title="Tüm program ve randevu verilerini sıfırlar">
                                                            <i class="fas fa-trash-alt me-2"></i>Sistemi Sıfırla
                                                        </button>
                                                        <i class="fas fa-question-circle ms-2 text-gray" 
                                                           data-bs-toggle="popover"
                                                           data-bs-placement="left"
                                                           data-bs-html="true"
                                                           data-bs-content="<strong>Bu işlem şunları temizler:</strong><br>
                                                           • Program şablonundaki tüm saatler<br>
                                                           • Oluşturulmuş tüm randevu kayıtları<br>
                                                           • Kullanıcılara atanmış randevular<br><br>
                                                           <small class='text-warning'><i class='fas fa-exclamation-triangle me-1'></i>Bu işlem geri alınamaz!</small>">
                                                        </i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Ayarları Kaydet
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Rapor Kartı -->
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-file-export me-2"></i>Randevu Raporları
                            </div>
                            <div class="card-body">
                                <form method="GET" action="rapor_indir.php" target="_blank">
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            Tarih Aralığı
                                        </label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">Başlangıç</span>
                                            <input type="date" class="form-control" name="baslangic" required>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text">Bitiş</span>
                                            <input type="date" class="form-control" name="bitis" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-download me-2"></i>Rapor İndir
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include 'includes/footer.php'; ?>
    </div>
</div>

<!-- Şablon Temizleme Modal -->
<div class="modal fade" id="sablonTemizleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Program Şablonunu Temizle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Dikkat!</strong> Bu işlem geri alınamaz. Program şablonundaki tüm saatler silinecektir.
                </div>
                <p>Devam etmek istediğinize emin misiniz?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-danger" id="sablonTemizleBtn">
                    <i class="fas fa-trash-alt me-2"></i>Şablonu Temizle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Randevu Temizleme Modal -->
<div class="modal fade" id="randevuTemizleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Randevu Saatlerini Temizle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Dikkat!</strong> Bu işlem geri alınamaz. Tüm randevu saatleri silinecektir.
                </div>
                <p>Devam etmek istediğinize emin misiniz?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-danger" id="randevuTemizleBtn">
                    <i class="fas fa-trash-alt me-2"></i>Randevuları Temizle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Temizleme Modal -->
<div class="modal fade" id="sistemTemizleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sistemi Temizle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Dikkat!</strong> Bu işlem geri alınamaz.
                </div>
                <p>Bu işlem:</p>
                <ul class="text-gray mb-3">
                    <li>Tüm program şablonlarını silecek</li>
                    <li>Tüm randevu kayıtlarını temizleyecek</li>
                    <li>Sistemdeki tüm randevu saatlerini sıfırlayacak</li>
                </ul>
                <p>Devam etmek istediğinize emin misiniz?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-danger" id="sistemTemizleBtn">
                    <i class="fas fa-trash-alt me-2"></i>Sistemi Temizle
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Şablon Temizleme
    $('#sablonTemizleBtn').click(function() {
        $.ajax({
            url: 'ajax/sablon_temizle.php',
            method: 'POST',
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            }
        });
    });

    // Randevu Temizleme
    $('#randevuTemizleBtn').click(function() {
        $.ajax({
            url: 'ajax/randevu_temizle.php',
            method: 'POST',
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    location.reload();
                } else {
                    alert('Hata: ' + data.message);
                }
            }
        });
    });

    // Sistem Temizleme
    $('#sistemTemizleBtn').click(function() {
        $.ajax({
            url: 'ajax/sablon_temizle.php',
            method: 'POST',
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    $.ajax({
                        url: 'ajax/randevu_temizle.php',
                        method: 'POST',
                        success: function(response) {
                            var data = JSON.parse(response);
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('Hata: ' + data.message);
                            }
                        }
                    });
                } else {
                    alert('Hata: ' + data.message);
                }
            }
        });
    });
});
</script>

<style>
.setting-item {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 12px;
    padding: 1.5rem;
    height: 100%;
}

.setting-item .form-label {
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--white);
}

.setting-item .input-group {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.setting-item .input-group-text {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: var(--gray);
}

.form-check-input {
    width: 3rem;
    height: 1.5rem;
    margin-right: 1rem;
}

.system-status .badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.alert {
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.alert-success {
    border-left: 4px solid var(--success);
}

.alert-danger {
    border-left: 4px solid var(--danger);
}

.btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

/* Modal Stilleri */
.modal-content {
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
}

.modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
}

.modal-header .modal-title {
    color: var(--light);
    font-weight: 600;
}

.modal-body {
    padding: 1.5rem;
    color: var(--light);
}

.modal-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1.25rem 1.5rem;
}

.modal .btn-close {
    color: var(--light);
    filter: invert(1) grayscale(100%) brightness(200%);
    opacity: 0.75;
    transition: opacity 0.2s;
}

.modal .btn-close:hover {
    opacity: 1;
}

.modal .alert {
    background: rgba(255, 255, 255, 0.05);
    border: none;
}

.modal .alert-warning {
    border-left: 4px solid var(--warning);
    color: var(--warning);
}

.modal .alert i {
    color: var(--warning);
}

.modal .btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: var(--light);
}

.modal .btn-secondary:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.25);
}

.modal .btn-danger {
    background: var(--danger);
    border: none;
}

.modal .btn-danger:hover {
    background: #dc2626;
}
</style> 