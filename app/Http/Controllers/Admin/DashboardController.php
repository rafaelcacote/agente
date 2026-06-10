<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();

        $stats = [
            'tenants_active'       => Tenant::query()->active()->count(),
            'conversations_today'  => Conversation::query()->whereDate('created_at', $today)->count(),
            'messages_today'       => Message::query()->whereDate('created_at', $today)->count(),
            'tokens_today'         => (int) Message::query()->whereDate('created_at', $today)->sum('total_tokens'),
            'conversations_active' => Conversation::query()->active()->count(),
        ];

        $recentConversations = Conversation::query()
            ->with('tenant')
            ->withCount('messages')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentConversations'));
    }
}
