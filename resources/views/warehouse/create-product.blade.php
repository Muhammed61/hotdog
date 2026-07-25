@extends('layouts.app')

@section('title', 'Depo Ürünü Ekle')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Depo Ürünü Ekle</h5>
                <a href="{{ route('warehouse.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Geri Dön
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('warehouse.products.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Ürün Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="initial_stock" class="form-label">Başlangıç Stok Miktarı <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('initial_stock') is-invalid @enderror" 
                                       id="initial_stock" name="initial_stock" value="{{ old('initial_stock', 0) }}" min="0" required>
                                @error('initial_stock')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="min_stock_level" class="form-label">Minimum Stok Seviyesi <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('min_stock_level') is-invalid @enderror" 
                                       id="min_stock_level" name="min_stock_level" value="{{ old('min_stock_level', 0) }}" min="0" required>
                                @error('min_stock_level')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Bu seviyenin altına düştüğünde uyarı verilecektir.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="description" class="form-label">Açıklama</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end">
                                <a href="{{ route('warehouse.index') }}" class="btn btn-secondary me-2">
                                    <i class="fas fa-times me-1"></i>İptal
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i>Ürün Ekle
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection