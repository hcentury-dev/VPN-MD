import 'dart:ui';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';
class HelperService {
  static final HelperService _instance = HelperService._internal();
  factory HelperService() => _instance;
  HelperService._internal();

  final GetStorage _storage = GetStorage();
  OverlayEntry? _overlayEntry;
  int _currentIndex = 0;
  late List<Map<String, dynamic>> _helpSteps;
  late String _pageName;
  late BuildContext _context;
  bool _isAnimating = false;

  bool checkFirstShowHelpBox(String pageName, {bool forceShow = false}) {
    if (forceShow) return true;
    return _storage.read<bool>("help_shown_$pageName") ?? true;
  }

  void showHelpBox({
    required BuildContext context,
    required String pageName,
    required List<Map<String, dynamic>> helpSteps,
    bool forceShow = false,
  }) {
    if (!checkFirstShowHelpBox(pageName, forceShow: forceShow)) return;

    _currentIndex = 0;
    _helpSteps = helpSteps;
    _pageName = pageName;
    _context = context;

    _showOverlay(context);
  }

  void _showOverlay(BuildContext context) {
    final overlay = Overlay.of(context);
    if (overlay == null) {
      debugPrint("Overlay is null. Make sure the context is inside a valid MaterialApp.");
      return;
    }

    if (_overlayEntry?.mounted ?? false) {
      _overlayEntry?.remove();
    }

    final step = _helpSteps[_currentIndex];
    final Offset position = step["position"];

    _overlayEntry = OverlayEntry(
      builder: (context) => HelpOverlayWidget(
        key: ValueKey(_currentIndex),
        position: position,
        step: step,
        onNext: () async {
          if (_isAnimating) return;
          _isAnimating = true;
          await Future.delayed(const Duration(milliseconds: 300));
          if (_currentIndex < _helpSteps.length - 1) {
            _currentIndex++;
            _showOverlay(context);
          } else {
            _overlayEntry?.remove();
            _storage.write("help_shown_$_pageName", false);
          }
          _isAnimating = false;
        },
        isLastStep: _currentIndex == _helpSteps.length - 1,
        helpStepsLength: _helpSteps.length,
        currentIndex: _currentIndex,
        pageName: _pageName,
      ),
    );

    overlay.insert(_overlayEntry!);
  }

  void resetHelpStatus(String pageName) {
    _storage.remove("help_shown_$pageName");
  }
}

class HelpOverlayWidget extends StatefulWidget {
  final Offset position;
  final Map<String, dynamic> step;
  final VoidCallback onNext;
  final bool isLastStep;
  final int helpStepsLength;
  final int currentIndex;
  final String pageName;

  const HelpOverlayWidget({
    Key? key,
    required this.position,
    required this.step,
    required this.onNext,
    required this.isLastStep,
    required this.helpStepsLength,
    required this.currentIndex,
    required this.pageName,
  }) : super(key: key);

  @override
  State<HelpOverlayWidget> createState() => _HelpOverlayWidgetState();
}

class _HelpOverlayWidgetState extends State<HelpOverlayWidget> with TickerProviderStateMixin {
  late AnimationController _titleAnimationController;
  late Animation<int> _titleTextAnimation;
  late AnimationController _textAnimationController;
  late Animation<int> _helperTextAnimation;
  late AnimationController _buttonFadeController;
  late Animation<double> _buttonFadeAnimation;
  String titleText = "";
  String helperText = "";

  @override
  void initState() {
    super.initState();

    // Title Animation
    _titleAnimationController = AnimationController(
      duration: Duration(milliseconds: 800),
      vsync: this,
    );
    _titleTextAnimation = IntTween(begin: 0, end: (widget.step["title"] as String).length)
        .animate(CurvedAnimation(parent: _titleAnimationController, curve: Curves.easeIn));

    _textAnimationController = AnimationController(
      duration: Duration(milliseconds: 1200),
      vsync: this,
    );
    _helperTextAnimation = IntTween(begin: 0, end: (widget.step["text"] as String).length)
        .animate(CurvedAnimation(parent: _textAnimationController, curve: Curves.easeIn));

    _buttonFadeController = AnimationController(
      duration: const Duration(milliseconds: 500),
      vsync: this,
    );
    _buttonFadeAnimation = Tween<double>(begin: 0.0, end: 1.0)
        .animate(CurvedAnimation(parent: _buttonFadeController, curve: Curves.easeInOut));

    startAnimations();
  }

  @override
  void didUpdateWidget(covariant HelpOverlayWidget oldWidget) {
    super.didUpdateWidget(oldWidget);

    // If the current index has changed, reset and start animations
    if (widget.currentIndex != oldWidget.currentIndex) {
      resetAnimations();
      startAnimations();
    }
  }

  void startAnimations() {
    _titleAnimationController.forward(from: 0);
    _titleTextAnimation.addListener(() {
      setState(() {
        titleText = (widget.step["title"] as String).substring(0, _titleTextAnimation.value);
      });
    });
    _titleAnimationController.addStatusListener((status) {
      if (status == AnimationStatus.completed) {
        _textAnimationController.forward(from: 0);
      }
    });
    _helperTextAnimation.addListener(() {
      setState(() {
        helperText = (widget.step["text"] as String).substring(0, _helperTextAnimation.value);
      });
    });

    _textAnimationController.addStatusListener((status) {
      if (status == AnimationStatus.completed) {
        _buttonFadeController.forward(from: 0);
      }
    });
  }

  void resetAnimations() {
    _titleAnimationController.reset();
    _textAnimationController.reset();
    _buttonFadeController.reset();
    titleText = "";
    helperText = "";
  }


  @override
  void dispose() {
    _titleAnimationController.dispose();
    _textAnimationController.dispose();
    _buttonFadeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        Positioned(
          left: widget.position.dx,
          top: widget.position.dy,
          child: Material(
            elevation: 0,
            color: Colors.transparent,
            child: TweenAnimationBuilder<double>(
              tween: Tween<double>(begin: 0.90, end: .95),
              duration: const Duration(milliseconds: 500),
              curve: Curves.easeInOut,
              builder: (context, scale, child) {
                return Transform.scale(
                  scale: scale,
                  child: child,
                );
              },
              child: Stack(
                alignment: Alignment.center,
                children: [
                  Positioned.fill(
                    child:      ClipRRect(


                      borderRadius: BorderRadius.circular(8),
                      child: BackdropFilter(filter: ImageFilter.blur(
                        sigmaY: 25,
                        sigmaX: 25,
                      ),
                        child: Container(
                            width: MediaQuery.of(context).size.width,
                            padding: const EdgeInsets.all(18),

                      
                        ),
                      ),
                    ),
                  ),

                  Container(
                    width: MediaQuery.of(context).size.width,
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.7),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          titleText,
                          style: const TextStyle(
                            fontSize: 17,
                            fontFamily: 'ShabnamBold',
                          ),
                          textAlign: TextAlign.start,
                          textDirection: TextDirection.rtl,
                        ),
                        const SizedBox(height: 5),
                        Text(
                          helperText,
                          style: TextStyle(
                            fontSize: 14,
                            fontFamily: 'ShabnamLight',
                            color: Colors.black.withOpacity(0.7),
                            height: 1.7,
                          ),
                          textAlign: TextAlign.start,
                          textDirection: TextDirection.rtl,
                        ),
                        const SizedBox(height: 8),
                        Align(
                          alignment: Alignment.centerRight,
                          child: GestureDetector(
                            onTap: widget.onNext,

                            child: Row(
                              children: [
                                Text(
                                  '${widget.helpStepsLength} / ${widget.currentIndex + 1}',
                                  style: TextStyle(color: Colors.orange),
                                ),
                                const Spacer(),
                                FadeTransition(
                                    opacity: _buttonFadeAnimation,
                                    child: Text(widget.isLastStep ? "بستن" : "بعدی" ,style: TextStyle(
                                      color: Colors.orange,
                                      fontSize: 15
                                    ),)),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}