@extends('layouts.app')

@section('title', 'Stok Hareketleri')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Stok Hareketleri</h5>
                <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Geri Dön
                </a>
            </div>
            <div class="card-body">
                <!-- Filtreler -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i>Filtrele
                        </button>
                    </div>
                </form>

                <!-- Özet -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="stats-card bg-success">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-light">Toplam Giriş</h6>
                                    <div class="stats-number">{{ number_format($inMovements) }}</div>
                                </div>
                                <i class="fas fa-arrow-down fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stats-card bg-danger">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-light">Toplam Çıkış</h6>
                                    <div class="stats-number">{{ number_format($outMovements) }}</div>
                                </div>
                                <i class="fas fa-arrow-up fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hareket Listesi -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Ürün</th>
                                <th>İşlem</th>
                                <th>Miktar</th>
                                <th>Birim Fiyat</th>
                                <th>Açıklama</th>
                                <th>Kullanıcı</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $movement)
                                <tr>
                                    <td>{{ $movement->created_at->format('d.m.Y H:i') }}</td>
                                    <td>{{ $movement->product->name }}</td>
                                    <td>
                                        <span class="badge {{ $movement->type == 'in' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $movement->type == 'in' ? 'Giriş' : 'Çıkış' }}
                                        </span>
                                    </td>
                                    <td>{{ $movement->quantity }} {{ $movement->product->unit }}</td>
                                    <td>{{ number_format($movement->unit_price, 2) }} ₺</td>
                                    <td>{{ $movement->reason }}</td>
                                    <td>{{ $movement->user->name ?? 'Sistem' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Bu tarih aralığında hareket bulunmuyor.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Sayfalama -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Toplam {{ $movements->total() }} hareket
                    </div>
                    
                    <!-- Basit sayfalama butonları -->
                    <div class="btn-group" role="group">
                        @if($movements->currentPage() > 1)
                            <a href="{{ $movements->appends(request()->query())->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chevron-left"></i> Önceki
                            </a>
                        @endif
                        
                        <span class="btn btn-primary btn-sm">
                            Sayfa {{ $movements->currentPage() }} / {{ $movements->lastPage() }}
                        </span>
                        
                        @if($movements->hasMorePages())
                            <a href="{{ $movements->appends(request()->query())->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                Sonraki <i class="fas fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection