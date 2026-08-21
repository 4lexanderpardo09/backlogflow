<?php

namespace App\Helpers;

/**
 * Central Spanish-language label map for every English catalog code used
 * in the database, so views never hardcode translated strings and every
 * screen stays consistent. Also holds the shared "no data" placeholders
 * required by the SLA spec (never invent missing provider/contract data).
 */
class Labels
{
    public const NOT_DEFINED = 'Por definir';
    public const PENDING_VALIDATION = 'Pendiente de validar';
    public const IN_HOUSE_PROVIDER = 'Desarrollo interno / Área de Sistemas';

    private const MAP = [
        'priority' => [
            'critical' => 'Crítica', 'high' => 'Alta', 'medium' => 'Media', 'low' => 'Baja',
        ],
        'project_status' => [
            'not_started' => 'Sin iniciar', 'in_progress' => 'En progreso', 'on_hold' => 'En pausa',
            'completed' => 'Terminado', 'delayed' => 'Atrasado', 'cancelled' => 'Cancelado',
        ],
        'backlog_status' => [
            'pending' => 'Pendiente', 'in_analysis' => 'En análisis', 'in_development' => 'En desarrollo',
            'in_testing' => 'En pruebas', 'blocked' => 'Bloqueado', 'completed' => 'Terminado', 'cancelled' => 'Cancelado',
        ],
        'activity_status' => [
            'pending' => 'Pendiente', 'in_progress' => 'En progreso', 'blocked' => 'Bloqueada',
            'overdue' => 'Vencida', 'completed' => 'Terminada', 'cancelled' => 'Cancelada',
        ],
        'backlog_type' => [
            'new_development' => 'Desarrollo nuevo', 'migration' => 'Migración', 'maintenance' => 'Mantenimiento',
            'integration' => 'Integración', 'support' => 'Soporte', 'other' => 'Otro',
        ],
        'activity_type' => [
            'analysis' => 'Análisis', 'development' => 'Desarrollo', 'testing' => 'Pruebas',
            'deployment' => 'Despliegue', 'documentation' => 'Documentación', 'data_cleanup' => 'Limpieza de datos', 'other' => 'Otro',
        ],
        'developer_status' => [
            'active' => 'Activo', 'inactive' => 'Inactivo',
        ],
        'application_type' => [
            'erp' => 'ERP', 'crm' => 'CRM', 'accounting' => 'Contabilidad', 'payroll' => 'Nómina',
            'collections' => 'Cartera', 'inventory' => 'Inventario', 'fixed_assets' => 'Activos fijos',
            'help_desk' => 'Mesa de ayuda', 'document_management' => 'Gestión documental',
            'bi_analytics' => 'BI / Analítica', 'email' => 'Correo electrónico', 'security' => 'Seguridad',
            'infrastructure' => 'Infraestructura', 'web_app' => 'Aplicación web', 'mobile_app' => 'Aplicación móvil', 'other' => 'Otro',
        ],
        'modality' => [
            'in_house_development' => 'Desarrollo propio', 'outsourced_development' => 'Desarrollo tercerizado',
            'perpetual_license' => 'Licencia perpetua', 'subscription_license' => 'Licencia por suscripción',
            'saas' => 'SaaS', 'cloud' => 'Cloud', 'lease' => 'Arrendamiento', 'managed_service' => 'Servicio administrado',
            'open_source' => 'Open Source', 'other' => 'Otro',
        ],
        'ownership' => [
            'in_house' => 'Desarrollo interno', 'outsourced' => 'Desarrollo/proveedor externo',
        ],
        'criticality' => [
            'critical' => 'Crítica', 'high' => 'Alta', 'medium' => 'Media', 'low' => 'Baja',
        ],
        'application_status' => [
            'active' => 'Activa', 'in_implementation' => 'En implementación', 'retired' => 'Retirada',
        ],
        'support_type_level' => [
            1 => 'Nivel 1', 2 => 'Nivel 2',
        ],
        'request_type' => [
            'incident' => 'Incidente', 'request' => 'Solicitud', 'requirement' => 'Requerimiento',
            'problem' => 'Problema', 'change' => 'Cambio',
        ],
        'contract_type' => [
            'contract' => 'Contrato', 'license' => 'Licencia', 'certificate' => 'Certificado',
            'domain' => 'Dominio', 'cloud' => 'Servicio cloud', 'provider_support' => 'Soporte de proveedor',
        ],
        'traffic_light' => [
            'green' => 'Al día', 'yellow' => 'En riesgo', 'red' => 'Atrasado',
        ],
        'contract_alert' => [
            'expired' => 'Vencido', 'lt_30' => 'Menos de 30 días', 'd30_60' => 'Entre 30 y 60 días',
            'd60_90' => 'Entre 60 y 90 días', 'gt_90' => 'Más de 90 días',
        ],
    ];

    public static function get(string $group, string|int|null $code): string
    {
        if ($code === null || $code === '') {
            return self::NOT_DEFINED;
        }

        return self::MAP[$group][$code] ?? (string) $code;
    }

    public static function options(string $group): array
    {
        return self::MAP[$group] ?? [];
    }
}
