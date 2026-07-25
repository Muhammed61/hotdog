@extends('layouts.app')

@section('content')
<div class="container-fluid">

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-cash-register me-2"></i>Kasa Sistemi</h5>
    </div>

    <div class="card-body">
        <!-- Tarih Filtresi -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Tarih ve Saat Filtresi</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('cash-register.index') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="{{ $startDate }}" required>
                        </div>
                        <div class="col-md-3">
                            <label for="start_time" class="form-label">Başlangıç Saati</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" 
                                   value="{{ $startTime }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="{{ $endDate }}" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_time" class="form-label">Bitiş Saati</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" 
                                   value="{{ $endTime }}">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Filtrele
                            </button>
                            <a href="{{ route('cash-register.index') }}" class="btn btn-secondary">
                                <i class="fas fa-refresh me-1"></i>Temizle
                            </a>
                            <span class="text-muted ms-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Gösterilen veriler: {{ $startDate }} {{ $startTime }} - {{ $endDate }} {{ $endTime }}
                            </span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kasa Bakiyeleri -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Stok Takip Kasası</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <h3 class="text-primary">{{ number_format($stockBalance, 2) }} ₺</h3>
                            <p class="text-muted mb-0">Toplam Bakiye</p>
                            <small class="text-muted">
                                Mevcut: {{ number_format($existingStockCash, 2) }} ₺
                            </small>
                        </div>
                        <div class="col-6 text-end">
                            <a href="{{ route('cash-register.create', 'stock') }}" class="btn btn-primary btn-sm mb-2">
                                <i class="fas fa-plus me-1"></i>İşlem Ekle
                            </a><br>
                            <a href="{{ route('cash-register.transactions', 'stock') }}?start_date={{ $startDate }}&start_time={{ $startTime }}&end_date={{ $endDate }}&end_time={{ $endTime }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list me-1"></i>Tüm İşlemler
                            </a>
                        </div>
                    </div>
                    
                    <!-- Günlük Özet -->
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h6 class="text-success">{{ number_format($todayStats['stock']['income'], 2) }} ₺</h6>
                            <small class="text-muted">Bugün Gelir</small>
                        </div>
                        <div class="col-6">
                            <h6 class="text-danger">{{ number_format($todayStats['stock']['expense'], 2) }} ₺</h6>
                            <small class="text-muted">Bugün Gider</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-coffee me-2"></i>Kafe Sistemi Kasası (Sadece Kasadaki Nakit Para)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <h3 class="text-success">{{ number_format($cafeBalance, 2) }} ₺</h3>
                            <p class="text-muted mb-0">Toplam Nakit Para</p>
                            <small class="text-muted">
                                Kafe Geliri Müşetiri Ödemeleri: {{ number_format($existingCafeCash, 2) }} ₺
                            </small>
                        </div>
                        <div class="col-6 text-end">
                            <a href="{{ route('cash-register.create', 'cafe') }}" class="btn btn-primary btn-sm mb-2">
                                <i class="fas fa-plus me-1"></i>İşlem Ekle
                            </a><br>
                            <a href="{{ route('cash-register.transactions', 'cafe') }}?start_date={{ $startDate }}&start_time={{ $startTime }}&end_date={{ $endDate }}&end_time={{ $endTime }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list me-1"></i>Tüm İşlemler
                            </a>
                        </div>
                    </div>
                    
                    <!-- Günlük Özet -->
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h6 class="text-success">{{ number_format($todayStats['cafe']['income'], 2) }} ₺</h6>
                            <small class="text-muted">Bugün Gelir</small>
                        </div>
                        <div class="col-6">
                            <h6 class="text-danger">{{ number_format($todayStats['cafe']['expense'], 2) }} ₺</h6>
                            <small class="text-muted">Bugün Gider</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mevcut Para Detayları -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Kasa Detayları</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Stok Takip Kasası Detayı</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-shopping-cart me-2 text-success"></i>Satış Gelirleri: <strong>{{ number_format($existingStockCash, 2) }} ₺</strong></li>
                                <li><i class="fas fa-exchange-alt me-2 text-info"></i>Kasa İşlemleri: <strong>{{ number_format($stockBalance - $existingStockCash, 2) }} ₺</strong></li>
                                <li><i class="fas fa-calculator me-2 text-primary"></i>Toplam Bakiye: <strong>{{ number_format($stockBalance, 2) }} ₺</strong></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">Kafe Sistemi Kasası Detayı</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-coffee me-2 text-success"></i>Kafe Gelirleri (Tümü): <strong>{{ number_format($existingCafeCash, 2) }} ₺</strong></li>
                                <li><i class="fas fa-exchange-alt me-2 text-info"></i>Kasa İşlemleri: <strong>{{ number_format($cafeBalance - $existingCafeCash, 2) }} ₺</strong></li>
                                <li><i class="fas fa-calculator me-2 text-success"></i>Toplam Bakiye: <strong>{{ number_format($cafeBalance, 2) }} ₺</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Son İşlemler -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Stok Kasası - Son İşlemler</h6>
                </div>
                <div class="card-body">
                    @if($stockTransactions->count() > 0)
                        @foreach($stockTransactions as $transaction)
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div>
                                    <span class="badge bg-{{ $transaction->transaction_type_color }}">
                                        <i class="{{ $transaction->transaction_type_icon }} me-1"></i>
                                        {{ $transaction->transaction_type_text }}
                                    </span>
                                    <small class="text-muted d-block">{{ $transaction->description }}</small>
                                </div>
                                <div class="text-end">
                                    <strong class="text-{{ $transaction->transaction_type === 'income' ? 'success' : 'danger' }}">
                                        {{ $transaction->transaction_type === 'income' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} ₺
                                    </strong>
                                    <small class="text-muted d-block">{{ $transaction->created_at->format('d.m.Y H:i') }}</small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">Henüz işlem bulunmuyor.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Kafe Kasası - Son İşlemler</h6>
                </div>
                <div class="card-body">
                    @if($cafeTransactions->count() > 0)
                        @foreach($cafeTransactions as $transaction)
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                <div>
                                    <span class="badge bg-{{ $transaction->transaction_type_color }}">
                                        <i class="{{ $transaction->transaction_type_icon }} me-1"></i>
                                        {{ $transaction->transaction_type_text }}
                                    </span>
                                    <small class="text-muted d-block">{{ $transaction->description }}</small>
                                </div>
                                <div class="text-end">
                                    <strong class="text-{{ $transaction->transaction_type === 'income' ? 'success' : 'danger' }}">
                                        {{ $transaction->transaction_type === 'income' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} ₺
                                    </strong>
                                    <small class="text-muted d-block">{{ $transaction->created_at->format('d.m.Y H:i') }}</small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center">Henüz işlem bulunmuyor.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
</div>
@endsection