@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-utensils me-2"></i>Sipariş Al - {{ $table->name }}
                    <span class="badge bg-info ms-2">{{ $table->capacity }} Kişilik</span>
                </h5>
            </div>
            <div class="card-body">
                <!-- Kategori Seçimi -->
                <div class="mb-4">
                    <h6><i class="fas fa-tags me-2"></i>Kategoriler</h6>
                    <div class="btn-group flex-wrap" role="group">
                        <button type="button" class="btn btn-outline-primary category-btn active" data-category="all">
                            Tümü
                        </button>
                        @foreach($categories as $category)
                            <button type="button" class="btn btn-outline-primary category-btn" data-category="{{ $category->id }}">
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Ürün Arama -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="product-search" class="form-control" placeholder="Ürün ara... (ilk harfe göre)">
                        <button class="btn btn-outline-secondary" type="button" id="clear-search">Temizle</button>
                    </div>
                    <small class="text-muted">Yazmaya başladığında ürünler anında filtrelenir. Arama boşsa kategori filtresi geçerli olur.</small>
                </div>

                <!-- Ürün Listesi -->
                <div class="row" id="products-container">
                    @foreach($categories as $category)
                        @foreach($category->products as $product)
                            <div class="col-lg-4 col-md-6 mb-3 product-item" data-category="{{ $category->id }}">
                                <div class="card product-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ $product->name }}</h6>
                                        <p class="card-text">
                                            <small class="text-muted">{{ $category->name }}</small><br>
                                            <strong class="text-success">{{ number_format($product->sale_price, 2) }} ₺</strong>
                                        </p>
                                        <div class="d-grid">
                                            <button type="button" class="btn btn-primary btn-sm add-product" 
                                                    data-id="{{ $product->id }}" 
                                                    data-name="{{ $product->name }}" 
                                                    data-price="{{ $product->sale_price }}">
                                                <i class="fas fa-plus me-1"></i>Ekle
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card sticky-top">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Sipariş Özeti</h5>
            </div>
            <div class="card-body">
                <form id="order-form" action="{{ route('cafe.order.store', $table) }}" method="POST">
                    @csrf
                    
                    <div id="order-items" class="mb-3">
                        <div class="text-center text-muted py-4" id="empty-cart">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <p>Sipariş boş</p>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Toplam:</strong>
                            <strong id="total-amount">0.00 ₺</strong>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Not (Opsiyonel)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Sipariş notu..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success" id="submit-order" disabled>
                                <i class="fas fa-check me-1"></i>Siparişi Onayla
                            </button>
                            <a href="{{ route('cafe.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Geri Dön
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if(session('auto_print_script'))
<script>
    {!! session('auto_print_script') !!}
</script>
@php
    session()->forget('auto_print_script');
@endphp
@endif

<style>
.product-card {
    transition: transform 0.3s ease;
    cursor: pointer;
}

.product-card:hover {
    transform: translateY(-2px);
}

.category-btn.active {
    background-color: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

.order-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 10px;
    margin-bottom: 10px;
    background-color: #f8f9fa;
}

.sticky-top {
    top: 20px;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 5px;
}

.quantity-btn {
    width: 30px;
    height: 30px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let orderItems = [];
    let totalAmount = 0;

    // Kategori filtreleme
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
        });
    });

    // Arama kutusu - canlı filtre
    const searchInput = document.getElementById('product-search');
    const clearSearchBtn = document.getElementById('clear-search');
    const normalize = (str) => (str || '').toLocaleLowerCase('tr').trim();
    const tokenize = (str) => normalize(str).split(/\s+/).filter(Boolean);

    function applyFilters() {
        const categoryId = document.querySelector('.category-btn.active')?.dataset.category || 'all';
        const searchTermRaw = searchInput?.value || '';
        const searchTerms = tokenize(searchTermRaw); // birden fazla kelimeyi destekler
        const products = document.querySelectorAll('.product-item');

        products.forEach(product => {
            const nameEl = product.querySelector('.card-title');
            const productName = nameEl ? nameEl.textContent : '';
            const nameTokens = tokenize(productName);

            // Arama varsa kategori filtresini yok say; arama yoksa kategori filtresi geçerli
            const matchesCategory = (searchTerms.length === 0) ? (categoryId === 'all' || product.dataset.category === categoryId) : true;

            // Her arama kelimesi için, ürün adındaki herhangi bir kelime başlangıcıyla eşleşmeli
            const matchesSearch = (searchTerms.length === 0)
                ? true
                : searchTerms.every(term => nameTokens.some(token => token.startsWith(term)));

            product.style.display = (matchesCategory && matchesSearch) ? 'block' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            applyFilters();
        });
    }

    // Ürün ekleme
    document.querySelectorAll('.add-product').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.id;
            const productName = this.dataset.name;
            const productPrice = parseFloat(this.dataset.price);

            console.log('Ürün ekleniyor:', productName, productPrice);

            // Mevcut ürün var mı kontrol et
            const existingItemIndex = orderItems.findIndex(item => item.product_id === productId);

            if (existingItemIndex !== -1) {
                // Mevcut ürünün miktarını artır
                orderItems[existingItemIndex].quantity++;
                orderItems[existingItemIndex].total_price = orderItems[existingItemIndex].quantity * orderItems[existingItemIndex].unit_price;
                console.log('Mevcut ürün miktarı artırıldı:', orderItems[existingItemIndex]);
            } else {
                // Yeni ürün ekle
                const newItem = {
                    product_id: productId,
                    name: productName,
                    unit_price: productPrice,
                    quantity: 1,
                    total_price: productPrice
                };
                orderItems.push(newItem);
                console.log('Yeni ürün eklendi:', newItem);
            }

            updateOrderDisplay();
        });
    });

    function updateOrderDisplay() {
        console.log('Sipariş güncelleniyor:', orderItems);
        
        const container = document.getElementById('order-items');
        const emptyCart = document.getElementById('empty-cart');
        const submitBtn = document.getElementById('submit-order');

        if (orderItems.length === 0) {
            container.innerHTML = '<div class="text-center text-muted py-4" id="empty-cart"><i class="fas fa-shopping-cart fa-2x mb-2"></i><p>Sipariş boş</p></div>';
            submitBtn.disabled = true;
            totalAmount = 0;
        } else {
            let html = '';
            totalAmount = 0;

            orderItems.forEach((item, index) => {
                totalAmount += item.total_price;
                html += `
                    <div class="order-item" data-index="${index}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${item.name}</h6>
                                <small class="text-muted">${item.unit_price.toFixed(2)} ₺ / adet</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger ms-2 remove-item-btn" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="quantity-controls">
                                <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn decrease-btn" data-index="${index}">-</button>
                                <span class="mx-2 fw-bold">${item.quantity}</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn increase-btn" data-index="${index}">+</button>
                            </div>
                            <strong class="text-success">${item.total_price.toFixed(2)} ₺</strong>
                        </div>
                        <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                        <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                    </div>
                `;
            });

            container.innerHTML = html;
            submitBtn.disabled = false;

            // Event listener'ları yeniden ekle
            attachEventListeners();
        }

        document.getElementById('total-amount').textContent = totalAmount.toFixed(2) + ' ₺';
        console.log('Toplam tutar:', totalAmount);
    }

    function attachEventListeners() {
        // Miktar artırma butonları
        document.querySelectorAll('.increase-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                if (orderItems[index]) {
                    orderItems[index].quantity++;
                    orderItems[index].total_price = orderItems[index].quantity * orderItems[index].unit_price;
                    console.log('Miktar artırıldı:', orderItems[index]);
                    updateOrderDisplay();
                }
            });
        });

        // Miktar azaltma butonları
        document.querySelectorAll('.decrease-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                if (orderItems[index]) {
                    orderItems[index].quantity--;
                    if (orderItems[index].quantity <= 0) {
                        orderItems.splice(index, 1);
                        console.log('Ürün silindi (miktar 0)');
                    } else {
                        orderItems[index].total_price = orderItems[index].quantity * orderItems[index].unit_price;
                        console.log('Miktar azaltıldı:', orderItems[index]);
                    }
                    updateOrderDisplay();
                }
            });
        });

        // Ürün silme butonları
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                orderItems.splice(index, 1);
                console.log('Ürün silindi:', index);
                updateOrderDisplay();
            });
        });
    }

    // Form submit kontrolü
    document.getElementById('order-form').addEventListener('submit', function(e) {
        if (orderItems.length === 0) {
            e.preventDefault();
            alert('Lütfen en az bir ürün ekleyin!');
            return false;
        }
        console.log('Form gönderiliyor:', orderItems);
    });
});
</script>
</div>
@endsection