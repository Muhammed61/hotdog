@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Kafe Siparişleri</h5>
                <div class="d-flex align-items-center">
                    <!-- Sayfalama Miktarı Seçici -->
                    <label for="per_page" class="form-label me-2 mb-0">Sayfa başına:</label>
                    <select id="per_page" class="form-select form-select-sm me-3" style="width: auto;" onchange="changePerPage(this.value)">
                        <option value="5" {{ request('per_page', 15) == 5 ? 'selected' : '' }}>5</option>
                        <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                        <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                        <option value="20" {{ request('per_page', 15) == 20 ? 'selected' : '' }}>20</option>
                        <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                    </select>
                    <a href="{{ route('cafe.index') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Yeni Sipariş
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Arama ve Filtreler -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <form method="GET" action="{{ route('cafe.orders') }}" class="row g-3">
                            <input type="hidden" name="per_page" value="{{ request('per_page', 15) }}">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Sipariş no, masa, garson ara..." 
                                           value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select name="table_id" class="form-select">
                                    <option value="">Tüm Masalar</option>
                                    @foreach($tables as $table)
                                        <option value="{{ $table->id }}" {{ request('table_id') == $table->id ? 'selected' : '' }}>
                                            {{ $table->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select">
                                    <option value="">Tüm Durumlar</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Bekliyor</option>
                                    <option value="preparing" {{ request('status') === 'preparing' ? 'selected' : '' }}>Hazırlanıyor</option>
                                    <option value="ready" {{ request('status') === 'ready' ? 'selected' : '' }}>Hazır</option>
                                    <option value="served" {{ request('status') === 'served' ? 'selected' : '' }}>Servis Edildi</option>
                                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>İptal</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="payment_status" class="form-select">
                                    <option value="">Tüm Ödemeler</option>
                                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Ödendi</option>
                                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Ödenmedi</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-filter"></i> Filtrele
                                    </button>
                                    <a href="{{ route('cafe.orders') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                @if($orders->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Sipariş No</th>
                                    <th>Tarih</th>
                                    <th>Masa</th>
                                    <th>Garson</th>
                                    <th>Durum</th>
                                    <th>Ödeme Durumu</th>
                                    <th>Tutar</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    <tr>
                                        <td>
                                            <strong>{{ $order->order_number }}</strong>
                                        </td>
                                        <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                                        <td>
                                            <i class="fas fa-table me-1"></i>{{ $order->table->name }}
                                            <small class="text-muted">({{ $order->table->capacity }} kişi)</small>
                                        </td>
                                        <td>{{ $order->user->name }}</td>
                                        <td>
                                            <span class="badge bg-{{ $order->status_color }}">
                                                {{ $order->status_text }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($order->is_paid)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Ödendi
                                                </span>
                                                <small class="d-block text-muted">{{ $order->payment_method_text }}</small>
                                            @else
                                                @if($order->status === 'served')
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>Ödeme Bekleniyor
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-minus me-1"></i>Ödenmedi
                                                    </span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ number_format($order->total_amount, 2) }} ₺</strong>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('cafe.order.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($order->status !== 'served' && $order->status !== 'cancelled')
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            @if($order->status === 'pending')
                                                                <li>
                                                                    <form action="{{ route('cafe.order.status', $order) }}" method="POST" class="d-inline">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <input type="hidden" name="status" value="preparing">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="fas fa-play me-2"></i>Hazırlanıyor
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            @endif
                                                            @if($order->status === 'preparing')
                                                                <li>
                                                                    <form action="{{ route('cafe.order.status', $order) }}" method="POST" class="d-inline">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <input type="hidden" name="status" value="ready">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="fas fa-check me-2"></i>Hazır
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            @endif
                                                            @if($order->status === 'ready')
                                                                <li>
                                                                    <form action="{{ route('cafe.order.status', $order) }}" method="POST" class="d-inline">
                                                                        @csrf
                                                                        @method('PATCH')
                                                                        <input type="hidden" name="status" value="served">
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="fas fa-utensils me-2"></i>Servis Et
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            @endif
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form action="{{ route('cafe.order.status', $order) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="status" value="cancelled">
                                                                    <button type="submit" class="dropdown-item text-danger" 
                                                                            onclick="return confirm('Bu siparişi iptal etmek istediğinizden emin misiniz?')">
                                                                        <i class="fas fa-times me-2"></i>İptal Et
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Sayfalama -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Toplam {{ $orders->total() }} sipariş
                        </div>
                        
                        <!-- Basit sayfalama butonları -->
                        <div class="btn-group" role="group">
                            @if($orders->currentPage() > 1)
                                <a href="{{ $orders->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-chevron-left"></i> Önceki
                                </a>
                            @endif
                            
                            <span class="btn btn-primary btn-sm">
                                Sayfa {{ $orders->currentPage() }} / {{ $orders->lastPage() }}
                            </span>
                            
                            @if($orders->hasMorePages())
                                <a href="{{ $orders->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                    Sonraki <i class="fas fa-chevron-right"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-coffee fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">
                            @if(request()->hasAny(['search', 'status', 'payment_status', 'table_id']))
                                Arama kriterlerinize uygun sipariş bulunamadı
                            @else
                                Henüz sipariş alınmamış
                            @endif
                        </h5>
                        <p class="text-muted">
                            @if(request()->hasAny(['search', 'status', 'payment_status', 'table_id']))
                                Filtreleri temizleyerek tekrar deneyebilirsiniz.
                            @else
                                İlk siparişi almak için "Yeni Sipariş" butonuna tıklayın.
                            @endif
                        </p>
                        <div class="d-flex gap-2 justify-content-center">
                            @if(request()->hasAny(['search', 'status', 'payment_status', 'table_id']))
                                <a href="{{ route('cafe.orders') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Filtreleri Temizle
                                </a>
                            @endif
                            <a href="{{ route('cafe.index') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Sipariş Al
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')

<script>
// Bekliyor durumundaki siparişleri kontrol eden sistem
let autoRefreshInterval;
let newOrderCheckInterval;
let audioContext = null;

console.log('🚀 Bekliyor durumu kontrol sistemi başlatılıyor...');

// Ses sistemini başlat (kullanıcı etkileşimi sonrası)
function initAudioContext() {
    try {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            console.log('🔊 Ses sistemi başlatıldı');
        }
        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }
    } catch (e) {
        console.log('❌ Ses sistemi başlatılamadı:', e);
    }
}

// localStorage'dan son kontrol edilen bekliyor sipariş sayısını al
function getLastPendingCount() {
    const stored = localStorage.getItem('lastPendingCount');
    if (!stored) {
        // İlk kez çalışıyorsa mevcut bekliyor sayısını kaydet
        const currentPending = countPendingOrders();
        localStorage.setItem('lastPendingCount', currentPending.toString());
        console.log('🆕 İlk bekliyor sayısı kaydedildi:', currentPending);
        return currentPending;
    }
    return parseInt(stored);
}

// Bekliyor sipariş sayısını localStorage'a kaydet
function savePendingCount(count) {
    localStorage.setItem('lastPendingCount', count.toString());
    console.log('💾 Bekliyor sipariş sayısı kaydedildi:', count);
}

// Sadece sipariş durumu "Bekliyor" olanları say (ödeme durumu değil!)
// Bekliyor siparişleri say ve ID'lerini al
function countPendingOrders(doc = document) {
    const badges = doc.querySelectorAll('tbody tr');
    let pendingCount = 0;
    let pendingOrderData = [];
    
    badges.forEach(row => {
        const statusBadge = row.querySelector('td:nth-child(5) .badge');
        if (statusBadge && statusBadge.textContent.trim() === 'Bekliyor') {
            pendingCount++;
            // Sipariş ID'sini al (detay linkinden)
            const detailLink = row.querySelector('a[href*="order"]');
            if (detailLink) {
                const href = detailLink.getAttribute('href');
                const orderId = href.split('/').pop();
                
                // Masa bilgisini al (3. sütun)
                const tableCell = row.querySelector('td:nth-child(3)');
                const tableName = tableCell ? tableCell.textContent.trim() : 'Bilinmeyen Masa';
                
                // Sipariş zamanını al (2. sütun)
                const timeCell = row.querySelector('td:nth-child(2)');
                const orderTime = timeCell ? timeCell.textContent.trim() : '';
                
                // Sipariş numarasını al (1. sütun)
                const orderNumberCell = row.querySelector('td:nth-child(1)');
                const orderNumber = orderNumberCell ? orderNumberCell.textContent.trim() : '';
                
                pendingOrderData.push({
                    id: orderId,
                    table: tableName,
                    time: orderTime,
                    orderNumber: orderNumber
                });
            }
        }
    });
    
    console.log('🔍 Bekliyor sipariş sayısı:', pendingCount);
    console.log('📋 Bekliyor sipariş verileri:', pendingOrderData);
    
    // Bekliyor sipariş verilerini localStorage'a kaydet
    if (pendingOrderData.length > 0) {
        localStorage.setItem('pendingOrderData', JSON.stringify(pendingOrderData));
        // Eski format için de kaydet (geriye uyumluluk)
        const orderIds = pendingOrderData.map(order => order.id);
        localStorage.setItem('pendingOrderIds', JSON.stringify(orderIds));
    }
    
    return pendingCount;
}

// Bekliyor durumundaki siparişleri kontrol et
function checkForPendingOrders() {
    console.log('🔍 Bekliyor durumu kontrol ediliyor...');
    
    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => response.text())
    .then(html => {
        console.log('📄 Sayfa verisi alındı, analiz ediliyor...');
        
        // HTML'i parse et
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Sipariş tablosunun tüm içeriğini al
        const orderTable = doc.querySelector('tbody');
        const currentTableContent = orderTable ? orderTable.innerHTML : '';
        
        // Önceki tablo içeriğini al
        const lastTableContent = localStorage.getItem('lastTableContent') || '';
        
        // Eski ve yeni bekliyor sipariş verilerini al
        const oldPendingData = JSON.parse(localStorage.getItem('pendingOrderData') || '[]');
        const oldPendingIds = oldPendingData.map(order => order.id);
        
        // Yeni bekliyor sipariş sayısını say ve verilerini al
        const newPendingOrders = countPendingOrders(doc);
        const newPendingData = JSON.parse(localStorage.getItem('pendingOrderData') || '[]');
        const newPendingIds = newPendingData.map(order => order.id);
        
        const lastPendingCount = getLastPendingCount();
        
        console.log('📊 Eski bekliyor sayısı:', lastPendingCount);
        console.log('📊 Yeni bekliyor sayısı:', newPendingOrders);
        console.log('📋 Eski bekliyor ID\'leri:', oldPendingIds);
        console.log('📋 Yeni bekliyor ID\'leri:', newPendingIds);
        console.log('🔍 Tablo içeriği değişti mi:', currentTableContent !== lastTableContent);
        
        // Eğer tablo içeriği değiştiyse VE bekliyor sipariş varsa
        if (currentTableContent !== lastTableContent && newPendingOrders > 0) {
            
            let newOrderInfo = null;
            
            // Yeni sipariş ID'sini bul
            const newOrderIds = newPendingIds.filter(id => !oldPendingIds.includes(id));
            
            if (newOrderIds.length > 0) {
                // Yeni sipariş var - onun bilgilerini bul
                newOrderInfo = newPendingData.find(order => newOrderIds.includes(order.id));
                console.log('🎉 YENİ SİPARİŞ BULUNDU:', newOrderInfo);
            } else if (newPendingOrders === lastPendingCount) {
                // Ek sipariş durumu - değişen satırı bul
                newOrderInfo = findChangedOrder(lastTableContent, currentTableContent, newPendingData);
                console.log('🎉 EK SİPARİŞ - DEĞİŞEN SİPARİŞ:', newOrderInfo);
            }
            
            // Modal göster
            if (newOrderInfo) {
                showNewOrderModalWithInfo(newOrderInfo, newPendingOrders > lastPendingCount ? (newPendingOrders - lastPendingCount) : 1);
            } else {
                showNewOrderModal(1);
            }
            
            // Yeni verileri kaydet
            savePendingCount(newPendingOrders);
            localStorage.setItem('lastTableContent', currentTableContent);
            
        } else if (newPendingOrders < lastPendingCount) {
            console.log('📉 Bekliyor sipariş sayısı azaldı, güncelleniyor');
            savePendingCount(newPendingOrders);
            localStorage.setItem('lastTableContent', currentTableContent);
        } else {
            console.log('ℹ️ Değişiklik yok');
        }
    })
    .catch(error => {
        console.error('❌ Kontrol hatası:', error);
        console.log('🔄 Hata nedeniyle 5 saniye sonra tekrar denenecek');
    });
}

// Değişen siparişi bul (ek sipariş durumu için)
function findChangedOrder(oldContent, newContent, pendingOrders) {
    try {
        // Eski ve yeni içerikleri satırlara böl
        const oldRows = oldContent.split('<tr>').filter(row => row.includes('Bekliyor'));
        const newRows = newContent.split('<tr>').filter(row => row.includes('Bekliyor'));
        
        console.log('🔍 Eski satır sayısı:', oldRows.length);
        console.log('🔍 Yeni satır sayısı:', newRows.length);
        
        // Her yeni satırı eski satırlarla karşılaştır
        for (let i = 0; i < newRows.length; i++) {
            const newRow = newRows[i];
            const oldRow = oldRows[i] || '';
            
            // Eğer satır değiştiyse, bu siparişin ID'sini bul
            if (newRow !== oldRow) {
                console.log('🔍 Değişen satır bulundu:', i);
                
                // Bu satırdaki sipariş ID'sini çıkar
                const orderIdMatch = newRow.match(/\/order\/(\d+)/);
                if (orderIdMatch) {
                    const changedOrderId = orderIdMatch[1];
                    console.log('🔍 Değişen sipariş ID:', changedOrderId);
                    
                    // Bu ID'ye sahip siparişi bul
                    const changedOrder = pendingOrders.find(order => order.id === changedOrderId);
                    if (changedOrder) {
                        console.log('✅ Değişen sipariş bulundu:', changedOrder);
                        return changedOrder;
                    }
                }
            }
        }
        
        // Eğer değişen satır bulunamazsa, en son siparişi döndür
        console.log('⚠️ Değişen satır bulunamadı, en son siparişi döndürüyorum');
        return pendingOrders[pendingOrders.length - 1];
        
    } catch (error) {
        console.error('❌ Değişen sipariş bulma hatası:', error);
        // Hata durumunda en son siparişi döndür
        return pendingOrders[pendingOrders.length - 1];
    }
}

// Yeni sipariş bilgisi ile modal göster
function showNewOrderModalWithInfo(orderInfo, newOrdersCount) {
    console.log('🔔 Modal açılıyor - Sipariş bilgisi:', orderInfo);
    
    // Eski modal varsa kaldır
    const existingModal = document.getElementById('newOrderModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Masa bilgisi için metin hazırla
    let tableInfo = '';
    if (orderInfo) {
        tableInfo = `
            <div class="alert alert-info fs-5 mb-4">
                <i class="fas fa-table fa-2x"></i><br>
                <strong>${orderInfo.table}</strong><br>
                <small class="text-muted">Sipariş No: ${orderInfo.orderNumber}</small><br>
                <small class="text-muted">Zaman: ${orderInfo.time}</small>
            </div>
        `;
    }
    
    // Büyük ve dikkat çekici modal
    const modalHtml = `
        <div class="modal fade show" id="newOrderModal" tabindex="-1" 
             style="display: block !important; background: rgba(0,0,0,0.9); z-index: 9999;">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-warning shadow-lg" style="border-width: 4px;">
                    <div class="modal-header bg-warning text-dark text-center">
                        <h2 class="modal-title w-100">
                            <i class="fas fa-bell fa-2x me-3" style="animation: shake 0.5s infinite;"></i>
                            YENİ BEKLİYOR SİPARİŞ!
                        </h2>
                    </div>
                    <div class="modal-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-hourglass-half fa-5x text-warning" style="animation: bounce 1s infinite;"></i>
                        </div>
                        <h1 class="text-warning mb-4 display-4">⏳ ${newOrdersCount} YENİ SİPARİŞ!</h1>
                        
                        ${tableInfo}
                        
                        <div class="alert alert-warning alert-lg fs-5">
                            <i class="fas fa-exclamation-triangle fa-2x"></i><br>
                            <strong>Yeni siparişler hazırlanmayı bekliyor!</strong>
                        </div>
                        <p class="text-muted fs-4">Hemen kontrol edin ve hazırlamaya başlayın</p>
                        
                        <!-- Ses test butonu -->
                        <button type="button" class="btn btn-info btn-sm mt-3" onclick="testSound()">
                            <i class="fas fa-volume-up"></i> Ses Test
                        </button>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-warning btn-lg me-3" onclick="refreshAndClose()">
                            <i class="fas fa-sync fa-lg"></i> YENİLE VE KAPAT
                        </button>
                        ${orderInfo ? `
                        <button type="button" class="btn btn-primary btn-lg" onclick="goToOrderDetail('${orderInfo.id}')">
                            <i class="fas fa-eye fa-lg"></i> SİPARİŞ DETAYI
                        </button>
                        ` : `
                        <button type="button" class="btn btn-secondary btn-lg" onclick="closeModal()">
                            <i class="fas fa-times fa-lg"></i> KAPAT
                        </button>
                        `}
                    </div>
                </div>
            </div>
        </div>
        <style>
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-30px); }
            60% { transform: translateY(-15px); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .alert-lg {
            padding: 2rem;
            border-radius: 1rem;
        }
        </style>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Ses çal
    playNotificationSound();
    
    console.log('✅ Modal başarıyla açıldı');
}

// Sipariş detayına git
function goToOrderDetail(orderId) {
    console.log('🔗 Sipariş detayına gidiliyor:', orderId);
    window.location.href = `/cafe/order/${orderId}`;
}

// Gelişmiş ses sistemi - birden fazla yöntem dene
function playNotificationSound() {
    console.log('🔊 Ses çalmaya çalışılıyor...');
    
    // Yöntem 1: Web Audio API
    playWebAudioSound();
    
    // Yöntem 2: HTML5 Audio (yedek)
    setTimeout(() => {
        playHTML5Audio();
    }, 100);
    
    // Yöntem 3: Sistem sesi (yedek)
    setTimeout(() => {
        playSystemSound();
    }, 200);
}

function playWebAudioSound() {
    try {
        if (!audioContext) {
            initAudioContext();
        }
        
        if (audioContext && audioContext.state === 'running') {
            // Üç kez beep sesi
            for (let i = 0; i < 3; i++) {
                setTimeout(() => {
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.value = 1000; // Yüksek ses
                    gainNode.gain.value = 0.5;
                    
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.3);
                    
                    console.log(`🔊 Web Audio beep ${i + 1} çalındı`);
                }, i * 400);
            }
        } else {
            console.log('❌ AudioContext çalışmıyor, durum:', audioContext?.state);
        }
    } catch (e) {
        console.log('❌ Web Audio hatası:', e);
    }
}

function playHTML5Audio() {
    try {
        // Data URL ile beep sesi oluştur
        const audioData = "data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT";
        const audio = new Audio(audioData);
        audio.volume = 0.5;
        
        for (let i = 0; i < 3; i++) {
            setTimeout(() => {
                const audioClone = audio.cloneNode();
                audioClone.play().then(() => {
                    console.log(`🔊 HTML5 Audio beep ${i + 1} çalındı`);
                }).catch(e => {
                    console.log(`❌ HTML5 Audio beep ${i + 1} hatası:`, e);
                });
            }, i * 400);
        }
    } catch (e) {
        console.log('❌ HTML5 Audio hatası:', e);
    }
}

function playSystemSound() {
    try {
        // Sistem beep sesi (eski tarayıcılar için)
        console.log('\x07'); // ASCII bell character
        console.log('🔊 Sistem sesi denendi');
    } catch (e) {
        console.log('❌ Sistem sesi hatası:', e);
    }
}

function testSound() {
    console.log('🧪 Ses test ediliyor...');
    initAudioContext();
    playNotificationSound();
}

function closeModal() {
    const modal = document.getElementById('newOrderModal');
    if (modal) {
        modal.remove();
        console.log('❌ Modal kapatıldı');
    }
}

function refreshAndClose() {
    console.log('🔄 Sayfa yenileniyor ve modal kapatılıyor');
    closeModal();
    window.location.reload();
}

function testModal() {
    console.log('🧪 Test modal açılıyor');
    initAudioContext(); // Ses sistemini başlat
    
    // Test için sahte sipariş bilgisi oluştur
    const testOrderInfo = {
        id: '999',
        tableInfo: 'Test Masa 1',
        orderNumber: 'TEST-001',
        orderTime: new Date().toLocaleString('tr-TR')
    };
    
    showNewOrderModalWithInfo(testOrderInfo, 1);
}

// Manuel bekliyor sayısını sıfırla (test için)
function resetPendingCount() {
    localStorage.removeItem('lastPendingCount');
    console.log('🔄 Bekliyor sayısı sıfırlandı');
    
    // Mevcut bekliyor sayısını tekrar kaydet
    const currentPending = countPendingOrders();
    savePendingCount(currentPending);
    console.log('📊 Yeni başlangıç bekliyor sayısı:', currentPending);
}

// Mevcut sayfadaki bekliyor sayısını kontrol et (debug için)
function checkCurrentPending() {
    const currentPending = countPendingOrders();
    const storedPending = getLastPendingCount();
    console.log('🔍 Mevcut sayfa bekliyor sayısı:', currentPending);
    console.log('💾 Kaydedilmiş bekliyor sayısı:', storedPending);
    
    // Detaylı analiz
    const rows = document.querySelectorAll('tbody tr');
    console.log('📋 Toplam sipariş satırı:', rows.length);
    
    rows.forEach((row, index) => {
        const statusCell = row.cells[4];
        if (statusCell) {
            const statusBadge = statusCell.querySelector('.badge');
            if (statusBadge) {
                console.log(`📋 Satır ${index + 1} durumu:`, statusBadge.textContent.trim());
            }
        }
    });
}

function startAutoRefresh() {
    console.log('🔄 Otomatik yenileme başlatıldı - Her 10 saniyede bir');
    
    autoRefreshInterval = setInterval(function() {
        // Modal açıksa yenileme yapma
        if (!document.getElementById('newOrderModal')) {
            console.log('📄 Sayfa yenileniyor...');
            // Sayfa yenilenmeden önce mevcut bekliyor sayısını kaydet
            const currentPending = countPendingOrders();
            savePendingCount(currentPending);
            window.location.reload();
        } else {
            console.log('⏸️ Modal açık - Yenileme ertelendi');
        }
    }, 15000); // 15 saniye
}

// Sayfa yüklendiğinde çalışacak
document.addEventListener('DOMContentLoaded', function() {
    console.log('📱 Sayfa yüklendi, sistem başlatılıyor...');
    
    // İlk tablo içeriğini kaydet
    const orderTable = document.querySelector('tbody');
    if (orderTable) {
        localStorage.setItem('lastTableContent', orderTable.innerHTML);
        console.log('💾 İlk tablo içeriği kaydedildi');
    }
    
    // Başlangıç bekliyor sayısını kaydet
    const initialPendingCount = countPendingOrders();
    savePendingCount(initialPendingCount);
    
    // Test butonları ekle
    const testButton = document.createElement('button');
    testButton.textContent = '🧪 Test Modal';
    testButton.className = 'btn btn-warning btn-sm ms-2';
    testButton.onclick = testModal;
    
    const resetButton = document.createElement('button');
    resetButton.textContent = '🔄 Reset Bekliyor';
    resetButton.className = 'btn btn-danger btn-sm ms-2';
    resetButton.onclick = resetPendingCount;
    
    // Debug butonu ekle
    const debugButton = document.createElement('button');
    debugButton.textContent = '🔍 Debug';
    debugButton.className = 'btn btn-info btn-sm ms-2';
    debugButton.onclick = checkCurrentPending;
    
    // Ses test butonu
    const soundButton = document.createElement('button');
    soundButton.textContent = '🔊 Ses Test';
    soundButton.className = 'btn btn-success btn-sm ms-2';
    soundButton.onclick = function() {
        initAudioContext();
        testSound();
    };
    
    // Kontrol durumu butonu - KALDIRILDI
    // const statusButton = document.createElement('button');
    // statusButton.textContent = '⏳ Bekliyor Kontrol: AÇIK';
    // statusButton.className = 'btn btn-success btn-sm ms-2';
    // statusButton.id = 'statusButton';
    
    const headerElement = document.querySelector('.card-header h5');
    if (headerElement) {
        headerElement.appendChild(testButton);
        headerElement.appendChild(resetButton);
        headerElement.appendChild(debugButton);
        headerElement.appendChild(soundButton);
        // headerElement.appendChild(statusButton); // KALDIRILDI
    }
    
    // Ses sistemini kullanıcı etkileşimi ile başlat
    document.addEventListener('click', initAudioContext, { once: true });
    document.addEventListener('keydown', initAudioContext, { once: true });
    
    // Otomatik yenileme başlat
    startAutoRefresh();
    
    // Bekliyor sipariş kontrolü başlat - Her 3 saniyede bir
    newOrderCheckInterval = setInterval(checkForPendingOrders, 3000);
    
    // İlk kontrolü 2 saniye sonra yap
    setTimeout(checkForPendingOrders, 2000);
    
    // Kontrol toggle - KALDIRILDI
    /*
    statusButton.addEventListener('click', function() {
        if (this.textContent.includes('AÇIK')) {
            clearInterval(newOrderCheckInterval);
            clearInterval(autoRefreshInterval);
            this.textContent = '⏳ Bekliyor Kontrol: KAPALI';
            this.className = 'btn btn-secondary btn-sm ms-2';
            console.log('⏹️ Bekliyor kontrolleri durduruldu');
        } else {
            newOrderCheckInterval = setInterval(checkForPendingOrders, 3000);
            startAutoRefresh();
            this.textContent = '⏳ Bekliyor Kontrol: AÇIK';
            this.className = 'btn btn-success btn-sm ms-2';
            console.log('▶️ Bekliyor kontrolleri yeniden başlatıldı');
        }
    });
    */
});

// Sayfa yenilenmeden önce mevcut bekliyor sayısını kaydet
window.addEventListener('beforeunload', function() {
    const currentPending = countPendingOrders();
    savePendingCount(currentPending);
    
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    if (newOrderCheckInterval) clearInterval(newOrderCheckInterval);
});
</script>
@endsection