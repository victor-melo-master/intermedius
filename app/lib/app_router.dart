import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'core/auth/auth_provider.dart';
import 'features/auth/auth_screen.dart';
import 'features/shell/shell_screen.dart';
import 'features/dashboard/dashboard_screen.dart';
import 'features/operaciones/operaciones_screen.dart';
import 'features/operaciones/operacion_detail_screen.dart';
import 'features/operaciones/operacion_form_screen.dart';
import 'features/tasas/tasas_screen.dart';
import 'features/clientes/clientes_screen.dart';
import 'features/cuentas/cuentas_screen.dart';
import 'features/reportes/reportes_screen.dart';

class _RouterNotifier extends ChangeNotifier {
  final Ref _ref;
  late final ProviderSubscription<AuthState> _sub;

  _RouterNotifier(this._ref) {
    _sub = _ref.listen(authNotifierProvider, (_, __) => notifyListeners());
  }

  @override
  void dispose() {
    _sub.close();
    super.dispose();
  }

  String? redirect(BuildContext context, GoRouterState state) {
    final auth = _ref.read(authNotifierProvider);
    if (!auth.isInitialized) return null;

    final isLoginPage = state.matchedLocation == '/login';
    if (!auth.isAuthenticated && !isLoginPage) return '/login';
    if (auth.isAuthenticated && isLoginPage) return '/dashboard';
    return null;
  }
}

final routerProvider = Provider<GoRouter>((ref) {
  final notifier = _RouterNotifier(ref);
  return GoRouter(
    refreshListenable: notifier,
    redirect: notifier.redirect,
    initialLocation: '/dashboard',
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => const AuthScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) => ShellScreen(child: child),
        routes: [
          GoRoute(
            path: '/dashboard',
            builder: (context, state) => const DashboardScreen(),
          ),
          GoRoute(
            path: '/operaciones',
            builder: (context, state) => const OperacionesScreen(),
            routes: [
              GoRoute(
                path: 'nueva',
                builder: (context, state) => const OperacionFormScreen(),
              ),
              GoRoute(
                path: ':id',
                builder: (context, state) => OperacionDetailScreen(
                  id: int.parse(state.pathParameters['id']!),
                ),
              ),
            ],
          ),
          GoRoute(
            path: '/tasas',
            builder: (context, state) => const TasasScreen(),
          ),
          GoRoute(
            path: '/clientes',
            builder: (context, state) => const ClientesScreen(),
          ),
          GoRoute(
            path: '/cuentas',
            builder: (context, state) => const CuentasScreen(),
          ),
          GoRoute(
            path: '/reportes',
            builder: (context, state) => const ReportesScreen(),
          ),
        ],
      ),
    ],
  );
});
