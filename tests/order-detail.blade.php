@extends('layouts.app')

@section('content')
<!-- QZ Tray kütüphanesini yükle -->
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.4/qz-tray.js"></script>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-receipt me-2"></i>Sipariş Detayı - {{ $order->order_number }}
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6><i class="fas fa-table me-2"></i>Masa Bilgileri</h6>
                        <p class="mb-1"><strong>Masa:</strong> {{ $order->table->name }}</p>
                        <p class="mb-1"><strong>Kapasite:</strong> {{ $order->table->capacity }} kişi</p>
                        <p class="mb-0"><strong>Durum:</strong> 
                            <span class="badge bg-{{ $order->table->status_color }}">{{ $order->table->status_text }}</span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle me-2"></i>Sipariş Bilgileri</h6>
                        <p class="mb-1"><strong>Tarih:</strong> {{ $order->created_at->format('d.m.Y H:i') }}</p>
                        <p class="mb-1"><strong>Garson:</strong> {{ $order->user->name }}</p>
                        <p class="mb-1"><strong>Durum:</strong> 
                            <span class="badge bg-{{ $order->status_color }}">{{ $order->status_text }}</span>
                        </p>
                        <p class="mb-1"><strong>Masa Oturma Süresi:</strong> 
                            <span class="badge bg-info">
                                <i class="fas fa-clock me-1"></i>{{ $order->formatted_duration }}
                            </span>
                        </p>
                        <p class="mb-1"><strong>Toplam Tutar:</strong> 
                            @if($order->discount_percentage > 0)
                                <span class="text-decoration-line-through text-muted">{{ number_format($order->total_amount, 2) }} ₺</span>
                                <span class="text-success fw-bold">{{ number_format($order->final_amount, 2) }} ₺</span>
                                <small class="text-success d-block">
                                    <i class="fas fa-tag me-1"></i>%{{ $order->discount_percentage }} indirim uygulandı
                                </small>
                            @else
                                {{ number_format($order->total_amount, 2) }} ₺
                            @endif
                        </p>
                        
                        <!-- Kısmi Ödeme Bilgileri -->
                        @if($order->is_partially_paid)
                            <p class="mb-1"><strong>Ödenen Tutar:</strong> 
                                <span class="text-success">{{ number_format($order->total_paid_amount, 2) }} ₺</span>
                            </p>
                            <p class="mb-1"><strong>Kalan Tutar:</strong> 
                                <span class="text-warning fw-bold">{{ number_format($order->remaining_amount, 2) }} ₺</span>
                            </p>
                        @endif
                        
                        <!-- Ekstra Fiyatlar ve Açıklamaları -->
                        @if(($order->extra_amount && $order->extra_amount > 0) || ($order->cafeOrderExtras && $order->cafeOrderExtras->count() > 0))
                            <div class="mb-2">
                                <strong>Ekstra Fiyatlar:</strong>
                                
                                @if($order->cafeOrderExtras && $order->cafeOrderExtras->count() > 0)
                                    <!-- Yeni sistem: Detaylı ekstra fiyatlar -->
                                    @foreach($order->cafeOrderExtras as $extra)
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <span class="text-success">
                                                <i class="fas fa-plus-circle me-1"></i>
                                                {{ $extra->description }} + {{ number_format($extra->amount, 2) }} ₺
                                            </span>
                                            
                                        </div>
                                    @endforeach
                                @elseif($order->extra_amount && $order->extra_amount > 0)
                                    <!-- Eski sistem: Sadece tutar -->
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="text-success">
                                            <i class="fas fa-plus-circle me-1"></i>
                                            Ekstra ücret
                                        </span>
                                        <span class="text-success fw-bold">+{{ number_format($order->extra_amount, 2) }} ₺</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                        @if($order->is_paid)
                            <p class="mb-0"><strong>Ödeme:</strong> 
                                <span class="badge bg-success">{{ $order->payment_method_text }}</span>
                                <small class="text-muted d-block">{{ $order->paid_at->format('d.m.Y H:i') }}</small>
                            </p>
                        @elseif($order->is_partially_paid)
                            <p class="mb-0"><strong>Ödeme:</strong> 
                                <span class="badge bg-warning">Kısmi Ödendi</span>
                                <small class="text-muted d-block">Kalan: {{ number_format($order->remaining_amount, 2) }} ₺</small>
                            </p>
                        @else
                            <p class="mb-0"><strong>Ödeme:</strong> 
                                <span class="badge bg-danger">Ödenmedi</span>
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Sipariş Notları -->
                <div class="alert alert-secondary">
                    <strong><i class="fas fa-sticky-note me-2"></i>Notlar:</strong><br>
                    @if($order->cafeOrderNotes && $order->cafeOrderNotes->count() > 0)
                        @foreach($order->cafeOrderNotes as $note)
                            <div class="mb-2">
                                <strong>{{ $note->type_text }}:</strong> {{ $note->note }}
                                <br><small class="text-muted">{{ $note->user->name }} - {{ $note->created_at->format('d.m.Y H:i') }}</small>
                            </div>
                            @if(!$loop->last)<hr class="my-2">@endif
                        @endforeach
                    @elseif($order->notes)
                        {{ $order->notes }}
                    @else
                        <em class="text-muted">Henüz not eklenmemiş.</em>
                    @endif
                </div>

                <!-- Sipariş Detayları -->
                <div class="table-responsive">
                    <h6><i class="fas fa-list me-2"></i>Sipariş Detayları</h6>
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Ürün</th>
                                <th>Birim Fiyat</th>
                                <th>Adet</th>
                                <th>Toplam</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->cafeOrderItems as $item)
                            <tr>
                                <td>{{ $item->product->name }}</td>
                                <td>{{ number_format($item->unit_price, 2) }} ₺</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->total_price, 2) }} ₺</td>
                                <td>
                                    <span class="badge bg-{{ $item->status_color }}">{{ $item->status_text }}</span>
                                </td>
                                <td>
                                    @if($item->status !== 'served' && $item->status !== 'cancelled')
                                    <div class="btn-group btn-group-sm" role="group">
                                        @if($item->status === 'pending')
                                        <form method="POST" action="{{ route('cafe.item.status', $item) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="preparing">
                                            <button type="submit" class="btn btn-info btn-sm" title="Hazırlanıyor">
                                                <i class="fas fa-clock"></i>
                                            </button>
                                        </form>
                                        @endif
                                        
                                        @if(in_array($item->status, ['pending', 'preparing']))
                                        <form method="POST" action="{{ route('cafe.item.status', $item) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="ready">
                                            <button type="submit" class="btn btn-success btn-sm" title="Hazır">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        @endif
                                        
                                        @if(in_array($item->status, ['ready']))
                                        <form method="POST" action="{{ route('cafe.item.status', $item) }}" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="served">
                                            <button type="submit" class="btn btn-primary btn-sm" title="Servis Et">
                                                <i class="fas fa-utensils"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                    
                                    {{-- Silme butonu - sadece ödeme kontrolü --}}
                                    @if(!$order->is_paid)
                                    <form method="POST" action="{{ route('cafe.item.remove', $item) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm ms-1" onclick="return confirm('Bu ürünü silmek istediğinizden emin misiniz?')" title="Ürünü Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            
                            <!-- Ekstra Fiyatları Ayrı Satırlar Halinde Göster -->
                            @if($order->cafeOrderExtras && $order->cafeOrderExtras->count() > 0)
                                @foreach($order->cafeOrderExtras as $extra)
                                <tr class="table-success">
                                    <td>
                                        <i class="fas fa-plus-circle text-success me-2"></i>
                                        <strong>{{ $extra->description ?: 'Ekstra Ücret' }}</strong>
                                    </td>
                                    <td>{{ number_format($extra->amount, 2) }} ₺</td>
                                    <td>1</td>
                                    <td><strong class="text-success">{{ number_format($extra->amount, 2) }} ₺</strong></td>
                                    <td>
                                        <span class="badge bg-success">Eklendi</span>
                                    </td>
                                    <td>
                                        @if(auth()->user()->hasAnyRole(['admin', 'manager','cashier']) && !$order->is_paid)
                                        <form action="{{ route('cafe.extra.remove', $extra) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bu ekstra fiyatı silmek istediğinizden emin misiniz?')" title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @elseif($order->is_paid)
                                        <span class="text-muted" title="Ödeme alındığı için silinemez">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                            @if($order->is_partially_paid)
                                <tr class="table-info">
                                    <th colspan="3">Kısmi Ödeme Yapılmış</th>
                                    <th colspan="3"></th>
                                </tr>
                                <tr class="table-light">
                                    <th colspan="2">Ödenen:</th>
                                    <th class="text-success">{{ number_format($order->total_paid_amount, 2) }} ₺</th>
                                    <th colspan="3"></th>
                                </tr>
                                <tr class="table-light">
                                    <th colspan="2">Kalan:</th>
                                    <th class="text-warning">{{ number_format($order->remaining_amount, 2) }} ₺</th>
                                    <th colspan="3"></th>
                                </tr>
                                <tr class="table-warning">
                                    <th colspan="3">Toplam Tutar</th>
                                    <th>
                                        @if($order->discount_percentage > 0)
                                            <span class="text-decoration-line-through text-muted">{{ number_format($order->total_amount, 2) }} ₺</span>
                                            <br>
                                            <span class="text-success fw-bold">{{ number_format($order->final_amount, 2) }} ₺</span>
                                            <small class="text-success d-block">
                                                (%{{ $order->discount_percentage }} indirim)
                                            </small>
                                        @else
                                            {{ number_format($order->total_amount, 2) }} ₺
                                        @endif
                                    </th>
                                    <th colspan="2"></th>
                                </tr>
                            @else
                                <tr class="table-warning">
                                    <th colspan="3">Toplam Tutar</th>
                                    <th>
                                        @if($order->discount_percentage > 0)
                                            <span class="text-decoration-line-through text-muted">{{ number_format($order->total_amount, 2) }} ₺</span>
                                            <br>
                                            <span class="text-success fw-bold">{{ number_format($order->final_amount, 2) }} ₺</span>
                                            <small class="text-success d-block">
                                                (%{{ $order->discount_percentage }} indirim)
                                            </small>
                                        @else
                                            {{ number_format($order->total_amount, 2) }} ₺
                                        @endif
                                    </th>
                                    <th colspan="2"></th>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Sipariş Durumu Güncelleme -->
        @if(!$order->is_paid && $order->status !== 'cancelled')
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-edit me-2"></i>Sipariş Durumu</h6>
                </div>
                <div class="card-body">
                    <!-- Mevcut Ekstra Fiyatlar Gösterimi -->
                    @if($order->cafeOrderExtras && $order->cafeOrderExtras->count() > 0)
                        <div class="mb-3">
                            <h6 class="text-success"><i class="fas fa-plus-circle me-2"></i>Ekstra Fiyatlar</h6>
                            @foreach($order->cafeOrderExtras as $extra)
                                <div class="card border-success mb-2">
                                    <div class="card-body py-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong class="text-success">{{ number_format($extra->amount, 2) }} ₺</strong>
                                            <br>
                                            <small class="text-muted">{{ $extra->description }}</small>
                                        </div>
                                        <form action="{{ route('cafe.extra.remove', $extra) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bu ekstra fiyatı silmek istediğinizden emin misiniz?')" title="Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('cafe.order.status', $order) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label for="status" class="form-label">Durum</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Bekliyor</option>
                                <option value="preparing" {{ $order->status === 'preparing' ? 'selected' : '' }}>Hazırlanıyor</option>
                                <option value="ready" {{ $order->status === 'ready' ? 'selected' : '' }}>Hazır</option>
                                <option value="served" {{ $order->status === 'served' ? 'selected' : '' }}>Servis Edildi</option>
                                <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>İptal</option>
                            </select>
                        </div>
                        @if(auth()->user()->hasAnyRole(['admin', 'manager','cashier']))
                        <div class="mb-3">
                            <label for="extra_amount" class="form-label">Yeni Ekstra Fiyat Ekle (₺)</label>
                            <input type="number" name="extra_amount" class="form-control" step="0.01" min="0" placeholder="0.00">
                            <small class="text-muted">Opsiyonel: Ekstra ücret varsa giriniz</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="extra_description" class="form-label">Açıklama</label>
                            <input type="text" name="extra_description" class="form-control" placeholder="Ekstra fiyat açıklaması">
                            <small class="text-muted">Opsiyonel: Ekstra fiyat için açıklama</small>
                        </div>
                        @endif
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i>Durumu Güncelle
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if(auth()->user()->hasAnyRole(['admin', 'manager','cashier']))
        <!-- Masa Taşıma -->
        @if(!$order->is_paid && $order->status !== 'cancelled')
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Masa Taşıma</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Bu siparişi başka bir masaya taşıyabilirsiniz.
                    </p>
                    
                    <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#transferModal">
                        <i class="fas fa-exchange-alt me-2"></i>Masayı Değiştir
                    </button>
                    
                    <button type="button" class="btn btn-info w-100 mt-2" data-bs-toggle="modal" data-bs-target="#mergeModal">
                        <i class="fas fa-object-group me-2"></i>Masa Birleştir
                    </button>
                </div>
            </div>
        @endif

        <!-- Ödeme İşlemleri -->
        @if($order->status === 'served' && !$order->is_paid)
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-credit-card me-2"></i>Ödeme Al</h6>
                </div>
                <div class="card-body">
                    @if($order->is_partially_paid)
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Kısmi Ödeme Yapılmış</strong><br>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <small class="text-muted">Ödenen:</small><br>
                                    <strong class="text-success">{{ number_format($order->total_paid_amount, 2) }} ₺</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Kalan:</small><br>
                                    <strong class="text-warning">{{ number_format($order->remaining_amount, 2) }} ₺</strong>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Toplam Tutar: {{ number_format($order->total_amount, 2) }} ₺</strong>
                        </div>
                    @endif
                    
                    <!-- İndirim Butonları -->
                    <div class="mb-3">
                        <h6 class="mb-2"><i class="fas fa-percentage me-2"></i>İndirim Uygula</h6>
                        <div class="row g-2">
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-danger w-100" onclick="applyDiscount(10)">
                                    <i class="fas fa-tag me-1"></i>%10
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-danger w-100" onclick="applyDiscount(20)">
                                    <i class="fas fa-tag me-1"></i>%20
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-danger w-100" onclick="applyDiscount(30)">
                                    <i class="fas fa-tag me-1"></i>%30
                                </button>
                            </div>
                        </div>
                        
                        <!-- İndirim Bilgisi -->
                        <div id="discount-info" class="alert alert-info mt-2" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-info-circle me-2"></i>
                                    <span id="discount-text"></span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearDiscount()">
                                    <i class="fas fa-times"></i> İptal
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <form action="{{ route('cafe.order.payment', $order) }}" method="POST" class="d-inline" id="cash-payment-form">
                            @csrf
                            <input type="hidden" name="payment_method" value="cash">
                            <input type="hidden" name="discount_percentage" id="cash-discount" value="0">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                <span id="cash-button-text">
                                    @if($order->is_partially_paid)
                                        Kalan Tutarı Nakit Öde ({{ number_format($order->remaining_amount, 2) }} ₺)
                                    @else
                                        Nakit Ödeme
                                    @endif
                                </span>
                            </button>
                        </form>
                        
                        <form action="{{ route('cafe.order.payment', $order) }}" method="POST" class="d-inline" id="card-payment-form">
                            @csrf
                            <input type="hidden" name="payment_method" value="card">
                            <input type="hidden" name="discount_percentage" id="card-discount" value="0">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-credit-card me-2"></i>
                                <span id="card-button-text">
                                    @if($order->is_partially_paid)
                                        Kalan Tutarı Kart ile Öde ({{ number_format($order->remaining_amount, 2) }} ₺)
                                    @else
                                        Kredi Kartı
                                    @endif
                                </span>
                            </button>
                        </form>
                        
                        <a href="{{ route('cafe.order.split-payment', $order) }}" class="btn btn-warning w-100" id="split-button">
                            <i class="fas fa-calculator me-2"></i>
                            @if($order->is_partially_paid)
                                Kalan Tutarı Böl ({{ number_format($order->remaining_amount, 2) }} ₺)
                            @else
                                Hesabı Böl
                            @endif
                        </a>
                    </div>
                    
                    
                </div>
            </div>
        @elseif($order->is_paid)
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i>Ödeme Tamamlandı</h6>
                </div>
                <div class="card-body">
                    @if($order->is_split_payment)
                        <!-- Başarı Kartı - Kalıcı -->
                        <div class="card border-success mb-3">
                            <div class="card-header bg-success text-white">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle fa-lg me-2"></i>
                                    <h6 class="mb-0"><strong>Bölünmüş Ödeme Başarıyla Tamamlandı!</strong></h6>
                                </div>
                            </div>
                            <div class="card-body bg-success bg-opacity-10">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="p-2">
                                            <i class="fas fa-calendar fa-lg text-success mb-1"></i>
                                            <br>
                                            <small class="text-muted">Tarih</small>
                                            <br>
                                            <strong>{{ $order->paid_at->format('d.m.Y') }}</strong>
                                            <br>
                                            <small>{{ $order->paid_at->format('H:i') }}</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2">
                                            <i class="fas fa-users fa-lg text-primary mb-1"></i>
                                            <br>
                                            <small class="text-muted">Kişi Sayısı</small>
                                            <br>
                                            <strong class="text-primary">{{ $order->cafeOrderPayments->count() }} Kişi</strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2">
                                            <i class="fas fa-money-bill fa-lg text-success mb-1"></i>
                                            <br>
                                            <small class="text-muted">Toplam</small>
                                            <br>
                                            <strong class="text-success">{{ number_format($order->total_amount, 2) }} ₺</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-success mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-receipt me-2"></i>Ödeme Detayları
                                </h6>
                            </div>
                            <div class="card-body">
                                @foreach($order->cafeOrderPayments as $payment)
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                        <div class="d-flex align-items-center">
                                            @if($payment->payment_method === 'cash')
                                                <i class="fas fa-money-bill-wave text-success me-2"></i>
                                            @else
                                                <i class="fas fa-credit-card text-primary me-2"></i>
                                            @endif
                                            <span>{{ $payment->description }}</span>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-success">{{ number_format($payment->amount, 2) }} ₺</strong>
                                            <br>
                                            <small class="text-muted">{{ $payment->payment_method_text }}</small>
                                        </div>
                                    </div>
                                @endforeach
                                
                                <!-- Hesap kapatma bilgisi -->
                                @if($order->is_closed_with_partial_payment)
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom bg-warning bg-opacity-10">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-times-circle text-warning me-2"></i>
                                            <span class="text-warning fw-bold">Hesap Kapatıldı</span>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-warning">{{ number_format($order->remaining_amount, 2) }} ₺</strong>
                                            <br>
                                            <small class="text-muted">Kalan tutar</small>
                                        </div>
                                    </div>
                                @endif
                                
                                <div class="d-flex justify-content-between align-items-center pt-3 mt-2 border-top">
                                    <strong>Toplam Ödenen:</strong>
                                    <strong class="text-success h5 mb-0">
                                        @if($order->discount_percentage > 0)
                                            {{ number_format($order->final_amount, 2) }} ₺
                                            <small class="d-block text-muted fs-6">
                                                (Orijinal: {{ number_format($order->total_amount, 2) }} ₺ - %{{ $order->discount_percentage }} indirim)
                                            </small>
                                        @else
                                            @if($order->is_closed_with_partial_payment)
                                                {{ number_format($order->total_paid_amount, 2) }} ₺
                                                <small class="d-block text-warning fs-6">
                                                    ({{ number_format($order->total_paid_amount, 2) }} ₺ alındı, {{ number_format($order->remaining_amount, 2) }} ₺ kapatıldı)
                                                </small>
                                            @else
                                                {{ number_format($order->total_amount, 2) }} ₺
                                            @endif
                                        @endif
                                    </strong>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Başarı Kartı - Kalıcı -->
                        <div class="card border-success mb-3">
                            <div class="card-header bg-success text-white">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle fa-lg me-2"></i>
                                    <h6 class="mb-0"><strong>Ödeme Başarıyla Tamamlandı!</strong></h6>
                                </div>
                            </div>
                            <div class="card-body bg-success bg-opacity-10">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="p-2">
                                            @if($order->payment_method === 'cash')
                                                <i class="fas fa-money-bill-wave fa-lg text-success mb-1"></i>
                                                <br>
                                                <small class="text-muted">Ödeme Türü</small>
                                                <br>
                                                <strong class="text-success">Nakit</strong>
                                            @else
                                                <i class="fas fa-credit-card fa-lg text-primary mb-1"></i>
                                                <br>
                                                <small class="text-muted">Ödeme Türü</small>
                                                <br>
                                                <strong class="text-primary">Kredi Kartı</strong>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2">
                                            <i class="fas fa-calendar fa-lg text-info mb-1"></i>
                                            <br>
                                            <small class="text-muted">Ödeme Tarihi</small>
                                            <br>
                                            <strong>{{ $order->paid_at->format('d.m.Y') }}</strong>
                                            <br>
                                            <small>{{ $order->paid_at->format('H:i') }}</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2">
                                            <i class="fas fa-money-bill fa-lg text-success mb-1"></i>
                                            <br>
                                            <small class="text-muted">Ödenen Tutar</small>
                                            <br>
                                            @if($order->discount_percentage > 0)
                                                <strong class="text-success">{{ number_format($order->final_amount, 2) }} ₺</strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-tag me-1"></i>%{{ $order->discount_percentage }} indirim
                                                </small>
                                            @else
                                                <strong class="text-success">{{ number_format($order->total_amount, 2) }} ₺</strong>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-success mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Ödeme Bilgileri
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span><i class="fas fa-user me-2"></i>Ödemeyi Alan:</span>
                                    <strong>{{ $order->user->name }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <span><i class="fas fa-table me-2"></i>Masa:</span>
                                    <strong>{{ $order->table->name }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center py-2">
                                    <span><i class="fas fa-receipt me-2"></i>Sipariş No:</span>
                                    <strong>{{ $order->order_number }}</strong>
                                </div>
                                
                                <div class="mt-3 p-3 bg-success bg-opacity-10 rounded text-center">
                                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                    <h5 class="text-success mb-0">Ödeme Başarıyla Alındı</h5>
                                    <small class="text-muted">Teşekkür ederiz!</small>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(auth()->user()->hasAnyRole(['admin', 'manager','cashier']))
                        <form action="{{ route('cafe.order.cancel-payment', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Ödemeyi iptal etmek istediğinizden emin misiniz?')">
                                <i class="fas fa-undo me-2"></i>Ödemeyi İptal Et
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
        @endif

        <!-- Geri Dön -->
        <div class="card mt-3">
            <div class="card-body text-center">
                <!-- Fiş Yazdır Butonu -->
                <form action="{{ route('cafe.print.receipt', $order) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success me-2">
                        <i class="fas fa-print me-1"></i>Fiş Yazdır
                    </button>
                </form>
                
                <!-- Manuel Yazdırma Linki -->
                @if(session('qz_direct_print'))
                <script>
                    // QZ Tray kütüphanesi yüklendikten sonra yazdırma scriptini çalıştır
                    document.addEventListener('DOMContentLoaded', function() {
                        console.log('Sayfa yüklendi, QZ Tray scripti başlatılıyor...');
                        
                        // QZ Tray kütüphanesinin yüklenmesini bekle
                        var checkQZ = setInterval(function() {
                            if (typeof qz !== 'undefined') {
                                clearInterval(checkQZ);
                                console.log('QZ Tray kütüphanesi hazır');

                                // Yazdırma scriptini çalıştır
                                setTimeout(function() {
                                    {!! session('qz_direct_print') !!}
                                }, 500);
                            }
                        }, 100);
                        
                        // 5 saniye sonra timeout
                        setTimeout(function() {
                            if (typeof qz === 'undefined') {
                                clearInterval(checkQZ);
                                console.log('QZ Tray kütüphanesi yüklenemedi');
                                
                                // Hata mesajı göster
                                var errorDiv = document.createElement('div');
                                errorDiv.className = 'alert alert-warning alert-dismissible fade show';
                                errorDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 350px;';
                                errorDiv.innerHTML = `
                                    <i class='fas fa-exclamation-triangle me-2'></i>
                                    <strong>QZ Tray Bulunamadı!</strong> 
                                    QZ Tray programını başlatın ve sayfayı yenileyin.
                                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                                `;
                                document.body.appendChild(errorDiv);
                                
                                // 7 saniye sonra mesajı kaldır
                                setTimeout(function() {
                                    if (errorDiv.parentNode) {
                                        errorDiv.parentNode.removeChild(errorDiv);
                                    }
                                }, 7000);
                            }
                        }, 5000);
                    });
                </script>
                @php
                    session()->forget('qz_direct_print');
                @endphp
                @endif
                
                @if(session('manual_print_url'))
                    <div class="alert alert-info mt-2">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Fiş hazır!</strong> 
                        <a href="{{ session('manual_print_url') }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                            <i class="fas fa-external-link-alt me-1"></i>Fişi Tarayıcıda Aç
                        </a>
                    </div>
                    @php
                        session()->forget('manual_print_url');
                    @endphp
                @endif
                
                <a href="{{ route('cafe.orders') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Sipariş Listesi
                </a>
                <a href="{{ route('cafe.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-home me-1"></i>Ana Sayfa
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Masa Birleştirme Modal -->
<div class="modal fade" id="mergeModal" tabindex="-1" aria-labelledby="mergeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mergeModalLabel">
                    <i class="fas fa-object-group me-2"></i>Masa Birleştirme
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('cafe.order.merge', $order) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Mevcut Masa:</strong> {{ $order->table->name }}
                        <br>
                        <strong>Sipariş No:</strong> {{ $order->order_number }}
                        <br>
                        <strong>Bu masadaki hesaplar seçilen masaya aktarılacak</strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="target_table_id" class="form-label">Hedef Masa Seçin</label>
                        <select name="target_table_id" id="target_table_id" class="form-select" required>
                            <option value="">Masa seçiniz...</option>
                        </select>
                        <small class="text-muted">Sadece dolu masalar gösterilir (hesapların birleştirileceği masa)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="merge_reason" class="form-label">Birleştirme Sebebi (Opsiyonel)</label>
                        <textarea name="merge_reason" id="merge_reason" class="form-control" rows="2" 
                               placeholder="Örn: Müşteri grupları birleşti, aynı hesap istendi"></textarea>
                        <small class="text-muted">Bu bilgi sipariş notlarına eklenecektir</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-object-group me-1"></i>Masa Birleştir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Masa Taşıma Modal -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transferModalLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Masa Taşıma
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('cafe.order.transfer', $order) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Mevcut Masa:</strong> {{ $order->table->name }}
                        <br>
                        <strong>Sipariş No:</strong> {{ $order->order_number }}
                        <br>
                        <strong>Bu sipariş seçilen masaya taşınacak</strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_table_id" class="form-label">Yeni Masa Seçin</label>
                        <select name="new_table_id" id="new_table_id" class="form-select" required>
                            <option value="">Masa seçiniz...</option>
                        </select>
                        <small class="text-muted">Sadece müsait masalar gösterilir</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="transfer_reason" class="form-label">Taşıma Sebebi (Opsiyonel)</label>
                        <textarea name="transfer_reason" id="transfer_reason" class="form-control" rows="2" 
                               placeholder="Örn: Müşteri isteği, masa değişikliği"></textarea>
                        <small class="text-muted">Bu bilgi sipariş notlarına eklenecektir</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-exchange-alt me-1"></i>Masayı Taşı
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')

<script>
// Sayfa yüklendiğinde çalışacak
document.addEventListener('DOMContentLoaded', function() {
    
    // Masa taşıma modal açıldığında müsait masaları yükle
    const transferModal = document.getElementById('transferModal');
    if (transferModal) {
        transferModal.addEventListener('show.bs.modal', function () {
            console.log('Transfer modal açılıyor...');
            fetch('{{ route("cafe.order.available-tables", $order) }}')
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(tables => {
                    console.log('Gelen masalar:', tables);
                    const select = document.getElementById('new_table_id');
                    if (select) {
                        select.innerHTML = '<option value="">Masa seçiniz...</option>';
                        
                        tables.forEach(table => {
                            const option = document.createElement('option');
                            option.value = table.id;
                            option.textContent = `${table.name} (${table.capacity} kişi)`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Masa listesi yüklenirken hata:', error);
                    alert('Masa listesi yüklenirken bir hata oluştu: ' + error.message);
                });
        });
    }

    // Masa birleştirme modal açıldığında dolu masaları yükle
    const mergeModal = document.getElementById('mergeModal');
    if (mergeModal) {
        mergeModal.addEventListener('show.bs.modal', function () {
            console.log('Merge modal açılıyor...');
            fetch('{{ route("cafe.order.occupied-tables", $order) }}')
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(tables => {
                    console.log('Gelen dolu masalar:', tables);
                    const select = document.getElementById('target_table_id');
                    if (select) {
                        select.innerHTML = '<option value="">Masa seçiniz...</option>';
                        
                        tables.forEach(table => {
                            const option = document.createElement('option');
                            option.value = table.id;
                            option.textContent = `${table.name} (${table.capacity} kişi) - ${table.order_count} sipariş`;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Masalar yüklenirken hata:', error);
                    alert('Masalar yüklenirken bir hata oluştu: ' + error.message);
                });
        });
    }
});
</script>

<script>
let currentDiscount = 0;
const originalAmount = {{ $order->is_partially_paid ? $order->remaining_amount : $order->total_amount }};
const isPartiallyPaid = {{ $order->is_partially_paid ? 'true' : 'false' }};

// İndirim butonlarına event listener ekle
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.discount-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const percentage = parseInt(this.dataset.discount);
            applyDiscount(percentage, this);
        });
    });
});

function applyDiscount(percentage, buttonElement) {
    currentDiscount = percentage;
    const discountAmount = originalAmount * (percentage / 100);
    const finalAmount = originalAmount - discountAmount;
    
    // İndirim bilgisini göster
    document.getElementById('discount-info').style.display = 'block';
    document.getElementById('discount-text').innerHTML = 
        `<strong>%${percentage} İndirim:</strong> ${discountAmount.toFixed(2)} ₺ indirim<br>` +
        `<strong>Yeni Tutar:</strong> ${finalAmount.toFixed(2)} ₺`;
    
    // Hidden input'ları güncelle
    document.getElementById('cash-discount').value = percentage;
    document.getElementById('card-discount').value = percentage;
    
    // Buton metinlerini güncelle
    const cashText = isPartiallyPaid ? 
        `Kalan Tutarı Nakit Öde (${finalAmount.toFixed(2)} ₺)` : 
        `Nakit Ödeme (${finalAmount.toFixed(2)} ₺)`;
    const cardText = isPartiallyPaid ? 
        `Kalan Tutarı Kart ile Öde (${finalAmount.toFixed(2)} ₺)` : 
        `Kredi Kartı (${finalAmount.toFixed(2)} ₺)`;
    
    document.getElementById('cash-button-text').textContent = cashText;
    document.getElementById('card-button-text').textContent = cardText;
    
    // İndirim butonlarını aktif göster
    document.querySelectorAll('.discount-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    if (buttonElement) {
        buttonElement.classList.add('active');
    }
}

function clearDiscount() {
    currentDiscount = 0;
    
    // İndirim bilgisini gizle
    document.getElementById('discount-info').style.display = 'none';
    
    // Hidden input'ları sıfırla
    document.getElementById('cash-discount').value = '0';
    document.getElementById('card-discount').value = '0';
    
    // Buton metinlerini orijinal haline döndür
    const originalCashText = isPartiallyPaid ? 
        `Kalan Tutarı Nakit Öde (${originalAmount.toFixed(2)} ₺)` : 
        `Nakit Ödeme (${originalAmount.toFixed(2)} ₺)`;
    const originalCardText = isPartiallyPaid ? 
        `Kalan Tutarı Kart ile Öde (${originalAmount.toFixed(2)} ₺)` : 
        `Kredi Kartı (${originalAmount.toFixed(2)} ₺)`;
    
    document.getElementById('cash-button-text').textContent = originalCashText;
    document.getElementById('card-button-text').textContent = originalCardText;
    
    // Aktif buton stilini kaldır
    document.querySelectorAll('.discount-btn').forEach(btn => {
        btn.classList.remove('active');
    });
}
</script>
@endsection