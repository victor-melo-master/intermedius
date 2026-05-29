class UserModel {
  final int id;
  final String name;
  final String email;
  final List<String> roles;
  final int? titularId;
  final String? lastLoginAt;

  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    required this.roles,
    this.titularId,
    this.lastLoginAt,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) => UserModel(
        id: json['id'] as int,
        name: json['name'] as String,
        email: json['email'] as String,
        roles: List<String>.from(json['roles'] as List),
        titularId: json['titular_id'] as int?,
        lastLoginAt: json['last_login_at'] as String?,
      );

  bool get isSuperAdmin => roles.contains('super_admin');
  bool get isAdmin => roles.contains('admin') || isSuperAdmin;
  bool get isOperador => roles.contains('operador') || isAdmin;
  bool get isContador => roles.contains('contador') || isAdmin;

  String get rolDisplay {
    if (isSuperAdmin) return 'Super Admin';
    if (roles.contains('admin')) return 'Admin';
    if (roles.contains('contador')) return 'Contador';
    if (roles.contains('operador')) return 'Operador';
    return 'Lectura';
  }
}
