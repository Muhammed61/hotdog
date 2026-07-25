@extends('layouts.app')

@section('title', 'Kullanıcılar')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Kullanıcı Yönetimi</h5>
                <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>Yeni Kullanıcı
                </a>
            </div>
            <div class="card-body">
                <!-- Filtreler -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Ad veya email ara..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="role">
                            <option value="">Tüm Roller</option>
                            @foreach(\App\Models\User::getRoles() as $roleKey => $roleName)
                                <option value="{{ $roleKey }}" {{ request('role') == $roleKey ? 'selected' : '' }}>
                                    {{ $roleName }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">Tüm Durumlar</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Pasif</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i>Ara
                        </button>
                    </div>
                </form>

                <!-- Kullanıcı Listesi -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ad</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Durum</th>
                                <th>Kayıt Tarihi</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <strong>{{ $user->name }}</strong>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @php
                                            $roleColors = [
                                                'admin' => 'bg-danger',
                                                'manager' => 'bg-warning',
                                                'waiter' => 'bg-info',
                                                'cashier' => 'bg-success'
                                            ];
                                        @endphp
                                        <span class="badge {{ $roleColors[$user->role] ?? 'bg-secondary' }}">
                                            {{ $user->role_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $user->is_active ? 'Aktif' : 'Pasif' }}
                                        </span>
                                    </td>
                                    <td>{{ $user->created_at->format('d.m.Y H:i') }}</td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirmDelete()">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Henüz kullanıcı bulunmuyor.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}
</style>
@endsection