@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-coffee me-2"></i>Kafe Sistemi - Masa Seçimi
                    <small class="text-muted ms-2" style="color: white!important;">
                        <i class="fas fa-sync-alt me-1"></i>
                        <span id="refresh-timer">10</span>s sonra yenilenecek
                    </small>
                </h5>
                <div>
                    @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                    <a href="{{ route('tables.index') }}" class="btn btn-outline-secondary btn-sm me-2" style="color: white">
                        <i class="fas fa-cog me-1"></i>Masa Yönetimi
                    </a>
                    <a href="{{ route('tables.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>Yeni Masa
                    </a>
                    @endif
                </div>
            </div>
            
            <!-- Ürün Arama Bölümü -->
            <div class="card-body border-bottom" style="background: #ffc107;">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="position-relative">
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white text-primary border-0">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" id="product-search" class="form-control border-0" placeholder="Ürün ara... (hangi masada sipariş edildiğini görmek için)" style="font-size: 1rem;">
                            </div>
                            <div id="search-results" class="position-absolute w-100 mt-2" style="display: none; z-index: 1050; top: 100%;">
                                <div class="card shadow-lg border-0">
                                    <div class="card-body p-0">
                                        <div id="search-results-content"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body">
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

                @if($tables->count() > 0)
                    <div class="row">
                        @foreach($tables as $table)
                            @php
                                $unpaidOrder = $table->cafeOrders->first();
                                $lastActivityAt = $unpaidOrder?->latestLog?->created_at ?? $unpaidOrder?->updated_at;
                                $idle = $lastActivityAt && $lastActivityAt->lt(now()->subHour());
                            @endphp
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="card table-card h-100 border-{{ $table->status_color }}">
                                    <div class="card-body text-center">
                                        <div class="table-icon mb-3">
                                            <i class="fas fa-table fa-3x text-{{ $table->status_color }}"></i>
                                        </div>
                                        <h5 class="card-title">{{ $table->name }}</h5>
                                        <p class="card-text">
                                            <small class="text-muted">Kapasite: {{ $table->capacity }} kişi</small>
                                        </p>
                                        <span class="badge bg-{{ $table->status_color }} mb-3">
                                            {{ $table->status_text }}
                                        </span>
                                        
                                        @if($table->status === 'cleaning')
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-secondary" disabled>
                                                    <i class="fas fa-broom me-1"></i>Temizleniyor
                                                </button>
                                                <!-- Masa Durumu Değiştirme Dropdown -->
                                                <div class="dropup">
                                                    <button class="btn btn-outline-primary btn-sm dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-exchange-alt me-1"></i>Durum Değiştir
                                                    </button>
                                                    <ul class="dropdown-menu w-100">
                                                        <li>
                                                            <form action="{{ route('tables.update-status', $table) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="status" value="available">
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="fas fa-check text-success me-2"></i>Müsait
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('tables.update-status', $table) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="status" value="reserved">
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="fas fa-bookmark text-warning me-2"></i>Rezerve
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        @else
                                            <div class="d-grid gap-2">
                                                @if($unpaidOrder)
                                                    <a href="{{ route('cafe.order.show', $unpaidOrder) }}" class="btn btn-primary">
                                                        <i class="fas fa-eye me-1"></i> Sipariş Detay
                                                    </a>
                                                @endif
                                                
                                                <a href="{{ route('cafe.table.select', $table) }}" class="btn btn-{{ $table->status === 'available' ? 'success' : ($idle ? 'danger' : 'warning') }}">
                                                    @if($table->status === 'available' || $table->status === 'cancelled')
                                                        <i class="fas fa-plus me-1"></i>Sipariş Al
                                                    @else
                                                        <i class="fas fa-plus me-1"></i>Ek Sipariş Al
                                                    @endif
                                                </a>
                                                @if($unpaidOrder && $idle)
                                                    <small class="text-danger">
                                                        Son işlem 1 saati geçti
                                                        <span class="badge bg-danger rounded-pill ms-1">
                                                            <i class="fas fa-hourglass-half me-1"></i>1 saat+
                                                        </span>
                                                    </small>
                                                @endif
                                                
                                                <!-- Masa Durumu Değiştirme Dropdown -->
                                                <div class="dropup">
                                                    <button class="btn btn-outline-primary btn-sm dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-exchange-alt me-1"></i>Durum Değiştir
                                                    </button>
                                                    <ul class="dropdown-menu w-100">
                                                        @if($table->status !== 'available')
                                                            <li>
                                                                <form action="{{ route('tables.update-status', $table) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="available">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-check text-success me-2"></i>Müsait
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                        @if($table->status !== 'occupied')
                                                            <li>
                                                                <form action="{{ route('tables.update-status', $table) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="occupied">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-users text-danger me-2"></i>Dolu
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                        @if($table->status !== 'reserved')
                                                            <li>
                                                                <form action="{{ route('tables.update-status', $table) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="reserved">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-bookmark text-warning me-2"></i>Rezerve
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                        @if($table->status !== 'cleaning')
                                                            <li>
                                                                <form action="{{ route('tables.update-status', $table) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="cleaning">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-broom text-info me-2"></i>Temizlik
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-table fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Henüz masa eklenmemiş</h5>
                        <p class="text-muted">Sipariş alabilmek için önce masa eklemeniz gerekiyor.</p>
                        <a href="{{ route('tables.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>İlk Masayı Ekle
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.table-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.table-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.table-icon {
    transition: transform 0.3s ease;
}

.table-card:hover .table-icon {
    transform: scale(1.1);
}

.dropdown-menu {
    min-width: 100%;
}

.dropdown-item {
    padding: 0.5rem 1rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

/* 1 saat+ durumunda Sipariş Detay için kırmızıya yakın gradient */
.btn-idle-danger {
    background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
    color: #fff !important;
    border: none;
}
.btn-idle-danger:hover,
.btn-idle-danger:focus {
    filter: brightness(0.95);
    color: #fff !important;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ürün arama fonksiyonu
    const productSearchInput = document.getElementById('product-search');
    const searchResults = document.getElementById('search-results');
    const searchResultsContent = document.getElementById('search-results-content');
    let searchTimeout;

    if (productSearchInput) {
        productSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(function() {
                fetch(`{{ route('cafe.search.product') }}?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            searchResultsContent.innerHTML = '<div class="p-3 text-center text-muted">Ürün bulunamadı</div>';
                            searchResults.style.display = 'block';
                            return;
                        }

                        let html = '<div class="list-group list-group-flush">';
                        data.forEach(product => {
                            if (product.tables.length > 0) {
                                // Her masa için ayrı satır oluştur
                                product.tables.forEach(table => {
                                    html += `
                                        <a href="{{ url('cafe/order') }}/${table.order_id}" class="list-group-item list-group-item-action" style="cursor: pointer; border-left: 4px solid var(--bs-${table.status_color});">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">${product.name}</h6>
                                                    <small class="text-muted">Fiyat: ${product.price} TL</small>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-${table.status_color}" style="font-size: 0.9rem;">
                                                        ${table.name} (${table.quantity} adet - ${table.status})
                                                    </span>
                                                </div>
                                            </div>
                                        </a>`;
                                });
                            } else {
                                // Sipariş edilmemiş ürün
                                html += `
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">${product.name}</h6>
                                                <small class="text-muted">Fiyat: ${product.price} TL</small>
                                            </div>
                                        </div>
                                        <div class="mt-2"><small class="text-warning"><i class="fas fa-info-circle me-1"></i>Şu anda hiçbir masada sipariş edilmemiş</small></div>
                                    </div>`;
                            }
                        });

                        searchResultsContent.innerHTML = html;
                        searchResults.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Arama hatası:', error);
                        searchResultsContent.innerHTML = '<div class="p-3 text-center text-danger">Arama sırasında bir hata oluştu</div>';
                        searchResults.style.display = 'block';
                    });
            }, 300);
        });

        // Arama kutusunun dışına tıklandığında sonuçları gizle
        document.addEventListener('click', function(e) {
            if (!productSearchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }
    // Tüm dropdown butonlarını seç
    const dropdownButtons = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    
    dropdownButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const dropdownMenu = this.nextElementSibling;
            
            if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu-custom')) {
                // Butonun pozisyonunu al
                const rect = this.getBoundingClientRect();
                
                // Dropdown menüyü konumlandır
                dropdownMenu.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                dropdownMenu.style.left = rect.left + 'px';
                dropdownMenu.style.width = rect.width + 'px';
            }
        });
    });
    
    // Dropdown dışına tıklandığında kapat
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu-custom.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
    
    // Otomatik yenileme - 10 saniye
    let countdown = 10;
    const timerElement = document.getElementById('refresh-timer');
    
    const timer = setInterval(function() {
        countdown--;
        timerElement.textContent = countdown;
        
        if (countdown <= 0) {
            // Sayfayı yenile
            window.location.reload();
        }
    }, 1000);
    
    // Kullanıcı sayfada bir işlem yaparsa timer'ı sıfırla
    document.addEventListener('click', function() {
        countdown = 10;
    });
    
    document.addEventListener('keypress', function() {
        countdown = 10;
    });
});
</script>
@endsection
