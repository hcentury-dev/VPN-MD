import 'package:flutter/material.dart';
import 'package:permission_handler/permission_handler.dart';

class PermissionsGuide extends StatefulWidget {
  const PermissionsGuide({super.key});

  @override
  State<PermissionsGuide> createState() => _PermissionsGuideState();
}

class _PermissionsGuideState extends State<PermissionsGuide> {
  static const Color _accent = Color(0xff416ff4);
  bool _notif = false;
  bool _battery = false;

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  Future<void> _refresh() async {
    final n = await Permission.notification.isGranted;
    final b = await Permission.ignoreBatteryOptimizations.isGranted;
    if (mounted) {
      setState(() {
        _notif = n;
        _battery = b;
      });
    }
  }

  Future<void> _ask(Permission p) async {
    await p.request();
    await _refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: const Color(0xff0e0e14),
        appBar: AppBar(
          backgroundColor: const Color(0xff14141c),
          elevation: 0,
          title: const Text('راهنمای مجوزها',
              style: TextStyle(color: Colors.white)),
          iconTheme: const IconThemeData(color: Colors.white),
        ),
        body: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            const Text(
              'برای کارکرد درست برنامه، این مجوزها را فعال کن:',
              style: TextStyle(color: Colors.white70, fontSize: 14, height: 1.8),
            ),
            const SizedBox(height: 16),
            _tile(
              icon: Icons.notifications_active_rounded,
              title: 'اعلان‌ها',
              desc: 'برای دریافت وضعیت اتصال، هشدار حجم و زمان، و پاسخ پشتیبانی.',
              granted: _notif,
              onTap: () => _ask(Permission.notification),
            ),
            _tile(
              icon: Icons.battery_charging_full_rounded,
              title: 'حذف محدودیت باتری',
              desc: 'تا اتصال در پس‌زمینه توسط سیستم قطع نشود.',
              granted: _battery,
              onTap: () => _ask(Permission.ignoreBatteryOptimizations),
            ),
            const SizedBox(height: 10),
            TextButton.icon(
              onPressed: () => openAppSettings(),
              icon: const Icon(Icons.settings_rounded, color: _accent, size: 18),
              label: const Text('باز کردن تنظیمات برنامه',
                  style: TextStyle(color: _accent)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _tile({
    required IconData icon,
    required String title,
    required String desc,
    required bool granted,
    required VoidCallback onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xff1a1a24),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        children: [
          Icon(icon, color: _accent, size: 26),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 15)),
                const SizedBox(height: 4),
                Text(desc,
                    style: const TextStyle(
                        color: Colors.white54, fontSize: 12, height: 1.6)),
              ],
            ),
          ),
          const SizedBox(width: 10),
          granted
              ? const Icon(Icons.check_circle_rounded,
                  color: Color(0xff2ecc71), size: 26)
              : ElevatedButton(
                  onPressed: onTap,
                  style: ElevatedButton.styleFrom(
                      backgroundColor: _accent, elevation: 0),
                  child: const Text('فعال'),
                ),
        ],
      ),
    );
  }
}
