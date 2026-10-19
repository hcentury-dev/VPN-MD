import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';
import '../helpers/pref.dart';
import '../controller/language/language_controller.dart';

class LanguageSheet extends StatelessWidget {
  const LanguageSheet({super.key});

  @override
  Widget build(BuildContext context) {
    var storage = GetStorage();
    var controller = Get.find<LanguageController>();

    List<Map<String, String>> languages = [
      {'code': 'en_US', 'label': '🇮🇷 فارسی'},
      {'code': 'fa_IR', 'label': '🇺🇸 English'},
    ];

    return AnimatedContainer(
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeOut,
      height: 70.0 * languages.length,
      width: double.infinity,
      decoration: const BoxDecoration(
        color: Color(0xFF0F2027),
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(18),
          topRight: Radius.circular(18),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black54,
            blurRadius: 10,
            offset: Offset(0, -3),
          )
        ],
      ),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: languages.length,
        itemBuilder: (context, index) {
          String langCode = languages[index]['code']!;
          String langLabel = languages[index]['label']!;
          bool isSelected = controller.selectedLanguage == langCode;

          return Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 300),
              curve: Curves.easeInOut,
              height: 55,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                gradient: isSelected
                    ? const LinearGradient(
                  colors: [Colors.cyanAccent, Colors.blueAccent],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                )
                    : null,
                border: Border.all(color: Colors.white24, width: 1.5),
                color: isSelected ? null : Colors.transparent,
              ),
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  borderRadius: BorderRadius.circular(12),
                  onTap: () {
                    storage.write('firstCheck', true);
                    controller.changeLanguage(langCode);
                    Get.back();
                  },
                  child: Center(
                    child: Text(
                      langLabel,
                      style: TextStyle(
                        color: isSelected ? Colors.black : Colors.white,
                        fontWeight:
                        isSelected ? FontWeight.bold : FontWeight.w500,
                        fontSize: 14,
                      ),
                    ),
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
