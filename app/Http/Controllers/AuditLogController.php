<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    private array $actionBadgeMap = [
        'login' => 'success',
        'logout' => 'secondary',
        'login_failed' => 'warning',
        'login_blocked' => 'danger',
        'access_denied' => 'danger',
        'user_created' => 'success',
        'user_updated' => 'info',
        'user_deleted' => 'danger',
        'profile_updated' => 'info',
        'password_updated' => 'warning',
    ];

    public function index()
    {
        $actions = AuditLog::select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
        $causers = AuditLog::select('causer_username')
            ->distinct()
            ->orderBy('causer_username')
            ->pluck('causer_username')
            ->filter()
            ->prepend('System');
        $subjects = AuditLog::select('subject_username')
            ->whereNotNull('subject_username')
            ->distinct()
            ->orderBy('subject_username')
            ->pluck('subject_username');
        return view('pages.audit-logs', compact('actions', 'causers', 'subjects'));
    }

    private function formatActionLabel(string $action): string
    {
        return strtoupper(str_replace('_', ' ', $action));
    }

    private function getActionBadgeClass(string $action): string
    {
        $tone = $this->actionBadgeMap[$action] ?? 'primary';
        return "bg-label-{$tone}";
    }

    public function data(Request $request): JsonResponse
    {
        $query = AuditLog::query();
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('causer_username', 'like', "%{$search}%")
                    ->orWhere('subject_username', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
                if (strcasecmp($search, 'System') === 0) {
                    $q->orWhereNull('causer_username');
                }
            });
        }
        if ($action = $request->input('filter_action')) {
            $query->where('action', $action);
        }
        if ($causer = $request->input('filter_causer')) {
            if ($causer === 'System') {
                $query->whereNull('causer_username');
            } else {
                $query->where('causer_username', $causer);
            }
        }
        if ($subject = $request->input('filter_subject')) {
            $query->where('subject_username', $subject);
        }
        $recordsTotal = AuditLog::count();
        $recordsFiltered = $query->count();
        $orderColumnIndex = (int) $request->input('order.0.column', 5);
        $orderDir = in_array($request->input('order.0.dir'), ['asc', 'desc']) ? $request->input('order.0.dir') : 'desc';
        $columns = ['id', 'causer_username', 'action', 'subject_username', 'ip_address', 'created_at', null];
        $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';
        if ($orderColumn) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        $logs = $query->skip((int) $request->input('start', 0))
            ->take(min((int) $request->input('length', 10), 100))
            ->get();
        $data = $logs->map(function ($log, $index) use ($request) {
            return [
                'id' => $request->input('start', 0) + $index + 1,
                'causer' => $log->causer_username ?? 'System',
                'action' => $log->action,
                'action_label' => $this->formatActionLabel($log->action),
                'action_badge' => $this->getActionBadgeClass($log->action),
                'subject' => $log->subject_username ?? '—',
                'ip_address' => $log->ip_address ?? '—',
                'date' => $log->created_at->format('d M Y, H:i'),
                'has_detail' => (bool) ($log->old_values || $log->new_values),
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
            ];
        })->values();
        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function myData(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $baseQuery = fn() => AuditLog::where(function ($q) use ($userId) {
            $q->where('causer_id', $userId)
                ->orWhere('subject_id', $userId);
        });
        $query = $baseQuery();
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('causer_username', 'like', "%{$search}%")
                    ->orWhere('subject_username', 'like', "%{$search}%");
                if (strcasecmp($search, 'System') === 0) {
                    $q->orWhereNull('causer_username');
                }
            });
        }
        $recordsTotal = $baseQuery()->count();
        $recordsFiltered = $query->count();
        $orderColumnIndex = (int) $request->input('order.0.column', 4);
        $orderDir = in_array($request->input('order.0.dir'), ['asc', 'desc']) ? $request->input('order.0.dir') : 'desc';
        $columns = ['id', 'causer_username', 'action', 'subject_username', 'created_at', null];
        $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';
        if ($orderColumn) {
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        $logs = $query->skip((int) $request->input('start', 0))
            ->take(min((int) $request->input('length', 10), 100))
            ->get();
        $data = $logs->map(function ($log, $index) use ($request) {
            return [
                'id' => $request->input('start', 0) + $index + 1,
                'causer' => $log->causer_username ?? 'System',
                'action' => $log->action,
                'action_label' => $this->formatActionLabel($log->action),
                'action_badge' => $this->getActionBadgeClass($log->action),
                'subject' => $log->subject_username ?? '—',
                'date' => $log->created_at->format('d M Y, H:i'),
                'has_detail' => (bool) ($log->old_values || $log->new_values),
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
            ];
        })->values();
        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }
}