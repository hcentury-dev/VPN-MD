import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import '../controller/language/language_controller.dart';

/// 🌐 NetworkMonitor – smart connection monitor (telegram-style)
class NetworkMonitor {
  static final NetworkMonitor _instance = NetworkMonitor._internal();
  factory NetworkMonitor() => _instance;
  NetworkMonitor._internal();

  Timer? _timer;
  bool _isChecking = false;
  OverlayEntry? _overlayEntry;
  ConnectionStateType _lastState = ConnectionStateType.unknown;
  bool _isShowing = false;

  void start(BuildContext context) {
    stop();
    _runCheck(context);

    // هر 3 ثانیه بررسی وضعیت اینترنت
    _timer = Timer.periodic(const Duration(seconds: 3), (_) {
      _runCheck(context);
    });
  }

  void stop() {
    _timer?.cancel();
    _timer = null;
    _hideToast();
  }

  Future<void> _runCheck(BuildContext context) async {
    if (_isChecking || !context.mounted) return;
    _isChecking = true;

    final lang = Get.find<LanguageController>();
    final isFa = lang.isFA;

    final hasInternet = await _hasInternet();

    final newState = hasInternet
        ? ConnectionStateType.connected
        : ConnectionStateType.disconnected;

    if (newState != _lastState) {
      _lastState = newState;
      _showAnimatedToast(context, newState, isFa);
    }

    _isChecking = false;
  }

  /// بررسی اتصال واقعی اینترنت
  Future<bool> _hasInternet() async {
    try {
      final response = await http
          .get(Uri.parse('https://www.google.com/generate_204'))
          .timeout(const Duration(seconds: 2));
      return response.statusCode == 204 || response.statusCode == 200;
    } catch (_) {
      try {
        final result = await InternetAddress.lookup('1.1.1.1')
            .timeout(const Duration(seconds: 2));
        return result.isNotEmpty && result[0].rawAddress.isNotEmpty;
      } catch (_) {
        return false;
      }
    }
  }

  /// انیمیشن toast بالا صفحه
  void _showAnimatedToast(BuildContext context, ConnectionStateType state, bool isFa) {
    _hideToast();
    if (!context.mounted || Overlay.maybeOf(context) == null) return;

    String message;
    Color color;
    bool isError = false;

    switch (state) {
      case ConnectionStateType.disconnected:
        message = isFa ? "❌ اینترنت قطع شد" : "❌ Internet disconnected";
        color = Colors.redAccent;
        isError = true;
        break;
      case ConnectionStateType.connected:
        message = isFa ? "✅ اینترنت متصل شد" : "✅ Internet connected";
        color = Colors.greenAccent;
        break;
      default:
        message = isFa
            ? "📡 وضعیت اتصال نامشخص"
            : "📡 Connection status uncertain";
        color = Colors.amberAccent;
    }

    _overlayEntry = _createOverlay(context, message, color, isError);
    Overlay.of(context).insert(_overlayEntry!);
    _isShowing = true;

    // حذف خودکار بعد از 3 ثانیه
    Future.delayed(const Duration(seconds: 3), () {
      _hideToast();
    });
  }

  OverlayEntry _createOverlay(BuildContext context, String message, Color color, bool shake) {
    final key = GlobalKey<ShakeWidgetState>();

    return OverlayEntry(
      builder: (context) => Positioned(
        top: MediaQuery.of(context).padding.top + 12,
        left: 20,
        right: 20,
        child: TweenAnimationBuilder<double>(
          tween: Tween(begin: 0, end: 1),
          duration: const Duration(milliseconds: 400),
          builder: (context, opacity, child) =>
              Opacity(opacity: opacity, child: child),
          child: Material(
            color: Colors.transparent,
            child: ShakeWidget(
              key: key,
              shake: shake,
              child: Container(
                padding:
                const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.95),
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.3),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Center(
                  child: Text(
                    message,
                    style: const TextStyle(
                        color: Colors.black,
                        fontWeight: FontWeight.bold,
                        fontSize: 14),
                    textAlign: TextAlign.center,
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _hideToast() {
    if (_isShowing) {
      _overlayEntry?.remove();
      _overlayEntry = null;
      _isShowing = false;
    }
  }


  Future<bool> hasInternetQuick() async {
    try {
      final result = await InternetAddress.lookup('google.com')
          .timeout(const Duration(seconds: 2));
      return result.isNotEmpty && result.first.rawAddress.isNotEmpty;
    } catch (_) {
      return false;
    }
  }

  /// نمایش پیام کوتاه در صورت نبود اینترنت
  void showImmediateNoInternetToast(BuildContext context) {
    if (!context.mounted || Overlay.maybeOf(context) == null) return;
    final lang = Get.find<LanguageController>();
    final isFa = lang.isEN;
    final msg = isFa ? "اتصال اینترنت برقرار نیست ❌" : "No Internet Connection ❌";

    final overlay = OverlayEntry(
      builder: (_) => Positioned(
        top: MediaQuery.of(context).padding.top + 15,
        left: 25,
        right: 25,
        child: Material(
          color: Colors.transparent,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            decoration: BoxDecoration(
              color: Colors.redAccent.withOpacity(0.95),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Center(
              child: Text(
                msg,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                ),
              ),
            ),
          ),
        ),
      ),
    );
    Overlay.of(context).insert(overlay);
    Future.delayed(const Duration(seconds: 2), overlay.remove);
  }
}

/// وضعیت اتصال اینترنت
enum ConnectionStateType { unknown, connected, disconnected }

/// ویجت انیمیشن لرزش برای قطع اینترنت
class ShakeWidget extends StatefulWidget {
  final Widget child;
  final bool shake;
  const ShakeWidget({super.key, required this.child, this.shake = false});

  @override
  State<ShakeWidget> createState() => ShakeWidgetState();
}

class ShakeWidgetState extends State<ShakeWidget>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _animation;

  @override
  void initState() {
    super.initState();
    _controller =
        AnimationController(vsync: this, duration: const Duration(milliseconds: 500));

    _animation = TweenSequence([
      TweenSequenceItem(tween: Tween(begin: 0.0, end: -8.0), weight: 1),
      TweenSequenceItem(tween: Tween(begin: -8.0, end: 8.0), weight: 2),
      TweenSequenceItem(tween: Tween(begin: 8.0, end: 0.0), weight: 1),
    ]).animate(CurvedAnimation(parent: _controller, curve: Curves.easeInOut));

    if (widget.shake) {
      _controller.forward();
    }
  }

  @override
  void didUpdateWidget(covariant ShakeWidget oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.shake && !oldWidget.shake) {
      _controller.forward(from: 0);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _animation,
      builder: (context, child) => Transform.translate(
        offset: Offset(_animation.value, 0),
        child: child,
      ),
      child: widget.child,
    );
  }
}
