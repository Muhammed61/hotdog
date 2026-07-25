@extends('layouts.app')

@section('title', 'Stok Hareketleri')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Stok Hareketleri</h5>
            </div>
            <div class="card-body">
                <!-- Filtreler -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="product_id" class="form-label">Ürün</label>
                            <select class="form-select" id="product_id" name="product_id">
                                <option value="">Tüm Ürünler</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="type" class="form-label">Hareket Tipi</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Tümü</option>
                                <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Giriş</option>
                                <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Çıkış</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>Filtrele
                            </button>
                            <a href="{{ route('stock-movements.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Temizle
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Özet Bilgiler -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4 class="mb-0">{{ number_format($totalIn) }}</h4>
                                <small>Toplam Giriş</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h4 class="mb-0">{{ number_format($totalOut) }}</h4>
                                <small>Toplam Çıkış</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4 class="mb-0">{{ number_format($totalIn - $totalOut) }}</h4>
                                <small>Net Hareket</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hareket Listesi -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Tarih</th>
                                <th>Ürün</th>
                                <th>İşlem</th>
                                <th>Miktar</th>
                                <th>Birim Fiyat</th>
                                <th>Toplam Değer</th>
                                <th>Açıklama</th>
                                <th>Kullanıcı</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $movement)
                                <tr>
                                    <td>{{ $movement->created_at->format('d.m.Y H:i') }}</td>
                                    <td>
                                        <strong>{{ $movement->product->name ?? 'Silinmiş Ürün' }}</strong>
                                        @if($movement->product)
                                            <br><small class="text-muted">{{ $movement->product->category->name ?? '' }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($movement->type == 'in')
                                            <span class="badge bg-success">
                                                <i class="fas fa-arrow-up me-1"></i>Giriş
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-arrow-down me-1"></i>Çıkış
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($movement->quantity) }}</strong>
                                        @if($movement->product)
                                            <small class="text-muted">{{ $movement->product->unit ?? 'adet' }}</small>
                                        @endif
                                    </td>
                                    <td>{{ number_format($movement->unit_price, 2) }} ₺</td>
                                    <td>
                                        <strong>{{ number_format($movement->quantity * $movement->unit_price, 2) }} ₺</strong>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $movement->reason }}</span>
                                    </td>
                                    <td>
                                        <i class="fas fa-user me-1"></i>{{ $movement->user->name ?? 'Sistem' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Henüz stok hareketi bulunmuyor.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Gelişmiş Sayfalama -->
                @if($movements->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            <p class="text-muted mb-0">
                                Toplam {{ $movements->total() }} hareket
                            </p>
                        </div>
                        <div class="d-flex align-items-center">
                            @if($movements->currentPage() > 1)
                                <a href="{{ $movements->appends(request()->query())->previousPageUrl() }}" class="btn btn-outline-primary btn-sm me-2">
                                    <i class="fas fa-chevron-left"></i> Önceki
                                </a>
                            @endif
                            
                            <span class="mx-3">
                                Sayfa {{ $movements->currentPage() }} / {{ $movements->lastPage() }}
                            </span>
                            
                            @if($movements->hasMorePages())
                                <a href="{{ $movements->appends(request()->query())->nextPageUrl() }}" class="btn btn-outline-primary btn-sm ms-2">
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
@endsection