<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.audit.index', [
            'entries' => AuditLog::with('actor:id,name,email')
                ->when($request->input('action'), fn ($q, $action) => $q->where('action', 'like', "{$action}%"))
                ->when($request->input('actor'), fn ($q, $actor) => $q->where('actor_id', $actor))
                ->when($request->input('account'), fn ($q, $account) => $q->where('subject_type', 'User')->where('subject_id', $account))
                ->latest('created_at')
                ->latest('id')
                ->paginate(40)
                ->withQueryString(),
            'actions' => ['login', 'account', 'organization', 'accreditation', 'password'],
        ]);
    }
}
