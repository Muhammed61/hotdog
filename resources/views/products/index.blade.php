@extends('layouts.app')

@section('title', 'Ürünler')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Ürün Yönetimi</h5>
                <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i>Yeni Ürün
                </a>
            </div>
            <div class="card-body">
                <!-- Filtreler -->
                <form method="GET" class="row g-3 mb-4" id="products-filter-form">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="products-search-input" name="search" placeholder="Ürün adı veya barkod ara..." value="{{ request('search') }}" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="products-category-select" name="category_id">
                            <option value="">Tüm Kategoriler</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="products-low-stock" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }}>
                            <label class="form-check-label">Düşük Stok</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-search me-1"></i>Ara
                            </button>
                            <button type="button" class="btn btn-danger" id="products-bulk-delete-btn" disabled>
                                <i class="fas fa-trash me-1"></i>Seçilenleri Sil
                            </button>
                        </div>
                    </div>
                </form>

                <form id="products-bulk-delete-form" action="{{ route('products.bulk-destroy') }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>

                <!-- Ürün Listesi -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="products-select-all">
                                </th>
                                <th>Ürün</th>
                                <th>Kategori</th>
                                <th>Fiyat</th>
                                <th>Stok</th>
                                <th>Durum</th>
                                <th class="text-center">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="products-tbody">
                            @forelse($products as $product)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input products-select-item" value="{{ $product->id }}">
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $product->name }}</strong>
                                            @if($product->barcode)
                                                <br><small class="text-muted">{{ $product->barcode }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $product->category->name }}</td>
                                    <td>{{ number_format($product->sale_price, 2) }} ₺</td>
                                    <td>
                                        <span class="badge {{ $product->stock_quantity <= $product->min_stock_level ? 'bg-danger' : 'bg-success' }}">
                                            {{ $product->stock_quantity }} {{ $product->unit }}
                                        </span>
                                        @if($product->stock_quantity <= $product->min_stock_level)
                                            <br><small class="text-danger">Min: {{ $product->min_stock_level }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $product->is_active ? 'Aktif' : 'Pasif' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-info" title="Detay">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-warning" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#stockModal{{ $product->id }}"
                                                    title="Stok Güncelle">
                                                <i class="fas fa-boxes"></i>
                                            </button>
                                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirmDelete()" title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Henüz ürün bulunmuyor.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4" id="products-pagination">
                    <div class="text-muted">
                        Toplam <span id="products-total">{{ $products->total() }}</span> ürün
                    </div>
                    
                    <!-- Gelişmiş sayfalama butonları -->
                    <div class="btn-group" role="group">
                        @if($products->currentPage() > 1)
                            <a href="{{ $products->appends(request()->query())->previousPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chevron-left"></i> Önceki
                            </a>
                        @endif
                        
                        <span class="btn btn-primary btn-sm">
                            Sayfa {{ $products->currentPage() }} / {{ $products->lastPage() }}
                        </span>
                        
                        @if($products->hasMorePages())
                            <a href="{{ $products->appends(request()->query())->nextPageUrl() }}" class="btn btn-outline-primary btn-sm">
                                Sonraki <i class="fas fa-chevron-right"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stok Güncelleme Modalleri -->
@foreach($products as $product)
<div class="modal fade" id="stockModal{{ $product->id }}" tabindex="-1" aria-labelledby="stockModalLabel{{ $product->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockModalLabel{{ $product->id }}">
                    <i class="fas fa-boxes me-2"></i>Stok Güncelle - {{ $product->name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('products.update-stock', $product) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Mevcut Stok:</strong> {{ $product->stock_quantity }} {{ $product->unit }}
                    </div>
                    
                    <div class="mb-3">
                        <label for="type{{ $product->id }}" class="form-label">İşlem Türü *</label>
                        <select class="form-select" name="type" id="type{{ $product->id }}" required>
                            <option value="">Seçiniz</option>
                            <option value="in">Stok Girişi (+)</option>
                            <option value="out">Stok Çıkışı (-)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity{{ $product->id }}" class="form-label">Miktar *</label>
                        <input type="number" class="form-control" name="quantity" id="quantity{{ $product->id }}" 
                               min="1" max="9999" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes{{ $product->id }}" class="form-label">Açıklama</label>
                        <textarea class="form-control" name="notes" id="notes{{ $product->id }}" 
                                  rows="3" placeholder="İsteğe bağlı açıklama..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>İptal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

@section('scripts')
<script>
// Modal açılma/kapanma olaylarını dinle
document.addEventListener('DOMContentLoaded', function() {
    // Tüm stok modallarını tek seferde dinle
    const stockModals = document.querySelectorAll('[id^="stockModal"]');
    
    stockModals.forEach(function(modal) {
        // Modal açıldığında
        modal.addEventListener('show.bs.modal', function (event) {
            // Modal açıldığında form alanlarını temizle
            const form = this.querySelector('form');
            if (form) {
                form.reset();
            }
        });
        
        // Modal kapandığında
        modal.addEventListener('hidden.bs.modal', function (event) {
            // Modal kapandığında form alanlarını temizle
            const form = this.querySelector('form');
            if (form) {
                form.reset();
            }
        });
    });
});

// Silme onayı fonksiyonu
function confirmDelete() {
    return confirm('Bu ürünü silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('products-filter-form');
    const searchInput = document.getElementById('products-search-input');
    const categorySelect = document.getElementById('products-category-select');
    const lowStock = document.getElementById('products-low-stock');

    const selectAll = document.getElementById('products-select-all');
    const bulkDeleteBtn = document.getElementById('products-bulk-delete-btn');
    const bulkDeleteForm = document.getElementById('products-bulk-delete-form');
    const tbody = document.getElementById('products-tbody');
    const pagination = document.getElementById('products-pagination');
    const total = document.getElementById('products-total');

    function getItems() {
        return Array.prototype.slice.call(document.querySelectorAll('.products-select-item'));
    }

    function getSelected() {
        return getItems().filter(function(el) { return el.checked; });
    }

    function syncState() {
        const items = getItems();
        const selected = getSelected();
        const count = selected.length;

        if (bulkDeleteBtn) {
            bulkDeleteBtn.disabled = count === 0;
            bulkDeleteBtn.innerHTML = '<i class="fas fa-trash me-1"></i>Seçilenleri Sil' + (count ? ' (' + count + ')' : '');
        }

        if (selectAll) {
            selectAll.checked = items.length > 0 && count === items.length;
            selectAll.indeterminate = count > 0 && count < items.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const items = getItems();
            items.forEach(function(el) { el.checked = selectAll.checked; });
            syncState();
        });
    }

    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('products-select-item')) {
            syncState();
        }
    });

    if (bulkDeleteBtn && bulkDeleteForm) {
        bulkDeleteBtn.addEventListener('click', function() {
            const selected = getSelected();
            if (selected.length === 0) {
                return;
            }

            if (!confirm('Seçili ' + selected.length + ' ürünü silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
                return;
            }

            Array.prototype.slice.call(bulkDeleteForm.querySelectorAll('input[name="ids[]"]')).forEach(function(el) {
                el.remove();
            });

            selected.forEach(function(el) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = el.value;
                bulkDeleteForm.appendChild(input);
            });

            bulkDeleteForm.submit();
        });
    }

    function buildUrl(baseUrl) {
        const url = new URL(baseUrl || window.location.href);
        const params = url.searchParams;

        params.set('search', (searchInput && searchInput.value ? searchInput.value : '').trim());
        if (categorySelect && categorySelect.value) {
            params.set('category_id', categorySelect.value);
        } else {
            params.delete('category_id');
        }

        if (lowStock && lowStock.checked) {
            params.set('low_stock', '1');
        } else {
            params.delete('low_stock');
        }

        if (!baseUrl) {
            params.delete('page');
        }

        const s = params.get('search');
        if (!s) {
            params.delete('search');
        }

        return url.toString();
    }

    async function loadProducts(url) {
        if (!tbody || !pagination) {
            return;
        }

        const active = document.activeElement;
        const keepFocus = active === searchInput;
        const caret = keepFocus && searchInput ? searchInput.selectionStart : null;

        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        const newTbody = doc.querySelector('#products-tbody');
        const newPagination = doc.querySelector('#products-pagination');
        const newTotal = doc.querySelector('#products-total');

        if (newTbody) {
            tbody.innerHTML = newTbody.innerHTML;
        }
        if (newPagination) {
            pagination.innerHTML = newPagination.innerHTML;
        }
        if (newTotal && total) {
            total.textContent = newTotal.textContent;
        }

        history.replaceState({}, '', url);

        if (keepFocus && searchInput) {
            searchInput.focus();
            if (caret !== null) {
                searchInput.setSelectionRange(caret, caret);
            }
        }

        syncState();
    }

    let searchTimer = null;
    if (searchInput && form) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                loadProducts(buildUrl());
            }, 450);
        });
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            loadProducts(buildUrl());
        });
    }

    if (lowStock) {
        lowStock.addEventListener('change', function() {
            loadProducts(buildUrl());
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            loadProducts(buildUrl());
        });
    }

    if (pagination) {
        pagination.addEventListener('click', function(e) {
            const a = e.target && e.target.closest ? e.target.closest('a') : null;
            if (!a || !a.href) {
                return;
            }
            e.preventDefault();
            loadProducts(a.href);
        });
    }

    syncState();
});
</script>
@endsection
