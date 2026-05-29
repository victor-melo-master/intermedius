import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/network/dio_client.dart';

class ReportesScreen extends ConsumerStatefulWidget {
  const ReportesScreen({super.key});

  @override
  ConsumerState<ReportesScreen> createState() => _ReportesScreenState();
}

class _ReportesScreenState extends ConsumerState<ReportesScreen> {
  DateTime _desde = DateTime.now().copyWith(day: 1);
  DateTime _hasta = DateTime.now();
  bool _isExporting = false;
  List<Map<String, dynamic>> _data = [];
  bool _loaded = false;
  String? _error;

  final _fmt = DateFormat('yyyy-MM-dd');
  final _fmtDisplay = DateFormat('d MMM yyyy', 'es');
  final _fmtUsd = NumberFormat('\$#,##0.00', 'en');

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(title: const Text('Reportes')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Comisiones por operador', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 16),
                  Row(children: [
                    Expanded(child: _dateField(context, 'Desde', _desde, (d) => setState(() => _desde = d))),
                    const SizedBox(width: 12),
                    Expanded(child: _dateField(context, 'Hasta', _hasta, (d) => setState(() => _hasta = d))),
                  ]),
                  const SizedBox(height: 16),
                  Row(children: [
                    Expanded(child: FilledButton.icon(
                      onPressed: _buscar,
                      icon: const Icon(Icons.search),
                      label: const Text('Consultar'),
                    )),
                    const SizedBox(width: 12),
                    Expanded(child: OutlinedButton.icon(
                      onPressed: _isExporting ? null : () => _exportar('excel'),
                      icon: _isExporting ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.download_outlined),
                      label: const Text('Exportar Excel'),
                    )),
                  ]),
                ],
              ),
            ),
          ),
          if (_error != null) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: scheme.errorContainer, borderRadius: BorderRadius.circular(8)),
              child: Text(_error!, style: TextStyle(color: scheme.onErrorContainer)),
            ),
          ],
          if (_loaded && _data.isEmpty) ...[
            const SizedBox(height: 24),
            Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
              Icon(Icons.bar_chart, size: 64, color: scheme.onSurfaceVariant),
              const SizedBox(height: 12),
              const Text('Sin datos para el período seleccionado'),
            ])),
          ],
          if (_data.isNotEmpty) ...[
            const SizedBox(height: 16),
            ..._data.map((item) => _buildOperadorCard(context, item, scheme)),
          ],
        ],
      ),
    );
  }

  Widget _dateField(BuildContext context, String label, DateTime value, void Function(DateTime) onChanged) {
    return InkWell(
      onTap: () async {
        final picked = await showDatePicker(context: context, initialDate: value, firstDate: DateTime(2024), lastDate: DateTime.now());
        if (picked != null) onChanged(picked);
      },
      child: InputDecorator(
        decoration: InputDecoration(labelText: label, border: const OutlineInputBorder(), suffixIcon: const Icon(Icons.calendar_today, size: 18)),
        child: Text(_fmtDisplay.format(value)),
      ),
    );
  }

  Widget _buildOperadorCard(BuildContext context, Map<String, dynamic> item, ColorScheme scheme) {
    final nombre = item['operador'] as String? ?? item['nombre'] as String? ?? 'Operador';
    final totalComisiones = double.tryParse(item['total_comisiones']?.toString() ?? '0') ?? 0;
    final cantidadOps = item['cantidad_operaciones'] ?? item['total_operaciones'] ?? 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(children: [
          CircleAvatar(
            backgroundColor: scheme.primaryContainer,
            child: Text(nombre.substring(0, 1).toUpperCase(), style: TextStyle(color: scheme.primary, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(width: 12),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(nombre, style: const TextStyle(fontWeight: FontWeight.w600)),
            Text('$cantidadOps operaciones', style: TextStyle(fontSize: 12, color: scheme.onSurfaceVariant)),
          ])),
          Text(_fmtUsd.format(totalComisiones), style: TextStyle(fontWeight: FontWeight.bold, color: scheme.primary, fontSize: 16)),
        ]),
      ),
    );
  }

  Future<void> _buscar() async {
    setState(() { _error = null; _loaded = false; });
    try {
      final dio = ref.read(dioProvider);
      final response = await dio.get('/reportes/comisiones-operadores', queryParameters: {
        'desde': _fmt.format(_desde),
        'hasta': _fmt.format(_hasta),
      });
      final data = response.data['data'] as List? ?? [];
      setState(() { _data = data.cast<Map<String, dynamic>>(); _loaded = true; });
    } catch (e) {
      setState(() { _error = e.toString(); _loaded = true; });
    }
  }

  Future<void> _exportar(String formato) async {
    setState(() { _isExporting = true; _error = null; });
    try {
      final dio = ref.read(dioProvider);
      await dio.post('/reportes/comisiones-operadores/exportar', data: {
        'desde': _fmt.format(_desde),
        'hasta': _fmt.format(_hasta),
        'formato': formato,
      });
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Reporte generado. Revisa tu email o el servidor.')));
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      setState(() => _isExporting = false);
    }
  }
}
