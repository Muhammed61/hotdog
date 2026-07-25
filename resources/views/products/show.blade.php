@extends('layouts.app')

@section('title', 'Ürün Detayı')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ürün Detayı</h5>
                    <div>
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Düzenle
                        </a>
                        <a href="{{ route('products.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Geri Dön
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Ürün Adı:</th>
                                    <td>{{ $product->name }}</td>
                                </tr>
                                <tr>
                                    <th>Kategori:</th>
                                    <td>{{ $product->category->name }}</td>
                                </tr>
                                <tr>
                                    <th>Fiyat:</th>
                                    <td>{{ number_format($product->sale_price, 2) }} ₺</td>
                                </tr>
                                <tr>
                                    <th>Mevcut Stok:</th>
                                    <td>
                                        <span class="badge {{ $product->stock <= $product->min_stock ? 'bg-danger' : 'bg-success' }} fs-6">
                                            {{ $product->stock_quantity }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Minimum Stok:</th>
                                    <td>{{ $product->min_stock_level }}</td>
                                </tr>
                                <tr>
                                    <th>Durum:</th>
                                    <td>
                                        <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $product->is_active ? 'Aktif' : 'Pasif' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Oluşturulma:</th>
                                    <td>{{ $product->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            @if($product->description)
                                <h6>Açıklama:</h6>
                                <p>{{ $product->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Stok Hareketleri</h6>
                </div>
                <div class="card-body">
                    @forelse($product->stockMovements()->latest()->take(10)->get() as $movement)
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                            <div>
                                <small class="text-muted">{{ $movement->created_at->format('d.m.Y H:i') }}</small><br>
                                <span class="badge {{ $movement->type == 'in' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $movement->type == 'in' ? '+' : '-' }}{{ $movement->quantity }}
                                </span>
                            </div>
                            <div class="text-end">
                                @if($movement->notes)
                                    <small class="text-muted">{{ $movement->notes }}</small>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">Henüz stok hareketi bulunmuyor.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection