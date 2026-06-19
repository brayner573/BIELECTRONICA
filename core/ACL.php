<?php
/**
 * FAXEL BI — Control de Acceso (ACL)
 */
class ACL
{
    private static array $permissions = [
        'superadmin' => [
            'admin.global', 'empresas.view', 'empresas.manage', 'audit.view'
        ],
        'empresa' => [
            'dashboard.view', 'config.view', 'config.manage', 'empleados.view', 
            'empleados.manage', 'sucursales.view', 'sucursales.manage', 'clientes.view', 
            'clientes.manage', 'productos.view', 'productos.manage', 'ventas.view', 
            'facturas.view', 'facturas.create', 'facturas.void', 'alertas.view', 
            'alertas.manage', 'prediccion.view', 'prediccion.train', 'chat.use', 
            'reportes.view', 'reportes.export'
        ],
        'gerente' => [
            'dashboard.view', 'clientes.view', 'productos.view', 'ventas.view', 
            'facturas.view', 'alertas.view', 'alertas.manage', 'prediccion.view', 
            'chat.use', 'reportes.view', 'reportes.export', 'sucursales.view'
        ],
        'analista' => [
            'dashboard.view', 'clientes.view', 'productos.view', 'ventas.view', 
            'alertas.view', 'prediccion.view', 'prediccion.train', 'chat.use', 
            'reportes.view'
        ],
        'vendedor' => [
            'dashboard.view', 'clientes.view', 'clientes.manage', 'productos.view', 
            'ventas.view', 'facturas.view', 'facturas.create', 'alertas.view'
        ],
        'operador' => [
            'dashboard.view', 'clientes.view', 'productos.view', 'ventas.view', 
            'facturas.view', 'alertas.view'
        ]
    ];

    /**
     * Verifica si un rol tiene un permiso específico.
     */
    public static function hasPermission(string $role, string $permission): bool
    {
        // El superadmin hereda los permisos de administrador de empresa
        if ($role === 'superadmin') {
            if (in_array($permission, self::$permissions['superadmin']) || 
                in_array($permission, self::$permissions['empresa'])) {
                return true;
            }
        }

        return isset(self::$permissions[$role]) && in_array($permission, self::$permissions[$role]);
    }

    /**
     * Retorna todos los permisos asociados a un rol.
     */
    public static function getPermissionsForRole(string $role): array
    {
        if ($role === 'superadmin') {
            return array_merge(self::$permissions['superadmin'], self::$permissions['empresa']);
        }
        return self::$permissions[$role] ?? [];
    }
}
