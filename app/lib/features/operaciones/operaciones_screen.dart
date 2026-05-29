import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../../core/network/dio_client.dart';
import '../../models/operacion_model.dart';

final operacionesProvider = FutureProvider.family<List<OperacionModel>, Map<String, dynamic>>((ref, params) async {
  final dio = ref.watch(dioProvider);
  final response = await dio.get('/operaciones', queryParameters: params.isEmpty ? null : params);
  final data = response.data['data'] as List? ?? [];
  return data.map((e) => OperacionModel.fromJson(e as Map<String, dynamic>)).toList();
});

class OperacionesScreen extends ConsumerStatefulWidget {
  const OperacionesScreen({super.key});

  @override
  ConsumerState<OperacionesScreen> createState() => _OperacionesScreenState();
}

class _OperacionesScreenState extends ConsumerState<OperacionesScreen> {
  String? _filterEstatus;
  final Map<String, dynamic> _params = {};

  @override
  Widget build(BuildContext context) {
    final operacionesAsync = ref.watch(operacionesProvider(_params));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Operaciones'),
        actions: [
          IconButton(icon: const Icon(Icons.filter_list), onPressed: _showFilterSheet),
          IconButton(icon: const Icon(Icons.refresh), onPressed: () => ref.invalidate(operacionesProvider(_params))),
        ],
      ),
      body: operacionesAsync.when(
        data: (ops) => ops.isEmpty
            ? _buildEmpty(context)
            : RefreshIndicator(
                onRefresh: () async => ref.invalidate(operacionesProvider(_params)),
                child: ListView.separated(
                  padding: const EdgeInsets.all(8),
                  itemCount: ops.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 4),
                  itemBuilder: (_, i) => _OperacionTile(op: ops[i]),
                ),
              ),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.error_outline, size: 48, color: Colors.grey),
          const SizedBox(height: 8),
          Text(e.toString()),
          TextButton(onPressed: () => ref.invalidate(operacionesProvider(_params)), child: const Text('Reintentar')),
        ])),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.go('/operaciones/nueva'),
        icon: const Icon(Icons.add),
        label: const Text('Nueva'),
      ),
    );
  }

  void _showFilterSheet() {
    showModalBottomSheet(
      context: context,
      builder: (_) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('Filtrar por estatus', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            ...['sin_verificar', 'en_revision', 'verificado'].map((s) => RadioListTile<String?>(
              value: s,
              groupValue: _filterEstatus,
              title: Text(_estatusLabel(s)),
              onChanged: (v) { setState(() { _filterEstatus = v; if (v != null) _params['estatus'] = v; else _params.remove('estatus'); }); Navigator.pop(context); ref.invalidate(operacionesProvider(_params)); },
            )),
            if (_filterEstatus != null)
              TextButton(onPressed: () { setState(() { _filterEstatus = null; _params.remove('estatus'); }); Navigator.pop(context); ref.invalidate(operacionesProvider(_params)); }, child: const Text('Limpiar filtro')),
          ],
        ),
      ),
    );
  }

  Widget _buildEmpty(BuildContext context) {
    return Center(
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Icon(Icons.receipt_long_outlined, size: 64, color: Theme.of(context).colorScheme.onSurfaceVariant),
        const SizedBox(height: 16),
        Text('No hay operaciones', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 8),
        const Text('Pulsa + para registrar una nueva'),
      ]),
    );
  }

  String _estatusLabel(String s) {
    switch (s) {
      case 'verificado': return 'Verificado';
      case 'en_revision': return 'En revisión';
      default: return 'Sin verificar';
    }
  }
}

class _OperacionTile extends StatelessWidget {
  final OperacionModel op;
  const _OperacionTile({required this.op});

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final fmtUsd = NumberFormat('\$#,##0.00', 'en');
    final (color, icon) = _estatusStyle(op.estatus, scheme);

    return Card(
      child: ListTile(
        onTap: () => context.go('/operaciones/${op.id}'),
        leading: CircleAvatar(
          backgroundColor: color.withOpacity(0.15),
          child: Icon(icon, color: color, size: 20),
        ),
        title: Text(op.tipoOperacion ?? 'Operación #${op.id}', style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (op.clienteNombre != null) Text(op.clienteNombre!, style: const TextStyle(fontSize: 12)),
            Text('${op.fecha} · ${op.operadorNombre}', style: TextStyle(fontSize: 11, color: scheme.onSurfaceVariant)),
          ],
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(fmtUsd.format(op.gananciaNetaUsd), style: TextStyle(fontWeight: FontWeight.bold, color: op.gananciaNetaUsd >= 0 ? Colors.green.shade700 : scheme.error)),
            const SizedBox(height: 2),
            if (op.sinTasaReferencia) Icon(Icons.warning_amber_rounded, size: 16, color: scheme.error),
          ],
        ),
        isThreeLine: op.clienteNombre != null,
      ),
    );
  }

  (Color, IconData) _estatusStyle(String estatus, ColorScheme scheme) {
    switch (estatus) {
      case 'verificado': return (Colors.green, Icons.check_circle_outline);
      case 'en_revision': return (Colors.orange, Icons.hourglass_empty);
      default: return (scheme.onSurfaceVariant, Icons.radio_button_unchecked);
    }
  }
}
