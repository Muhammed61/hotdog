@extends('layouts.app')

@section('title', 'Stok Raporları')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Stok Raporları</h5>
                <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Geri Dön
                </a>
            </div>
            <div class="card-body">
                <!-- Özet Kartları -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-light">Toplam Stok Değeri</h6>
                                    <div class="stats-number">{{ number_format($totalStockValue, 2) }} ₺</div>
                                </div>
                                <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card bg-warning">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-light">Düşük Stok</h6>
                                    <div class="stats-number">{{ $lowStockProducts->count() }}</div>
                                </div>
                                <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card bg-info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-light">Toplam Ürün</h6>
                                    <div class="stats-number">{{ $products->count() }}</div>
                                </div>
                                <i class="fas fa-box fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Düşük Stok Uyarıları -->
                @if($lowStockProducts->count() > 0)
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Düşük Stok Uyarısı</h6>
                    <p class="mb-2">Aşağıdaki ürünlerin stok seviyeleri minimum seviyenin altında:</p>
                    <ul class="mb-0">
                        @foreach($lowStockProducts->take(5) as $product)
                            <li>{{ $product->name }} - Mevcut: {{ $product->stock_quantity }}, Min: {{ $product->min_stock_level }}</li>
                        @endforeach
                        @if($lowStockProducts->count() > 5)
                            <li>... ve {{ $lowStockProducts->count() - 5 }} ürün daha</li>
                        @endif
                    </ul>
                </div>
                @endif

                <!-- Stok Listesi -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ürün</th>
                                <th>Kategori</th>
                                <th>Mevcut Stok</th>
                                <th>Min. Seviye</th>
                                <th>Birim Fiyat</th>
                                <th>Stok Değeri</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr class="{{ $product->stock_quantity <= $product->min_stock_level ? 'table-warning' : '' }}">
                                    <td>
                                        <strong>{{ $product->name }}</strong>
                                        @if($product->barcode)
                                            <br><small class="text-muted">{{ $product->barcode }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $product->category->name }}</td>
                                    <td>
                                        <span class="badge {{ $product->stock_quantity <= $product->min_stock_level ? 'bg-danger' : 'bg-success' }}">
                                            {{ $product->stock_quantity }} {{ $product->unit }}
                                        </span>
                                    </td>
                                    <td>{{ $product->min_stock_level }} {{ $product->unit }}</td>
                                    <td>{{ number_format($product->purchase_price, 2) }} ₺</td>
                                    <td>{{ number_format($product->stock_value, 2) }} ₺</td>
                                    <td>
                                        @if($product->stock_quantity <= $product->min_stock_level)
                                            <span class="badge bg-danger">Düşük Stok</span>
                                        @elseif($product->stock_quantity <= $product->min_stock_level * 1.5)
                                            <span class="badge bg-warning">Dikkat</span>
                                        @else
                                            <span class="badge bg-success">Normal</span>
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
                        Toplam {{ $products->total() }} ürün
                    </div>
                    
                    <!-- Basit sayfalama butonları -->
                    <div class="btn-group" role="group">
                        @if($products->currentPage() > 1)
                            <a href="{{ $products->appends(request()->query())->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chevron-left"></i> Önceki
                            </a>
                        @endif
                        
                        <span class="btn btn-primary btn-sm">
                            Sayfa {{ $products->currentPage() }} / {{ $products->lastPage() }}
                        </span>
                        
                        @if($products->hasMorePages())
                            <a href="{{ $products->appends(request()->query())->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
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