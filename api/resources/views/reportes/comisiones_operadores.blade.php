<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        h1   { font-size: 14px; margin-bottom: 4px; }
        p.periodo { font-size: 10px; color: #555; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #1e3a5f; color: #fff; padding: 5px 8px; text-align: left; font-size: 10px; }
        td { padding: 4px 8px; border-bottom: 1px solid #ddd; font-size: 10px; }
        tr:nth-child(even) td { background: #f5f8fc; }
        .total td { font-weight: bold; background: #e8f0fe; }
    </style>
</head>
<body>
    <h1>Reporte de Comisiones — Operadores</h1>
    <p class="periodo">
        Período: {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}
        &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Titular / Operador</th>
                <th>Total Operaciones</th>
                <th>Total Comisiones (USD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos as $row)
            <tr>
                <td>{{ $row['titular'] }}</td>
                <td>{{ $row['total_operaciones'] }}</td>
                <td>{{ number_format($row['total_comisiones_usd'], 4) }}</td>
            </tr>
            @endforeach
            <tr class="total">
                <td>TOTAL</td>
                <td>{{ $datos->sum('total_operaciones') }}</td>
                <td>{{ number_format($datos->sum('total_comisiones_usd'), 4) }}</td>
            </tr>
        </tbody>
    </table>

    @foreach($datos as $row)
    <div style="page-break-before: always;">
        <h2 style="font-size:12px; margin-bottom:4px;">Detalle: {{ $row['titular'] }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Op. ID</th><th>Fecha</th><th>Descripción</th>
                    <th>Monto</th><th>Moneda</th><th>USD Equiv.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($row['detalle'] as $c)
                <tr>
                    <td>{{ $c->operacion_id }}</td>
                    <td>{{ optional($c->operacion)->fecha?->format('d/m/Y') }}</td>
                    <td>{{ $c->descripcion }}</td>
                    <td>{{ number_format($c->monto, 4) }}</td>
                    <td>{{ optional($c->moneda)->codigo }}</td>
                    <td>{{ number_format($c->monto_usd_equivalente, 4) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</body>
</html>
