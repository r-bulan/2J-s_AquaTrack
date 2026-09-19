<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        string $description = '',
        ?array $oldValues = null,
        ?array $newValues = null
    ): ActivityLog {
        $user = Auth::user();

        // Sanitize sensitive fields if present
        $sanitize = function (?array $data) {
            if (!$data) {
                return null;
            }
            $clean = $data;
            foreach (['password', 'password_confirmation', 'remember_token'] as $key) {
                if (isset($clean[$key])) {
                    $clean[$key] = '********';
                }
            }
            return $clean;
        };

        return ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'user_role' => $user?->role ?? 'system',
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'old_values' => $sanitize($oldValues),
            'new_values' => $sanitize($newValues),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
