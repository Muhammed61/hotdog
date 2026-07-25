@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-table me-2"></i>Masa Yönetimi</h5>
                <a href="{{ route('tables.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Yeni Masa
                </a>
            </div>
            <div class="card-body">
                @if($tables->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Masa Adı</th>
                                    <th>Kapasite</th>
                                    <th>Durum</th>
                                    <th>Aktif</th>
                                    <th>Oluşturulma</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tables as $table)
                                    <tr>
                                        <td>
                                            <strong>{{ $table->name }}</strong>
                                        </td>
                                        <td>
                                            <i class="fas fa-users me-1"></i>{{ $table->capacity }} kişi
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $table->status_color }}">
                                                {{ $table->status_text }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($table->is_active)
                                                <span class="badge bg-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger">Pasif</span>
                                            @endif
                                        </td>
                                        <td>{{ $table->created_at->format('d.m.Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('tables.edit', $table) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('tables.destroy', $table) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirmDelete('Bu masa silinecek!')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Sayfalama -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Toplam {{ $tables->total() }} masa
                        </div>
                        
                        <!-- Basit sayfalama butonları -->
                        <div class="btn-group" role="group">
                            @if($tables->currentPage() > 1)
                                <a href="{{ $tables->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-chevron-left"></i> Önceki
                                </a>
                            @endif
                            
                            <span class="btn btn-primary btn-sm">
                                Sayfa {{ $tables->currentPage() }} / {{ $tables->lastPage() }}
                            </span>
                            
                            @if($tables->hasMorePages())
                                <a href="{{ $tables->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                    Sonraki <i class="fas fa-chevron-right"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-table fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Henüz masa eklenmemiş</h5>
                        <p class="text-muted">İlk masanızı ekleyerek başlayın.</p>
                        <a href="{{ route('tables.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>İlk Masayı Ekle
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection