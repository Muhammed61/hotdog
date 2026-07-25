@extends('layouts.app')

@section('title', 'Ürün Düzenle')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ürün Düzenle</h5>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Geri Dön
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('products.update', $product) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Ürün Adı *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $product->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Kategori *</label>
                                    <select class="form-select @error('category_id') is-invalid @enderror" 
                                            id="category_id" name="category_id" required>
                                        <option value="">Kategori Seçin</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" 
                                                    {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="purchase_price" class="form-label">Alış Fiyatı (₺) *</label>
                                    <input type="number" step="0.01" class="form-control @error('purchase_price') is-invalid @enderror" 
                                           id="purchase_price" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" min="0" required>
                                    @error('purchase_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Ürünü kaça aldığınız</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="sale_price" class="form-label">Satış Fiyatı (₺) *</label>
                                    <input type="number" step="0.01" class="form-control @error('sale_price') is-invalid @enderror" 
                                           id="sale_price" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" min="0" required>
                                    @error('sale_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Müşteriye sattığınız fiyat</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="min_stock" class="form-label">Minimum Stok *</label>
                                    <input type="number" class="form-control @error('min_stock') is-invalid @enderror" 
                                           id="min_stock" name="min_stock" value="{{ old('min_stock', $product->min_stock_level) }}" min="0" required>
                                    @error('min_stock')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Kar Marjı Göstergesi -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info alert-dismissible-false" id="profit-info" style="position: relative;">
                                    <h6><i class="fas fa-calculator me-2"></i>Kar Hesaplaması</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <small>Birim Kar: <strong id="unit-profit">{{ number_format($product->sale_price - $product->purchase_price, 2) }} ₺</strong></small>
                                        </div>
                                        <div class="col-md-3">
                                            <small>Kar Marjı: <strong id="profit-margin">{{ $product->sale_price > 0 ? number_format((($product->sale_price - $product->purchase_price) / $product->sale_price) * 100, 2) : 0 }}%</strong></small>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Fiyat değiştikçe otomatik güncellenir</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Mevcut Stok: <strong>{{ $product->stock_quantity }}</strong></label>
                            <small class="text-muted d-block">Stok güncellemesi için ürün detay sayfasını kullanın.</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                       {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Aktif
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Güncelle
                            </button>
                            <a href="{{ route('products.index') }}" class="btn btn-secondary">İptal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const purchasePriceInput = document.getElementById('purchase_price');
    const salePriceInput = document.getElementById('sale_price');
    const profitInfo = document.getElementById('profit-info');
    const unitProfitSpan = document.getElementById('unit-profit');
    const profitMarginSpan = document.getElementById('profit-margin');

    // Alert'in otomatik kapanmasını engelle
    profitInfo.style.display = 'block';
    profitInfo.style.visibility = 'visible';
    profitInfo.style.opacity = '1';

    function calculateProfit() {
        const purchasePrice = parseFloat(purchasePriceInput.value) || 0;
        const salePrice = parseFloat(salePriceInput.value) || 0;

        if (purchasePrice > 0 && salePrice > 0) {
            const unitProfit = salePrice - purchasePrice;
            const profitMargin = (unitProfit / salePrice) * 100;

            unitProfitSpan.textContent = unitProfit.toFixed(2) + ' ₺';
            profitMarginSpan.textContent = profitMargin.toFixed(2) + '%';
            
            // Kar durumuna göre renk değiştir
            if (unitProfit > 0) {
                profitInfo.className = 'alert alert-success alert-dismissible-false';
            } else if (unitProfit < 0) {
                profitInfo.className = 'alert alert-danger alert-dismissible-false';
            } else {
                profitInfo.className = 'alert alert-warning alert-dismissible-false';
            }
            
            // Alert'in görünür kalmasını garanti et
            profitInfo.style.display = 'block';
            profitInfo.style.visibility = 'visible';
            profitInfo.style.opacity = '1';
        }
    }

    purchasePriceInput.addEventListener('input', calculateProfit);
    salePriceInput.addEventListener('input', calculateProfit);
    
    // Sayfa yüklendiğinde hesapla
    calculateProfit();
    
    // Bootstrap'in otomatik alert kapatmasını engelle
    profitInfo.addEventListener('close.bs.alert', function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
    
    // Herhangi bir fade/hide animasyonunu engelle
    setInterval(function() {
        if (profitInfo.style.display === 'none' || profitInfo.style.visibility === 'hidden') {
            profitInfo.style.display = 'block';
            profitInfo.style.visibility = 'visible';
            profitInfo.style.opacity = '1';
        }
    }, 1000);
});
</script>
@endsection