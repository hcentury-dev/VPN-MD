import 'dart:async';
import 'dart:convert';
import 'dart:developer';
import 'dart:io';
import 'dart:math' show Random;

import 'package:device_info_plus/device_info_plus.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';
import 'package:http/http.dart' as http;
import 'package:showcaseview/showcaseview.dart';

import 'Login.dart';
import 'device_helper.dart';
import 'controller/language/language_config.dart';
import 'controller/language/language_controller.dart';
import 'global.dart';
import 'helpers/pref.dart';
import 'screens/splash.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  FlutterError.onError = (details) {
    FlutterError.presentError(details);
    log("🔴 FlutterError: ${details.exception}", stackTrace: details.stack);
  };

  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: kBgColor,
    statusBarBrightness: Brightness.light,
    statusBarIconBrightness: Brightness.dark,
    systemNavigationBarColor: locColor,
  ));

  _configureEasyLoading();
  await _initCritical();

  runApp(
    ProviderScope(
      child: ShowCaseWidget(
        builder: (context) => const MyApp(),
      ),
    ),
  );

  WidgetsBinding.instance.addPostFrameCallback((_) {
    unawaited(_bootstrapNonBlocking());
  });
}

Future<void> _initCritical() async {
  final sw = Stopwatch()..start();
  log("🟢 Critical init start");

  try {
    await GetStorage.init().timeout(const Duration(seconds: 2));
    log("✅ GetStorage.init: ${sw.elapsedMilliseconds}ms");
  } catch (e, s) {
    log("⚠️ GetStorage.init failed: $e", stackTrace: s);
  }

  try {
    await Pref.initializeHive().timeout(const Duration(seconds: 4));
    log("✅ Pref.initializeHive: ${sw.elapsedMilliseconds}ms");
  } catch (e, s) {
    log("⚠️ Pref.initializeHive failed: $e", stackTrace: s);
  }

  log("🎉 Critical init done: ${sw.elapsedMilliseconds}ms");
}

Future<void> _bootstrapNonBlocking() async {
  log("🟢 Bootstrap start");

  unawaited(_safeRun("fetchData", () async {
    await fetchData().timeout(const Duration(seconds: 3));
  }));

  unawaited(_safeRun("push", () async {
    await _setupPushServiceNonBlocking();
  }));

  unawaited(_safeRun("v2ray", () async {
    await V2rayManager.i.flutterV2ray
        .initialize()
        .timeout(const Duration(seconds: 6));
  }));

  log("🎉 Bootstrap triggered");
}

Future<void> _safeRun(String tag, Future<void> Function() fn) async {
  try {
    await fn();
    log("✅ $tag done");
  } catch (e, s) {
    log("⚠️ $tag error: $e", stackTrace: s);
  }
}

void _configureEasyLoading() {
  EasyLoading.instance
    ..indicatorType = EasyLoadingIndicatorType.fadingCircle
    ..loadingStyle = EasyLoadingStyle.dark
    ..radius = 56
    ..backgroundColor = kBgColor
    ..maskColor = Colors.amber
    ..dismissOnTap = false;
}

// شناسه‌های غیریکتا/نامعتبر که نباید به‌عنوان هویت دستگاه فرستاده شوند.
// androidInfo.id همان Build.ID است و روی همه‌ی گوشی‌های یک بیلد یکسان است؛
// 9774d56d682e549c هم مقدار باگ‌دار شناخته‌شده‌ی ANDROID_ID است.
bool _isValidDeviceId(String? id) {
  if (id == null) return false;
  final v = id.trim();
  if (v.length < 8) return false;
  final bad = {'9774d56d682e549c', 'unknown', 'unknown device id', '0'};
  return !bad.contains(v.toLowerCase());
}

// یک UUID تصادفی ماندگار می‌سازد و در Hive ذخیره می‌کند تا در نبودِ
// ANDROID_ID هم هر دستگاه یک هویت یکتای پایدار داشته باشد.
String _persistentFallbackId() {
  final existing = Pref.deviceUuid;
  if (_isValidDeviceId(existing)) return existing;
  final rnd = Random.secure();
  final hex = List.generate(32, (_) => rnd.nextInt(16).toRadixString(16)).join();
  final uuid = 'app-$hex';
  Pref.deviceUuid = uuid;
  return uuid;
}

Future<String?> getDeviceId() async {
  if (Platform.isAndroid) {
    // ANDROID_ID یکتا به‌ازای دستگاه است (برخلاف Build.ID). اگر در دسترس
    // نبود، به UUID ماندگار محلی فال‌بک می‌کنیم — هرگز Build.ID نمی‌فرستیم.
    final androidId = await DeviceIdHelper.getUniqueDeviceId();
    if (_isValidDeviceId(androidId)) return androidId!.trim();
    return _persistentFallbackId();
  } else if (Platform.isIOS) {
    final iosInfo = await DeviceInfoPlugin().iosInfo;
    final vendorId = iosInfo.identifierForVendor;
    if (_isValidDeviceId(vendorId)) return vendorId!.trim();
    return _persistentFallbackId();
  }
  return _persistentFallbackId();
}

Future<void> _setupPushServiceNonBlocking() async {
  try {
    final deviceInfoPlugin = DeviceInfoPlugin();
    final deviceId = await getDeviceId();

    if (Platform.isAndroid) {
      final androidInfo = await deviceInfoPlugin.androidInfo;
      Pref.model = "${androidInfo.brand}-${androidInfo.model}";
    } else {
      Pref.model = "Unknown Model";
    }

    // مهاجرت بی‌درز: اگر کاربر از نسخه‌ی قبلی اپ شناسه‌ای دارد (که با Build.ID
    // ثبت شده)، همان را نگه می‌داریم تا سرور دستگاهش را پیدا کند و از حساب
    // خارج نشود. فقط نصب‌های تازه (device خالی) شناسه‌ی جدید ANDROID_ID می‌گیرند.
    if (Pref.device.isEmpty) {
      Pref.device =
          _isValidDeviceId(deviceId) ? deviceId!.trim() : _persistentFallbackId();
    }

    log("🟠 Device ID: ${Pref.device}");
    log("🟠 Model: ${Pref.model}");

    await Firebase.initializeApp().timeout(const Duration(seconds: 4));
    log("✅ Firebase.initializeApp");

    final messaging = FirebaseMessaging.instance;

    await _requestNotificationPermissionIfNeeded();

    try {
      final token =
          await messaging.getToken().timeout(const Duration(seconds: 20));
      Pref.token = (token != null && token.isNotEmpty) ? token : "Empty token";
      log("🟠 Token: ${Pref.token}");
    } catch (e) {
      Pref.token = "Token error";
      log("⚠️ getToken error: $e");
    }

    messaging.onTokenRefresh.listen((newToken) {
      Pref.token = newToken;
      log("🟢 Token refreshed: $newToken");
    });
  } catch (e, s) {
    Pref.token = "Push init error";
    log("🔴 setupPushService error: $e", stackTrace: s);
  }
}

Future<void> _requestNotificationPermissionIfNeeded() async {
  if (!Platform.isAndroid) return;

  final info = await DeviceInfoPlugin().androidInfo;
  final sdk = info.version.sdkInt;
  if (sdk < 33) return;

  final s = GetStorage();
  if (s.read('notif_perm_requested') == true) return;

  await Future.delayed(const Duration(seconds: 2));

  try {
    await FirebaseMessaging.instance.requestPermission();
    s.write('notif_perm_requested', true);
    log("✅ Notification permission requested (Android 13+)");
  } catch (e) {
    log("⚠️ Notification permission request failed: $e");
  }
}

Future<void> fetchData() async {
  final storage = GetStorage();
  // آدرس‌ها از pref.dart (بخش SERVER CONNECTION POINTS) می‌آیند و به ترتیب امتحان
  // می‌شوند؛ اولین منبعی که `address` معتبر بدهد ذخیره می‌شود. اگر همه قطع بودند،
  // کانفیگِ قبلیِ ذخیره‌شده دست‌نخورده می‌ماند و اپ کرش نمی‌کند.
  for (final url in configBootstrapUrls) {
    try {
      final response =
          await http.get(Uri.parse(url)).timeout(const Duration(seconds: 3));
      if (response.statusCode == 200) {
        final decoded = json.decode(response.body);
        final address = decoded['address'];
        if (address is List && address.isNotEmpty) {
          storage.write('address', address);
          return;
        }
      }
    } catch (_) {
      // به سراغ آدرس بعدی برو
    }
  }
  storage.writeIfNull('address', []);
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    final LanguageController languageController = Get.put(LanguageController());

    return ScreenUtilInit(
      designSize: const Size(360, 690),
      minTextAdapt: true,
      splitScreenMode: true,
      builder: (_, __) {
        final bool isLoginSafe = Pref.isLogin;
        return GetMaterialApp(
          title: "alpping",
          debugShowCheckedModeBanner: false,
          textDirection: languageController.selectedLanguage == 'en_US'
              ? TextDirection.ltr
              : TextDirection.rtl,
          home: isLoginSafe ? const SplashScreen() : const LoginPage(),
          theme: ThemeData(fontFamily: 'sans'),
          builder: (context, child) {
            return MediaQuery(
              data: MediaQuery.of(context).copyWith(
                textScaler: const TextScaler.linear(1.0),
              ),
              child: EasyLoading.init()(context, child),
            );
          },
          translations: ApplicationLanguage(),
          fallbackLocale: const Locale('fa', 'IR'),
          locale: Locale(languageController.selectedLanguage),
        );
      },
    );
  }
}
