@extends('layouts.app')

@section('title', 'Depo Hareket Geçmişi')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Depo Hareket Geçmişi</h5>
                <a href="{{ route('warehouse.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Depo Yönetimine Dön
                </a>
            </div>
            <div class="card-body">
                <!-- Özet Bilgiler -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4>{{ $totalIn }}</h4>
                                <p class="mb-0">Toplam Giriş</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h4>{{ $totalOut }}</h4>
                                <p class="mb-0">Toplam Çıkış</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4>{{ $totalIn - $totalOut }}</h4>
                                <p class="mb-0">Net Hareket</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtreler -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="date_from" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="date_to" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-4">
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
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="type" class="form-label">Hareket Tipi</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Tümü</option>
                                <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Giriş</option>
                                <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Çıkış</option>
                            </select>
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i>Filtrele
                            </button>
                            <a href="{{ route('warehouse.movements') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Temizle
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Hareket Listesi -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Tarih</th>
                                <th>Ürün</th>
                                <th>İşlem</th>
                                <th>Miktar</th>
                                <th>Açıklama</th>
                                <th>Kullanıcı</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $movement)
                                <tr>
                                    <td>{{ $movement->created_at->format('d.m.Y H:i') }}</td>
                                    <td>
                                        <strong>{{ $movement->warehouseProduct->name ?? 'Silinmiş Ürün' }}</strong>
                                    </td>
                                    <td>
                                        @if($movement->type == 'in')
                                            <span class="badge bg-success">
                                                <i class="fas fa-plus me-1"></i>Giriş
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-minus me-1"></i>Çıkış
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $movement->quantity }}</strong> adet
                                    </td>
                                    <td>{{ $movement->reason }}</td>
                                    <td>
                                        <i class="fas fa-user me-1"></i>{{ $movement->user->name ?? 'Sistem' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Henüz hareket kaydı bulunmuyor.</p>
                                        <a href="{{ route('warehouse.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>İlk Hareketi Kaydet
                                        </a>
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
@endsection