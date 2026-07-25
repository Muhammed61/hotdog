<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Todo extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'user_id'
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',  // Sadece tarih formatında döndür
        'completed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }

    public function isDueToday()
    {
        return $this->due_date && $this->due_date->isToday();
    }

    public function isDueTomorrow()
    {
        return $this->due_date && $this->due_date->isTomorrow();
    }

    public function getPriorityBadgeClass()
    {
        return match($this->priority) {
            'high' => 'bg-danger',
            'medium' => 'bg-warning',
            'low' => 'bg-success',
            default => 'bg-secondary'
        };
    }

    public function getStatusBadgeClass()
    {
        return match($this->status) {
            'completed' => 'bg-success',
            'in_progress' => 'bg-info',
            'pending' => 'bg-secondary',
            default => 'bg-secondary'
        };
    }

    public function getStatusText()
    {
        return match($this->status) {
            'completed' => 'Tamamlandı',
            'in_progress' => 'Devam Ediyor',
            'pending' => 'Bekliyor',
            default => 'Bilinmiyor'
        };
    }

    public function getPriorityText()
    {
        return match($this->priority) {
            'high' => 'Yüksek',
            'medium' => 'Orta',
            'low' => 'Düşük',
            default => 'Bilinmiyor'
        };
    }
}