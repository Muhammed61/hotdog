<?php

namespace App\Traits;

use App\Models\UserActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            self::logActivity('create', $model);
        });

        static::updated(function ($model) {
            self::logActivity('update', $model);
        });

        static::deleted(function ($model) {
            self::logActivity('delete', $model);
        });
    }

    protected static function logActivity($action, $model, $description = null)
    {
        if (!Auth::check()) {
            return;
        }

        $agent = new Agent();
        
        // Sistem tipini belirle
        $systemType = self::determineSystemType($model);
        
        // Açıklama oluştur
        if (!$description) {
            $description = self::generateDescription($action, $model);
        }

        // Eski ve yeni değerleri al
        $oldValues = null;
        $newValues = null;

        if ($action === 'update' && $model->isDirty()) {
            $oldValues = $model->getOriginal();
            $newValues = $model->getDirty();
        } elseif ($action === 'create') {
            $newValues = $model->getAttributes();
        }

        UserActivity::create([
            'user_id' => Auth::id(),
            'system_type' => $systemType,
            'action' => $action,
            'model' => class_basename($model),
            'model_id' => $model->id ?? null,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            'browser' => $agent->browser(),
            'platform' => $agent->platform()
        ]);
    }

    protected static function determineSystemType($model)
    {
        $stockModels = ['Product', 'Category', 'StockMovement'];
        $cafeModels = ['CafeOrder', 'CafeOrderItem', 'CafeOrderNote', 'Table', 'CashTransaction', 'Sale', 'SaleItem'];

        $modelName = class_basename($model);

        if (in_array($modelName, $stockModels)) {
            return 'stock';
        } elseif (in_array($modelName, $cafeModels)) {
            return 'cafe';
        }

        return 'cafe'; // Varsayılan cafe yap
    }

    protected static function generateDescription($action, $model)
    {
        $modelName = class_basename($model);
        $modelNames = [
            'Product' => 'Ürün',
            'Category' => 'Kategori',
            'StockMovement' => 'Stok Hareketi',
            'Sale' => 'Satış',
            'SaleItem' => 'Satış Kalemi',
            'CafeOrder' => 'Kafe Siparişi',
            'CafeOrderItem' => 'Sipariş Kalemi',
            'CafeOrderNote' => 'Sipariş Notu',
            'Table' => 'Masa',
            'CashTransaction' => 'Kasa İşlemi',
            'User' => 'Kullanıcı'
        ];

        $actions = [
            'create' => 'oluşturdu',
            'update' => 'güncelledi',
            'delete' => 'sildi'
        ];

        $modelDisplayName = $modelNames[$modelName] ?? $modelName;
        $actionDisplayName = $actions[$action] ?? $action;

        $identifier = '';
        if (isset($model->name)) {
            $identifier = " ({$model->name})";
        } elseif (isset($model->id)) {
            $identifier = " (ID: {$model->id})";
        }

        return "{$modelDisplayName}{$identifier} {$actionDisplayName}";
    }

    public static function logCustomActivity($systemType, $action, $description, $modelName = null, $modelId = null)
    {
        if (!Auth::check()) {
            return;
        }

        $agent = new Agent();

        UserActivity::create([
            'user_id' => Auth::id(),
            'system_type' => $systemType,
            'action' => $action,
            'model' => $modelName,
            'model_id' => $modelId,
            'description' => $description,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            'browser' => $agent->browser(),
            'platform' => $agent->platform()
        ]);
    }
}