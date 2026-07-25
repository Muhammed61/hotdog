@extends('layouts.app')

@section('title', 'Yapılacaklar Listesi')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Yapılacaklar Listesi</h5>
                    <div>
                        <button type="button" class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#addTodoModal">
                            <i class="fas fa-plus me-1"></i>Yeni Görev
                        </button>
                        <a href="{{ route('todos.reports') }}" class="btn btn-info btn-sm me-2">
                            <i class="fas fa-chart-bar me-1"></i>Raporlar
                        </a>
                        <a href="{{ route('todos.movements') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-history me-1"></i>Hareket Geçmişi
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- İstatistikler -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['total'] }}</h4>
                                            <small>Toplam</small>
                                        </div>
                                        <i class="fas fa-list fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['pending'] }}</h4>
                                            <small>Bekleyen</small>
                                        </div>
                                        <i class="fas fa-clock fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['in_progress'] }}</h4>
                                            <small>Devam Eden</small>
                                        </div>
                                        <i class="fas fa-spinner fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['completed'] }}</h4>
                                            <small>Tamamlanan</small>
                                        </div>
                                        <i class="fas fa-check fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['overdue'] }}</h4>
                                            <small>Geciken</small>
                                        </div>
                                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-dark text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4 class="mb-0">{{ $stats['today'] }}</h4>
                                            <small>Bugün</small>
                                        </div>
                                        <i class="fas fa-calendar-day fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>

                    <!-- Filtreler -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" action="{{ route('todos.index') }}" class="row g-3">
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Durum</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">Tümü</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Bekleyen</option>
                                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Devam Eden</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Tamamlanan</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="priority" class="form-label">Öncelik</label>
                                    <select name="priority" id="priority" class="form-select">
                                        <option value="">Tümü</option>
                                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Yüksek</option>
                                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Orta</option>
                                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Düşük</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="filter_type" class="form-label">Zaman Filtresi</label>
                                    <select name="filter_type" id="filter_type" class="form-select">
                                        <option value="">Tümü</option>
                                        <option value="today" {{ request('filter_type') == 'today' ? 'selected' : '' }}>Bugün Yapılacaklar</option>
                                        <option value="this_week" {{ request('filter_type') == 'this_week' ? 'selected' : '' }}>Bu Hafta Yapılacaklar</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="date" class="form-label">Tarih</label>
                                    <input type="date" name="date" id="date" class="form-control" value="{{ request('date') }}">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-outline-primary me-2">
                                        <i class="fas fa-filter me-1"></i>Filtrele
                                    </button>
                                    <a href="{{ route('todos.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Temizle
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Görevler Listesi -->
                    <div class="card">
                        <div class="card-body">
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show">
                                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th>Görev</th>
                                            <th width="10%">Öncelik</th>
                                            <th width="12%">Durum</th>
                                            <th width="12%">Bitiş Tarihi</th>
                                            <th width="15%">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($todos as $todo)
                                            <tr data-todo-id="{{ $todo->id }}" class="{{ $todo->isOverdue() ? 'table-danger' : ($todo->isDueToday() ? 'table-warning' : '') }}">
                                                <td>
                                                    <input type="checkbox" class="form-check-input todo-checkbox" value="{{ $todo->id }}">
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="form-check me-2">
                                                            <input type="checkbox" class="form-check-input status-toggle" 
                                                                   data-todo-id="{{ $todo->id }}" 
                                                                   {{ $todo->status === 'completed' ? 'checked' : '' }}>
                                                        </div>
                                                        <div class="{{ $todo->status === 'completed' ? 'text-decoration-line-through text-muted' : '' }}">
                                                            <strong>{{ $todo->title }}</strong>
                                                            @if($todo->description)
                                                                <br><small class="text-muted">{{ Str::limit($todo->description, 100) }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $todo->getPriorityBadgeClass() }}">
                                                        {{ $todo->getPriorityText() }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $todo->getStatusBadgeClass() }}">
                                                        @switch($todo->status)
                                                            @case('pending') Bekleyen @break
                                                            @case('in_progress') Devam Eden @break
                                                            @case('completed') Tamamlandı @break
                                                        @endswitch
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($todo->due_date)
                                                        <small class="{{ $todo->isOverdue() ? 'text-danger fw-bold' : ($todo->isDueToday() ? 'text-warning fw-bold' : '') }}">
                                                            {{ $todo->due_date->format('d.m.Y') }}
                                                            @if($todo->isOverdue())
                                                                <i class="fas fa-exclamation-triangle ms-1"></i>
                                                            @elseif($todo->isDueToday())
                                                                <i class="fas fa-clock ms-1"></i>
                                                            @endif
                                                        </small>
                                                    @else
                                                        <small class="text-muted">Tarih yok</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary edit-todo" 
                                                                data-todo-id="{{ $todo->id }}"
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editTodoModal">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger delete-todo" 
                                                                data-todo-id="{{ $todo->id }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">Henüz görev eklenmemiş.</p>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
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
</div>

<!-- Yeni Görev Modal -->
<div class="modal fade" id="addTodoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addTodoForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Görev Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addTitle" class="form-label">Görev Başlığı *</label>
                        <input type="text" class="form-control" id="addTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="addDescription" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="addDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="addPriority" class="form-label">Öncelik *</label>
                            <select class="form-select" id="addPriority" name="priority" required>
                                <option value="medium" selected>Orta</option>
                                <option value="high">Yüksek</option>
                                <option value="low">Düşük</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="addDueDate" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="addDueDate" name="due_date" min="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Düzenle Modal -->
<div class="modal fade" id="editTodoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editTodoForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="editTodoId">
                <div class="modal-header">
                    <h5 class="modal-title">Görev Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editTitle" class="form-label">Görev Başlığı *</label>
                        <input type="text" class="form-control" id="editTitle" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="editPriority" class="form-label">Öncelik *</label>
                            <select class="form-select" id="editPriority" name="priority" required>
                                <option value="high">Yüksek</option>
                                <option value="medium">Orta</option>
                                <option value="low">Düşük</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="editStatus" class="form-label">Durum *</label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="pending">Bekleyen</option>
                                <option value="in_progress">Devam Eden</option>
                                <option value="completed">Tamamlandı</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="editDueDate" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="editDueDate" name="due_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Sayfa başına gösterim miktarını değiştir
function changePerPage(perPage) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', perPage);
    url.searchParams.delete('page'); // Sayfa numarasını sıfırla
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    // Yeni görev ekleme
    document.getElementById('addTodoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Kaydediliyor...';
        
        fetch('{{ route("todos.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addTodoModal')).hide();
                location.reload();
            } else {
                throw new Error(data.message || 'Bir hata oluştu');
            }
        })
        .catch(error => {
            alert('Hata: ' + error.message);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    // Görev düzenleme
    document.querySelectorAll('.edit-todo').forEach(btn => {
        btn.addEventListener('click', function() {
            const todoId = this.dataset.todoId;
            
            // AJAX ile görev bilgilerini çek
            fetch(`/todos/${todoId}/edit`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.status === 403) {
                    throw new Error('Bu görevi düzenleme yetkiniz yok!');
                }
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const todo = data.todo;
                    
                    // Debug için console'a yazdır
                    console.log('Todo data:', todo);
                    console.log('Due date:', todo.due_date);
                    
                    // Modal alanlarını doldur
                    document.getElementById('editTodoId').value = todoId;
                    document.getElementById('editTitle').value = todo.title;
                    document.getElementById('editDescription').value = todo.description || '';
                    document.getElementById('editPriority').value = todo.priority;
                    document.getElementById('editStatus').value = todo.status;
                    
                    // Bitiş tarihi düzeltmesi - tarih formatını Y-m-d'ye çevir
                    if (todo.due_date) {
                        // Eğer tarih 2024-01-15T00:00:00.000000Z formatındaysa sadece tarih kısmını al
                        let dueDate = todo.due_date;
                        if (dueDate.includes('T')) {
                            dueDate = dueDate.split('T')[0];
                        } else if (dueDate.includes(' ')) {
                            dueDate = dueDate.split(' ')[0];
                        }
                        document.getElementById('editDueDate').value = dueDate;
                        console.log('Set due date to:', dueDate);
                    } else {
                        document.getElementById('editDueDate').value = '';
                    }
                    
                    // Modal'ı aç
                    new bootstrap.Modal(document.getElementById('editTodoModal')).show();
                } else {
                    throw new Error(data.message || 'Görev bilgileri alınamadı');
                }
            })
            .catch(error => {
                console.error('Edit error:', error);
                alert('Hata: ' + (error.message || 'Görev bilgileri alınamadı'));
            });
        });
    });

    // Durum değiştirme (checkbox)
    document.querySelectorAll('.status-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const todoId = this.dataset.todoId;
            
            fetch(`/todos/${todoId}/toggle`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (response.status === 403) {
                    throw new Error('Bu görevi değiştirme yetkiniz yok!');
                }
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.message || 'Bir hata oluştu');
                }
            })
            .catch(error => {
                console.error('Toggle error:', error);
                alert('Hata: ' + (error.message || 'Bir hata oluştu'));
                this.checked = !this.checked; // Checkbox'ı eski haline döndür
            });
        });
    });

    // Görev silme
    document.querySelectorAll('.delete-todo').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Bu görevi silmek istediğinizden emin misiniz?')) {
                const todoId = this.dataset.todoId;
                
                fetch(`/todos/${todoId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Bir hata oluştu');
                    }
                })
                .catch(error => {
                    alert('Hata: ' + error.message);
                });
            }
        });
    });

    // Görev güncelleme formu
    document.getElementById('editTodoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const todoId = document.getElementById('editTodoId').value;
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Güncelleniyor...';
        
        fetch(`/todos/${todoId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: formData.get('title'),
                description: formData.get('description'),
                priority: formData.get('priority'),
                status: formData.get('status'),
                due_date: formData.get('due_date')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Modal'ı kapat
                bootstrap.Modal.getInstance(document.getElementById('editTodoModal')).hide();
                location.reload();
            } else {
                throw new Error(data.message || 'Bir hata oluştu');
            }
        })
        .catch(error => {
            alert('Hata: ' + error.message);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    // Modal temizleme - hem ekleme hem düzenleme için
    document.getElementById('addTodoModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addTodoForm').reset();
    });
    
    document.getElementById('editTodoModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('editTodoForm').reset();
        // Modal backdrop'u tamamen temizle
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
            backdrop.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('padding-right');
    });
});
</script>
@endsection