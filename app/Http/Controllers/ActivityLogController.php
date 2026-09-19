<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->query('user_id');
        $action = $request->query('action');
        $module = $request->query('module');
        $search = $request->query('search');
        $date = $request->query('date');

        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($module) {
            $query->where('entity_type', $module);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        $users = User::orderBy('name')->get();
        $actions = ActivityLog::select('action')->distinct()->pluck('action');
        $modules = ActivityLog::select('entity_type')->whereNotNull('entity_type')->distinct()->pluck('entity_type');

        return view('activity_logs.index', compact('logs', 'users', 'actions', 'modules', 'userId', 'action', 'module', 'search', 'date'));
    }
}
