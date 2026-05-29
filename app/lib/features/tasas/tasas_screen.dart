import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/auth/auth_provider.dart';
import '../../core/network/dio_client.dart';
import '../../models/tasa_diaria_model.dart';
import '../../models/moneda_model.dart';

final tasasDiariasProvider = FutureProvider<List<TasaDiariaModel>>((ref) async {
  final dio = ref.watch(dioProvider);
  final response = await dio.get('/configuracion/tasas-diarias');
  final data = response.data['data'] as List? ?? [];
  return data.map((e) => TasaDiariaModel.fromJson(e as Map<String, dynamic>)).toList();
});

final monedasProvider = FutureProvider<List<MonedaModel>>((ref) async {
  final dio = ref.watch(dioProvider);
  final response = await dio.get('/monedas');
  final data = response.data['data'] as List? ?? [];
  return data.map((e) => MonedaModel.fromJson(e as Map<String, dynamic>)).toList();
});

class TasasScreen extends ConsumerWidget {
  const TasasScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final tasasAsync = ref.watch(tasasDiariasProvider);
    final user = ref.watch(authNotifierProvider).user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Tasas del día'),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: () => ref.invalidate(tasasDiariasProvider)),
        ],
      ),
      body: tasasAsync.when(
        data: (tasas) => tasas.isEmpty
            ? _buildEmpty(context)
            : RefreshIndicator(
                onRefresh: () async => ref.invalidate(tasasDiariasProvider),
                child: ListView.separated(
                  padding: const EdgeInsets.all(12),
                  itemCount: tasas.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (_, i) => _TasaTile(tasa: tasas[i]),
                ),
              ),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.cloud_off, size: 48, color: Colors.grey),
          const SizedBox(height: 8),
          Text(e.toString()),
          TextButton(onPressed: () => ref.invalidate(tasasDiariasProvider), child: const Text('Reintentar')),
        ])),
      ),
      floatingActionButton: user?.isAdmin == true
          ? FloatingActionButton.extended(
              onPressed: () => _showPublicarTasaSheet(context, ref),
              icon: const Icon(Icons.add_chart),
              label: const Text('Publicar tasa'),
            )
          : null,
    );
  }

  Widget _buildEmpty(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Center(
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(color: scheme.errorContainer, shape: BoxShape.circle),
          child: Icon(Icons.trending_flat, size: 48, color: scheme.onErrorContainer),
        ),
        const SizedBox(height: 16),
        Text('No hay tasas publicadas hoy', style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: 8),
        Text('Usa el botón + para publicar la tasa del día', style: TextStyle(color: scheme.onSurfaceVariant)),
      ]),
    );
  }

  void _showPublicarTasaSheet(BuildContext context, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (_) => ProviderScope(parent: ProviderScope.containerOf(context), child: _PublicarTasaSheet(onSaved: () => ref.invalidate(tasasDiariasProvider))),
    );
  }
}

class _TasaTile extends StatelessWidget {
  final TasaDiariaModel tasa;
  const _TasaTile({required this.tasa});

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final fmt = NumberFormat('#,##0.00##', 'es');

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Row(children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(color: tasa.isVigente ? scheme.primaryContainer : scheme.surfaceContainerHighest, borderRadius: BorderRadius.circular(20)),
                child: Text(tasa.parDisplay, style: TextStyle(fontWeight: FontWeight.bold, color: tasa.isVigente ? scheme.primary : scheme.onSurfaceVariant)),
              ),
              const Spacer(),
              if (tasa.isVigente)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(color: Colors.green.withOpacity(0.1), borderRadius: BorderRadius.circular(12)),
                  child: Row(mainAxisSize: MainAxisSize.min, children: [
                    const Icon(Icons.circle, size: 8, color: Colors.green),
                    const SizedBox(width: 4),
                    const Text('Vigente', style: TextStyle(color: Colors.green, fontSize: 12)),
                  ]),
                ),
            ]),
            const SizedBox(height: 16),
            Row(children: [
              _rateCol(context, 'Compra', fmt.format(tasa.tasaCompra), scheme.tertiary),
              const SizedBox(width: 16),
              _rateCol(context, 'Venta', fmt.format(tasa.tasaVenta), scheme.primary),
            ]),
            if (tasa.notas != null && tasa.notas!.isNotEmpty) ...[
              const Divider(height: 20),
              Row(children: [
                Icon(Icons.notes, size: 14, color: scheme.onSurfaceVariant),
                const SizedBox(width: 6),
                Expanded(child: Text(tasa.notas!, style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 12))),
              ]),
            ],
            const SizedBox(height: 8),
            Row(children: [
              Icon(Icons.access_time, size: 12, color: scheme.onSurfaceVariant),
              const SizedBox(width: 4),
              Text('Desde: ${tasa.vigenteDesde.substring(0, 16)}', style: TextStyle(fontSize: 11, color: scheme.onSurfaceVariant)),
              if (tasa.vigenteHasta != null) ...[
                const SizedBox(width: 8),
                Text('· Hasta: ${tasa.vigenteHasta!.substring(0, 16)}', style: TextStyle(fontSize: 11, color: scheme.onSurfaceVariant)),
              ],
            ]),
          ],
        ),
      ),
    );
  }

  Widget _rateCol(BuildContext context, String label, String value, Color color) {
    return Expanded(
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(label, style: const TextStyle(color: Colors.grey, fontSize: 12)),
        const SizedBox(height: 4),
        Text(value, style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: color)),
      ]),
    );
  }
}

class _PublicarTasaSheet extends ConsumerStatefulWidget {
  final VoidCallback onSaved;
  const _PublicarTasaSheet({required this.onSaved});

  @override
  ConsumerState<_PublicarTasaSheet> createState() => _PublicarTasaSheetState();
}

class _PublicarTasaSheetState extends ConsumerState<_PublicarTasaSheet> {
  final _formKey = GlobalKey<FormState>();
  final _compraCtrl = TextEditingController();
  final _ventaCtrl = TextEditingController();
  final _notasCtrl = TextEditingController();
  int? _monedaBaseId;
  int? _monedaCotizadaId;
  bool _isLoading = false;
  String? _error;

  @override
  void dispose() {
    _compraCtrl.dispose();
    _ventaCtrl.dispose();
    _notasCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final monedasAsync = ref.watch(monedasProvider);
    final scheme = Theme.of(context).colorScheme;

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(children: [
                Text('Publicar tasa del día', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
                const Spacer(),
                IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
              ]),
              const SizedBox(height: 20),
              monedasAsync.when(
                data: (monedas) => Row(children: [
                  Expanded(child: DropdownButtonFormField<int>(
                    value: _monedaBaseId,
                    decoration: const InputDecoration(labelText: 'Moneda base', border: OutlineInputBorder()),
                    items: monedas.map((m) => DropdownMenuItem(value: m.id, child: Text(m.codigo))).toList(),
                    onChanged: (v) => setState(() => _monedaBaseId = v),
                    validator: (v) => v == null ? 'Requerido' : null,
                  )),
                  const SizedBox(width: 12),
                  Expanded(child: DropdownButtonFormField<int>(
                    value: _monedaCotizadaId,
                    decoration: const InputDecoration(labelText: 'Cotizada', border: OutlineInputBorder()),
                    items: monedas.map((m) => DropdownMenuItem(value: m.id, child: Text(m.codigo))).toList(),
                    onChanged: (v) => setState(() => _monedaCotizadaId = v),
                    validator: (v) => v == null ? 'Requerido' : null,
                  )),
                ]),
                loading: () => const LinearProgressIndicator(),
                error: (_, __) => const Text('Error cargando monedas'),
              ),
              const SizedBox(height: 12),
              Row(children: [
                Expanded(child: TextFormField(
                  controller: _compraCtrl,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))],
                  decoration: const InputDecoration(labelText: 'Tasa compra', border: OutlineInputBorder()),
                  validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                )),
                const SizedBox(width: 12),
                Expanded(child: TextFormField(
                  controller: _ventaCtrl,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))],
                  decoration: const InputDecoration(labelText: 'Tasa venta', border: OutlineInputBorder()),
                  validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null,
                )),
              ]),
              const SizedBox(height: 12),
              TextFormField(
                controller: _notasCtrl,
                decoration: const InputDecoration(labelText: 'Notas (opcional)', border: OutlineInputBorder()),
              ),
              if (_error != null) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(color: scheme.errorContainer, borderRadius: BorderRadius.circular(8)),
                  child: Text(_error!, style: TextStyle(color: scheme.onErrorContainer)),
                ),
              ],
              const SizedBox(height: 20),
              FilledButton(
                onPressed: _isLoading ? null : _submit,
                style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14)),
                child: _isLoading ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)) : const Text('Publicar tasa'),
              ),
              const SizedBox(height: 8),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() { _isLoading = true; _error = null; });
    try {
      final dio = ref.read(dioProvider);
      await dio.post('/configuracion/tasas-diarias', data: {
        'moneda_base_id': _monedaBaseId,
        'moneda_cotizada_id': _monedaCotizadaId,
        'tasa_compra': double.parse(_compraCtrl.text),
        'tasa_venta': double.parse(_ventaCtrl.text),
        if (_notasCtrl.text.isNotEmpty) 'notas': _notasCtrl.text,
      });
      if (mounted) {
        Navigator.pop(context);
        widget.onSaved();
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Tasa publicada correctamente')));
      }
    } catch (e) {
      setState(() { _error = e.toString(); _isLoading = false; });
    }
  }
}
