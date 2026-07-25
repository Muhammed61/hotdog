@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calculator me-2"></i>Hesabı Böl - Sipariş #{{ $order->order_number }}
                    </h5>
                    <div style="text-align: right;">
                        <span style="text-align: right;" class="badge bg-info">{{ $order->table->name }}</span>
                        <span style="text-align: right;" class="badge bg-warning">{{ number_format($order->total_amount, 2) }} ₺</span>
                        @if($order->cafeOrderPayments->count() > 0)
                            <span class="badge bg-success">Ödenen: {{ number_format($order->cafeOrderPayments->sum('amount'), 2) }} ₺</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Sol Taraf - Sipariş Detayları -->
                        <div class="col-md-6">
                            <h6>Sipariş Detayları</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Ürün</th>
                                            <th>Toplam Adet</th>
                                            <th>Kalan Adet</th>
                                            <th>Birim Fiyat</th>
                                            <th>Kalan Tutar</th>
                                            <th>İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            // Ödenen ürünleri hesapla
                                            $paidItems = [];
                                            foreach($order->cafeOrderPayments as $payment) {
                                                if($payment->selected_items) {
                                                    foreach($payment->selected_items as $paidItem) {
                                                        $itemId = $paidItem['id'];
                                                        if(!isset($paidItems[$itemId])) {
                                                            $paidItems[$itemId] = 0;
                                                        }
                                                        $paidItems[$itemId] += $paidItem['quantity'];
                                                    }
                                                }
                                            }
                                        @endphp
                                        
                                        @php
                                            // Ürünleri (Ürün ID + Birim Fiyat) bazlı grupla
                                            $grouped = [];
                                            foreach($order->cafeOrderItems as $item) {
                                                $unitPrice = $item->total_price / $item->quantity;
                                                $key = $item->product->id . '|' . number_format($unitPrice, 2, '.', '');
                                                if(!isset($grouped[$key])) {
                                                    $grouped[$key] = [
                                                        'product_name' => $item->product->name,
                                                        'unit_price' => $unitPrice,
                                                        'total_quantity' => 0,
                                                        'remaining_quantity' => 0,
                                                        'item_ids' => [],
                                                        'item_remaining' => [],
                                                    ];
                                                }
                                                $paidQuantity = $paidItems[$item->id] ?? 0;
                                                $remainingQuantity = max($item->quantity - $paidQuantity, 0);
                                            
                                                $grouped[$key]['total_quantity'] += $item->quantity;
                                                $grouped[$key]['remaining_quantity'] += $remainingQuantity;
                                                $grouped[$key]['item_ids'][] = $item->id;
                                                $grouped[$key]['item_remaining'][$item->id] = $remainingQuantity;
                                            }
                                        @endphp
                                        
                                        @foreach($grouped as $groupKey => $group)
                                            @php
                                                $remainingPrice = $group['remaining_quantity'] * $group['unit_price'];
                                                $safeKey = str_replace(['|', '.', ' '], ['_', '_', '_'], $groupKey);
                                            @endphp
                                            <tr id="group-row-{{ $safeKey }}" class="{{ $group['remaining_quantity'] <= 0 ? 'table-success' : '' }}">
                                                <td>{{ $group['product_name'] }}</td>
                                                <td>{{ $group['total_quantity'] }}</td>
                                                <td>
                                                    <span class="badge {{ $group['remaining_quantity'] <= 0 ? 'bg-success' : 'bg-warning' }}">
                                                        {{ $group['remaining_quantity'] }}
                                                    </span>
                                                </td>
                                                <td>{{ number_format($group['unit_price'], 2) }} ₺</td>
                                                <td>{{ number_format($remainingPrice, 2) }} ₺</td>
                                                <td>
                                                    @if($group['remaining_quantity'] > 0)
                                                    <div class="d-flex align-items-center gap-1">
                                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="decreaseGroupQuantity(this)"
                                                            data-group-ids='@json($group['item_ids'])'
                                                            data-item-remaining='@json($group['item_remaining'])'
                                                            data-group-key="{{ $safeKey }}"
                                                            data-item-name="{{ $group['product_name'] }}"
                                                            data-unit-price="{{ $group['unit_price'] }}"
                                                            data-max-quantity="{{ $group['remaining_quantity'] }}">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <span class="badge bg-primary mx-1" id="qty-group-{{ $safeKey }}">0</span>
                                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="increaseGroupQuantity(this)"
                                                            data-group-ids='@json($group['item_ids'])'
                                                            data-item-remaining='@json($group['item_remaining'])'
                                                            data-group-key="{{ $safeKey }}"
                                                            data-item-name="{{ $group['product_name'] }}"
                                                            data-unit-price="{{ $group['unit_price'] }}"
                                                            data-max-quantity="{{ $group['remaining_quantity'] }}">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-primary btn-sm ms-1 select-item-btn" onclick="selectAllGroup(this)"
                                                            data-group-ids='@json($group['item_ids'])'
                                                            data-item-remaining='@json($group['item_remaining'])'
                                                            data-group-key="{{ $safeKey }}"
                                                            data-item-name="{{ $group['product_name'] }}"
                                                            data-unit-price="{{ $group['unit_price'] }}"
                                                            data-max-quantity="{{ $group['remaining_quantity'] }}">
                                                            Tümünü Seç
                                                        </button>
                                                    </div>
                                                    @else
                                                    <span class="badge bg-success">Ödendi</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach

                                        @if($order->cafeOrderExtras && $order->cafeOrderExtras->count() > 0)
                                            @foreach($order->cafeOrderExtras as $extra)
                                            @php
                                                $extraId = "extra_{$extra->id}";
                                                $paidQuantity = $paidItems[$extraId] ?? 0;
                                                $remainingQuantity = 1 - $paidQuantity;
                                                $remainingPrice = $remainingQuantity * $extra->amount;
                                            @endphp
                                            <tr id="extra-row-{{ $extra->id }}" class="{{ $remainingQuantity <= 0 ? 'table-success' : '' }}">
                                                <td>{{ $extra->description ?: 'Ekstra' }}</td>
                                                <td>1</td>
                                                <td>
                                                    <span class="badge {{ $remainingQuantity <= 0 ? 'bg-success' : 'bg-warning' }}">
                                                        {{ $remainingQuantity }}
                                                    </span>
                                                </td>
                                                <td>{{ number_format($extra->amount, 2) }} ₺</td>
                                                <td>{{ number_format($remainingPrice, 2) }} ₺</td>
                                                <td>
                                                    @if($remainingQuantity > 0)
                                                    <div class="d-flex align-items-center gap-1">
                                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="decreaseQuantity(this)" data-extra-id="{{ $extra->id }}" data-item-name="{{ $extra->description ?: 'Ekstra' }}" data-unit-price="{{ $extra->amount }}" data-max-quantity="1">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <span class="badge bg-primary mx-1" id="qty-extra-{{ $extra->id }}">0</span>
                                                        <button type="button" class="btn btn-outline-success btn-sm" onclick="increaseQuantity(this)" data-extra-id="{{ $extra->id }}" data-item-name="{{ $extra->description ?: 'Ekstra' }}" data-unit-price="{{ $extra->amount }}" data-max-quantity="1">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-primary btn-sm ms-1" onclick="selectAll(this)" data-extra-id="{{ $extra->id }}" data-item-name="{{ $extra->description ?: 'Ekstra' }}" data-unit-price="{{ $extra->amount }}" data-max-quantity="1">
                                                            Tümünü Seç
                                                        </button>
                                                    </div>
                                                    @else
                                                    <span class="badge bg-success">Ödendi</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-warning">
                                            <th colspan="4">Kalan Toplam</th>
                                            <th>{{ number_format($order->remaining_amount, 2) }} ₺</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Sipariş Detayları tablosu kapanışı -->
                            
                            
                            @if($order->cafeOrderPayments->count() > 0)
                            <h6 class="mt-3">Önceki Ödemeler</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Açıklama</th>
                                            <th>Yöntem</th>
                                            <th>Tutar</th>
                                            <th>Ödenen Ürünler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->cafeOrderPayments as $payment)
                                        <tr>
                                            <td>{{ $payment->description }}</td>
                                            <td>
                                                @if($payment->payment_method === 'cash')
                                                    <span class="badge bg-success">Nakit</span>
                                                @else
                                                    <span class="badge bg-primary">Kart</span>
                                                @endif
                                            </td>
                                            <td>{{ number_format($payment->amount, 2) }} ₺</td>
                                            <td>
                                                @if($payment->selected_items && count($payment->selected_items) > 0)
                                                    <ul class="mb-0 ps-3">
                                                        @foreach($payment->selected_items as $paid)
                                                            <li>
                                                                {{ $paid['name'] ?? 'Ürün' }} × {{ $paid['quantity'] }}
                                                                @if(!empty($paid['isExtra']))
                                                                    <span class="badge bg-info ms-1">Ekstra</span>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>

                        <!-- Sağ Taraf - Hesap Bölme -->
                        <div class="col-md-6">
                            <h6>Hesap Bölme / Kısmi Ödeme</h6>
                            
                            <!-- Seçilen Ürünler -->
                            <div class="mb-3">
                                <label class="form-label">Seçilen Ürünler</label>
                                <div id="selected-items" class="border rounded p-2 bg-light" style="min-height: 100px;">
                                    <small class="text-muted">Ürün seçmek için sol taraftaki "Seç" butonlarını kullanın</small>
                                </div>
                                <div class="mt-2">
                                    <strong>Seçilen Toplam: <span id="selected-total">0.00</span> ₺</strong>
                                    <button type="button" class="btn btn-outline-danger btn-sm ms-2" onclick="clearSelectedItems()">
                                        <i class="fas fa-trash me-1"></i>Temizle
                                    </button>
                                </div>
                            </div>

                            <!-- Hızlı Ödeme Yöntemi Seçimi -->
                            <div class="mb-3">
                                <label class="form-label">Ödeme Yöntemi</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-success payment-method-btn active" data-method="cash" onclick="setPaymentMethod('cash')">
                                        <i class="fas fa-money-bill-wave me-1"></i>Nakit
                                    </button>
                                    <button type="button" class="btn btn-outline-primary payment-method-btn" data-method="card" onclick="setPaymentMethod('card')">
                                        <i class="fas fa-credit-card me-1"></i>Kart
                                    </button>
                                </div>
                            </div>

                            <!-- Seçilen Ürünler için Ödeme Butonu -->
                            <div class="mb-3">
                                <button type="button" class="btn btn-success w-100" id="pay-selected-btn" onclick="paySelectedItems()" disabled>
                                    <i class="fas fa-credit-card me-2"></i>Seçilen Ürünleri Öde (<span id="pay-selected-amount">0.00</span> ₺)
                                </button>
                            </div>

                            <hr>

                            <!-- Hızlı İşlemler -->
                            <div class="mb-3">
                                <label class="form-label">Hızlı İşlemler</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary" onclick="equalSplit(2)">2 Kişi</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="equalSplit(3)">3 Kişi</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="equalSplit(4)">4 Kişi</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="equalSplit(5)">5 Kişi</button>
                                    <button type="button" class="btn btn-outline-primary" onclick="equalSplit(6)">6 Kişi</button>
                                </div>
                            </div>

                            <!-- Hızlı Ödeme Yöntemi Seçimi -->
                            <div class="mb-3">
                                <label class="form-label">Hızlı Ödeme Yöntemi</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-success" onclick="setAllPaymentMethod('cash')">
                                        <i class="fas fa-money-bill-wave me-1"></i>Tümü Nakit
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="setAllPaymentMethod('card')">
                                        <i class="fas fa-credit-card me-1"></i>Tümü Kart
                                    </button>
                                </div>
                            </div>

                            <!-- Kişi Ekleme -->
                            <div class="mb-3">
                                <button type="button" class="btn btn-success btn-sm" onclick="addPerson('cash')">
                                    <i class="fas fa-plus me-1"></i>Kişi Ekle
                                </button>
                            </div>

                            <!-- Ödeme Satırları -->
                            <div id="payment-rows" class="mb-3">
                                <!-- Kişiler buraya eklenecek -->
                            </div>

                            <!-- Toplam Kontrol -->
                            @php
                                $paidAmount = $order->cafeOrderPayments->sum('amount');
                                $remainingAmount = $order->total_amount - $paidAmount;
                            @endphp
                            <div class="alert alert-info">
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Toplam Tutar:</strong><br>
                                        <span class="h5">{{ number_format($order->total_amount, 2) }} ₺</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Şu An Ödenen:</strong><br>
                                        <span class="h5" id="current-paid">{{ number_format($paidAmount, 2) }} ₺</span>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-6">
                                        <strong>Yeni Ödeme:</strong><br>
                                        <span class="h5" id="new-payment">0.00 ₺</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Kalan:</strong><br>
                                        <span id="remaining-amount" class="h5 text-danger">{{ number_format($remainingAmount, 2) }} ₺</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Ödeme Butonları -->
                            <form id="split-payment-form" action="{{ route('cafe.order.process-partial-payment', $order) }}" method="POST">
                                @csrf
                                <input type="hidden" id="payment-data" name="payment_data" value="">
                                
                                <button type="submit" class="btn btn-success w-100 mb-2" id="complete-payment" disabled>
                                    <i class="fas fa-check me-2"></i>Kısmi Ödeme Al
                                </button>
                            </form>

                            <!-- Tam Ödeme Butonu (sadece kalan tutar varsa) -->
                            @if($remainingAmount > 0)
                            <button type="button" class="btn btn-warning w-100 mb-2" onclick="payRemaining()">
                                <i class="fas fa-money-bill-wave me-2"></i>Kalan Tutarı Öde ({{ number_format($remainingAmount, 2) }} ₺)
                            </button>
                            @endif

                            <!-- Hesabı Kapat Butonu (kısmi ödeme yapılmışsa) -->
                            @if($paidAmount > 0)
                            <form action="{{ route('cafe.order.close-order', $order) }}" method="POST" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Hesabı kapatmak istediğinizden emin misiniz? Kalan {{ number_format($remainingAmount, 2) }} ₺ tahsil edilmeyecek.')">
                                    <i class="fas fa-times-circle me-2"></i>Hesabı Kapat (Kalan: {{ number_format($remainingAmount, 2) }} ₺)
                                </button>
                            </form>
                            @endif

                            <div class="mt-3">
                                <a href="{{ route('cafe.order.show', $order) }}" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-arrow-left me-2"></i>Geri Dön
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let personCount = 0;
let selectedItems = [];
let currentPaymentMethod = 'cash';
const totalAmount = {{ $order->total_amount }};
const paidAmount = {{ $paidAmount }};
const remainingAmount = {{ $remainingAmount }};

// Ürün seçme fonksiyonu
function selectItem(button) {
    const itemId = button.dataset.itemId;
    const extraId = button.dataset.extraId;
    const itemName = button.dataset.itemName;
    const itemPrice = parseFloat(button.dataset.itemPrice);
    const itemQuantity = button.dataset.itemQuantity;
    
    const id = itemId || ('extra_' + extraId);
    
    // Zaten seçilmiş mi kontrol et
    const existingIndex = selectedItems.findIndex(item => item.id === id);
    
    if (existingIndex === -1) {
        // Yeni ürün ekle
        selectedItems.push({
            id: id,
            name: itemName,
            price: itemPrice,
            quantity: itemQuantity,
            isExtra: !!extraId
        });
        
        // Butonu güncelle
        button.innerHTML = '<i class="fas fa-check me-1"></i>Seçildi';
        button.className = 'btn btn-success btn-sm select-item-btn';
        button.disabled = false;
    } else {
        // Ürünü kaldır
        selectedItems.splice(existingIndex, 1);
        
        // Butonu güncelle
        button.innerHTML = '<i class="fas fa-plus me-1"></i>Seç';
        button.className = 'btn btn-outline-primary btn-sm select-item-btn';
        button.disabled = false;
    }
    
    updateSelectedItemsDisplay();
}

// Seçilen ürünleri göster
function updateSelectedItemsDisplay() {
    const container = document.getElementById('selected-items');
    const totalElement = document.getElementById('selected-total');
    const payButton = document.getElementById('pay-selected-btn');
    const payAmountElement = document.getElementById('pay-selected-amount');
    
    if (selectedItems.length === 0) {
        container.innerHTML = '<small class="text-muted">Ürün seçmek için sol taraftaki "Seç" butonlarını kullanın</small>';
        totalElement.textContent = '0.00';
        payButton.disabled = true;
        payAmountElement.textContent = '0.00';
        return;
    }
    
    let html = '';
    let total = 0;
    
    selectedItems.forEach((item, index) => {
        total += item.price;
        html += `
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span>${item.quantity}x ${item.name}</span>
                <div>
                    <span class="me-2">${item.price.toFixed(2)} ₺</span>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSelectedItem('${item.id}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    totalElement.textContent = total.toFixed(2);
    payAmountElement.textContent = total.toFixed(2);
    payButton.disabled = total <= 0;
}

// Seçilen ürünü kaldır
function removeSelectedItem(itemId) {
    const index = selectedItems.findIndex(item => item.id === itemId);
    if (index !== -1) {
        selectedItems.splice(index, 1);
        
        // Butonu güncelle - BURADA SORUN VAR!
        let button;
        if (itemId.startsWith('extra_')) {
            // Extra ürün için
            const extraId = itemId.replace('extra_', '');
            button = document.querySelector(`[data-extra-id="${extraId}"].select-item-btn`);
        } else {
            // Normal ürün için
            button = document.querySelector(`[data-item-id="${itemId}"].select-item-btn`);
        }
        
        if (button) {
            button.innerHTML = '<i class="fas fa-plus me-1"></i>Seç';
            button.className = 'btn btn-outline-primary btn-sm select-item-btn';
            button.disabled = false;
        }
        
        updateSelectedItemsDisplay();
    }
}

// Seçilen ürünleri temizle
function clearSelectedItems() {
    selectedItems = [];
    
    // Tüm butonları doğru metin ve class ile sıfırla
    document.querySelectorAll('.select-item-btn').forEach(button => {
        // Grup veya item/extra butonu ise varsayılan "Tümünü Seç" haline getir
        if (button.dataset.groupKey || button.dataset.itemId || button.dataset.extraId) {
            button.innerHTML = 'Tümünü Seç';
            button.className = 'btn btn-primary btn-sm ms-1 select-item-btn';
            button.disabled = false;
        } else {
            // Diğer olası "Seç" butonları için eski fallback
            button.innerHTML = '<i class="fas fa-plus me-1"></i>Seç';
            button.className = 'btn btn-outline-primary btn-sm select-item-btn';
            button.disabled = false;
        }
    });
    
    // Tüm quantity'leri sıfırla
    document.querySelectorAll('[id^="qty-"]').forEach(qtyElement => {
        qtyElement.textContent = '0';
    });
    
    updateSelectedItemsDisplay();
}

// Ödeme yöntemi seç
function setPaymentMethod(method) {
    currentPaymentMethod = method;
    
    document.querySelectorAll('.payment-method-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.querySelector(`[data-method="${method}"]`).classList.add('active');
}

// Seçilen ürünleri öde
function paySelectedItems() {
    if (selectedItems.length === 0) {
        alert('Lütfen ödeme yapılacak ürünleri seçin!');
        return;
    }

    const paymentMethod = document.querySelector('.payment-method-btn.active').dataset.method;
    const totalAmount = selectedItems.reduce((sum, item) => sum + item.price, 0);
    
    const paymentData = [{
        amount: totalAmount,
        payment_method: paymentMethod,
        description: 'Seçilen ürünler için ödeme'
    }];

    // Form oluştur ve gönder
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("cafe.order.process-partial-payment", $order) }}';
    
    // CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);
    
    // Payment data
    const paymentInput = document.createElement('input');
    paymentInput.type = 'hidden';
    paymentInput.name = 'payment_data';
    paymentInput.value = JSON.stringify(paymentData);
    form.appendChild(paymentInput);
    
    // Selected items data
    const selectedItemsInput = document.createElement('input');
    selectedItemsInput.type = 'hidden';
    selectedItemsInput.name = 'selected_items';
    selectedItemsInput.value = JSON.stringify(selectedItems);
    form.appendChild(selectedItemsInput);
    
    document.body.appendChild(form);
    form.submit();
}

function addPerson(paymentMethod = 'cash') {
    personCount++;
    const paymentRows = document.getElementById('payment-rows');
    
    const row = document.createElement('div');
    row.className = 'card mb-2';
    row.id = 'person-' + personCount;
    
    row.innerHTML = `
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-3">
                    <label class="form-label mb-0">Kişi ${personCount}</label>
                </div>
                <div class="col-3">
                    <input type="number" class="form-control form-control-sm amount-input" 
                           placeholder="Tutar" step="0.01" min="0" max="${remainingAmount}"
                           onchange="updateTotals()" oninput="updateTotals()">
                </div>
                <div class="col-4">
                    <select class="form-select form-select-sm payment-method" onchange="updateTotals()">
                        <option value="cash" ${paymentMethod === 'cash' ? 'selected' : ''}>Nakit</option>
                        <option value="card" ${paymentMethod === 'card' ? 'selected' : ''}>Kredi Kartı</option>
                    </select>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removePerson(${personCount})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    paymentRows.appendChild(row);
    updateRemoveButtons();
}

function removePerson(id) {
    const element = document.getElementById('person-' + id);
    if (element) {
        element.remove();
        updateTotals();
        updateRemoveButtons();
    }
}

function updateRemoveButtons() {
    const rows = document.querySelectorAll('#payment-rows .card');
    const removeButtons = document.querySelectorAll('#payment-rows .btn-danger');
    
    removeButtons.forEach(function(button) {
        button.style.display = rows.length > 1 ? 'block' : 'none';
    });
}

function equalSplit(numberOfPeople) {
    // Mevcut kişileri temizle
    document.getElementById('payment-rows').innerHTML = '';
    personCount = 0;
    
    // Eşit bölüm hesapla (kalan tutardan)
    const amountPerPerson = (remainingAmount / numberOfPeople).toFixed(2);
    
    // Kişileri ekle
    for (let i = 0; i < numberOfPeople; i++) {
        addPerson('cash');
        const lastInput = document.querySelector('#payment-rows .card:last-child .amount-input');
        lastInput.value = amountPerPerson;
    }
    
    updateTotals();
}

function payRemaining() {
    // Mevcut kişileri temizle
    document.getElementById('payment-rows').innerHTML = '';
    personCount = 0;
    
    // Kalan tutarı tek kişi olarak ekle
    addPerson('cash');
    const lastInput = document.querySelector('#payment-rows .card:last-child .amount-input');
    lastInput.value = remainingAmount.toFixed(2);
    
    updateTotals();
}

// Tüm ödeme yöntemlerini değiştir
function setAllPaymentMethod(method) {
    const paymentSelects = document.querySelectorAll('.payment-method');
    paymentSelects.forEach(function(select) {
        select.value = method;
    });
    updateTotals();
}

function updateTotals() {
    const amountInputs = document.querySelectorAll('.amount-input');
    let newPayment = 0;
    
    amountInputs.forEach(function(input) {
        const value = parseFloat(input.value) || 0;
        newPayment += value;
    });
    
    const newRemaining = remainingAmount - newPayment;
    
    document.getElementById('new-payment').textContent = newPayment.toFixed(2) + ' ₺';
    document.getElementById('remaining-amount').textContent = newRemaining.toFixed(2) + ' ₺';
    
    // Renk kontrolü
    const remainingElement = document.getElementById('remaining-amount');
    const completeButton = document.getElementById('complete-payment');
    
    if (newPayment > 0 && newPayment <= remainingAmount) {
        remainingElement.className = newRemaining <= 0 ? 'h5 text-success' : 'h5 text-warning';
        completeButton.disabled = false;
        completeButton.innerHTML = newRemaining <= 0 ? 
            '<i class="fas fa-check me-2"></i>Ödemeyi Tamamla' : 
            '<i class="fas fa-coins me-2"></i>Kısmi Ödeme Al';
    } else if (newPayment > remainingAmount) {
        remainingElement.className = 'h5 text-danger';
        completeButton.disabled = true;
    } else {
        remainingElement.className = 'h5 text-danger';
        completeButton.disabled = true;
    }
    
    // Ödeme verilerini hazırla
    updatePaymentData();
}

function updatePaymentData() {
    const payments = [];
    const rows = document.querySelectorAll('#payment-rows .card');
    
    rows.forEach(function(row, index) {
        const amount = parseFloat(row.querySelector('.amount-input').value) || 0;
        const method = row.querySelector('.payment-method').value;
        
        if (amount > 0) {
            payments.push({
                amount: amount,
                payment_method: method,
                description: 'Kişi ' + (index + 1)
            });
        }
    });
    
    document.getElementById('payment-data').value = JSON.stringify(payments);
}

// Form submit olduğunda kontrol et
document.getElementById('split-payment-form').addEventListener('submit', function(e) {
    const paymentData = document.getElementById('payment-data').value;
    
    try {
        const parsed = JSON.parse(paymentData);
        if (parsed.length === 0) {
            e.preventDefault();
            alert('En az bir ödeme girmelisiniz!');
        }
    } catch (error) {
        e.preventDefault();
        alert('Ödeme verilerinde hata var!');
    }
});

// Adet artırma
function increaseGroupQuantity(button) {
    const groupIds = JSON.parse(button.dataset.groupIds || '[]');
    const itemRemaining = JSON.parse(button.dataset.itemRemaining || '{}');
    const groupKey = button.dataset.groupKey;
    const unitPrice = parseFloat(button.dataset.unitPrice);
    const maxQuantity = parseInt(button.dataset.maxQuantity);

    const qtyElement = document.getElementById(`qty-group-${groupKey}`);
    let currentQty = parseInt(qtyElement.textContent);

    if (currentQty < maxQuantity) {
        currentQty++;
        qtyElement.textContent = currentQty;

        // +1'i gruptaki kalemlere kalan kotaya göre dağıt
        for (let i = 0; i < groupIds.length; i++) {
            const id = String(groupIds[i]);
            const existingIndex = selectedItems.findIndex(it => it.id === id);
            const currentSelected = existingIndex >= 0 ? selectedItems[existingIndex].quantity : 0;
            const remainingForItem = (itemRemaining[id] ?? 0) - currentSelected;

            if (remainingForItem > 0) {
                if (existingIndex >= 0) {
                    selectedItems[existingIndex].quantity = currentSelected + 1;
                    selectedItems[existingIndex].price = unitPrice * selectedItems[existingIndex].quantity;
                    selectedItems[existingIndex].name = button.dataset.itemName || selectedItems[existingIndex].name || '';
                } else {
                    selectedItems.push({
                        id: id,
                        name: button.dataset.itemName || '',
                        price: unitPrice,
                        quantity: 1,
                        isExtra: false
                    });
                }
                break;
            }
        }

        updateSelectedItemsDisplay();
    }
}

// Adet azaltma
function decreaseGroupQuantity(button) {
    const groupIds = JSON.parse(button.dataset.groupIds || '[]');
    const groupKey = button.dataset.groupKey;
    const unitPrice = parseFloat(button.dataset.unitPrice);

    const qtyElement = document.getElementById(`qty-group-${groupKey}`);
    let currentQty = parseInt(qtyElement.textContent);

    if (currentQty > 0) {
        currentQty--;
        qtyElement.textContent = currentQty;

        // -1'i gruptaki seçilmiş kalemlerden düş
        for (let i = 0; i < groupIds.length; i++) {
            const id = String(groupIds[i]);
            const existingIndex = selectedItems.findIndex(it => it.id === id);
            if (existingIndex >= 0 && selectedItems[existingIndex].quantity > 0) {
                const newQty = selectedItems[existingIndex].quantity - 1;
                if (newQty === 0) {
                    selectedItems.splice(existingIndex, 1);
                } else {
                    selectedItems[existingIndex].quantity = newQty;
                    selectedItems[existingIndex].price = unitPrice * newQty;
                }
                break;
            }
        }

        updateSelectedItemsDisplay();
    }
}

// Tümünü seç
function selectAllGroup(button) {
    const groupIds = JSON.parse(button.dataset.groupIds || '[]');
    const itemRemaining = JSON.parse(button.dataset.itemRemaining || '{}');
    const groupKey = button.dataset.groupKey;
    const unitPrice = parseFloat(button.dataset.unitPrice);
    const maxQuantity = parseInt(button.dataset.maxQuantity);

    const qtyElement = document.getElementById(`qty-group-${groupKey}`);
    qtyElement.textContent = maxQuantity;

    let toSelect = maxQuantity;

    // Gruptaki kalemlere maksimum seçimi dağıt
    for (let i = 0; i < groupIds.length && toSelect > 0; i++) {
        const id = String(groupIds[i]);
        const existingIndex = selectedItems.findIndex(it => it.id === id);
        const canSelect = Math.min((itemRemaining[id] ?? 0), toSelect);

        if (canSelect > 0) {
            if (existingIndex >= 0) {
                selectedItems[existingIndex].quantity = canSelect;
                selectedItems[existingIndex].price = unitPrice * canSelect;
                selectedItems[existingIndex].name = button.dataset.itemName || selectedItems[existingIndex].name || '';
            } else {
                selectedItems.push({
                    id: id,
                    name: button.dataset.itemName || '',
                    price: unitPrice * canSelect,
                    quantity: canSelect,
                    isExtra: false
                });
            }
            toSelect -= canSelect;
        } else if (existingIndex >= 0) {
            // Seçim kotası yoksa mevcut girdiyi temizle
            selectedItems.splice(existingIndex, 1);
        }
    }

    // Butonu "Seçildi" görünümüne al
    button.innerHTML = '<i class="fas fa-check me-1"></i>Seçildi';
    button.className = 'btn btn-success btn-sm select-item-btn';
    button.disabled = false;

    updateSelectedItemsDisplay();
}


// Sayfa yüklendiğinde 1 kişi ekle
document.addEventListener('DOMContentLoaded', function() {
    addPerson('cash');
});
</script>
@endsection
