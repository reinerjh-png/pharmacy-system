<?php
// config/permisos.php
// Mapa de permisos por módulo → roles permitidos
// admin=1, cajero=2, almacenero=3

return [
    'ventas'       => [1, 2],        // admin y cajero
    'inventario'   => [1, 3],        // admin y almacenero
    'compras'      => [1, 3],        // admin y almacenero
    'proveedores'  => [1, 3],        // admin y almacenero
    'reportes'     => [1],           // solo admin
    'usuarios'     => [1],           // solo admin
    'ajustes'      => [1, 3],        // admin y almacenero
    'dashboard'    => [1],           // solo admin
    'recetas'      => [1, 2],        // admin y cajero
];
