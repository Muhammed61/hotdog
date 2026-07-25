@extends('layouts.app')

@section('title', 'Satış Raporları')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Satış Raporları</h5>
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Geri Dön
                    </a>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="{{ request('end_date', now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="period" class="form-label">Periyot</label>
                                <select class="form-select" id="period" name="period">
                                    <option value="daily" {{ request('period') == 'daily' ? 'selected' : '' }}>Günlük</option>
                                    <option value="weekly" {{ request('period') == 'weekly' ? 'selected' : '' }}>Haftalık</option>
                                    <option value="monthly" {{ request('period') == 'monthly' ? 'selected' : '' }}>Aylık</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">Filtrele</button>
                                    <button type="button" onclick="exportReport()" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6>Toplam Satış</h6>
                                    <h4>{{ $summary['total_sales'] }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6>Toplam Gelir</h6>
                                    <h4>{{ number_format($summary['total_revenue'], 2) }} ₺</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6>Ortalama Satış</h6>
                                    <h4>{{ number_format($summary['average_sale'], 2) }} ₺</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h6>Satılan Ürün</h6>
                                    <h4>{{ $summary['total_items'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Satış Grafiği</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="salesChart" height="160"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">En Çok Satan Ürünler</h6>
                                </div>
                                <div class="card-body">
                                    @foreach($topProducts as $product)
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>{{ $product->name }}</span>
                                            <span class="badge bg-primary">{{ $product->total_sold }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Satış Sayısı</th>
                                    <th>Toplam Gelir</th>
                                    <th>Ortalama Satış</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salesData as $data)
                                    <tr>
                                        <td>{{ $data->date }}</td>
                                        <td>{{ $data->sales_count }}</td>
                                        <td>{{ number_format($data->total_revenue, 2) }} ₺</td>
                                        <td>{{ number_format($data->average_sale, 2) }} ₺</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Sayfalama -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Toplam {{ $salesData->total() }} kayıt
                        </div>
                        
                        <!-- Basit sayfalama butonları -->
                        <div class="btn-group" role="group">
                            @if($salesData->currentPage() > 1)
                                <a href="{{ $salesData->appends(request()->query())->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-chevron-left"></i> Önceki
                                </a>
                            @endif
                            
                            <span class="btn btn-primary btn-sm">
                                Sayfa {{ $salesData->currentPage() }} / {{ $salesData->lastPage() }}
                            </span>
                            
                            @if($salesData->hasMorePages())
                                <a href="{{ $salesData->appends(request()->query())->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                    Sonraki <i class="fas fa-chevron-right"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {

    const ctx = document.getElementById('salesChart');
    if (ctx) {
        const salesChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($salesData->pluck('date')->toArray()) !!},
                datasets: [{
                    label: 'Günlük Satış (₺)',
                    data: {!! json_encode($salesData->pluck('total_revenue')->toArray()) !!},
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
                                return value.toLocaleString('tr-TR') + ' ₺';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString('tr-TR') + ' ₺';
                            }
                        }
                    }
                }
            }
        });
    }
});

function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '{{ route("reports.sales") }}?' + params.toString();
}
</script>
@endsection