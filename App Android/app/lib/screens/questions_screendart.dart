import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';

import '../controller/language/language_config.dart';
import '../controller/language/language_controller.dart';
import '../helpers/pref.dart';
import '../models/question_model.dart';
import '../models/status_response.dart';
import '../repositories/api_repository.dart';

// ─── Theme ────────────────────────────────────────────────────────────────────
const _kDeepBg = Color(0xFF050510);
const _kAccent = Color(0xFF4F8EFF);
const _kGreen = Color(0xFF00E0C6);
const _kRed = Color(0xFFFF5F5F);
const _kWhite70 = Color(0xB3FFFFFF);

class QuestionsScreen extends StatefulWidget {
  const QuestionsScreen({super.key});

  @override
  State<QuestionsScreen> createState() => _QuestionsScreenState();
}

final LanguageController languageController = Get.find<LanguageController>();

class _QuestionsScreenState extends State<QuestionsScreen>
    with TickerProviderStateMixin {
  ApiStatus _status = ApiStatus.loading;
  List<QuestionsModel> _list = [];

  late AnimationController _bgCtrl;
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
    _listCtrl = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 700));
    _load();
  }

  @override
  void dispose() {
    _bgCtrl.dispose();
    _listCtrl.dispose();
    super.dispose();
  }

  Future<void> _load({bool refresh = false}) async {
    setState(() => _status = refresh ? ApiStatus.refresh : ApiStatus.loading);
    final result = await ApiRepostiory1.getQuestions();
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
                child: Text(LanguageConstants.home28.tr,
                    style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                        letterSpacing: 0.4))),
            // Refresh
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
              border: Border.all(color: _kAccent.withOpacity(0.25)),
            ),
            child: const Padding(
              padding: EdgeInsets.all(16),
              child:
                  CircularProgressIndicator(color: _kAccent, strokeWidth: 2.5),
            ),
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
            child: const Icon(Icons.wifi_off_rounded, color: _kRed, size: 34),
          ),
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
                final delay = (i * 0.08).clamp(0.0, 0.85);
                final t = Curves.easeOutCubic.transform(
                    ((_listCtrl.value - delay) / (1 - delay)).clamp(0.0, 1.0));
                return Opacity(
                    opacity: t,
                    child: Transform.translate(
                        offset: Offset(0, 20 * (1 - t)), child: child));
              },
              child: _QuestionTile(item: _list[i], index: i),
            );
          },
        ),
      );
}

// ─── Question Tile ────────────────────────────────────────────────────────────
class _QuestionTile extends StatefulWidget {
  final QuestionsModel item;
  final int index;

  const _QuestionTile({required this.item, required this.index});

  @override
  State<_QuestionTile> createState() => _QuestionTileState();
}

class _QuestionTileState extends State<_QuestionTile>
    with SingleTickerProviderStateMixin {
  bool _expanded = false;
  late AnimationController _expandCtrl;

  @override
  void initState() {
    super.initState();
    _expandCtrl = AnimationController(
        vsync: this, duration: const Duration(milliseconds: 300));
  }

  @override
  void dispose() {
    _expandCtrl.dispose();
    super.dispose();
  }

  // icon color cycling per index
  Color get _accent {
    const cols = [_kAccent, _kGreen, Color(0xFFFF9F43), Color(0xFF8B5FFF)];
    return cols[widget.index % cols.length];
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.04),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: _expanded
              ? _accent.withOpacity(0.45)
              : Colors.white.withOpacity(0.08),
          width: 1.1,
        ),
        boxShadow: _expanded
            ? [
                BoxShadow(
                  color: _accent.withOpacity(0.1),
                  blurRadius: 16,
                  offset: const Offset(0, 4),
                )
              ]
            : [],
      ),
      child: Theme(
        data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          onExpansionChanged: (v) {
            setState(() => _expanded = v);
            v ? _expandCtrl.forward() : _expandCtrl.reverse();
          },
          backgroundColor: Colors.transparent,
          collapsedBackgroundColor: Colors.transparent,
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          collapsedShape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          tilePadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
          childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
          leading: AnimatedContainer(
            duration: const Duration(milliseconds: 300),
            padding: const EdgeInsets.all(7),
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: _accent.withOpacity(_expanded ? 0.18 : 0.1),
              border: Border.all(
                  color: _accent.withOpacity(_expanded ? 0.5 : 0.25)),
            ),
            child: Icon(Icons.help_outline_rounded, color: _accent, size: 16),
          ),
          title: Text(widget.item.title,
              style: TextStyle(
                  color:
                      _expanded ? Colors.white : Colors.white.withOpacity(0.88),
                  fontWeight: _expanded ? FontWeight.w700 : FontWeight.w500,
                  fontSize: 13)),
          trailing: AnimatedRotation(
            turns: _expanded ? 0.25 : 0,
            duration: const Duration(milliseconds: 300),
            child: Icon(Icons.arrow_forward_ios_rounded,
                color: _expanded ? _accent : _kWhite70, size: 14),
          ),
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: _accent.withOpacity(0.05),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: _accent.withOpacity(0.15)),
              ),
              child: Text(widget.item.text,
                  style: const TextStyle(
                      color: _kWhite70, fontSize: 13, height: 1.6)),
            ),
          ],
        ),
      ),
    );
  }
}
