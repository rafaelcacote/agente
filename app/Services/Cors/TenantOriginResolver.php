<?php

namespace App\Services\Cors;

use App\Models\Conversation;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantOriginResolver
{
    public function resolveFromRequest(Request $request): ?Tenant
    {
        $slug = $this->resolveSlug($request);

        if ($slug !== null && $slug !== '') {
            return Tenant::query()
                ->where('slug', $slug)
                ->active()
                ->first();
        }

        $conversationUuid = $this->resolveConversationUuid($request);

        if ($conversationUuid === null) {
            return null;
        }

        $conversation = Conversation::query()
            ->where('uuid', $conversationUuid)
            ->with('tenant')
            ->first();

        return $conversation?->tenant;
    }

    private function resolveSlug(Request $request): ?string
    {
        $header = $request->header('X-Tenant-Slug');

        if (is_string($header) && $header !== '') {
            return trim($header);
        }

        $slug = $request->input('tenant_slug');

        return is_string($slug) && $slug !== '' ? trim($slug) : null;
    }

    private function resolveConversationUuid(Request $request): ?string
    {
        $routeUuid = $request->route('uuid');

        if (is_string($routeUuid) && $routeUuid !== '') {
            return $routeUuid;
        }

        $bodyUuid = $request->input('conversation_uuid');

        return is_string($bodyUuid) && $bodyUuid !== '' ? $bodyUuid : null;
    }
}
