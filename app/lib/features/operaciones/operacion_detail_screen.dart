import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/auth/auth_provider.dart';
import '../../core/network/dio_client.dart';
import '../../models/operacion_model.dart';

final operacionDetailProvider = FutureProvider.family<OperacionModel, int>((ref, id) async {
  final dio = ref.watch(dioProvider);
  final response = await dio.get('/operaciones/$id');
  return OperacionModel.fromJson(response.data['data'] ?? response.data);
});

class OperacionDetailScreen extends ConsumerWidget {
  final int id;
  const OperacionDetailScreen({super.key, required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final opAsync = ref.watch(operacionDetailProvider(id));
    final user = ref.watch(authNotifierProvider).user;

    return Scaffold(
      appBar: AppBar(
        title: Text('Operación #$id'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: () => ref.invalidate(operacionDetailProvider(id))),
        ],
      ),
      body: opAsync.when(
        data: (op) => _buildDetail(context, ref, op, user),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text(e.toString())),
      ),
    );
  }

  Widget _buildDetail(BuildContext context, WidgetRef ref, OperacionModel op, dynamic user) {
    final scheme = Theme.of(context).colorScheme;
    final fmtUsd = NumberFormat('\$#,##0.0000', 'en');
    final fmtVes = NumberFormat('Bs. #,##0.00', 'es');

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        _statusBanner(context, op, scheme),
        const SizedBox(height: 16),
        _section(context, 'Información general', [
          _row('Tipo', op.tipoOperacion ?? '-'),
          _row('Fecha', op.fecha),
          _row('Operador', op.operadorNombre),
          if (op.clienteNombre != null) _row('Cliente', op.clienteNombre!),
          if (op.referencia != null) _row('Referencia', op.referencia!),
          if (op.descripcion != null) _row('Descripción', op.descripcion!),
        ]),
        const SizedBox(height: 16),
        _section(context, 'Tasas', [
          _row('Tasa aplicada', op.tasaAplicada?.toStringAsFixed(4) ?? '-'),
          _row('Tasa sugerida', op.tasaSugerida?.toStringAsFixed(4) ?? '-'),
          if (op.sinTasaReferencia)
            _alertRow(context, 'Sin tasa de referencia del día', scheme),
        ]),
        const SizedBox(height: 16),
        _section(context, 'Resultados financieros', [
          _row('Ganancia bruta USD', fmtUsd.format(op.gananciaBrutaUsd)),
          _row('Ganancia bruta VES', fmtVes.format(op.gananciaBrutaVes)),
          _row('Total comisiones USD', fmtUsd.format(op.totalComisionesUsd)),
          _row('Total comisiones VES', fmtVes.format(op.totalComisionesVes)),
          _dividerRow(),
          _row('Ganancia neta USD', fmtUsd.format(op.gananciaNetaUsd), bold: true, color: op.gananciaNetaUsd >= 0 ? Colors.green.shade700 : scheme.error),
          _row('Ganancia neta VES', fmtVes.format(op.gananciaNetaVes), bold: true),
        ]),
        const SizedBox(height: 16),
        if (user?.isAdmin == true && !op.isVerificado)
          FilledButton.icon(
            onPressed: () => _verificar(context, ref, op),
            icon: const Icon(Icons.check_circle_outline),
            label: const Text('Verificar operación'),
          ),
        if (op.verificadoAt != null) ...[
          const SizedBox(height: 8),
          Text('Verificado el ${op.verificadoAt}', style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 12), textAlign: TextAlign.center),
        ],
      ],
    );
  }

  Future<void> _verificar(BuildContext context, WidgetRef ref, OperacionModel op) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Verificar operación'),
        content: Text('¿Confirmas la verificación de la operación #${op.id}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancelar')),
          FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Verificar')),
        ],
      ),
    );
    if (confirm != true) return;
    try {
      final dio = ref.read(dioProvider);
      await dio.patch('/operaciones/${op.id}/verificar');
      ref.invalidate(operacionDetailProvider(op.id));
      if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Operación verificada')));
    } catch (e) {
      if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }

  Widget _statusBanner(BuildContext context, OperacionModel op, ColorScheme scheme) {
    final (color, icon, label) = switch (op.estatus) {
      'verificado' => (Colors.green, Icons.check_circle, 'Verificado'),
      'en_revision' => (Colors.orange, Icons.hourglass_empty, 'En revisión'),
      _ => (scheme.onSurfaceVariant, Icons.radio_button_unchecked, 'Sin verificar'),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(color: color.withOpacity(0.1), borderRadius: BorderRadius.circular(12), border: Border.all(color: color.withOpacity(0.3))),
      child: Row(children: [Icon(icon, color: color), const SizedBox(width: 8), Text(label, style: TextStyle(color: color, fontWeight: FontWeight.bold))]),
    );
  }

  Widget _section(BuildContext context, String title, List<Widget> children) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold)),
            const Divider(height: 20),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _row(String label, String value, {bool bold = false, Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(children: [
        Expanded(flex: 2, child: Text(label, style: const TextStyle(color: Colors.grey))),
        Expanded(flex: 3, child: Text(value, style: TextStyle(fontWeight: bold ? FontWeight.bold : FontWeight.normal, color: color))),
      ]),
    );
  }

  Widget _alertRow(BuildContext context, String msg, ColorScheme scheme) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(children: [Icon(Icons.warning_amber_rounded, color: scheme.error, size: 16), const SizedBox(width: 6), Expanded(child: Text(msg, style: TextStyle(color: scheme.error, fontSize: 13)))]),
    );
  }

  Widget _dividerRow() => const Divider(height: 16);
}
