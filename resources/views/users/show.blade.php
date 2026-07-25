@extends('layouts.app')

@section('title', 'Kullanıcı Detayı')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Kullanıcı Bilgileri</h5>
            </div>
            <div class="card-body text-center">
                <div class="avatar-circle mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="fas fa-user"></i>
                </div>
                <h4>{{ $user->name }}</h4>
                <p class="text-muted">{{ $user->email }}</p>
                
                <div class="row text-center">
                    <div class="col-6">
                        <span class="badge {{ $user->role == 'admin' ? 'bg-danger' : 'bg-primary' }} fs-6">
                            {{ $user->role == 'admin' ? 'Admin' : 'Kullanıcı' }}
                        </span>
                    </div>
                    <div class="col-6">
                        <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }} fs-6">
                            {{ $user->is_active ? 'Aktif' : 'Pasif' }}
                        </span>
                    </div>
                </div>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <h5 class="text-primary">{{ $user->stockMovements->count() }}</h5>
                        <small class="text-muted">Stok Hareketi</small>
                    </div>
                    <div class="col-6">
                        <h5 class="text-success">{{ $user->sales->count() }}</h5>
                        <small class="text-muted">Satış</small>
                    </div>
                </div>
                
                <hr>
                
                <p class="text-muted mb-1">Kayıt Tarihi</p>
                <p>{{ $user->created_at->setTimezone('Europe/Istanbul')->format('d.m.Y H:i') }}</p>
                
                <div class="d-grid gap-2">
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i>Düzenle
                    </a>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Geri
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Son Aktiviteler</h5>
                
                <!-- Sayfalama Miktarı Seçici -->
                <div class="d-flex align-items-center">
                    <label for="per_page" class="form-label me-2 mb-0">Sayfa başına:</label>
                    <select id="per_page" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                        <option value="5" {{ request('per_page', 10) == 5 ? 'selected' : '' }}>5</option>
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                        <option value="15" {{ request('per_page', 10) == 15 ? 'selected' : '' }}>15</option>
                        <option value="20" {{ request('per_page', 10) == 20 ? 'selected' : '' }}>20</option>
                        <option value="25" {{ request('per_page', 10) == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page', 10) == 50 ? 'selected' : '' }}>50</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                @if($recentActivities->count() > 0)
                    <div class="timeline">
                        @foreach($recentActivities as $activity)
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="timeline-marker me-3">
                                        @if($activity['type'] == 'stock_movement')
                                            <i class="fas fa-box text-primary"></i>
                                        @else
                                            <i class="fas fa-shopping-cart text-success"></i>
                                        @endif
                                    </div>
                                    <div class="timeline-content">
                                        @if($activity['type'] == 'stock_movement')
                                            <h6 class="mb-1">Stok Hareketi</h6>
                                            <p class="mb-1">
                                                <strong>{{ $activity['data']->product->name }}</strong> - 
                                                {{ $activity['data']->type == 'in' ? 'Giriş' : 'Çıkış' }}: {{ $activity['data']->quantity }} adet
                                            </p>
                                            <small class="text-muted">{{ $activity['data']->reason }}</small>
                                        @else
                                            <h6 class="mb-1">Satış</h6>
                                            <p class="mb-1">
                                                Toplam: <strong>{{ number_format($activity['data']->total_amount, 2) }} ₺</strong>
                                            </p>
                                        @endif
                                        <small class="text-muted d-block">
                                            {{ $activity['data']->created_at->setTimezone('Europe/Istanbul')->format('d.m.Y H:i:s') }}
                                            ({{ $activity['data']->created_at->setTimezone('Europe/Istanbul')->diffForHumans() }})
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Sayfalama -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Toplam {{ $stockMovements->total() + $sales->total() }} aktivite
                        </div>
                        
                        <!-- Basit sayfalama butonları -->
                        <div class="btn-group" role="group">
                            @if(request('page', 1) > 1)
                                <a href="{{ request()->fullUrlWithQuery(['page' => request('page', 1) - 1]) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-chevron-left"></i> Önceki
                                </a>
                            @endif
                            
                            <span class="btn btn-primary btn-sm">
                                Sayfa {{ request('page', 1) }}
                            </span>
                            
                            @if($stockMovements->hasMorePages() || $sales->hasMorePages())
                                <a href="{{ request()->fullUrlWithQuery(['page' => request('page', 1) + 1]) }}" class="btn btn-outline-primary btn-sm">
                                    Sonraki <i class="fas fa-chevron-right"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Henüz aktivite bulunmuyor.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.timeline-marker {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #e9ecef;
}

.timeline-content {
    flex: 1;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    border-left: 3px solid var(--primary-color);
}
</style>

<script>
function changePerPage(value) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page'); // Sayfa numarasını sıfırla
    window.location.href = url.toString();
}
</script>
@endsection