@extends('layouts.app')

@section('title', 'Raporlar')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Raporlar</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                                    <h6>Satış Raporları</h6>
                                    <p class="text-muted">Günlük, haftalık ve aylık satış raporları</p>
                                    <a href="{{ route('reports.sales') }}" class="btn btn-primary">Görüntüle</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-boxes fa-3x text-success mb-3"></i>
                                    <h6>Stok Raporları</h6>
                                    <p class="text-muted">Mevcut stok durumu ve düşük stok uyarıları</p>
                                    <a href="{{ route('reports.stock') }}" class="btn btn-success">Görüntüle</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <i class="fas fa-exchange-alt fa-3x text-warning mb-3"></i>
                                    <h6>Stok Hareketleri</h6>
                                    <p class="text-muted">Stok giriş ve çıkış hareketleri</p>
                                    <a href="{{ route('reports.movements') }}" class="btn btn-warning">Görüntüle</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <i class="fas fa-dollar-sign fa-3x text-info mb-3"></i>
                                    <h6>Kar Raporları</h6>
                                    <p class="text-muted">Kar-zarar analizi ve karlılık raporları</p>
                                    <a href="{{ route('reports.profit') }}" class="btn btn-info">Görüntüle</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <div class="card border-secondary">
                                <div class="card-body text-center">
                                    <i class="fas fa-coffee fa-3x text-secondary mb-3"></i>
                                    <h6>Kafe Satış Raporları</h6>
                                    <p class="text-muted">Kafe sistemi sipariş ve satış raporları</p>
                                    <a href="{{ route('reports.cafe') }}" class="btn btn-secondary">Görüntüle</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card border-dark">
                                <div class="card-body text-center">
                                    <i class="fas fa-cash-register fa-3x text-dark mb-3"></i>
                                    <h6>Kasa Raporları</h6>
                                    <p class="text-muted">Kasa işlemleri ve kullanıcı bazında raporlar</p>
                                    <a href="{{ route('reports.cash') }}" class="btn btn-dark">Görüntüle</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-cog fa-3x text-danger mb-3"></i>
                                    <h6>Stok Sistemi Aktiviteleri</h6>
                                    <p class="text-muted">Stok takip sistemindeki kullanıcı aktiviteleri</p>
                                    <a href="{{ route('reports.activities.stock') }}" class="btn btn-danger">Görüntüle</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="card border-purple" style="border-color: #6f42c1 !important;">
                                <div class="card-body text-center">
                                    <i class="fas fa-users-cog fa-3x mb-3" style="color: #6f42c1;"></i>
                                    <h6>Kafe Sistemi Aktiviteleri</h6>
                                    <p class="text-muted">Kafe sistemindeki kullanıcı aktiviteleri</p>
                                    <a href="{{ route('reports.activities.cafe') }}" class="btn btn-outline-purple" style="color: #6f42c1; border-color: #6f42c1;">Görüntüle</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-outline-purple:hover {
    background-color: #6f42c1;
    color: white;
}
</style>
@endsection