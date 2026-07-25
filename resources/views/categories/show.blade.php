@extends('layouts.app')

@section('title', 'Kategori Detayı')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Kategori Detayı</h4>
                    <div>
                        <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Düzenle
                        </a>
                        <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Geri Dön
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Kategori Adı:</th>
                                    <td>{{ $category->name }}</td>
                                </tr>
                                <tr>
                                    <th>Açıklama:</th>
                                    <td>{{ $category->description ?? 'Açıklama yok' }}</td>
                                </tr>
                                <tr>
                                    <th>Oluşturulma Tarihi:</th>
                                    <td>{{ $category->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Son Güncelleme:</th>
                                    <td>{{ $category->updated_at->format('d.m.Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Ürün Sayısı:</th>
                                    <td>{{ $category->products->count() }} ürün</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($category->products->count() > 0)
                    <hr>
                    <h5>Bu Kategorideki Ürünler</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Ürün Adı</th>
                                    <th>Stok</th>
                                    <th>Birim Fiyat</th>
                                    <th>Satış Fiyatı</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category->products as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>
                                        <span class="badge {{ $product->stock <= $product->min_stock ? 'bg-danger' : 'bg-success' }}">
                                            {{ $product->stock }} {{ $product->unit }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($product->cost_price, 2) }} ₺</td>
                                    <td>{{ number_format($product->selling_price, 2) }} ₺</td>
                                    <td>
                                        <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection