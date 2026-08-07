<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historial de Operaciones — {{ $cliente->nombre }}</title>
    <style>
        @font-face {
            font-family: 'Montserrat';
            src: url('{{ resource_path('fonts/montserrat/Montserrat-Regular.ttf') }}') format('truetype');
            font-weight: 400;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('{{ resource_path('fonts/montserrat/Montserrat-Medium.ttf') }}') format('truetype');
            font-weight: 500;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('{{ resource_path('fonts/montserrat/Montserrat-SemiBold.ttf') }}') format('truetype');
            font-weight: 600;
        }
        @font-face {
            font-family: 'Montserrat';
            src: url('{{ resource_path('fonts/montserrat/Montserrat-Bold.ttf') }}') format('truetype');
            font-weight: 700;
        }

        @page { margin: 12mm 12mm 14mm 12mm; }

        body {
            font-family: 'Montserrat', 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #3F454C;
        }

        /* ── Encabezado ─────────────────────────────── */
        .head-row { width: 100%; border-collapse: collapse; }
        .head-row td { border: none; padding: 0; vertical-align: middle; }
        .logo { height: 14px; }
        .generado { text-align: right; color: #93A1A5; font-size: 8px; font-weight: 500; }

        .titulo { margin: 14px 0 2px; }
        .titulo h1 {
            font-size: 16px;
            font-weight: 700;
            color: #1D1D1B;
            margin: 0 0 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .accento {
            height: 2px;
            background-color: #005745;
            margin: 2px 0 8px;
        }
        .cliente { font-size: 10px; color: #3B444D; margin: 0; }
        .cliente .alias { color: #727276; }

        .banda {
            background-color: #3B444D;
            color: #ffffff;
            border-radius: 4px;
            margin-top: 10px;
            padding: 6px 10px;
            font-size: 9px;
            font-weight: 500;
        }
        .banda .isotipo { vertical-align: middle; margin-right: 6px; height: 12px; }
        .banda .filtros { vertical-align: middle; }

        /* ── Tabla ──────────────────────────────────── */
        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }
        table.datos th {
            background-color: #005745;
            color: #ffffff;
            font-weight: 600;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 6px 5px;
            text-align: left;
            border-bottom: 2px solid #D9C79E;
        }
        table.datos th.num { text-align: right; }
        table.datos td {
            padding: 5px;
            border-bottom: 1px solid #E3E6E8;
            font-size: 9px;
            vertical-align: middle;
        }
        table.datos td.num { text-align: right; }
        tr.zebra td { background-color: #F7F2E6; }
        table.datos td.usd { color: #005745; font-weight: 600; }
        table.datos td.ves { color: #3B444D; font-weight: 600; }
        table.datos td.tasa { color: #727276; }
        table.datos td.id { color: #93A1A5; }
        table.datos td.tipo { font-weight: 500; }
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 600;
        }
        .fila-total td {
            background-color: #3B444D !important;
            color: #ffffff;
            font-weight: 700;
            font-size: 10px;
            border-top: 2px solid #D9C79E;
        }
        .fila-total td.usd { color: #8FD0B8 !important; }
        .fila-total td.ves { color: #B9C2C9 !important; }
        .sin-operaciones {
            text-align: center;
            color: #727276;
            font-size: 10px;
            padding: 20px 0;
        }

        /* ── Pie ────────────────────────────────────── */
        .footer { margin-top: 18px; }
        .footer .linea { border: none; border-top: 1px solid #E3E6E8; margin: 0; }
        .footer .texto {
            text-align: center;
            color: #93A1A5;
            font-size: 8px;
            font-weight: 500;
            padding-top: 5px;
        }
        .footer .isotipo { vertical-align: middle; height: 11px; margin-right: 4px; }
    </style>
</head>
<body>

    @php
        $estadoMap = [
            'en_espera'  => ['En espera',   '#3B444D', '#E8EBED'],
            'solicitud'  => ['Solicitud',   '#3B444D', '#E6E9EB'],
            'en_progreso'=> ['En progreso', '#93A1A5', '#EDEFF0'],
            'cerrada'    => ['Cerrada',     '#005745', '#E0EFEA'],
            'cancelada'  => ['Cancelada',   '#AB0A3D', '#F6E9EC'],
            'revertida'  => ['Revertida',   '#6B5D33', '#F3ECD8'],
        ];
        $totUsd = 0;
        $totVes = 0;
    @endphp

    <table class="head-row">
        <tr>
            <td style="width:60%;">
                <img src="{{ public_path('img/intermedius-logo.png') }}" alt="Intermedius Group" class="logo" />
            </td>
            <td class="generado">Generado: {{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="titulo">
        <h1>Historial de Operaciones</h1>
        <div class="accento"></div>
        <p class="cliente">
            Cliente: <strong>{{ $cliente->nombre }}</strong>
            @if($cliente->alias)
                <span class="alias">· Alias: {{ $cliente->alias }}</span>
            @endif
            @if($cliente->telefono)
                <span class="alias">· Tel: {{ $cliente->telefono }}</span>
            @endif
        </p>
    </div>

    @php
        $fd = $filtros['fecha_desde'] ?? '';
        $fh = $filtros['fecha_hasta'] ?? '';
        $tc = $filtros['tipo_codigo'] ?? '';
        $tipos = [
            'compra_usd' => 'Compra USD',
            'venta_usd'  => 'Venta USD',
            'intermediada' => 'Intermediada',
            'comision'   => 'Comisión',
        ];
        $tcLabel = $tc ? ($tipos[$tc] ?? $tc) : 'Todos';
    @endphp
    <div class="banda">
        <img src="{{ public_path('img/intermedius-isotipo-blanco.png') }}" alt="" class="isotipo" />
        <span class="filtros">
            Período: {{ $fd ?: 'Inicio' }} — {{ $fh ?: 'Hoy' }}
            &nbsp;|&nbsp; Tipo: {{ $tcLabel }}
        </span>
    </div>

    <table class="datos">
        <thead>
            <tr>
                <th style="width:8%;">ID</th>
                <th style="width:15%;">Fecha</th>
                <th style="width:23%;">Tipo</th>
                <th style="width:15%;" class="num">USD</th>
                <th style="width:16%;" class="num">VES</th>
                <th style="width:11%;" class="num">Tasa</th>
                <th style="width:12%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($operaciones as $op)
                @php
                    $movUsd = $op->movimientos->first(fn($m) => $m->moneda->codigo === 'USD');
                    $movVes = $op->movimientos->first(fn($m) => $m->moneda->codigo === 'VES');
                    $usd = $movUsd ? abs((float) $movUsd->monto) : null;
                    $ves = $movVes ? abs((float) $movVes->monto) : null;
                    $totUsd += $usd ?? 0;
                    $totVes += $ves ?? 0;
                    [$etLabel, $etColor, $etBg] = $estadoMap[$op->estado] ?? [$op->estado, '#727276', '#EFEFEF'];
                @endphp
                <tr @if($loop->even) class="zebra" @endif>
                    <td class="id">#{{ $op->id }}</td>
                    <td>{{ $op->fecha ? $op->fecha->format('d/m/Y') : '—' }}</td>
                    <td class="tipo">{{ $op->tipoOperacion->nombre ?? '—' }}</td>
                    <td class="num usd">{{ $usd !== null ? '$' . number_format($usd, 2) : '—' }}</td>
                    <td class="num ves">{{ $ves !== null ? 'Bs. ' . number_format($ves, 2) : '—' }}</td>
                    <td class="num tasa">{{ $op->tasa_aplicada ? number_format($op->tasa_aplicada, 2) : '—' }}</td>
                    <td><span class="badge" style="background:{{ $etBg }}; color:{{ $etColor }};">{{ $etLabel }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="sin-operaciones">Sin operaciones en el período seleccionado.</td>
                </tr>
            @endforelse

            @if($operaciones->count() > 0)
                <tr class="fila-total">
                    <td colspan="3" style="text-align:right; text-transform:uppercase; letter-spacing:0.5px;">Total</td>
                    <td class="num usd">${{ number_format($totUsd, 2) }}</td>
                    <td class="num ves">Bs. {{ number_format($totVes, 2) }}</td>
                    <td></td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        <hr class="linea" />
        <div class="texto">
            <img src="{{ public_path('img/intermedius-isotipo-gris.png') }}" alt="" class="isotipo" />
            Intermedius Group · Sistema de Gestión de Casa de Cambio
        </div>
    </div>
</body>
</html>
