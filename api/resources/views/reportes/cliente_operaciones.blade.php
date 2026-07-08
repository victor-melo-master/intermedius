<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Historial de Operaciones</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f5f5f5; }
        .header { margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin-bottom: 5px; }
        .filtros { font-size: 11px; color: #666; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Historial de Operaciones — {{ $cliente->nombre }}</h1>
        @if($cliente->alias)
            <p>Alias: {{ $cliente->alias }}</p>
        @endif
        @php
            $fd = $filtros['fecha_desde'] ?? '';
            $fh = $filtros['fecha_hasta'] ?? '';
            $tc = $filtros['tipo_codigo'] ?? '';
        @endphp
        <div class="filtros">
            @if($fd || $fh)
                Período: {{ $fd ?: '...' }} — {{ $fh ?: '...' }}
            @endif
            @if($tc)
                | Tipo: {{ $tc }}
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Monto USD</th>
                <th>Monto VES</th>
                <th>Tasa</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody>
            @foreach($operaciones as $op)
                <tr>
                    <td>#{{ $op->id }}</td>
                    <td>{{ $op->fecha ? $op->fecha->format('d/m/Y') : '—' }}</td>
                    <td>{{ $op->tipoOperacion->nombre ?? '—' }}</td>
                    <td>
                        @php
                            $movUsd = $op->movimientos->first(fn($m) => $m->moneda->codigo === 'USD');
                        @endphp
                        {{ $movUsd ? number_format(abs($movUsd->monto), 2) : '—' }}
                    </td>
                    <td>
                        @php
                            $movVes = $op->movimientos->first(fn($m) => $m->moneda->codigo === 'VES');
                        @endphp
                        {{ $movVes ? number_format(abs($movVes->monto), 2) : '—' }}
                    </td>
                    <td>{{ $op->tasa_aplicada ? number_format($op->tasa_aplicada, 2) : '—' }}</td>
                    <td>{{ $op->estatus }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
