<?php

/**
 * Console (schedule) routes.
 *
 * Defines the task schedule:
 * - SincronizarTasasJob / SincronizarTasasReferenciaJob: every minute.
 * - AlertarTasasFaltantesJob: daily at 08:00 and 14:00.
 * - GenerarReporteMensualComisionesJob: monthly on 1st at 06:00.
 * - AutoArchivarClientesInactivos: weekly on Sunday at 03:00.
 */

use App\Jobs\AlertarTasasFaltantesJob;
use App\Jobs\GenerarReporteMensualComisionesJob;
use App\Jobs\SincronizarTasasJob;
use App\Jobs\AutoArchivarClientesInactivos;
use App\Jobs\SincronizarTasasReferenciaJob;
use App\Jobs\VerificarSlaPoolJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::job(new SincronizarTasasJob())
    ->everyMinute()
    ->withoutOverlapping()
    ->name('sincronizar-tasas');

Schedule::job(new SincronizarTasasReferenciaJob())
    ->everyMinute()
    ->withoutOverlapping()
    ->name('sincronizar-tasas-referencia');

Schedule::job(new AlertarTasasFaltantesJob())
    ->dailyAt('08:00')
    ->name('alertar-tasas-faltantes-manana');

Schedule::job(new AlertarTasasFaltantesJob())
    ->dailyAt('14:00')
    ->name('alertar-tasas-faltantes-tarde');

Schedule::job(new GenerarReporteMensualComisionesJob())
    ->monthlyOn(1, '06:00')
    ->withoutOverlapping()
    ->name('reporte-mensual-comisiones');

Schedule::job(new AutoArchivarClientesInactivos())
    ->weeklyOn(0, '03:00')
    ->withoutOverlapping()
    ->name('auto-archivar-clientes-inactivos');

Schedule::job(new VerificarSlaPoolJob())
    ->everyMinute()
    ->withoutOverlapping()
    ->name('verificar-sla-pool');
