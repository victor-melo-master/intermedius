<?php

use App\Jobs\AlertarTasasFaltantesJob;
use App\Jobs\GenerarReporteMensualComisionesJob;
use App\Jobs\SincronizarTasasJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::job(new SincronizarTasasJob())
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->name('sincronizar-tasas');

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
