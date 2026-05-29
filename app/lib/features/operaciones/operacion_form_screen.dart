import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/network/dio_client.dart';
import '../../models/cliente_model.dart';
import '../../models/tasa_diaria_model.dart';

final _clientesListProvider = FutureProvider<List<ClienteModel>>((ref) async {
  final dio = ref.watch(dioProvider);
  final response = await dio.get('/clientes', queryParameters: {'per_page': 200});
  final data = response.data['data'] as List? ?? [];
  return data.map((e) => ClienteModel.fromJson(e as Map<String, dynamic>)).toList();
});

final _tasasFormProvider = FutureProvider<List<TasaDiariaModel>>((ref) async {
  final dio = ref.watch(dioProvider);
  final response = await dio.get('/configuracion/tasas-vigentes');
  final data = response.data['data'] as List? ?? [];
  return data.map((e) => TasaDiariaModel.fromJson(e as Map<String, dynamic>)).toList();
});

final _tiposOpProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  final dio = ref.watch(dioProvider);
  try {
    final response = await dio.get('/tipos-operacion');
    final data = response.data['data'] as List? ?? response.data as List? ?? [];
    return data.cast<Map<String, dynamic>>();
  } catch (_) {
    return [];
  }
});

class OperacionFormScreen extends ConsumerStatefulWidget {
  const OperacionFormScreen({super.key});

  @override
  ConsumerState<OperacionFormScreen> createState() => _OperacionFormScreenState();
}

class _OperacionFormScreenState extends ConsumerState<OperacionFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _tasaCtrl = TextEditingController();
  final _referenciaCtrl = TextEditingController();
  final _descripcionCtrl = TextEditingController();

  int? _tipoOperacionId;
  int? _clienteId;
  double? _tasaSugerida;
  bool _isLoading = false;
  String? _error;

  @override
  void dispose() {
    _tasaCtrl.dispose();
    _referenciaCtrl.dispose();
    _descripcionCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final clientesAsync = ref.watch(_clientesListProvider);
    final tasasAsync = ref.watch(_tasasFormProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Nueva Operación')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _buildTipoSection(context),
            const SizedBox(height: 16),
            _buildClienteSection(context, clientesAsync),
            const SizedBox(height: 16),
            _buildTasaSection(context, tasasAsync),
            const SizedBox(height: 16),
            _buildExtrasSection(context),
            if (_error != null) ...[
              const SizedBox(height: 12),
              _buildErrorBanner(context),
            ],
            const SizedBox(height: 24),
            _buildSubmitButton(context),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _buildTipoSection(BuildContext context) {
    final tiposAsync = ref.watch(_tiposOpProvider);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Tipo de operación', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            tiposAsync.when(
              data: (tipos) => tipos.isEmpty
                  ? _buildTipoManual(context)
                  : DropdownButtonFormField<int>(
                      value: _tipoOperacionId,
                      decoration: const InputDecoration(labelText: 'Tipo', border: OutlineInputBorder()),
                      items: tipos.map((t) => DropdownMenuItem<int>(value: t['id'] as int, child: Text(t['nombre'] as String? ?? ''))).toList(),
                      onChanged: (v) => setState(() => _tipoOperacionId = v),
                      validator: (v) => v == null ? 'Seleccione el tipo' : null,
                    ),
              loading: () => _buildTipoManual(context),
              error: (_, __) => _buildTipoManual(context),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTipoManual(BuildContext context) {
    return DropdownButtonFormField<int>(
      value: _tipoOperacionId,
      decoration: const InputDecoration(labelText: 'Tipo', border: OutlineInputBorder()),
      items: const [
        DropdownMenuItem(value: 1, child: Text('Venta USD')),
        DropdownMenuItem(value: 2, child: Text('Compra USD')),
        DropdownMenuItem(value: 3, child: Text('Cambio multimoneda')),
        DropdownMenuItem(value: 4, child: Text('Gasto')),
      ],
      onChanged: (v) => setState(() => _tipoOperacionId = v),
      validator: (v) => v == null ? 'Seleccione el tipo' : null,
    );
  }

  Widget _buildClienteSection(BuildContext context, AsyncValue<List<ClienteModel>> clientesAsync) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Cliente', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            clientesAsync.when(
              data: (clientes) => DropdownButtonFormField<int>(
                value: _clienteId,
                decoration: const InputDecoration(labelText: 'Cliente (opcional)', border: OutlineInputBorder()),
                isExpanded: true,
                items: [
                  const DropdownMenuItem<int>(value: null, child: Text('Sin cliente')),
                  ...clientes.map((c) => DropdownMenuItem<int>(value: c.id, child: Text(c.displayName, overflow: TextOverflow.ellipsis))),
                ],
                onChanged: (v) => setState(() => _clienteId = v),
              ),
              loading: () => const LinearProgressIndicator(),
              error: (_, __) => TextFormField(decoration: const InputDecoration(labelText: 'ID de cliente', border: OutlineInputBorder()), keyboardType: TextInputType.number, onChanged: (v) => _clienteId = int.tryParse(v)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTasaSection(BuildContext context, AsyncValue<List<TasaDiariaModel>> tasasAsync) {
    final scheme = Theme.of(context).colorScheme;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Tasa aplicada', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            tasasAsync.when(
              data: (tasas) {
                if (tasas.isNotEmpty && _tasaSugerida == null) {
                  final primera = tasas.first;
                  _tasaSugerida = primera.tasaVenta;
                  if (_tasaCtrl.text.isEmpty) {
                    _tasaCtrl.text = primera.tasaVenta.toStringAsFixed(4);
                  }
                }
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (tasas.isNotEmpty) ...[
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(color: scheme.secondaryContainer, borderRadius: BorderRadius.circular(8)),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Tasas del día', style: TextStyle(fontWeight: FontWeight.bold, color: scheme.onSecondaryContainer)),
                            const SizedBox(height: 8),
                            ...tasas.map((t) => Padding(
                              padding: const EdgeInsets.symmetric(vertical: 2),
                              child: Row(children: [
                                Text('${t.parDisplay}:', style: TextStyle(color: scheme.onSecondaryContainer)),
                                const SizedBox(width: 8),
                                Text('C: ${t.tasaCompra.toStringAsFixed(4)}', style: TextStyle(color: scheme.onSecondaryContainer)),
                                const SizedBox(width: 12),
                                Text('V: ${t.tasaVenta.toStringAsFixed(4)}', style: TextStyle(color: scheme.onSecondaryContainer, fontWeight: FontWeight.bold)),
                              ]),
                            )),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                    ] else
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(color: scheme.errorContainer, borderRadius: BorderRadius.circular(8)),
                        child: Row(children: [
                          Icon(Icons.warning_amber_rounded, color: scheme.onErrorContainer),
                          const SizedBox(width: 8),
                          Expanded(child: Text('Sin tasa publicada hoy. Se usará la última conocida.', style: TextStyle(color: scheme.onErrorContainer))),
                        ]),
                      ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _tasaCtrl,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9.]'))],
                      decoration: InputDecoration(
                        labelText: 'Tasa a aplicar',
                        border: const OutlineInputBorder(),
                        helperText: _tasaSugerida != null ? 'Sugerida: ${_tasaSugerida!.toStringAsFixed(4)}' : null,
                      ),
                      validator: (v) {
                        if (v == null || v.isEmpty) return 'Ingrese la tasa';
                        if (double.tryParse(v) == null) return 'Tasa inválida';
                        return null;
                      },
                    ),
                  ],
                );
              },
              loading: () => const LinearProgressIndicator(),
              error: (_, __) => TextFormField(
                controller: _tasaCtrl,
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                decoration: const InputDecoration(labelText: 'Tasa aplicada', border: OutlineInputBorder()),
                validator: (v) => (v == null || v.isEmpty) ? 'Ingrese la tasa' : null,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildExtrasSection(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Detalles adicionales', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            TextFormField(
              controller: _referenciaCtrl,
              decoration: const InputDecoration(labelText: 'Referencia (opcional)', border: OutlineInputBorder()),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _descripcionCtrl,
              maxLines: 3,
              decoration: const InputDecoration(labelText: 'Descripción (opcional)', border: OutlineInputBorder()),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildErrorBanner(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: scheme.errorContainer, borderRadius: BorderRadius.circular(8)),
      child: Row(children: [
        Icon(Icons.error_outline, color: scheme.onErrorContainer),
        const SizedBox(width: 8),
        Expanded(child: Text(_error!, style: TextStyle(color: scheme.onErrorContainer))),
      ]),
    );
  }

  Widget _buildSubmitButton(BuildContext context) {
    return FilledButton.icon(
      onPressed: _isLoading ? null : _submit,
      style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 16)),
      icon: _isLoading ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.save_outlined),
      label: Text(_isLoading ? 'Registrando...' : 'Registrar operación', style: const TextStyle(fontSize: 16)),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() { _isLoading = true; _error = null; });
    try {
      final dio = ref.read(dioProvider);
      await dio.post('/operaciones', data: {
        'tipo_operacion_id': _tipoOperacionId,
        if (_clienteId != null) 'cliente_id': _clienteId,
        'tasa_aplicada': double.parse(_tasaCtrl.text),
        if (_referenciaCtrl.text.isNotEmpty) 'referencia': _referenciaCtrl.text,
        if (_descripcionCtrl.text.isNotEmpty) 'descripcion': _descripcionCtrl.text,
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Operación registrada correctamente')));
        context.go('/operaciones');
      }
    } catch (e) {
      setState(() { _error = e.toString(); _isLoading = false; });
    }
  }
}
