<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Comisiones — Operadores</title>
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
        .periodo { font-size: 10px; color: #3B444D; margin: 0; }

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
        table.datos td.id { color: #93A1A5; }
        table.datos td.titular { font-weight: 500; }

        .fila-total td {
            background-color: #3B444D !important;
            color: #ffffff;
            font-weight: 700;
            font-size: 10px;
            border-top: 2px solid #D9C79E;
        }
        .fila-total td.usd { color: #8FD0B8 !important; }
        .sin-operaciones {
            text-align: center;
            color: #727276;
            font-size: 10px;
            padding: 20px 0;
        }

        /* ── Detalle por titular ────────────────────── */
        .detalle-titular { margin-top: 0; }
        .detalle-titular h2 {
            font-size: 12px;
            font-weight: 700;
            color: #1D1D1B;
            margin: 0 0 2px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .detalle-titular p { font-size: 9px; color: #727276; margin: 0 0 2px; }

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

    <table class="head-row">
        <tr>
            <td style="width:60%;">
                <img src="{{ public_path('img/intermedius-logo.png') }}" alt="Intermedius Group" class="logo" />
            </td>
            <td class="generado">Generado: {{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="titulo">
        <h1>Reporte de Comisiones</h1>
        <div class="accento"></div>
        <p class="periodo">
            Operadores &nbsp;·&nbsp; Período: {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}
        </p>
    </div>

    <div class="banda">
        <img src="{{ public_path('img/intermedius-isotipo-blanco.png') }}" alt="" class="isotipo" />
        <span class="filtros">
            {{ $datos->count() }} {{ $datos->count() === 1 ? 'operador con comisiones' : 'operadores con comisiones' }}
            &nbsp;|&nbsp; Período: {{ $desde->format('d/m/Y') }} — {{ $hasta->format('d/m/Y') }}
        </span>
    </div>

    <table class="datos">
        <thead>
            <tr>
                <th style="width:44%;">Titular / Operador</th>
                <th style="width:28%;" class="num">Total Operaciones</th>
                <th style="width:28%;" class="num">Total Comisiones (USD)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($datos as $row)
                <tr @if($loop->even) class="zebra" @endif>
                    <td class="titular">{{ $row['titular'] }}</td>
                    <td class="num">{{ $row['total_operaciones'] }}</td>
                    <td class="num usd">${{ number_format($row['total_comisiones_usd'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="sin-operaciones">Sin comisiones en el período seleccionado.</td>
                </tr>
            @endforelse

            @if($datos->count() > 0)
                <tr class="fila-total">
                    <td style="text-align:right; text-transform:uppercase; letter-spacing:0.5px;">Total</td>
                    <td class="num">{{ $datos->sum('total_operaciones') }}</td>
                    <td class="num usd">${{ number_format($datos->sum('total_comisiones_usd'), 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @foreach($datos as $row)
        <div class="detalle-titular" style="page-break-before: always;">
            <h2>{{ $row['titular'] }}</h2>
            <p>{{ $row['total_operaciones'] }} operaciones · {{ number_format($row['total_comisiones_usd'], 2) }} USD en comisiones</p>
            <table class="datos">
                <thead>
                    <tr>
                        <th style="width:10%;">Op.</th>
                        <th style="width:16%;">Fecha</th>
                        <th style="width:34%;">Descripción</th>
                        <th style="width:12%;" class="num">Monto</th>
                        <th style="width:10%;" class="num">Moneda</th>
                        <th style="width:18%;" class="num">USD Equiv.</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($row['detalle'] as $c)
                        <tr @if($loop->even) class="zebra" @endif>
                            <td class="id">#{{ $c->operacion_id }}</td>
                            <td>{{ optional($c->operacion)->fecha?->format('d/m/Y') }}</td>
                            <td>{{ $c->descripcion }}</td>
                            <td class="num">{{ number_format($c->monto, 4) }}</td>
                            <td class="num">{{ optional($c->moneda)->codigo }}</td>
                            <td class="num usd">${{ number_format($c->monto_usd_equivalente, 4) }}</td>
                        </tr>
                    @endforeach
                    <tr class="fila-total">
                        <td colspan="5" style="text-align:right; text-transform:uppercase; letter-spacing:0.5px;">Total</td>
                        <td class="num usd">${{ number_format($row['detalle']->sum('monto_usd_equivalente'), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        <hr class="linea" />
        <div class="texto">
            <img src="{{ public_path('img/intermedius-isotipo-gris.png') }}" alt="" class="isotipo" />
            Intermedius Group · Sistema de Gestión de Casa de Cambio
        </div>
    </div>
</body>
</html>
