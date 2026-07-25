@extends('layouts.app')

@section('title', 
    isset($systemType) && $systemType === 'stock' ? 'Stok Sistemi Aktiviteleri' : 
    (isset($systemType) && $systemType === 'cafe' ? 'Kafe Sistemi Aktiviteleri' : 'Kullanıcı Aktiviteleri')
)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        @if(isset($systemType) && $systemType === 'stock')
                            Stok Sistemi Aktiviteleri
                        @elseif(isset($systemType) && $systemType === 'cafe')
                            Kafe Sistemi Aktiviteleri
                        @else
                            Kullanıcı Aktiviteleri
                        @endif
                    </h5>
                    <div>
                        <!-- Excel İndirme Butonu -->
                        <form method="GET" action="{{ 
                            isset($systemType) && $systemType === 'stock' ? route('reports.activities.stock') : 
                            (isset($systemType) && $systemType === 'cafe' ? route('reports.activities.cafe') : route('reports.activities.user'))
                        }}" class="d-inline">
                            @foreach(request()->except(['export']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <input type="hidden" name="export" value="excel">
                            <button type="submit" class="btn btn-success btn-sm me-2">
                                <i class="fas fa-file-excel me-1"></i>Excel İndir
                            </button>
                        </form>
                        
                        <!-- Aktivite Temizleme Butonu -->
                        @if(auth()->user()->isAdmin())
                            <button type="button" class="btn btn-danger btn-sm me-2" data-bs-toggle="modal" data-bs-target="#cleanupModal">
                                <i class="fas fa-trash-alt"></i> Aktiviteleri Temizle
                            </button>
                        @endif
                        <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Geri Dön
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filtreler -->
                    <form method="GET" action="{{ 
                        isset($systemType) && $systemType === 'stock' ? route('reports.activities.stock') : 
                        (isset($systemType) && $systemType === 'cafe' ? route('reports.activities.cafe') : route('reports.activities.user'))
                    }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-2">
                                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-2">
                                <label for="end_date" class="form-label">Bitiş Tarihi</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-2">
                                <label for="user_id" class="form-label">Kullanıcı</label>
                                <select class="form-control" id="user_id" name="user_id">
                                    <option value="">Tüm Kullanıcılar</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="action" class="form-label">İşlem Türü</label>
                                <select class="form-control" id="action" name="action">
                                    <option value="">Tüm İşlemler</option>
                                    <option value="create" {{ request('action') === 'create' ? 'selected' : '' }}>Oluşturma</option>
                                    <option value="update" {{ request('action') === 'update' ? 'selected' : '' }}>Güncelleme</option>
                                    <option value="delete" {{ request('action') === 'delete' ? 'selected' : '' }}>Silme</option>
                                </select>
                            </div>
                            @if(isset($systemType) && $systemType === 'cafe' && isset($products))
                            <div class="col-md-2">
                                <label for="product_id" class="form-label">Ürün</label>
                                <select class="form-control" id="product_id" name="product_id">
                                    <option value="">Tüm Ürünler</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                            {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-{{ isset($systemType) && $systemType === 'cafe' && isset($products) ? '2' : '4' }} d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-filter"></i> Filtrele
                                </button>
                                <a href="{{ 
                                    isset($systemType) && $systemType === 'stock' ? route('reports.activities.stock') : 
                                    (isset($systemType) && $systemType === 'cafe' ? route('reports.activities.cafe') : route('reports.activities.user'))
                                }}" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> Sıfırla
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Özet Bilgiler -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Toplam Aktivite</h6>
                                            <h4>{{ $summary['total'] }}</h4>
                                        </div>
                                        <div>
                                            <i class="fas fa-chart-line fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Oluşturma</h6>
                                            <h4>{{ $summary['create'] }}</h4>
                                        </div>
                                        <div>
                                            <i class="fas fa-plus fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Güncelleme</h6>
                                            <h4>{{ $summary['update'] }}</h4>
                                        </div>
                                        <div>
                                            <i class="fas fa-edit fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Silme</h6>
                                            <h4>{{ $summary['delete'] }}</h4>
                                        </div>
                                        <div>
                                            <i class="fas fa-trash fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    @if(isset($systemType) && $systemType === 'cafe' && isset($waiterProductStats))
                    <!-- Garson Bazında Ürün Sipariş İstatistikleri -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i>Garson Bazında Ürün Sipariş İstatistikleri</h6>
                            <div class="d-flex align-items-center">
                                <label for="stats_per_page" class="form-label me-2 mb-0 text-white">Sayfa başına:</label>
                                <select id="stats_per_page" class="form-select form-select-sm" style="width: auto;" onchange="changeStatsPerPage(this.value)">
                                    <option value="10" {{ request('stats_per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ request('stats_per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                                    <option value="50" {{ request('stats_per_page', 20) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('stats_per_page', 20) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            @if($waiterProductStats->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Garson</th>
                                                <th>Ürün</th>
                                                <th class="text-end">Toplam Miktar</th>
                                                <th class="text-end">Sipariş Sayısı</th>
                                                <th class="text-end">Toplam Gelir</th>
                                                <th>Son Sipariş</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $currentUser = null;
                                                $index = 0;
                                            @endphp
                                            @foreach($waiterProductStats as $stat)
                                                @php
                                                    if ($currentUser !== $stat->user_name) {
                                                        $currentUser = $stat->user_name;
                                                        $index++;
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>{{ $index }}</td>
                                                    <td>
                                                        @if($currentUser === $stat->user_name)
                                                            <strong class="text-primary">{{ $stat->user_name }}</strong>
                                                        @endif
                                                    </td>
                                                    <td>{{ $stat->product_name }}</td>
                                                    <td class="text-end">
                                                        <span class="badge bg-primary">{{ $stat->total_quantity }} adet</span>
                                                    </td>
                                                    <td class="text-end">{{ $stat->order_count }} sipariş</td>
                                                    <td class="text-end"><strong>{{ number_format($stat->total_revenue, 2) }} ₺</strong></td>
                                                    <td>{{ \Carbon\Carbon::parse($stat->last_order_date)->format('d.m.Y H:i') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-secondary">
                                            <tr>
                                                <th colspan="3">TOPLAM</th>
                                                <th class="text-end">
                                                    <span class="badge bg-success">{{ $waiterProductStats->sum('total_quantity') }} adet</span>
                                                </th>
                                                <th class="text-end">{{ $waiterProductStats->sum('order_count') }} sipariş</th>
                                                <th class="text-end"><strong>{{ number_format($waiterProductStats->sum('total_revenue'), 2) }} ₺</strong></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                <!-- Sayfalama -->
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div class="text-muted">
                                        Toplam {{ $waiterProductStats->total() }} kayıt ({{ $waiterProductStats->firstItem() }}-{{ $waiterProductStats->lastItem() }} arası gösteriliyor)
                                    </div>
                                    
                                    <!-- Basit sayfalama butonları -->
                                    <div class="btn-group" role="group">
                                        @if($waiterProductStats->onFirstPage())
                                            <span class="btn btn-outline-secondary btn-sm disabled">
                                                <i class="fas fa-chevron-left"></i> Önceki
                                            </span>
                                        @else
                                            <a href="{{ $waiterProductStats->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-chevron-left"></i> Önceki
                                            </a>
                                        @endif
                                        
                                        <span class="btn btn-primary btn-sm">
                                            Sayfa {{ $waiterProductStats->currentPage() }} / {{ $waiterProductStats->lastPage() }}
                                        </span>
                                        
                                        @if($waiterProductStats->hasMorePages())
                                            <a href="{{ $waiterProductStats->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                                Sonraki <i class="fas fa-chevron-right"></i>
                                            </a>
                                        @else
                                            <span class="btn btn-outline-secondary btn-sm disabled">
                                                Sonraki <i class="fas fa-chevron-right"></i>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <p class="text-muted text-center mb-0">Seçilen kriterlere uygun veri bulunamadı.</p>
                            @endif
                        </div>
                    </div>
                    @endif
                    <!-- Detaylı Aktivite Listesi -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Detaylı Aktivite Listesi</h6>
                            
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
                            @if($activities->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tarih/Saat</th>
                                                <th>Kullanıcı</th>
                                                <th>İşlem</th>
                                                <th>Açıklama</th>
                                                <th>IP Adresi</th>
                                                <th>Cihaz Bilgisi</th>
                                                <th>Detay</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($activities as $activity)
                                                <tr>
                                                    <td>
                                                        <div>{{ $activity->created_at->format('d.m.Y') }}</div>
                                                        <small class="text-muted">{{ $activity->created_at->format('H:i:s') }}</small>
                                                    </td>
                                                    <td>
                                                        <strong>{{ $activity->user->name }}</strong><br>
                                                        <small class="text-muted">{{ $activity->user->email }}</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ 
                                                            $activity->action === 'create' ? 'success' : 
                                                            ($activity->action === 'update' ? 'warning' : 
                                                            ($activity->action === 'delete' ? 'danger' : 'info')) 
                                                        }}">
                                                            {{ $activity->action_name }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $activity->description }}</td>
                                                    <td>
                                                        <code>{{ $activity->ip_address }}</code>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <i class="fas fa-{{ 
                                                                $activity->device_type === 'mobile' ? 'mobile-alt' : 
                                                                ($activity->device_type === 'tablet' ? 'tablet-alt' : 'desktop') 
                                                            }}"></i>
                                                            {{ ucfirst($activity->device_type) }}<br>
                                                            {{ $activity->browser }} / {{ $activity->platform }}
                                                        </small>
                                                    </td>
                                                    <td>
                                                        @if($activity->old_values || $activity->new_values)
                                                            <button class="btn btn-sm btn-outline-info" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#activityModal{{ $activity->id }}">
                                                                <i class="fas fa-eye"></i> Detay
                                                            </button>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Sayfalama - Kasa raporları tarzında -->
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div class="text-muted">
                                        Toplam {{ $activities->total() }} aktivite ({{ $activities->firstItem() }}-{{ $activities->lastItem() }} arası gösteriliyor)
                                    </div>
                                    
                                    <!-- Basit sayfalama butonları -->
                                    <div class="btn-group" role="group">
                                        @if($activities->onFirstPage())
                                            <span class="btn btn-outline-secondary btn-sm disabled">
                                                <i class="fas fa-chevron-left"></i> Önceki
                                            </span>
                                        @else
                                            <a href="{{ $activities->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-chevron-left"></i> Önceki
                                            </a>
                                        @endif
                                        
                                        <span class="btn btn-primary btn-sm">
                                            Sayfa {{ $activities->currentPage() }} / {{ $activities->lastPage() }}
                                        </span>
                                        
                                        @if($activities->hasMorePages())
                                            <a href="{{ $activities->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                                Sonraki <i class="fas fa-chevron-right"></i>
                                            </a>
                                        @else
                                            <span class="btn btn-outline-secondary btn-sm disabled">
                                                Sonraki <i class="fas fa-chevron-right"></i>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Seçilen kriterlere uygun aktivite bulunamadı.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Aktivite Detay Modalları -->
@foreach($activities as $activity)
    @if($activity->old_values || $activity->new_values)
        <div class="modal fade" id="activityModal{{ $activity->id }}" tabindex="-1" aria-labelledby="activityModalLabel{{ $activity->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="activityModalLabel{{ $activity->id }}">
                            <i class="fas fa-info-circle me-2"></i>Aktivite Detayı
                        </h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                    </div>
                    <div class="modal-body">
                        <h6><i class="fas fa-user me-2"></i>Genel Bilgiler</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <td width="150"><strong>Kullanıcı:</strong></td>
                                    <td>{{ $activity->user->name }} ({{ $activity->user->email }})</td>
                                </tr>
                                <tr>
                                    <td><strong>İşlem:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ 
                                            $activity->action === 'create' ? 'success' : 
                                            ($activity->action === 'update' ? 'warning' : 
                                            ($activity->action === 'delete' ? 'danger' : 'info')) 
                                        }}">
                                            {{ $activity->action_name }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Tarih:</strong></td>
                                    <td>{{ $activity->created_at->format('d.m.Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>IP Adresi:</strong></td>
                                    <td><code>{{ $activity->ip_address }}</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Cihaz:</strong></td>
                                    <td>
                                        <i class="fas fa-{{ 
                                            $activity->device_type === 'mobile' ? 'mobile-alt' : 
                                            ($activity->device_type === 'tablet' ? 'tablet-alt' : 'desktop') 
                                        }}"></i>
                                        {{ ucfirst($activity->device_type) }} - {{ $activity->browser }} / {{ $activity->platform }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Açıklama:</strong></td>
                                    <td>{{ $activity->description }}</td>
                                </tr>
                            </table>
                        </div>

                        @if($activity->old_values && $activity->action === 'update')
                            <h6 class="mt-4"><i class="fas fa-history me-2"></i>Eski Değerler</h6>
                            <div class="bg-light p-3 rounded">
                                <pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($activity->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif

                        @if($activity->new_values)
                            <h6 class="mt-4">
                                <i class="fas fa-{{ $activity->action === 'update' ? 'edit' : 'plus' }} me-2"></i>
                                {{ $activity->action === 'update' ? 'Yeni Değerler' : 'Değerler' }}
                            </h6>
                            <div class="bg-light p-3 rounded">
                                <pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($activity->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Kapat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

<!-- Aktivite Temizleme Modalı -->
@if(auth()->user()->isAdmin())
<div class="modal fade" id="cleanupModal" tabindex="-1" aria-labelledby="cleanupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cleanupModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Aktivite Temizleme
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form id="cleanupForm" method="POST" action="{{ route('reports.activities.cleanup') }}">
                @csrf
                @if(isset($systemType))
                    <input type="hidden" name="system_type" value="{{ $systemType }}">
                @endif
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Dikkat!</strong> Bu işlem geri alınamaz. Seçilen kriterlere uygun tüm aktivite kayıtları silinecektir.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="cleanup_start_date" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="cleanup_start_date" name="start_date" required>
                            <small class="text-muted">Bu tarihten itibaren</small>
                        </div>
                        <div class="col-md-6">
                            <label for="cleanup_end_date" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="cleanup_end_date" name="end_date" required>
                            <small class="text-muted">Bu tarihe kadar</small>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label for="cleanup_action" class="form-label">İşlem Türü (İsteğe Bağlı)</label>
                        <select class="form-control" id="cleanup_action" name="action">
                            <option value="">Tüm İşlemler</option>
                            <option value="create">Sadece Oluşturma</option>
                            <option value="update">Sadece Güncelleme</option>
                            <option value="delete">Sadece Silme</option>
                        </select>
                    </div>
                    
                    <div class="mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmCleanup" required>
                            <label class="form-check-label text-danger" for="confirmCleanup">
                                <strong>Bu işlemin geri alınamayacağını anlıyorum ve onaylıyorum</strong>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>İptal
                    </button>
                    <button type="submit" class="btn btn-danger" id="cleanupSubmitBtn" disabled>
                        <i class="fas fa-trash-alt me-1"></i>Aktiviteleri Sil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
function changePerPage(value) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page'); // Sayfa numarasını sıfırla
    window.location.href = url.toString();
}

// Temizleme modalı için JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheckbox = document.getElementById('confirmCleanup');
    const submitBtn = document.getElementById('cleanupSubmitBtn');
    const cleanupForm = document.getElementById('cleanupForm');
    
    if (confirmCheckbox && submitBtn) {
        confirmCheckbox.addEventListener('change', function() {
            submitBtn.disabled = !this.checked;
        });
    }
    
    if (cleanupForm) {
        cleanupForm.addEventListener('submit', function(e) {
            if (!confirm('Bu işlem geri alınamaz! Devam etmek istediğinizden emin misiniz?')) {
                e.preventDefault();
            }
        });
    }
});
</script>
@endsection
