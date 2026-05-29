class OperacionModel {
  final int id;
  final String fecha;
  final String? tipoOperacion;
  final int tipoOperacionId;
  final String? clienteNombre;
  final int? clienteId;
  final String operadorNombre;
  final int operadorId;
  final double? tasaAplicada;
  final double? tasaSugerida;
  final bool sinTasaReferencia;
  final double gananciaBrutaUsd;
  final double gananciaBrutaVes;
  final double gananciaNetaUsd;
  final double gananciaNetaVes;
  final double totalComisionesUsd;
  final double totalComisionesVes;
  final String? referencia;
  final String? descripcion;
  final String estatus;
  final String? verificadoAt;
  final String createdAt;

  const OperacionModel({
    required this.id,
    required this.fecha,
    this.tipoOperacion,
    required this.tipoOperacionId,
    this.clienteNombre,
    this.clienteId,
    required this.operadorNombre,
    required this.operadorId,
    this.tasaAplicada,
    this.tasaSugerida,
    required this.sinTasaReferencia,
    required this.gananciaBrutaUsd,
    required this.gananciaBrutaVes,
    required this.gananciaNetaUsd,
    required this.gananciaNetaVes,
    required this.totalComisionesUsd,
    required this.totalComisionesVes,
    this.referencia,
    this.descripcion,
    required this.estatus,
    this.verificadoAt,
    required this.createdAt,
  });

  factory OperacionModel.fromJson(Map<String, dynamic> json) {
    final tipo = json['tipo_operacion'];
    final cliente = json['cliente'];
    final operador = json['operador'];

    return OperacionModel(
      id: json['id'] as int,
      fecha: json['fecha'] as String,
      tipoOperacion: tipo is Map ? tipo['nombre'] as String? : json['tipo_operacion_nombre'] as String?,
      tipoOperacionId: json['tipo_operacion_id'] as int,
      clienteNombre: cliente is Map ? cliente['nombre'] as String? : json['cliente_nombre'] as String?,
      clienteId: json['cliente_id'] as int?,
      operadorNombre: operador is Map ? (operador['name'] as String? ?? '') : (json['operador_nombre'] as String? ?? ''),
      operadorId: json['operador_id'] as int,
      tasaAplicada: json['tasa_aplicada'] != null ? double.parse(json['tasa_aplicada'].toString()) : null,
      tasaSugerida: json['tasa_sugerida'] != null ? double.parse(json['tasa_sugerida'].toString()) : null,
      sinTasaReferencia: (json['sin_tasa_referencia'] as int?) == 1 || json['sin_tasa_referencia'] == true,
      gananciaBrutaUsd: double.parse(json['ganancia_bruta_usd']?.toString() ?? json['ganancia_directa_usd']?.toString() ?? '0'),
      gananciaBrutaVes: double.parse(json['ganancia_bruta_ves']?.toString() ?? json['ganancia_directa_ves']?.toString() ?? '0'),
      gananciaNetaUsd: double.parse(json['ganancia_neta_usd']?.toString() ?? '0'),
      gananciaNetaVes: double.parse(json['ganancia_neta_ves']?.toString() ?? '0'),
      totalComisionesUsd: double.parse(json['total_comisiones_usd']?.toString() ?? '0'),
      totalComisionesVes: double.parse(json['total_comisiones_ves']?.toString() ?? '0'),
      referencia: json['referencia'] as String?,
      descripcion: json['descripcion'] as String?,
      estatus: json['estatus'] as String? ?? 'sin_verificar',
      verificadoAt: json['verificado_at'] as String?,
      createdAt: json['created_at'] as String,
    );
  }

  bool get isVerificado => estatus == 'verificado';
  bool get isEnRevision => estatus == 'en_revision';

  String get estatusDisplay {
    switch (estatus) {
      case 'verificado': return 'Verificado';
      case 'en_revision': return 'En revisión';
      default: return 'Sin verificar';
    }
  }
}
