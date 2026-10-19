import 'dart:math' as math;
import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:persian_number_utility/persian_number_utility.dart';

import '../controller/language/language_config.dart';
import '../controller/language/language_controller.dart';
import '../helpers/pref.dart';
import '../models/news_models.dart';
import '../models/status_response.dart';
import '../repositories/api_repository.dart';

// ─── Theme ────────────────────────────────────────────────────────────────────
const _kDeepBg = Color(0xFF050510);
const _kCard = Color(0xFF0D1B3E);
const _kAccent = Color(0xFF4F8EFF);
const _kGreen = Color(0xFF00E0C6);
const _kOrange = Color(0xFFFF9F43);
const _kRed = Color(0xFFFF5F5F);
const _kWhite70 = Color(0xB3FFFFFF);

class NewsScreen extends StatefulWidget {
  const NewsScreen({super.key});

  @override
  State<NewsScreen> createState() => _NewsScreenState();
}

final LanguageController languageController = Get.find<LanguageController>();

class _NewsScreenState extends State<NewsScreen> with TickerProviderStateMixin {
  ApiStatus _status = ApiStatus.loading;
  List<NewsModel> _list = [];

  late AnimationController _bgCtrl;
  late AnimationController _rgbCtrl; // for important news RGB border
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
    _rgbCtrl =
        AnimationController(vsync: this, duration: const Duration(seconds: 3))
          ..repeat();
    _listCtrl = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 700));

    Pref.count = '0';
    _load();
  }

  @override
  void dispose() {
    _bgCtrl.dispose();
    _rgbCtrl.dispose();
    _listCtrl.dispose();
    super.dispose();
  }

  Future<void> _load({bool refresh = false}) async {
    setState(() => _status = refresh ? ApiStatus.refresh : ApiStatus.loading);
    final result = await ApiRepostiory1.getNews();
    if (!mounted) return;
    if (result == null) {
      setState(() => _status = ApiStatus.error);
      return;
    }
    _list = result;
    setState(() => _status = ApiStatus.sucess);
    _listCtrl.forward(from: 0);
  }

  // ─── BUILD ────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kDeepBg,
      body: Stack(children: [
        _buildBg(),
        Directionality(
          textDirection: languageController.selectedLanguage == 'fa_IR'
              ? TextDirection.ltr
              : TextDirection.rtl,
          child: Column(children: [
            _buildTopBar(),
            Expanded(child: _buildBody()),
          ]),
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
                child: Text(LanguageConstants.newspaper.tr,
                    style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                        letterSpacing: 0.4))),
            GestureDetector(
              onTap: () => _load(refresh: true),
              child: Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: _kAccent.withOpacity(0.1),
                  border: Border.all(color: _kAccent.withOpacity(0.3)),
                ),
                child: const Icon(Icons.refresh_rounded,
                    color: _kAccent, size: 18),
              ),
            ),
          ]),
        ),
      );

  Widget _buildBody() {
    switch (_status) {
      case ApiStatus.loading:
        return _buildLoading();
      case ApiStatus.refresh:
        return _buildLoading();
      case ApiStatus.error:
        return _buildError();
      case ApiStatus.sucess:
        return _buildList();
    }
  }

  Widget _buildLoading() => Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: _kAccent.withOpacity(0.08),
                border: Border.all(color: _kAccent.withOpacity(0.25))),
            child: const Padding(
                padding: EdgeInsets.all(16),
                child: CircularProgressIndicator(
                    color: _kAccent, strokeWidth: 2.5)),
          ),
          const SizedBox(height: 14),
          Text(LanguageConstants.loading.tr,
              style: const TextStyle(color: _kWhite70, fontSize: 13)),
        ]),
      );

  Widget _buildError() => Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: _kRed.withOpacity(0.08),
                  border: Border.all(color: _kRed.withOpacity(0.25))),
              child:
                  const Icon(Icons.wifi_off_rounded, color: _kRed, size: 34)),
          const SizedBox(height: 14),
          Text(LanguageConstants.empty.tr,
              style: const TextStyle(color: _kWhite70, fontSize: 13)),
          const SizedBox(height: 18),
          GestureDetector(
            onTap: _load,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 10),
              decoration: BoxDecoration(
                color: _kAccent.withOpacity(0.12),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: _kAccent.withOpacity(0.4)),
              ),
              child: Text(LanguageConstants.refresh.tr,
                  style: const TextStyle(
                      color: _kAccent,
                      fontSize: 13,
                      fontWeight: FontWeight.w600)),
            ),
          ),
        ]),
      );

  Widget _buildList() => RefreshIndicator(
        color: _kAccent,
        backgroundColor: const Color(0xFF1C1C2E),
        onRefresh: () => _load(refresh: true),
        child: ListView.builder(
          physics: const BouncingScrollPhysics(
              parent: AlwaysScrollableScrollPhysics()),
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
          itemCount: _list.length,
          itemBuilder: (_, i) {
            return AnimatedBuilder(
              animation: _listCtrl,
              builder: (_, child) {
                final delay = (i * 0.07).clamp(0.0, 0.85);
                final t = Curves.easeOutCubic.transform(
                    ((_listCtrl.value - delay) / (1 - delay)).clamp(0.0, 1.0));
                return Opacity(
                    opacity: t,
                    child: Transform.translate(
                        offset: Offset(0, 22 * (1 - t)), child: child));
              },
              child: _NewsCard(
                item: _list[i],
                index: i,
                rgbCtrl: _rgbCtrl,
                lang: languageController,
              ),
            );
          },
        ),
      );
}

// ─── News Card ────────────────────────────────────────────────────────────────
class _NewsCard extends StatelessWidget {
  final NewsModel item;
  final int index;
  final AnimationController rgbCtrl;
  final LanguageController lang;

  const _NewsCard({
    required this.item,
    required this.index,
    required this.rgbCtrl,
    required this.lang,
  });

  bool get _important => int.tryParse(item.show) == 1;

  String get _title =>
      lang.selectedLanguage == 'fa_IR' ? item.title_en : item.title_fa;

  String get _text =>
      lang.selectedLanguage == 'fa_IR' ? item.text_en : item.text_fa;

  String get _date =>
      lang.selectedLanguage == 'fa_IR' ? item.date : item.date.toPersianDate();

  @override
  Widget build(BuildContext context) {
    if (_important) {
      return AnimatedBuilder(
        animation: rgbCtrl,
        builder: (_, child) => _wrapRGB(context, child!),
        child: _cardInner(context),
      );
    }
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        color: Colors.white.withOpacity(0.0),
        border: Border.all(color: _kAccent.withOpacity(0.22), width: 1.1),
        boxShadow: [BoxShadow(color: _kAccent.withOpacity(0.0), blurRadius: 1)],
      ),
      child: _cardInner(context),
    );
  }

  Widget _wrapRGB(BuildContext context, Widget child) {
    final hue = (rgbCtrl.value * 360) % 360;

    final c1 = HSLColor.fromAHSL(1, hue, 1, 0.6).toColor();
    final c2 = HSLColor.fromAHSL(1, (hue + 60) % 360, 1, 0.6).toColor();
    final c3 = HSLColor.fromAHSL(1, (hue + 120) % 360, 1, 0.6).toColor();

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(2), // ضخامت RGB
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: LinearGradient(
          colors: [c1, c2, c3, c1],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 15, sigmaY: 15),
          child: Container(
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(20),
              color: const Color(0xFF0B1A2A).withOpacity(0.6),
              // سرمه‌ای شیشه‌ای
              border: Border.all(
                color: Colors.white.withOpacity(0.08),
                width: 1,
              ),
            ),
            child: child,
          ),
        ),
      ),
    );
  }

  Widget _cardInner(BuildContext context) {
    return GestureDetector(
      onTap: () => _showDetail(context),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color:
              _important ? Colors.white.withOpacity(0.1) : Colors.transparent,
          borderRadius: BorderRadius.circular(18),
        ),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          // ─── Icon ───────────────────────────────────────────────────
          Container(
            padding: const EdgeInsets.all(7),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: (_important ? _kOrange : _kAccent).withOpacity(0.4),
              border: Border.all(
                  color: (_important ? _kOrange : _kAccent).withOpacity(0.1)),
            ),
            child: Icon(
              _important ? Icons.priority_high_rounded : Icons.article_rounded,
              color: _important ? _kOrange : _kAccent,
              size: 18,
            ),
          ),

          const SizedBox(width: 10),

          // ─── Text ───────────────────────────────────────────────────
          Expanded(
              child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (_important)
                Container(
                  margin: const EdgeInsets.only(bottom: 6),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: _kOrange.withOpacity(0.12),
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(color: _kOrange.withOpacity(0.3)),
                  ),
                  child: const Text('مهم',
                      style: TextStyle(
                          fontSize: 9,
                          color: _kOrange,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 0.5)),
                ),
              Text(_title,
                  style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: Colors.white),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis),
              const SizedBox(height: 5),
              Text(_text,
                  style: const TextStyle(
                      fontSize: 12, color: _kWhite70, height: 2.4),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis),
              const SizedBox(height: 7),
              Row(children: [
                const Icon(Icons.schedule_rounded, size: 10, color: _kWhite70),
                const SizedBox(width: 4),
                Text(_date,
                    style: const TextStyle(fontSize: 10, color: _kWhite70)),
              ]),
            ],
          )),

          // ─── Arrow ──────────────────────────────────────────────────
          const Padding(
            padding: EdgeInsets.only(top: 2),
            child:
                Icon(Icons.chevron_right_rounded, color: _kWhite70, size: 20),
          ),
        ]),
      ),
    );
  }

  void _showDetail(BuildContext context) {
    showDialog(
      context: context,
      builder: (_) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 40),
        child: Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: const Color(0xFF10142A),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
                color: (_important ? _kOrange : _kAccent).withOpacity(0.35)),
            boxShadow: [
              BoxShadow(
                  color: (_important ? _kOrange : _kAccent).withOpacity(0.15),
                  blurRadius: 30)
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Title row
              Row(children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color:
                          (_important ? _kOrange : _kAccent).withOpacity(0.12),
                      border: Border.all(
                          color: (_important ? _kOrange : _kAccent)
                              .withOpacity(0.3))),
                  child: Icon(
                      _important
                          ? Icons.priority_high_rounded
                          : Icons.article_rounded,
                      color: _important ? _kOrange : _kAccent,
                      size: 16),
                ),
                const SizedBox(width: 10),
                Expanded(
                    child: Text(_title,
                        style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                            color: Colors.white))),
              ]),
              const SizedBox(height: 14),
              Divider(color: Colors.white.withOpacity(0.1)),
              const SizedBox(height: 12),
              Text(_text,
                  style: const TextStyle(
                      fontSize: 13, color: _kWhite70, height: 1.7)),
              const SizedBox(height: 16),
              Row(children: [
                const Icon(Icons.schedule_rounded, size: 12, color: _kWhite70),
                const SizedBox(width: 4),
                Text(_date,
                    style: const TextStyle(fontSize: 11, color: _kWhite70)),
                const Spacer(),
                GestureDetector(
                  onTap: () => Navigator.pop(context),
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    decoration: BoxDecoration(
                      color: _kAccent.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: _kAccent.withOpacity(0.35)),
                    ),
                    child: Text(LanguageConstants.close.tr,
                        style: const TextStyle(
                            color: _kAccent,
                            fontSize: 12,
                            fontWeight: FontWeight.w600)),
                  ),
                ),
              ]),
            ],
          ),
        ),
      ),
    );
  }
}
