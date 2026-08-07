<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resumen del Período — Intermedius</title>
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

        /* ── Tarjetas KPI ───────────────────────────── */
        table.kpis { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 10px -6px 0; }
        table.kpis td {
            width: 25%;
            border: 1px solid #E3E6E8;
            border-top: 3px solid #005745;
            border-radius: 4px;
            padding: 8px 10px;
            vertical-align: top;
        }
        .kpi-label { font-size: 8px; color: #727276; text-transform: uppercase; letter-spacing: 0.4px; margin: 0 0 3px; }
        .kpi-value { font-size: 14px; font-weight: 700; color: #1D1D1B; margin: 0; }
        .kpi-line { font-size: 9px; margin: 0; }
        .kpi-line span { float: right; font-weight: 600; }
        .kpi-line.usd span { color: #005745; }

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
        table.datos td.neg { color: #3B444D; font-weight: 600; }

        .fila-total td {
            background-color: #3B444D !important;
            color: #ffffff;
            font-weight: 700;
            font-size: 10px;
            border-top: 2px solid #D9C79E;
        }
        .fila-total td.usd { color: #8FD0B8 !important; }
        .fila-total td.neg { color: #B9C2C9 !important; }

        .sin-operaciones {
            text-align: center;
            color: #727276;
            font-size: 10px;
            padding: 20px 0;
        }

        .seccion-titulo { margin: 14px 0 0; }
        .seccion-titulo h3 {
            font-size: 11px;
            font-weight: 700;
            color: #1D1D1B;
            margin: 0 0 2px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .seccion-titulo .accento { margin: 0 0 4px; }

        table.columnas { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; }
        table.columnas > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }

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
        $ops    = $resumen['operaciones'];
        $gan    = $resumen['ganancias'];
        $ef     = $resumen['efectivo_pendiente'];
        $vol    = collect($resumen['volumenes'] ?? []);
        $act    = collect($resumen['por_operador'] ?? []);
        $moneda = isset($moneda) && $moneda ? strtoupper($moneda) : 'Todas';
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
        <h1>Resumen del Período</h1>
        <div class="accento"></div>
        <p class="periodo">
            Período: {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}
        </p>
    </div>

    <div class="banda">
        <img src="{{ public_path('img/intermedius-isotipo-blanco.png') }}" alt="" class="isotipo" />
        <span class="filtros">
            Período: {{ $desde->format('d/m/Y') }} — {{ $hasta->format('d/m/Y') }}
            &nbsp;|&nbsp; Moneda: {{ $moneda }}
        </span>
    </div>

    <table class="kpis">
        <tr>
            <td>
                <p class="kpi-label">Total operaciones</p>
                <p class="kpi-value">{{ $ops['total'] ?? 0 }}</p>
            </td>
            <td>
                <p class="kpi-label">Desglose</p>
                <p class="kpi-line">Compras <span>{{ $ops['compras'] ?? 0 }}</span></p>
                <p class="kpi-line">Ventas <span>{{ $ops['ventas'] ?? 0 }}</span></p>
                <p class="kpi-line">Intermediadas <span>{{ $ops['intermediadas'] ?? 0 }}</span></p>
            </td>
            <td>
                <p class="kpi-label">Ganancia</p>
                <p class="kpi-line usd">Bruta <span>${{ number_format($gan['bruta_usd'] ?? 0, 2) }}</span></p>
                <p class="kpi-line usd">Neta <span>${{ number_format($gan['neta_usd'] ?? 0, 2) }}</span></p>
            </td>
            <td>
                <p class="kpi-label">Efectivo pendiente</p>
                <p class="kpi-value">{{ $ef['count'] ?? 0 }}</p>
                <p class="kpi-line usd">${{ number_format($ef['monto_usd'] ?? 0, 2) }}</p>
            </td>
        </tr>
    </table>

    <table class="columnas">
        <tr>
            <td>
                <div class="seccion-titulo">
                    <h3>Volúmenes por moneda</h3>
                    <div class="accento"></div>
                </div>
                <table class="datos">
                    <thead>
                        <tr>
                            <th style="width:30%;">Moneda</th>
                            <th style="width:35%;" class="num">Comprado</th>
                            <th style="width:35%;" class="num">Vendido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vol as $v)
                            <tr @if($loop->even) class="zebra" @endif>
                                <td class="neg">{{ $v['moneda'] }}</td>
                                <td class="num neg">{{ number_format($v['comprado'], 2) }}</td>
                                <td class="num neg">{{ number_format($v['vendido'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="sin-operaciones">Sin volúmenes</td>
                            </tr>
                        @endforelse
                        @if($vol->count() > 0)
                            <tr class="fila-total">
                                <td style="text-align:right; text-transform:uppercase;">Total</td>
                                <td class="num neg">{{ number_format($vol->sum('comprado'), 2) }}</td>
                                <td class="num neg">{{ number_format($vol->sum('vendido'), 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </td>
            <td>
                <div class="seccion-titulo">
                    <h3>Actividad por operador</h3>
                    <div class="accento"></div>
                </div>
                <table class="datos">
                    <thead>
                        <tr>
                            <th style="width:40%;">Operador</th>
                            <th style="width:30%;" class="num">Operaciones</th>
                            <th style="width:30%;" class="num">Volumen USD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($act as $o)
                            <tr @if($loop->even) class="zebra" @endif>
                                <td class="neg">{{ $o['operador'] }}</td>
                                <td class="num">{{ $o['total_operaciones'] ?? 0 }}</td>
                                <td class="num usd">${{ number_format($o['volumen_usd'] ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="sin-operaciones">Sin actividad</td>
                            </tr>
                        @endforelse
                        @if($act->count() > 0)
                            <tr class="fila-total">
                                <td style="text-align:right; text-transform:uppercase;">Total</td>
                                <td class="num">{{ $act->sum('total_operaciones') }}</td>
                                <td class="num usd">${{ number_format($act->sum('volumen_usd'), 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </td>
        </tr>
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
