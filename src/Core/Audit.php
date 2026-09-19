<?php

declare(strict_types=1);

namespace Craw\Core;

/**
 * Journal des actions administrateur sensibles (RG-10).
 */
final class Audit
{
    public static function record(
        string $action,
        string $entity,
        ?int $entityId = null,
        array $details = [],
        ?string $ipHash = null
    ): void {
        try {
            Database::insert('audit_log', [
                'admin_user_id' => Auth::id(),
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'details' => $details === [] ? null : (string) json_encode($details, JSON_UNESCAPED_UNICODE),
                'ip_hash' => $ipHash,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $exception) {
            // L'audit ne doit jamais faire echouer l'action metier.
            Logger::error('Echec d\'ecriture du journal d\'audit.', ['message' => $exception->getMessage()]);
        }
    }
}
