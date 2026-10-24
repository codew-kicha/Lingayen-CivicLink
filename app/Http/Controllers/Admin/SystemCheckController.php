<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\GoLiveChecks;
use Illuminate\View\View;

// The go-live checks on the web server's own PHP (shared hosts often run a different PHP for the
// command line, and may have no SSH at all). The private-file check runs in the browser instead.
class SystemCheckController extends Controller
{
    public function __invoke(GoLiveChecks $checks): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.system-check', [
            'results' => $checks->run(probeUrls: false),
            'privatePaths' => GoLiveChecks::PRIVATE_PATHS,
        ]);
    }
}
