@extends('layouts.app')

@section('title', 'Kar Raporları')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Kar Raporları</h5>
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

                <!-- Özet Kartları -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card bg-info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-light">Toplam Gelir</h6>
                                    <div class="stats-number">{{ number_format($totalRevenue, 2) }} ₺</div>
                                </div>
                                <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card bg-warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-light">Toplam Maliyet</h6>
                                    <div class="stats-number">{{ number_format($totalCost, 2) }} ₺</div>
                                </div>
                                <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card {{ $totalProfit >= 0 ? 'bg-success' : 'bg-danger' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-light">Net Kar</h6>
                                    <div class="stats-number">{{ number_format($totalProfit, 2) }} ₺</div>
                                </div>
                                <i class="fas fa-chart-line fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kar Marjı -->
                @if($totalRevenue > 0)
                <div class="alert alert-info">
                    <h6><i class="fas fa-percentage me-2"></i>Kar Marjı</h6>
                    <p class="mb-0">
                        <strong>{{ number_format(($totalProfit / $totalRevenue) * 100, 2) }}%</strong>
                        - {{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }} tarihleri arası
                    </p>
                </div>
                @endif

                <!-- Ürün Bazlı Kar Analizi -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ürün</th>
                                <th>Satılan Miktar</th>
                                <th>Toplam Gelir</th>
                                <th>Toplam Maliyet</th>
                                <th>Net Kar</th>
                                <th>Kar Marjı</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($profitData as $data)
                                <tr>
                                    <td><strong>{{ $data->product_name }}</strong></td>
                                    <td>{{ number_format($data->total_sold) }}</td>
                                    <td>{{ number_format($data->total_revenue, 2) }} ₺</td>
                                    <td>{{ number_format($data->total_cost, 2) }} ₺</td>
                                    <td>
                                        <span class="badge {{ $data->total_profit >= 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ number_format($data->total_profit, 2) }} ₺
                                        </span>
                                    </td>
                                    <td>
                                        @if($data->total_revenue > 0)
                                            {{ number_format(($data->total_profit / $data->total_revenue) * 100, 2) }}%
                                        @else
                                            0%
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Bu tarih aralığında satış bulunmuyor.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Sayfalama -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Toplam {{ $profitData->total() }} ürün
                    </div>
                    
                    <!-- Basit sayfalama butonları -->
                    <div class="btn-group" role="group">
                        @if($profitData->currentPage() > 1)
                            <a href="{{ $profitData->appends(request()->query())->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chevron-left"></i> Önceki
                            </a>
                        @endif
                        
                        <span class="btn btn-primary btn-sm">
                            Sayfa {{ $profitData->currentPage() }} / {{ $profitData->lastPage() }}
                        </span>
                        
                        @if($profitData->hasMorePages())
                            <a href="{{ $profitData->appends(request()->query())->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
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