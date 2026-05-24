<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class AuditLogger
{
    /**
     * Keys that should never be persisted into old_values / new_values.
     */
    private const SENSITIVE_KEYS = [
        'password',
        'remember_token',
        'code_hash',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
        'access_token',
        'refresh_token',
    ];

    /**
     * Generic audit entry. Use the helpers below when possible.
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): ?AuditLog {
        try {
            $request = request();

            return AuditLog::create([
                'user_id'        => auth()->id(),
                'action'         => $action,
                'module'         => $module,
                'description'    => $description,
                'auditable_type' => $auditable?->getMorphClass(),
                'auditable_id'   => $auditable?->getKey(),
                'old_values'     => self::sanitize($oldValues),
                'new_values'     => self::sanitize($newValues),
                'ip_address'     => $request?->ip(),
                'user_agent'     => self::truncate($request?->userAgent(), 255),
                'created_at'     => now(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    public static function logCreated(Model $model, string $module, string $description): ?AuditLog
    {
        return self::log(
            'created',
            $module,
            $description,
            $model,
            null,
            self::sanitize($model->getAttributes()),
        );
    }

    /**
     * @param array<string, mixed> $original  the snapshot captured BEFORE the update
     */
    public static function logUpdated(Model $model, string $module, string $description, array $original): ?AuditLog
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return null;
        }

        $old = [];
        foreach (array_keys($changes) as $key) {
            $old[$key] = array_key_exists($key, $original) ? $original[$key] : null;
        }

        return self::log('updated', $module, $description, $model, $old, $changes);
    }

    public static function logArchived(Model $model, string $module, string $description): ?AuditLog
    {
        return self::log(
            'archived',
            $module,
            $description,
            $model,
            ['archived_at' => null],
            ['archived_at' => self::carbonString($model, 'archived_at')],
        );
    }

    public static function logRestored(Model $model, string $module, string $description): ?AuditLog
    {
        return self::log(
            'restored',
            $module,
            $description,
            $model,
            ['archived_at' => 'previous'],
            ['archived_at' => null],
        );
    }

    private static function carbonString(Model $model, string $attribute): ?string
    {
        $value = $model->{$attribute};
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    private static function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return collect($values)
            ->except(self::SENSITIVE_KEYS)
            ->all();
    }

    private static function truncate(?string $value, int $limit): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit) : $value;
    }
}
