import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/network/dio_client.dart';
import '../../models/cliente_model.dart';

final clientesScreenProvider = FutureProvider.family<List<ClienteModel>, String>((ref, search) async {
  final dio = ref.watch(dioProvider);
  final params = search.isNotEmpty ? {'search': search} : <String, dynamic>{};
  final response = await dio.get('/clientes', queryParameters: params.isEmpty ? null : params);
  final data = response.data['data'] as List? ?? [];
  return data.map((e) => ClienteModel.fromJson(e as Map<String, dynamic>)).toList();
});

class ClientesScreen extends ConsumerStatefulWidget {
  const ClientesScreen({super.key});

  @override
  ConsumerState<ClientesScreen> createState() => _ClientesScreenState();
}

class _ClientesScreenState extends ConsumerState<ClientesScreen> {
  final _searchCtrl = TextEditingController();
  String _search = '';

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final clientesAsync = ref.watch(clientesScreenProvider(_search));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Clientes'),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(64),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: TextField(
              controller: _searchCtrl,
              decoration: InputDecoration(
                hintText: 'Buscar por nombre o alias...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _search.isNotEmpty ? IconButton(icon: const Icon(Icons.clear), onPressed: () { _searchCtrl.clear(); setState(() => _search = ''); }) : null,
                filled: true,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                contentPadding: const EdgeInsets.symmetric(vertical: 0),
              ),
              onChanged: (v) => setState(() => _search = v),
            ),
          ),
        ),
        actions: [
          IconButton(icon: const Icon(Icons.refresh), onPressed: () => ref.invalidate(clientesScreenProvider(_search))),
        ],
      ),
      body: clientesAsync.when(
        data: (clientes) => clientes.isEmpty
            ? Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
                Icon(Icons.people_outline, size: 64, color: Theme.of(context).colorScheme.onSurfaceVariant),
                const SizedBox(height: 16),
                Text(_search.isNotEmpty ? 'Sin resultados para "$_search"' : 'No hay clientes'),
              ]))
            : ListView.separated(
                padding: const EdgeInsets.all(8),
                itemCount: clientes.length,
                separatorBuilder: (_, __) => const SizedBox(height: 4),
                itemBuilder: (_, i) => _ClienteTile(cliente: clientes[i], onChanged: () => ref.invalidate(clientesScreenProvider(_search))),
              ),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text(e.toString())),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showClienteForm(context, ref, null),
        icon: const Icon(Icons.person_add_outlined),
        label: const Text('Nuevo cliente'),
      ),
    );
  }

  void _showClienteForm(BuildContext context, WidgetRef ref, ClienteModel? cliente) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (_) => ProviderScope(
        parent: ProviderScope.containerOf(context),
        child: _ClienteForm(
          cliente: cliente,
          onSaved: () => ref.invalidate(clientesScreenProvider(_search)),
        ),
      ),
    );
  }
}

class _ClienteTile extends StatelessWidget {
  final ClienteModel cliente;
  final VoidCallback onChanged;
  const _ClienteTile({required this.cliente, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final fmtUsd = NumberFormat('\$#,##0.00', 'en');

    return Card(
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: scheme.primaryContainer,
          child: Text(cliente.nombre.substring(0, 1).toUpperCase(), style: TextStyle(color: scheme.primary, fontWeight: FontWeight.bold)),
        ),
        title: Text(cliente.nombre, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          if (cliente.alias != null) Text(cliente.alias!, style: const TextStyle(fontSize: 12)),
          if (cliente.telefono != null) Text(cliente.telefono!, style: const TextStyle(fontSize: 12)),
        ]),
        trailing: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.end, children: [
          Text(fmtUsd.format(cliente.saldoCacheUsd), style: TextStyle(fontWeight: FontWeight.bold, color: cliente.saldoCacheUsd >= 0 ? Colors.green.shade700 : scheme.error, fontSize: 13)),
          const SizedBox(height: 2),
          if (!cliente.activo) Container(padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2), decoration: BoxDecoration(color: scheme.errorContainer, borderRadius: BorderRadius.circular(8)), child: Text('Inactivo', style: TextStyle(fontSize: 10, color: scheme.onErrorContainer))),
        ]),
        isThreeLine: cliente.alias != null,
      ),
    );
  }
}

class _ClienteForm extends ConsumerStatefulWidget {
  final ClienteModel? cliente;
  final VoidCallback onSaved;
  const _ClienteForm({this.cliente, required this.onSaved});

  @override
  ConsumerState<_ClienteForm> createState() => _ClienteFormState();
}

class _ClienteFormState extends ConsumerState<_ClienteForm> {
  final _formKey = GlobalKey<FormState>();
  late final _nombreCtrl = TextEditingController(text: widget.cliente?.nombre);
  late final _aliasCtrl = TextEditingController(text: widget.cliente?.alias);
  late final _telefonoCtrl = TextEditingController(text: widget.cliente?.telefono);
  late final _emailCtrl = TextEditingController(text: widget.cliente?.email);
  late final _notasCtrl = TextEditingController(text: widget.cliente?.notas);
  bool _isLoading = false;
  String? _error;

  @override
  void dispose() {
    _nombreCtrl.dispose(); _aliasCtrl.dispose(); _telefonoCtrl.dispose();
    _emailCtrl.dispose(); _notasCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Text(widget.cliente == null ? 'Nuevo cliente' : 'Editar cliente', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold)),
            const SizedBox(height: 20),
            TextFormField(controller: _nombreCtrl, decoration: const InputDecoration(labelText: 'Nombre *', border: OutlineInputBorder()), validator: (v) => (v == null || v.isEmpty) ? 'Requerido' : null),
            const SizedBox(height: 12),
            TextFormField(controller: _aliasCtrl, decoration: const InputDecoration(labelText: 'Alias', border: OutlineInputBorder())),
            const SizedBox(height: 12),
            TextFormField(controller: _telefonoCtrl, decoration: const InputDecoration(labelText: 'Teléfono', border: OutlineInputBorder()), keyboardType: TextInputType.phone),
            const SizedBox(height: 12),
            TextFormField(controller: _emailCtrl, decoration: const InputDecoration(labelText: 'Email', border: OutlineInputBorder()), keyboardType: TextInputType.emailAddress),
            const SizedBox(height: 12),
            TextFormField(controller: _notasCtrl, decoration: const InputDecoration(labelText: 'Notas', border: OutlineInputBorder()), maxLines: 2),
            if (_error != null) ...[
              const SizedBox(height: 12),
              Container(padding: const EdgeInsets.all(10), decoration: BoxDecoration(color: scheme.errorContainer, borderRadius: BorderRadius.circular(8)), child: Text(_error!, style: TextStyle(color: scheme.onErrorContainer))),
            ],
            const SizedBox(height: 20),
            FilledButton(
              onPressed: _isLoading ? null : _submit,
              style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14)),
              child: _isLoading ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)) : Text(widget.cliente == null ? 'Crear cliente' : 'Guardar cambios'),
            ),
            const SizedBox(height: 8),
          ]),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() { _isLoading = true; _error = null; });
    try {
      final dio = ref.read(dioProvider);
      final body = {
        'nombre': _nombreCtrl.text,
        if (_aliasCtrl.text.isNotEmpty) 'alias': _aliasCtrl.text,
        if (_telefonoCtrl.text.isNotEmpty) 'telefono': _telefonoCtrl.text,
        if (_emailCtrl.text.isNotEmpty) 'email': _emailCtrl.text,
        if (_notasCtrl.text.isNotEmpty) 'notas': _notasCtrl.text,
      };
      if (widget.cliente == null) {
        await dio.post('/clientes', data: body);
      } else {
        await dio.put('/clientes/${widget.cliente!.id}', data: body);
      }
      if (mounted) { Navigator.pop(context); widget.onSaved(); ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(widget.cliente == null ? 'Cliente creado' : 'Cliente actualizado'))); }
    } catch (e) {
      setState(() { _error = e.toString(); _isLoading = false; });
    }
  }
}
