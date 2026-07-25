@extends('layouts.app')

@section('title', 'Satışlar')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Satışlar</h5>
                    <a href="{{ route('sales.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Yeni Satış
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($sales->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Satış No</th>
                                        <th>Tarih</th>
                                        <th>Toplam Tutar</th>
                                        <th>Ürün Sayısı</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sales as $sale)
                                        <tr>
                                            <td>#{{ $sale->id }}</td>
                                            <td>{{ $sale->created_at->format('d.m.Y H:i') }}</td>
                                            <td>{{ number_format($sale->total_amount, 2) }} ₺</td>
                                            <td>{{ $sale->saleItems->count() }}</td>
                                            <td>
                                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> Detay
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Sayfalama -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Toplam {{ $sales->total() }} satış
                            </div>
                            
                            <!-- Basit sayfalama butonları -->
                            <div class="btn-group" role="group">
                                @if($sales->currentPage() > 1)
                                    <a href="{{ $sales->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-chevron-left"></i> Önceki
                                    </a>
                                @endif
                                
                                <span class="btn btn-primary btn-sm">
                                    Sayfa {{ $sales->currentPage() }} / {{ $sales->lastPage() }}
                                </span>
                                
                                @if($sales->hasMorePages())
                                    <a href="{{ $sales->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                        Sonraki <i class="fas fa-chevron-right"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Henüz satış bulunmuyor</h5>
                            <p class="text-muted">İlk satışı yapmak için "Yeni Satış" butonuna tıklayın.</p>
                            <a href="{{ route('sales.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Yeni Satış
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection