import 'dart:convert';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;

import '../controller/language/language_controller.dart';
import '../helpers/api_auth.dart';
import '../helpers/pref.dart';

// ─── Theme ────────────────────────────────────────────────────────────────────
const _kDeepBg = Color(0xFF050510);
const _kCard = Color(0xFF0D1B3E);
const _kAccent = Color(0xFF4F8EFF);
const _kGreen = Color(0xFF00E0C6);
const _kRed = Color(0xFFFF5F5F);
const _kAmber = Color(0xFFFFB020);
const _kWhite70 = Color(0xB3FFFFFF);

class DevicesScreen extends StatefulWidget {
  const DevicesScreen({super.key});

  @override
  State<DevicesScreen> createState() => _DevicesScreenState();
}

class _DevicesScreenState extends State<DevicesScreen>
    with TickerProviderStateMixin {
  List<Map<String, dynamic>>? _devices;
  final LanguageController _lang = Get.find<LanguageController>();

  late AnimationController _bgCtrl;
  late AnimationController _pulseCtrl;
  late AnimationController _listCtrl;

  @override
  void initState() {
    super.initState();
    SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
      systemNavigationBarColor: _kDeepBg,
    ));

    _bgCtrl =
        AnimationController(vsync: this, duration: const Duration(seconds: 18))
          ..repeat(reverse: true);
    _pulseCtrl = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 1400))
      ..repeat(reverse: true);
    _listCtrl = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 600));

    _load();
  }

  @override
  void dispose() {
    _bgCtrl.dispose();
    _pulseCtrl.dispose();
    _listCtrl.dispose();
    super.dispose();
  }

  bool _busy = false;
  List<Map<String, dynamic>> _requests = [];

  void _load() {
    setState(() {
      _devices = Pref.devices;
      _requests = Pref.deviceRequests;
    });
    _listCtrl.forward();
  }

  Future<void> _respond(Map<String, dynamic> req, String action) async {
    final targetId = (req['deviceId'] ?? '').toString();
    if (targetId.isEmpty || _busy) return;
    setState(() => _busy = true);
    try {
      final request = http.Request('POST', Uri.parse(urlPost))
        ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
        ..bodyFields = {
          ...ApiAuth.sign('respondRejoin'),
          'method': 'respondRejoin',
          'username': Pref.username,
          'password': Pref.password,
          'deviceIMEI': Pref.device,
          'targetDeviceId': targetId,
          'action': action, // approve | reject
        };
      final response =
          await request.send().timeout(const Duration(seconds: 12));
      final data = json.decode(await response.stream.bytesToString())
          as Map<String, dynamic>;
      final r = (data['result'] ?? '').toString();

      if (r == 'approved' || r == 'rejected' || r == 'blocked') {
        final updated = List<Map<String, dynamic>>.from(_requests)
          ..removeWhere((x) => (x['deviceId'] ?? '').toString() == targetId);
        Pref.deviceRequests = updated;
        if (mounted) setState(() => _requests = updated);
        _toast(action == 'approve' ? _t('approved') : _t('rejectedDone'));
      } else {
        _toast(data['msg']?.toString() ?? _t('reqFailed'));
      }
    } catch (_) {
      _toast(_t('reqFailed'));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _confirmRemove(Map<String, dynamic> device) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: _kCard,
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
        title: Text(_t('confirmTitle'),
            style: const TextStyle(
                color: Colors.white, fontWeight: FontWeight.w700)),
        content: Text(_t('confirmMsg'),
            style: const TextStyle(color: _kWhite70, fontSize: 13)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(_t('cancel'),
                style: const TextStyle(color: _kWhite70)),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(_t('remove'),
                style: const TextStyle(
                    color: _kRed, fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
    if (ok == true) await _removeDevice(device);
  }

  Future<void> _removeDevice(Map<String, dynamic> device) async {
    final targetId = (device['deviceId'] ?? '').toString();
    if (targetId.isEmpty || _busy) return;
    setState(() => _busy = true);
    try {
      final request = http.Request('POST', Uri.parse(urlPost))
        ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
        ..bodyFields = {
          ...ApiAuth.sign('removeDevice'),
          'method': 'removeDevice',
          'username': Pref.username,
          'password': Pref.password,
          'deviceIMEI': Pref.device,
          'targetDeviceId': targetId,
        };
      final response =
          await request.send().timeout(const Duration(seconds: 12));
      final body = await response.stream.bytesToString();
      final data = json.decode(body) as Map<String, dynamic>;

      if (data['result'] == 'true') {
        // حذف محلی از لیست و همگام‌سازی با Pref
        final updated = List<Map<String, dynamic>>.from(_devices ?? [])
          ..removeWhere((d) => (d['deviceId'] ?? '').toString() == targetId);
        Pref.devices = updated;
        if (mounted) setState(() => _devices = updated);
        _toast(_t('removed'));
      } else {
        _toast(data['msg']?.toString() ?? _t('removeFailed'));
      }
    } catch (_) {
      _toast(_t('removeFailed'));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  void _toast(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg, textAlign: TextAlign.center),
      backgroundColor: _kCard,
      behavior: SnackBarBehavior.floating,
    ));
  }

  // ─── Localised strings ────────────────────────────────────────────────────
  String _t(String key) {
    const map = {
      'en_US': {
        'title': 'دستگاه های متصل',
        'unknown': 'دستگاه نامشخص',
        'usage': 'حجم مصرفی',
        'status': 'وضعیت',
        'active': 'فعال',
        'disable': 'غیرفعال',
        'thisDevice': 'دستگاه فعلی',
        'remove': 'حذف',
        'confirmTitle': 'حذف دستگاه',
        'confirmMsg': 'این دستگاه از اکانت حذف و اتصالش قطع می‌شود. مطمئنید؟',
        'cancel': 'انصراف',
        'removed': 'دستگاه حذف شد',
        'removeFailed': 'حذف دستگاه ناموفق بود',
        'requestsTitle': 'درخواست‌های در انتظار',
        'approve': 'تأیید',
        'reject': 'رد',
        'approved': 'دستگاه تأیید شد',
        'rejectedDone': 'درخواست رد شد',
        'reqFailed': 'عملیات ناموفق بود',
      },
      'fa_IR': {
        'title': 'Connected Devices',
        'unknown': 'Unknown Device',
        'usage': 'Usage',
        'status': 'Status',
        'active': 'Active',
        'disable': 'Disabled',
        'thisDevice': 'This device',
        'remove': 'Remove',
        'confirmTitle': 'Remove device',
        'confirmMsg': 'This device will be removed from the account and disconnected. Are you sure?',
        'cancel': 'Cancel',
        'removed': 'Device removed',
        'removeFailed': 'Failed to remove device',
        'requestsTitle': 'Pending requests',
        'approve': 'Approve',
        'reject': 'Reject',
        'approved': 'Device approved',
        'rejectedDone': 'Request rejected',
        'reqFailed': 'Operation failed',
      },
      'zh_CN': {
        'title': '已连接设备',
        'unknown': '未知设备',
        'usage': '用量',
        'status': '状态',
        'active': '启用',
        'disable': '停用',
        'thisDevice': '当前设备',
        'remove': '移除',
        'confirmTitle': '移除设备',
        'confirmMsg': '该设备将从账户中移除并断开连接。确定吗？',
        'cancel': '取消',
        'removed': '设备已移除',
        'removeFailed': '移除设备失败',
      },
      'ru_RU': {
        'title': 'Устройства',
        'unknown': 'Неизвестное',
        'usage': 'Трафик',
        'status': 'Статус',
        'active': 'Активен',
        'disable': 'Отключен',
        'thisDevice': 'Текущее',
        'remove': 'Удалить',
        'confirmTitle': 'Удалить устройство',
        'confirmMsg': 'Устройство будет удалено из аккаунта и отключено. Вы уверены?',
        'cancel': 'Отмена',
        'removed': 'Устройство удалено',
        'removeFailed': 'Не удалось удалить устройство',
      },
    };
    return map[_lang.selectedLanguage]?[key] ?? map['en_US']![key]!;
  }

  bool _isActive(String? s) =>
      s == 'فعال' || s == 'Active' || s == '启用' || s == 'Активен';

  // ─── BUILD ────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kDeepBg,
      body: Stack(children: [
        _buildBg(),
        Column(children: [
          _buildTopBar(),
          if (_requests.isNotEmpty) _buildRequests(),
          Expanded(
              child: _devices == null
                  ? _buildEmpty()
                  : _devices!.isEmpty
                      ? _buildEmpty()
                      : _buildList()),
        ]),
      ]),
    );
  }

  Widget _buildRequests() {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 4, 16, 4),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        color: _kAmber.withOpacity(0.06),
        border: Border.all(color: _kAmber.withOpacity(0.3)),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          const Icon(Icons.person_add_alt_1_rounded,
              color: _kAmber, size: 17),
          const SizedBox(width: 6),
          Text(_t('requestsTitle'),
              style: const TextStyle(
                  color: _kAmber, fontSize: 13, fontWeight: FontWeight.w700)),
        ]),
        const SizedBox(height: 10),
        ..._requests.map((req) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(children: [
                const Icon(Icons.smartphone_rounded,
                    color: Colors.white70, size: 18),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    (req['model'] ?? '').toString().isEmpty
                        ? _t('unknown')
                        : req['model'].toString(),
                    style: const TextStyle(
                        color: Colors.white, fontSize: 12.5),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                // reject
                GestureDetector(
                  onTap: _busy ? null : () => _respond(req, 'reject'),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: _kRed.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: _kRed.withOpacity(0.35)),
                    ),
                    child: Text(_t('reject'),
                        style: const TextStyle(
                            color: _kRed,
                            fontSize: 11.5,
                            fontWeight: FontWeight.w700)),
                  ),
                ),
                const SizedBox(width: 8),
                // approve
                GestureDetector(
                  onTap: _busy ? null : () => _respond(req, 'approve'),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: _kGreen.withOpacity(0.14),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: _kGreen.withOpacity(0.4)),
                    ),
                    child: Text(_t('approve'),
                        style: const TextStyle(
                            color: _kGreen,
                            fontSize: 11.5,
                            fontWeight: FontWeight.w700)),
                  ),
                ),
              ]),
            )),
      ]),
    );
  }

  Widget _buildBg() => AnimatedBuilder(
        animation: _bgCtrl,
        builder: (_, __) {
          final t = _bgCtrl.value;
          return Container(
            decoration: BoxDecoration(
              gradient: RadialGradient(
                center: Alignment(
                  math.sin(t * math.pi * 2) * 0.2,
                  math.cos(t * math.pi * 2) * 0.2,
                ),
                radius: 1.4,
                colors: const [
                  Color(0xFF0D1B4B),
                  Color(0xFF050510),
                  Color(0xFF000000)
                ],
                stops: const [0.0, 0.55, 1.0],
              ),
            ),
          );
        },
      );

  Widget _buildTopBar() => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(8, 10, 16, 6),
          child: Row(children: [
            GestureDetector(
              onTap: () => Navigator.pop(context),
              child: Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white.withOpacity(0.06),
                  border: Border.all(color: Colors.white.withOpacity(0.1)),
                ),
                child: const Icon(Icons.arrow_back_ios_new_rounded,
                    color: Colors.white, size: 15),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
                child: Text(_t('title'),
                    style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                        letterSpacing: 0.4))),
            // device count badge
            if (_devices != null && _devices!.isNotEmpty)
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: _kAccent.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: _kAccent.withOpacity(0.35)),
                ),
                child: Row(mainAxisSize: MainAxisSize.min, children: [
                  const Icon(Icons.devices_rounded, color: _kAccent, size: 13),
                  const SizedBox(width: 4),
                  Text('${_devices!.length}',
                      style: const TextStyle(
                          color: _kAccent,
                          fontSize: 12,
                          fontWeight: FontWeight.w700)),
                ]),
              ),
          ]),
        ),
      );

  Widget _buildEmpty() => Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
            padding: const EdgeInsets.all(22),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: _kAccent.withOpacity(0.08),
              border: Border.all(color: _kAccent.withOpacity(0.2)),
            ),
            child: const Icon(Icons.devices_other_rounded,
                color: _kAccent, size: 38),
          ),
          const SizedBox(height: 14),
          Text(_t('unknown'),
              style: const TextStyle(color: _kWhite70, fontSize: 14)),
        ]),
      );

  Widget _buildList() {
    return ListView.builder(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
      itemCount: _devices!.length,
      itemBuilder: (_, i) {
        return AnimatedBuilder(
          animation: _listCtrl,
          builder: (_, child) {
            final delay = (i * 0.12).clamp(0.0, 0.9);
            final t = Curves.easeOutCubic.transform(
                ((_listCtrl.value - delay) / (1 - delay)).clamp(0.0, 1.0));
            return Opacity(
              opacity: t,
              child: Transform.translate(
                  offset: Offset(0, 24 * (1 - t)), child: child),
            );
          },
          child: _DeviceCard(
            device: _devices![i],
            index: i,
            isActive: _isActive(_devices![i]['status']),
            isCurrent:
                (_devices![i]['deviceId'] ?? '').toString() == Pref.device,
            labelUsage: _t('usage'),
            labelStatus: _t('status'),
            labelActive: _t('active'),
            labelDisable: _t('disable'),
            labelThisDevice: _t('thisDevice'),
            labelRemove: _t('remove'),
            busy: _busy,
            onRemove: () => _confirmRemove(_devices![i]),
            pulseCtrl: _pulseCtrl,
          ),
        );
      },
    );
  }
}

// ─── Device Card ──────────────────────────────────────────────────────────────
class _DeviceCard extends StatelessWidget {
  final Map<String, dynamic> device;
  final int index;
  final bool isActive;
  final bool isCurrent;
  final String labelUsage, labelStatus, labelActive, labelDisable;
  final String labelThisDevice, labelRemove;
  final bool busy;
  final VoidCallback onRemove;
  final AnimationController pulseCtrl;

  const _DeviceCard({
    required this.device,
    required this.index,
    required this.isActive,
    required this.isCurrent,
    required this.labelUsage,
    required this.labelStatus,
    required this.labelActive,
    required this.labelDisable,
    required this.labelThisDevice,
    required this.labelRemove,
    required this.busy,
    required this.onRemove,
    required this.pulseCtrl,
  });

  @override
  Widget build(BuildContext context) {
    final statusColor = isActive ? _kGreen : _kRed;
    final accentColor = isActive ? _kGreen : _kAccent;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        color: Colors.white.withOpacity(0.04),
        border: Border.all(color: accentColor.withOpacity(0.25), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: accentColor.withOpacity(0.08),
            blurRadius: 16,
            offset: const Offset(0, 4),
          )
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Row(children: [
          // ─── Device icon with pulse ──────────────────────────────────
          AnimatedBuilder(
            animation: pulseCtrl,
            builder: (_, __) {
              final pulse = 0.6 + 0.4 * pulseCtrl.value;
              return Stack(alignment: Alignment.center, children: [
                // Outer glow
                if (isActive)
                  Container(
                    width: 62 + 8 * pulse,
                    height: 62 + 8 * pulse,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: _kGreen.withOpacity(0.08 * pulse),
                    ),
                  ),
                Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: accentColor.withOpacity(0.12),
                    border: Border.all(color: accentColor.withOpacity(0.35)),
                  ),
                  child: Icon(
                    index % 3 == 0
                        ? Icons.smartphone_rounded
                        : index % 3 == 1
                            ? Icons.tablet_mac_rounded
                            : Icons.laptop_rounded,
                    color: accentColor,
                    size: 26,
                  ),
                ),
              ]);
            },
          ),

          const SizedBox(width: 16),

          // ─── Info ────────────────────────────────────────────────────
          Expanded(
              child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(device['model'] ?? '',
                  style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                      color: Colors.white),
                  overflow: TextOverflow.ellipsis),
              const SizedBox(height: 6),
              _infoRow(Icons.data_usage_rounded,
                  '$labelUsage: ${device['size'] ?? '-'}'),
              const SizedBox(height: 4),
              _infoRow(
                isActive
                    ? Icons.check_circle_outline_rounded
                    : Icons.cancel_outlined,
                '$labelStatus: ${isActive ? labelActive : labelDisable}',
                color: statusColor,
              ),
            ],
          )),

          // ─── Trailing: status dot + remove / current badge ────────────
          Column(mainAxisAlignment: MainAxisAlignment.center, children: [
            AnimatedBuilder(
              animation: pulseCtrl,
              builder: (_, __) => Container(
                width: 10,
                height: 10,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: statusColor.withOpacity(0.7 + 0.3 * pulseCtrl.value),
                  boxShadow: [
                    BoxShadow(
                      color: statusColor.withOpacity(0.5 * pulseCtrl.value),
                      blurRadius: 8,
                      spreadRadius: 2,
                    )
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            if (isCurrent)
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: _kGreen.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: _kGreen.withOpacity(0.35)),
                ),
                child: Text(labelThisDevice,
                    style: const TextStyle(
                        color: _kGreen,
                        fontSize: 10,
                        fontWeight: FontWeight.w700)),
              )
            else
              GestureDetector(
                onTap: busy ? null : onRemove,
                child: Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: _kRed.withOpacity(0.12),
                    border: Border.all(color: _kRed.withOpacity(0.35)),
                  ),
                  child: Icon(
                    busy
                        ? Icons.hourglass_empty_rounded
                        : Icons.logout_rounded,
                    color: _kRed,
                    size: 17,
                  ),
                ),
              ),
          ]),
        ]),
      ),
    );
  }

  Widget _infoRow(IconData icon, String text, {Color? color}) => Row(
        children: [
          Icon(icon, size: 13, color: color ?? _kWhite70),
          const SizedBox(width: 5),
          Expanded(
              child: Text(text,
                  style: TextStyle(fontSize: 12, color: color ?? _kWhite70),
                  overflow: TextOverflow.ellipsis)),
        ],
      );
}
