<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Citas — {{ $business->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p { margin: 0 0 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 6px; text-align: left; }
        th { background: #0f766e; color: #fff; }
    </style>
</head>
<body>
    <h1>Citas — {{ $business->name }}</h1>
    <p>Periodo: {{ $desde }} a {{ $hasta }}</p>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Profesional</th>
                <th>Servicio</th>
                <th>Cliente</th>
                <th>Teléfono</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($citas as $cita)
                <tr>
                    <td>{{ $cita->starts_at }}</td>
                    <td>{{ $cita->professional_name }}</td>
                    <td>{{ $cita->service_name }}</td>
                    <td>{{ $cita->customer_name }}</td>
                    <td>{{ $cita->customer_phone }}</td>
                    <td>{{ $cita->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
