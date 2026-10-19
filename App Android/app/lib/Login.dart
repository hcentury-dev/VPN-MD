import 'dart:async' show TimeoutException;
import 'dart:convert';
import 'dart:developer';
import 'dart:io';
import 'dart:ui';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:cherry_toast/cherry_toast.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;

import '../controller/language/language_controller.dart';
import '../helpers/api_auth.dart';
import '../helpers/pref.dart';
import 'controller/language/language_config.dart';
import 'screens/custon_language_sheet.dart';
import 'screens/splash.dart';
import 'screens/waiting_approval.dart';

// ─── رنگ‌های تم Aron Connector (پالت ملایم/دارک) ────────────────────────────
const kBg = Color(0xff1D1B26);
const kBgDark = Color(0xff181621);
const kBgLight = Color(0xff221F2D);
const kCard = Color(0xff262432); // کارت داخلی دایره لوگو
const kCardSoft = Color(0xff2E2C3A); // کارت‌های شیشه‌ای
const kGold = Color(0xFF0E2256); // طلایی ملایم (به جای زرد روشن)
const kGoldDim = Color(0xFF0D1B3E); // طلایی متوسط
const kGoldDeep = Color(0xFF050510); // طلایی تیره
const kAccent = Color(0xff3F3354); // accent بنفش‌-خاکستری
const _kDeepBg = Color(0xFF050510);
const _kCard = Color(0xFF0D1B3E);
// ═════════════════════════════════════════════════════════════════════════════
class AronBackground extends StatelessWidget {
  final Widget child;

  const AronBackground({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      color: kBg,
      child: Stack(
        children: [
          // گرادینت پایه (تیره‌تر در گوشه‌ها)
          const Positioned.fill(
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [kBgLight, kBg, kBgDark],
                  stops: [0.0, 0.5, 1.0],
                ),
              ),
            ),
          ),

          // لکه‌های نوری محو (برای دیده شدن افکت شیشه‌ای)
          Positioned(
            top: -120,
            right: -80,
            child: _Blob(color: kGold.withOpacity(0.10), size: 260),
          ),
          Positioned(
            top: 240,
            left: -100,
            child: _Blob(color: kAccent.withOpacity(0.35), size: 220),
          ),
          Positioned(
            bottom: -120,
            right: -60,
            child: _Blob(color: kGold.withOpacity(0.06), size: 240),
          ),

          // محتوای صفحه
          child,
        ],
      ),
    );
  }
}

class _Blob extends StatelessWidget {
  final Color color;
  final double size;

  const _Blob({required this.color, required this.size});

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: color,
          boxShadow: [
            BoxShadow(color: color, blurRadius: 100, spreadRadius: 50),
          ],
        ),
      ),
    );
  }
}

// ═════════════════════════════════════════════════════════════════════════════
//  صفحه ورود
// ═════════════════════════════════════════════════════════════════════════════
class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage>
    with SingleTickerProviderStateMixin {
  final TextEditingController controllerUsername = TextEditingController();
  final TextEditingController controllerPassword = TextEditingController();
  final LanguageController _lang = Get.find<LanguageController>();

  bool _obscurePassword = true;
  bool _isLoggingIn = false;

  late AnimationController _formCtrl;

  @override
  void initState() {
    super.initState();

    SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
      systemNavigationBarColor: kBg,
    ));

    _formCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );

    _checkRootStatus();

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      await _setting();
      if (urlPost
          .trim()
          .isEmpty) {
        await Future.delayed(const Duration(seconds: 2));
        await _setting();
      }
      checkFirstOpenApp();
      _formCtrl.forward();
    });
  }

  @override
  void dispose() {
    _formCtrl.dispose();
    controllerUsername.dispose();
    controllerPassword.dispose();
    super.dispose();
  }

  // ─── اولین اجرا ──────────────────────────────────────────────────────────
  void checkFirstOpenApp() {
    if (storage.read('firstCheck') == null) {
      Get.bottomSheet(const SizedBox(child: LanguageSheet()));
    }
  }

  // ─── بررسی روت ───────────────────────────────────────────────────────────
  Future<void> _checkRootStatus() async {
    final isRooted = await _isDeviceRooted();
    if (isRooted) {
      await showDialog(
        context: context,
        builder: (_) =>
            AlertDialog(
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
    Pref.isConnect = false;
    Pref.username = "";
    Pref.password = "";
    Pref.isLogin = false;
  }

  Future<bool> _isDeviceRooted() async {
    if (!Platform.isAndroid) return false;
    final androidInfo = await DeviceInfoPlugin().androidInfo;
    const paths = [
      '/system/app/Superuser.apk',
      '/system/xbin/su',
      '/system/bin/su',
      '/system/bin/.ext/.su',
      '/system/sd/xbin/su',
      '/sbin/su',
      '/system/etc/init.d/99SuperSUDaemon',
    ];
    for (final p in paths) {
      if (await File(p).exists()) return true;
    }
    return androidInfo.tags.contains('test-keys');
  }

  // ─── BUILD ───────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBg,
      body: AronBackground(
        child: Stack(
          children: [
            // محتوای اصلی
            SafeArea(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 25),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    const SizedBox(height: 120),
                    _buildLogo(),
                    const SizedBox(height: 30),
                    Text(
                      LanguageConstants.loginTitle.tr,
                      style: TextStyle(
                        color: Colors.white.withOpacity(0.3),
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 20),
                    _buildFormAnimated(),
                    const SizedBox(height: 30),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ─── لوگو ────────────────────────────────────────────────────────────────
  Widget _buildLogo() {
    return Center(
      child: _LogoWidget(logoUrl: Pref.app_logo),
    );
  }

  // ─── فرم انیمیشن‌دار ─────────────────────────────────────────────────────
  Widget _buildFormAnimated() {
    return AnimatedBuilder(
      animation: _formCtrl,
      builder: (_, child) {
        final t = CurvedAnimation(parent: _formCtrl, curve: Curves.easeOutCubic)
            .value;
        return Opacity(
          opacity: t,
          child: Transform.translate(
              offset: Offset(0, 40 * (1 - t)), child: child),
        );
      },
      child: _buildLoginForm(),
    );
  }

  // ─── فرم لاگین ───────────────────────────────────────────────────────────
  Widget _buildLoginForm() {
    return Column(
      children: [
        // کارت ۱: ورود با حساب
        _glassCard(
          child: Column(
            children: [
              _buildTextField(
                controller: controllerUsername,
                hintText: LanguageConstants.loginUsername.tr,
              ),
              const SizedBox(height: 10),
              _buildPasswordField(),
              const SizedBox(height: 18),
              _buildLoginButton(),
            ],
          ),
        ),

        const SizedBox(height: 22),
        _buildOrDivider(),
        const SizedBox(height: 22),

        // کارت ۲: ورود مهمان + خرید (نسخه مدیر)
        _glassCard(
          child: Column(
            children: [
              _buildGuestButton(),
            ],
          ),
        ),
      ],
    );
  }

  // ─── کارت شیشه‌ای ────────────────────────────────────────────────────────
  Widget _glassCard({required Widget child}) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(22),
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 14, sigmaY: 14),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(22),
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                Colors.white.withOpacity(0.06),
                Colors.white.withOpacity(0.02),
              ],
            ),
            border: Border.all(
              color: Colors.white.withOpacity(0.08),
              width: 1,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.20),
                blurRadius: 18,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: child,
        ),
      ),
    );
  }

  // ─── خط جداکننده با متن «یا» ─────────────────────────────────────────────
  Widget _buildOrDivider() {
    return Row(
      children: [
        Expanded(
          child: Container(
            height: 1,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  Colors.white.withOpacity(0),
                  Colors.white.withOpacity(0.18),
                ],
              ),
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14),
          child: Text(
            // اگه کلید 'or' توی LanguageConstants داری، می‌تونی به
            // LanguageConstants.or.tr تغییر بدی.
            'یا',
            style: TextStyle(
              color: Colors.white.withOpacity(0.45),
              fontSize: 13,
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
        Expanded(
          child: Container(
            height: 1,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  Colors.white.withOpacity(0.18),
                  Colors.white.withOpacity(0),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }

  // ─── فیلد متنی ───────────────────────────────────────────────────────────
  Widget _buildTextField({
    required TextEditingController controller,
    required String hintText,
  }) {
    return TextField(
      controller: controller,
      style: const TextStyle(color: Colors.white),
      textDirection: TextDirection.ltr,
      cursorColor: kGold,
      decoration: InputDecoration(
        hintText: hintText,
        hintStyle: TextStyle(color: Colors.white.withOpacity(0.22)),
        filled: true,
        fillColor: Colors.white.withOpacity(0.04),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: kGold.withOpacity(0.45), width: 1.2),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide:
          BorderSide(color: Colors.white.withOpacity(0.06), width: 1),
        ),
        contentPadding:
        const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      ),
    );
  }

  // ─── فیلد رمز عبور ───────────────────────────────────────────────────────
  Widget _buildPasswordField() {
    return TextField(
      controller: controllerPassword,
      obscureText: _obscurePassword,
      style: const TextStyle(color: Colors.white),
      textDirection: TextDirection.ltr,
      cursorColor: kGold,
      decoration: InputDecoration(
        hintText: LanguageConstants.loginPassword.tr,
        hintStyle: TextStyle(color: Colors.white.withOpacity(0.22)),
        filled: true,
        fillColor: Colors.white.withOpacity(0.04),
        suffixIcon: IconButton(
          icon: Icon(
            _obscurePassword ? Icons.visibility_off : Icons.visibility,
            color: Colors.white.withOpacity(0.5),
            size: 20,
          ),
          onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: kGold.withOpacity(0.45), width: 1.2),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide:
          BorderSide(color: Colors.white.withOpacity(0.06), width: 1),
        ),
        contentPadding:
        const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      ),
    );
  }

  // ─── دکمه ورود ───────────────────────────────────────────────────────────
  Widget _buildLoginButton() {
    return GestureDetector(
      onTap: _isLoggingIn ? null : _login,
      child: Container(
        height: 56,
        width: double.infinity,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: const LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [kGold, kGoldDeep],
          ),
          boxShadow: [
            BoxShadow(
              color: kGold.withOpacity(0.18),
              blurRadius: 16,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Center(
          child: _isLoggingIn
              ? const SizedBox(
            width: 22,
            height: 22,
            child: CircularProgressIndicator(
              strokeWidth: 2.5,
              color: Colors.white,
            ),
          )
              : Text(
            LanguageConstants.loginBtn.tr,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 16,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ),
    );
  }

  // ─── دکمه ورود مهمان ─────────────────────────────────────────────────────
  Widget _buildGuestButton() {
    return GestureDetector(
      onTap: _loginNew,
      child: Container(
        height: 56,
        width: double.infinity,
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.03),
          border: Border.all(color: kGold.withOpacity(0.55), width: 1.2),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Center(
          child: Text(
            LanguageConstants.loginFree.tr,
            style: TextStyle(
              color: Colors.white.withOpacity(0.92),
              fontSize: 16,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ),
    );
  }

  // ─── منطق لاگین ──────────────────────────────────────────────────────────
  Future<void> _setting() async {
    try {
      final base = urlPost.trim();
      if (base.isEmpty) return;
      final uri = Uri.tryParse(base);
      if (uri == null || uri.host.isEmpty) return;

      final request = http.Request('POST', uri)
        ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
        ..bodyFields = {...ApiAuth.sign('setting'), 'method': 'setting'};

      http.StreamedResponse response;
      try {
        response = await request.send().timeout(const Duration(seconds: 4));
      } on TimeoutException {
        return;
      }

      if (response.statusCode == 200) {
        final res = await response.stream.bytesToString();
        dynamic data;
        try {
          data = json.decode(res);
        } catch (_) {
          return;
        }

        if ((data['result'] ?? '').toString() == "true") {
          final settings =
          (data['settings'] is Map) ? data['settings'] as Map : {};
          final appName = (settings['app_name'] ?? '').toString();
          final appLogo = (settings['app_logo'] ?? '').toString();
          if (appName.isNotEmpty) Pref.app_name = appName;
          if (appLogo.isNotEmpty) Pref.app_logo = appLogo;
          if (mounted) setState(() {});
        }
      } else {
        _showErrorToast(LanguageConstants.login7.tr);
      }
    } catch (e, s) {
      log("_setting error: $e", stackTrace: s);
    }
  }

  Future<void> _login() async {
    setState(() => _isLoggingIn = true);
    try {
      final request = http.Request('POST', Uri.parse(urlPost))
        ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
        ..bodyFields = {
          ...ApiAuth.sign('login'),
          'username': controllerUsername.text,
          'password': controllerPassword.text,
          'deviceIMEI': Pref.device,
          'deviceModel': Pref.model,
          'deviceToken': Pref.token,
          'method': 'login',
        };

      final response = await request.send();
      if (response.statusCode != 200) {
        _showErrorToast(LanguageConstants.login7.tr);
        return;
      }

      final data = json.decode(await response.stream.bytesToString());

      switch (data['result']) {
        case 'success':
          _saveAndGo(data, isGB: true, isDay: true);
        case 'duplicate':
          _showInfoToast(LanguageConstants.login2.tr);
          return;
        case 'EndGB':
          _showSuccessToast(LanguageConstants.login3.tr);
          _saveAndGo(data, isGB: false, isDay: true);
        case 'EndDay':
          _showSuccessToast(LanguageConstants.login4.tr);
          _saveAndGo(data, isGB: true, isDay: false);
        case 'logout':
          deleteAccount();
        case 'notActive':
          _showErrorToast(LanguageConstants.login5.tr);
        case 'current':
          _showErrorToast(LanguageConstants.login6.tr);
        case 'waiting':
        case 'blocked':
          // دستگاه کیک‌شده: ذخیره‌ی اعتبارنامه و رفتن به صفحه‌ی انتظار/بلاک
          Pref.username = controllerUsername.text;
          Pref.password = controllerPassword.text;
          if (mounted) {
            Navigator.of(context).pushReplacement(MaterialPageRoute(
              builder: (_) => WaitingApprovalScreen(
                  blocked: data['result'] == 'blocked'),
            ));
          }
        default:
          _showErrorToast(LanguageConstants.login7.tr);
      }
    } catch (_) {
      _showErrorToast(LanguageConstants.login7.tr);
    } finally {
      if (mounted) setState(() => _isLoggingIn = false);
    }
  }

  Future<void> _loginNew() async {
    final request = http.Request('POST', Uri.parse(urlPost))
      ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
      ..bodyFields = {
        ...ApiAuth.sign('login_new'),
        'deviceIMEI': Pref.device,
        'deviceModel': Pref.model,
        'deviceToken': Pref.token,
        'method': 'login_new',
      };

    final response = await request.send();
    if (response.statusCode != 200) {
      _showErrorToast(LanguageConstants.login7.tr);
      return;
    }

    final data = json.decode(await response.stream.bytesToString());

    switch (data['result']) {
      case 'success':
      case 'success1':
      case 'success2':
        _showSuccessToast(
          data['result'] == 'success'
              ? LanguageConstants.login8.tr
              : LanguageConstants.login9.tr,
        );
        _saveAndGo(data, isGB: true, isDay: true);
      case 'EndGB':
        _showSuccessToast(LanguageConstants.login3.tr);
        _saveAndGo(data, isGB: false, isDay: true);
      case 'EndDay':
        _showSuccessToast(LanguageConstants.login4.tr);
        _saveAndGo(data, isGB: true, isDay: false);
      case 'off':
        _showWarningToast(LanguageConstants.login10.tr);
      default:
        _showErrorToast(LanguageConstants.login5.tr);
    }
  }

  void _saveUser(dynamic data, {required bool isGB, required bool isDay}) {
    Pref.username = data['users']['username'];
    Pref.password = data['users']['password'];
    Pref.isLogin = true;
    Pref.isGB = isGB;
    Pref.isDay = isDay;
  }

  void _saveAndGo(dynamic data, {required bool isGB, required bool isDay}) {
    _saveUser(data, isGB: isGB, isDay: isDay);
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const SplashScreen()),
    );
  }

  void deleteAccount() {
    _showWarningToast(LanguageConstants.login1.tr);
    Pref.username = "";
    Pref.password = "";
    Pref.isLogin = false;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const LoginPage()),
    );
  }

  // ─── Toast helpers ────────────────────────────────────────────────────────
  void _showErrorToast(String msg) =>
      CherryToast.error(title: Text(msg)).show(context);

  void _showSuccessToast(String msg) =>
      CherryToast.success(title: Text(msg)).show(context);

  void _showInfoToast(String msg) =>
      CherryToast.info(title: Text(msg)).show(context);

  void _showWarningToast(String msg) =>
      CherryToast.warning(title: Text(msg)).show(context);
}

// ═════════════════════════════════════════════════════════════════════════════
//  ویجت لوگو
// ═════════════════════════════════════════════════════════════════════════════
class _LogoWidget extends StatefulWidget {
  final String logoUrl;

  const _LogoWidget({required this.logoUrl});

  @override
  State<_LogoWidget> createState() => _LogoWidgetState();
}

class _LogoWidgetState extends State<_LogoWidget> {
  bool _useLocal = false;

  @override
  void initState() {
    super.initState();
    _checkImageLoad();
  }

  @override
  void didUpdateWidget(_LogoWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.logoUrl != widget.logoUrl) {
      setState(() => _useLocal = false);
      _checkImageLoad();
    }
  }

  Future<void> _checkImageLoad() async {
    if (widget.logoUrl.isEmpty || !widget.logoUrl.startsWith('http')) {
      if (mounted) setState(() => _useLocal = true);
      return;
    }
    try {
      final response = await http
          .get(Uri.parse(widget.logoUrl))
          .timeout(const Duration(seconds: 3));
      if (response.statusCode != 200 && mounted)
        setState(() => _useLocal = true);
    } catch (_) {
      if (mounted) setState(() => _useLocal = true);
    }
  }

  @override
  Widget build(BuildContext context) {
    // محتوای داخلی: عکس لوگو (لوکال یا شبکه)
    final Widget logoImage = _useLocal
        ? Image.asset(
      'assets/images/logo.png',
      fit: BoxFit.contain,
    )
        : CachedNetworkImage(
      imageUrl: widget.logoUrl,
      fit: BoxFit.contain,
      fadeInDuration: const Duration(milliseconds: 300),
      placeholder: (_, __) =>
      const Center(
        child: SizedBox(
          width: 28,
          height: 28,
          child: CircularProgressIndicator(
            strokeWidth: 2,
            color: Colors.white38,
          ),
        ),
      ),
      errorWidget: (_, __, ___) =>
          Image.asset(
            'assets/images/logo.png',
            fit: BoxFit.contain,
          ),
    );

    // قاب دایره‌ای با حلقه gradient طلایی
    return Container(
      width: 116,
      height: 116,
      padding: const EdgeInsets.all(3),
      // ضخامت حلقه
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            kGold.withOpacity(0.55),
            kGold.withOpacity(0.12),
          ],
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.35),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
          BoxShadow(
            color: kGold.withOpacity(0.12),
            blurRadius: 22,
            spreadRadius: 1,
          ),
        ],
      ),
      // دایره داخلی با پس‌زمینه تیره (اگه عکس بک‌گرند مشکی داشت
      // باز هم حلقه طلایی دور آن دیده میشه و کادر گم نمیشه)
      child: Container(
        decoration: const BoxDecoration(
          shape: BoxShape.circle,
          color: kCard,
        ),
        child: Padding(
          padding: const EdgeInsets.all(8),
          child: ClipOval(child: logoImage),
        ),
      ),
    );
  }
}
