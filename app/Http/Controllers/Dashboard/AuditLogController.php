<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $institutId = session('current_institut_id', Auth::user()->institut_id);

        $query = AuditLog::with('user:id,prenom,nom_famille,email')
            ->where('institut_id', $institutId);

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn ($w) => $w->where('label', 'like', "%$q%")
                ->orWhere('subject_id', $q));
        }

        $logs = $query->latest()->paginate(50)->withQueryString();

        $actions = AuditLog::where('institut_id', $institutId)
            ->select('action')->distinct()->pluck('action');
        $subjects = AuditLog::where('institut_id', $institutId)
            ->select('subject_type')->whereNotNull('subject_type')->distinct()->pluck('subject_type');

        return view('dashboard.audit.index', compact('logs', 'actions', 'subjects'));
    }
}
