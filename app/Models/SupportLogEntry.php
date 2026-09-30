<?php

namespace App\Models;

use App\Core\Model;

/**
 * Pure data access for the support_logs table (daily support log).
 */
class SupportLogEntry extends Model
{
    protected string $table = 'support_logs';

    /**
     * @param array{developer_id?:int,category?:string,helpdesk?:string,desde?:?string,hasta?:?string} $filters
     */
    public function filtered(array $filters): array
    {
        $where = [];
        $params = [];

        if (($filters['developer_id'] ?? 0) > 0) {
            $where[] = 's.developer_id = :developer_id';
            $params['developer_id'] = $filters['developer_id'];
        }
        if (($filters['category'] ?? '') !== '') {
            $where[] = 's.category = :category';
            $params['category'] = $filters['category'];
        }
        if (in_array($filters['helpdesk'] ?? '', ['1', '0'], true)) {
            $where[] = 's.in_helpdesk = :helpdesk';
            $params['helpdesk'] = (int) $filters['helpdesk'];
        }
        if (!empty($filters['desde'])) {
            $where[] = 's.log_date >= :desde';
            $params['desde'] = $filters['desde'];
        }
        if (!empty($filters['hasta'])) {
            $where[] = 's.log_date <= :hasta';
            $params['hasta'] = $filters['hasta'];
        }

        $sql = 'SELECT s.*, d.name AS developer_name
                FROM support_logs s
                JOIN developers d ON d.id = s.developer_id'
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY s.log_date DESC, s.id DESC';

        return $this->fetchAll($sql, $params);
    }
}
