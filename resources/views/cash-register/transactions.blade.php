@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-{{ $cashType === 'stock' ? 'boxes' : 'coffee' }} me-2"></i>
                    {{ $cashType === 'stock' ? 'Stok Takip Kasası' : 'Kafe Sistemi Kasası' }} - Tüm İşlemler
                </h2>
                <div>
                    <a href="{{ route('cash-register.create', $cashType) }}" class="btn btn-primary me-2">
                        <i class="fas fa-plus me-1"></i>Yeni İşlem
                    </a>
                    <a href="{{ route('cash-register.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Geri Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarih ve Saat Filtresi -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Tarih ve Saat Filtresi</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('cash-register.transactions', $cashType) }}">
                <div class="row">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="{{ $startDate ?? now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label for="start_time" class="form-label">Başlangıç Saati</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" 
                               value="{{ $startTime ?? '00:00' }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="{{ $endDate ?? now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label for="end_time" class="form-label">Bitiş Saati</label>
                        <input type="time" class="form-control" id="end_time" name="end_time" 
                               value="{{ $endTime ?? '23:59' }}">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filtrele
                        </button>
                        <a href="{{ route('cash-register.transactions', $cashType) }}" class="btn btn-secondary">
                            <i class="fas fa-refresh me-1"></i>Temizle
                        </a>
                        <span class="text-muted ms-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Gösterilen veriler: {{ $startDate ?? now()->format('Y-m-d') }} {{ $startTime ?? '00:00' }} - {{ $endDate ?? now()->format('Y-m-d') }} {{ $endTime ?? '23:59' }}
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Kasa Özeti -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center border-{{ $cashType === 'stock' ? 'primary' : 'success' }}">
                <div class="card-body">
                    <h4 class="text-{{ $cashType === 'stock' ? 'primary' : 'success' }}">
                        {{ number_format($currentBalance, 2) }} ₺
                    </h4>
                    <p class="text-muted mb-0">Mevcut Bakiye</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-success">
                <div class="card-body">
                    <h5 class="text-success">{{ number_format($totalIncome, 2) }} ₺</h5>
                    <p class="text-muted mb-0">Toplam Gelir</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-danger">
                <div class="card-body">
                    <h5 class="text-danger">{{ number_format($totalExpense, 2) }} ₺</h5>
                    <p class="text-muted mb-0">Toplam Gider</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-warning">
                <div class="card-body">
                    <h5 class="text-warning">{{ number_format($totalWithdrawal, 2) }} ₺</h5>
                    <p class="text-muted mb-0">Toplam Para Çekme</p>
                </div>
            </div>
        </div>
    </div>

    <!-- İşlemler Tablosu -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list me-2"></i>İşlem Geçmişi</h6>
            
            <!-- Sayfalama Miktarı Seçici -->
            <div class="d-flex align-items-center">
                <label for="per_page" class="form-label me-2 mb-0">Sayfa başına:</label>
                <select id="per_page" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                    <option value="5" {{ request('per_page', 15) == 5 ? 'selected' : '' }}>5</option>
                    <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                    <option value="20" {{ request('per_page', 15) == 20 ? 'selected' : '' }}>20</option>
                    <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            @if($transactions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>İşlem Türü</th>
                                <th>Açıklama</th>
                                <th>Tutar</th>
                                <th>Kullanıcı</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                <tr>
                                    <td>
                                        <strong>{{ $transaction->created_at->format('d.m.Y') }}</strong><br>
                                        <small class="text-muted">{{ $transaction->created_at->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->transaction_type_color }}">
                                            <i class="{{ $transaction->transaction_type_icon }} me-1"></i>
                                            {{ $transaction->transaction_type_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $transaction->description }}</strong>
                                        @if($transaction->notes)
                                            <br><small class="text-muted">{{ $transaction->notes }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-{{ $transaction->transaction_type === 'income' ? 'success' : 'danger' }}">
                                            {{ $transaction->transaction_type === 'income' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} ₺
                                        </strong>
                                    </td>
                                    <td>
                                        <i class="fas fa-user me-1"></i>{{ $transaction->user->name }}
                                    </td>
                                    <td>
                                        <a href="{{ route('cash-register.show', $transaction) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Detay
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Gelişmiş Sayfalama -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Toplam {{ $transactions->total() }} işlem gösteriliyor
                        ({{ $transactions->firstItem() }}-{{ $transactions->lastItem() }})
                    </div>
                    
                    <!-- Sayfalama Butonları -->
                    <div class="btn-group" role="group">
                        @if($transactions->onFirstPage())
                            <span class="btn btn-outline-secondary btn-sm disabled">
                                <i class="fas fa-chevron-left"></i> Önceki
                            </span>
                        @else
                            <a href="{{ $transactions->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chevron-left"></i> Önceki
                            </a>
                        @endif
                        
                        <span class="btn btn-primary btn-sm">
                            Sayfa {{ $transactions->currentPage() }} / {{ $transactions->lastPage() }}
                        </span>
                        
                        @if($transactions->hasMorePages())
                            <a href="{{ $transactions->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                Sonraki <i class="fas fa-chevron-right"></i>
                            </a>
                        @else
                            <span class="btn btn-outline-secondary btn-sm disabled">
                                Sonraki <i class="fas fa-chevron-right"></i>
                            </span>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Henüz işlem bulunmuyor</h5>
                    <p class="text-muted">İlk işleminizi eklemek için yukarıdaki "Yeni İşlem" butonunu kullanın.</p>
                    <a href="{{ route('cash-register.create', $cashType) }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>İlk İşlemi Ekle
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function changePerPage(value) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page'); // Sayfa numarasını sıfırla
    window.location.href = url.toString();
}
</script>
@endsection