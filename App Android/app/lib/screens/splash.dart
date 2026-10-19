import 'dart:async';
import 'dart:convert';
import 'dart:developer';
import 'dart:io';
import 'dart:math' as math;

import 'package:get_storage/get_storage.dart';
import 'package:http/http.dart' as http;
import 'package:get/get.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:cherry_toast/cherry_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_v2ray_client/flutter_v2ray.dart';
import 'package:device_info_plus/device_info_plus.dart';

import '../Login.dart';
import '../helpers/api_auth.dart';
import 'waiting_approval.dart';
import '../controller/language/language_config.dart';
import '../controller/language/language_controller.dart';
import '../global.dart';
import '../helpers/pref.dart';
import 'home_screen.dart';
import '../class.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with TickerProviderStateMixin {
  // ─── State ────────────────────────────────────────────────────────────────
  bool _isError = false;
  ResultStatus _status = ResultStatus.initial;
  bool _navigated = false; // ✅ جلوگیری از navigate دوگانه
  bool _deviceHealHandled = false; // ✅ جلوگیری از اجرای دوباره‌ی مهاجرت/auto-login

  // ─── Animation Controllers ────────────────────────────────────────────────
  late AnimationController _logoController;
  late AnimationController _pulseController;
  late AnimationController _particleController;
  late AnimationController _bgController;
  late AnimationController _textController;

  late Animation<double> _logoScale;
  late Animation<double> _logoOpacity;
  late Animation<double> _pulseAnim;
  late Animation<double> _textSlide;
  late Animation<double> _textOpacity;

  V2ray flutterV2ray = V2rayManager.i.flutterV2ray;
  final LanguageController languageController = Get.find<LanguageController>();

  @override
  void initState() {
    super.initState();
    _initAnimations();
    _setSystemUI();
    _checkRootStatus();
  }

  void _setSystemUI() {
    SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
      systemNavigationBarColor: Color(0xFF050510),
    ));
  }

  void _initAnimations() {
    // Logo
    _logoController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    );
    _logoScale = CurvedAnimation(
      parent: _logoController,
      curve: Curves.easeOutBack,
    ).drive(Tween(begin: 0.0, end: 1.0));
    _logoOpacity = CurvedAnimation(
      parent: _logoController,
      curve: const Interval(0.0, 0.6),
    ).drive(Tween(begin: 0.0, end: 1.0));

    // Pulse glow
    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    )..repeat(reverse: true);
    _pulseAnim = CurvedAnimation(
      parent: _pulseController,
      curve: Curves.easeInOut,
    ).drive(Tween(begin: 0.6, end: 1.0));

    // Particle rotation
    _particleController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 12),
    )..repeat();

    // Background
    _bgController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 20),
    )..repeat(reverse: true);

    // Text slide
    _textController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _textSlide = CurvedAnimation(
      parent: _textController,
      curve: Curves.easeOutCubic,
    ).drive(Tween(begin: 30.0, end: 0.0));
    _textOpacity = CurvedAnimation(
      parent: _textController,
      curve: Curves.easeOut,
    ).drive(Tween(begin: 0.0, end: 1.0));

    // Start sequence
    _logoController.forward().then((_) {
      _textController.forward();
    });
  }

  @override
  void dispose() {
    _logoController.dispose();
    _pulseController.dispose();
    _particleController.dispose();
    _bgController.dispose();
    _textController.dispose();
    super.dispose();
  }

  // ─── Root Check ───────────────────────────────────────────────────────────
  Future<void> _checkRootStatus() async {
    final isRooted = await _isDeviceRooted();
    if (!mounted) return;

    if (isRooted) {
      await showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => AlertDialog(
          title: Text(LanguageConstants.home11.tr),
          content: Text(LanguageConstants.server15.tr),
          actions: [
            TextButton(
              onPressed: () => exit(0),
              child: Text(LanguageConstants.close.tr),
            ),
          ],
        ),
      );
      return;
    }

    _startFetch();
  }

  void _startFetch() {
    _fetchData();
    // Timeout fallback
    Future.delayed(const Duration(seconds: 10), () {
      if (mounted && _status == ResultStatus.initial) {
        setState(() {
          _isError = true;
          _status = ResultStatus.error;
        });
      }
    });
  }

  Future<bool> _isDeviceRooted() async {
    if (!Platform.isAndroid) return false;
    final deviceInfo = DeviceInfoPlugin();
    final androidInfo = await deviceInfo.androidInfo;
    final rootPaths = [
      '/system/app/Superuser.apk',
      '/system/xbin/su',
      '/system/bin/su',
      '/system/bin/.ext/.su',
      '/system/sd/xbin/su',
      '/sbin/su',
      '/system/etc/init.d/99SuperSUDaemon',
    ];
    for (final path in rootPaths) {
      if (await File(path).exists()) return true;
    }
    return androidInfo.tags.contains('test-keys');
  }

  // ─── Data Fetch ───────────────────────────────────────────────────────────

  /// ✅ تنها نقطه‌ای که navigate میشه
  void _safeGoHome() {
    if (_navigated) return;
    _navigated = true;
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      PageRouteBuilder(
        transitionDuration: const Duration(milliseconds: 600),
        pageBuilder: (_, animation, __) => HomeScreen(
          Pref.vpnClass ??
              VPNClass(
                serverId: '0',
                nameEn: LanguageConstants.server7.tr,
                nameFa: LanguageConstants.server7.tr,
                src: useR,
                locationsEn: LanguageConstants.server8.tr,
                locationsFa: LanguageConstants.server8.tr,
                titleEn: '',
                titleFa: '',
                permium: '',
                fragment: '',
                json: '',
                status: '',
                date: '',
                link:
                    'vless://1430c454-5344-4a90-ddf1-16016b98bad4@8.8.8.8.8:81?encryption=none&security=none&type=tcp&headerType=http#End volume',
              ),
        ),
        transitionsBuilder: (_, animation, __, child) {
          return FadeTransition(
            opacity: CurvedAnimation(parent: animation, curve: Curves.easeOut),
            child: child,
          );
        },
      ),
    );
  }

  /// انتقال به صفحه‌ی انتظار/بلاک (سیستم kick و بازگشت)
  void _goWaiting({required bool blocked}) {
    if (_navigated) return;
    _navigated = true;
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(
        builder: (_) => WaitingApprovalScreen(blocked: blocked),
      ),
    );
  }

  Future<void> _fetchData() async {
    _status = await _getData().timeout(
      const Duration(seconds: 10),
      onTimeout: () => ResultStatus.error,
    );

    if (!mounted) return;

    switch (_status) {
      // ✅ همه حالت‌های موفق فقط یه بار goHome صدا میشه
      case ResultStatus.success:
      case ResultStatus.endDay:
      case ResultStatus.endGB:
      case ResultStatus.suspended:
        await syncServersWithHive();
        if (Pref.fisrtTime) {
          String msg = '';
          if (_status == ResultStatus.endDay) msg = LanguageConstants.login4.tr;
          if (_status == ResultStatus.endGB) msg = LanguageConstants.login3.tr;
          if (_status == ResultStatus.suspended)
            msg = LanguageConstants.login5.tr;
          if (msg.isNotEmpty) _showToast(msg);
          await Pref.setFisrtTime(false);
        }
        _safeGoHome(); // ✅ فقط یه بار
        break;

      case ResultStatus.device:
        _handleDeviceNotFound();
        break;

      case ResultStatus.waiting:
        _goWaiting(blocked: false);
        break;

      case ResultStatus.blocked:
        _goWaiting(blocked: true);
        break;

      case ResultStatus.logout:
        deleteAccount();
        break;

      default:
        if (mounted) setState(() => _isError = true);
    }
  }

  Future<ResultStatus> _getData() async {
    try {
      final request = http.Request('POST', Uri.parse(urlPost));
      request.headers['Content-Type'] = 'application/x-www-form-urlencoded';
      request.bodyFields = {
        ...ApiAuth.sign('details'),
        'method': 'details',
        'deviceIMEI': Pref.device,
        'username': Pref.username,
        'password': Pref.password,
      };

      final response = await request.send();
      if (response.statusCode != 200) return ResultStatus.error;

      final res = await response.stream.bytesToString();
      final data = json.decode(res) as Map<String, dynamic>;

      switch (data['result']) {
        case 'success':
          _updatePrefs(data);
          Pref.result = 'success';
          return ResultStatus.success;

        case 'EndGB':
          Pref.result = 'EndGB';
          _updatePrefs(data, isGB: false);
          return ResultStatus.endGB; // ✅ بدون goHome اینجا

        case 'EndDay':
          Pref.result = 'EndDay';
          _updatePrefs(data, isDay: false);
          return ResultStatus.endDay; // ✅ بدون goHome اینجا

        case 'suspended':
          Pref.result = 'suspended';
          _updatePrefs(data, isActive: false);
          return ResultStatus.suspended; // ✅ بدون goHome اینجا

        case 'logout':
          return ResultStatus.logout;

        case 'waiting':
          return ResultStatus.waiting;

        case 'blocked':
          return ResultStatus.blocked;

        case 'approved':
          // لیدر در زمان بسته‌بودن اپ تأیید کرده ⇒ ثبت و ادامه
          final ok =
              await _tryReRegisterDevice(Pref.username, Pref.password);
          if (ok) return await _getData();
          return ResultStatus.waiting;

        case 'device_not_found':
        case 'current':
          return ResultStatus.device;

        default:
          return ResultStatus.error;
      }
    } catch (e, s) {
      log('getData error: $e', stackTrace: s);
      return ResultStatus.error;
    }
  }

  void _updatePrefs(
    Map<String, dynamic> data, {
    bool isGB = true,
    bool isDay = true,
    bool isActive = true,
  }) {
    if (data['ads'] != null) Pref.ads = jsonEncode(data['ads']);
    if (data['adsFull'] != null) Pref.adsFull = jsonEncode(data['adsFull']);

    final users = data['users'] as Map<String, dynamic>? ?? {};
    final app = data['application'] as Map<String, dynamic>? ?? {};
    final notif = data['notification'] as Map<String, dynamic>? ?? {};
    final agents = data['agents'] as Map<String, dynamic>? ?? {};

    Pref.username = users['username'] ?? Pref.username;
    Pref.password = users['password'] ?? Pref.password;
    Pref.pro = users['permium'] ?? '';
    Pref.size = users['size'] ?? '';
    Pref.total = users['total'] ?? '';
    Pref.full = users['full'] ?? '';
    Pref.multi = users['multi'] ?? '';
    Pref.status = users['active'] ?? '';
    Pref.devicesStatus = data['status'] ?? '';
    Pref.date = users['date'] ?? '';
    Pref.adate = users['adate'] ?? '';
    Pref.version = app['version'] ?? '';
    Pref.versionWin = app['versionWin'] ?? '';
    Pref.link = app['link'] ?? '';
    Pref.linkWin = app['linkWin'] ?? '';
    Pref.sure = app['appstatus'] ?? '';
    Pref.not_name = notif['title'] ?? '';
    Pref.not_text = notif['text'] ?? '';
    Pref.not_link = notif['link'] ?? '';
    Pref.not_status = notif['status'] ?? '';
    Pref.not_users = notif['users'] ?? '';
    Pref.app_name = agents['app'] ?? Pref.app_name;
    Pref.app_logo = agents['logo'] ?? Pref.app_logo;
    Pref.days = data['credit']?.toString() ?? '0';
    Pref.count = data['news']?.toString() ?? '0';
    Pref.isLogin = true;
    Pref.isGB = isGB;
    Pref.isDay = isDay;
    Pref.isActive = isActive;

    final devList = data['devices'];
    if (devList is Map && devList['devices'] is List) {
      Pref.devices = (devList['devices'] as List)
          .map<Map<String, dynamic>>((e) => Map<String, dynamic>.from(e as Map))
          .toList();
    } else {
      Pref.devices = [];
    }

    // درخواست‌های در انتظار (برای لیدر)
    final reqList = data['requests'];
    Pref.deviceRequests = (reqList is List)
        ? reqList
            .map<Map<String, dynamic>>((e) => Map<String, dynamic>.from(e as Map))
            .toList()
        : [];
  }

  Future<List<VPNClass>> getAServers() async {
    final request = http.Request('POST', Uri.parse(urlPost));
    request.headers['Content-Type'] = 'application/x-www-form-urlencoded';
    request.bodyFields = {
      ...ApiAuth.sign('getAServers'),
      'username': Pref.username,
      'password': Pref.password,
      'deviceIMEI': Pref.device,
      'method': 'getAServers',
    };

    final response = await request.send();
    if (response.statusCode != 200) return [];

    final res = await response.stream.bytesToString();
    final data = json.decode(res) as Map<String, dynamic>;
    if (data['result'] != 'success') return [];

    final conf = data['confs'] as List? ?? [];
    final tempList = <VPNClass>[];

    for (final s in conf) {
      if (s['status'] == 'true' && s['permium'] == Pref.pro) {
        tempList.add(VPNClass(
          serverId: s['id'],
          nameFa: s['name_fa'],
          nameEn: s['name_en'],
          src: cryptoService.decryptConfig(s['src']) ?? '',
          locationsFa: s['locations_fa'],
          locationsEn: s['locations_en'],
          titleFa: s['title_fa'],
          titleEn: s['title_en'],
          link: cryptoService.decryptConfig(s['link']) ?? '',
          permium: s['permium'],
          fragment: s['fragment'],
          json: s['json'],
          operator: s['oprator'],
          status: s['status'],
          date: '',
        ));
      }
    }

    storage.write('vpnServers', tempList);
    return tempList;
  }

  Future<void> syncServersWithHive() async {
    final onlineServers = await getAServers();
    final savedMap = Pref.vpnClassbox.toMap();
    final onlineIds = onlineServers.map((s) => s.serverId).toList();

    for (final entry in savedMap.entries) {
      if (!onlineIds.contains(entry.value.serverId)) {
        await Pref.vpnClassbox.delete(entry.key);
      }
    }
    for (final server in onlineServers) {
      if (!Pref.vpnClassbox.values.any((s) => s.serverId == server.serverId)) {
        await Pref.vpnClassbox.add(server);
      }
    }
  }

  void deleteAccount() {
    _showToast(LanguageConstants.login1.tr);
    Pref.isConnect = false;
    Pref.username = '';
    Pref.password = '';
    Pref.isLogin = false;
    V2rayManager.i.flutterV2ray.stopV2Ray();
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const LoginPage()),
    );
  }

  Future<void> _handleDeviceNotFound() async {
    if (_deviceHealHandled) return;
    _deviceHealHandled = true;

    // Self-heal / مهاجرت یک‌باره: چون طرح شناسه‌ی دستگاه از Build.ID به
    // ANDROID_ID تغییر کرده، ردیف دستگاه قدیمی دیگر تطبیق نمی‌خورد و سرور
    // device_not_found می‌دهد. قبل از خروج اجباری، تلاش می‌کنیم با یوزر/پس
    // ذخیره‌شده دستگاه را بی‌صدا دوباره ثبت کنیم تا کاربر چیزی نبیند.
    // فقط یک‌بار در طول عمر نصب اجرا می‌شود؛ بعد از آن device_not_found به
    // رفتار عادی (خروج) برمی‌گردد تا کیکِ دستگاه توسط ادمین بی‌اثر نشود.
    final u = Pref.username;
    final p = Pref.password;
    if (!Pref.deviceMigrated &&
        u.isNotEmpty &&
        p.isNotEmpty &&
        await _tryReRegisterDevice(u, p)) {
      Pref.deviceMigrated = true;
      final r = await _getData();
      if (r == ResultStatus.success ||
          r == ResultStatus.endGB ||
          r == ResultStatus.endDay ||
          r == ResultStatus.suspended) {
        _safeGoHome();
        return;
      }
    }

    _showToast(LanguageConstants.login0.tr);
    Pref.isConnect = false;
    Pref.username = '';
    Pref.password = '';
    Pref.isLogin = false;
    V2rayManager.i.flutterV2ray.stopV2Ray();
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const LoginPage()),
    );
  }

  /// تلاش برای ثبت مجدد این دستگاه با متد login و یوزر/پس ذخیره‌شده.
  /// موفقیت یعنی دستگاه با شناسه‌ی جدید در سرور ثبت شد (success/EndGB/EndDay).
  Future<bool> _tryReRegisterDevice(String username, String password) async {
    try {
      final request = http.Request('POST', Uri.parse(urlPost))
        ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
        ..bodyFields = {
          ...ApiAuth.sign('login'),
          'username': username,
          'password': password,
          'deviceIMEI': Pref.device,
          'deviceModel': Pref.model,
          'deviceToken': Pref.token,
          'method': 'login',
        };

      final response = await request.send().timeout(const Duration(seconds: 10));
      if (response.statusCode != 200) return false;

      final data = json.decode(await response.stream.bytesToString());
      final result = (data['result'] ?? '').toString();

      // success / EndGB / EndDay ⇒ دستگاه ثبت شد و اکانت معتبر است؛ details
      // بعدی داده‌ی کامل را برمی‌گرداند. duplicate/current/notActive ⇒ ناموفق.
      return result == 'success' || result == 'EndGB' || result == 'EndDay';
    } catch (_) {
      return false;
    }
  }

  void _showToast(String message) {
    if (!context.mounted) return;
    CherryToast.info(
      title: Text(
        message,
        style: const TextStyle(color: Colors.black, fontFamily: 'sans'),
        textDirection: languageController.selectedLanguage == 'fa_IR'
            ? TextDirection.ltr
            : TextDirection.rtl,
      ),
    ).show(context);
  }

  // ─── UI ───────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF050510),
      body: Stack(
        children: [
          // 1. Animated deep background
          _buildAnimatedBackground(),

          // 2. Orbiting particle rings
          _buildParticleRings(),

          // 3. Center content
          Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                _buildLogo(),
                const SizedBox(height: 28),
                _buildAppName(),
                const SizedBox(height: 12),
                _buildSubtitle(),
              ],
            ),
          ),

          // 4. Bottom status area
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            child: _buildBottomArea(),
          ),
        ],
      ),
    );
  }

  Widget _buildAnimatedBackground() {
    return AnimatedBuilder(
      animation: _bgController,
      builder: (_, __) {
        final t = _bgController.value;
        return Container(
          decoration: BoxDecoration(
            gradient: RadialGradient(
              center: Alignment(
                math.sin(t * math.pi * 2) * 0.3,
                math.cos(t * math.pi * 2) * 0.3,
              ),
              radius: 1.4,
              colors: const [
                Color(0xFF0D1B4B),
                Color(0xFF050510),
                Color(0xFF000000),
              ],
              stops: const [0.0, 0.55, 1.0],
            ),
          ),
        );
      },
    );
  }

  Widget _buildParticleRings() {
    return AnimatedBuilder(
      animation: _particleController,
      builder: (context, _) {
        final size = MediaQuery.of(context).size;
        return CustomPaint(
          size: Size(size.width, size.height),
          painter: _ParticleRingPainter(
            progress: _particleController.value,
            pulse: _pulseAnim.value,
          ),
        );
      },
    );
  }

  Widget _buildLogo() {
    return AnimatedBuilder(
      animation: Listenable.merge([_logoController, _pulseController]),
      builder: (_, __) {
        return Opacity(
          opacity: _logoOpacity.value,
          child: Transform.scale(
            scale: _logoScale.value,
            child: Stack(
              alignment: Alignment.center,
              children: [
                // Outer glow ring
                Container(
                  width: 160 + (_pulseAnim.value * 20),
                  height: 160 + (_pulseAnim.value * 20),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: RadialGradient(
                      colors: [
                        const Color(0xFF4F8EFF).withOpacity(0.0),
                        const Color(0xFF4F8EFF)
                            .withOpacity(0.15 * _pulseAnim.value),
                        const Color(0xFF4F8EFF).withOpacity(0.0),
                      ],
                    ),
                  ),
                ),

                // Inner glow
                Container(
                  width: 130,
                  height: 130,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF4F8EFF)
                            .withOpacity(0.3 * _pulseAnim.value),
                        blurRadius: 40,
                        spreadRadius: 10,
                      ),
                    ],
                  ),
                ),

                // Logo container with glassmorphism
                Container(
                  width: 120,
                  height: 120,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white.withOpacity(0.07),
                    border: Border.all(
                      color: Colors.white.withOpacity(0.15),
                      width: 1.5,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF4F8EFF).withOpacity(0.2),
                        blurRadius: 20,
                        spreadRadius: 2,
                      ),
                    ],
                  ),
                  child: ClipOval(
                    child: Pref.app_logo.isNotEmpty
                        ? CachedNetworkImage(
                            imageUrl: Pref.app_logo,
                            fit: BoxFit.cover,
                            placeholder: (_, __) => const _LogoPlaceholder(),
                            errorWidget: (_, __, ___) => Image.asset(
                              'assets/images/logo.png',
                              fit: BoxFit.cover,
                            ),
                          )
                        : const _LogoPlaceholder(),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildAppName() {
    return AnimatedBuilder(
      animation: _textController,
      builder: (_, __) {
        return Opacity(
          opacity: _textOpacity.value,
          child: Transform.translate(
            offset: Offset(0, _textSlide.value),
            child: Text(
              Pref.app_name,
              style: const TextStyle(
                fontSize: 26,
                fontWeight: FontWeight.w700,
                color: Colors.white,
                fontFamily: 'sans',
                letterSpacing: 2.0,
              ),
            ),
          ),
        );
      },
    );
  }

  Widget _buildSubtitle() {
    return AnimatedBuilder(
      animation: _textController,
      builder: (_, __) {
        return Opacity(
          opacity: _textOpacity.value * 0.6,
          child: Transform.translate(
            offset: Offset(0, _textSlide.value * 1.5),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 30,
                  height: 1,
                  color: const Color(0xFF4F8EFF).withOpacity(0.6),
                ),
                const SizedBox(width: 10),
                Text(
                  'SECURE · FAST · RELIABLE',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w500,
                    color: const Color(0xFF4F8EFF).withOpacity(0.8),
                    letterSpacing: 3.0,
                    fontFamily: 'sans',
                  ),
                ),
                const SizedBox(width: 10),
                Container(
                  width: 30,
                  height: 1,
                  color: const Color(0xFF4F8EFF).withOpacity(0.6),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildBottomArea() {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 400),
      curve: Curves.easeInOut,
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).padding.bottom + 40,
        top: 30,
        left: 32,
        right: 32,
      ),
      child: _isError ? _buildErrorState() : _buildLoadingState(),
    );
  }

  Widget _buildLoadingState() {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        // Custom loading dots
        _AnimatedDots(),
        const SizedBox(height: 16),
        Text(
          LanguageConstants.loading.tr,
          style: TextStyle(
            color: Colors.white.withOpacity(0.5),
            fontSize: 13,
            letterSpacing: 1.5,
            fontFamily: 'sans',
          ),
        ),
      ],
    );
  }

  Widget _buildErrorState() {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        // Error icon
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Colors.red.withOpacity(0.1),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.red.withOpacity(0.3)),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.wifi_off_rounded,
                  color: Colors.redAccent, size: 18),
              const SizedBox(width: 8),
              Text(
                LanguageConstants.internetConnectionError.tr,
                style: const TextStyle(
                  color: Colors.redAccent,
                  fontSize: 13,
                  fontFamily: 'sans',
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),
        Row(
          children: [
            // Retry button
            Expanded(
              child: _GlassButton(
                label: LanguageConstants.tryAgain.tr,
                icon: Icons.refresh_rounded,
                color: const Color(0xFF4F8EFF),
                onTap: () {
                  V2rayManager.i.flutterV2ray.stopV2Ray();
                  setState(() {
                    _isError = false;
                    _status = ResultStatus.initial;
                    _navigated = false;
                    _deviceHealHandled = false;
                  });
                  _checkRootStatus();
                },
              ),
            ),
            const SizedBox(width: 12),
            // Offline button
            Expanded(
              child: _GlassButton(
                label: LanguageConstants.loginOffline.tr,
                icon: Icons.offline_bolt_rounded,
                color: const Color(0xFF00C896),
                onTap: _safeGoHome,
              ),
            ),
          ],
        ),
      ],
    );
  }
}

// ─── Custom Painter: Particle Rings ─────────────────────────────────────────
class _ParticleRingPainter extends CustomPainter {
  final double progress;
  final double pulse;

  _ParticleRingPainter({required this.progress, required this.pulse});

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final paint = Paint()..style = PaintingStyle.fill;

    // Ring 1 - outer slow
    _drawRing(canvas, center, paint, 200, 12, 0.06, progress, 0xFF4F8EFF);
    // Ring 2 - inner fast
    _drawRing(canvas, center, paint, 140, 8, 0.10, -progress * 1.3, 0xFF00C8FF);
    // Ring 3 - tiny close
    _drawRing(canvas, center, paint, 88, 6, 0.15, progress * 0.7, 0xFF8B5FFF);
  }

  void _drawRing(Canvas canvas, Offset center, Paint paint, double radius,
      int count, double opacity, double angleOffset, int colorHex) {
    for (int i = 0; i < count; i++) {
      final angle = (i / count) * math.pi * 2 + angleOffset * math.pi * 2;
      final x = center.dx + radius * math.cos(angle);
      final y = center.dy + radius * math.sin(angle);
      final dotSize = (i % 3 == 0) ? 2.5 : 1.5;

      paint.color = Color(colorHex).withOpacity(opacity * pulse);
      canvas.drawCircle(Offset(x, y), dotSize, paint);
    }
  }

  @override
  bool shouldRepaint(_ParticleRingPainter old) =>
      old.progress != progress || old.pulse != pulse;
}

// ─── Logo Placeholder ────────────────────────────────────────────────────────
class _LogoPlaceholder extends StatelessWidget {
  const _LogoPlaceholder();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: const Color(0xFF0D1B4B),
      child: const Center(
        child: SizedBox(
          width: 28,
          height: 28,
          child: CircularProgressIndicator(
            strokeWidth: 2,
            color: Color(0xFF4F8EFF),
          ),
        ),
      ),
    );
  }
}

// ─── Animated Loading Dots ───────────────────────────────────────────────────
class _AnimatedDots extends StatefulWidget {
  @override
  State<_AnimatedDots> createState() => _AnimatedDotsState();
}

class _AnimatedDotsState extends State<_AnimatedDots>
    with TickerProviderStateMixin {
  final List<AnimationController> _controllers = [];

  @override
  void initState() {
    super.initState();
    for (int i = 0; i < 3; i++) {
      final ctrl = AnimationController(
        vsync: this,
        duration: const Duration(milliseconds: 600),
      );
      Future.delayed(Duration(milliseconds: i * 200), () {
        if (mounted) ctrl.repeat(reverse: true);
      });
      _controllers.add(ctrl);
    }
  }

  @override
  void dispose() {
    for (final c in _controllers) {
      c.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: List.generate(3, (i) {
        return AnimatedBuilder(
          animation: _controllers[i],
          builder: (_, __) {
            return Container(
              margin: const EdgeInsets.symmetric(horizontal: 4),
              width: 6,
              height: 6 + (_controllers[i].value * 8),
              decoration: BoxDecoration(
                color: Color.lerp(
                  const Color(0xFF4F8EFF).withOpacity(0.3),
                  const Color(0xFF4F8EFF),
                  _controllers[i].value,
                ),
                borderRadius: BorderRadius.circular(4),
              ),
            );
          },
        );
      }),
    );
  }
}

// ─── Glass Button ─────────────────────────────────────────────────────────────
class _GlassButton extends StatefulWidget {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _GlassButton({
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  @override
  State<_GlassButton> createState() => _GlassButtonState();
}

class _GlassButtonState extends State<_GlassButton> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: (_) => setState(() => _pressed = true),
      onTapUp: (_) {
        setState(() => _pressed = false);
        widget.onTap();
      },
      onTapCancel: () => setState(() => _pressed = false),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 120),
        padding: const EdgeInsets.symmetric(vertical: 14),
        decoration: BoxDecoration(
          color: _pressed
              ? widget.color.withOpacity(0.25)
              : widget.color.withOpacity(0.12),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: widget.color.withOpacity(_pressed ? 0.7 : 0.4),
            width: 1.2,
          ),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(widget.icon, color: widget.color, size: 16),
            const SizedBox(width: 6),
            Text(
              widget.label,
              style: TextStyle(
                color: widget.color,
                fontWeight: FontWeight.w600,
                fontSize: 13,
                fontFamily: 'sans',
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Enums ────────────────────────────────────────────────────────────────────
enum ResultStatus {
  initial,
  success,
  endGB,
  endDay,
  suspended,
  logout,
  current,
  error,
  device,
  waiting,
  blocked,
}
