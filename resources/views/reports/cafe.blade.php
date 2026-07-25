@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-coffee me-2"></i>Kafe Satış Raporları</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Geri Dön
                    </a>
                    <div class="d-flex gap-1">
                        <input type="date" id="reportStartDate" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" style="width: 150px;" title="Başlangıç Tarihi">
                        <input type="time" id="reportStartTime" class="form-control form-control-sm" value="00:00" style="width: 100px;" title="Başlangıç Saati">
                        <input type="date" id="reportEndDate" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" style="width: 150px;" title="Bitiş Tarihi">
                        <input type="time" id="reportEndTime" class="form-control form-control-sm" value="23:59" style="width: 100px;" title="Bitiş Saati">
                        <button type="button" class="btn btn-info btn-sm" onclick="printDailyReportWithDate()">
                            <i class="fas fa-print me-1"></i>Gün Sonu Raporu
                        </button>
                    </div>
                    <form method="GET" class="d-inline">
                        <input type="hidden" name="start_date" value="{{ request('start_date', $startDateTime->format('Y-m-d')) }}">
                        <input type="hidden" name="end_date" value="{{ request('end_date', $endDateTime->format('Y-m-d')) }}">
                        <input type="hidden" name="period" value="{{ request('period', $period) }}">
                        <input type="hidden" name="start_time" value="{{ request('start_time', $startTime ?? '00:00') }}">
                        <input type="hidden" name="end_time" value="{{ request('end_time', $endTime ?? '23:59') }}">
                        <input type="hidden" name="export" value="excel">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-file-excel me-1"></i>Excel İndir
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtre Formu -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-2">
                        <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="{{ $startDateTime->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label for="start_time" class="form-label">Başlangıç Saati</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" 
                               value="{{ $startTime ?? '00:00' }}">
                    </div>
                    <div class="col-md-2">
                        <label for="end_date" class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="{{ $endDateTime->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label for="end_time" class="form-label">Bitiş Saati</label>
                        <input type="time" class="form-control" id="end_time" name="end_time" 
                               value="{{ $endTime ?? '23:59' }}">
                    </div>
                    <div class="col-md-2">
                        <label for="period" class="form-label">Periyot</label>
                        <select class="form-select" id="period" name="period">
                            <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Günlük</option>
                            <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Haftalık</option>
                            <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Aylık</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>Filtrele
                            </button>
                            <a href="{{ route('reports.cafe') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Temizle
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Özet Kartları -->
                <div class="row mb-4">
                    <div class="col-lg-2 col-md-4 mb-3">
                        <div class="card bg-primary text-white dashboard-button-cafe">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Toplam Sipariş</h6>
                                        <h4 class="mb-0">{{ $totalOrders ?? 0 }}</h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-shopping-cart fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-3">
                        <div class="card bg-success text-white dashboard-button-cafe">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Toplam Gelir</h6>
                                        <h4 class="mb-0">{{ number_format($totalRevenue ?? 0, 0) }} ₺</h4>
                                        <small>tüm ödemeler</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-chart-line fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- İndirim kartını her zaman göster -->
                    <div class="col-lg-2 col-md-4 mb-3">
                        <div class="card bg-danger text-white dashboard-button-cafe">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Toplam İndirim</h6>
                                        <h4 class="mb-0">{{ number_format($discountStats['total_discount_amount'] ?? 0, 0) }} ₺</h4>
                                        <small>{{ $discountStats['orders_with_discount'] ?? 0 }} sipariş</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-tags fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-3">
                        <div class="card bg-warning text-white dashboard-button-cafe">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Nakit Ödeme</h6>
                                        <h4 class="mb-0">{{ number_format($enhancedCashPayments->total_amount ?? 0, 0) }} ₺</h4>
                                        <small>{{ $enhancedCashPayments->order_count ?? 0 }} ödeme</small>
                                        @if(($enhancedCashPayments->split_amount ?? 0) > 0)
                                            <div class="mt-1">
                                                <small class="text-light opacity-75">
                                                    <i class="fas fa-users me-1"></i>Split: {{ number_format($enhancedCashPayments->split_amount, 0) }} ₺
                                                    ({{ $enhancedCashPayments->split_count }} adet)
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-money-bill-wave fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-3">
                        <div class="card bg-secondary text-white dashboard-button-cafe">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Kart Ödeme</h6>
                                        <h4 class="mb-0">{{ number_format($enhancedCardPayments->total_amount ?? 0, 0) }} ₺</h4>
                                        <small>{{ $enhancedCardPayments->order_count ?? 0 }} ödeme</small>
                                        @if(($enhancedCardPayments->split_amount ?? 0) > 0)
                                            <div class="mt-1">
                                                <small class="text-light opacity-75">
                                                    <i class="fas fa-users me-1"></i>Split: {{ number_format($enhancedCardPayments->split_amount, 0) }} ₺
                                                    ({{ $enhancedCardPayments->split_count }} adet)
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-credit-card fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-3">
                        <div class="card bg-danger text-white dashboard-button-cafe">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="card-title">Ödenmedi</h6>
                                        <h4 class="mb-0">{{ $unpaidOrders ?? 0 }}</h4>
                                        <small>sipariş</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>

                
                <!-- Ödeme Yöntemi Detayları -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Ödeme Yöntemi Detayları</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Ödeme Yöntemi</th>
                                                <th>Sipariş/Ödeme Sayısı</th>
                                                <th>Toplam Tutar</th>
                                                <th>Yüzde</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalPaymentAmount = $paymentStats->sum('total_amount');
                                            @endphp
                                            @foreach($paymentStats as $payment)
                                                @if($payment->total_amount > 0)
                                                <tr>
                                                    <td>
                                                        @if($payment->payment_method === 'cash')
                                                            <i class="fas fa-money-bill-wave text-warning me-2"></i>Nakit (Bölünmüş)
                                                        @elseif($payment->payment_method === 'card')
                                                            <i class="fas fa-credit-card text-info me-2"></i>Kredi Kartı (Bölünmüş)
                                                        @else
                                                            <i class="fas fa-question-circle text-secondary me-2"></i>{{ ucfirst($payment->payment_method) }} (Bölünmüş)
                                                        @endif
                                                    </td>
                                                    <td>{{ $payment->order_count ?? $payment->payment_count ?? 0 }}</td>
                                                    <td>{{ number_format($payment->total_amount, 2) }} ₺</td>
                                                    <td>
                                                        @if($totalPaymentAmount > 0)
                                                            {{ number_format(($payment->total_amount / $totalPaymentAmount) * 100, 1) }}%
                                                        @else
                                                            0%
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endif
                                            @endforeach
                                            @if($splitPaymentTotal > 0)
                                                <tr class="table-info">
                                                    <td>
                                                        <i class="fas fa-users text-purple me-2"></i><strong>Bölünmüş Hesap Toplam Tutarı</strong>
                                                    </td>
                                                    <td>-</td>
                                                    <td><strong>{{ number_format($splitPaymentTotal, 2) }} ₺</strong></td>
                                                    <td>
                                                        @if($totalRevenue > 0)
                                                            <strong>{{ number_format(($splitPaymentTotal / $totalRevenue) * 100, 1) }}%</strong>
                                                        @else
                                                            <strong>0%</strong>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ürün Satış Detayları -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-box me-2"></i>Tüm Ürün Satış Detayları</h6>
                                <div class="d-flex align-items-center">
                                    <label for="product_per_page" class="form-label me-2 mb-0 text-white">Sayfa başına:</label>
                                    <select id="product_per_page" class="form-select form-select-sm" style="width: auto;" onchange="changeProductPerPage(this.value)">
                                        <option value="10" {{ request('product_per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                                        <option value="20" {{ request('product_per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                                        <option value="50" {{ request('product_per_page', 20) == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('product_per_page', 20) == 100 ? 'selected' : '' }}>100</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body">
                                @if($allProductSales && $allProductSales->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Ürün Adı</th>
                                                    <th class="text-end">Toplam Miktar</th>
                                                    <th class="text-end">Sipariş Sayısı</th>
                                                    <th class="text-end">Toplam Gelir</th>
                                                    <th class="text-end">Ort. Fiyat</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $totalQuantity = $allProductSales->sum('total_quantity');
                                                    $totalProductRevenue = $allProductSales->sum('total_revenue');
                                                @endphp
                                                @foreach($allProductSales as $index => $product)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td><strong>{{ $product->name }}</strong></td>
                                                        <td class="text-end">
                                                            <span class="badge bg-primary">{{ $product->total_quantity }} adet</span>
                                                        </td>
                                                        <td class="text-end">{{ $product->order_count }} sipariş</td>
                                                        <td class="text-end"><strong>{{ number_format($product->total_revenue, 2) }} ₺</strong></td>
                                                        <td class="text-end">{{ number_format($product->total_revenue / $product->total_quantity, 2) }} ₺</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-secondary">
                                                <tr>
                                                    <th colspan="2">TOPLAM</th>
                                                    <th class="text-end">
                                                        <span class="badge bg-success">{{ $totalQuantity }} adet</span>
                                                    </th>
                                                    <th class="text-end">{{ $allProductSales->sum('order_count') }} sipariş</th>
                                                    <th class="text-end"><strong>{{ number_format($totalProductRevenue, 2) }} ₺</strong></th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    
                                    <!-- Sayfalama -->
                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <div class="text-muted">
                                            Toplam {{ $allProductSales->total() }} ürün ({{ $allProductSales->firstItem() }}-{{ $allProductSales->lastItem() }} arası gösteriliyor)
                                        </div>
                                        
                                        <!-- Basit sayfalama butonları -->
                                        <div class="btn-group" role="group">
                                            @if($allProductSales->onFirstPage())
                                                <span class="btn btn-outline-secondary btn-sm disabled">
                                                    <i class="fas fa-chevron-left"></i> Önceki
                                                </span>
                                            @else
                                                <a href="{{ $allProductSales->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-chevron-left"></i> Önceki
                                                </a>
                                            @endif
                                            
                                            <span class="btn btn-primary btn-sm">
                                                Sayfa {{ $allProductSales->currentPage() }} / {{ $allProductSales->lastPage() }}
                                            </span>
                                            
                                            @if($allProductSales->hasMorePages())
                                                <a href="{{ $allProductSales->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
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
                                    <p class="text-muted text-center mb-0">Bu tarih aralığında ürün satışı bulunamadı.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafikler -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Periyodik Sipariş Trendi</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="ordersChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Sipariş Durumu Dağılımı</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Saatlik Dağılım -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Saatlik Sipariş Dağılımı</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="hourlyChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- En Çok Sipariş Edilen Ürünler -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">En Çok Sipariş Edilen Ürünler</h6>
                                <select id="top_products_per_page" class="form-select form-select-sm" style="width: auto;" onchange="changeTopProductsPerPage(this.value)">
                                    <option value="5" {{ request('top_products_per_page', 10) == 5 ? 'selected' : '' }}>5</option>
                                    <option value="10" {{ request('top_products_per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ request('top_products_per_page', 10) == 20 ? 'selected' : '' }}>20</option>
                                </select>
                            </div>
                            <div class="card-body">
                                @if($topProducts->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Ürün</th>
                                                    <th>Miktar</th>
                                                    <th>Gelir</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topProducts as $product)
                                                    <tr>
                                                        <td>{{ $product->name }}</td>
                                                        <td>{{ $product->total_ordered }}</td>
                                                        <td>{{ number_format($product->total_revenue, 2) }} ₺</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-center mt-3">
                                        <div class="btn-group" role="group">
                                            @if($topProducts->onFirstPage())
                                                <span class="btn btn-outline-secondary btn-sm disabled"><i class="fas fa-chevron-left"></i> Önceki</span>
                                            @else
                                                <a href="{{ $topProducts->previousPageUrl() }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-chevron-left"></i> Önceki</a>
                                            @endif
                                            <span class="btn btn-primary btn-sm">{{ $topProducts->currentPage() }} / {{ $topProducts->lastPage() }}</span>
                                            @if($topProducts->hasMorePages())
                                                <a href="{{ $topProducts->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">Sonraki <i class="fas fa-chevron-right"></i></a>
                                            @else
                                                <span class="btn btn-outline-secondary btn-sm disabled">Sonraki <i class="fas fa-chevron-right"></i></span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted text-center">Veri bulunamadı.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Masa Performansı</h6>
                                <select id="table_performance_per_page" class="form-select form-select-sm" style="width: auto;" onchange="changeTablePerformancePerPage(this.value)">
                                    <option value="5" {{ request('table_performance_per_page', 10) == 5 ? 'selected' : '' }}>5</option>
                                    <option value="10" {{ request('table_performance_per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ request('table_performance_per_page', 10) == 20 ? 'selected' : '' }}>20</option>
                                </select>
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
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($tablePerformance as $table)
                                                    <tr>
                                                        <td>{{ $table->table_name }}</td>
                                                        <td>{{ $table->orders_count }}</td>
                                                        <td>{{ number_format($table->total_revenue, 2) }} ₺</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-center mt-3">
                                        <div class="btn-group" role="group">
                                            @if($tablePerformance->onFirstPage())
                                                <span class="btn btn-outline-secondary btn-sm disabled"><i class="fas fa-chevron-left"></i> Önceki</span>
                                            @else
                                                <a href="{{ $tablePerformance->previousPageUrl() }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-chevron-left"></i> Önceki</a>
                                            @endif
                                            <span class="btn btn-primary btn-sm">{{ $tablePerformance->currentPage() }} / {{ $tablePerformance->lastPage() }}</span>
                                            @if($tablePerformance->hasMorePages())
                                                <a href="{{ $tablePerformance->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">Sonraki <i class="fas fa-chevron-right"></i></a>
                                            @else
                                                <span class="btn btn-outline-secondary btn-sm disabled">Sonraki <i class="fas fa-chevron-right"></i></span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted text-center">Veri bulunamadı.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Süre Analizi ve Kafe Yoğunluğu -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Oturma Süresi Analizi</h6>
                            </div>
                            <div class="card-body">
                                @if($durationStats['total_analyzed_orders'] > 0)
                                    <div class="row text-center mb-3">
                                        <div class="col-6">
                                            <h4 class="text-primary mb-0">{{ $durationStats['average_duration'] }} dk</h4>
                                            <small class="text-muted">Ortalama Oturma Süresi</small>
                                        </div>
                                        <div class="col-6">
                                            <h4 class="text-info mb-0">{{ $durationStats['total_analyzed_orders'] }}</h4>
                                            <small class="text-muted">Analiz Edilen Sipariş</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <canvas id="durationChart" height="200"></canvas>
                                    </div>
                                    
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted">En Kısa</small>
                                            <div class="fw-bold">{{ $durationStats['min_duration_minutes'] }} dk</div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">En Uzun</small>
                                            <div class="fw-bold">{{ $durationStats['max_duration_minutes'] }} dk</div>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted text-center">Süre analizi için yeterli veri bulunamadı.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-chart-area me-2"></i>Kafe Yoğunluk Analizi</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <h4 class="text-success mb-0">{{ $occupancyStats['average_occupancy_percentage'] }}%</h4>
                                        <small class="text-muted">Ortalama Doluluk</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-warning mb-0">{{ count($occupancyStats['peak_hours']) }}</h4>
                                        <small class="text-muted">Yoğun Saat</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-info mb-0">{{ $occupancyStats['total_tables'] }}</h4>
                                        <small class="text-muted">Toplam Masa</small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <strong class="text-primary">En Yoğun Saatler:</strong>
                                    <div class="mt-1">
                                        @foreach($occupancyStats['peak_hours'] as $hour)
                                            <span class="badge bg-primary me-1">{{ $hour }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <canvas id="occupancyChart" height="150"></canvas>
                                </div>
                                
                                <div class="row text-center">
                                    <div class="col-6">
                                        <small class="text-muted">Hafta İçi</small>
                                        <div class="fw-bold text-primary">{{ $occupancyStats['weekday_orders'] }} sipariş</div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Hafta Sonu</small>
                                        <div class="fw-bold text-success">{{ $occupancyStats['weekend_orders'] }} sipariş</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detaylı Sipariş Listesi -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Detaylı Sipariş Listesi</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Sipariş No</th>
                                        <th>Tarih</th>
                                        <th>Masa</th>
                                        <th>Garson</th>
                                        <th>Süre</th>
                                        <th>Durum</th>
                                        <th>Ödeme</th>
                                        <th>Toplam</th>
                                        <th>Ürünler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cafeOrders as $order)
                                        <tr>
                                            <td><strong>#{{ $order->id }}</strong></td>
                                            <td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
                                            <td>{{ $order->table->name ?? 'Bilinmiyor' }}</td>
                                            <td>{{ $order->user->name ?? 'Bilinmiyor' }}</td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-clock me-1"></i>{{ $order->formatted_duration }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($order->status === 'pending')
                                                    <span class="badge bg-warning">Bekliyor</span>
                                                @elseif($order->status === 'preparing')
                                                    <span class="badge bg-info">Hazırlanıyor</span>
                                                @elseif($order->status === 'ready')
                                                    <span class="badge bg-primary">Hazır</span>
                                                @elseif($order->status === 'served')
                                                    <span class="badge bg-success">Servis Edildi</span>
                                                @elseif($order->status === 'cancelled')
                                                    <span class="badge bg-danger">İptal</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($order->is_paid)
                                                    <span class="badge bg-success">Ödendi</span>
                                                    <br><small class="text-muted">
                                                        {{ $order->payment_method === 'cash' ? 'Nakit' : 'Kart' }}
                                                    </small>
                                                @else
                                                    <span class="badge bg-danger">Ödenmedi</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>
                                                    @if($order->final_amount && $order->final_amount > 0)
                                                        {{ number_format($order->final_amount, 2) }} ₺
                                                        @if($order->discount_amount > 0)
                                                            <small class="text-success d-block">
                                                                <i class="fas fa-tag"></i> {{ number_format($order->discount_amount, 2) }} ₺ indirim
                                                            </small>
                                                        @endif
                                                    @else
                                                        {{ number_format($order->total_amount, 2) }} ₺
                                                    @endif
                                                </strong>
                                            </td>
                                            
                                                <td>
                                                    @foreach($order->cafeOrderItems as $item)
                                                        <small class="d-block">
                                                            {{ $item->quantity }}x {{ $item->product->name }}
                                                            ({{ number_format($item->total_price, 2) }} ₺)
                                                        </small>
                                                    @endforeach
                                                    
                                                    @if($order->cafeOrderExtras && $order->cafeOrderExtras->count() > 0)
                                                        @foreach($order->cafeOrderExtras as $extra)
                                                            <small class="d-block text-info">
                                                                <i class="fas fa-plus-circle"></i> {{ $extra->description }}
                                                                ({{ number_format($extra->amount, 2) }} ₺)
                                                            </small>
                                                        @endforeach
                                                    @endif
                                                </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Sayfalama -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Toplam {{ $cafeOrders->total() }} sipariş
                            </div>
                            
                            <!-- Basit sayfalama butonları -->
                            <div class="btn-group" role="group">
                                @if($cafeOrders->currentPage() > 1)
                                    <a href="{{ $cafeOrders->appends(request()->query())->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-chevron-left"></i> Önceki
                                    </a>
                                @endif
                                
                                <span class="btn btn-primary btn-sm">
                                    Sayfa {{ $cafeOrders->currentPage() }} / {{ $cafeOrders->lastPage() }}
                                </span>
                                
                                @if($cafeOrders->hasMorePages())
                                    <a href="{{ $cafeOrders->appends(request()->query())->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sipariş trendi grafiği
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    new Chart(ordersCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($ordersData->pluck('date')) !!},
            datasets: [{
                label: 'Sipariş Sayısı',
                data: {!! json_encode($ordersData->pluck('orders_count')) !!},
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'Gelir (₺)',
                data: {!! json_encode($ordersData->pluck('total_revenue')) !!},
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });

    // Sipariş durumu grafiği
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($statusDistribution->pluck('status')->map(function($status) {
                return match($status) {
                    'pending' => 'Bekliyor',
                    'preparing' => 'Hazırlanıyor',
                    'ready' => 'Hazır',
                    'served' => 'Servis Edildi',
                    'cancelled' => 'İptal',
                    default => 'Bilinmiyor'
                };
            })) !!},
            datasets: [{
                data: {!! json_encode($statusDistribution->pluck('count')) !!},
                backgroundColor: [
                    '#ffc107',
                    '#17a2b8',
                    '#28a745',
                    '#007bff',
                    '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Saatlik dağılım grafiği
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($hourlyOrders->pluck('hour')->map(function($hour) { return $hour . ':00'; })) !!},
            datasets: [{
                label: 'Sipariş Sayısı',
                data: {!! json_encode($hourlyOrders->pluck('orders_count')) !!},
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Süre analizi grafiği
    @if($durationStats['total_valid_orders'] > 0)
    const durationCtx = document.getElementById('durationChart').getContext('2d');
    new Chart(durationCtx, {
        type: 'doughnut',
        data: {
            labels: ['0-30 dk', '31-60 dk', '1-2 saat', '2-3 saat', '3+ saat'],
            datasets: [{
                data: [
                    {{ $durationStats['duration_ranges']['0-30'] }},
                    {{ $durationStats['duration_ranges']['31-60'] }},
                    {{ $durationStats['duration_ranges']['61-120'] }},
                    {{ $durationStats['duration_ranges']['121-180'] }},
                    {{ $durationStats['duration_ranges']['180+'] }}
                ],
                backgroundColor: [
                    '#28a745',
                    '#17a2b8',
                    '#ffc107',
                    '#fd7e14',
                    '#dc3545'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    @endif

    // Kafe yoğunluk grafiği
    const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
    new Chart(occupancyCtx, {
        type: 'line',
        data: {
            labels: [
                @for($i = 0; $i < 24; $i++)
                    '{{ $i }}:00'{{ $i < 23 ? ',' : '' }}
                @endfor
            ],
            datasets: [{
                label: 'Aktif Masa Sayısı',
                data: [
                    @for($i = 0; $i < 24; $i++)
                        {{ $occupancyStats['hourly_occupancy'][$i] ?? 0 }}{{ $i < 23 ? ',' : '' }}
                    @endfor
                ],
                borderColor: 'rgb(255, 193, 7)',
                backgroundColor: 'rgba(255, 193, 7, 0.2)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: {{ $occupancyStats['total_tables'] }}
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});


function printDailyReportWithDate() {
    // Seçilen tarihleri ve saatleri al
    const startDate = document.getElementById('reportStartDate').value;
    const startTime = document.getElementById('reportStartTime').value;
    const endDate = document.getElementById('reportEndDate').value;
    const endTime = document.getElementById('reportEndTime').value;
    
    // Termal rapor URL'ini oluştur
    const reportUrl = `{{ route('reports.cafe.thermal-daily') }}?start_date=${startDate}&start_time=${startTime}&end_date=${endDate}&end_time=${endTime}`;
    
    // Yeni pencerede aç - termal yazıcı boyutunda
    const printWindow = window.open(reportUrl, '_blank', 'width=220,height=600,scrollbars=no,resizable=no');
    
    // Pencere yüklendikten sonra CSS ekle ve yazdır
    printWindow.onload = function() {
        // CSS stilleri ekle
        const style = printWindow.document.createElement('style');
        style.textContent = `
            @media print {
                @page {
                    size: 58mm auto;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                body {
                    margin: 0 !important;
                    padding: 2mm !important;
                    font-family: 'Courier New', monospace !important;
                    font-size: 8pt !important;
                    line-height: 1.1 !important;
                    width: 58mm !important;
                    max-width: 58mm !important;
                }
            }
            body {
                margin: 0;
                padding: 6px;
                font-family: 'Courier New', monospace;
                font-size: 10pt;
                line-height: 1.2;
                width: 100%;
                max-width: 200px;
            }
        `;
        printWindow.document.head.appendChild(style);
        
        setTimeout(function() {
            printWindow.print();
        }, 1000);
    };
}


function changeProductPerPage(value) {
    const url = new URL(window.location);
    url.searchParams.set('product_per_page', value);
    url.searchParams.delete('product_page');
    window.location.href = url.toString();
}

function changeTopProductsPerPage(value) {
    const url = new URL(window.location);
    url.searchParams.set('top_products_per_page', value);
    url.searchParams.delete('top_products_page');
    window.location.href = url.toString();
}

function changeTablePerformancePerPage(value) {
    const url = new URL(window.location);
    url.searchParams.set('table_performance_per_page', value);
    url.searchParams.delete('table_performance_page');
    window.location.href = url.toString();
}

</script>
@endsection
