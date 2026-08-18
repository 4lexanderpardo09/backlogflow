<?php

namespace App\Models;

use App\Core\Model;

class ContractExpiration extends Model
{
    protected string $table = 'contract_expirations';

    public function allWithApplication(): array
    {
        return $this->fetchAll(
            'SELECT c.*, a.name AS application_name
             FROM contract_expirations c
             JOIN applications a ON a.id = c.application_id
             ORDER BY c.expiration_date ASC'
        );
    }
}
