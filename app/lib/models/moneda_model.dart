class MonedaModel {
  final int id;
  final String codigo;
  final String nombre;
  final String? simbolo;
  final bool esFiat;
  final bool esCripto;
  final int decimales;
  final bool activa;

  const MonedaModel({
    required this.id,
    required this.codigo,
    required this.nombre,
    this.simbolo,
    required this.esFiat,
    required this.esCripto,
    required this.decimales,
    required this.activa,
  });

  factory MonedaModel.fromJson(Map<String, dynamic> json) => MonedaModel(
        id: json['id'] as int,
        codigo: json['codigo'] as String,
        nombre: json['nombre'] as String,
        simbolo: json['simbolo'] as String?,
        esFiat: (json['es_fiat'] as int?) == 1 || json['es_fiat'] == true,
        esCripto: (json['es_cripto'] as int?) == 1 || json['es_cripto'] == true,
        decimales: json['decimales'] as int? ?? 2,
        activa: (json['activa'] as int?) == 1 || json['activa'] == true,
      );

  String get displaySymbol => simbolo ?? codigo;
}
