@extends('layouts.app')

@section('title', 'Görev Raporları')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Görev Raporları</h5>
                    <a href="{{ route('todos.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Geri Dön
                    </a>
                </div>
                <div class="card-body">
                    <!-- Özet Kartları -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Tamamlanan Görevler</h6>
                                            <h3>{{ $totalCompleted }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Bekleyen Görevler</h6>
                                            <h3>{{ $totalPending }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title">Devam Eden Görevler</h6>
                                            <h3>{{ $totalInProgress }}</h3>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-spinner fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtreler -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" action="{{ route('todos.reports') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="date_from" class="form-label">Başlangıç Tarihi</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="date_to" class="form-label">Bitiş Tarihi</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="status" class="form-label">Durum</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="">Tümü</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Bekleyen</option>
                                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Devam Eden</option>
                                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Tamamlanan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="priority" class="form-label">Öncelik</label>
                                        <select class="form-select" id="priority" name="priority">
                                            <option value="">Tümü</option>
                                            <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Düşük</option>
                                            <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Orta</option>
                                            <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Yüksek</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search me-1"></i>Filtrele
                                            </button>
                                            <a href="{{ route('todos.reports') }}" class="btn btn-secondary">
                                                <i class="fas fa-times me-1"></i>Temizle
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Öncelik Bazında Özet -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-flag me-2"></i>Öncelik Bazında Özet</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Öncelik</th>
                                                    <th>Toplam</th>
                                                    <th>Tamamlanan</th>
                                                    <th>Bekleyen</th>
                                                    <th>Devam Eden</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($prioritySummary as $priority)
                                                <tr>
                                                    <td>
                                                        <span class="badge {{ $priority->priority == 'high' ? 'bg-danger' : ($priority->priority == 'medium' ? 'bg-warning' : 'bg-secondary') }}">
                                                            {{ ucfirst($priority->priority) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $priority->total }}</td>
                                                    <td>{{ $priority->completed }}</td>
                                                    <td>{{ $priority->pending }}</td>
                                                    <td>{{ $priority->in_progress }}</td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">Veri bulunamadı</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Aylık Özet -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Aylık Özet</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Ay</th>
                                                    <th>Toplam</th>
                                                    <th>Tamamlanan</th>
                                                    <th>Başarı Oranı</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($monthlySummary as $month)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $month->month)->format('F Y') }}</td>
                                                    <td>{{ $month->total }}</td>
                                                    <td>{{ $month->completed }}</td>
                                                    <td>
                                                        @php
                                                            $percentage = $month->total > 0 ? round(($month->completed / $month->total) * 100) : 0;
                                                        @endphp
                                                        <span class="badge {{ $percentage >= 80 ? 'bg-success' : ($percentage >= 60 ? 'bg-warning' : 'bg-danger') }}">
                                                            %{{ $percentage }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Veri bulunamadı</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detaylı Görev Listesi -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-list me-2"></i>Detaylı Görev Listesi</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Görev</th>
                                            <th>Öncelik</th>
                                            <th>Durum</th>
                                            <th>Bitiş Tarihi</th>
                                            <th>Oluşturulma</th>
                                            <th>Tamamlanma</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($todos as $todo)
                                        <tr>
                                            <td>
                                                <strong>{{ $todo->title }}</strong>
                                                @if($todo->description)
                                                    <br><small class="text-muted">{{ Str::limit($todo->description, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $todo->getPriorityBadgeClass() }}">
                                                    {{ ucfirst($todo->priority) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $todo->getStatusBadgeClass() }}">
                                                    {{ $todo->getStatusText() }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($todo->due_date)
                                                    {{ $todo->due_date->format('d.m.Y') }}
                                                    @if($todo->isOverdue())
                                                        <i class="fas fa-exclamation-triangle text-danger ms-1"></i>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $todo->created_at->format('d.m.Y H:i') }}</td>
                                            <td>
                                                @if($todo->completed_at)
                                                    {{ $todo->completed_at->format('d.m.Y H:i') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <br>Görev bulunamadı
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Sayfalama -->
                            @if($todos->hasPages())
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <div class="text-muted">
                                        Toplam {{ $todos->total() }} görev
                                    </div>
                                    
                                    <!-- Basit sayfalama butonları -->
                                    <div class="btn-group" role="group">
                                        @if($todos->currentPage() > 1)
                                            <a href="{{ $todos->appends(request()->query())->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-chevron-left"></i> Önceki
                                            </a>
                                        @endif
                                        
                                        <span class="btn btn-primary btn-sm">
                                            Sayfa {{ $todos->currentPage() }} / {{ $todos->lastPage() }}
                                        </span>
                                        
                                        @if($todos->hasMorePages())
                                            <a href="{{ $todos->appends(request()->query())->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
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
        </div>
    </div>
</div>
@endsection