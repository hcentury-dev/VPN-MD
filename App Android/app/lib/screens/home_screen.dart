import 'dart:async';
import 'dart:convert';
import 'dart:developer';
import 'dart:io';
import 'dart:math' as math;
import 'dart:ui';
import 'dart:ui' as ui;

import 'package:badges/badges.dart' as badges;
import 'package:cached_network_image/cached_network_image.dart';
import 'package:cherry_toast/cherry_toast.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart' hide ConnectionState;
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_svg/svg.dart';
import 'package:flutter_v2ray_client/flutter_v2ray.dart';
import 'package:get_storage/get_storage.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:http/http.dart' as http;
import 'package:permission_handler/permission_handler.dart';
import 'package:persian_number_utility/persian_number_utility.dart';
import 'package:showcaseview/showcaseview.dart';
import 'package:snapping_sheet/snapping_sheet.dart';
import 'package:tray_manager/tray_manager.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:wave/config.dart';
import 'package:wave/wave.dart';
import 'package:window_manager/window_manager.dart';
import 'package:get/get.dart';

import '../Login.dart';
import '../class.dart';
import '../global.dart';
import '../helpers/ip_details.dart';
import '../helpers/pref.dart';
import '../services/network_monitor.dart';
import 'custon_language_sheet.dart';
import 'news_screen.dart';
import 'questions_screendart.dart';
import 'server_location.dart';
import 'widgets/custom_switch.dart';
import 'connected_devices.dart';
import '../helpers/api_auth.dart';
import '../controller/language/language_config.dart';
import '../controller/language/language_controller.dart';
import '../screens/setting_screen.dart';

// ─── Constants ────────────────────────────────────────────────────────────────
const _kDeepBg = Color(0xFF050510);
const _kCard = Color(0xFF0D1B3E);
const _kAccent = Color(0xFF4F8EFF);
const _kAccent2 = Color(0xFF00C8FF);
const _kGreen = Color(0xFF00E0C6);
const _kOrange = Color(0xFFFF9F43);
const _kRed = Color(0xFFFF5F5F);
const _kWhite70 = Color(0xB3FFFFFF);
const _kBorder = Color(0x264F8EFF);

int pingV2ra = 0;
bool isEntered = false;
late AnimationController _waveController;

// ─── Widget ───────────────────────────────────────────────────────────────────
class HomeScreen extends ConsumerStatefulWidget {
  final VPNClass selected;
  final VPNClass? best;

  const HomeScreen(this.selected, {super.key, this.best});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen>
    with SingleTickerProviderStateMixin, TrayListener, WindowListener {
  // ─── Keys ─────────────────────────────────────────────────────────────────
  final GlobalKey _menuKey = GlobalKey();
  // final GlobalKey helpKey1 = GlobalKey();
  final GlobalKey helpKey2 = GlobalKey();
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();
  final TextEditingController config = TextEditingController();

  // ─── State ────────────────────────────────────────────────────────────────
  IPDetails ip = IPDetails(
    country: '',
    regionName: '',
    city: '',
    zip: '',
    timezone: '',
    isp: '',
    query: '0.0.0.0',
  );

  bool updateBanner = false;
  bool updateApp = false;
  bool notBanner = false;

  String username = '', password = '', time = '', status = '';
  String pro = '', sub = '', size = '', full = '';
  int days = 0;
  String total = '0';
  bool active = true;
  bool permium = true;
  bool proxyOnly = false;
  int newsLength = 0;
  bool isConnected = false;
  String textDownload = '', textUpload = '', textTimer = '';
  String subscriptionStatus = '';
  String notstatus = '';

  Timer? _timer;

  final LanguageController languageController = Get.find<LanguageController>();
  V2ray flutterV2ray = V2rayManager.i.flutterV2ray;

  // ─── Auto-connect ─────────────────────────────────────────────────────────
  int _attemptId = 0;
  bool _isAutoConnecting = false;
  bool isSwitchedOn = false;

  // ─── Init ─────────────────────────────────────────────────────────────────
  @override
  void initState() {
    super.initState();
    _setSystemUI();
    Pref.isConnect = isConnected;
    _loadInitialSwitchStatus();
    _get();
    _checkVersion();
    loadSwitchStatus();
    _checkAccount();
    _checkUserNotification();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      final hasShown = GetStorage().read('hasShownShowcase') ?? false;
      if (!hasShown) {
        ShowCaseWidget.of(context)
            .startShowCase([_menuKey, helpKey2]);
        GetStorage().write('hasShownShowcase', true);
      }
    });

    _waveController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 8),
    )..repeat();

    V2rayManager.i.v2rayStatus.addListener(() {
      if (!mounted) return;
      final connected = V2rayManager.i.v2rayStatus.value.state == 'CONNECTED';
      if (connected != isConnected) {
        setState(() {
          isConnected = connected;
          _waveController.duration = connected
              ? const Duration(seconds: 3)
              : const Duration(seconds: 25);
          _waveController.repeat();
        });
      }
    });

    WidgetsBinding.instance.addPostFrameCallback((_) async {
      await Future.delayed(const Duration(milliseconds: 100));
      if (mounted) NetworkMonitor().start(context);
    });

    _timer = Timer.periodic(const Duration(seconds: 5), (_) {
      if (!mounted) return;
      _get();
    });
  }

  void _setSystemUI() {
    SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
      systemNavigationBarColor: _kDeepBg,
      systemNavigationBarIconBrightness: Brightness.light,
    ));
  }

  @override
  void dispose() {
    _timer?.cancel();
    config.dispose();
    V2rayManager.i.flutterV2ray.stopV2Ray();
    Pref.isConnect = false;
    isSwitchedOn = false;
    _waveController.dispose();
    super.dispose();
  }

  // ─── V2Ray text update ────────────────────────────────────────────────────
  void _updateTexts(V2RayStatus s) {
    textTimer = s.duration;
    final isFa = languageController.selectedLanguage == 'fa_IR';
    final rawD = s.download.toString();
    final rawU = s.upload.toString();
    textDownload = isFa ? rawD : convertToPersianNumbers(rawD);
    textUpload = isFa ? rawU : convertToPersianNumbers(rawU);

    if (s.state == 'CONNECTED') {
      isConnected = true;
    } else {
      textDownload = '0.0 B';
      textUpload = '0.0 B';
      isConnected = false;
      GetStorage().write('switchStatus', false);
      isSwitchedOn = false;
    }
  }

  // ─── Computed volume helpers ───────────────────────────────────────────────
  /// remaining GB  (remaining = total - used)
  double get _remainingGB {
    final total = double.tryParse(Pref.total) ?? 0;
    final used = (double.tryParse(Pref.size) ?? 0) / 1024;
    return (total - used).clamp(0, double.infinity);
  }

  /// progress = remaining / total  (0.0 – 1.0)
  double get _volumeProgress {
    final total = double.tryParse(Pref.total) ?? 0;
    if (total <= 0 || total >= 1024) return 1.0; // unlimited → full
    return (_remainingGB / total).clamp(0.0, 1.0);
  }

  // ─── BUILD ────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    _updateTexts(V2rayManager.i.v2rayStatus.value);

    return Stack(
      children: [
        Scaffold(
          key: _scaffoldKey,
          endDrawer: _buildDrawer(context),
          backgroundColor: _kDeepBg,
          body: Stack(
            children: [
              _buildBackground(),
              SafeArea(child: _buildBody(context)),
            ],
          ),
        ),
        if (updateApp) _buildUpdateOverlay(),
        if (notBanner) _buildNotifOverlay(),
        if (updateBanner && Pref.sure == 'true' && Pref.version != version)
          _buildForceUpdateOverlay(),
      ],
    );
  }

  // ─── Background ───────────────────────────────────────────────────────────
  Widget _buildBackground() {
    return AnimatedBuilder(
      animation: _waveController,
      builder: (_, __) {
        final t = _waveController.value;
        return Container(
          decoration: BoxDecoration(
            gradient: RadialGradient(
              center: Alignment(
                math.sin(t * math.pi * 2) * 0.25,
                math.cos(t * math.pi * 2) * 0.25,
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

  // ─── Body ─────────────────────────────────────────────────────────────────
  Widget _buildBody(BuildContext context) {
    return Directionality(
      textDirection: languageController.selectedLanguage == 'fa_IR'
          ? TextDirection.ltr
          : TextDirection.rtl,
      child: Column(
        children: [
          _buildTopBar(),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
              child: Column(
                children: [
                  const SizedBox(height: 12),
                  if (Pref.pro == 'Pro') ...[
                    _buildStatusCard(),
                    const SizedBox(height: 14),
                  ] else ...[
                    _buildFreeCard(),
                    const SizedBox(height: 14),
                  ],
                  _buildServerRow(),
                  const SizedBox(height: 14),
                  _buildTrafficCard(),
                  const SizedBox(height: 24),
                  _buildConnectButton(),
                  const SizedBox(height: 16),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ─── Top Bar ──────────────────────────────────────────────────────────────
  Widget _buildTopBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 0),
      child: Row(
        children: [
          ClipOval(
            child: CachedNetworkImage(
              imageUrl: Pref.app_logo,
              width: 46,
              height: 46,
              fit: BoxFit.cover,
              placeholder: (_, __) => Container(
                width: 46,
                height: 46,
                color: _kCard,
                child: const Icon(Icons.shield, color: _kAccent, size: 22),
              ),
              errorWidget: (_, __, ___) => Container(
                width: 46,
                height: 46,
                color: _kCard,
                child: const Icon(Icons.shield, color: _kAccent, size: 22),
              ),
            ),
          ),
          const SizedBox(width: 10),
          Container(width: 1, height: 28, color: Colors.white12),
          const SizedBox(width: 10),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(Pref.app_name,
                  style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                      letterSpacing: 0.5)),
            ],
          ),
          const Spacer(),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.06),
              borderRadius: BorderRadius.circular(50),
              border: Border.all(color: Colors.white12),
            ),
            child: Row(
              children: [
                GestureDetector(
                  onTap: () {
                    setState(() {});
                    Navigator.push(context,
                        MaterialPageRoute(builder: (_) => const NewsScreen()));
                  },
                  child: badges.Badge(
                    showBadge: int.tryParse(Pref.count) != null &&
                        int.parse(Pref.count) > 0,
                    badgeContent: Text(Pref.count,
                        style:
                            const TextStyle(color: Colors.white, fontSize: 9)),
                    badgeStyle: const badges.BadgeStyle(
                        badgeColor: _kRed, padding: EdgeInsets.all(4)),
                    child: Container(
                      padding: const EdgeInsets.all(7),
                      decoration: const BoxDecoration(
                          color: Colors.white10, shape: BoxShape.circle),
                      child: const Icon(Icons.notifications_outlined,
                          size: 20, color: Colors.white),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Showcase(
                  key: _menuKey,
                  title: LanguageConstants.mainMenu.tr,
                  description: LanguageConstants.menuText.tr,
                  titleAlignment: Alignment.centerRight,
                  descriptionAlignment: Alignment.centerRight,
                  descriptionTextDirection: TextDirection.rtl,
                  titleTextStyle: const TextStyle(fontSize: 15),
                  descTextStyle: TextStyle(
                      fontSize: 12, color: Colors.black.withOpacity(0.6)),
                  targetBorderRadius: BorderRadius.circular(50),
                  child: GestureDetector(
                    onTap: () => _scaffoldKey.currentState?.openEndDrawer(),
                    child: Container(
                      padding: const EdgeInsets.all(7),
                      decoration: BoxDecoration(
                        color: _kAccent.withOpacity(0.15),
                        shape: BoxShape.circle,
                        border: Border.all(color: _kAccent.withOpacity(0.3)),
                      ),
                      child: SvgPicture.asset('assets/images/menu.svg',
                          height: 16, color: Colors.white),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ─── Status Card (Pro) ─────────────────────────────────────────────────────
  Widget _buildStatusCard() {
    final isEnd = Pref.result == 'EndGB' || Pref.result == 'EndDay';
    final isSuccess = Pref.result == 'success';
    final Color statusAccent = isEnd ? _kOrange : (isSuccess ? _kGreen : _kRed);

    final int daysLeft = int.tryParse(Pref.days) ?? 0;

    return _GlassCard(
      borderColor: statusAccent.withOpacity(0.35),
      padding: const EdgeInsets.all(14),
      child: Column(
        children: [
          // ── Top row: status + IP ──
          Row(
            children: [
              // Status pill
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: statusAccent.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(30),
                  border: Border.all(color: statusAccent.withOpacity(0.35)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      isSuccess
                          ? Icons.verified_user_rounded
                          : (isEnd
                              ? Icons.hourglass_bottom_rounded
                              : Icons.block_rounded),
                      color: statusAccent,
                      size: 13,
                    ),
                    const SizedBox(width: 5),
                    Text(
                      isEnd
                          ? LanguageConstants.noActive.tr
                          : (isSuccess
                              ? LanguageConstants.active.tr
                              : LanguageConstants.deactivate.tr),
                      style: TextStyle(
                          fontSize: 11,
                          color: statusAccent,
                          fontWeight: FontWeight.w700),
                    ),
                  ],
                ),
              ),
              const Spacer(),
              // IP badge
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.05),
                  borderRadius: BorderRadius.circular(30),
                  border: Border.all(color: Colors.white12),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.router_outlined,
                        color: _kAccent2, size: 13),
                    const SizedBox(width: 5),
                    Text(
                      ip.query == '0.0.0.0'
                          ? '**.**.**.**'
                          : abbreviateIP(ip.query),
                      style: const TextStyle(
                          fontSize: 11,
                          color: _kAccent2,
                          fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
            ],
          ),

          const SizedBox(height: 5),
          // const Divider(color: Colors.white10, height: 1),
          const SizedBox(height: 5),

          // ── Bottom row: Volume + Days ──
          Row(
            children: [
              // Volume block
              Expanded(
                child: _buildVolumeBlock(),
              ),
              const SizedBox(width: 10),
              // Days block
              Expanded(
                child: _buildDaysBlock(daysLeft),
              ),
            ],
          ),
        ],
      ),
    );
  }

// ─── Volume Block ─────────────────────────────────────────────────────────────

// ─── Color & State Getters ────────────────────────────────────────────────────

  Color get _volumeColor {
    final total = double.tryParse(Pref.total) ?? 0;
    final isUnlimited = total >= 1024;
    if (isUnlimited) return const Color(0xFF4CAF50);
    if (_remainingGB <= 0) return const Color(0xFFE53935);
    if (_remainingGB <= 1.0) return const Color(0xFFFF9800);
    return const Color(0xFF4CAF50);
  }

  bool get _volumeWarning {
    final total = double.tryParse(Pref.total) ?? 0;
    if (total >= 1024) return false;
    return _remainingGB > 0 && _remainingGB <= 1.0;
  }

  bool get _volumeEmpty {
    final total = double.tryParse(Pref.total) ?? 0;
    if (total >= 1024) return false;
    return _remainingGB <= 0;
  }

  Color get _daysColor {
    final daysLeft = int.tryParse(Pref.days) ?? 0;
    if (daysLeft <= 0) return const Color(0xFFE53935);
    if (daysLeft <= 1) return const Color(0xFFFF9800);
    return const Color(0xFF4CAF50);
  }

  bool get _daysWarning {
    final daysLeft = int.tryParse(Pref.days) ?? 0;
    return daysLeft == 1;
  }

  bool get _daysEmpty {
    final daysLeft = int.tryParse(Pref.days) ?? 0;
    return daysLeft <= 0;
  }

// ─── Volume Block ─────────────────────────────────────────────────────────────

  Widget _buildVolumeBlock() {
    final total = double.tryParse(Pref.total) ?? 0;
    final isUnlimited = total >= 1024;
    final remaining = _remainingGB;
    final progress = _volumeProgress;
    final color = _volumeColor;
    final isFa = languageController.selectedLanguage == 'en_US';
    final isEmpty = !isUnlimited && remaining <= 0;

    final String remainLabel;
    if (isUnlimited) {
      remainLabel = isFa ? 'نامحدود' : 'Unlimited';
    } else if (isEmpty) {
      remainLabel = isFa ? 'اتمام حجم' : 'Out of Data';
    } else {
      final val = remaining.toStringAsFixed(1);
      remainLabel = isFa ? '${convertToPersianNumbers(val)} گیگ' : '$val GB';
    }

    final String subLabel;
    if (isUnlimited) {
      subLabel = isFa ? 'نامحدود' : 'Unlimited';
    } else if (isEmpty) {
      subLabel = isFa ? 'حجم شما تمام شده' : 'No data remaining';
    } else {
      subLabel =
          '${(progress * 100).toStringAsFixed(0)}% ${isFa ? 'باقی‌مانده' : 'remaining'}';
    }

    return AnimatedContainer(
      duration: const Duration(milliseconds: 400),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.07),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: color.withOpacity(isEmpty
              ? 0.5
              : _volumeWarning
                  ? 0.4
                  : 0.2),
          width: isEmpty ? 1.2 : 1.0,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Header row ──
          Row(
            mainAxisSize: MainAxisSize.max,
            children: [
              Container(
                padding: const EdgeInsets.all(5),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.15),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.data_usage_rounded, color: color, size: 13),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  LanguageConstants.home15.tr,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 11, color: _kWhite70),
                ),
              ),
              if (_volumeWarning || isEmpty) ...[
                const SizedBox(width: 4),
                _PulsingDot(color: color),
              ],
            ],
          ),
          const SizedBox(height: 10),
          // ── Value badge ──
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: color.withOpacity(0.12),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              remainLabel,
              style: TextStyle(
                fontSize: 13,
                color: color,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(height: 10),
          // ── Progress ──
          _StripedProgressBar(
            key: ValueKey('vol_${progress}_$isEmpty'),
            value: isUnlimited ? 1.0 : progress,
            color: color,
            striped: isEmpty,
          ),
          const SizedBox(height: 5),
          // ── Sub label ──
          Text(
            subLabel,
            style: TextStyle(fontSize: 10, color: color.withOpacity(0.6)),
          ),
        ],
      ),
    );
  }

// ─── Days Block ───────────────────────────────────────────────────────────────

  Widget _buildDaysBlock(int daysLeft) {
    final color = _daysColor;
    final isFa = languageController.selectedLanguage == 'en_US';
    final isEmpty = daysLeft <= 0;
    final totalDays = int.tryParse(Pref.adate) ?? 30;
    final double daysProgress =
        (daysLeft / (totalDays > 0 ? totalDays : 1)).clamp(0.0, 1.0);

    final String label;
    if (isEmpty) {
      label = isFa ? 'منقضی شده' : 'Expired';
    } else {
      label = isFa
          ? '${convertToPersianNumbers(daysLeft.toString())} ${LanguageConstants.day.tr}'
          : '${daysLeft.toString().extractNumber()} ${LanguageConstants.day.tr}';
    }

    final String subLabel;
    if (isEmpty) {
      subLabel = isFa ? 'اشتراک منقضی شده' : 'Subscription expired';
    } else {
      subLabel =
          isFa ? 'از ${toPersianDigits(totalDays)} روز' : 'of $totalDays days';
    }

    return AnimatedContainer(
      duration: const Duration(milliseconds: 400),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.07),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: color.withOpacity(isEmpty
              ? 0.5
              : _daysWarning
                  ? 0.4
                  : 0.2),
          width: isEmpty ? 1.2 : 1.0,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Header row ──
          Row(
            mainAxisSize: MainAxisSize.max,
            children: [
              Container(
                padding: const EdgeInsets.all(5),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.15),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.schedule_rounded, color: color, size: 13),
              ),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  LanguageConstants.home16.tr,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 11, color: _kWhite70),
                ),
              ),
              if (_daysWarning || isEmpty) ...[
                const SizedBox(width: 4),
                _PulsingDot(color: color),
              ],
            ],
          ),
          const SizedBox(height: 10),
          // ── Value badge ──
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: color.withOpacity(0.12),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                color: color,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(height: 10),
          // ── Progress ──
          _StripedProgressBar(
            key: ValueKey('days_${daysLeft}_$isEmpty'),
            value: daysProgress,
            color: color,
            striped: isEmpty,
          ),
          const SizedBox(height: 5),
          // ── Sub label ──
          Text(
            subLabel,
            style: TextStyle(fontSize: 10, color: color.withOpacity(0.6)),
          ),
        ],
      ),
    );
  }

  // ─── Free Card ────────────────────────────────────────────────────────────
  Widget _buildFreeCard() {
    return _GlassCard(
      borderColor: _kOrange.withOpacity(0.35),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: _kOrange.withOpacity(0.15),
              shape: BoxShape.circle,
              border: Border.all(color: _kOrange.withOpacity(0.3)),
            ),
            child: const Icon(Icons.lock_outline_rounded,
                color: _kOrange, size: 24),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(LanguageConstants.freeTitleText.tr,
                    style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: Colors.white)),
                const SizedBox(height: 3),
                Text(LanguageConstants.freeText.tr,
                    style: const TextStyle(
                        fontSize: 11, color: _kWhite70, height: 1.4)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ─── Server Row ───────────────────────────────────────────────────────────
  Widget _buildServerRow() {
    return GestureDetector(
      onTap: () async {
        final reason = _getDisabledReason();

        if (reason != null) {
          setState(() {}); // اگر لازم داری UI آپدیت بشه
          return;
        }

        await Navigator.push(
          context,
          MaterialPageRoute(builder: (_) => const ServerLocation()),
        );

        loadSwitchStatus();
      },
      child: Stack(
        children: [
          AnimatedBuilder(
            animation: _waveController,
            builder: (_, __) {
              final reason = _getDisabledReason();
              final isLocked = reason != null;

              return Opacity(
                opacity: isLocked ? 0.5 : 1.0,
                child: Container(
                  padding: const EdgeInsets.all(2),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(18),
                    gradient: SweepGradient(
                      transform: GradientRotation(_waveController.value * 6.28),
                      colors: isConnected
                          ? const [_kCard, _kBorder]
                          : const [_kBorder, _kBorder],
                    ),
                  ),
                  child: _GlassCard(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 12),
                    borderColor: _kBorder.withOpacity(0.5),
                    child: ValueListenableBuilder(
                      valueListenable: Pref.vpnClassbox.listenable(),
                      builder: (_, box, __) {
                        final vpn =
                            box.values.isNotEmpty ? box.values.first : null;
                        final hasServer =
                            vpn != null && vpn.serverId.isNotEmpty;

                        final isRtl =
                            languageController.selectedLanguage == 'en_US';

                        final String name = hasServer
                            ? (isRtl ? vpn.nameFa : vpn.nameEn)
                            : LanguageConstants.home42.tr;

                        return Directionality(
                          textDirection:
                              isRtl ? TextDirection.rtl : TextDirection.ltr,
                          child: Row(
                            children: [
                              ClipOval(
                                child: hasServer && vpn.src.isNotEmpty
                                    ? Image.network(
                                        vpn.src,
                                        width: 44,
                                        height: 44,
                                        fit: BoxFit.cover,
                                        errorBuilder: (_, __, ___) =>
                                            _serverIconFallback(),
                                      )
                                    : _serverIconFallback(),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  name,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontSize: 14,
                                    color: Colors.white,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                              Container(
                                width: 36,
                                height: 36,
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.08),
                                  shape: BoxShape.circle,
                                  border: Border.all(color: Colors.white12),
                                ),
                                child: const Icon(
                                  Icons.arrow_forward_ios_rounded,
                                  size: 14,
                                  color: _kWhite70,
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                  ),
                ),
              );
            },
          ),

          // 🔒 Overlay وقتی قفل است
          ValueListenableBuilder(
            valueListenable: Pref.vpnClassbox.listenable(),
            builder: (_, __, ___) {
              final reason = _getDisabledReason();
              if (reason == null) return const SizedBox();

              return Positioned.fill(
                child: Container(
                  decoration: BoxDecoration(
                    color: Colors.black.withOpacity(0.45),
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(
                          Icons.lock_outline,
                          color: Colors.white70,
                          size: 28,
                        ),
                        const SizedBox(height: 8),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 10),
                          child: Text(
                            reason,
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  String? _getDisabledReason() {
    if (!Platform.isAndroid) return "فقط روی اندروید قابل استفاده است";
    if (Pref.isGB == false) return LanguageConstants.home1.tr;
    if (Pref.isDay == false) return LanguageConstants.home3.tr;
    if (Pref.isActive == false) return LanguageConstants.home4.tr;
    if (isConnected) return LanguageConstants.home41.tr;
    return null;
  }

  Widget _serverIconFallback() => Container(
        width: 44,
        height: 44,
        color: _kCard,
        child: const Icon(Icons.flag_outlined, color: _kWhite70, size: 20),
      );

  // ─── Traffic Card ─────────────────────────────────────────────────────────
  Widget _buildTrafficCard() {
    return _GlassCard(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      borderColor: _kBorder.withOpacity(0.5),
      child: Directionality(
        textDirection: languageController.selectedLanguage == 'en_US'
            ? TextDirection.rtl
            : TextDirection.ltr,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 16),
          child: Row(
            children: [
              _trafficCol(
                icon: Icons.cloud_upload_sharp,
                color: _kOrange,
                value: languageController.selectedLanguage == 'en_US'
                    ? convertToPersianNumbers(textUpload)
                    : textUpload,
                label: LanguageConstants.upload.tr,
              ),
              const Spacer(),
              Column(
                children: [
                  // دایره‌ی زمان اتصال با انیمیشن
                  TweenAnimationBuilder(
                    tween: Tween<double>(begin: 0.95, end: 1.0),
                    duration: const Duration(seconds: 1),
                    curve: Curves.elasticOut,
                    builder: (context, scale, child) => Transform.scale(
                      scale: scale,
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          gradient: LinearGradient(
                            colors: [
                              Colors.white.withOpacity(0.1),
                              Colors.white.withOpacity(0.02),
                            ],
                          ),
                          border: Border.all(
                              color: Colors.white.withOpacity(0.2), width: 1),
                        ),
                        child: ValueListenableBuilder(
                          valueListenable: V2rayManager.i.v2rayStatus,
                          builder: (_, v, __) => Text(
                            languageController.selectedLanguage == 'fa_IR'
                                ? v.duration
                                : convertToPersianNumbers(v.duration),
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                              color: Colors.white,
                              letterSpacing: 0.5,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  AnimatedContainer(
                    duration: const Duration(milliseconds: 400),
                    padding:
                        const EdgeInsets.symmetric(horizontal: 18, vertical: 8),
                    decoration: BoxDecoration(
                      color: (isConnected ? _kGreen : _kRed).withOpacity(0.12),
                      borderRadius: BorderRadius.circular(30),
                      border: Border.all(
                          color:
                              (isConnected ? _kGreen : _kRed).withOpacity(0.5),
                          width: 0.8),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                            isConnected
                                ? Icons.wifi_rounded
                                : Icons.wifi_off_rounded,
                            size: 14,
                            color: isConnected ? _kGreen : _kRed),
                        const SizedBox(width: 6),
                        Text(
                          isConnected
                              ? LanguageConstants.connect.tr
                              : LanguageConstants.home8.tr,
                          style: TextStyle(
                              fontSize: 11,
                              color: isConnected ? _kGreen : _kRed,
                              fontWeight: FontWeight.w700),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const Spacer(),
              _trafficCol(
                icon: Icons.cloud_download_sharp,
                color: _kGreen,
                value: languageController.selectedLanguage == 'en_US'
                    ? convertToPersianNumbers(textDownload)
                    : textDownload,
                label: LanguageConstants.download.tr,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _trafficCol({
    required IconData icon,
    required Color color,
    required String value,
    required String label,
  }) {
    return Column(
      children: [
        // آیکون در دایره‌ی شیشه‌ای
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: color.withOpacity(0.1),
            border: Border.all(color: color.withOpacity(0.3), width: 1),
          ),
          child: Icon(icon, color: color, size: 20),
        ),
        const SizedBox(height: 10),
        // مقدار ترافیک (بولد و براق)
        Text(
          value,
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: color,
            shadows: [
              Shadow(
                  blurRadius: 4,
                  color: color.withOpacity(0.4),
                  offset: const Offset(0, 1)),
            ],
          ),
        ),
        const SizedBox(height: 6),
        // لیبل (خاکستری کم‌رنگ)
        Text(
          label,
          style: const TextStyle(
            fontSize: 11,
            color: Color(0xCCFFFFFF),
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }

  // ─── Connect Button with World Map Background ─────────────────────────────
  Widget _buildConnectButton() {
    return Showcase(
      key: helpKey2,
      title: LanguageConstants.connectButton.tr,
      description: LanguageConstants.connectText.tr,
      titleAlignment: Alignment.centerRight,
      descriptionAlignment: Alignment.centerRight,
      descriptionTextDirection: TextDirection.rtl,
      titleTextStyle: const TextStyle(fontSize: 15),
      descTextStyle: const TextStyle(fontSize: 12),
      targetBorderRadius: BorderRadius.circular(1000),
      child: SizedBox(
        width: double.infinity,
        height: 220,
        child: Stack(
          alignment: Alignment.center,
          children: [
            // ── World map image ──
            Positioned.fill(
              child: Image.asset(
                'assets/images/bg.png',
                fit: BoxFit.cover,
                color: Colors.white.withOpacity(0.3),
                colorBlendMode: BlendMode.modulate,
              ),
            ),

            // ── Animated glow behind button ──
            AnimatedBuilder(
              animation: _waveController,
              builder: (_, __) {
                final pulse =
                    (math.sin(_waveController.value * math.pi * 2) * 0.5 + 0.5);
                return Container(
                  width: 180,
                  height: 180,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: RadialGradient(
                      colors: isConnected
                          ? [
                              _kGreen.withOpacity(0.25 + pulse * 0.1),
                              Colors.transparent,
                            ]
                          : [
                              _kAccent.withOpacity(0.12 + pulse * 0.06),
                              Colors.transparent,
                            ],
                    ),
                  ),
                );
              },
            ),

            // ── Orbit ring ──
            AnimatedBuilder(
              animation: _waveController,
              builder: (_, __) => CustomPaint(
                size: const Size(190, 190),
                painter: _ConnectRingPainter(
                  progress: _waveController.value,
                  isOn: isConnected,
                ),
              ),
            ),

            // ── Core button ──
            GestureDetector(
              onTap: _handleConnectTap,
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 400),
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: _kCard,
                  border: Border.all(
                    color: isConnected
                        ? _kGreen.withOpacity(0.5)
                        : _kAccent.withOpacity(0.25),
                    width: 1.5,
                  ),
                  boxShadow: [
                    BoxShadow(
                      color:
                          (isConnected ? _kGreen : _kAccent).withOpacity(0.25),
                      blurRadius: 20,
                      spreadRadius: 3,
                    ),
                  ],
                ),
                child: Center(
                  child: Icon(
                    isConnected
                        ? Icons.stop_rounded
                        : Icons.electric_bolt_rounded,
                    size: 44,
                    color: isConnected ? _kGreen : _kAccent,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _handleConnectTap() {
    if (!isConnected) {
      if (Pref.status != 'active' || Pref.devicesStatus != 'active') {
        _showError(LanguageConstants.home4.tr);
        return;
      }
      if (Pref.result == 'EndDay') {
        _showInfo(LanguageConstants.home3.tr);
        return;
      }
      if (Pref.result == 'EndGB') {
        _showInfo(LanguageConstants.home1.tr);
        return;
      }
      if (Pref.result == 'suspended') {
        _showError(LanguageConstants.home4.tr);
        return;
      }
      if (Pref.vpnClass == null) {
        _showInfo(LanguageConstants.home5.tr);
        return;
      }
      connectedAndroid(Pref.vpnClass!);
    } else {
      disconnectAndroid();
    }
  }

  // ─── Drawer ───────────────────────────────────────────────────────────────
  Widget _buildDrawer(BuildContext context) {
    return ClipRRect(
      borderRadius: const BorderRadius.only(topLeft: Radius.circular(32)),
      child: Drawer(
        backgroundColor: const Color(0xFF080818),
        child: Directionality(
          textDirection: languageController.selectedLanguage == 'en_US'
              ? TextDirection.rtl
              : TextDirection.ltr,
          child: Column(
            children: [
              // Header
              Container(
                width: double.infinity,
                padding: const EdgeInsets.fromLTRB(20, 55, 20, 20),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [_kAccent.withOpacity(0.15), Colors.transparent],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  border:
                      const Border(bottom: BorderSide(color: Colors.white10)),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: _kAccent.withOpacity(0.1),
                        shape: BoxShape.circle,
                        border: Border.all(color: _kAccent.withOpacity(0.2)),
                      ),
                      child: const Icon(Icons.account_circle,
                          color: _kAccent, size: 35),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(LanguageConstants.home13.tr,
                            style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: Colors.white)),
                        GestureDetector(
                          onTap: () => Clipboard.setData(
                                  ClipboardData(text: Pref.username))
                              .then((_) => _showSuccess(
                                  LanguageConstants.usernameCopied.tr)),
                          child: Text(
                            Pref.username,
                            style:
                                const TextStyle(fontSize: 14, color: _kWhite70),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // Items
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  children: [
                    _drawerInfoRow(
                        LanguageConstants.home14.tr, _packageLabel()),
                    _drawerInfoRow(LanguageConstants.home51.tr, _multiLabel()),

                    // ── Volume progress inside drawer ──
                    _drawerVolumeRow(),

                    // ── Days progress inside drawer ──
                    _drawerDaysRow(),

                    _drawerInfoRow(
                        LanguageConstants.home48.tr, _totalVolumeLabel()),
                    if (Pref.pro == 'Pro')
                      _drawerInfoRow(LanguageConstants.home17.tr, _dateLabel()),
                    _drawerInfoRow(
                        LanguageConstants.home18.tr,
                        active
                            ? LanguageConstants.active.tr
                            : LanguageConstants.deactivate.tr,
                        valueColor: active ? const Color(0xFF059D40) : _kRed),

                    const Divider(color: Colors.white10, height: 24),

                    // ── Live light/dark theme switch ──
                    _drawerItem(
                        languageController.selectedLanguage == 'en_US'
                            ? (Pref.isDarkMode ? 'Light mode' : 'Dark mode')
                            : (Pref.isDarkMode ? 'حالت روشن' : 'حالت تاریک'),
                        languageController.selectedLanguage == 'en_US'
                            ? 'Switch app theme'
                            : 'تغییر پوستهٔ برنامه',
                        Pref.isDarkMode
                            ? Icons.light_mode_rounded
                            : Icons.dark_mode_rounded, () {
                      final next = !Pref.isDarkMode;
                      Pref.isDarkMode = next;
                      Get.changeThemeMode(
                          next ? ThemeMode.dark : ThemeMode.light);
                      setState(() {});
                    }),

                    _drawerItem(
                        LanguageConstants.home39.tr,
                        LanguageConstants.home40.tr,
                        Icons.filter_alt_off_rounded,
                        () => Navigator.push(
                            context,
                            MaterialPageRoute(
                                builder: (_) => const SettingScreen()))),
                    _drawerItem(
                        LanguageConstants.home49.tr,
                        LanguageConstants.home50.tr,
                        Icons.mobile_friendly_rounded,
                        () => Navigator.push(
                            context,
                            MaterialPageRoute(
                                builder: (_) => const DevicesScreen()))),
                    _drawerItem(
                        LanguageConstants.home19.tr,
                        LanguageConstants.home20.tr,
                        Icons.language_rounded,
                        () => Get.bottomSheet(
                            const SizedBox(child: LanguageSheet()))),
                    _drawerItem(
                        LanguageConstants.home21.tr,
                        LanguageConstants.home22.tr,
                        Icons.refresh_rounded,
                        _handleCheckUpdate),
                    if (Pref.buy != '#')
                      _drawerItem(
                          LanguageConstants.home24.tr,
                          LanguageConstants.home25.tr,
                          Icons.shopping_cart_outlined,
                          () => launchUrl(Uri.parse(Pref.buy))),
                    if (Pref.renew != '#')
                      _drawerItem(
                          LanguageConstants.home240.tr,
                          LanguageConstants.home250.tr,
                          Icons.local_offer_rounded,
                          () => launchUrl(Uri.parse(Pref.renew))),
                    if (Pref.support != '#')
                      _drawerItem(
                          LanguageConstants.home26.tr,
                          LanguageConstants.home27.tr,
                          Icons.support_agent_rounded,
                          () => launchUrl(Uri.parse(Pref.support))),
                    if (Pref.amozesh != '#')
                      _drawerItem(
                          LanguageConstants.home43.tr,
                          LanguageConstants.home44.tr,
                          Icons.menu_book_rounded,
                          () => launchUrl(Uri.parse(Pref.amozesh))),
                    _drawerItem(
                        LanguageConstants.home28.tr,
                        LanguageConstants.home29.tr,
                        Icons.help_outline_rounded, () {
                      Navigator.pop(context);
                      Navigator.push(
                          context,
                          MaterialPageRoute(
                              builder: (_) => const QuestionsScreen()));
                    }),
                    _drawerItem(
                        LanguageConstants.home30.tr,
                        LanguageConstants.home31.tr,
                        Icons.logout_rounded,
                        _confirmLogout,
                        color: _kRed),

                    const Divider(color: Colors.white10, height: 16),
                    InkWell(
                      child: ListTile(
                        title: Text(
                          ' ${LanguageConstants.version.tr} ${Pref.version}',
                          style: const TextStyle(
                              fontFamily: 'sans',
                              color: toolbarC,
                              fontSize: 14),
                        ),
                        trailing:
                            const Icon(Icons.verified_sharp, color: fColor),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

// ─── Volume Row ─────────────────────────────────────────────────────────────

  Widget _drawerVolumeRow() {
    final total = double.tryParse(Pref.total) ?? 0;
    final isUnlimited = total >= 1024;
    final remaining = _remainingGB;
    final progress = _volumeProgress;
    final color = _volumeColor;
    final isFa = languageController.selectedLanguage == 'en_US';

    final isEmpty = !isUnlimited && remaining <= 0;
    final isWarning = _volumeWarning;

    final String label;
    if (isUnlimited) {
      label = isFa ? 'نامحدود' : 'Unlimited';
    } else if (isEmpty) {
      label = isFa ? 'اتمام حجم' : 'Out of Data';
    } else {
      final v = remaining.toStringAsFixed(1);
      label = isFa ? '$v GB' : '${convertToPersianNumbers(v)} گیگ';
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 400),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: color.withOpacity(0.07),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: color.withOpacity(isEmpty ? 0.45 : 0.2)),
        ),
        child: Column(
          children: [
            Row(
              children: [
                Icon(Icons.data_usage_rounded, color: color, size: 14),
                const SizedBox(width: 6),
                Text(LanguageConstants.home15.tr,
                    style: const TextStyle(fontSize: 12, color: _kWhite70)),
                const Spacer(),
                if (isWarning || isEmpty) _PulsingDot(color: color),
                const SizedBox(width: 4),
                Text(label,
                    style: TextStyle(
                        fontSize: 12,
                        color: color,
                        fontWeight: FontWeight.w700)),
              ],
            ),
            const SizedBox(height: 8),
            _StripedProgressBar(
              key: ValueKey('vol_${progress}_$isEmpty'),
              value: isUnlimited ? 1.0 : progress,
              color: color,
              striped: isEmpty,
            ),
          ],
        ),
      ),
    );
  }

// ─── Days Row ────────────────────────────────────────────────────────────────

  Widget _drawerDaysRow() {
    final daysLeft = int.tryParse(Pref.days) ?? 0;
    final color = _daysColor;
    final isFa = languageController.selectedLanguage == 'en_US';
    final totalDays = int.tryParse(Pref.adate) ?? 30;

    final double daysProgress =
    (daysLeft / (totalDays > 0 ? totalDays : 1)).clamp(0.0, 1.0);

    final isEmpty = daysLeft <= 0;
    final isWarning = _daysWarning;

    final String label;
    if (isEmpty) {
      label = isFa ? 'منقضی شده' : 'Expired';
    } else {
      label = isFa
          ? '$daysLeft ${LanguageConstants.day.tr}'
          : '${daysLeft.toString().extractNumber()} ${LanguageConstants.day.tr}';
    }

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 400),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: color.withOpacity(0.07),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: color.withOpacity(isEmpty ? 0.45 : 0.2)),
        ),
        child: Column(
          children: [
            Row(
              children: [
                Icon(Icons.schedule_rounded, color: color, size: 14),
                const SizedBox(width: 6),
                Text(LanguageConstants.home16.tr,
                    style: const TextStyle(fontSize: 12, color: _kWhite70)),
                const Spacer(),
                if (isWarning || isEmpty) _PulsingDot(color: color),
                const SizedBox(width: 4),
                Text(label,
                    style: TextStyle(
                        fontSize: 12,
                        color: color,
                        fontWeight: FontWeight.w700)),
              ],
            ),
            const SizedBox(height: 8),
            _StripedProgressBar(
              key: ValueKey('days_${daysLeft}_$isEmpty'),
              value: daysProgress,
              color: color,
              striped: isEmpty,
            ),
          ],
        ),
      ),
    );
  }

  Widget _drawerInfoRow(String label, String value, {Color? valueColor}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 5),
      child: Row(
        children: [
          Text(label, style: const TextStyle(fontSize: 13, color: _kWhite70)),
          const Spacer(),
          Text(value,
              style: TextStyle(
                  fontSize: 12,
                  color: valueColor ?? Colors.white,
                  fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  Widget _drawerItem(
      String title, String sub, IconData icon, VoidCallback onTap,
      {Color? color}) {
    return ListTile(
      dense: true,
      onTap: onTap,
      title: Text(title,
          style: TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: color ?? Colors.white)),
      subtitle:
          Text(sub, style: const TextStyle(fontSize: 10, color: _kWhite70)),
      trailing: Icon(icon, color: color ?? _kAccent, size: 20),
    );
  }

  // ─── Drawer label helpers ─────────────────────────────────────────────────
  String _packageLabel() {
    final isFa = languageController.selectedLanguage == 'en_US';
    return isFa
        ? '${LanguageConstants.package.tr} ${Pref.adate} ${LanguageConstants.days.tr}'
        : '${LanguageConstants.package.tr} ${Pref.adate.extractNumber()} ${LanguageConstants.days.tr}';
  }

  String _multiLabel() {
    final isFa = languageController.selectedLanguage == 'en_US';
    return isFa
        ? '${Pref.multi} ${LanguageConstants.usersMulti.tr}'
        : '${Pref.multi.extractNumber()} ${LanguageConstants.usersMulti.tr}';
  }

  String _totalVolumeLabel() {
    final total = double.tryParse(Pref.total);
    if (total == null) return '0 GB';
    final isFa = languageController.selectedLanguage == 'en_US';
    if (total >= 1024) {
      return isFa ? 'نامحدود' : 'Unlimited';
    }

    final numberStr = total.toStringAsFixed(1);
    final formatted = isFa ? '$numberStr گیگ' : '$numberStr GB';

    if (isFa) {
      return convertToPersianNumbers(formatted);
    } else {
      return formatted.extractNumber();
    }
  }

  String _remainingVolumeLabel() {
    final total = double.tryParse(Pref.total) ?? 0;
    final sz = double.tryParse(Pref.size) ?? 0;
    final rem = total - (sz / 1024);
    if (total >= 500000 || rem >= 1024) return 'نامحدود';
    if (rem <= 0) return 'اتمام حجم';
    return '${rem.toStringAsFixed(1)} GB';
  }

  String _dateLabel() {
    switch (languageController.selectedLanguage) {
      case 'fa_IR':
        return Pref.date.toEnglishDate();
      case 'en_US':
        return Pref.date.toJalali();
      default:
        return Pref.date;
    }
  }

  void _handleCheckUpdate() {
    final upToDate = Platform.isAndroid
        ? Pref.version == version
        : Pref.versionWin == version;
    if (upToDate) {
      _showSuccess(LanguageConstants.home23.tr);
    } else {
      setState(() => updateApp = true);
    }
  }

  void _confirmLogout() async {
    await showDialog(
      context: context,
      builder: (_) => AlertDialog(
        backgroundColor: const Color(0xFF1C1C1E),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text(LanguageConstants.home30.tr,
            style: const TextStyle(color: Colors.white, fontSize: 16)),
        content: Text(LanguageConstants.home32.tr,
            style: const TextStyle(color: _kWhite70, fontSize: 14)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(LanguageConstants.no.tr,
                style: const TextStyle(color: _kRed)),
          ),
          ElevatedButton(
            onPressed: logout,
            style: ElevatedButton.styleFrom(
              backgroundColor: _kAccent,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10)),
            ),
            child: Text(LanguageConstants.yes.tr,
                style: const TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  // ─── Overlays ─────────────────────────────────────────────────────────────
  Widget _buildUpdateOverlay() => _overlay(_buildUpdateCard(forced: false));

  Widget _buildForceUpdateOverlay() => _overlay(_buildUpdateCard(forced: true));

  Widget _buildNotifOverlay() => _overlay(_buildNotifCard());

  Widget _overlay(Widget child) => Positioned.fill(
        child: Container(
          color: Colors.black.withOpacity(0.6),
          child: Center(child: child),
        ),
      );

  Widget _buildUpdateCard({required bool forced}) {
    return _GlassCard(
      margin: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: _kCard,
            backgroundImage: NetworkImage(Pref.app_logo),
          ),
          const SizedBox(height: 14),
          Text(LanguageConstants.text1Updated.tr,
              style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.white)),
          const SizedBox(height: 8),
          Text(LanguageConstants.text2Updated.tr,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 13, color: _kWhite70)),
          const SizedBox(height: 18),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              if (!forced)
                TextButton(
                  onPressed: () => setState(() => updateApp = false),
                  child: Text(LanguageConstants.text5Updated.tr,
                      style: const TextStyle(color: _kWhite70)),
                ),
              ElevatedButton(
                onPressed: () => launchUrl(
                    Uri.parse(Platform.isAndroid ? Pref.link : Pref.linkWin)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: _kAccent,
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(10)),
                ),
                child: Text(LanguageConstants.text4Updated.tr,
                    style: const TextStyle(color: Colors.white)),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildNotifCard() {
    return TweenAnimationBuilder(
      tween: Tween<double>(begin: 0.95, end: 1.0),
      duration: const Duration(milliseconds: 1500),
      curve: Curves.easeInOutSine,
      builder: (context, double scale, child) {
        return Transform.scale(
          scale: scale,
          child: child,
        );
      },
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(32),
          boxShadow: [
            BoxShadow(
              color: Colors.orange.shade800.withOpacity(0.4),
              blurRadius: 35,
              spreadRadius: 5,
              offset: const Offset(0, 15),
            ),
            BoxShadow(
              color: Colors.black.withOpacity(0.4),
              blurRadius: 20,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(32),
          child: BackdropFilter(
            filter: ui.ImageFilter.blur(sigmaX: 12, sigmaY: 12),
            child: Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    Colors.grey.shade900.withOpacity(0.85),
                    Colors.black.withOpacity(0.9),
                  ],
                ),
                border: Border.all(
                  width: 1.5,
                  color: Colors.orange.shade300.withOpacity(0.6),
                ),
              ),
              child: Stack(
                children: [
                  // افکت درخشش گوشه‌ای (اختیاری، خیلی شیک)
                  Positioned(
                    top: -20,
                    right: -20,
                    child: Container(
                      width: 100,
                      height: 100,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.orange.shade500.withOpacity(0.15),
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: -30,
                    left: -30,
                    child: Container(
                      width: 120,
                      height: 120,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: Colors.deepPurple.shade500.withOpacity(0.1),
                      ),
                    ),
                  ),
                  // محتوای اصلی
                  Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        // آیکون با حلقه‌ی درخشان
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: LinearGradient(
                              colors: [
                                Colors.orange.shade400,
                                Colors.orange.shade700,
                              ],
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.orange.shade400.withOpacity(0.6),
                                blurRadius: 18,
                                spreadRadius: 2,
                              ),
                            ],
                          ),
                          child: const Icon(
                            Icons.notifications_active_rounded,
                            color: Colors.white,
                            size: 32,
                          ),
                        ),
                        const SizedBox(height: 20),
                        // عنوان
                        Text(
                          Pref.not_name,
                          style: const TextStyle(
                            fontSize: 22,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                            letterSpacing: 0.3,
                            shadows: [
                              Shadow(
                                blurRadius: 10,
                                color: Colors.black45,
                                offset: Offset(1, 1),
                              ),
                            ],
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 14),
                        // متن اصلی
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8),
                          child: Text(
                            Pref.not_text,
                            style: const TextStyle(
                              fontSize: 15,
                              color: Color(0xEEFFFFFF),
                              height: 1.45,
                              fontWeight: FontWeight.w500,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ),
                        const SizedBox(height: 28),
                        // دکمه‌ها
                        Row(
                          mainAxisAlignment: MainAxisAlignment.end,
                          children: [
                            if (Pref.not_link != '#')
                              Container(
                                decoration: BoxDecoration(
                                  borderRadius: BorderRadius.circular(40),
                                  gradient: LinearGradient(
                                    colors: [
                                      Colors.orange.shade400,
                                      Colors.orange.shade800,
                                    ],
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.orange.shade700
                                          .withOpacity(0.4),
                                      blurRadius: 12,
                                      offset: const Offset(0, 4),
                                    ),
                                  ],
                                ),
                                child: ElevatedButton(
                                  onPressed: () async {
                                    setState(() => notBanner = false);
                                    await launchUrl(Uri.parse(Pref.not_link));
                                  },
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.transparent,
                                    shadowColor: Colors.transparent,
                                    foregroundColor: Colors.white,
                                    elevation: 0,
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 20, vertical: 12),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(40),
                                    ),
                                    textStyle: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                    ),
                                  ),
                                  child: Text(LanguageConstants.openTheLink.tr),
                                ),
                              ),
                            const SizedBox(width: 12),
                            TextButton(
                              onPressed: () => setState(() {
                                notification();
                              }),
                              style: TextButton.styleFrom(
                                foregroundColor: Colors.grey.shade300,
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 16, vertical: 12),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(30),
                                ),
                              ),
                              child: Text(
                                LanguageConstants.dont.tr,
                                style: const TextStyle(
                                    fontWeight: FontWeight.w500),
                              ),
                            ),
                            TextButton(
                              onPressed: () =>
                                  setState(() => notBanner = false),
                              style: TextButton.styleFrom(
                                foregroundColor: Colors.grey.shade300,
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 16, vertical: 12),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(30),
                                ),
                              ),
                              child: Text(
                                LanguageConstants.close.tr,
                                style: const TextStyle(
                                    fontWeight: FontWeight.w500),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  // ─── VPN Logic ────────────────────────────────────────────────────────────
  Future<String> _buildConfigText(VPNClass vpn) async {
    if (vpn.json.toLowerCase() == 'true') return vpn.link;
    if (vpn.fragment == 'true') {
      return activeFragment(parser: V2ray.parseFromURL(vpn.link));
    }
    return V2ray.parseFromURL(vpn.link).getFullConfiguration();
  }

  void connectedAndroid(VPNClass model) async {
    await checkNotificationPermission();

    final filterApp =
        (GetStorage().read('appFilter') as List<dynamic>?)?.cast<String>() ??
            [];
    if (!filterApp.contains(packageName)) {
      filterApp.add(packageName);
    }

    const List<String> iranianAppPackages = [
      'com.rubika.android',
      'ir.rubx.bapp',
      'app.eitaa',
      'ir.bale',
      'ir.nasim',
      'ir.eitaa.messenger',
      'mobi.mmdt.ottplus',
      'ir.tgchannel.soroush',
      'ir.tgchannel.soroushPlus',
      'net.iGap',
      'app.igap',
      'com.shad',
      'app.hiddify.com',
      'com.iranicard.app',
      'com.v2ray.ang',
      'ir.mci.ecareapp',
      'com.myirancell',
      'ir.divar',
      'ir.bmi.bam.nativeweb',
      'cab.snapp.passenger',
      'com.ada.mbank.mehr',
      'ir.torob',
      'com.farsitel.bazaar',
      'ir.mservices.market',
      'ir.ayantech.ghabzino',
      'com.aparat.filimo',
      'app.rbmain.a',
    ];

    for (var pkg in iranianAppPackages) {
      if (!filterApp.contains(pkg)) {
        filterApp.add(pkg);
      }
    }

    await GetStorage().write('appFilter', filterApp);

    if (!await flutterV2ray.requestPermission()) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(LanguageConstants.home37.tr)));
      }
      return;
    }

    if (model.json == 'true') {
      config.text = model.link;
    } else if (model.fragment == 'true') {
      config.text = activeFragment(parser: V2ray.parseFromURL(model.link));
    } else {
      config.text = V2ray.parseFromURL(model.link).getFullConfiguration();
    }

    await flutterV2ray.startV2Ray(
      remark:
          '${languageController.isEN ? model.nameFa : model.nameEn} - ${languageController.isEN ? model.locationsFa : model.locationsEn}',
      config: config.text,
      proxyOnly: proxyOnly,
      blockedApps: filterApp,
      address: urlPost,
      time: timeData,
      data: {
        'method': 'usage',
        'username': Pref.username,
        'password': Pref.password,
        'idServer': model.serverId,
        'deviceIMEI': Pref.device,
        'deviceConfig': model.link,
        'usage': '{USAGE} MB',
      },
    );
  }

  void disconnectAndroid() async {
    V2rayManager.i.flutterV2ray.stopV2Ray();
    if (mounted) {
      setState(() {
        Pref.isConnect = false;
        toggleSwitchOff();
      });
    }
    await EasyLoading.dismiss();
  }

  void stopVpn() {
    V2rayManager.i.flutterV2ray.stopV2Ray();
    Pref.isConnect = false;
    if (mounted) setState(() => isSwitchedOn = false);
  }

  // ─── Account / Data ───────────────────────────────────────────────────────
  String accountNotif() {
    if (Pref.isActive) {
      if (Pref.isDay) {
        if (!Pref.isGB) {
          stopVpn();
          return LanguageConstants.home1.tr;
        }
        return LanguageConstants.home2.tr;
      } else {
        stopVpn();
        return LanguageConstants.home3.tr;
      }
    } else {
      stopVpn();
      return LanguageConstants.home4.tr;
    }
  }

  void _get() {
    getIsp();
    checkAccount();
    if (!mounted) return;
    setState(() {
      notstatus = Pref.not_status;
      time = Pref.date;
      username = Pref.username;
      password = Pref.password;
      pro = Pref.pro;
      sub = Pref.subscription;
      full = Pref.full;
      total = Pref.total;
      size = Pref.size;
      days = int.tryParse(Pref.days) ?? 0;
      active = Pref.status == 'active' && Pref.devicesStatus == 'active';
      status = active
          ? LanguageConstants.active.tr
          : LanguageConstants.deactivate.tr;
      permium = pro == 'Pro' || pro == 'Full';
    });
  }

  void _checkAccount() async {
    final req = http.Request('POST', Uri.parse(urlPost));
    req.headers['Content-Type'] = 'application/x-www-form-urlencoded';
    req.bodyFields = {
      ...ApiAuth.sign('delete'),
      'username': Pref.username,
      'method': 'delete'
    };
    try {
      final resp = await req.send();
      if (resp.statusCode == 200) {
        final data = json.decode(await resp.stream.bytesToString());
        if (data['result'] == 'false' && mounted) stopApp();
      }
    } catch (_) {}
  }

  void _checkUserNotification() {
    if (Pref.not_status == 'deactive') {
      setState(() => notBanner = false);
      return;
    }
    setState(() => notBanner = !Pref.not_users.contains('|${Pref.username}|'));
  }

  void notification() async {
    final req = http.Request('POST', Uri.parse(urlPost));
    req.headers['Content-Type'] = 'application/x-www-form-urlencoded';
    req.bodyFields = {
      ...ApiAuth.sign('notification'),
      'username': Pref.username,
      'method': 'notification'
    };
    try {
      final resp = await req.send();
      if (resp.statusCode == 200 && mounted) setState(() => notBanner = false);
    } catch (_) {}
  }

  void _checkVersion() {
    final upToDate = Platform.isAndroid
        ? Pref.version == version
        : Pref.versionWin == version;
    setState(() => updateBanner = !upToDate);
  }

  void checkAccount() async {
    try {
      final req = http.Request('POST', Uri.parse(urlPost));
      req.headers['Content-Type'] = 'application/x-www-form-urlencoded';
      req.bodyFields = {
        ...ApiAuth.sign('details'),
        'method': 'details',
        'deviceIMEI': Pref.device,
        'deviceModel': Pref.model,
        'deviceToken': Pref.token,
        'username': Pref.username,
        'password': Pref.password,
      };
      final resp = await req.send();
      if (resp.statusCode != 200) return;

      final data = json.decode(await resp.stream.bytesToString())
          as Map<String, dynamic>;
      final result = data['result'] as String? ?? '';

      void apply({bool gb = true, bool day = true, bool act = true}) {
        final u = data['users'] as Map<String, dynamic>? ?? {};
        final a = data['agents'] as Map<String, dynamic>? ?? {};
        Pref.pro = u['permium'] ?? '';
        Pref.size = u['size'] ?? '';
        Pref.full = u['full'] ?? '';
        Pref.total = u['total'] ?? '';
        Pref.multi = u['multi'] ?? '';
        Pref.status = u['active'] ?? '';
        Pref.devicesStatus = data['status'] ?? '';
        Pref.date = u['date'] ?? '';
        Pref.buy = a['buy'] ?? '';
        Pref.renew = a['renew'] ?? '';
        Pref.support = a['support'] ?? '';
        Pref.amozesh = a['amozesh'] ?? '';
        Pref.app_name = a['app'] ?? Pref.app_name;
        Pref.subscription = u['subscription'] ?? '';
        Pref.result = result;
        Pref.days = data['credit']?.toString() ?? '0';
        Pref.isLogin = true;
        Pref.isGB = gb;
        Pref.isDay = day;
        Pref.isActive = act;
        final devList = data['devices'];
        Pref.devices = (devList is Map && devList['devices'] is List)
            ? (devList['devices'] as List)
                .map<Map<String, dynamic>>(
                    (e) => Map<String, dynamic>.from(e as Map))
                .toList()
            : [];
        final reqList = data['requests'];
        Pref.deviceRequests = (reqList is List)
            ? reqList
                .map<Map<String, dynamic>>(
                    (e) => Map<String, dynamic>.from(e as Map))
                .toList()
            : [];
        if (mounted) setState(() {});
      }

      switch (result) {
        case 'success':
          apply();
          break;
        case 'EndGB':
          apply(gb: false);
          break;
        case 'EndDay':
          apply(day: false);
          break;
        case 'suspended':
          apply(act: false);
          break;
        case 'device_not_found':
          if (mounted) {
            _showError(LanguageConstants.login0.tr);
            stopApp();
          }
          break;
      }
    } catch (e) {
      log(e.toString());
    }
  }

  void logout() async {
    final req = http.Request('POST', Uri.parse(urlPost));
    req.headers['Content-Type'] = 'application/x-www-form-urlencoded';
    req.bodyFields = {
      ...ApiAuth.sign('logout'),
      'username': username,
      'password': password,
      'deviceIMEI': Pref.device,
      'method': 'logout',
    };
    try {
      final resp = await req.send();
      if (resp.statusCode == 200) {
        final data = json.decode(await resp.stream.bytesToString());
        if (data['result'] == 'true') {
          flutterV2ray.stopV2Ray();
          _showSuccess(LanguageConstants.home38.tr);
          stopApp();
        }
      }
    } catch (_) {
      _showError(LanguageConstants.login7.tr);
    }
  }

  Future<void> stopApp() async {
    _timer?.cancel();
    Pref.isConnect = false;
    Pref.username = '';
    Pref.password = '';
    Pref.isLogin = false;
    V2rayManager.i.flutterV2ray.stopV2Ray();
    if (!mounted) return;
    Navigator.of(context)
        .pushReplacement(MaterialPageRoute(builder: (_) => const LoginPage()));
  }

  void deleteAccount() {
    _showWarning(LanguageConstants.login1.tr);
    stopApp();
  }

  // ─── Switch / Auto-connect ────────────────────────────────────────────────
  Future<void> _loadInitialSwitchStatus() async {
    final saved = GetStorage().read('switchStatus') ?? false;
    if (mounted) setState(() => isSwitchedOn = isConnected && saved);
  }

  Future<void> loadSwitchStatus() async =>
      isSwitchedOn = GetStorage().read('switchStatus') ?? false;

  void _invalidateAttempt() => _attemptId++;

  void toggleSwitchOff() {
    _invalidateAttempt();
    _isAutoConnecting = false;
    if (Get.isDialogOpen == true) Get.back();
    GetStorage().write('switchStatus', false);
    isSwitchedOn = false;
    if (mounted) setState(() {});
  }

  Future<bool> _hasRealInternet() async {
    try {
      final r = await InternetAddress.lookup('varzesh3.com')
          .timeout(const Duration(seconds: 3));
      return r.isNotEmpty && r.first.rawAddress.isNotEmpty;
    } catch (_) {
      return false;
    }
  }

  void _showAutoConnectDialog(VoidCallback onCancel) {
    Get.dialog(
      WillPopScope(
        onWillPop: () async => false,
        child: Material(
          color: Colors.transparent,
          child: Center(
            child: _GlassCard(
              margin: const EdgeInsets.all(40),
              child: Stack(
                children: [
                  Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const SizedBox(
                          width: 36,
                          height: 36,
                          child: CircularProgressIndicator(
                              color: _kAccent, strokeWidth: 2.5)),
                      const SizedBox(height: 16),
                      Text(LanguageConstants.checkingTheFastestServer.tr,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                              color: Colors.white, fontSize: 13)),
                    ],
                  ),
                  Positioned(
                    top: -8,
                    right: -8,
                    child: IconButton(
                      icon: const Icon(Icons.close_rounded,
                          color: _kWhite70, size: 20),
                      onPressed: onCancel,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
      barrierDismissible: false,
    );
  }

  Future<Map<String, int>> _pingServers(
      List<VPNClass> servers, int attemptId) async {
    final results = <String, int>{};
    int idx = 0;

    Future<void> worker() async {
      while (true) {
        if (attemptId != _attemptId) return;
        if (idx >= servers.length) return;
        final s = servers[idx++];
        if (s.link.isEmpty) continue;
        try {
          final cfg = s.json == 'true'
              ? s.link
              : s.fragment == 'true'
                  ? activeFragment(parser: V2ray.parseFromURL(s.link))
                  : V2ray.parseFromURL(s.link).getFullConfiguration();
          final p = processNumber(await flutterV2ray
              .getServerDelay(config: cfg)
              .timeout(const Duration(seconds: 5), onTimeout: () => -1));
          if (attemptId != _attemptId) return;
          if (p > 0 && p < 100000) results[s.serverId] = p;
        } catch (_) {}
      }
    }

    await Future.wait(List.generate(6, (_) => worker()));
    return results;
  }

  VPNClass? _bestServer(List<VPNClass> servers, Map<String, int> pings) {
    VPNClass? best;
    int bestPing = 1 << 30;
    for (final s in servers) {
      final p = pings[s.serverId];
      if (p != null && p < bestPing) {
        bestPing = p;
        best = s;
      }
    }
    return best;
  }

  Future<void> onSwitchToggle(bool on) async {
    if (!_guardConnect()) return;
    final storage = GetStorage();
    final prev = isSwitchedOn;

    if (!on) {
      _invalidateAttempt();
      _isAutoConnecting = false;
      if (Get.isDialogOpen == true) Get.back();
      if (mounted) setState(() => isSwitchedOn = false);
      storage.write('switchStatus', false);
      return;
    }

    if (_isAutoConnecting || isConnected) return;
    _invalidateAttempt();
    final id = _attemptId;
    _isAutoConnecting = true;

    void cancel() {
      _invalidateAttempt();
      _isAutoConnecting = false;
      if (Get.isDialogOpen == true) Get.back();
      if (mounted) setState(() => isSwitchedOn = prev);
      storage.write('switchStatus', prev);
    }

    _showAutoConnectDialog(cancel);

    try {
      if (!await _hasRealInternet()) {
        if (Get.isDialogOpen == true) Get.back();
        _isAutoConnecting = false;
        if (mounted) setState(() => isSwitchedOn = prev);
        storage.write('switchStatus', prev);
        _showError(languageController.selectedLanguage == 'en_US'
            ? 'اینترنت در دسترس نیست'
            : 'No internet');
        return;
      }

      final servers =
          (storage.read('vpnServers') as List?)?.cast<VPNClass>() ?? [];
      if (servers.isEmpty) {
        if (Get.isDialogOpen == true) Get.back();
        _isAutoConnecting = false;
        if (mounted) setState(() => isSwitchedOn = false);
        storage.write('switchStatus', false);
        _showError(languageController.selectedLanguage == 'en_US'
            ? 'سرور یافت نشد'
            : 'No servers found');
        return;
      }

      final pings = await _pingServers(servers, id);
      if (id != _attemptId) return;

      final best = _bestServer(servers, pings);
      if (best == null) {
        if (Get.isDialogOpen == true) Get.back();
        _isAutoConnecting = false;
        if (mounted) setState(() => isSwitchedOn = false);
        storage.write('switchStatus', false);
        _showError(languageController.selectedLanguage == 'en_US'
            ? 'سرور مناسب یافت نشد'
            : 'No suitable server');
        return;
      }

      await Pref.setVpnClass(best);
      if (id != _attemptId) return;
      connectedAndroid(best);
      if (id != _attemptId) return;

      _isAutoConnecting = false;
      if (mounted) setState(() => isSwitchedOn = true);
      storage.write('switchStatus', true);
      if (Get.isDialogOpen == true) Get.back();

      Get.snackbar(
        '',
        '',
        snackPosition: SnackPosition.BOTTOM,
        colorText: Colors.white,
        backgroundColor: _kGreen.withOpacity(0.85),
        titleText: Text(LanguageConstants.autoBestLocation.tr,
            style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.bold,
                color: Colors.white)),
        messageText: Text(
          '${languageController.selectedLanguage == "en_US" ? best.nameFa : best.nameEn} ✅',
          style: const TextStyle(fontSize: 12, color: Colors.white),
        ),
      );
    } catch (_) {
      if (id != _attemptId) return;
      if (Get.isDialogOpen == true) Get.back();
      _isAutoConnecting = false;
      if (mounted) setState(() => isSwitchedOn = prev);
      storage.write('switchStatus', prev);
      _showError(languageController.selectedLanguage == 'en_US'
          ? 'خطا در اتصال'
          : 'Connection error');
    }
  }

  bool _guardConnect() {
    if (Pref.status != 'active' || Pref.devicesStatus != 'active') {
      _showError(LanguageConstants.home4.tr);
      return false;
    }
    if (Pref.result == 'EndDay') {
      _showInfo(LanguageConstants.home3.tr);
      return false;
    }
    if (Pref.result == 'EndGB') {
      _showInfo(LanguageConstants.home1.tr);
      return false;
    }
    if (Pref.result == 'suspended') {
      _showError(LanguageConstants.home4.tr);
      return false;
    }
    return true;
  }

  // ─── IP / ISP ─────────────────────────────────────────────────────────────
  void getIsp() async {
    final apis = [
      {'name': 'daneshvaran', 'url': 'http://ip.daneshvaran.ir/json'},
      {'name': 'parsdevelop', 'url': 'http://api.parsdevelop.com/ip'},
      {'name': 'httpbin', 'url': 'https://httpbin.org/ip'},
      {'name': 'ident', 'url': 'https://ident.me/json'},
    ];

    for (final api in apis) {
      try {
        final resp = await http
            .get(Uri.parse(api['url']!))
            .timeout(const Duration(seconds: 3));
        if (resp.statusCode == 200) {
          final data = json.decode(resp.body) as Map<String, dynamic>;
          final ipStr = _extractIP(data, api['name']!);
          if (ipStr.isNotEmpty && mounted) {
            setState(() {
              ip = IPDetails.fromJson({
                'query': ipStr,
                'country': '',
                'regionName': '',
                'city': '',
                'zip': '',
                'timezone': '',
                'isp': '',
              });
            });
            return;
          }
        }
      } catch (_) {
        continue;
      }
    }
  }

  String _extractIP(Map<String, dynamic> d, String api) {
    switch (api) {
      case 'daneshvaran':
      case 'parsdevelop':
      case 'ident':
        return d['ip'] ?? '';
      case 'httpbin':
        return d['origin'] ?? '';
      default:
        return d['ip'] ?? d['query'] ?? '';
    }
  }

  String abbreviateIP(String ip) {
    final parts = ip.split('.');
    if (parts.length >= 4) return '${parts[3]}.**.**${parts[0]}';
    return ip;
  }

  // ─── Permissions ──────────────────────────────────────────────────────────
  Future<void> checkNotificationPermission() async {
    if (!await Permission.notification.isGranted) {
      await Permission.notification.request();
    }
  }

  // ─── Toast helpers ────────────────────────────────────────────────────────
  void _showInfo(String msg) => CherryToast.info(
          title: _toastText(msg), backgroundColor: const Color(0xFF1C1C1E))
      .show(Get.context!);

  void _showError(String msg) {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final hasShown = GetStorage().read('hasShownShowcase') ?? false;

      if (!hasShown) {
        ShowCaseWidget.of(context).startShowCase([
          _menuKey,
          helpKey2,
        ]);
      }
    });
  }

  void _showSuccess(String msg) => CherryToast.success(
          title: _toastText(msg), backgroundColor: const Color(0xFF1C1C1E))
      .show(Get.context!);

  void _showWarning(String msg) => CherryToast.warning(
          title: _toastText(msg), backgroundColor: const Color(0xFF1C1C1E))
      .show(Get.context!);

  Text _toastText(String msg) => Text(
        msg,
        style: const TextStyle(
            color: Colors.white, fontFamily: 'sans', fontSize: 13),
        textDirection: languageController.selectedLanguage == 'fa_IR'
            ? TextDirection.rtl
            : TextDirection.ltr,
      );
}

// ─── Reusable GlassCard ───────────────────────────────────────────────────────
class _GlassCard extends StatelessWidget {
  final Widget child;
  final EdgeInsets padding;
  final EdgeInsets margin;
  final Color? borderColor;

  const _GlassCard({
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.margin = EdgeInsets.zero,
    this.borderColor,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      padding: padding,
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.05),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: borderColor ?? Colors.white.withOpacity(0.1),
          width: 1.2,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.3),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: child,
    );
  }
}

// ─── Connect Ring Painter ─────────────────────────────────────────────────────
class _ConnectRingPainter extends CustomPainter {
  final double progress;
  final bool isOn;

  _ConnectRingPainter({required this.progress, required this.isOn});

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = math.min(size.width, size.height) / 2 - 6;
    final color = isOn ? _kGreen : _kAccent;
    const n = 60;

    for (int i = 0; i < n; i++) {
      final angle = progress * math.pi * 2 + (i / n) * math.pi * 2;
      final len = (i % 6 == 0) ? 10.0 : 6.0;
      final p1 = Offset(center.dx + radius * math.cos(angle),
          center.dy + radius * math.sin(angle));
      final p2 = Offset(center.dx + (radius - len) * math.cos(angle),
          center.dy + (radius - len) * math.sin(angle));

      canvas.drawLine(
          p1,
          p2,
          Paint()
            ..color = color.withOpacity(isOn ? 0.85 : 0.35 + 0.5 * (i / n))
            ..strokeWidth = 2
            ..strokeCap = StrokeCap.round);
    }
  }

  @override
  bool shouldRepaint(_ConnectRingPainter old) =>
      old.progress != progress || old.isOn != isOn;
}

// ─── Animated Progress Bar (fixed: key-based reset, no spurious re-animation) ─
class _AnimatedProgressBar extends StatefulWidget {
  final double value;
  final Color color;

  const _AnimatedProgressBar({
    super.key,
    required this.value,
    required this.color,
  });

  @override
  State<_AnimatedProgressBar> createState() => _AnimatedProgressBarState();
}

class _AnimatedProgressBarState extends State<_AnimatedProgressBar>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _anim;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 900));
    _anim = Tween<double>(begin: 0, end: widget.value).animate(
        CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic));
    _controller.forward();
  }

  /// didUpdateWidget is NOT called when the key changes (widget is recreated),
  /// so we only need this for same-key value changes (none expected, but safe).
  @override
  void didUpdateWidget(covariant _AnimatedProgressBar old) {
    super.didUpdateWidget(old);
    if (old.value != widget.value) {
      _anim = Tween<double>(begin: old.value, end: widget.value).animate(
          CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic));
      _controller
        ..reset()
        ..forward();
    }
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _anim,
      builder: (_, __) {
        final glow = _anim.value > 0.8;
        return Container(
          decoration: glow
              ? BoxDecoration(boxShadow: [
                  BoxShadow(
                      color: widget.color.withOpacity(0.4),
                      blurRadius: 12,
                      spreadRadius: 1)
                ])
              : null,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: LinearProgressIndicator(
              value: _anim.value,
              minHeight: 6,
              backgroundColor: Colors.white.withOpacity(0.08),
              valueColor: AlwaysStoppedAnimation(widget.color),
            ),
          ),
        );
      },
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }
}

// ─── Pulsing warning dot ──────────────────────────────────────────────────────
class _PulsingDot extends StatefulWidget {
  final Color color;

  const _PulsingDot({required this.color});

  @override
  State<_PulsingDot> createState() => _PulsingDotState();
}

class _PulsingDotState extends State<_PulsingDot>
    with SingleTickerProviderStateMixin {
  late AnimationController _c;
  late Animation<double> _a;

  @override
  void initState() {
    super.initState();
    _c = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 900))
      ..repeat(reverse: true);
    _a = Tween<double>(begin: 0.3, end: 1.0)
        .animate(CurvedAnimation(parent: _c, curve: Curves.easeInOut));
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _a,
      builder: (_, __) => Container(
        width: 7,
        height: 7,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: widget.color.withOpacity(_a.value),
          boxShadow: [
            BoxShadow(
                color: widget.color.withOpacity(0.5 * _a.value), blurRadius: 6),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }
}

// ─── Progress Bar با انیمیشن راه‌راه ───────────────────────────────────────

class _StripedProgressBar extends StatefulWidget {
  final double value; // 0.0 → 1.0
  final Color color;
  final bool striped; // فعال برای حالت empty

  const _StripedProgressBar({
    super.key,
    required this.value,
    required this.color,
    this.striped = false,
  });

  @override
  State<_StripedProgressBar> createState() => _StripedProgressBarState();
}

class _StripedProgressBarState extends State<_StripedProgressBar>
    with SingleTickerProviderStateMixin {
  late AnimationController _ctrl;
  late Animation<double> _widthAnim;
  late Animation<double> _stripeAnim;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    );
    _widthAnim = Tween<double>(begin: 0, end: widget.value)
        .animate(CurvedAnimation(parent: _ctrl, curve: Curves.easeOut));
    _stripeAnim = Tween<double>(begin: 0, end: 1).animate(_ctrl);
    _ctrl.forward();
    if (widget.striped) _ctrl.repeat();
  }

  @override
  void didUpdateWidget(_StripedProgressBar old) {
    super.didUpdateWidget(old);
    if (old.value != widget.value || old.striped != widget.striped) {
      _widthAnim = Tween<double>(begin: old.value, end: widget.value)
          .animate(CurvedAnimation(parent: _ctrl, curve: Curves.easeOut));
      _ctrl
        ..reset()
        ..forward();
      if (widget.striped) _ctrl.repeat();
    }
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _ctrl,
      builder: (_, __) {
        return LayoutBuilder(builder: (_, constraints) {
          final w = constraints.maxWidth;
          return ClipRRect(
            borderRadius: BorderRadius.circular(6),
            child: SizedBox(
              height: 6,
              child: Stack(children: [
                // track
                Container(
                  width: w,
                  color: widget.color.withOpacity(0.15),
                ),
                // fill
                widget.striped
                    ? _StripePainter(
                        width: w,
                        color: widget.color,
                        offset: _stripeAnim.value,
                      )
                    : Container(
                        width: w * _widthAnim.value,
                        decoration: BoxDecoration(
                          color: widget.color,
                          borderRadius: BorderRadius.circular(6),
                        ),
                      ),
              ]),
            ),
          );
        });
      },
    );
  }
}

class _StripePainter extends StatelessWidget {
  final double width;
  final Color color;
  final double offset;

  const _StripePainter({
    required this.width,
    required this.color,
    required this.offset,
  });

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      size: Size(width, 6),
      painter: _StripeCustomPainter(color: color, offset: offset),
    );
  }
}

class _StripeCustomPainter extends CustomPainter {
  final Color color;
  final double offset;

  const _StripeCustomPainter({required this.color, required this.offset});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()..color = color.withOpacity(0.85);
    final bgPaint = Paint()..color = color.withOpacity(0.25);

    canvas.drawRect(Rect.fromLTWH(0, 0, size.width, size.height), bgPaint);

    const stripeW = 8.0;
    const gap = 6.0;
    final step = stripeW + gap;
    final shift = offset * step;

    for (double x = -step + shift; x < size.width + step; x += step) {
      final path = Path()
        ..moveTo(x, 0)
        ..lineTo(x + stripeW, 0)
        ..lineTo(x + stripeW - size.height * 0.6, size.height)
        ..lineTo(x - size.height * 0.6, size.height)
        ..close();
      canvas.drawPath(path, paint);
    }
  }

  @override
  bool shouldRepaint(_StripeCustomPainter old) =>
      old.offset != offset || old.color != color;
}
