import 'moneda_model.dart';

class TasaDiariaModel {
  final int id;
  final String fecha;
  final MonedaModel? monedaBase;
  final MonedaModel? monedaCotizada;
  final int monedaBaseId;
  final int monedaCotizadaId;
  final double tasaCompra;
  final double tasaVenta;
  final String? notas;
  final String vigenteDesde;
  final String? vigenteHasta;

  const TasaDiariaModel({
    required this.id,
    required this.fecha,
    this.monedaBase,
    this.monedaCotizada,
    required this.monedaBaseId,
    required this.monedaCotizadaId,
    required this.tasaCompra,
    required this.tasaVenta,
    this.notas,
    required this.vigenteDesde,
    this.vigenteHasta,
  });

  factory TasaDiariaModel.fromJson(Map<String, dynamic> json) => TasaDiariaModel(
        id: json['id'] as int,
        fecha: json['fecha'] as String,
        monedaBase: json['moneda_base'] != null
            ? MonedaModel.fromJson(json['moneda_base'] as Map<String, dynamic>)
            : null,
        monedaCotizada: json['moneda_cotizada'] != null
            ? MonedaModel.fromJson(json['moneda_cotizada'] as Map<String, dynamic>)
            : null,
        monedaBaseId: json['moneda_base_id'] as int,
        monedaCotizadaId: json['moneda_cotizada_id'] as int,
        tasaCompra: double.parse(json['tasa_compra'].toString()),
        tasaVenta: double.parse(json['tasa_venta'].toString()),
        notas: json['notas'] as String?,
        vigenteDesde: json['vigente_desde'] as String,
        vigenteHasta: json['vigente_hasta'] as String?,
      );

  bool get isVigente => vigenteHasta == null;

  String get parDisplay {
    final base = monedaBase?.codigo ?? monedaBaseId.toString();
    final cotizada = monedaCotizada?.codigo ?? monedaCotizadaId.toString();
    return '$base/$cotizada';
  }
}
