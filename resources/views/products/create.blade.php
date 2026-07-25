@extends('layouts.app')

@section('title', 'Yeni Ürün')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Yeni Ürün Ekle</h5>
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

                    <form action="{{ route('products.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Ürün Adı *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required>
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
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="purchase_price" class="form-label">Alış Fiyatı (₺) *</label>
                                    <input type="number" step="0.01" class="form-control @error('purchase_price') is-invalid @enderror" 
                                           id="purchase_price" name="purchase_price" value="{{ old('purchase_price') }}" min="0" required>
                                    @error('purchase_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Ürünü kaça aldığınız</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="sale_price" class="form-label">Satış Fiyatı (₺) *</label>
                                    <input type="number" step="0.01" class="form-control @error('sale_price') is-invalid @enderror" 
                                           id="sale_price" name="sale_price" value="{{ old('sale_price') }}" min="0" required>
                                    @error('sale_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Müşteriye sattığınız fiyat</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="stock" class="form-label">Başlangıç Stok *</label>
                                    <input type="number" class="form-control @error('stock') is-invalid @enderror" 
                                           id="stock" name="stock" value="{{ old('stock', 0) }}" min="0" required>
                                    @error('stock')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="min_stock" class="form-label">Minimum Stok *</label>
                                    <input type="number" class="form-control @error('min_stock') is-invalid @enderror" 
                                           id="min_stock" name="min_stock" value="{{ old('min_stock', 5) }}" min="0" required>
                                    @error('min_stock')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Kar Marjı Göstergesi -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info" id="profit-info" style="display: none;">
                                    <h6><i class="fas fa-calculator me-2"></i>Kar Hesaplaması</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <small>Birim Kar: <strong id="unit-profit">0.00 ₺</strong></small>
                                        </div>
                                        <div class="col-md-3">
                                            <small>Kar Marjı: <strong id="profit-margin">0%</strong></small>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Alış ve satış fiyatı girdiğinizde kar hesaplanır</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Aktif
                                </label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Kaydet
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
                profitInfo.className = 'alert alert-success';
            } else if (unitProfit < 0) {
                profitInfo.className = 'alert alert-danger';
            } else {
                profitInfo.className = 'alert alert-warning';
            }
            
            profitInfo.style.display = 'block';
        } else {
            profitInfo.style.display = 'none';
        }
    }

    purchasePriceInput.addEventListener('input', calculateProfit);
    salePriceInput.addEventListener('input', calculateProfit);
});
</script>
@endsection