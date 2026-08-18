<?php

namespace App\Models;

use App\Core\Model;

/**
 * Generic data access for every cat_* lookup table. All of them share the
 * same shape (id, code[, sort_order]), so one class serves them all instead
 * of duplicating a model per catalog.
 */
class Catalog extends Model
{
    private const ALLOWED_TABLES = [
        'cat_priorities', 'cat_project_statuses', 'cat_backlog_statuses', 'cat_activity_statuses',
        'cat_backlog_types', 'cat_activity_types', 'cat_application_types', 'cat_modalities',
        'cat_criticality_levels', 'cat_support_types',
    ];

    public function __construct(string $table)
    {
        if (!in_array($table, self::ALLOWED_TABLES, true)) {
            throw new \InvalidArgumentException("Unknown catalog table: {$table}");
        }

        $this->table = $table;
        parent::__construct();
    }

    public function all(string $orderBy = 'id ASC'): array
    {
        $hasSortOrder = str_contains($this->table, 'statuses') || $this->table === 'cat_priorities' || $this->table === 'cat_criticality_levels';
        $order = $hasSortOrder ? 'sort_order ASC' : 'code ASC';

        return parent::all($order);
    }

    public function isReferenced(int $id): bool
    {
        $referencingColumns = $this->referencingColumns();

        if ($referencingColumns === []) {
            return false;
        }

        $params = [];
        $clauses = [];

        foreach ($referencingColumns as $i => [$refTable, $refColumn]) {
            $paramName = "id{$i}";
            $clauses[] = "(SELECT COUNT(*) FROM {$refTable} WHERE {$refColumn} = :{$paramName})";
            $params[$paramName] = $id;
        }

        $row = $this->fetchOne('SELECT (' . implode(' + ', $clauses) . ') AS total', $params);

        return (int) ($row['total'] ?? 0) > 0;
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function referencingColumns(): array
    {
        return match ($this->table) {
            'cat_priorities' => [['projects', 'priority_id'], ['backlog_items', 'priority_id'], ['activities', 'priority_id']],
            'cat_project_statuses' => [['projects', 'status_id']],
            'cat_backlog_statuses' => [['backlog_items', 'status_id']],
            'cat_activity_statuses' => [['activities', 'status_id']],
            'cat_backlog_types' => [['backlog_items', 'type_id']],
            'cat_activity_types' => [['activities', 'type_id']],
            'cat_application_types' => [['applications', 'application_type_id']],
            'cat_modalities' => [['application_ownership', 'modality_id']],
            'cat_criticality_levels' => [['applications', 'criticality_id']],
            'cat_support_types' => [['application_support_type', 'support_type_id']],
            default => [],
        };
    }
}
