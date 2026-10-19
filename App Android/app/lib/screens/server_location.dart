import 'dart:async';
import 'dart:convert';
import 'dart:developer';
import 'dart:ui';

import 'package:cached_network_image/cached_network_image.dart';
import 'package:cherry_toast/cherry_toast.dart';
import 'package:flutter/material.dart' hide ConnectionState;
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_v2ray_client/flutter_v2ray.dart';
import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';
import 'package:http/http.dart' as http;
import 'package:permission_handler/permission_handler.dart';

import '../controller/language/language_config.dart';
import '../controller/language/language_controller.dart';
import '../global.dart';
import '../helpers/api_auth.dart';
import '../helpers/pref.dart';
import '../class.dart';
import 'conectivity_screen.dart';

// ─── Theme (هم‌تم با splash و home) ──────────────────────────────────────────
const _kDeepBg = Color(0xFF050510);
const _kCard = Color(0xFF0D1B3E);
const _kAccent = Color(0xFF4F8EFF);
const _kAccent2 = Color(0xFF00C8FF);
const _kGreen = Color(0xFF00E0C6);
const _kOrange = Color(0xFFFF9F43);
const _kRed = Color(0xFFFF5F5F);
const _kWhite70 = Color(0xB3FFFFFF);

final _config = TextEditingController();

class ServerLocation extends ConsumerStatefulWidget {
  const ServerLocation({super.key});

  @override
  ConsumerState<ServerLocation> createState() => _ServerLocationState();
}

class _ServerLocationState extends ConsumerState<ServerLocation>
    with TickerProviderStateMixin {
  final List<VPNClass> _list = [];

  bool _online = false;
  bool _loaded = false;
  bool _wait = true;
  bool _isTesting = false;
  bool _end = false, _day = false, _sus = false;
  bool _shouldStop = false;

  String _search = '';
  String? _bestServerName;

  final LanguageController _lang = Get.find<LanguageController>();
  late AnimationController _bgCtrl;

  @override
  void initState() {
    super.initState();
    SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
    ));
    _bgCtrl = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 20),
    )..repeat(reverse: true);
    _init();
  }

  @override
  void dispose() {
    _shouldStop = true;
    _bgCtrl.dispose();
    EasyLoading.dismiss();
    super.dispose();
  }

  Future<void> _init() async {
    try {
      final resp = await fetchWithTimeout(urlPost, const Duration(seconds: 3));
      if (resp == null || resp.statusCode != 200) {
        _handleNetworkError();
      } else {
        await _loadServers();
      }
    } catch (_) {
      _handleNetworkError();
    }
  }

  // ─── BUILD ────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kDeepBg,
      body: Stack(children: [
        _buildBg(),
        if (_wait || !_loaded)
          _buildLoadingState()
        else if (!_online)
          _buildOfflineState()
        else
          _buildOnlineState(),
      ]),
    );
  }

  Widget _buildBg() => AnimatedBuilder(
        animation: _bgCtrl,
        builder: (_, __) => Container(
          decoration: const BoxDecoration(
            gradient: RadialGradient(
              center: Alignment(-0.2, -0.3),
              radius: 1.4,
              colors: [Color(0xFF0D1B4B), Color(0xFF050510), Color(0xFF000000)],
              stops: [0.0, 0.55, 1.0],
            ),
          ),
        ),
      );

  // ─── Loading ──────────────────────────────────────────────────────────────
  Widget _buildLoadingState() => SafeArea(
        child: Column(children: [
          _topBar(showRefresh: false),
          Expanded(
              child: Center(
                  child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 78,
                height: 78,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: _kAccent.withOpacity(0.08),
                  border: Border.all(color: _kAccent.withOpacity(0.25)),
                ),
                child: const Padding(
                  padding: EdgeInsets.all(20),
                  child: CircularProgressIndicator(
                      color: _kAccent, strokeWidth: 2.5),
                ),
              ),
              const SizedBox(height: 18),
              Text(LanguageConstants.server6.tr,
                  style: const TextStyle(
                      fontSize: 14, color: _kWhite70, letterSpacing: 0.4)),
              const SizedBox(height: 22),
              _GhostBtn(
                  label: LanguageConstants.back.tr,
                  onTap: () => Navigator.pop(context)),
            ],
          ))),
        ]),
      );

  // ─── Offline ──────────────────────────────────────────────────────────────
  Widget _buildOfflineState() => SafeArea(
        child: Column(children: [
          _topBar(),
          Expanded(
              child: Center(
                  child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: _kRed.withOpacity(0.08),
                  border: Border.all(color: _kRed.withOpacity(0.3)),
                ),
                child:
                    const Icon(Icons.wifi_off_rounded, color: _kRed, size: 34),
              ),
              const SizedBox(height: 14),
              Text(LanguageConstants.internetConnectionError.tr,
                  style: const TextStyle(color: _kWhite70, fontSize: 13)),
              const SizedBox(height: 20),
              _GhostBtn(
                label: LanguageConstants.tryAgain.tr,
                color: _kAccent,
                onTap: () {
                  setState(() {
                    _wait = true;
                    _loaded = false;
                  });
                  _init();
                },
              ),
            ],
          ))),
        ]),
      );

  // ─── Online ───────────────────────────────────────────────────────────────
  Widget _buildOnlineState() {
    final filtered = _list.where((s) {
      final q = _search.toLowerCase();
      return q.isEmpty ||
          (s.nameEn?.toLowerCase().contains(q) ?? false) ||
          (s.nameFa?.toLowerCase().contains(q) ?? false);
    }).toList();

    return SafeArea(
      child: Column(children: [
        _topBar(),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 0),
            child: Column(children: [
              const SizedBox(height: 10),
              _searchBar(),
              const SizedBox(height: 10),
              _pingAllBtn(),
              const SizedBox(height: 8),
              _autoConnectCard(),
              const SizedBox(height: 8),
              Expanded(child: _serverList(filtered)),
            ]),
          ),
        ),
      ]),
    );
  }

  // ─── Top Bar ──────────────────────────────────────────────────────────────
  Widget _topBar({bool showRefresh = true}) => Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
        child: Row(children: [
          _CircleBtn(
              icon: Icons.arrow_back_ios_new_rounded,
              onTap: () => Navigator.pop(context)),
          const Spacer(),
          Text(LanguageConstants.server1.tr,
              style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                  letterSpacing: 0.4)),
          const Spacer(),
          if (showRefresh)
            _CircleBtn(
                icon: Icons.refresh_rounded,
                onTap: () {
                  setState(() {
                    _loaded = false;
                    _online = false;
                    _wait = true;
                  });
                  _shouldStop = true;
                  Future.delayed(const Duration(milliseconds: 200), () {
                    _shouldStop = false;
                    _loadServers();
                  });
                })
          else
            const SizedBox(width: 38),
        ]),
      );

  // ─── Search ───────────────────────────────────────────────────────────────
  Widget _searchBar() => Container(
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.05),
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: Colors.white.withOpacity(0.09)),
        ),
        child: TextField(
          onChanged: (v) => setState(() => _search = v),
          style: const TextStyle(color: Colors.white, fontSize: 13),
          decoration: InputDecoration(
            hintText: LanguageConstants.search.tr,
            hintStyle: const TextStyle(color: _kWhite70, fontSize: 12),
            prefixIcon:
                const Icon(Icons.search_rounded, color: _kWhite70, size: 19),
            border: InputBorder.none,
            contentPadding: const EdgeInsets.symmetric(vertical: 12),
          ),
        ),
      );

  // ─── Ping All ─────────────────────────────────────────────────────────────
  Widget _pingAllBtn() => GestureDetector(
        onTap: _isTesting ? null : _pingAll,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: _kAccent.withOpacity(_isTesting ? 0.05 : 0.09),
            borderRadius: BorderRadius.circular(13),
            border: Border.all(
                color: _kAccent.withOpacity(_isTesting ? 0.15 : 0.4)),
          ),
          child: Center(
              child: _isTesting
                  ? Row(mainAxisSize: MainAxisSize.min, children: [
                      SizedBox(
                          width: 15,
                          height: 15,
                          child: CircularProgressIndicator(
                              color: _kAccent.withOpacity(0.7),
                              strokeWidth: 1.8)),
                      const SizedBox(width: 9),
                      Text(LanguageConstants.testAllServers.tr,
                          style: const TextStyle(
                              color: _kAccent,
                              fontSize: 12,
                              fontWeight: FontWeight.w600)),
                    ])
                  : Row(mainAxisSize: MainAxisSize.min, children: [
                      const Icon(Icons.network_check_rounded,
                          color: _kAccent, size: 17),
                      const SizedBox(width: 7),
                      Text(
                          _bestServerName ??
                              LanguageConstants.testAllServers.tr,
                          style:
                              TextStyle(
                                  color: _bestServerName != null
                                      ? _kGreen
                                      : _kAccent,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600)),
                    ])),
        ),
      );

  // ─── Auto Connect ─────────────────────────────────────────────────────────
  Widget _autoConnectCard() => GestureDetector(
        onTap: _startAutoConnect,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(15),
            gradient: LinearGradient(
              colors: [_kAccent.withOpacity(0.13), _kGreen.withOpacity(0.07)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            border: Border.all(color: _kAccent.withOpacity(0.22)),
          ),
          child: Directionality(
            textDirection: _lang.selectedLanguage == 'en_US'
                ? TextDirection.rtl
                : TextDirection.ltr,
            child: Row(children: [
              Container(
                padding: const EdgeInsets.all(7),
                decoration: BoxDecoration(
                  color: _kGreen.withOpacity(0.12),
                  shape: BoxShape.circle,
                  border: Border.all(color: _kGreen.withOpacity(0.3)),
                ),
                child: const Icon(Icons.auto_fix_high_rounded,
                    color: _kGreen, size: 16),
              ),
              const SizedBox(width: 11),
              Expanded(
                  child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(LanguageConstants.autoBestLocation.tr,
                      style: const TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w700,
                          color: Colors.white)),
                  Text(LanguageConstants.checkingTheFastestServer.tr,
                      style: const TextStyle(fontSize: 10, color: _kWhite70)),
                ],
              )),
              const Icon(Icons.arrow_forward_ios_rounded,
                  color: _kWhite70, size: 13),
            ]),
          ),
        ),
      );

  // ─── Server List ──────────────────────────────────────────────────────────
  Widget _serverList(List<VPNClass> servers) => ListView.builder(
        physics: const BouncingScrollPhysics(),
        itemCount: servers.length,
        itemBuilder: (_, i) => _ServerTile(
          server: servers[i],
          lang: _lang,
          onTap: () => _onTap(servers[i]),
        ),
      );

  // ─── Logic ────────────────────────────────────────────────────────────────
  Future<void> _loadServers() async {
    try {
      final req = http.Request('POST', Uri.parse(urlPost));
      req.headers['Content-Type'] = 'application/x-www-form-urlencoded';
      req.bodyFields = {
        ...ApiAuth.sign('getAServers'),
        'username': Pref.username,
        'password': Pref.password,
        'deviceIMEI': Pref.device,
        'method': 'getAServers',
      };
      final resp = await req.send();
      if (resp.statusCode != 200) {
        _handleNetworkError();
        return;
      }

      final data = json.decode(await resp.stream.bytesToString())
          as Map<String, dynamic>;
      if (data['result'] == 'success') {
        final confs = data['confs'] as List? ?? [];
        final list = <VPNClass>[];
        for (final s in confs) {
          if (s['status'] == 'true' && s['permium'] == Pref.pro) {
            list.add(_makeVPN(
              s,
              cryptoService.decryptConfig(s['src']) ?? '',
              cryptoService.decryptConfig(s['link']) ?? '',
            ));
          }
        }
        if (mounted)
          setState(() {
            _list
              ..clear()
              ..addAll(list);
            _online = _loaded = true;
            _wait = false;
          });
      } else {
        _handleResult(data['result'] as String? ?? '');
      }
    } catch (e) {
      log('_loadServers: $e');
      _handleNetworkError();
    }
  }

  VPNClass _makeVPN(Map<String, dynamic> s, String src, String link) =>
      VPNClass(
        serverId: s['id'],
        nameFa: s['name_fa'],
        nameEn: s['name_en'],
        src: src,
        locationsFa: s['locations_fa'],
        locationsEn: s['locations_en'],
        titleFa: s['title_fa'],
        titleEn: s['title_en'],
        link: link,
        permium: s['permium'],
        fragment: s['fragment'],
        json: s['json'],
        operator: s['oprator'],
        status: s['status'],
        date: '',
      );

  void _handleResult(String r) {
    final dummy = _makeVPN({
      'id': '0',
      'name_fa': '',
      'name_en': '',
      'title_fa': '',
      'title_en': '',
      'src': useR,
      'locations_fa': '',
      'locations_en': '',
      'permium': '',
      'fragment': '',
      'json': '',
      'oprator': '',
      'status': '',
      'date': '',
    }, useR, 'vless://...');
    switch (r) {
      case 'EndDay':
        _list.add(dummy.copyWith(
            nameFa: LanguageConstants.server7.tr,
            nameEn: LanguageConstants.server7.tr));
        if (mounted)
          setState(() {
            _day = true;
            _online = _loaded = false;
            _wait = false;
          });
        break;
      case 'EndGB':
        _list.add(dummy.copyWith(
            nameFa: LanguageConstants.server9.tr,
            nameEn: LanguageConstants.server9.tr));
        if (mounted)
          setState(() {
            _end = true;
            _online = _loaded = false;
            _wait = false;
          });
        break;
      case 'suspended':
        _list.add(dummy.copyWith(
            nameFa: LanguageConstants.server10.tr,
            nameEn: LanguageConstants.server10.tr));
        if (mounted)
          setState(() {
            _sus = true;
            _online = _loaded = false;
            _wait = false;
          });
        break;
    }
  }

  void _handleNetworkError() {
    if (!mounted) return;
    setState(() {
      _wait = _loaded = _online = false;
    });
    _showError(LanguageConstants.login7.tr);
  }

  // ✅ Parallel ping — همه سرورها همزمان (مثل V2RayNG)
  Future<void> _pingAll() async {
    if (_isTesting) return;
    setState(() {
      _isTesting = true;
      _bestServerName = null;
      _shouldStop = false;
      for (int i = 0; i < _list.length; i++) {
        _list[i] = _list[i].copyWith(ping: null);
      }
    });

    final v2ray = V2rayManager.i.flutterV2ray;
    await v2ray.requestPermission();

    // همه با هم — هر کدوم که تموم شد UI آپدیت میشه
    await Future.wait(
      List.generate(_list.length, (i) => _pingSingle(i, v2ray)),
      eagerError: false,
    );

    if (!mounted) return;

    _list.sort((a, b) {
      final pa = a.ping ?? 99999, pb = b.ping ?? 99999;
      if (pa < 0) return 1;
      if (pb < 0) return -1;
      return pa.compareTo(pb);
    });

    final best =
        _list.firstWhere((s) => (s.ping ?? -1) > 0, orElse: () => _list.first);

    setState(() {
      _isTesting = false;
      _bestServerName = _lang.isEN ? best.nameFa : best.nameEn;
    });

    Future.delayed(const Duration(seconds: 5), () {
      if (mounted) setState(() => _bestServerName = null);
    });
  }

  Future<void> _pingSingle(int i, V2ray v2ray) async {
    if (_shouldStop || i >= _list.length) return;
    try {
      final cfg = _buildCfg(_list[i]);
      final raw = await v2ray
          .getServerDelay(config: cfg)
          .timeout(const Duration(seconds: 6), onTimeout: () => -1);
      final p = raw == -1 ? -1 : raw ~/ 2;
      if (!mounted || _shouldStop || i >= _list.length) return;
      setState(() => _list[i] = _list[i].copyWith(ping: p));
    } catch (_) {
      if (mounted && !_shouldStop && i < _list.length) {
        setState(() => _list[i] = _list[i].copyWith(ping: -1));
      }
    }
  }

  String _buildCfg(VPNClass s) {
    if (s.json.toLowerCase() == 'true') return s.link;
    if (s.fragment == 'true')
      return activeFragment(parser: V2ray.parseFromURL(s.link));
    return V2ray.parseFromURL(s.link).getFullConfiguration();
  }

  // ─── Auto Connect ─────────────────────────────────────────────────────────
  Future<void> _startAutoConnect() async {
    await _loadServers();
    if (!mounted) return;
    for (int i = 0; i < _list.length; i++) {
      _list[i] = _list[i].copyWith(ping: null);
    }
    final note =
        ValueNotifier<String>(LanguageConstants.checkingTheFastestServer.tr);
    bool started = false;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => StatefulBuilder(builder: (ctx, setD) {
        if (!started) {
          started = true;
          _autoConnectPing(setD, ctx, note);
        }
        return _AutoConnectDialog(list: _list, note: note, lang: _lang);
      }),
    );
  }

  Future<void> _autoConnectPing(
      StateSetter setD, BuildContext ctx, ValueNotifier<String> note) async {
    final v2ray = V2rayManager.i.flutterV2ray;
    await v2ray.requestPermission();

    await Future.wait(
      List.generate(_list.length, (i) async {
        if (!ctx.mounted) return;
        final s = _list[i];
        note.value =
            '${LanguageConstants.checkingTheFastestServer.tr} ${s.nameEn ?? ""}';
        try {
          final raw = await v2ray
              .getServerDelay(config: _buildCfg(s))
              .timeout(const Duration(seconds: 6), onTimeout: () => -1);
          final p = raw == -1 ? -1 : raw ~/ 2;
          if (ctx.mounted) {
            _list[i] = _list[i].copyWith(ping: p);
            setD(() {});
          }
        } catch (_) {
          if (ctx.mounted) {
            _list[i] = _list[i].copyWith(ping: -1);
            setD(() {});
          }
        }
      }),
      eagerError: false,
    );

    if (!ctx.mounted) return;
    _list.sort((a, b) {
      final pa = a.ping ?? 99999, pb = b.ping ?? 99999;
      if (pa < 0) return 1;
      if (pb < 0) return -1;
      return pa.compareTo(pb);
    });
    setD(() {});

    final best =
        _list.firstWhere((s) => (s.ping ?? -1) > 0, orElse: () => _list.first);
    note.value =
        _lang.isEN ? (best.locationsFa ?? '') : (best.locationsEn ?? '');

    await Future.delayed(const Duration(seconds: 2));
    if (ctx.mounted) {
      Navigator.pop(ctx);
      _connectServer(best);
    }
  }

  // ─── Tap ──────────────────────────────────────────────────────────────────
  Future<void> _onTap(VPNClass s) async {
    if (_end) {
      _showInfo(LanguageConstants.home1.tr);
      return;
    }
    if (_day) {
      _showInfo(LanguageConstants.home3.tr);
      return;
    }
    if (_sus) {
      _showWarning(LanguageConstants.home4.tr);
      return;
    }

    GetStorage().write('switchStatus', false);
    EasyLoading.show(
      indicator: const SizedBox(
          width: 34,
          height: 34,
          child: CircularProgressIndicator(color: _kAccent, strokeWidth: 2.5)),
      dismissOnTap: false,
      maskType: EasyLoadingMaskType.black,
    );

    // quick ping
    try {
      final raw = await V2rayManager.i.flutterV2ray
          .getServerDelay(config: _buildCfg(s))
          .timeout(const Duration(seconds: 4), onTimeout: () => -1);
      final p = raw == -1 ? -1 : raw ~/ 2;
      final idx = _list.indexOf(s);
      if (idx >= 0 && mounted)
        setState(() => _list[idx] = _list[idx].copyWith(ping: p));
    } catch (_) {}

    _connectServer(s);
  }

  Future<void> _connectServer(VPNClass model) async {
    await EasyLoading.dismiss();
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

    if (!await Permission.notification.isGranted) {
      await Permission.notification.request();
    }

    final v2ray = V2rayManager.i.flutterV2ray;
    if (!await v2ray.requestPermission()) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(LanguageConstants.confirmAccess.tr)));
      }
      return;
    }

    _config.text = _buildCfg(model);
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

    await v2ray.startV2Ray(
      remark: '${_lang.isEN ? model.nameFa : model.nameEn}'
          ' - ${_lang.isEN ? model.locationsFa : model.locationsEn}',
      config: _config.text,
      proxyOnly: false,
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

    await Pref.setVpnClass(model);
    if (mounted) Navigator.pop(context);
  }

  // ─── Toasts ───────────────────────────────────────────────────────────────
  Text _toastText(String msg) => Text(
        msg,
        style: const TextStyle(
          color: Colors.white,
          fontFamily: 'sans',
          fontSize: 13,
        ),
        textDirection: languageController.selectedLanguage == 'fa_IR'
            ? TextDirection.rtl
            : TextDirection.ltr,
      );

  void _showInfo(String m) => CherryToast.info(
          title: _toastText(m), backgroundColor: const Color(0xFF1C1C1E))
      .show(Get.context!);

  void _showError(String m) => CherryToast.error(
          title: _toastText(m), backgroundColor: const Color(0xFF1C1C1E))
      .show(Get.context!);

  void _showWarning(String m) => CherryToast.warning(
          title: _toastText(m), backgroundColor: const Color(0xFF1C1C1E))
      .show(Get.context!);
}

// ─── Server Tile ──────────────────────────────────────────────────────────────
class _ServerTile extends StatelessWidget {
  final VPNClass server;
  final LanguageController lang;
  final VoidCallback onTap;

  const _ServerTile(
      {required this.server, required this.lang, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final isFa = lang.selectedLanguage == 'fa_IR';
    final name = isFa ? server.nameEn : server.nameFa;
    final loc = isFa ? server.locationsEn : server.locationsFa;
    final title = isFa ? server.titleEn : server.titleFa;

    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 7),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.04),
          borderRadius: BorderRadius.circular(15),
          border: Border.all(color: Colors.white.withOpacity(0.07)),
        ),
        child: Directionality(
          textDirection: isFa ? TextDirection.ltr : TextDirection.rtl,
          child: Row(children: [
            ClipOval(
                child: CachedNetworkImage(
              imageUrl: server.src ?? '',
              width: 40,
              height: 40,
              fit: BoxFit.cover,
              placeholder: (_, __) => Container(
                  width: 40,
                  height: 40,
                  color: _kCard,
                  child: const Icon(Icons.flag_outlined,
                      color: _kWhite70, size: 17)),
              errorWidget: (_, __, ___) => Container(
                  width: 40,
                  height: 40,
                  color: _kCard,
                  child: const Icon(Icons.flag_outlined,
                      color: _kWhite70, size: 17)),
            )),
            const SizedBox(width: 11),
            Expanded(
                child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name ?? '',
                    style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: Colors.white)),
                const SizedBox(height: 2),
                Text(loc ?? '',
                    style: const TextStyle(fontSize: 10, color: _kWhite70)),
              ],
            )),
            _PingBadge(ping: server.ping, title: title ?? ''),
          ]),
        ),
      ),
    );
  }
}

// ─── Ping Badge ───────────────────────────────────────────────────────────────
class _PingBadge extends StatelessWidget {
  final int? ping;
  final String title;

  const _PingBadge({this.ping, required this.title});

  @override
  Widget build(BuildContext context) {
    if (ping == null) {
      return Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
        const Icon(Icons.network_check_rounded, color: _kWhite70, size: 17),
        if (title.isNotEmpty)
          Text(title, style: const TextStyle(fontSize: 9, color: _kWhite70)),
      ]);
    }
    final isOff = ping! < 0;
    final isHigh = ping! > 300;
    final color = isOff ? _kRed : (isHigh ? _kOrange : _kGreen);
    final label = isOff ? 'offline' : '${ping} ms';

    return Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
      AnimatedContainer(
        duration: const Duration(milliseconds: 350),
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(7),
          border: Border.all(color: color.withOpacity(0.35)),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Container(
              width: 5,
              height: 5,
              decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
          const SizedBox(width: 4),
          Text(label,
              style: TextStyle(
                  fontSize: 10, color: color, fontWeight: FontWeight.w700)),
        ]),
      ),
      if (title.isNotEmpty)
        Text(title, style: const TextStyle(fontSize: 9, color: _kWhite70)),
    ]);
  }
}

// ─── Auto Connect Dialog ──────────────────────────────────────────────────────
class _AutoConnectDialog extends StatelessWidget {
  final List<VPNClass> list;
  final ValueNotifier<String> note;
  final LanguageController lang;

  const _AutoConnectDialog(
      {required this.list, required this.note, required this.lang});

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 32),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 16, sigmaY: 16),
          child: Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.06),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: _kAccent.withOpacity(0.22)),
            ),
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                Container(
                  padding: const EdgeInsets.all(7),
                  decoration: BoxDecoration(
                    color: _kGreen.withOpacity(0.1),
                    shape: BoxShape.circle,
                    border: Border.all(color: _kGreen.withOpacity(0.3)),
                  ),
                  child: const Icon(Icons.auto_fix_high_rounded,
                      color: _kGreen, size: 18),
                ),
                const SizedBox(width: 9),
                ValueListenableBuilder<String>(
                  valueListenable: note,
                  builder: (_, text, __) => Flexible(
                      child: Text(text,
                          style: const TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.w600))),
                ),
              ]),
              const SizedBox(height: 12),
              SizedBox(
                height: 300,
                child: ListView.builder(
                  physics: const BouncingScrollPhysics(),
                  itemCount: list.length,
                  itemBuilder: (_, i) {
                    final s = list[i];
                    final isBest = i == 0 && (s.ping ?? -1) > 0;
                    return AnimatedContainer(
                      duration: const Duration(milliseconds: 300),
                      margin: const EdgeInsets.only(bottom: 6),
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 8),
                      decoration: BoxDecoration(
                        color: isBest
                            ? _kGreen.withOpacity(0.1)
                            : Colors.white.withOpacity(0.03),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: isBest
                              ? _kGreen.withOpacity(0.35)
                              : Colors.white.withOpacity(0.07),
                        ),
                      ),
                      child: Row(children: [
                        ClipOval(
                            child: CachedNetworkImage(
                          imageUrl: s.src ?? '',
                          width: 28,
                          height: 28,
                          fit: BoxFit.cover,
                          errorWidget: (_, __, ___) => Container(
                              width: 28,
                              height: 28,
                              color: _kCard,
                              child: const Icon(Icons.flag_outlined,
                                  color: _kWhite70, size: 12)),
                        )),
                        const SizedBox(width: 9),
                        Expanded(
                            child: Text(
                                lang.isEN ? (s.nameFa ?? '') : (s.nameEn ?? ''),
                                style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w500))),
                        if (isBest)
                          Container(
                            margin: const EdgeInsets.only(right: 5),
                            padding: const EdgeInsets.symmetric(
                                horizontal: 5, vertical: 2),
                            decoration: BoxDecoration(
                              color: _kGreen.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(5),
                            ),
                            child: const Text('best',
                                style: TextStyle(
                                    color: _kGreen,
                                    fontSize: 9,
                                    fontWeight: FontWeight.w700)),
                          ),
                        s.ping == null
                            ? const SizedBox(
                                width: 14,
                                height: 14,
                                child: CircularProgressIndicator(
                                    strokeWidth: 1.6, color: _kAccent))
                            : _PingBadge(ping: s.ping, title: ''),
                      ]),
                    );
                  },
                ),
              ),
            ]),
          ),
        ),
      ),
    );
  }
}

// ─── Ghost Button ─────────────────────────────────────────────────────────────
class _GhostBtn extends StatelessWidget {
  final String label;
  final VoidCallback onTap;
  final Color color;

  const _GhostBtn(
      {required this.label, required this.onTap, this.color = _kWhite70});

  @override
  Widget build(BuildContext context) => GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 10),
          decoration: BoxDecoration(
            color: color.withOpacity(0.07),
            borderRadius: BorderRadius.circular(11),
            border: Border.all(color: color.withOpacity(0.3)),
          ),
          child: Text(label,
              style: TextStyle(
                  color: color, fontSize: 12, fontWeight: FontWeight.w600)),
        ),
      );
}

// ─── Circle Icon Button ───────────────────────────────────────────────────────
class _CircleBtn extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;

  const _CircleBtn({required this.icon, required this.onTap});

  @override
  Widget build(BuildContext context) => GestureDetector(
        onTap: onTap,
        child: Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: Colors.white.withOpacity(0.05),
            border: Border.all(color: Colors.white.withOpacity(0.1)),
          ),
          child: Icon(icon, color: Colors.white, size: 15),
        ),
      );
}
