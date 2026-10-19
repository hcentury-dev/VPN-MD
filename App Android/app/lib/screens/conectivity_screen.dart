import 'package:flutter/material.dart';
import 'package:cross_connectivity/cross_connectivity.dart';
import 'package:get/get.dart';

import '../controller/language/language_controller.dart';
import '../helpers/pref.dart';

final LanguageController languageController = Get.find<LanguageController>();

class ConectivityScreen extends StatelessWidget {
  final Widget child;

  const ConectivityScreen({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    return Material(
      child: Column(
        children: <Widget>[
          ConnectivityBuilder(
            builder: (context, isConnected, status) => Expanded(
              child: IgnorePointer(
                ignoring: isConnected == false,
                child: child,
              ),
            ),
          ),
          ConnectivityBuilder(
            builder: (context, isConnected, status) => Container(
              height: isConnected == true ? 50.0 : 0.0,
              color: isConnected == true ? banColorBg : Colors.transparent,
              child: isConnected == true
                  ? Center(
                      child: Row(
                        textDirection:
                            languageController.selectedLanguage == 'fa_IR'
                                ? TextDirection.ltr
                                : TextDirection.rtl,
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(
                            Icons.error_rounded,
                            color: Colors.white,
                          ),
                          const SizedBox(width: 5),
                          Text(
                            'خطا در دسترسی به اینترنت',
                            style: Theme.of(context)
                                .textTheme
                                .titleMedium
                                ?.copyWith(
                                    color: Colors.white,
                                    fontSize: 16,
                                    fontFamily: 'sans'),
                          ),
                        ],
                      ),
                    )
                  : null,
            ),
          ),
        ],
      ),
    );
  }
}
