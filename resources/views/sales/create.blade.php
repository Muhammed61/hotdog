@extends('layouts.app')

@section('title', 'Yeni Satış')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 mb">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Yeni Satış</h5>
                </div>
                <div class="card-body">
                    <form id="saleForm" action="{{ route('sales.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="product_search" class="form-label">Ürün Ara</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="product_search" placeholder="Ürün adı yazın..." autocomplete="off">
                                <div class="position-absolute top-50 end-0 translate-middle-y me-3">
                                    <i class="fas fa-search text-muted" id="search_icon"></i>
                                    <div class="spinner-border spinner-border-sm text-primary d-none" id="search_spinner" role="status">
                                        <span class="visually-hidden">Aranıyor...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="product_results" class="mb-3"></div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="sale_items">
                                <thead>
                                    <tr>
                                        <th>Ürün</th>
                                        <th>Fiyat</th>
                                        <th>Miktar</th>
                                        <th>Toplam</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Ürünler buraya eklenecek -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Genel Toplam</th>
                                        <th id="grand_total">0.00 ₺</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="submit_sale" disabled>
                                <i class="fas fa-save"></i> Satışı Tamamla
                            </button>
                            <a href="{{ route('sales.index') }}" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Hızlı Ürünler</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($products as $product)
                            <div class="col-md-3 col-sm-12">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 quick-add-product" 
                                        data-id="{{ $product->id }}" 
                                        data-name="{{ $product->name }}" 
                                        data-price="{{ $product->sale_price }}"
                                        data-stock="{{ $product->stock_quantity }}"
                                        title="{{ $product->name }} - {{ $product->sale_price }} ₺">
                                    <div class="text-truncate">{{ $product->name }}</div>
                                    <small class="badge bg-secondary">{{ $product->stock_quantity }}</small>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let saleItems = [];
    let itemCounter = 0;
    let searchTimeout;

    // Hızlı ürün arama - debounce ile
    document.getElementById('product_search').addEventListener('input', function() {
        const query = this.value.trim();
        
        // Önceki timeout'u temizle
        clearTimeout(searchTimeout);
        
        // Spinner göster, search icon gizle
        document.getElementById('search_icon').classList.add('d-none');
        document.getElementById('search_spinner').classList.remove('d-none');
        
        if (query.length > 1) {
            // 300ms gecikme ile arama yap
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        } else {
            // Arama sonuçlarını temizle
            document.getElementById('product_results').innerHTML = '';
            hideSearchSpinner();
        }
    });

    // Enter tuşu ile ilk sonucu seç
    document.getElementById('product_search').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const firstResult = document.querySelector('.search-result-item');
            if (firstResult) {
                firstResult.click();
            }
        }
    });

    function performSearch(query) {
        fetch(`/products/search?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                showProductResults(data);
                hideSearchSpinner();
            })
            .catch(error => {
                console.error('Arama hatası:', error);
                hideSearchSpinner();
                showError('Arama sırasında bir hata oluştu.');
            });
    }

    function hideSearchSpinner() {
        document.getElementById('search_spinner').classList.add('d-none');
        document.getElementById('search_icon').classList.remove('d-none');
    }

    function showError(message) {
        const resultsDiv = document.getElementById('product_results');
        resultsDiv.innerHTML = `<div class="alert alert-danger">${message}</div>`;
    }

    // Hızlı ürün ekleme
    document.querySelectorAll('.quick-add-product').forEach(button => {
        button.addEventListener('click', function() {
            const product = {
                id: this.dataset.id,
                name: this.dataset.name,
                price: parseFloat(this.dataset.price),
                stock: parseInt(this.dataset.stock)
            };
            addProductToSale(product);
        });
    });

    function showProductResults(products) {
        const resultsDiv = document.getElementById('product_results');
        resultsDiv.innerHTML = '';
        
        if (products.length === 0) {
            resultsDiv.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Ürün bulunamadı.</div>';
            return;
        }
        
        // Sonuçları container'a ekle
        const container = document.createElement('div');
        container.className = 'search-results-container border rounded p-2 bg-light';
        
        products.forEach((product, index) => {
            const productDiv = document.createElement('div');
            productDiv.className = 'search-result-item d-flex justify-content-between align-items-center p-2 mb-1 bg-white border rounded cursor-pointer';
            productDiv.style.cursor = 'pointer';
            
            // Hover efekti
            productDiv.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            productDiv.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'white';
            });
            
            productDiv.innerHTML = `
                <div>
                    <strong class="text-primary">${product.name}</strong>
                    <br>
                    <small class="text-muted">Fiyat: ${parseFloat(product.price).toFixed(2)} ₺</small>
                </div>
                <div class="text-end">
                    <span class="badge ${product.stock > 0 ? 'bg-success' : 'bg-danger'} fs-6">
                        ${product.stock} adet
                    </span>
                    ${index === 0 ? '<br><small class="text-muted">Enter ile seç</small>' : ''}
                </div>
            `;
            
            productDiv.addEventListener('click', () => addProductToSale({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                stock: parseInt(product.stock)
            }));
            
            container.appendChild(productDiv);
        });
        
        resultsDiv.appendChild(container);
    }

    function addProductToSale(product) {
        if (product.stock <= 0) {
            showAlert('Bu ürün stokta yok!', 'warning');
            return;
        }

        // Ürün zaten ekliyse miktarını artır
        const existingItem = saleItems.find(item => item.product_id == product.id);
        if (existingItem) {
            if (existingItem.quantity < product.stock) {
                existingItem.quantity++;
                updateSaleTable();
                showAlert(`${product.name} miktarı artırıldı!`, 'success');
            } else {
                showAlert('Stok yetersiz!', 'warning');
            }
            return;
        }

        // Yeni ürün ekle
        saleItems.push({
            id: itemCounter++,
            product_id: product.id,
            name: product.name,
            price: product.price,
            quantity: 1,
            stock: product.stock
        });

        updateSaleTable();
        document.getElementById('product_search').value = '';
        document.getElementById('product_results').innerHTML = '';
        showAlert(`${product.name} sepete eklendi!`, 'success');
    }

    function showAlert(message, type) {
        // Önceki alert'leri temizle
        const existingAlert = document.querySelector('.temp-alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show temp-alert`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Alert'i form'un üstüne ekle
        const form = document.getElementById('saleForm');
        form.insertBefore(alert, form.firstChild);
        
        // Sadece success alert'leri otomatik kapat
        if (type === 'success') {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 3000);
        }
    }

    function updateSaleTable() {
        const tbody = document.querySelector('#sale_items tbody');
        tbody.innerHTML = '';

        let grandTotal = 0;

        saleItems.forEach(item => {
            const total = item.price * item.quantity;
            grandTotal += total;

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <strong>${item.name}</strong>
                    <br><small class="text-muted">Stok: ${item.stock}</small>
                </td>
                <td>${item.price.toFixed(2)} ₺</td>
                <td>
                    <div class="input-group" style="width: 140px;">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="changeQuantity(${item.id}, -1)">-</button>
                        <input type="number" class="form-control form-control-sm text-center" value="${item.quantity}" min="1" max="${item.stock}" onchange="updateQuantity(${item.id}, this.value)">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="changeQuantity(${item.id}, 1)">+</button>
                    </div>
                </td>
                <td><strong>${total.toFixed(2)} ₺</strong></td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${item.id})" title="Kaldır">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });

        document.getElementById('grand_total').innerHTML = `<strong>${grandTotal.toFixed(2)} ₺</strong>`;
        document.getElementById('submit_sale').disabled = saleItems.length === 0;

        // Hidden inputları güncelle
        updateHiddenInputs();
    }

    function updateHiddenInputs() {
        // Önceki hidden inputları temizle
        document.querySelectorAll('input[name^="items"]').forEach(input => input.remove());

        // Yeni hidden inputları ekle
        const form = document.getElementById('saleForm');
        saleItems.forEach((item, index) => {
            ['product_id', 'quantity', 'price'].forEach(field => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${index}][${field}]`;
                input.value = item[field === 'product_id' ? 'product_id' : field];
                form.appendChild(input);
            });
        });

        // Ödeme yöntemi ekle
        if (!document.querySelector('input[name="payment_method"]')) {
            const paymentInput = document.createElement('input');
            paymentInput.type = 'hidden';
            paymentInput.name = 'payment_method';
            paymentInput.value = 'cash'; // Varsayılan nakit
            form.appendChild(paymentInput);
        }
    }

    // Global fonksiyonlar
    window.changeQuantity = function(itemId, change) {
        const item = saleItems.find(i => i.id === itemId);
        if (item) {
            const newQuantity = item.quantity + change;
            if (newQuantity >= 1 && newQuantity <= item.stock) {
                item.quantity = newQuantity;
                updateSaleTable();
            }
        }
    };

    window.updateQuantity = function(itemId, newQuantity) {
        const item = saleItems.find(i => i.id === itemId);
        if (item) {
            const quantity = parseInt(newQuantity);
            if (quantity >= 1 && quantity <= item.stock) {
                item.quantity = quantity;
                updateSaleTable();
            } else {
                // Geçersiz miktar girilirse eski değeri geri yükle
                updateSaleTable();
            }
        }
    };

    window.removeItem = function(itemId) {
        if (confirm('Bu ürünü sepetten kaldırmak istediğinizden emin misiniz?')) {
            saleItems = saleItems.filter(item => item.id !== itemId);
            updateSaleTable();
            showAlert('Ürün sepetten kaldırıldı!', 'info');
        }
    };

    // Form submit olayını dinle
    document.getElementById('saleForm').addEventListener('submit', function(e) {
        if (saleItems.length === 0) {
            e.preventDefault();
            showAlert('Lütfen en az bir ürün ekleyin!', 'warning');
            return false;
        }
        
        // Submit butonunu devre dışı bırak
        document.getElementById('submit_sale').disabled = true;
        document.getElementById('submit_sale').innerHTML = '<i class="fas fa-spinner fa-spin"></i> İşleniyor...';
    });

    // Sayfa dışına tıklandığında arama sonuçlarını gizle
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#product_search') && !e.target.closest('#product_results')) {
            document.getElementById('product_results').innerHTML = '';
        }
    });
});
</script>

<style>
.cursor-pointer {
    cursor: pointer;
}

.search-result-item:hover {
    background-color: #f8f9fa !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

.search-results-container {
    max-height: 300px;
    overflow-y: auto;
}

.temp-alert {
    position: relative;
    z-index: 1050;
}

.quick-add-product:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>
@endsection