@extends('layouts.app')

@section('title', 'Kategoriler')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Kategoriler</h1>
    <a href="{{ route('categories.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Yeni Kategori
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($categories->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Kategori Adı</th>
                            <th>Açıklama</th>
                            <th>Ürün Sayısı</th>
                            <th>Durum</th>
                            <th>Oluşturulma</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>
                                <a href="{{ route('categories.show', $category) }}" class="text-decoration-none fw-bold">
                                    {{ $category->name }}
                                </a>
                            </td>
                            <td>{{ Str::limit($category->description, 50) }}</td>
                            <td>
                                <span class="badge bg-info">{{ $category->products_count }}</span>
                            </td>
                            <td>
                                @if($category->is_active)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Pasif</span>
                                @endif
                            </td>
                            <td>{{ $category->created_at->format('d.m.Y') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('categories.show', $category) }}" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-outline-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
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
                    Toplam {{ $categories->total() }} kategori
                </div>
                
                <!-- Basit sayfalama butonları -->
                <div class="btn-group" role="group">
                    @if($categories->currentPage() > 1)
                        <a href="{{ $categories->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-chevron-left"></i> Önceki
                        </a>
                    @endif
                    
                    <span class="btn btn-primary btn-sm">
                        Sayfa {{ $categories->currentPage() }} / {{ $categories->lastPage() }}
                    </span>
                    
                    @if($categories->hasMorePages())
                        <a href="{{ $categories->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                            Sonraki <i class="fas fa-chevron-right"></i>
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Henüz kategori eklenmemiş</h5>
                <p class="text-muted">İlk kategoriyi eklemek için "Yeni Kategori" butonuna tıklayın.</p>
                <a href="{{ route('categories.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Yeni Kategori
                </a>
            </div>
        @endif
    </div>
</div>
@endsection