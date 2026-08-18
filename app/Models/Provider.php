<?php

namespace App\Models;

use App\Core\Model;

class Provider extends Model
{
    protected string $table = 'providers';

    public function applicationsFor(int $providerId): array
    {
        return $this->fetchAll(
            'SELECT a.id, a.name FROM applications a
             JOIN application_provider ap ON ap.application_id = a.id
             WHERE ap.provider_id = :id',
            ['id' => $providerId]
        );
    }

    public function isReferenced(int $id): bool
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM application_provider WHERE provider_id = :id',
            ['id' => $id]
        );

        return (int) ($row['total'] ?? 0) > 0;
    }
}
