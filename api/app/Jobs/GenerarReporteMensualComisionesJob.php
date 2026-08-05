<?php

namespace App\Jobs;

use App\Models\Ajuste;
use App\Services\Reportes\ReporteComisionesOperadoresService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job programado que genera el reporte mensual de comisiones de operadores
 * (Excel + PDF) y opcionalmente envía notificación por email a los destinatarios configurados.
 */
class GenerarReporteMensualComisionesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Execute the job.
     *
     * @param  ReporteComisionesOperadoresService  $service
     */
    public function handle(ReporteComisionesOperadoresService $service): void
    {
        $desde = now()->subMonth()->startOfMonth();
        $hasta = now()->subMonth()->endOfMonth();

        Log::info("GenerarReporteMensualComisionesJob: generando reporte {$desde->format('Y-m')}");

        try {
            $pathExcel = $service->exportarExcel($desde, $hasta);
            $pathPdf   = $service->exportarPdf($desde, $hasta);

            Log::info("Reporte mensual generado: Excel={$pathExcel} | PDF={$pathPdf}");

            if (config('reportes.comisiones_operadores.enviar_email', false) && Ajuste::activo('envio_emails', true)) {
                $destinatariosRaw = config('reportes.comisiones_operadores.destinatarios', '');

                if (empty(trim($destinatariosRaw))) {
                    Log::warning('GenerarReporteMensualComisionesJob: enviar_email=true pero no hay destinatarios configurados. Email omitido.');
                    return;
                }

                $destinatarios = array_filter(
                    array_map('trim', explode(',', $destinatariosRaw))
                );

                try {
                    Mail::raw(
                        "Reporte mensual de comisiones de operadores para el período {$desde->format('M Y')} generado.\n\nAdjunte los archivos manualmente desde storage o configure el Mailable.",
                        fn ($msg) => $msg->to($destinatarios)
                                        ->subject("Reporte comisiones operadores — {$desde->format('M Y')}")
                    );
                } catch (\Throwable $e) {
                    Log::error("GenerarReporteMensualComisionesJob: error al enviar email: {$e->getMessage()}");
                }
            }
        } catch (\Throwable $e) {
            Log::error("GenerarReporteMensualComisionesJob: error generando reporte: {$e->getMessage()}", [
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
