@extends('layouts.app')

@section('title', 'Kasa Raporları')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Kasa Raporları</h5>
                    <div>
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Geri Dön
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Debug Bilgileri (Geçici) olarak kaptıldı. Filtrelemede hata olursa açabilirisn-->
                    <!--<div class="alert alert-info mb-4">
                        <strong>Debug Bilgileri:</strong><br>
                        Başlangıç Tarihi: {{ $startDate->format('Y-m-d H:i:s') }}<br>
                        Bitiş Tarihi: {{ $endDate->format('Y-m-d H:i:s') }}<br>
                        Kasa Türü: {{ $cashType }}<br>
                        İşlem Türü: {{ $transactionType }}<br>
                        Toplam İşlem Sayısı: {{ $transactions->total() }}<br>
                        Request Parametreleri: {{ json_encode(request()->all()) }}
                    </div>-->

                    <!-- Filtreler -->
                    <form method="GET" action="{{ route('reports.cash') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-1">
                                <label for="start_time" class="form-label">Başlangıç Saati</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" 
                                       value="{{ request('start_time', $startTime ?? '00:00') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="end_date" class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-1">
                                <label for="end_time" class="form-label">Bitiş Saati</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" 
                                       value="{{ request('end_time', $endTime ?? '23:59') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="cash_type" class="form-label">Kasa Türü</label>
                                <select class="form-control" id="cash_type" name="cash_type">
                                    <option value="all" {{ request('cash_type', $cashType) === 'all' ? 'selected' : '' }}>Tümü</option>
                                    <option value="stock" {{ request('cash_type', $cashType) === 'stock' ? 'selected' : '' }}>Stok Kasası</option>
                                    <option value="cafe" {{ request('cash_type', $cashType) === 'cafe' ? 'selected' : '' }}>Kafe Kasası</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label for="transaction_type" class="form-label">İşlem Türü</label>
                                <select class="form-control" id="transaction_type" name="transaction_type">
                                    <option value="all" {{ request('transaction_type', $transactionType) === 'all' ? 'selected' : '' }}>Tümü</option>
                                    <option value="income" {{ request('transaction_type', $transactionType) === 'income' ? 'selected' : '' }}>Gelir</option>
                                    <option value="expense" {{ request('transaction_type', $transactionType) === 'expense' ? 'selected' : '' }}>Gider</option>
                                    <option value="withdrawal" {{ request('transaction_type', $transactionType) === 'withdrawal' ? 'selected' : '' }}>Para Çekme</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-filter"></i> Filtrele
                                </button>
                                <a href="{{ route('reports.cash') }}" class="btn btn-secondary me-2">
                                    <i class="fas fa-undo"></i> Sıfırla
                                </a>
                                <a href="{{ route('reports.cash', array_merge(request()->all(), ['export' => 'excel'])) }}" 
                                   class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Excel
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Mevcut Kasa Bakiyeleri -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Stok Kasası</h6>
                                            <h4>{{ number_format($currentBalances['stock_cash'], 0) }} ₺</h4>
                                        </div>
                                        <div>
                                            <i class="fas fa-boxes fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Kafe Kasası</h6>
                                            <h4>{{ number_format($currentBalances['cafe_cash'], 0) }} ₺</h4>
                                        </div>
                                        <div>
                                            <i class="fas fa-coffee fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Toplam Kasa</h6>
                                            <h4>{{ number_format($currentBalances['total_cash'], 0) }} ₺</h4>
                                        </div>
                                        <div>
                                            <i class="fas fa-cash-register fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Özet Bilgiler -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Stok Kasası Özeti</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Toplam Gelir</small>
                                            <div class="text-success fw-bold">{{ number_format($stockSummary['total_income'], 0) }} ₺</div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Toplam Gider</small>
                                            <div class="text-danger fw-bold">{{ number_format($stockSummary['total_expense'], 0) }} ₺</div>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <small class="text-muted">Para Çekme</small>
                                            <div class="text-warning fw-bold">{{ number_format($stockSummary['total_withdrawal'], 0) }} ₺</div>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <small class="text-muted">İşlem Sayısı</small>
                                            <div class="fw-bold">{{ $stockSummary['transaction_count'] }}</div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="text-center">
                                        <small class="text-muted">Net Bakiye</small>
                                        <div class="fw-bold {{ $stockSummary['net_balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($stockSummary['net_balance'], 0) }} ₺
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Kafe Kasası Özeti</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Toplam Gelir</small>
                                            <div class="text-success fw-bold">{{ number_format($cafeSummary['total_income'], 0) }} ₺</div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Toplam Gider</small>
                                            <div class="text-danger fw-bold">{{ number_format($cafeSummary['total_expense'], 0) }} ₺</div>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <small class="text-muted">Para Çekme</small>
                                            <div class="text-warning fw-bold">{{ number_format($cafeSummary['total_withdrawal'], 0) }} ₺</div>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <small class="text-muted">İşlem Sayısı</small>
                                            <div class="fw-bold">{{ $cafeSummary['transaction_count'] }}</div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="text-center">
                                        <small class="text-muted">Net Bakiye</small>
                                        <div class="fw-bold {{ $cafeSummary['net_balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($cafeSummary['net_balance'], 0) }} ₺
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Genel Özet</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Toplam Gelir</small>
                                            <div class="text-success fw-bold">{{ number_format($generalSummary['total_income'], 0) }} ₺</div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Toplam Gider</small>
                                            <div class="text-danger fw-bold">{{ number_format($generalSummary['total_expense'], 0) }} ₺</div>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <small class="text-muted">Para Çekme</small>
                                            <div class="text-warning fw-bold">{{ number_format($generalSummary['total_withdrawal'], 0) }} ₺</div>
                                        </div>
                                        <div class="col-6 mt-2">
                                            <small class="text-muted">İşlem Sayısı</small>
                                            <div class="fw-bold">{{ $generalSummary['transaction_count'] }}</div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="text-center">
                                        <small class="text-muted">Net Bakiye</small>
                                        <div class="fw-bold {{ $generalSummary['net_balance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($generalSummary['net_balance'], 0) }} ₺
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kullanıcı Bazında İşlem Özeti -->
                    @if($userSummary->count() > 0)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Kullanıcı Bazında İşlem Özeti</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Kullanıcı</th>
                                            <th>Kasa Türü</th>
                                            <th>İşlem Türü</th>
                                            <th>Toplam Tutar</th>
                                            <th>İşlem Sayısı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($userSummary as $user)
                                        <tr>
                                            <td>{{ $user->user->name ?? 'Bilinmiyor' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $user->cash_type === 'stock' ? 'primary' : 'success' }}">
                                                    {{ $user->cash_type === 'stock' ? 'Stok Kasası' : 'Kafe Kasası' }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $badgeClass = match($user->transaction_type) {
                                                        'income' => 'success',
                                                        'expense' => 'danger',
                                                        'withdrawal' => 'warning',
                                                        default => 'secondary'
                                                    };
                                                    $typeText = match($user->transaction_type) {
                                                        'income' => 'Gelir',
                                                        'expense' => 'Gider',
                                                        'withdrawal' => 'Para Çekme',
                                                        default => 'Bilinmiyor'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $badgeClass }}">{{ $typeText }}</span>
                                            </td>
                                            <td class="fw-bold">{{ number_format($user->total_amount, 0) }} ₺</td>
                                            <td>{{ $user->transaction_count }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Detaylı İşlem Listesi -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Detaylı İşlem Listesi</h6>
                            
                            <!-- Sayfalama Miktarı Seçici -->
                            <div class="d-flex align-items-center">
                                <label for="per_page" class="form-label me-2 mb-0">Sayfa başına:</label>
                                <select id="per_page" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                                    <option value="5" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5</option>
                                    <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                    <option value="15" {{ request('per_page', 10) == 15 ? 'selected' : '' }}>15</option>
                                    <option value="20" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20</option>
                                    <option value="25" {{ request('per_page', 10) == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
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
                                                <th>Kullanıcı</th>
                                                <th>Kasa Türü</th>
                                                <th>İşlem Türü</th>
                                                <th>Tutar</th>
                                                <th>Açıklama</th>
                                                <th>Notlar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($transactions as $transaction)
                                            <tr>
                                                <td>
                                                    <div>{{ $transaction->created_at->format('d.m.Y') }}</div>
                                                    <small class="text-muted">{{ $transaction->created_at->format('H:i') }}</small>
                                                </td>
                                                <td>{{ $transaction->user->name ?? 'Bilinmiyor' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $transaction->cash_type === 'stock' ? 'primary' : 'success' }}">
                                                        {{ $transaction->cash_type === 'stock' ? 'Stok Kasası' : 'Kafe Kasası' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $badgeClass = match($transaction->transaction_type) {
                                                            'income' => 'success',
                                                            'expense' => 'danger',
                                                            'withdrawal' => 'warning',
                                                            default => 'secondary'
                                                        };
                                                        $typeText = match($transaction->transaction_type) {
                                                            'income' => 'Gelir',
                                                            'expense' => 'Gider',
                                                            'withdrawal' => 'Para Çekme',
                                                            default => 'Bilinmiyor'
                                                        };
                                                    @endphp
                                                    <span class="badge bg-{{ $badgeClass }}">{{ $typeText }}</span>
                                                </td>
                                                <td class="fw-bold">{{ number_format($transaction->amount, 0) }} ₺</td>
                                                <td>{{ $transaction->description ?? '-' }}</td>
                                                <td>{{ $transaction->notes ?? '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Sayfalama -->
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div class="text-muted">
                                        Toplam {{ $transactions->total() }} işlem ({{ $transactions->firstItem() }}-{{ $transactions->lastItem() }} arası gösteriliyor)
                                    </div>
                                    
                                    <!-- Basit sayfalama butonları -->
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
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Seçilen kriterlere uygun işlem bulunamadı.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
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