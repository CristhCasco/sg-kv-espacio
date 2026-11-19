<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Carga el autoload de Composer
require __DIR__.'/../../system/calle10/vendor/autoload.php';

// Carga la aplicación Laravel
$app = require_once __DIR__.'/../../system/calle10/bootstrap/app.php';

// Verifica si la aplicación está en modo mantenimiento
if (file_exists(__DIR__.'/../../system/calle10/storage/framework/maintenance.php')) {
    require __DIR__.'/../../system/calle10/storage/framework/maintenance.php';
}

// Inicia el kernel HTTP y maneja la solicitud
$kernel = $app->make(Kernel::class);

$response = tap($kernel->handle(
    $request = Request::capture()
))->send();

$kernel->terminate($request, $response);