<?php

namespace App\Http\Controllers\Cso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Everything the system and the Desk Office sent the representative: notices and status updates. */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->latest()->paginate(10);

        // Shown as "New" this once, then read.
        $request->user()->unreadNotifications()->whereIn('id', $notifications->pluck('id'))->update(['read_at' => now()]);

        return view('cso.notifications.index', ['notifications' => $notifications]);
    }
}
