import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/auth/auth_provider.dart';

class ShellScreen extends ConsumerWidget {
  final Widget child;
  const ShellScreen({super.key, required this.child});

  static const _tabs = [
    _NavTab(path: '/dashboard', icon: Icons.dashboard_outlined, activeIcon: Icons.dashboard, label: 'Dashboard'),
    _NavTab(path: '/operaciones', icon: Icons.receipt_long_outlined, activeIcon: Icons.receipt_long, label: 'Operaciones'),
    _NavTab(path: '/tasas', icon: Icons.trending_up_outlined, activeIcon: Icons.trending_up, label: 'Tasas'),
    _NavTab(path: '/clientes', icon: Icons.people_outline, activeIcon: Icons.people, label: 'Clientes'),
    _NavTab(path: '/reportes', icon: Icons.bar_chart_outlined, activeIcon: Icons.bar_chart, label: 'Reportes'),
  ];

  int _currentIndex(BuildContext context) {
    final location = GoRouterState.of(context).matchedLocation;
    for (var i = 0; i < _tabs.length; i++) {
      if (location.startsWith(_tabs[i].path)) return i;
    }
    return 0;
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authNotifierProvider).user;
    final current = _currentIndex(context);

    return Scaffold(
      body: child,
      bottomNavigationBar: NavigationBar(
        selectedIndex: current,
        onDestinationSelected: (i) => context.go(_tabs[i].path),
        destinations: _tabs.map((t) => NavigationDestination(
          icon: Icon(t.icon),
          selectedIcon: Icon(t.activeIcon),
          label: t.label,
        )).toList(),
      ),
      drawer: _buildDrawer(context, ref, user),
    );
  }

  Widget _buildDrawer(BuildContext context, WidgetRef ref, dynamic user) {
    final scheme = Theme.of(context).colorScheme;
    return Drawer(
      child: SafeArea(
        child: Column(
          children: [
            DrawerHeader(
              decoration: BoxDecoration(color: scheme.primaryContainer),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: scheme.primary,
                    child: Icon(Icons.person, color: scheme.onPrimary, size: 30),
                  ),
                  const SizedBox(height: 12),
                  Text(user?.name ?? '', style: TextStyle(fontWeight: FontWeight.bold, color: scheme.onPrimaryContainer, fontSize: 16)),
                  Text(user?.rolDisplay ?? '', style: TextStyle(color: scheme.onPrimaryContainer.withOpacity(0.8), fontSize: 12)),
                ],
              ),
            ),
            ListTile(
              leading: const Icon(Icons.currency_exchange),
              title: const Text('Cuentas'),
              onTap: () { Navigator.pop(context); context.go('/cuentas'); },
            ),
            if (user?.isSuperAdmin == true)
              ListTile(
                leading: const Icon(Icons.history),
                title: const Text('Bitácora'),
                onTap: () => Navigator.pop(context),
              ),
            const Spacer(),
            const Divider(),
            ListTile(
              leading: Icon(Icons.logout, color: scheme.error),
              title: Text('Cerrar sesión', style: TextStyle(color: scheme.error)),
              onTap: () async {
                Navigator.pop(context);
                await ref.read(authNotifierProvider.notifier).logout();
              },
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}

class _NavTab {
  final String path;
  final IconData icon;
  final IconData activeIcon;
  final String label;
  const _NavTab({required this.path, required this.icon, required this.activeIcon, required this.label});
}
