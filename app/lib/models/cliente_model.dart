class ClienteModel {
  final int id;
  final String nombre;
  final String? alias;
  final String? documento;
  final String? telefono;
  final String? email;
  final String? notas;
  final double saldoCacheUsd;
  final bool activo;

  const ClienteModel({
    required this.id,
    required this.nombre,
    this.alias,
    this.documento,
    this.telefono,
    this.email,
    this.notas,
    required this.saldoCacheUsd,
    required this.activo,
  });

  factory ClienteModel.fromJson(Map<String, dynamic> json) => ClienteModel(
        id: json['id'] as int,
        nombre: json['nombre'] as String,
        alias: json['alias'] as String?,
        documento: json['documento'] as String?,
        telefono: json['telefono'] as String?,
        email: json['email'] as String?,
        notas: json['notas'] as String?,
        saldoCacheUsd: double.parse(json['saldo_cache_usd']?.toString() ?? '0'),
        activo: (json['activo'] as int?) == 1 || json['activo'] == true,
      );

  String get displayName => alias != null && alias!.isNotEmpty ? '$nombre ($alias)' : nombre;
}
