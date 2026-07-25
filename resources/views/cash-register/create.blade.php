@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus me-2"></i>
                        {{ $cashType === 'stock' ? 'Stok Takip Kasası' : 'Kafe Sistemi Kasası' }} - Yeni İşlem
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('cash-register.store', $cashType) }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="transaction_type" class="form-label">İşlem Türü</label>
                                    <select class="form-select @error('transaction_type') is-invalid @enderror" 
                                            id="transaction_type" name="transaction_type" required>
                                        <option value="">Seçiniz...</option>
                                        <option value="income" {{ old('transaction_type') === 'income' ? 'selected' : '' }}>
                                            <i class="fas fa-plus-circle"></i> Gelir
                                        </option>
                                        <option value="expense" {{ old('transaction_type') === 'expense' ? 'selected' : '' }}>
                                            <i class="fas fa-minus-circle"></i> Gider
                                        </option>
                                        <option value="withdrawal" {{ old('transaction_type') === 'withdrawal' ? 'selected' : '' }}>
                                            <i class="fas fa-hand-holding-usd"></i> Para Çekme
                                        </option>
                                    </select>
                                    @error('transaction_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Tutar (₺)</label>
                                    <input type="number" step="0.01" min="0.01" 
                                           class="form-control @error('amount') is-invalid @enderror" 
                                           id="amount" name="amount" value="{{ old('amount') }}" required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama</label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror" 
                                   id="description" name="description" value="{{ old('description') }}" 
                                   placeholder="İşlem açıklaması..." required>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notlar (Opsiyonel)</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3" 
                                      placeholder="Ek notlar, detaylar...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('cash-register.index') }}" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times me-1"></i>İptal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const transactionType = document.getElementById('transaction_type');
    const examples = {
        'stock': {
            'income': ['Ürün satışı', 'Stok geliri', 'Envanter satışı'],
            'expense': ['Ürün alımı', 'Depo kirası', 'Nakliye ücreti'],
            'withdrawal': ['Yeni ürün alımı için avans', 'Depo masrafları']
        },
        'cafe': {
            'income': ['Müşteri ödemesi', 'Kafe satışı', 'İçecek satışı'],
            'expense': ['Malzeme alımı', 'Elektrik faturası', 'Personel maaşı'],
            'withdrawal': ['Günlük iaşe alımı', 'Acil mutfak malzemesi']
        }
    };
    
    transactionType.addEventListener('change', function() {
        const cashType = '{{ $cashType }}';
        const type = this.value;
        const descriptionInput = document.getElementById('description');
        
        if (type && examples[cashType] && examples[cashType][type]) {
            const exampleTexts = examples[cashType][type];
            const randomExample = exampleTexts[Math.floor(Math.random() * exampleTexts.length)];
            descriptionInput.placeholder = `Örnek: ${randomExample}`;
        }
    });
});
</script>
@endsection