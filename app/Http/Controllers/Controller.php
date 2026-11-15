<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Respuesta dual: JSON para clientes API y redirección con
     * mensaje flash para formularios web.
     */
    protected function respuesta(Request $request, string $mensaje, array $data = [], int $status = 200)
    {
        return $request->expectsJson()
            ? response()->json(array_merge(['message' => $mensaje], $data), $status)
            : redirect()->back()->with('success', $mensaje);
    }
}
