<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Support\ListPagination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.audit.index', [
            'entries' => ListPagination::paginate(AuditLog::with('actor:id,name,email')
                ->when($request->input('action'), fn ($q, $action) => $q->where('action', 'like', "{$action}%"))
                ->when($request->input('actor'), fn ($q, $actor) => $q->where('actor_id', $actor))
                ->when($request->input('account'), fn ($q, $account) => $q->where('subject_type', 'User')->where('subject_id', $account))
                // An organization's full history (linked from its page); uses the (subject_type, subject_id) index.
                ->when($request->input('organization'), fn ($q, $organization) => $q->where('subject_type', 'Organization')->where('subject_id', $organization))
                ->latest('created_at')
                ->latest('id'),
                $request),
            'organization' => $request->filled('organization') ? Organization::find($request->input('organization')) : null,
            'actions' => ['login', 'account', 'organization', 'accreditation', 'password'],
        ]);
    }
}
