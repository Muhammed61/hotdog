<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        $query = Todo::where('user_id', Auth::id());
    
        // Filtreler
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
    
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
    
        if ($request->filled('date')) {
            $query->whereDate('due_date', $request->date);
        }
    
        // Zaman filtreleri - Bugün ve Bu Hafta
        if ($request->filled('filter_type')) {
            switch ($request->filter_type) {
                case 'today':
                    $query->whereDate('due_date', today());
                    break;
                case 'this_week':
                    $query->whereBetween('due_date', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
            }
        }

        // Sayfalama miktarı (varsayılan 15)
        $perPage = $request->get('per_page', 10);
        
        $todos = $query->orderBy('due_date', 'asc')
                      ->orderBy('priority', 'desc')
                      ->orderBy('created_at', 'desc')
                      ->paginate($perPage);

        // Özet bilgiler
        $stats = [
            'total' => Todo::where('user_id', Auth::id())->count(),
            'pending' => Todo::where('user_id', Auth::id())->where('status', 'pending')->count(),
            'in_progress' => Todo::where('user_id', Auth::id())->where('status', 'in_progress')->count(),
            'completed' => Todo::where('user_id', Auth::id())->where('status', 'completed')->count(),
            'overdue' => Todo::where('user_id', Auth::id())
                            ->where('status', '!=', 'completed')
                            ->whereDate('due_date', '<', now())
                            ->count(),
            'today' => Todo::where('user_id', Auth::id())
                          ->whereDate('due_date', today())
                          ->count(),
            'this_week' => Todo::where('user_id', Auth::id())
                              ->whereBetween('due_date', [
                                  now()->startOfWeek(),
                                  now()->endOfWeek()
                              ])
                              ->count()
        ];

        return view('todos.index', compact('todos', 'stats'));
    }

    public function movements(Request $request)
    {
        $query = Todo::where('user_id', Auth::id())->with('user');

        // Tarih filtreleri
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Durum filtresi
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Öncelik filtresi
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $todos = $query->orderBy('updated_at', 'desc')->paginate(5);

        // Özet bilgiler (filtrelere göre)
        $totalCompleted = Todo::where('user_id', Auth::id())
            ->where('status', 'completed')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('completed_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('completed_at', '<=', $request->date_to);
            })
            ->count();

        $totalPending = Todo::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->count();

        $totalInProgress = Todo::where('user_id', Auth::id())
            ->where('status', 'in_progress')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->count();

        return view('todos.movements', compact('todos', 'totalCompleted', 'totalPending', 'totalInProgress'));
    }

    public function reports(Request $request)
    {
        $query = Todo::where('user_id', Auth::id())->with('user');

        // Tarih filtreleri
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Durum filtresi
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Öncelik filtresi
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $todos = $query->orderBy('created_at', 'desc')->paginate(5);

        // Özet bilgiler
        $totalCompleted = Todo::where('user_id', Auth::id())
            ->where('status', 'completed')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('completed_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('completed_at', '<=', $request->date_to);
            })
            ->count();

        $totalPending = Todo::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->count();

        $totalInProgress = Todo::where('user_id', Auth::id())
            ->where('status', 'in_progress')
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->count();

        // Öncelik bazında özet
        $prioritySummary = Todo::where('user_id', Auth::id())
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->selectRaw('priority, 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress')
            ->groupBy('priority')
            ->get();

        // Aylık özet
        $monthlySummary = Todo::where('user_id', Auth::id())
            ->when($request->filled('date_from'), function($q) use ($request) {
                return $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($q) use ($request) {
                return $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month,
                        COUNT(*) as total,
                        SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        return view('todos.reports', compact(
            'todos', 
            'totalCompleted', 
            'totalPending', 
            'totalInProgress',
            'prioritySummary',
            'monthlySummary'
        ));
    }


    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date|after_or_equal:today'
        ]);

        $todo = Todo::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'user_id' => Auth::id()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Görev başarıyla eklendi!',
                'todo' => $todo
            ]);
        }

        return redirect()->route('todos.index')->with('success', 'Görev başarıyla eklendi!');
    }

    public function edit(Todo $todo)
    {
        // Admin ve depo yöneticisi tüm görevleri düzenleyebilir, diğer kullanıcılar sadece kendi görevlerini
        if ($todo->user_id !== Auth::id() && !Auth::user()->isAdmin() && !Auth::user()->isWarehouseManager()) {
            abort(403);
        }

        // Tarihi Y-m-d formatında döndür (timezone sorununu çözmek için)
        $todoData = $todo->toArray();
        if ($todoData['due_date']) {
            // Carbon ile tarihi Y-m-d formatına çevir
            $todoData['due_date'] = \Carbon\Carbon::parse($todoData['due_date'])->format('Y-m-d');
        }

        return response()->json([
            'success' => true,
            'todo' => $todoData
        ]);
    }

    public function update(Request $request, Todo $todo)
    {
        // Admin ve depo yöneticisi tüm görevleri güncelleyebilir, diğer kullanıcılar sadece kendi görevlerini
        if ($todo->user_id !== Auth::id() && !Auth::user()->isAdmin() && !Auth::user()->isWarehouseManager()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,in_progress,completed',
            'due_date' => 'nullable|date'
        ]);

        $data = $request->only(['title', 'description', 'priority', 'status', 'due_date']);

        // Eğer completed yapılıyorsa completed_at'i set et
        if ($request->status === 'completed' && $todo->status !== 'completed') {
            $data['completed_at'] = now();
        } elseif ($request->status !== 'completed') {
            $data['completed_at'] = null;
        }

        $todo->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Görev başarıyla güncellendi!',
                'todo' => $todo->fresh()
            ]);
        }

        return redirect()->route('todos.index')->with('success', 'Görev başarıyla güncellendi!');
    }

    public function destroy(Todo $todo)
    {
        // Admin ve depo yöneticisi tüm görevleri silebilir, diğer kullanıcılar sadece kendi görevlerini
        if ($todo->user_id !== Auth::id() && !Auth::user()->isAdmin() && !Auth::user()->isWarehouseManager()) {
            abort(403);
        }

        $todo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Görev başarıyla silindi!'
        ]);
    }

    public function toggleStatus(Todo $todo)
    {
        // Admin ve depo yöneticisi tüm görevleri değiştirebilir, diğer kullanıcılar sadece kendi görevlerini
        if ($todo->user_id !== Auth::id() && !Auth::user()->isAdmin() && !Auth::user()->isWarehouseManager()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu görevi değiştirme yetkiniz yok!'
            ], 403);
        }

        $newStatus = $todo->status === 'completed' ? 'pending' : 'completed';
        
        $todo->update([
            'status' => $newStatus,
            'completed_at' => $newStatus === 'completed' ? now() : null
        ]);

        return response()->json([
            'success' => true,
            'message' => $newStatus === 'completed' ? 'Görev tamamlandı!' : 'Görev beklemede!',
            'todo' => $todo->fresh()
        ]);
    }
}