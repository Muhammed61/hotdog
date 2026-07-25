@extends('layouts.app')

@section('title', 'Depo Raporları')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Depo Raporları</h5>
                <a href="{{ route('warehouse.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Depo Yönetimine Dön
                </a>
            </div>
            <div class="card-body">
                <!-- Özet Bilgiler -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4>{{ number_format($totalIn) }}</h4>
                                <p class="mb-0"><i class="fas fa-arrow-down me-1"></i>Toplam Giriş</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h4>{{ number_format($totalOut) }}</h4>
                                <p class="mb-0"><i class="fas fa-arrow-up me-1"></i>Toplam Çıkış</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4>{{ number_format($totalIn - $totalOut) }}</h4>
                                <p class="mb-0"><i class="fas fa-balance-scale me-1"></i>Net Hareket</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h4>{{ $productSummary->count() }}</h4>
                                <p class="mb-0"><i class="fas fa-boxes me-1"></i>Hareket Eden Ürün</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtreler -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filtreler</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="date_from" class="form-label">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="date_to" class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-2">
                                <label for="warehouse_product_id" class="form-label">Ürün</label>
                                <select class="form-select" id="warehouse_product_id" name="warehouse_product_id">
                                    <option value="">Tüm Ürünler</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ request('warehouse_product_id') == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="type" class="form-label">Hareket Tipi</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">Tümü</option>
                                    <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Giriş</option>
                                    <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Çıkış</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Filtrele
                                </button>
                                <a href="{{ route('warehouse.reports') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Temizle
                                </a>
                                <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel me-1"></i>Excel'e Aktar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ürün Bazında Özet -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Ürün Bazında Özet</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Ürün</th>
                                        <th>Açıklama</th>
                                        <th class="text-center">Giriş</th>
                                        <th class="text-center">Çıkış</th>
                                        <th class="text-center">Net</th>
                                        <th class="text-center">Mevcut Stok</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($productSummary as $summary)
                                        <tr>
                                            <td>
                                                <strong>{{ $summary->warehouseProduct->name ?? 'Ürün Bulunamadı' }}</strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $summary->warehouseProduct->description ?? 'Açıklama yok' }}</small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success">{{ number_format($summary->total_in) }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-danger">{{ number_format($summary->total_out) }}</span>
                                            </td>
                                            <td class="text-center">
                                                @php $net = $summary->total_in - $summary->total_out @endphp
                                                <span class="badge {{ $net >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $net >= 0 ? '+' : '' }}{{ number_format($net) }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ number_format($summary->warehouseProduct->current_stock ?? 0) }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>Seçilen kriterlere uygun hareket bulunamadı.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Detaylı Hareket Listesi -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Detaylı Hareket Listesi</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Ürün</th>
                                        <th>Açıklama</th>
                                        <th class="text-center">Tip</th>
                                        <th class="text-center">Miktar</th>
                                        <th>Sebep</th>
                                        <th>Kullanıcı</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($movements as $movement)
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $movement->created_at->format('d.m.Y H:i') }}
                                                </small>
                                            </td>
                                            <td>
                                                <strong>{{ $movement->warehouseProduct->name ?? 'Ürün Bulunamadı' }}</strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $movement->warehouseProduct->description ?? 'Açıklama yok' }}</small>
                                            </td>
                                            <td class="text-center">
                                                @if($movement->type == 'in')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-arrow-down me-1"></i>Giriş
                                                    </span>
                                                @else
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-arrow-up me-1"></i>Çıkış
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary">{{ number_format($movement->quantity) }}</span>
                                            </td>
                                            <td>
                                                <small>{{ $movement->reason ?? 'Sebep belirtilmemiş' }}</small>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $movement->user->name ?? 'Bilinmiyor' }}</small>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>Seçilen kriterlere uygun hareket bulunamadı.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Sayfalama -->
                        @php
                            $prevUrl = $movements->appends(request()->query())->previousPageUrl();
                            $nextUrl = $movements->appends(request()->query())->nextPageUrl();
                        @endphp

                        @if($movements->lastPage() > 1)
                        <div class="d-flex justify-content-between align-items-center mt-4" id="warehouse-pagination">
                            <div class="text-muted">Toplam {{ $movements->total() }} hareket</div>
                            <div class="btn-group" role="group">
                                @if($prevUrl)
                                    <a href="{{ $prevUrl }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-chevron-left"></i> Önceki
                                    </a>
                                @endif

                                <span class="btn btn-primary btn-sm">
                                    Sayfa {{ $movements->currentPage() }} / {{ $movements->lastPage() }}
                                </span>

                                @if($nextUrl)
                                    <a href="{{ $nextUrl }}" class="btn btn-outline-primary btn-sm">
                                        Sonraki <i class="fas fa-chevron-right"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportToExcel() {
    // Excel export fonksiyonu - gerekirse implement edilebilir
    alert('Excel export özelliği yakında eklenecek!');
}
</script>
@endsection