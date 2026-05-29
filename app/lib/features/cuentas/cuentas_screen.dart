import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/network/dio_client.dart';

final cuentasProvider = FutureProvider<List<Map<String, dynamic>>>((ref) async {
  final dio = ref.watch(dioProvider);
  final response = await dio.get('/cuentas');
  final data = response.data['data'] as List? ?? [];
  return data.cast<Map<String, dynamic>>();
});

class CuentasScreen extends ConsumerWidget {
  const CuentasScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cuentasAsync = ref.watch(cuentasProvider);
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Cuentas'),
        actions: [IconButton(icon: const Icon(Icons.refresh), onPressed: () => ref.invalidate(cuentasProvider))],
      ),
      body: cuentasAsync.when(
        data: (cuentas) => cuentas.isEmpty
            ? Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
                Icon(Icons.account_balance_outlined, size: 64, color: scheme.onSurfaceVariant),
                const SizedBox(height: 16),
                const Text('No hay cuentas registradas'),
              ]))
            : ListView.separated(
                padding: const EdgeInsets.all(8),
                itemCount: cuentas.length,
                separatorBuilder: (_, __) => const SizedBox(height: 4),
                itemBuilder: (_, i) {
                  final c = cuentas[i];
                  final titular = c['titular'] as Map<String, dynamic>?;
                  final banco = c['banco'] as Map<String, dynamic>?;
                  final moneda = c['moneda'] as Map<String, dynamic>?;
                  return Card(
                    child: ListTile(
                      leading: CircleAvatar(
                        backgroundColor: scheme.primaryContainer,
                        child: Icon(Icons.account_balance, color: scheme.primary, size: 20),
                      ),
                      title: Text(c['nombre'] as String? ?? 'Cuenta #${c['id']}', style: const TextStyle(fontWeight: FontWeight.w600)),
                      subtitle: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        if (titular != null) Text('Titular: ${titular['nombre'] ?? ''}', style: const TextStyle(fontSize: 12)),
                        if (banco != null) Text('Banco: ${banco['nombre'] ?? ''}', style: const TextStyle(fontSize: 12)),
                      ]),
                      trailing: moneda != null
                          ? Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(color: scheme.secondaryContainer, borderRadius: BorderRadius.circular(12)),
                              child: Text(moneda['codigo'] as String? ?? '', style: TextStyle(fontWeight: FontWeight.bold, color: scheme.onSecondaryContainer)),
                            )
                          : null,
                      isThreeLine: titular != null,
                    ),
                  );
                },
              ),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Icon(Icons.error_outline, size: 48, color: Colors.grey),
          const SizedBox(height: 8),
          Text(e.toString()),
          TextButton(onPressed: () => ref.invalidate(cuentasProvider), child: const Text('Reintentar')),
        ])),
      ),
    );
  }
}
