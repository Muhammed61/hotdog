@extends('layouts.app')

@section('title', 'Depo Yönetimi')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Özet Kartları -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Toplam Ürün</h6>
                                <h3>{{ $totalProducts }}</h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-boxes fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Düşük Stok</h6>
                                <h3>{{ $lowStockProducts }}</h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Girilen Mal Sayısı</h6>
                                <h3>{{ number_format($totalInQuantity) }}</h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-arrow-down fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Çıkan Mal Sayısı</h6>
                                <h3>{{ number_format($totalOutQuantity) }}</h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-arrow-up fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>        

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Depo Yönetimi</h5>
                <div>
                    <a href="{{ route('warehouse.products.create') }}" class="btn btn-success btn-sm me-2">
                        <i class="fas fa-plus me-1"></i>Ürün Ekle
                    </a>
                    <a href="{{ route('warehouse.reports') }}" class="btn btn-danger btn-sm me-2">
                        <i class="fas fa-chart-bar me-1"></i>Raporlar
                    </a>
                    <a href="{{ route('warehouse.movements') }}" class="btn btn-secondary btn-sm me-2">
                        <i class="fas fa-history me-1"></i>Hareket Geçmişi
                    </a>
                    <a href="{{ route('warehouse.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-exchange-alt me-1"></i>Giriş/Çıkış Yap
                    </a>
                </div>
            </div>
            <div class="card-body" id="warehouse-list-card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Arama Alanı --}}
                <div class="row g-2 mb-3 align-items-center">
                    <div class="col-md-6">
                        <form id="warehouse-search-form" method="GET" action="{{ route('warehouse.index') }}">
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="warehouse-search"
                                    name="search"
                                    class="form-control"
                                    placeholder="Ürün adı ara... (örn: latte)"
                                    value="{{ request('search') }}"
                                >
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Ara
                                </button>
                                <a href="{{ route('warehouse.index') }}" id="warehouse-clear-search" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Temizle
                                </a>
                            </div>
                        </form>
                    </div>
                    <div class="col text-end">
                        <small id="warehouse-search-info" class="text-muted"></small>
                    </div>
                </div>

                <!-- Ürün Listesi -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Ürün Adı</th>
                                <th>Açıklama</th>
                                <th>Mevcut Stok</th>
                                <th>Min. Stok</th>
                                <th>Durum</th>
                                <th>Hızlı İşlem</th>
                            </tr>
                        </thead>
                        <tbody id="warehouse-products-tbody">
                            @forelse($products as $product)
                                <tr data-product-id="{{ $product->id }}">
                                    <td>
                                        <strong>{{ $product->name }}</strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $product->description ?? 'Açıklama yok' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $product->isLowStock() ? 'bg-danger' : 'bg-success' }} fs-6 current-stock">
                                            {{ $product->current_stock }} adet
                                        </span>
                                    </td>
                                    <td>{{ $product->min_stock_level }} adet</td>
                                    <td class="stock-status">
                                        @if($product->isLowStock())
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Düşük Stok
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Normal
                                            </span>
                                        @endif
                                    </td>
                                    <!-- Hızlı İşlem butonlarını değiştirelim -->
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#quickInModal"
                                                data-product-id="{{ $product->id }}" 
                                                data-product-name="{{ $product->name }}"
                                                data-current-stock="{{ $product->current_stock }}"
                                                onclick="setQuickInData(this)">
                                                <i class="fas fa-plus"></i> Giriş
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#quickOutModal"
                                                data-product-id="{{ $product->id }}" 
                                                data-product-name="{{ $product->name }}" 
                                                data-current-stock="{{ $product->current_stock }}"
                                                onclick="setQuickOutData(this)">
                                                <i class="fas fa-minus"></i> Çıkış
                                            </button>
                                            <form action="{{ route('warehouse.products.destroy', $product->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('{{ $product->name }} ürününü silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')" title="Sil">
                                                    <i class="fas fa-trash"></i> Sil
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Henüz ürün bulunmuyor.</p>
                                        <a href="{{ route('warehouse.products.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>İlk Ürünü Ekle
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Sayfalama (server-render, sağlam) -->
                @php
                    $prevUrl = $products->appends(['search' => request('search')])->previousPageUrl();
                    $nextUrl = $products->appends(['search' => request('search')])->nextPageUrl();
                @endphp

                @if($products->lastPage() > 1)
                <div class="d-flex justify-content-between align-items-center mt-4" id="warehouse-pagination">
                    <div class="text-muted">Toplam {{ $products->total() }} ürün</div>
                    <div class="btn-group" role="group">
                        @if($prevUrl)
                            <a href="{{ $prevUrl }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chevron-left"></i> Önceki
                            </a>
                        @endif
                
                        <span class="btn btn-primary btn-sm">
                            Sayfa {{ $products->currentPage() }} / {{ $products->lastPage() }}
                        </span>
                
                        @if($nextUrl)
                            <a href="{{ $nextUrl }}" class="btn btn-outline-primary btn-sm">
                                Sonraki <i class="fas fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Hızlı Giriş Modal -->
<div class="modal fade" id="quickInModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Hızlı Giriş
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickInForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong id="quickInProductName"></strong> ürünü için giriş işlemi yapıyorsunuz.
                    </div>
                    
                    <div class="mb-3">
                        <label for="quickInQuantity" class="form-label">Giriş Miktarı <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg" id="quickInQuantity" name="quantity" min="1" required placeholder="Miktar girin...">
                        <div class="form-text">Depoya giren mal miktarını girin.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quickInReason" class="form-label">Açıklama</label>
                        <input type="text" class="form-control" id="quickInReason" name="reason" placeholder="Örn: Tedarikçiden mal geldi" value="Hızlı giriş işlemi">
                    </div>
                    
                    <input type="hidden" id="quickInProductId" name="warehouse_product_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>İptal
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Giriş Yap
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hızlı Çıkış Modal -->
<div class="modal fade" id="quickOutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-minus me-2"></i>Hızlı Çıkış
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickOutForm">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong id="quickOutProductName"></strong> ürünü için çıkış işlemi yapıyorsunuz.
                        <br><small>Mevcut Stok: <span id="quickOutCurrentStock"></span> adet</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quickOutQuantity" class="form-label">Çıkış Miktarı <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg" id="quickOutQuantity" name="quantity" min="1" required placeholder="Miktar girin...">
                        <div class="form-text">Depodan çıkan mal miktarını girin.</div>
                        <div id="stockWarning" class="text-danger mt-1" style="display: none;">
                            <i class="fas fa-exclamation-triangle me-1"></i>Yetersiz stok!
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quickOutReason" class="form-label">Açıklama</label>
                        <input type="text" class="form-control" id="quickOutReason" name="reason" placeholder="Örn: Satış için çıkarıldı" value="Hızlı çıkış işlemi">
                    </div>
                    
                    <input type="hidden" id="quickOutProductId" name="warehouse_product_id">
                    <input type="hidden" id="quickOutMaxStock" name="max_stock">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>İptal
                    </button>
                    <button type="submit" class="btn btn-danger" id="quickOutSubmit">
                        <i class="fas fa-check me-1"></i>Çıkış Yap
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function setQuickInData(button) {
        const productId = button.dataset.productId;
        const productName = button.dataset.productName;

        document.getElementById('quickInProductId').value = productId;
        document.getElementById('quickInProductName').textContent = productName;
        document.getElementById('quickInQuantity').value = '';
        document.getElementById('quickInReason').value = 'Hızlı giriş işlemi';
    }

    function setQuickOutData(button) {
        const productId = button.dataset.productId;
        const productName = button.dataset.productName;
        const currentStock = parseInt(button.dataset.currentStock);

        document.getElementById('quickOutProductId').value = productId;
        document.getElementById('quickOutProductName').textContent = productName;
        document.getElementById('quickOutCurrentStock').textContent = currentStock;
        document.getElementById('quickOutMaxStock').value = currentStock;
        document.getElementById('quickOutQuantity').value = '';
        document.getElementById('quickOutQuantity').max = currentStock;
        document.getElementById('quickOutReason').value = 'Hızlı çıkış işlemi';
    }

    // Hızlı işlem butonları için global handler'lar (inline onclick çalışsın)
    window.setQuickInData = function(button) {
        const productId = button.dataset.productId;
        const productName = button.dataset.productName;

        document.getElementById('quickInProductId').value = productId;
        document.getElementById('quickInProductName').textContent = productName;
        document.getElementById('quickInQuantity').value = '';
        document.getElementById('quickInReason').value = 'Hızlı giriş işlemi';
    };

    window.setQuickOutData = function(button) {
        const productId = button.dataset.productId;
        const productName = button.dataset.productName;
        const currentStock = parseInt(button.dataset.currentStock);

        document.getElementById('quickOutProductId').value = productId;
        document.getElementById('quickOutProductName').textContent = productName;
        document.getElementById('quickOutCurrentStock').textContent = currentStock;
        document.getElementById('quickOutMaxStock').value = currentStock;
        document.getElementById('quickOutQuantity').value = '';
        document.getElementById('quickOutQuantity').max = currentStock;
        document.getElementById('quickOutReason').value = 'Hızlı çıkış işlemi';
    };

    // Çıkış miktarı kontrolü — nested DOMContentLoaded yerine input event'e bağla
    const quickOutQuantityEl = document.getElementById('quickOutQuantity');
    if (quickOutQuantityEl) {
        quickOutQuantityEl.addEventListener('input', function() {
            const quantity = parseInt(this.value) || 0;
            const maxStock = parseInt(document.getElementById('quickOutMaxStock').value) || 0;
            const warning = document.getElementById('stockWarning');
            const submitBtn = document.getElementById('quickOutSubmit');

            if (quantity > maxStock) {
                warning.style.display = 'block';
                submitBtn.disabled = true;
            } else {
                warning.style.display = 'none';
                submitBtn.disabled = false;
            }
        });
    }

    // Hızlı giriş form
    document.getElementById('quickInForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const productId = formData.get('warehouse_product_id');
        const quantity = formData.get('quantity');
        const reason = formData.get('reason');

        if (!productId) {
            alert('Ürün seçimi eksik. Lütfen listeden bir ürün için Giriş’e basın.');
            return;
        }

        quickEntry(productId, 'in', quantity, reason);
    });

    // Hızlı çıkış form
    document.getElementById('quickOutForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const productId = formData.get('warehouse_product_id');
        const quantity = formData.get('quantity');
        const reason = formData.get('reason');

        quickEntry(productId, 'out', quantity, reason);
    });
});

function quickEntry(productId, type, quantity, reason) {
    // Buton durumunu değiştir
    const submitBtn = document.querySelector(`#quick${type === 'in' ? 'In' : 'Out'}Form button[type="submit"]`);
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>İşleniyor...';
    
    fetch('{{ route("warehouse.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({
            warehouse_product_id: productId,
            type: type,
            quantity: Number(quantity),
            reason: reason
        })
    })
    .then(async res => {
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const message = (data && data.message) || 'İşlem başarısız';
            const errors = (data && data.errors) || null;
            throw { message, errors };
        }
        return data;
    })
    .then(data => {
        // Başarılı alert — doğru konteyner içine
        const listBodyEl = document.getElementById('warehouse-list-card-body');
        const beforeNode = listBodyEl.querySelector('.table-responsive');
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>${data.message || 'İşlem başarıyla tamamlandı'}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        listBodyEl.insertBefore(alertDiv, beforeNode);

        // Tablodaki stok bilgisini güncelle
        const idForUpdate = data.product_id ?? data.warehouse_product_id;
        if (idForUpdate && typeof data.new_stock !== 'undefined') {
            updateStockInTable(idForUpdate, data.new_stock);
        }

        // İşlem başarılıysa ilgili modalı kapat
        const modalEl = document.getElementById(type === 'in' ? 'quickInModal' : 'quickOutModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            bsModal.hide();
        }

        setTimeout(() => {
            if (alertDiv.parentNode) alertDiv.remove();
        }, 3000);
    })
    .catch(error => {
        console.error('Error:', error);

        const listBodyEl = document.getElementById('warehouse-list-card-body');
        const beforeNode = listBodyEl.querySelector('.table-responsive');
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        const details = error.errors
            ? '<br><small>' + Object.values(error.errors).flat().join('<br>') + '</small>'
            : '';
        alertDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>${error.message || 'Bir hata oluştu'}${details}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        listBodyEl.insertBefore(alertDiv, beforeNode);

        setTimeout(() => {
            if (alertDiv.parentNode) alertDiv.remove();
        }, 4000);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Tablodaki stok bilgisini güncelle
function updateStockInTable(productId, newStock) {
    console.log('Updating stock for product:', productId, 'New stock:', newStock);
    
    // Ürün satırını bul
    const productRow = document.querySelector(`tr[data-product-id="${productId}"]`);
    if (productRow) {
        // Mevcut stok sütununu bul ve güncelle
        const stockBadge = productRow.querySelector('.current-stock');
        if (stockBadge) {
            stockBadge.textContent = newStock + ' adet';
            
            // Min stok bilgisini al
            const minStockText = productRow.querySelector('td:nth-child(4)').textContent;
            const minStock = parseInt(minStockText.replace(' adet', ''));
            
            // Stok durumuna göre badge rengini güncelle
            if (newStock <= minStock) {
                stockBadge.className = 'badge bg-danger fs-6 current-stock';
            } else {
                stockBadge.className = 'badge bg-success fs-6 current-stock';
            }
            
            // Güncelleme animasyonu
            stockBadge.style.transform = 'scale(1.2)';
            stockBadge.style.transition = 'transform 0.3s';
            setTimeout(() => {
                stockBadge.style.transform = 'scale(1)';
            }, 300);
        }
        
        // Durum sütununu da güncelle
        const statusCell = productRow.querySelector('.stock-status');
        if (statusCell) {
            const minStockText = productRow.querySelector('td:nth-child(4)').textContent;
            const minStock = parseInt(minStockText.replace(' adet', ''));
            
            if (newStock <= minStock) {
                statusCell.innerHTML = `
                    <span class="badge bg-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>Düşük Stok
                    </span>
                `;
            } else {
                statusCell.innerHTML = `
                    <span class="badge bg-success">
                        <i class="fas fa-check me-1"></i>Normal
                    </span>
                `;
            }
        }
        
        // Butonlardaki data-current-stock değerini de güncelle
        const quickButtons = productRow.querySelectorAll('[data-current-stock]');
        quickButtons.forEach(btn => {
            btn.dataset.currentStock = newStock;
        });
    } else {
        console.error('Product row not found for ID:', productId);
    }
}

// Modal kapandığında formu temizle
document.getElementById('quickInModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('quickInForm').reset();
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
});

document.getElementById('quickOutModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('quickOutForm').reset();
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    if(document.getElementById('stockWarning')) {
        document.getElementById('stockWarning').style.display = 'none';
    }
});

// AŞAŞIDAKİ CLIENT-SIDE FİLTRELEME BLOĞUNU KALDIRIN:
// Depo Yönetimi Arama - Sipariş Al sayfasındaki kelime bazlı mantığın birebir aynısı
// function applyWarehouseFilters() { ... }
// searchInput.addEventListener('input', applyWarehouseFilters);
// clearBtn.addEventListener('click', ...);
// applyWarehouseFilters();

// YERİNE SADECE SUNUCU TARAFI ARAMAYI (GET form) TETİKLEYELİM:
// Arama ve temizleme — sadece GET ile (basit ve stabil)
document.addEventListener('DOMContentLoaded', function () {
    const searchForm = document.getElementById('warehouse-search-form');
    const searchInput = document.getElementById('warehouse-search');
    const clearBtn = document.getElementById('warehouse-clear-search');
    const infoEl = document.getElementById('warehouse-search-info');
    const tbodyEl = document.getElementById('warehouse-products-tbody');
    const listBodyEl = document.getElementById('warehouse-list-card-body');
    const csrfToken = '{{ csrf_token() }}';

    function renderRow(item) {
        const badgeClass = item.is_low_stock ? 'bg-danger' : 'bg-success';
        const statusHtml = item.is_low_stock
            ? '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Düşük Stok</span>'
            : '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Normal</span>';

        return `
        <tr data-product-id="${item.id}">
            <td><strong>${item.name}</strong></td>
            <td><small class="text-muted">${item.description ?? 'Açıklama yok'}</small></td>
            <td>
                <span class="badge ${badgeClass} fs-6 current-stock">${item.current_stock} adet</span>
            </td>
            <td>${item.min_stock_level} adet</td>
            <td class="stock-status">${statusHtml}</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-sm btn-success"
                        data-bs-toggle="modal"
                        data-bs-target="#quickInModal"
                        data-product-id="${item.id}"
                        data-product-name="${item.name}"
                        data-current-stock="${item.current_stock}"
                        onclick="setQuickInData(this)">
                        <i class="fas fa-plus"></i> Giriş
                    </button>
                    <button type="button" class="btn btn-sm btn-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#quickOutModal"
                        data-product-id="${item.id}"
                        data-product-name="${item.name}"
                        data-current-stock="${item.current_stock}"
                        onclick="setQuickOutData(this)">
                        <i class="fas fa-minus"></i> Çıkış
                    </button>
                    <form action="/warehouse/products/${item.id}" method="POST" class="d-inline">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('${item.name} ürününü silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')">
                            <i class="fas fa-trash"></i> Sil
                        </button>
                    </form>
                </div>
            </td>
        </tr>`;
    }

    function renderPagination(meta) {
        // last_page 1 veya altı ise pagination içeriğini temizle
        if (meta.last_page <= 1) {
            const container = document.getElementById('warehouse-pagination');
            if (container) container.innerHTML = '';
            return '';
        }

        const prevBtn = meta.prev_url
            ? `<a href="${meta.prev_url}" class="btn btn-outline-primary btn-sm"><i class="fas fa-chevron-left"></i> Önceki</a>`
            : '';
        const nextBtn = meta.next_url
            ? `<a href="${meta.next_url}" class="btn btn-outline-primary btn-sm">Sonraki <i class="fas fa-chevron-right"></i></a>`
            : '';

        return `
        <div class="d-flex justify-content-between align-items-center mt-4" id="warehouse-pagination">
            <div class="text-muted">Toplam ${meta.total} ürün</div>
            <div class="btn-group" role="group">
                ${prevBtn}
                <span class="btn btn-primary btn-sm">Sayfa ${meta.current_page} / ${meta.last_page}</span>
                ${nextBtn}
            </div>
        </div>`;
    }

    function ajaxLoad(url) {
        infoEl.textContent = 'Aranıyor...';
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) throw new Error('İstek başarısız');

            // Ürünleri yaz
            tbodyEl.innerHTML = data.items.map(renderRow).join('');

            // Sayfa hesapları ve linkler
            const searchVal = (searchInput?.value || '').trim();
            const searchParam = searchVal ? '&search=' + encodeURIComponent(searchVal) : '';
            const computedLast = Math.max(1, Math.ceil((data.total || 0) / (data.per_page || 20)));
            const lastPage = (data.last_page && data.last_page > 1) ? data.last_page : computedLast;
            const currentPage = data.current_page || 1;

            const baseUrl = searchForm.action;
            const prevUrl = data.prev_url || (currentPage > 1 ? `${baseUrl}?page=${currentPage - 1}${searchParam}` : null);
            const nextUrl = data.next_url || (currentPage < lastPage ? `${baseUrl}?page=${currentPage + 1}${searchParam}` : null);

            const paginationHtml = renderPagination({
                total: data.total,
                current_page: currentPage,
                last_page: lastPage,
                prev_url: prevUrl,
                next_url: nextUrl
            });

            const paginationEl = document.getElementById('warehouse-pagination');
            if (paginationEl) {
                // Mevcut konumda kalması için sadece içeriği değiştir
                paginationEl.outerHTML = paginationHtml;
            } else if (paginationHtml && listBodyEl) {
                const container = document.createElement('div');
                container.innerHTML = paginationHtml;
                listBodyEl.appendChild(container.firstElementChild); // YALNIZCA depo listesi kartına ekle
            }

            infoEl.textContent = `Toplam sonuç: ${data.total}`;
        })
        .catch(err => {
            console.error(err);
            infoEl.textContent = 'Hata oluştu';
        });
    }

    // Yazarken canlı arama (debounce 300 ms, her zaman page=1)
    let debounceTimer;
    if (searchForm && searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                const params = new URLSearchParams(new FormData(searchForm));
                params.set('page', '1');
                ajaxLoad(searchForm.action + '?' + params.toString());
            }, 300);
        });

        // Form submit'e gerek yok ama tıklayan olursa yine page=1
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const params = new URLSearchParams(new FormData(searchForm));
            params.set('page', '1');
            ajaxLoad(this.action + '?' + params.toString());
        });
    }

    // Temizle — aramayı sıfırla ve page=1
    if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (searchInput) searchInput.value = '';
            ajaxLoad((searchForm ? searchForm.action : this.href) + '?page=1');
        });
    }

    // Pagination linkleri AJAX ile çalışsın
    document.addEventListener('click', function (e) {
        const a = e.target.closest('#warehouse-pagination a');
        if (a) {
            e.preventDefault();
            ajaxLoad(a.href);
        }
    });
});
</script>
@endsection
