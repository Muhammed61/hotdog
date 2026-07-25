@extends('layouts.app')

@section('title', 'Dashboard - Kafe Stok Takip')

@section('content')
<div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Stok Takip İstatistikleri</h5>
                     {{ date('d.m.Y H:i') }}
            </div>

    <div class="card-body">

@if(auth()->user()->hasAnyRole(['admin', 'manager']))
<!-- Genel İstatistik Kartları -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white dashboard-button">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title dbtn">Toplam Ürün</h6>
                        <h2 class="mb-0">{{ $stats['total_products'] }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-box fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white dashboard-button">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Düşük Stok</h6>
                        <h2 class="mb-0">{{ $stats['low_stock_products'] }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white dashboard-button">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Bugünkü Satış</h6>
                        <h2 class="mb-0">₺{{ number_format($stats['today_sales'], 2) }}</h2>
                        <small>{{ $stats['today_sales_count'] }} adet</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-turkish-lira-sign fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white dashboard-button">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Aylık Satış</h6>
                        <h2 class="mb-0">₺{{ number_format($stats['monthly_sales'], 2) }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Kafe Sistemi İstatistikleri -->
<div class="mb-4">
    <div class="d-flex align-items-center mb-3">
        <i class="fas fa-coffee me-2 text-primary"></i>
        <h4 class="mb-0">Kafe Sistemi İstatistikleri</h4>
    </div>
    <div class="row">
        <div class="col-md-2 mb-3">
            <div class="card text-white dashboard-button-cafe" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-center p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.8rem;">Toplam Sipariş</h6>
                            <h3 class="mb-0">{{ $cafeStats['total_orders'] }}</h3>
                        </div>
                        <div>
                            <i class="fas fa-shopping-cart fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-white dashboard-button-cafe" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-center p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.8rem;">Toplam Gelir</h6>
                            <h4 class="mb-0" style="font-size: 1.2rem;">{{ number_format($cafeStats['total_revenue'], 2) }} ₺</h4>
                        </div>
                        <div>
                            <i class="fas fa-turkish-lira-sign fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-white dashboard-button-cafe" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-center p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.8rem;">Kafe Kasası</h6>
                            <h4 class="mb-0" style="font-size: 1.2rem;">{{ number_format($cafeBalance, 2) }} ₺</h4>
                        </div>
                        <div>
                            <i class="fas fa-cash-register fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-white dashboard-button-cafe" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="card-body text-center p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.8rem;">Stok Kasası</h6>
                            <h4 class="mb-0" style="font-size: 1.2rem;">{{ number_format($stats['stock_cash_balance'], 0) }} ₺</h4>
                        </div>
                        <div>
                            <i class="fas fa-boxes fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-white dashboard-button-cafe" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <div class="card-body text-center p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.8rem;">Ödenmedi</h6>
                            <h3 class="mb-0">{{ $cafeStats['unpaid_orders'] }}</h3>
                            <small style="font-size: 0.7rem;">bekliyor</small>
                        </div>
                        <div>
                            <i class="fas fa-exclamation-triangle fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card text-white dashboard-button-cafe" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333 !important;">
                <div class="card-body text-center p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1" style="font-size: 0.8rem; color: #333;">Ödendi</h6>
                            <h3 class="mb-0" style="color: #333;">{{ $cafeStats['completed_orders'] }}</h3>
                            <small style="font-size: 0.7rem; color: #333;">sipariş</small>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-lg" style="color: #333;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if(auth()->user()->hasAnyRole(['admin', 'manager']))
<div class="row">
    <!-- Düşük Stok Ürünleri -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Düşük Stok Ürünleri</h5>
                <a href="{{ route('products.index', ['low_stock' => 1]) }}" class="btn btn-primary btn-sm">Tümünü Gör</a>
            </div>
            <div class="card-body">
                @if($lowStockProducts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Ürün</th>
                                    <th>Kategori</th>
                                    <th>Stok</th>
                                    <th>Min. Seviye</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->category->name }}</td>
                                    <td><span class="badge bg-danger">{{ $product->stock_quantity }}</span></td>
                                    <td>{{ $product->min_stock_level }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">Düşük stoklu ürün bulunmuyor.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Son Satışlar -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Son Satışlar</h5>
                <a href="{{ route('sales.index') }}" class="btn btn-primary btn-sm">Tümünü Gör</a>
            </div>
            <div class="card-body">
                @if($recentSales->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fatura No</th>
                                    <th>Tutar</th>
                                    <th>Tarih</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentSales as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $sale) }}" class="text-decoration-none">
                                            {{ $sale->invoice_number }}
                                        </a>
                                    </td>
                                    <td>₺{{ number_format($sale->total_amount, 2) }}</td>
                                    <td>{{ $sale->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">Henüz satış kaydı bulunmuyor.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
<!-- En Çok Sipariş Edilen Ürünler ve Masa Performansı -->
<div class="row">
    <!-- En Çok Sipariş Edilen Ürünler -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">En Çok Sipariş Edilen Ürünler</h5>
                <small class="text-muted color-white">Son 30 gün</small>
            </div>
            <div class="card-body">
                @if($topProducts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Ürün</th>
                                    <th>Sipariş</th>
                                    <th>Gelir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topProducts as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td><span class="badge bg-primary">{{ $product->total_ordered }}</span></td>
                                    <td>₺{{ number_format($product->total_revenue, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">Henüz kafe siparişi bulunmuyor.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Masa Performansı -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Masa Performansı</h5>
                <small class="text-muted color-white">Son 30 gün</small>
            </div>
            <div class="card-body">
                @if($tablePerformance->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Masa</th>
                                    <th>Sipariş</th>
                                    <th>Gelir</th>
                                    <th>Ort.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tablePerformance as $table)
                                <tr>
                                    <td>{{ $table->table_name }}</td>
                                    <td><span class="badge bg-info">{{ $table->orders_count }}</span></td>
                                    <td>₺{{ number_format($table->total_revenue, 2) }}</td>
                                    <td>₺{{ number_format($table->average_order, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted mb-0">Henüz masa siparişi bulunmuyor.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@if(auth()->user()->hasAnyRole(['admin', 'manager']))
<!-- Günlük Satış Grafiği -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Son 7 Günlük Satış Trendi</h5>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: {!! json_encode($dailySales->pluck('date')->map(function($date) { return date('d.m', strtotime($date)); })) !!},
        datasets: [{
            label: 'Günlük Satış (₺)',
            data: {!! json_encode($dailySales->pluck('total')) !!},
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₺' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Satış: ₺' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>
@endsection