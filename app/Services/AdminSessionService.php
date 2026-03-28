<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminSessionService
{
    public function registerSession(Request $request, int $adminUserId): void
    {
        if (!$this->hasSessionTable()) {
            return;
        }

        DB::table('admin_sessions')->updateOrInsert(
            ['session_id' => $request->session()->getId()],
            [
                'admin_user_id' => $adminUserId,
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'last_activity_at' => now(),
                'revoked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function touch(Request $request): void
    {
        if (!$this->hasSessionTable()) {
            return;
        }

        DB::table('admin_sessions')
            ->where('session_id', $request->session()->getId())
            ->whereNull('revoked_at')
            ->update([
                'last_activity_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function revokeCurrent(Request $request): void
    {
        if (!$this->hasSessionTable()) {
            return;
        }

        DB::table('admin_sessions')
            ->where('session_id', $request->session()->getId())
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function revokeBySessionId(int $adminUserId, string $sessionId): bool
    {
        if (!$this->hasSessionTable()) {
            return false;
        }

        $updated = DB::table('admin_sessions')
            ->where('admin_user_id', $adminUserId)
            ->where('session_id', $sessionId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);

        return $updated > 0;
    }

    public function revokeOthers(int $adminUserId, string $currentSessionId): int
    {
        if (!$this->hasSessionTable()) {
            return 0;
        }

        return DB::table('admin_sessions')
            ->where('admin_user_id', $adminUserId)
            ->where('session_id', '!=', $currentSessionId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function isRevoked(string $sessionId): bool
    {
        if (!$this->hasSessionTable()) {
            return false;
        }

        return DB::table('admin_sessions')
            ->where('session_id', $sessionId)
            ->whereNotNull('revoked_at')
            ->exists();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActiveForAdmin(int $adminUserId): array
    {
        if (!$this->hasSessionTable()) {
            return [];
        }

        return DB::table('admin_sessions')
            ->where('admin_user_id', $adminUserId)
            ->whereNull('revoked_at')
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(fn ($row) => [
                'session_id' => (string) $row->session_id,
                'ip_address' => (string) ($row->ip_address ?? ''),
                'user_agent' => (string) ($row->user_agent ?? ''),
                'last_activity_at' => $row->last_activity_at,
                'created_at' => $row->created_at,
            ])
            ->all();
    }

    private function hasSessionTable(): bool
    {
        try {
            return Schema::hasTable('admin_sessions');
        } catch (\Throwable) {
            return false;
        }
    }
}
