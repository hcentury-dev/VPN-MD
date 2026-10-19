import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';
import 'package:installed_apps/app_info.dart';
import 'package:installed_apps/installed_apps.dart';

import '../helpers/pref.dart';

// ─── Theme ────────────────────────────────────────────────────────────────────
const _kDeepBg = Color(0xFF050510);
const _kCard = Color(0xFF0D1B3E);
const _kAccent = Color(0xFF4F8EFF);
const _kGreen = Color(0xFF00E0C6);
const _kRed = Color(0xFFFF5F5F);
const _kWhite70 = Color(0xB3FFFFFF);

// ─── Loading phases ───────────────────────────────────────────────────────────
enum _Phase { fetching, streaming, done }

class SettingScreen extends StatefulWidget {
  const SettingScreen({super.key});

  @override
  State<SettingScreen> createState() => _SettingScreenState();
}

class _SettingScreenState extends State<SettingScreen>
    with TickerProviderStateMixin {
  final _box = GetStorage();

  final List<AppInfo> _userApps = [];
  final List<AppInfo> _systemApps = [];
  List<AppInfo> _filteredUser = [];
  List<AppInfo> _filteredSystem = [];

  _Phase _phase = _Phase.fetching;
  int _loaded = 0;
  int _total = 0;
  String _loadMsg = '';

  late TabController _tabCtrl;
  final _searchCtrl = TextEditingController();

  late AnimationController _bgCtrl;
  late AnimationController _ringCtrl;
  late AnimationController _fadeCtrl;

  final String _myPkg = packageName;

  @override
  void initState() {
    super.initState();
    SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
      systemNavigationBarColor: _kDeepBg,
    ));

    _tabCtrl = TabController(length: 2, vsync: this);
    _bgCtrl =
        AnimationController(vsync: this, duration: const Duration(seconds: 18))
          ..repeat(reverse: true);
    _ringCtrl =
        AnimationController(vsync: this, duration: const Duration(seconds: 2))
          ..repeat();
    _fadeCtrl = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 500));

    // ✅ kick off in next frame so the loading UI renders first
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadApps());
  }

  @override
  void dispose() {
    _tabCtrl.dispose();
    _searchCtrl.dispose();
    _bgCtrl.dispose();
    _ringCtrl.dispose();
    _fadeCtrl.dispose();
    super.dispose();
  }

  // ─── Load ─────────────────────────────────────────────────────────────────
  Future<void> _loadApps() async {
    // Phase 1: fetch raw lists (slow IO – runs in isolate inside the plugin)
    // UI shows spinning ring with 0% during this time – no freeze
    final results = await Future.wait([
      InstalledApps.getInstalledApps(true, true), // user
      InstalledApps.getInstalledApps(false, true), // all
    ]);

    if (!mounted) return;

    final userApps = results[0];
    final allApps = results[1];
    final userSet = userApps.map((a) => a.packageName).toSet();
    final sysApps =
        allApps.where((a) => !userSet.contains(a.packageName)).toList();

    final saved =
        (_box.read('appFilter') as List<dynamic>?)?.cast<String>() ?? [];

    _total = userApps.length + sysApps.length;
    if (_total == 0) _total = 1;

    // Phase 2: stream into UI one by one
    setState(() => _phase = _Phase.streaming);

    for (final app in userApps) {
      if (!mounted) return;
      await Future.delayed(const Duration(milliseconds: 6));
      _userApps.add(app);
      if (app.packageName == _myPkg || saved.contains(app.packageName)) {
        _box.write('selected_${app.packageName}', true);
      }
      _loaded++;
      if (mounted) setState(() => _loadMsg = _trim(app.name));
    }

    for (final app in sysApps) {
      if (!mounted) return;
      await Future.delayed(const Duration(milliseconds: 3));
      _systemApps.add(app);
      if (app.packageName == _myPkg || saved.contains(app.packageName)) {
        _box.write('selected_${app.packageName}', true);
      }
      _loaded++;
      if (mounted) setState(() => _loadMsg = _trim(app.name));
    }

    // Persist
    final selectedPkgs = {
      ...[..._userApps, ..._systemApps]
          .where((a) => _box.read('selected_${a.packageName}') != null)
          .map((a) => a.packageName),
      _myPkg,
    }.toList();
    _box.write('appFilter', selectedPkgs);

    if (!mounted) return;
    setState(() {
      _filteredUser = List.from(_userApps);
      _filteredSystem = List.from(_systemApps);
      _phase = _Phase.done;
    });
    _fadeCtrl.forward();
  }

  String _trim(String s) => s.length > 24 ? '${s.substring(0, 24)}…' : s;

  // ─── Toggle ───────────────────────────────────────────────────────────────
  void _toggle(AppInfo app) {
    if (app.packageName == _myPkg) return;
    final key = 'selected_${app.packageName}';
    if (_box.read(key) != null)
      _box.remove(key);
    else
      _box.write(key, true);
    final sel = {
      ...[..._userApps, ..._systemApps]
          .where((a) => _box.read('selected_${a.packageName}') != null)
          .map((a) => a.packageName),
      _myPkg,
    };
    _box.write('appFilter', sel.toList());
    setState(() {});
  }

  // ─── Search ───────────────────────────────────────────────────────────────
  void _filter(String q) {
    final qL = q.toLowerCase();
    setState(() {
      _filteredUser = qL.isEmpty
          ? List.from(_userApps)
          : _userApps
              .where((a) =>
                  a.name.toLowerCase().contains(qL) ||
                  a.packageName.toLowerCase().contains(qL))
              .toList();
      _filteredSystem = qL.isEmpty
          ? List.from(_systemApps)
          : _systemApps
              .where((a) =>
                  a.name.toLowerCase().contains(qL) ||
                  a.packageName.toLowerCase().contains(qL))
              .toList();
    });
  }

  // ─── BUILD ────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kDeepBg,
      body: Stack(children: [
        _buildBg(),
        Column(children: [
          _buildTopBar(),
          Expanded(
              child: _phase == _Phase.done
                  ? _buildContent()
                  : _buildLoadingView()),
        ]),
      ]),
    );
  }

  // ─── Background ───────────────────────────────────────────────────────────
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

  // ─── Top bar ──────────────────────────────────────────────────────────────
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
              child: Text('filter_apps'.tr,
                  style: const TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                      letterSpacing: 0.4)),
            ),
            if (_phase == _Phase.done)
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: _kAccent.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: _kAccent.withOpacity(0.35)),
                ),
                child: Row(mainAxisSize: MainAxisSize.min, children: [
                  const Icon(Icons.shield_outlined, color: _kAccent, size: 13),
                  const SizedBox(width: 4),
                  Text(_selectedCount().toString(),
                      style: const TextStyle(
                          color: _kAccent,
                          fontSize: 12,
                          fontWeight: FontWeight.w700)),
                ]),
              ),
          ]),
        ),
      );

  int _selectedCount() => [..._userApps, ..._systemApps]
      .where((a) => _box.read('selected_${a.packageName}') != null)
      .length;

  // ─── Loading view ─────────────────────────────────────────────────────────
  Widget _buildLoadingView() {
    final pct = _total > 0 ? _loaded / _total : 0.0;
    // During "fetching" phase, pulse the ring smoothly with no progress
    final displayPct = _phase == _Phase.fetching ? null : pct;

    return Center(
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        // Ring
        AnimatedBuilder(
          animation: _ringCtrl,
          builder: (_, __) => SizedBox(
            width: 130,
            height: 130,
            child: CustomPaint(
              painter: _RingPainter(
                progress: displayPct,
                spin: _ringCtrl.value,
              ),
              child: Center(
                child: _phase == _Phase.fetching
                    ? const Icon(Icons.apps_rounded, color: _kAccent, size: 32)
                    : Column(mainAxisSize: MainAxisSize.min, children: [
                        Text('${(pct * 100).toInt()}%',
                            style: const TextStyle(
                                fontSize: 26,
                                fontWeight: FontWeight.w800,
                                color: Colors.white)),
                        Text('$_loaded/$_total',
                            style: const TextStyle(
                                fontSize: 10, color: _kWhite70)),
                      ]),
              ),
            ),
          ),
        ),

        const SizedBox(height: 26),

        // App name ticker — only during streaming
        AnimatedOpacity(
          opacity: _phase == _Phase.streaming ? 1.0 : 0.0,
          duration: const Duration(milliseconds: 300),
          child: Container(
            width: 260,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.05),
              borderRadius: BorderRadius.circular(11),
              border: Border.all(color: _kAccent.withOpacity(0.18)),
            ),
            child: Row(children: [
              const SizedBox(
                  width: 14,
                  height: 14,
                  child: CircularProgressIndicator(
                      strokeWidth: 1.6, color: _kAccent)),
              const SizedBox(width: 9),
              Expanded(
                child: AnimatedSwitcher(
                  duration: const Duration(milliseconds: 200),
                  child: Text(
                    _loadMsg,
                    key: ValueKey(_loadMsg),
                    style: const TextStyle(fontSize: 12, color: _kWhite70),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ),
            ]),
          ),
        ),

        const SizedBox(height: 14),

        // Segmented bar
        AnimatedOpacity(
          opacity: _phase == _Phase.streaming ? 1.0 : 0.0,
          duration: const Duration(milliseconds: 300),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 48),
            child: _SegmentedBar(progress: pct),
          ),
        ),
      ]),
    );
  }

  // ─── Main content ─────────────────────────────────────────────────────────
  Widget _buildContent() => FadeTransition(
        opacity: _fadeCtrl,
        child: Column(children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 4, 14, 0),
            child: Container(
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.05),
                borderRadius: BorderRadius.circular(13),
                border: Border.all(color: Colors.white.withOpacity(0.09)),
              ),
              child: TextField(
                controller: _searchCtrl,
                onChanged: _filter,
                style: const TextStyle(color: Colors.white, fontSize: 13),
                decoration: InputDecoration(
                  hintText: 'search_app'.tr,
                  hintStyle: const TextStyle(color: _kWhite70, fontSize: 12),
                  prefixIcon: const Icon(Icons.search_rounded,
                      color: _kWhite70, size: 19),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 8, 14, 4),
            child: Container(
              height: 40,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.05),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white.withOpacity(0.08)),
              ),
              child: TabBar(
                controller: _tabCtrl,
                indicator: BoxDecoration(
                  color: _kAccent.withOpacity(0.18),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: _kAccent.withOpacity(0.4)),
                ),
                indicatorSize: TabBarIndicatorSize.tab,
                labelColor: Colors.white,
                unselectedLabelColor: _kWhite70,
                labelStyle:
                    const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                tabs: [Tab(text: 'user_apps'.tr), Tab(text: 'system_apps'.tr)],
              ),
            ),
          ),
          Expanded(
            child: TabBarView(
              controller: _tabCtrl,
              children: [
                _buildList(_filteredUser),
                _buildList(_filteredSystem)
              ],
            ),
          ),
        ]),
      );

  Widget _buildList(List<AppInfo> apps) {
    if (apps.isEmpty) {
      return Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withOpacity(0.05),
              border: Border.all(color: Colors.white.withOpacity(0.1)),
            ),
            child: const Icon(Icons.search_off_rounded,
                color: _kWhite70, size: 32),
          ),
          const SizedBox(height: 12),
          Text('not_found'.tr,
              style: const TextStyle(color: _kWhite70, fontSize: 14)),
        ]),
      );
    }

    return ListView.builder(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(14, 4, 14, 20),
      itemCount: apps.length,
      itemBuilder: (_, i) => _AppTile(
        app: apps[i],
        isMyApp: apps[i].packageName == _myPkg,
        isSelected: apps[i].packageName == _myPkg ||
            _box.read('selected_${apps[i].packageName}') != null,
        onTap: () => _toggle(apps[i]),
      ),
    );
  }
}

// ─── App Tile ─────────────────────────────────────────────────────────────────
class _AppTile extends StatelessWidget {
  final AppInfo app;
  final bool isMyApp, isSelected;
  final VoidCallback onTap;

  const _AppTile(
      {required this.app,
      required this.isMyApp,
      required this.isSelected,
      required this.onTap});

  @override
  Widget build(BuildContext context) {
    final borderColor = isMyApp
        ? _kRed.withOpacity(0.45)
        : isSelected
            ? _kGreen.withOpacity(0.45)
            : Colors.white.withOpacity(0.07);
    final bgColor = isMyApp
        ? _kRed.withOpacity(0.07)
        : isSelected
            ? _kGreen.withOpacity(0.07)
            : Colors.white.withOpacity(0.04);
    final nameColor = isMyApp ? _kRed : Colors.white;
    final subColor = isMyApp ? _kRed.withOpacity(0.55) : _kWhite70;

    return GestureDetector(
      onTap: isMyApp ? null : onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsets.only(bottom: 7),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
        decoration: BoxDecoration(
          color: bgColor,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: borderColor, width: 1.1),
        ),
        child: Row(children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(9),
            child: app.icon != null
                ? Image.memory(app.icon!,
                    width: 40,
                    height: 40,
                    fit: BoxFit.cover,
                    color: isMyApp ? _kRed.withOpacity(0.5) : null,
                    colorBlendMode: isMyApp ? BlendMode.modulate : null)
                : Container(
                    width: 40,
                    height: 40,
                    color: _kCard,
                    child:
                        Icon(Icons.apps_rounded, color: nameColor, size: 22)),
          ),
          const SizedBox(width: 12),
          Expanded(
              child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(app.name,
                  style: TextStyle(
                      color: nameColor,
                      fontSize: 13,
                      fontWeight: FontWeight.w600),
                  overflow: TextOverflow.ellipsis),
              const SizedBox(height: 2),
              Text(app.packageName,
                  style: TextStyle(color: subColor, fontSize: 10),
                  overflow: TextOverflow.ellipsis),
            ],
          )),
          AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: isMyApp
                  ? _kRed.withOpacity(0.12)
                  : isSelected
                      ? _kGreen.withOpacity(0.12)
                      : Colors.white.withOpacity(0.05),
              border: Border.all(
                color: isMyApp
                    ? _kRed.withOpacity(0.3)
                    : isSelected
                        ? _kGreen.withOpacity(0.3)
                        : Colors.white.withOpacity(0.1),
              ),
            ),
            child: Icon(
              isMyApp
                  ? Icons.lock_rounded
                  : isSelected
                      ? Icons.shield_rounded
                      : Icons.shield_outlined,
              size: 15,
              color: isMyApp
                  ? _kRed
                  : isSelected
                      ? _kGreen
                      : _kWhite70,
            ),
          ),
        ]),
      ),
    );
  }
}

// ─── Ring Painter ─────────────────────────────────────────────────────────────
/// [progress] null  → indeterminate pulse (fetching phase)
/// [progress] 0..1  → determinate fill   (streaming phase)
class _RingPainter extends CustomPainter {
  final double? progress;
  final double spin;

  _RingPainter({required this.progress, required this.spin});

  @override
  void paint(Canvas canvas, Size size) {
    final c = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2 - 8;
    final rect = Rect.fromCircle(center: c, radius: radius);

    // Track
    canvas.drawCircle(
        c,
        radius,
        Paint()
          ..color = Colors.white.withOpacity(0.07)
          ..style = PaintingStyle.stroke
          ..strokeWidth = 7);

    if (progress == null) {
      // ── Indeterminate: spinning arc (60°) ─────────────────────────────
      const sweep = math.pi / 3;
      final start = spin * 2 * math.pi - math.pi / 2;
      canvas.drawArc(
          rect,
          start,
          sweep,
          false,
          Paint()
            ..shader = SweepGradient(
              startAngle: start,
              endAngle: start + sweep,
              colors: [_kAccent.withOpacity(0.2), _kAccent],
            ).createShader(rect)
            ..style = PaintingStyle.stroke
            ..strokeWidth = 7
            ..strokeCap = StrokeCap.round);
    } else {
      // ── Determinate: filled arc ────────────────────────────────────────
      final sweep = 2 * math.pi * progress!.clamp(0.0, 1.0);
      const start = -math.pi / 2;
      canvas.drawArc(
          rect,
          start,
          sweep,
          false,
          Paint()
            ..shader = SweepGradient(
              startAngle: start,
              endAngle: start + math.max(sweep, 0.01),
              colors: const [_kAccent, _kGreen],
            ).createShader(rect)
            ..style = PaintingStyle.stroke
            ..strokeWidth = 7
            ..strokeCap = StrokeCap.round);

      // Glowing tip dot
      if (progress! > 0.01) {
        final tip = start + sweep;
        final tx = c.dx + radius * math.cos(tip);
        final ty = c.dy + radius * math.sin(tip);
        canvas.drawCircle(
            Offset(tx, ty),
            5,
            Paint()
              ..color = _kGreen
              ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 4));
        canvas.drawCircle(Offset(tx, ty), 3, Paint()..color = Colors.white);
      }
    }

    // Particle dots around outer edge (always spinning)
    const n = 48;
    for (int i = 0; i < n; i++) {
      final angle = spin * 2 * math.pi + (i / n) * 2 * math.pi;
      final r2 = radius + 14;
      final px = c.dx + r2 * math.cos(angle);
      final py = c.dy + r2 * math.sin(angle);
      final sz = i % 6 == 0 ? 2.5 : 1.2;
      final op = 0.10 + 0.18 * math.sin(i / n * math.pi);
      canvas.drawCircle(
          Offset(px, py), sz, Paint()..color = _kAccent.withOpacity(op));
    }
  }

  @override
  bool shouldRepaint(_RingPainter old) =>
      old.progress != progress || old.spin != spin;
}

// ─── Segmented Bar ────────────────────────────────────────────────────────────
class _SegmentedBar extends StatelessWidget {
  final double progress;

  const _SegmentedBar({required this.progress});

  @override
  Widget build(BuildContext context) {
    const n = 20;
    final filled = (progress * n).floor();
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(n, (i) {
        final active = i < filled;
        final isTip = i == filled;
        return AnimatedContainer(
          duration: const Duration(milliseconds: 120),
          margin: const EdgeInsets.symmetric(horizontal: 1.5),
          width: 8,
          height: active || isTip ? 10 : 6,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(3),
            color: active
                ? Color.lerp(_kAccent, _kGreen, i / n)!
                : isTip
                    ? _kAccent.withOpacity(0.35)
                    : Colors.white.withOpacity(0.09),
            boxShadow: active
                ? [
                    BoxShadow(
                      color: Color.lerp(_kAccent, _kGreen, i / n)!
                          .withOpacity(0.4),
                      blurRadius: 4,
                    )
                  ]
                : null,
          ),
        );
      }),
    );
  }
}
