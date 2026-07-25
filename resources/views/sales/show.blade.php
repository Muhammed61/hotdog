@extends('layouts.app')

@section('title', 'Satış Detayı')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Satış Detayı #{{ $sale->id }}</h5>
                    <div>
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fas fa-print"></i> Yazdır
                        </button>
                        <a href="{{ route('sales.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Geri Dön
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Satış Bilgileri</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Satış No:</th>
                                    <td>#{{ $sale->id }}</td>
                                </tr>
                                <tr>
                                    <th>Tarih:</th>
                                    <td>{{ $sale->created_at->format('d.m.Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Toplam Tutar:</th>
                                    <td><strong>{{ number_format($sale->total_amount, 2) }} ₺</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h6>Satılan Ürünler</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Ürün Adı</th>
                                    <th>Birim Fiyat</th>
                                    <th>Miktar</th>
                                    <th>Toplam</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sale->saleItems as $item)
                                    <tr>
                                        <td>{{ $item->product->name }}</td>
                                        <td>{{ number_format($item->product->sale_price, 2) }} ₺</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->price * $item->quantity, 2) }} ₺</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3">Genel Toplam</th>
                                    <th>{{ number_format($sale->total_amount, 2) }} ₺</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection