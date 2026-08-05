<?php

namespace App\Jobs;

use App\Models\Ajuste;
use App\Models\Moneda;
use App\Models\TasaDiaria;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Job programado que verifica si hay tasas diarias vigentes para los pares principales
 * y, si faltan, envía una alerta por email a los administradores.
 */
class AlertarTasasFaltantesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $paresPrincipales = config('sistema.pares_principales', ['USD/VES', 'USDT/VES']);
        $paresFaltantes   = [];

        foreach ($paresPrincipales as $par) {
            [$codigoBase, $codigoCotizada] = explode('/', $par);

            $monedaBase     = Moneda::where('codigo', $codigoBase)->first();
            $monedaCotizada = Moneda::where('codigo', $codigoCotizada)->first();

            if (!$monedaBase || !$monedaCotizada) {
                continue;
            }

            $tasaVigente = TasaDiaria::where('moneda_base_id', $monedaBase->id)
                ->where('moneda_cotizada_id', $monedaCotizada->id)
                ->whereNull('vigente_hasta')
                ->whereDate('fecha', today())
                ->exists();

            if (!$tasaVigente) {
                $paresFaltantes[] = $par;
            }
        }

        if (empty($paresFaltantes)) {
            return;
        }

        // Si el envío de correos está desactivado, solo se registra la falta.
        if (! Ajuste::activo('envio_emails', true)) {
            Log::info('AlertarTasasFaltantesJob: envío de emails desactivado. Pares faltantes: ' . implode(', ', $paresFaltantes));
            return;
        }

        $listaTexto = implode(', ', $paresFaltantes);
        Log::warning("AlertarTasasFaltantesJob: pares sin tasa del día: {$listaTexto}");

        $admins = User::role(['admin', 'super_admin'])
            ->where('activo', true)
            ->whereNotNull('email')
            ->get();

        foreach ($admins as $admin) {
            try {
                Mail::raw(
                    "⚠️ Faltan tasas del día para los siguientes pares: {$listaTexto}.\n\n" .
                    "Por favor publique las tasas en el panel de configuración antes de iniciar operaciones.",
                    fn ($msg) => $msg->to($admin->email)
                                    ->subject("⚠️ Falta publicar tasa del día para: {$listaTexto}")
                );
            } catch (\Throwable $e) {
                Log::error("AlertarTasasFaltantesJob: no se pudo enviar email a {$admin->email}: {$e->getMessage()}");
            }
        }
    }
}
