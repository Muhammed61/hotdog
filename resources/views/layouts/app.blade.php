<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $settings['site_name'] ?? 'Kafe Stok Takip')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <style>
        :root {
            --primary-color: #8B4513;
            --secondary-color: #D2691E;
            --accent-color: #F4A460;
            --dark-color: #5D4037;
            --light-color: #FFF8DC;
        }

        body {
            background-color: var(--light-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: bold;
            color: white !important;
        }

        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s ease;
            border-radius: 5px;
            margin: 0 2px;
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white !important;
        }

        .navbar-nav .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            color: white !important;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 25px;
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: rgba(139, 69, 19, 0.05);
        }

        .alert {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(139, 69, 19, 0.25);
        }

        .badge {
            border-radius: 20px;
            padding: 5px 12px;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .stats-card .stats-number {
            font-size: 2rem;
            font-weight: bold;
        }

        /* Role Badge */
        .role-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 10px;
            }
            
            .card {
                margin-bottom: 15px;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
            
            .stats-card .stats-number {
                font-size: 1.5rem;
            }
            
            .navbar-nav {
                text-align: center;
            }
            
            .navbar-collapse {
                background-color: rgba(0,0,0,0.1);
                border-radius: 10px;
                margin-top: 10px;
                padding: 10px;
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding: 5px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .btn-group {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            
            .btn-group .btn {
                margin: 0;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--dark-color);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-coffee me-2"></i>{{ $settings['site_name'] ?? 'Kafe Stok Takip' }}
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Dashboard - Sadece Admin, Manager ve Cashier görebilir -->
                    @if(auth()->user()->hasAnyRole(['admin', 'manager', 'cashier']))
                    <!-- Navbar Menü (Dashboard linki) -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" aria-label="Ana Sayfa" title="Ana Sayfa">
                            <i class="fas fa-home"></i>
                        </a>
                    </li>
                    @endif

                    <!-- Kafe Sistemi - Garson, Kasiyer, Manager ve Admin (Warehouse Manager görmez) -->
                    @if(auth()->user()->hasAnyRole(['admin', 'manager', 'waiter', 'cashier']))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('cafe.*') || request()->routeIs('tables.*') ? 'active' : '' }}" href="#" id="cafeDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-utensils me-1"></i>Kafe Sistemi
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item {{ request()->routeIs('cafe.index') ? 'active' : '' }}" href="{{ route('cafe.index') }}">
                                <i class="fas fa-coffee me-2"></i>Sipariş Al
                            </a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('cafe.orders') ? 'active' : '' }}" href="{{ route('cafe.orders') }}">
                                <i class="fas fa-list me-2"></i>Siparişler
                            </a></li>
                            @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item {{ request()->routeIs('tables.*') ? 'active' : '' }}" href="{{ route('tables.index') }}">
                                <i class="fas fa-table me-2"></i>Masa Yönetimi
                            </a></li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    <!-- Kasa Sistemi - Sadece Admin ve Manager (Warehouse Manager görmez) -->
                    @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('cash-register.*') ? 'active' : '' }}" href="{{ route('cash-register.index') }}">
                            <i class="fas fa-cash-register me-1"></i>Kasa
                        </a>
                    </li>
                    @endif

                    <!-- Stok Yönetimi - Sadece Admin ve Manager (Warehouse Manager görmez) -->
                    @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('categories.*') || request()->routeIs('products.*') ? 'active' : '' }}" href="#" id="stockDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-boxes me-1"></i>Stok Yönetimi
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                                <i class="fas fa-tags me-2"></i>Kategoriler
                            </a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                                <i class="fas fa-box me-2"></i>Ürünler
                            </a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('stock-movements.*') ? 'active' : '' }}" href="{{ route('stock-movements.index') }}">
                                <i class="fas fa-box me-2"></i>Stok Hareketleri
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('sales.*') ? 'active' : '' }}" href="{{ route('sales.index') }}">
                            <i class="fas fa-shopping-cart me-1"></i>Satışlar
                        </a>
                    </li>

                    @endif
                
                @if(auth()->user()->hasAnyRole(['admin', 'manager', 'warehouse_manager']))
                    <!-- DEPO YÖNETİMİ VE YAPILACAKLAR - Dropdown Menü -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('warehouse.*') || request()->routeIs('todos.*') ? 'active' : '' }}" href="#" id="managementDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cogs me-1"></i>Yönetim
                        </a>
                        <ul class="dropdown-menu">
                            
                            <li><a class="dropdown-item {{ request()->routeIs('warehouse.index') ? 'active' : '' }}" href="{{ route('warehouse.index') }}">
                                <i class="fas fa-warehouse me-2"></i>Depo Yönetimi
                            </a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('warehouse.movements') ? 'active' : '' }}" href="{{ route('warehouse.movements') }}">
                                <i class="fas fa-history me-2"></i>Depo Hareket Geçmişi
                            </a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('warehouse.reports') ? 'active' : '' }}" href="{{ route('warehouse.reports') }}">
                                <i class="fas fa-chart-bar me-2"></i>Depo Raporları
                            </a></li>
                            <li><hr class="dropdown-divider"></li>

                            <li><a class="dropdown-item {{ request()->routeIs('todos.index') ? 'active' : '' }}" href="{{ route('todos.index') }}">
                                <i class="fas fa-tasks me-2"></i>Yapılacaklar Listesi
                            </a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('todos.reports') ? 'active' : '' }}" href="{{ route('todos.reports') }}">
                                <i class="fas fa-chart-line me-2"></i>Görev Raporları
                            </a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('todos.movements') ? 'active' : '' }}" href="{{ route('todos.movements') }}">
                                <i class="fas fa-clock me-2"></i>Görev Hareket Geçmişi
                            </a></li>
                        </ul>
                    </li>
                @endif

                    <!-- Kullanıcı Yönetimi - Sadece Admin ve Manager (Warehouse Manager görmez) -->
                    @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                            <i class="fas fa-users me-1"></i>Kullanıcılar
                        </a>
                    </li>
                    @endif

                    <!-- Raporlar - Sadece Admin ve Manager (Warehouse Manager görmez) -->
                    @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                            <i class="fas fa-chart-bar me-1"></i>Raporlar
                        </a>
                    </li>
                    @endif
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>{{ auth()->user()->name ?? 'Admin' }}
                            <span class="role-badge bg-light text-dark">{{ auth()->user()->role_name }}</span>
                        </a>
                        <ul class="dropdown-menu" style="z-index: 9999;">
                            @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                            <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="fas fa-user-cog me-2"></i>Profil</a></li>
                            <li><a class="dropdown-item" href="{{ route('settings.index') }}"><i class="fas fa-cogs me-2"></i>Ayarlar</a></li>
                            <li><hr class="dropdown-divider"></li>
                            @endif
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item" onclick="return confirm('Çıkış yapmak istediğinizden emin misiniz?')">
                                        <i class="fas fa-sign-out-alt me-2"></i>Çıkış
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 dashboard-background">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qz-tray/qz-tray.js"></script>
    <script>
        // Auto hide only flash success alerts (dismissible)
        setTimeout(function() {
            var successAlerts = document.querySelectorAll('.alert-success.alert-dismissible');
            successAlerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Loading button animation
        function showLoading(button) {
            button.innerHTML = '<span class="loading"></span> Yükleniyor...';
            button.disabled = true;
        }

        // Form validation
        function validateForm(form) {
            var isValid = true;
            var inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            
            inputs.forEach(function(input) {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            return isValid;
        }

        // Auto-save functionality for forms
        function enableAutoSave(formId) {
            var form = document.getElementById(formId);
            if (form) {
                var inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(function(input) {
                    input.addEventListener('change', function() {
                        localStorage.setItem(formId + '_' + input.name, input.value);
                    });
                    
                    // Load saved values
                    var savedValue = localStorage.getItem(formId + '_' + input.name);
                    if (savedValue) {
                        input.value = savedValue;
                    }
                });
                
                // Clear saved data on successful submit
                form.addEventListener('submit', function() {
                    inputs.forEach(function(input) {
                        localStorage.removeItem(formId + '_' + input.name);
                    });
                });
            }
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    </script>
    
    @yield('scripts')
</body>
</html>