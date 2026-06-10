<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Conversation::query()
            ->with('tenant')
            ->withCount('messages');

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        if ($request->filled('source')) {
            $query->where('source', 'like', '%'.$request->string('source')->trim().'%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from')->value());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to')->value());
        }

        $conversations = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name', 'slug']);

        return view('admin.conversations.index', compact('conversations', 'tenants'));
    }

    public function show(Conversation $conversation): View
    {
        $conversation->load(['tenant', 'messages']);

        $tokenStats = [
            'prompt'     => $conversation->messages->sum('prompt_tokens'),
            'completion' => $conversation->messages->sum('completion_tokens'),
            'total'      => $conversation->messages->sum('total_tokens'),
        ];

        return view('admin.conversations.show', compact('conversation', 'tokenStats'));
    }

    public function close(Conversation $conversation): RedirectResponse
    {
        if ($conversation->isActive()) {
            $conversation->close();
        }

        return redirect()
            ->route('admin.conversations.show', $conversation)
            ->with('success', 'Conversa encerrada.');
    }
}
