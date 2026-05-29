import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/user_model.dart';
import '../network/dio_client.dart';
import '../storage/secure_storage.dart';

class AuthState {
  final UserModel? user;
  final String? token;
  final bool isInitialized;
  final bool isLoading;
  final String? error;

  const AuthState({
    this.user,
    this.token,
    this.isInitialized = false,
    this.isLoading = false,
    this.error,
  });

  bool get isAuthenticated => isInitialized && user != null && token != null;

  AuthState copyWith({
    UserModel? user,
    String? token,
    bool? isInitialized,
    bool? isLoading,
    String? error,
    bool clearUser = false,
    bool clearToken = false,
    bool clearError = false,
  }) {
    return AuthState(
      user: clearUser ? null : (user ?? this.user),
      token: clearToken ? null : (token ?? this.token),
      isInitialized: isInitialized ?? this.isInitialized,
      isLoading: isLoading ?? this.isLoading,
      error: clearError ? null : (error ?? this.error),
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final Ref _ref;

  AuthNotifier(this._ref) : super(const AuthState()) {
    _init();
  }

  Dio get _dio => _ref.read(dioProvider);

  Future<void> _init() async {
    final token = await SecureStorage.getToken();
    if (token == null) {
      state = state.copyWith(isInitialized: true);
      return;
    }
    try {
      final response = await _dio.get('/auth/me');
      final user = UserModel.fromJson(response.data['data'] ?? response.data);
      state = state.copyWith(user: user, token: token, isInitialized: true);
    } catch (_) {
      await SecureStorage.deleteToken();
      state = state.copyWith(isInitialized: true);
    }
  }

  Future<void> login(String email, String password) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _dio.post('/auth/login', data: {
        'email': email,
        'password': password,
      });
      final data = response.data;
      final token = data['token'] as String;
      final user = UserModel.fromJson(data['user'] as Map<String, dynamic>);
      await SecureStorage.saveToken(token);
      state = state.copyWith(user: user, token: token, isLoading: false, isInitialized: true);
    } on DioException catch (e) {
      final msg = _parseError(e);
      state = state.copyWith(isLoading: false, error: msg);
    }
  }

  Future<void> logout() async {
    try {
      await _dio.post('/auth/logout');
    } catch (_) {}
    await SecureStorage.deleteToken();
    state = const AuthState(isInitialized: true);
  }

  String _parseError(DioException e) {
    final data = e.response?.data;
    if (data is Map && data['message'] != null) {
      return data['message'] as String;
    }
    if (e.response?.statusCode == 401) return 'Credenciales incorrectas';
    if (e.response?.statusCode == 422) return 'Datos inválidos';
    return 'Error de conexión. Verifique su red.';
  }
}

final authNotifierProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(ref);
});
