@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>İşlem Detayı
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Kasa Türü</h6>
                            <p class="mb-3">
                                <i class="fas fa-{{ $transaction->cash_type === 'stock' ? 'boxes' : 'coffee' }} me-2"></i>
                                {{ $transaction->cash_type_text }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">İşlem Türü</h6>
                            <p class="mb-3">
                                <span class="badge bg-{{ $transaction->transaction_type_color }} fs-6">
                                    <i class="{{ $transaction->transaction_type_icon }} me-1"></i>
                                    {{ $transaction->transaction_type_text }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Tutar</h6>
                            <p class="mb-3">
                                <strong class="text-{{ $transaction->transaction_type === 'income' ? 'success' : 'danger' }} fs-4">
                                    {{ $transaction->transaction_type === 'income' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} ₺
                                </strong>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">İşlem Tarihi</h6>
                            <p class="mb-3">
                                <i class="fas fa-calendar me-2"></i>{{ $transaction->created_at->format('d.m.Y H:i') }}
                            </p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted">Açıklama</h6>
                            <p class="mb-3">{{ $transaction->description }}</p>
                        </div>
                    </div>

                    @if($transaction->notes)
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-muted">Notlar</h6>
                            <div class="bg-light p-3 rounded">
                                {{ $transaction->notes }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">İşlemi Yapan</h6>
                            <p class="mb-3">
                                <i class="fas fa-user me-2"></i>{{ $transaction->user->name }}
                                <span class="badge bg-secondary ms-2">{{ $transaction->user->role_name }}</span>
                            </p>
                        </div>
                    </div>

                    <hr>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('cash-register.transactions', $transaction->cash_type) }}" class="btn btn-secondary me-md-2">
                            <i class="fas fa-arrow-left me-1"></i>İşlem Listesine Dön
                        </a>
                        <a href="{{ route('cash-register.index') }}" class="btn btn-primary">
                            <i class="fas fa-home me-1"></i>Ana Sayfa
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection