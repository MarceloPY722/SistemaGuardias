<?php
/**
 * Archivo de funciones comunes para el sistema de guardias
 */

/**
 * Formatea una fecha para mostrarla en formato dd/mm/yyyy
 */
function formatearFecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

/**
 * Genera un badge HTML con el estado correspondiente
 */
function generarBadgeEstado($estado) {
    $clase = '';
    switch ($estado) {
        case 'Pendiente':
            $clase = 'warning';
            break;
        case 'En progreso':
            $clase = 'info';
            break;
        case 'Completada':
            $clase = 'success';
            break;
        case 'Incumplida':
            $clase = 'danger';
            break;
        case 'Reasignada':
            $clase = 'secondary';
            break;
        default:
            $clase = 'secondary';
    }
    
    return '<span class="badge bg-' . $clase . '">' . $estado . '</span>';
}

/**
 * Verifica si el usuario tiene permisos para acceder a una acción
 */
function tienePermiso($accion) {
    // Implementar lógica de permisos según sea necesario
    return true;
}