<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Clase base para los controladores de la aplicación.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
