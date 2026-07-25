@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Masa Düzenle - {{ $table->name }}</h5>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('tables.update', $table) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Masa Adı <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name', $table->name) }}" 
                                       placeholder="Örn: Masa 1, Balkon Masası" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="capacity" class="form-label">Kapasite <span class="text-danger">*</span></label>
                                <select class="form-select @error('capacity') is-invalid @enderror" id="capacity" name="capacity" required>
                                    <option value="">Kapasite Seçin</option>
                                    @for($i = 1; $i <= 20; $i++)
                                        <option value="{{ $i }}" {{ old('capacity', $table->capacity) == $i ? 'selected' : '' }}>
                                            {{ $i }} kişi
                                        </option>
                                    @endfor
                                </select>
                                @error('capacity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Durum <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="">Durum Seçin</option>
                                    <option value="available" {{ old('status', $table->status) == 'available' ? 'selected' : '' }}>Müsait</option>
                                    <option value="occupied" {{ old('status', $table->status) == 'occupied' ? 'selected' : '' }}>Dolu</option>
                                    <option value="reserved" {{ old('status', $table->status) == 'reserved' ? 'selected' : '' }}>Rezerve</option>
                                    <option value="cleaning" {{ old('status', $table->status) == 'cleaning' ? 'selected' : '' }}>Temizleniyor</option>
                                    <option value="closed" {{ old('status', $table->status) == 'closed' ? 'selected' : '' }}>Kapalı</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                           {{ old('is_active', $table->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Aktif
                                    </label>
                                </div>
                                <small class="text-muted">Pasif masalar sipariş almak için kullanılamaz.</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Güncelle
                        </button>
                        <a href="{{ route('tables.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Geri Dön
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection