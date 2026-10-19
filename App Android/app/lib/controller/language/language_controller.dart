import 'dart:ui';

import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';

class LanguageController extends GetxController {
  final box = GetStorage();
  bool isEN = false;
  bool isFA = false;
  // bool isRU = false;
  // bool isZH = false;

  String selectedLanguage = '';

  @override
  void onInit() {
    super.onInit();
    selectedLanguage = box.read('language') ?? 'en_US';
    _updateFlags();
  }

  void changeLanguage(String languageCode) {
    selectedLanguage = languageCode;
    box.write('language', languageCode);
    _updateFlags();
    updateLocale();
  }

  void _updateFlags() {
    isEN = selectedLanguage == 'en_US';
    isFA = selectedLanguage == 'fa_IR';
    // isRU = selectedLanguage == 'ru_RU';
    // isZH = selectedLanguage== 'zh_CN';
  }


  void updateLocale() {
    Get.updateLocale(Locale(selectedLanguage));
    Get.forceAppUpdate();
  }

  @override
  void onClose() {
    box.write('language', selectedLanguage);
    super.onClose();
  }
}
