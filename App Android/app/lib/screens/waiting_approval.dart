import 'dart:async';
import 'dart:convert';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;

import '../Login.dart';
import '../controller/language/language_controller.dart';
import '../helpers/api_auth.dart';
import '../helpers/pref.dart';
import 'splash.dart';

// ─── Theme ──────────────────────────────────────────────────────────────────
const _kDeepBg = Color(0xFF050510);
const _kCard = Color(0xFF0D1B3E);
const _kAccent = Color(0xFF4F8EFF);
const _kGreen = Color(0xFF00E0C6);
const _kRed = Color(0xFFFF5F5F);
const _kAmber = Color(0xFFFFB020);
const _kWhite70 = Color(0xB3FFFFFF);

/// صفحه‌ی انتظار برای دستگاهی که از اکانت کیک شده و منتظر تأیید لیدر است.
class WaitingApprovalScreen extends StatefulWidget {
  final bool blocked;
  const WaitingApprovalScreen({super.key, this.blocked = false});

  @override
  State<WaitingApprovalScreen> createState() => _WaitingApprovalScreenState();
}

class _WaitingApprovalScreenState extends State<WaitingApprovalScreen>
    with TickerProviderStateMixin {
  final LanguageController _lang = Get.find<LanguageController>();

  late AnimationController _bgCtrl;
  late AnimationController _pulseCtrl;
  late AnimationController _orbitCtrl;

  Timer? _poll;
  bool _blocked = false;
  bool _busyUrgent = false;
  bool _navigating = false;
  int _rejectCount = 0;

  @override
  void initState() {
    super.initState();
    _blocked = widget.blocked;
    SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
      systemNavigationBarColor: _kDeepBg,
    ));

    _bgCtrl =
        AnimationController(vsync: this, duration: const Duration(seconds: 16))
          ..repeat(reverse: true);
    _pulseCtrl = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 1500))
      ..repeat(reverse: true);
    _orbitCtrl =
        AnimationController(vsync: this, duration: const Duration(seconds: 6))
          ..repeat();

    if (!_blocked) {
      _checkStatus();
      _poll = Timer.periodic(const Duration(seconds: 6), (_) => _checkStatus());
    }
  }

  @override
  void dispose() {
    _poll?.cancel();
    _bgCtrl.dispose();
    _pulseCtrl.dispose();
    _orbitCtrl.dispose();
    super.dispose();
  }

  // ─── Networking ───────────────────────────────────────────────────────────
  Future<void> _checkStatus() async {
    if (_navigating || !mounted) return;
    try {
      final request = http.Request('POST', Uri.parse(urlPost))
        ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
        ..bodyFields = {
          ...ApiAuth.sign('details'),
          'method': 'details',
          'deviceIMEI': Pref.device,
          'username': Pref.username,
          'password': Pref.password,
        };
      final response =
          await request.send().timeout(const Duration(seconds: 10));
      final data = json.decode(await response.stream.bytesToString())
          as Map<String, dynamic>;
      final result = (data['result'] ?? '').toString();

      if (!mounted) return;

      if (result == 'approved') {
        // لیدر تأیید کرد ⇒ دستگاه را ثبت کن و به اپ برگرد
        final ok = await _registerAfterApproval();
        if (ok) _goSplash();
      } else if (result == 'success' ||
          result == 'EndGB' ||
          result == 'EndDay' ||
          result == 'suspended') {
        _goSplash();
      } else if (result == 'blocked') {
        setState(() => _blocked = true);
        _poll?.cancel();
      } else if (result == 'waiting') {
        final rc = int.tryParse('${data['reject_count'] ?? 0}') ?? 0;
        if (rc != _rejectCount) setState(() => _rejectCount = rc);
      }
    } catch (_) {
      // خطای شبکه ⇒ در دور بعدی دوباره تلاش می‌شود
    }
  }

  Future<bool> _registerAfterApproval() async {
    try {
      final request = http.Request('POST', Uri.parse(urlPost))
        ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
        ..bodyFields = {
          ...ApiAuth.sign('login'),
          'username': Pref.username,
          'password': Pref.password,
          'deviceIMEI': Pref.device,
          'deviceModel': Pref.model,
          'deviceToken': Pref.token,
          'method': 'login',
        };
      final response =
          await request.send().timeout(const Duration(seconds: 10));
      final data = json.decode(await response.stream.bytesToString())
          as Map<String, dynamic>;
      final r = (data['result'] ?? '').toString();
      return r == 'success' || r == 'EndGB' || r == 'EndDay';
    } catch (_) {
      return false;
    }
  }

  Future<void> _sendUrgent() async {
    if (_busyUrgent) return;

    setState(() => _busyUrgent = true);

    try {
      final request = http.Request('POST', Uri.parse(urlPost))
        ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
        ..bodyFields = {
          ...ApiAuth.sign('requestRejoin'),
          'method': 'requestRejoin',
          'username': Pref.username,
          'password': Pref.password,
          'deviceIMEI': Pref.device,
          'deviceModel': Pref.model,
          'deviceToken': Pref.token,
        };

      final response =
      await request.send().timeout(const Duration(seconds: 15));

      final body = await response.stream.bytesToString();

      // اگر سرور پاسخ HTTP معتبر داده باشد، درخواست ارسال شده است.
      if (response.statusCode >= 200 && response.statusCode < 300) {
        try {
          final data = json.decode(body) as Map<String, dynamic>;
          final r = (data['result'] ?? '').toString();

          if (!mounted) return;

          if (r == 'blocked') {
            setState(() => _blocked = true);
            _poll?.cancel();
            return;
          }

          // هر پاسخ معتبر از سرور که blocked نباشد
          // یعنی درخواست به سرور رسیده است.
          _toast(_t('urgentSent'));
        } catch (_) {
          // درخواست به سرور رسیده ولی پاسخ JSON قابل پردازش نبوده.
          // چون خود درخواست ارسال شده، پیام موفقیت نشان داده می‌شود.
          if (mounted) {
            _toast(_t('urgentSent'));
          }
        }
      } else {
        // فقط وقتی HTTP واقعاً ناموفق باشد، خطا نشان بده.
        if (mounted) {
          _toast(_t('urgentFailed'));
        }
      }
    } on TimeoutException {
      // اگر درخواست به سرور ارسال شده ولی پاسخ دیر برسد،
      // دیگر آن را خطای قطعی در نظر نمی‌گیریم.
      if (mounted) {
        _toast(_t('urgentSent'));
      }
    } catch (_) {
      if (mounted) {
        _toast(_t('urgentFailed'));
      }
    } finally {
      if (mounted) {
        setState(() => _busyUrgent = false);
      }
    }
  }

  void _exitAccount() {
    _poll?.cancel();
    Pref.isConnect = false;
    Pref.username = '';
    Pref.password = '';
    Pref.isLogin = false;
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const LoginPage()),
    );
  }

  void _goSplash() {
    if (_navigating) return;
    _navigating = true;
    _poll?.cancel();
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const SplashScreen()),
    );
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
        'waitTitle': 'در انتظار تأیید',
        'waitMsg':
            'این دستگاه از اکانت خارج شده است. برای ورود مجدد باید یکی از دستگاه‌های فعال درخواست شما را تأیید کند.',
        'blockedTitle': 'دستگاه مسدود شد',
        'blockedMsg':
            'درخواست شما ۳ بار رد شد و این دستگاه به‌طور دائم از این اکانت مسدود شده است.',
        'urgent': 'درخواست فوری',
        'exit': 'خروج از حساب',
        'urgentSent': 'درخواست شما برای دستگاه‌های فعال ارسال شد',
        'urgentFailed': 'ارسال درخواست ناموفق بود',
        'rejected': 'تعداد رد: ',
      },
      'fa_IR': {
        'waitTitle': 'Waiting for approval',
        'waitMsg':
            'This device was removed from the account. To sign in again, one of the active devices must approve your request.',
        'blockedTitle': 'Device blocked',
        'blockedMsg':
            'Your request was rejected 3 times and this device is permanently blocked from this account.',
        'urgent': 'Urgent request',
        'exit': 'Sign out',
        'urgentSent': 'Your request was sent to the active devices',
        'urgentFailed': 'Failed to send request',
        'rejected': 'Rejections: ',
      },
    };
    final lang = _lang.selectedLanguage == 'en_US' ? 'en_US' : 'fa_IR';
    return map[lang]?[key] ?? map['fa_IR']![key]!;
  }

  // ─── BUILD ────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kDeepBg,
      body: Stack(children: [
        _buildBg(),
        SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 26),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Spacer(),
                _buildOrb(),
                const SizedBox(height: 40),
                Text(
                  _blocked ? _t('blockedTitle') : _t('waitTitle'),
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w800,
                    color: _blocked ? _kRed : Colors.white,
                    letterSpacing: 0.3,
                  ),
                ),
                const SizedBox(height: 14),
                Text(
                  _blocked ? _t('blockedMsg') : _t('waitMsg'),
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                      fontSize: 13.5, height: 1.7, color: _kWhite70),
                ),
                if (!_blocked && _rejectCount > 0) ...[
                  const SizedBox(height: 14),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 7),
                    decoration: BoxDecoration(
                      color: _kAmber.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: _kAmber.withOpacity(0.35)),
                    ),
                    child: Text('${_t('rejected')}$_rejectCount / 3',
                        style: const TextStyle(
                            color: _kAmber,
                            fontSize: 12,
                            fontWeight: FontWeight.w700)),
                  ),
                ],
                const Spacer(),
                if (!_blocked) _buildUrgentButton(),
                if (!_blocked) const SizedBox(height: 12),
                _buildExitButton(),
                const SizedBox(height: 30),
              ],
            ),
          ),
        ),
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
                    math.sin(t * math.pi * 2) * 0.3, -0.4 + t * 0.2),
                radius: 1.5,
                colors: [
                  (_blocked ? _kRed : _kAccent).withOpacity(0.18),
                  const Color(0xFF050510),
                  Colors.black,
                ],
                stops: const [0.0, 0.5, 1.0],
              ),
            ),
          );
        },
      );

  Widget _buildOrb() {
    final color = _blocked ? _kRed : _kAccent;
    return SizedBox(
      width: 160,
      height: 160,
      child: AnimatedBuilder(
        animation: Listenable.merge([_pulseCtrl, _orbitCtrl]),
        builder: (_, __) {
          final pulse = 0.5 + 0.5 * _pulseCtrl.value;
          return Stack(alignment: Alignment.center, children: [
            // pulsing halo
            Container(
              width: 120 + 30 * pulse,
              height: 120 + 30 * pulse,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: color.withOpacity(0.06 + 0.05 * pulse),
              ),
            ),
            Container(
              width: 96,
              height: 96,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: color.withOpacity(0.10),
                border: Border.all(color: color.withOpacity(0.4), width: 1.4),
              ),
            ),
            // orbiting dot (only while waiting)
            if (!_blocked)
              Transform.rotate(
                angle: _orbitCtrl.value * 2 * math.pi,
                child: Transform.translate(
                  offset: const Offset(0, -58),
                  child: Container(
                    width: 12,
                    height: 12,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: _kGreen,
                      boxShadow: [
                        BoxShadow(
                            color: _kGreen.withOpacity(0.6),
                            blurRadius: 10,
                            spreadRadius: 1)
                      ],
                    ),
                  ),
                ),
              ),
            Icon(
              _blocked ? Icons.block_rounded : Icons.hourglass_top_rounded,
              color: color,
              size: 40,
            ),
          ]);
        },
      ),
    );
  }

  Widget _buildUrgentButton() {
    return SizedBox(
      width: double.infinity,
      height: 54,
      child: ElevatedButton(
        onPressed: _busyUrgent ? null : _sendUrgent,
        style: ElevatedButton.styleFrom(
          backgroundColor: _kAccent,
          disabledBackgroundColor: _kAccent.withOpacity(0.4),
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          elevation: 0,
        ),
        child: _busyUrgent
            ? const SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(
                    strokeWidth: 2.4, color: Colors.white),
              )
            : Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                const Icon(Icons.notifications_active_rounded,
                    color: Colors.white, size: 19),
                const SizedBox(width: 8),
                Text(_t('urgent'),
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 15,
                        fontWeight: FontWeight.w700)),
              ]),
      ),
    );
  }

  Widget _buildExitButton() {
    return SizedBox(
      width: double.infinity,
      height: 54,
      child: OutlinedButton(
        onPressed: _exitAccount,
        style: OutlinedButton.styleFrom(
          side: BorderSide(color: _kRed.withOpacity(0.5), width: 1.3),
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        ),
        child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
          const Icon(Icons.logout_rounded, color: _kRed, size: 19),
          const SizedBox(width: 8),
          Text(_t('exit'),
              style: const TextStyle(
                  color: _kRed, fontSize: 15, fontWeight: FontWeight.w700)),
        ]),
      ),
    );
  }
}
