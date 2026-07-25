@extends('layouts.app')

@section('title', 'Sistem Ayarları')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-cogs me-2"></i>Sistem Ayarları</span>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Ayarlar hakkında bilgi kutusu -->
                    <div class="alert alert-info mb-4">
                        <h6><i class="fas fa-info-circle me-2"></i>Ayarlar Nasıl Çalışır?</h6>
                        <ul class="mb-0">
                            <li><strong>Otomatik Stok Güncelleme:</strong> Açık olduğunda, kafe siparişleri "Servis Edildi" durumuna geçtiğinde ürün stokları otomatik olarak azaltılır.</li>
                            <li><strong>Düşük Stok Uyarısı:</strong> Burada belirlediğiniz değer, ürünlerin kendi "Minimum Stok Seviyesi" tanımlanmış tüm ürünlere atanır. Ürünler sayfasından'da her ürün için ayrı ayrı minimum seviye belirleyebilirsiniz.</li>
                            <li><strong>Otomatik Adisyon Fişi:</strong> Açık olduğunda, sipariş alındığında otomatik olarak adisyon fişi yazdırılır. Kapalı olduğunda sadece manuel olarak "Fiş Yazdır" butonuna basarak fiş yazdırabilirsiniz.</li>
                        </ul>
                    </div>

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')

                        <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">Genel Ayarlar</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock" type="button" role="tab">Stok Ayarları</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="settingsTabsContent">
                            <!-- Genel Ayarlar -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <h5 class="border-bottom pb-2">Genel Sistem Ayarları</h5>
                                <div class="row mt-3">
                                    @forelse($generalSettings as $setting)
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label for="{{ $setting->key }}" class="form-label">{{ $setting->label ?? $setting->key }}</label>
                                                
                                                @if($setting->type == 'boolean')
                                                    <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="{{ $setting->key }}" name="settings[{{ $setting->key }}]" value="1" {{ $setting->value == '1' ? 'checked' : '' }}>
                                                    </div>
                                                @elseif($setting->type == 'textarea')
                                                    <textarea class="form-control" id="{{ $setting->key }}" name="settings[{{ $setting->key }}]" rows="3">{{ $setting->value }}</textarea>
                                                @else
                                                    <input type="text" class="form-control" id="{{ $setting->key }}" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}">
                                                @endif
                                                
                                                @if($setting->description)
                                                    <div class="form-text text-muted">{{ $setting->description }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="alert alert-info">Henüz genel ayar bulunmuyor.</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Stok Ayarları -->
                            <div class="tab-pane fade" id="stock" role="tabpanel">
                                <h5 class="border-bottom pb-2">Stok Yönetimi Ayarları</h5>
                                
                                <!-- Stok ayarları açıklama kutusu -->
                                <div class="alert alert-warning mb-3">
                                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Önemli Bilgiler:</h6>
                                    <ul class="mb-0">
                                        <li><strong>Otomatik Stok Güncelleme:</strong> Bu ayar açık olduğunda, kafe siparişleri "Servis Edildi" durumuna geçtiğinde siparişteki ürünlerin stok miktarları otomatik olarak azaltılır.</li>
                                        <li><strong>Düşük Stok Uyarısı:</strong> Bu değer, ürünlerin "Minimum Stok Seviyesi" tanımlanmış olanlar için geçerlidir. Ürün düzenleme sayfasından her ürün için özel minimum seviye belirleyebilirsiniz.</li>
                                        <li><strong>Örnek:</strong> Düşük stok uyarısını 5 yaparsanız, minimum stok seviyesi tanımlanmış tüm ürünler için 5 ve altında uyarı verilir.</li>
                                    </ul>
                                </div>

                                <div class="row mt-3">
                                    @forelse($stockSettings as $setting)
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label for="{{ $setting->key }}" class="form-label">{{ $setting->label ?? $setting->key }}</label>
                                                
                                                @if($setting->type == 'boolean')
                                                    <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                                                    <div class="form-check form-switch">
                                                        <input type="checkbox" class="form-check-input" id="{{ $setting->key }}" name="settings[{{ $setting->key }}]" value="1" {{ $setting->value == '1' ? 'checked' : '' }}>
                                                    </div>
                                                    @if($setting->key == 'auto_stock_update')
                                                        <div class="form-text text-success">
                                                            <i class="fas fa-check-circle me-1"></i>
                                                            Açık olduğunda: Kafe siparişleri "Servis Edildi" durumuna geçtiğinde stok otomatik azalır.
                                                        </div>
                                                    @endif
                                                @elseif($setting->type == 'textarea')
                                                    <textarea class="form-control" id="{{ $setting->key }}" name="settings[{{ $setting->key }}]" rows="3">{{ $setting->value }}</textarea>
                                                @else
                                                    <input type="number" class="form-control" id="{{ $setting->key }}" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" min="1">
                                                    @if($setting->key == 'low_stock_alert')
                                                        <div class="form-text text-info">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            Bu değer, minimum stok seviyesi tanımlanmış ürünler için kullanılır.
                                                        </div>
                                                    @endif
                                                @endif
                                                
                                                @if($setting->description)
                                                    <div class="form-text text-muted">{{ $setting->description }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12">
                                            <div class="alert alert-info">Henüz stok ayarı bulunmuyor.</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Ayarları Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection