@extends('layouts.app')

@section('title', 'Depo Giriş/Çıkış')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Depo Giriş/Çıkış İşlemi</h5>
                <a href="{{ route('warehouse.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Geri Dön
                </a>
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

                <form action="{{ route('warehouse.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="warehouse_product_id" class="form-label">Ürün Seçin <span class="text-danger">*</span></label>
                                <select class="form-select @error('warehouse_product_id') is-invalid @enderror" id="warehouse_product_id" name="warehouse_product_id" required>
                                    <option value="">Ürün seçin...</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ old('warehouse_product_id') == $product->id ? 'selected' : '' }} data-stock="{{ $product->current_stock }}">
                                            {{ $product->name }} (Stok: {{ $product->current_stock }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('warehouse_product_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">İşlem Tipi <span class="text-danger">*</span></label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check @error('type') is-invalid @enderror" name="type" id="type_in" value="in" {{ old('type') == 'in' ? 'checked' : '' }} required>
                                    <label class="btn btn-outline-success" for="type_in">
                                        <i class="fas fa-plus me-1"></i>Giriş (Depoya Mal Geldi)
                                    </label>
                                    <input type="radio" class="btn-check @error('type') is-invalid @enderror" name="type" id="type_out" value="out" {{ old('type') == 'out' ? 'checked' : '' }} required>
                                    <label class="btn btn-outline-danger" for="type_out">
                                        <i class="fas fa-minus me-1"></i>Çıkış (Depodan Mal Çıktı)
                                    </label>
                                </div>
                                @error('type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Miktar <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" value="{{ old('quantity') }}" min="1" required>
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div id="stock-warning" class="form-text text-danger" style="display: none;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Yetersiz stok!
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reason" class="form-label">Açıklama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" value="{{ old('reason') }}" placeholder="Örn: Tedarikçiden mal geldi, Yukarı kata taşındı" required>
                                @error('reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Seçili Ürün Bilgisi -->
                    <div id="product-info" class="alert alert-info" style="display: none;">
                        <h6><i class="fas fa-info-circle me-2"></i>Seçili Ürün Bilgisi</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Ürün:</strong> <span id="selected-product-name">-</span>
                            </div>
                            <div class="col-md-4">
                                <strong>Mevcut Stok:</strong> <span id="selected-product-stock">-</span>
                            </div>
                            <div class="col-md-4">
                                <strong>İşlem Sonrası:</strong> <span id="calculated-stock">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('warehouse.index') }}" class="btn btn-secondary me-md-2">
                            <i class="fas fa-times me-1"></i>İptal
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-save me-1"></i>İşlemi Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('warehouse_product_id');
    const quantityInput = document.getElementById('quantity');
    const typeInputs = document.querySelectorAll('input[name="type"]');
    const productInfo = document.getElementById('product-info');
    const stockWarning = document.getElementById('stock-warning');
    const submitBtn = document.getElementById('submit-btn');

    function updateProductInfo() {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        if (selectedOption.value) {
            const productName = selectedOption.text.split(' (Stok:')[0];
            const currentStock = parseInt(selectedOption.dataset.stock);
            const quantity = parseInt(quantityInput.value) || 0;
            const type = document.querySelector('input[name="type"]:checked')?.value;

            document.getElementById('selected-product-name').textContent = productName;
            document.getElementById('selected-product-stock').textContent = currentStock + ' adet';

            let calculatedStock = currentStock;
            if (type === 'in') {
                calculatedStock = currentStock + quantity;
            } else if (type === 'out') {
                calculatedStock = currentStock - quantity;
            }

            document.getElementById('calculated-stock').textContent = calculatedStock + ' adet';
            
            // Stok uyarısı
            if (type === 'out' && quantity > currentStock) {
                stockWarning.style.display = 'block';
                submitBtn.disabled = true;
            } else {
                stockWarning.style.display = 'none';
                submitBtn.disabled = false;
            }

            productInfo.style.display = 'block';
        } else {
            productInfo.style.display = 'none';
            stockWarning.style.display = 'none';
            submitBtn.disabled = false;
        }
    }

    productSelect.addEventListener('change', updateProductInfo);
    quantityInput.addEventListener('input', updateProductInfo);
    typeInputs.forEach(input => {
        input.addEventListener('change', updateProductInfo);
    });

    // Sayfa yüklendiğinde kontrol et
    updateProductInfo();
});
</script>
@endsection