import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/auth/auth_provider.dart';
import '../../core/network/dio_client.dart';
import '../../models/tasa_diaria_model.dart';

final tasasVigentesProvider = FutureProvider<List<TasaDiariaModel>>((ref) async {
  final dio = ref.watch(dioProvider);
  final response = await dio.get('/configuracion/tasas-vigentes');
  final data = response.data['data'] as List? ?? [];
  return data.map((e) => TasaDiariaModel.fromJson(e as Map<String, dynamic>)).toList();
});

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authNotifierProvider).user;
    final tasasAsync = ref.watch(tasasVigentesProvider);
    final scheme = Theme.of(context).colorScheme;
    final now = DateFormat('EEEE d \'de\' MMMM yyyy', 'es').format(DateTime.now());

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () => ref.invalidate(tasasVigentesProvider),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(tasasVigentesProvider),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _buildGreeting(context, user?.name ?? '', now, scheme),
            const SizedBox(height: 24),
            Text('Tasas vigentes hoy', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            tasasAsync.when(
              data: (tasas) => tasas.isEmpty
                  ? _buildNoRatesCard(context, scheme)
                  : Column(children: tasas.map((t) => _buildRateCard(context, t, scheme)).toList()),
              loading: () => const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator())),
              error: (e, _) => _buildErrorCard(context, scheme, () => ref.invalidate(tasasVigentesProvider)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildGreeting(BuildContext context, String name, String date, ColorScheme scheme) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(colors: [scheme.primary, scheme.primaryContainer], begin: Alignment.topLeft, end: Alignment.bottomRight),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Icon(Icons.currency_exchange, color: scheme.onPrimary, size: 28),
            const SizedBox(width: 12),
            Text('Intermedius', style: TextStyle(color: scheme.onPrimary, fontWeight: FontWeight.bold, fontSize: 18)),
          ]),
          const SizedBox(height: 12),
          Text('Bienvenido, $name', style: TextStyle(color: scheme.onPrimary, fontSize: 20, fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          Text(date, style: TextStyle(color: scheme.onPrimary.withOpacity(0.85), fontSize: 13)),
        ],
      ),
    );
  }

  Widget _buildRateCard(BuildContext context, TasaDiariaModel tasa, ColorScheme scheme) {
    final fmt = NumberFormat('#,##0.00##', 'es');
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(color: scheme.primaryContainer, borderRadius: BorderRadius.circular(20)),
                child: Text(tasa.parDisplay, style: TextStyle(fontWeight: FontWeight.bold, color: scheme.primary)),
              ),
              const Spacer(),
              if (tasa.sinTasaReferencia ?? false)
                Chip(label: const Text('Sin tasa ref.'), backgroundColor: scheme.errorContainer),
            ]),
            const SizedBox(height: 16),
            Row(children: [
              Expanded(child: _rateValue(context, 'Compra', fmt.format(tasa.tasaCompra), scheme.tertiary)),
              const SizedBox(width: 12),
              Expanded(child: _rateValue(context, 'Venta', fmt.format(tasa.tasaVenta), scheme.primary)),
            ]),
            if (tasa.notas != null && tasa.notas!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(tasa.notas!, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant)),
            ],
          ],
        ),
      ),
    );
  }

  Widget _rateValue(BuildContext context, String label, String value, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.grey)),
        const SizedBox(height: 4),
        Text(value, style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: color)),
      ],
    );
  }

  Widget _buildNoRatesCard(BuildContext context, ColorScheme scheme) {
    return Card(
      color: scheme.errorContainer,
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Row(children: [
          Icon(Icons.warning_amber_rounded, color: scheme.onErrorContainer),
          const SizedBox(width: 12),
          Expanded(child: Text('No hay tasas publicadas para hoy. Contacta al administrador.', style: TextStyle(color: scheme.onErrorContainer))),
        ]),
      ),
    );
  }

  Widget _buildErrorCard(BuildContext context, ColorScheme scheme, VoidCallback onRetry) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(children: [
          Icon(Icons.cloud_off, color: scheme.onSurfaceVariant, size: 40),
          const SizedBox(height: 8),
          Text('Error cargando tasas', style: TextStyle(color: scheme.onSurfaceVariant)),
          const SizedBox(height: 8),
          TextButton(onPressed: onRetry, child: const Text('Reintentar')),
        ]),
      ),
    );
  }
}

extension on TasaDiariaModel {
  bool? get sinTasaReferencia => null;
}
