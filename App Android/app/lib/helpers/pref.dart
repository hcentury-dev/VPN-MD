import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_v2ray_client/url/url.dart';
import 'package:get_storage/get_storage.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:http/http.dart' as http;
import '../services/crypto_service.dart';
import '../class.dart';

/// ---------------- SAFE REMOTE CONFIG (NO CRASH ON FIRST INSTALL) ----------------
class RemoteConfig {
  static final GetStorage _s = GetStorage();

  static List<dynamic> get _data {
    final v = _s.read('address');
    if (v is List && v.isNotEmpty) return v;
    return const [];
  }

  static Map<String, dynamic> get _first {
    final d = _data;
    if (d.isEmpty) return const {};
    final f = d.first;
    if (f is Map) return Map<String, dynamic>.from(f);
    return const {};
  }

  static String get post => (_first['post'] ?? '').toString();

  static String get version => (_first['version'] ?? '').toString();

  static dynamic get timeData => _first['timeData'];

  static String get server => (_first['server'] ?? '').toString();

  static String get user => (_first['user'] ?? '').toString();

  static String get newsUrl => post.isEmpty ? '' : '$post?method=news';

  static String get questionsUrl =>
      post.isEmpty ? '' : '$post?method=questions';

  static String get alwaysNotificationUrl =>
      post.isEmpty ? '' : '$post?method=massage';
}

String get urlPost => RemoteConfig.post;

String get version => RemoteConfig.version;

dynamic get timeData => RemoteConfig.timeData;

String get urlServer => RemoteConfig.server;

String get useR => RemoteConfig.user;

String get newsUrl => RemoteConfig.newsUrl;

String get questionsUrl => RemoteConfig.questionsUrl;

String get alwaysNotificationUrl => RemoteConfig.alwaysNotificationUrl;
final storage = GetStorage();

/// ============================================================
/// SERVER CONNECTION POINTS  —  نقاط اتصال به سرور (همه از همین‌جا)
/// هر چیزی که اپ برای رسیدن به بک‌اند لازم دارد، فقط در همین بلوک است.
/// جزئیات و نحوهٔ مچ‌شدن با سمت سرور در فایل SERVER_SETUP.md.
/// ============================================================

/// کلید AES-256-CBC. باید دقیقاً برابرِ $key در API/postN.php باشد.
/// سرور:  openssl_encrypt(data,"AES-256-CBC", key, OPENSSL_RAW_DATA, iv)  →  base64(iv + data)
/// اپ:    CryptoService.decryptConfig()  همین را برعکس می‌کند.
String key = '3154b04f1932a28423f1e86bf94657886e99b057b19b79193051e067148fa536';

/// منبع کانفیگِ بوت‌استرپ (db.json). اپ از آن `address:[{post,server,user,version,timeData}]`
/// را می‌خواند. به ترتیب امتحان می‌شوند؛ اولین آدرسی که `address` معتبر بدهد برنده است
/// (مقاوم در برابر فیلتر/قطعی دامنه). آدرس اصلی باید نسخهٔ raw باشد، نه صفحهٔ edit گیت‌هاب.
const List<String> configBootstrapUrls = [
  'https://raw.githubusercontent.com/hcentury-dev/Alpha/main/db.json',
  'https://fere34e2we.alpping.ir/w4reizwiz.json', // دامنهٔ جایگزین (fallback)
];

/// هویت اپ
const String nameAppFa = "alpping";
const String nameAppEn = "alpping";
var packageName = "com.md.alpping";

/// ---------------- HOME COLORS ----------------
const menuColor = Color(0xd50a0a0a);
const tmenuColor = Color(0xff0a83e6);
const toolbarC = Color(0xa0ffffff);
const kBgColor = Color(0xFF0B232C);
const monColor = Color(0xff0d5eaf);
const locColor = Colors.black;
const sColorBg = toolbarC;
const dayColorBg = toolbarC;
const kColorBg = toolbarC;
const actColorBg = Color(0xffF57C00);
const banColorBg = Color(0xffe62727);
const jColorBg = Color(0xCFFFFFFF);
const onColor = Color(0xff36c063);
const ncenterColor = Color(0xFFFFFFFF);
const offColor = Color(0xFFFF0000);
const fcenterColor = Color(0xff1976D2);

/// ---------------- LOGIN ----------------
const BackColor = Color(0xCFFFFFFF);
const oneColor = monColor;
const twoColor = menuColor;
const loginColor = kBgColor;
const gustColor = kBgColor;

/// ---------------- LOCATION ----------------
const SnColorBg = Color(0x941A5369);
const SfColorBg = Color(0x00000000);
const ColorBg = kBgColor;
const TColorBg = Color(0xffFAFAFA);
const MColorBg = TColorBg;
const lodingColorBg = TColorBg;
const lodingColor = kBgColor;
const updateColorBg = Color(0xffe27a24);
const updatetxtColor = TColorBg;
const toolColorBg = locColor;

/// ---------------- EXTRA COLORS ----------------
const aColor = Color(0xff2691fd);
const bColor = Color(0xff1d77d2);
const cColor = Color(0xff14579a);
const dColor = Color(0xff104172);

const eColor = menuColor;
const fColor = Color(0xff086d78);
const gColor = Color(0xff0a83e6);
const hColor = Color(0xc8059d40);

/// ---------------- V2RAY FRAGMENT ----------------
String activeFragment({required V2RayURL parser, int indent = 2}) {
  parser.routing = {
    "domainStrategy": "IPIfNonMatch",
    "rules": [
      {
        "ip": ["1.1.1.1"],
        "outboundTag": "proxy",
        "port": "53",
        "type": "field"
      }
    ]
  };
  parser.outbound3 = {
    "protocol": "blackhole",
    "settings": {
      "response": {"type": "http"}
    },
    "tag": "block"
  };

  var fullConfig = parser.fullConfiguration;
  fullConfig["outbounds"].add({
    "protocol": "freedom",
    "settings": {
      "domainStrategy": "AsIs",
      "fragment": {
        "packets": "tlshello",
        "length": "50-100",
        "interval": "10-20"
      }
    },
    "streamSettings": {
      "network": "tcp",
      "security": "",
      "sockopt": {"TcpNoDelay": true, "mark": 255}
    },
    "tag": "fragment"
  });

  return JsonEncoder.withIndent(' ' * indent).convert(
    parser.removeNulls(Map.from(fullConfig)),
  );
}

String toPersianDigits(int number) {
  const english = '0123456789';
  const persian = '۰۱۲۳۴۵۶۷۸۹';
  return number.toString().split('').map((char) {
    int index = english.indexOf(char);
    return index != -1 ? persian[index] : char;
  }).join('');
}

int processNumber(int number) {
  if (number == -1) return number;
  return (number ~/ 2);
}

int parseDataString(String dataString) {
  if (dataString.contains("MB")) {
    double value = double.parse(dataString.replaceAll("MB", "").trim());
    return value > 1 ? value.toInt() : 0;
  } else {
    return int.parse(dataString.trim());
  }
}

String convertIntToString(int value) => value.toString();

String formatFullValue(double full, String language) {
  int gb = full.toInt();
  int mb = ((full - gb) * 1000).toInt();

  String formattedValue = '';
  if (gb > 0) {
    formattedValue += language == 'fa_IR' ? 'GB $gb |' : '$gb گیگ ';
  }
  if (mb > 0) {
    formattedValue += language == 'fa_IR' ? ' $mb MB' : '$mb مگ ';
  }
  return formattedValue.trim();
}

String convertToPer(String input) {
  Map<String, String> persianNumbers = {
    '0': '۰',
    '1': '۱',
    '2': '۲',
    '3': '۳',
    '4': '۴',
    '5': '۵',
    '6': '۶',
    '7': '۷',
    '8': '۸',
    '9': '۹'
  };
  return input.split('').map((c) => persianNumbers[c] ?? c).join('');
}

String convertToPersianNumbers(String input) {
  const englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
  const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

  String output = '';
  for (int i = 0; i < input.length; i++) {
    String char = input[i];
    int index = englishNumbers.indexOf(char);
    output += (index != -1) ? persianNumbers[index] : char;
  }
  return output;
}

void printLongLog(String logText) {
  const int chunkSize = 1024;
  for (int i = 0; i < logText.length; i += chunkSize) {
    print(logText.substring(
      i,
      i + chunkSize > logText.length ? logText.length : i + chunkSize,
    ));
  }
}

extension SimpleDateFormat on String {
  String toEnglishDate() {
    final date = DateTime.parse(this);
    return "${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}";
  }

  String toRussianDate() {
    final date = DateTime.parse(this);
    return "${date.day.toString().padLeft(2, '0')}.${date.month.toString().padLeft(2, '0')}.${date.year}";
  }

  String toChineseDate() {
    final date = DateTime.parse(this);
    return "${date.year}年${date.month.toString().padLeft(2, '0')}月${date.day.toString().padLeft(2, '0')}日";
  }

  String toJalali() {
    final dateTime = DateTime.parse(this);

    int gYear = dateTime.year;
    int gMonth = dateTime.month;
    int gDay = dateTime.day;

    List<int> gDaysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    List<int> jDaysInMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    int gy = gYear - 1600;
    int gm = gMonth - 1;
    int gd = gDay - 1;

    int gDayNo =
        365 * gy + (gy + 3) ~/ 4 - (gy + 99) ~/ 100 + (gy + 399) ~/ 400;
    for (int i = 0; i < gm; i++) {
      gDayNo += gDaysInMonth[i];
    }
    if (gm > 1 &&
        ((gYear % 4 == 0 && gYear % 100 != 0) || (gYear % 400 == 0))) {
      gDayNo++;
    }
    gDayNo += gd;

    int jDayNo = gDayNo - 79;
    int jNp = jDayNo ~/ 12053;
    jDayNo %= 12053;

    int jy = 979 + 33 * jNp + 4 * (jDayNo ~/ 1461);
    jDayNo %= 1461;

    if (jDayNo >= 366) {
      jy += (jDayNo - 1) ~/ 365;
      jDayNo = (jDayNo - 1) % 365;
    }

    int jm = 0;
    while (jm < 11 && jDayNo >= jDaysInMonth[jm]) {
      jDayNo -= jDaysInMonth[jm];
      jm++;
    }
    int jd = jDayNo + 1;
    jm += 1;

    String jalali =
        "$jy/${jm.toString().padLeft(2, '0')}/${jd.toString().padLeft(2, '0')}";

    return _convertToPersianNumbers(jalali);
  }

  String _convertToPersianNumbers(String input) {
    const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    for (int i = 0; i < english.length; i++) {
      input = input.replaceAll(english[i], persian[i]);
    }
    return input;
  }
}

Future<http.Response?> fetchWithTimeout(String url, Duration timeout) async {
  try {
    return await http.get(Uri.parse(url)).timeout(timeout, onTimeout: () {
      print('Timeout for $url');
      return http.Response('{"error": "timeout"}', 408);
    });
  } catch (e) {
    print('Error during request to $url: $e');
    return null;
  }
}

CryptoService cryptoService = CryptoService();

/// ---------------- HIVE PREF (همون کد خودت، فقط امن‌تر) ----------------
class Pref {
  static late Box box;
  static late Box<VPNClass> vpnClassbox;
  static late Box<String> appBox;

  static Future<void> initializeHive() async {
    await Hive.initFlutter();
    Hive.registerAdapter(VPNClassAdapter());

    // باکس‌های اصلی
    box = await Hive.openBox('data');
    appBox = await Hive.openBox('appBox');

    // باکس سرورها با مدیریت Corrupted
    try {
      vpnClassbox = await Hive.openBox<VPNClass>('vpnClassBox');
    } catch (e) {
      print("⚠️ vpnClassBox open error: $e");
      try {
        await Hive.deleteBoxFromDisk('vpnClassBox');
        vpnClassbox = await Hive.openBox<VPNClass>('vpnClassBox');
        print("✅ vpnClassBox recreated successfully.");
      } catch (retryError) {
        print("❌ Failed to recreate vpnClassBox: $retryError");
        vpnClassbox = await Hive.openBox<VPNClass>('vpnClassBox_temp');
      }
    }
  }

  static List<String> get apps => appBox.values.toList();

  static VPNClass? get vpnClass => vpnClassbox.values.firstOrNull;

  static Future<void> addApp(String pakcage) async {
    if (appBox.containsKey(pakcage)) {
      await appBox.delete(pakcage);
    } else {
      await appBox.put(pakcage, pakcage);
    }
  }

  static Future<void> setVpnClass(VPNClass model) async {
    try {
      await vpnClassbox.clear();
      await vpnClassbox.add(model);
    } catch (e) {
      print("Error setting VPN Class: $e");
    }
  }

  // --- Getters (با default امن) ---
  static String get app_name => box.get('app_name') ?? "alpping";

  static String get app_logo => box.get('app_logo') ?? "assets/images/logo.png";

  static String get app_text => box.get('app_text') ?? "alpping";

  static String get urlPost => box.get('urlPost') ?? "";

  static String get urlServer => box.get('urlServer') ?? "";

  static String get urlUser => box.get('urlUser') ?? "";

  static String get count => box.get('count') ?? "";

  static bool get isFull => box.get('isFull') ?? false;

  static bool get isDays => box.get('isDays') ?? false;

  static bool get isDarkMode => box.get('isDarkMode') ?? false;

  static bool get isConnect => box.get('isConnect') ?? false;

  static bool get isConnectWin => box.get('isConnectWin') ?? false;

  static bool get firebaseInitialized =>
      box.get('firebaseInitialized') ?? false;

  static bool get deviceInitialized => box.get('deviceInitialized') ?? false;

  // ✅ مهم: برای اینکه هیچوقت Box error UI رو نکشه
  static bool get isLogin {
    try {
      return box.get('isLogin') ?? false;
    } catch (_) {
      return false;
    }
  }

  static bool get isGB => box.get('isGB') ?? false;

  static bool get isDay => box.get('isDay') ?? false;

  static bool get isActive => box.get('isActive') ?? false;

  static String get device => box.get('device') ?? "";

  // شناسه‌ی یکتای ماندگار برای فال‌بک وقتی ANDROID_ID در دسترس نیست؛
  // یک‌بار ساخته و برای همیشه ذخیره می‌شود تا هر بار اجرا عوض نشود.
  static String get deviceUuid => box.get('deviceUuid') ?? "";

  static set deviceUuid(String v) => box.put('deviceUuid', v);

  // فلگ ماندگار: آیا مهاجرت یک‌باره‌ی شناسه‌ی دستگاه (Build.ID → ANDROID_ID)
  // انجام شده؟ بعد از اولین ثبت مجدد موفق true می‌شود تا رفتار عادی
  // device_not_found (خروج و امکان کیک توسط ادمین) دوباره برقرار بماند.
  static bool get deviceMigrated => box.get('deviceMigrated') ?? false;

  static set deviceMigrated(bool v) => box.put('deviceMigrated', v);

  static String get model => box.get('model') ?? "";

  static String get token => box.get('token') ?? "";

  static String get sure => box.get('sure') ?? "";

  static String get username => box.get('username') ?? "";

  static String get password => box.get('password') ?? "";

  static String get subscription => box.get('subscription') ?? "";

  static String get status => box.get('status') ?? "";

  static String get devicesStatus => box.get('devicesStatus') ?? "";

  static String get result => box.get('result') ?? "";

  static String get ads => box.get('ads') ?? '';

  static String get adsFull => box.get('adsFull') ?? '';

  static String get pro => box.get('pro') ?? "";

  static String get date => box.get('date') ?? "";

  static String get adate => box.get('adate') ?? "";

  static String get days => box.get('days') ?? "";

  static String get support => box.get('support') ?? "";

  static String get amozesh => box.get('amozesh') ?? "";

  static String get buy => box.get('buy') ?? "";

  static String get renew => box.get('renew') ?? "";

  static String get version => box.get('version') ?? "";

  static String get versionWin => box.get('versionWin') ?? "";

  static String get link => box.get('link') ?? "";

  static String get linkWin => box.get('linkWin') ?? "";

  static String get size => box.get('size') ?? "";

  static String get total => box.get('total') ?? "";

  static String get full => box.get('full') ?? "";

  static String get multi => box.get('multi') ?? "";

  static String get not_name => box.get('not_name') ?? "";

  static String get not_text => box.get('not_text') ?? "";

  static String get not_link => box.get('not_link') ?? "";

  static String get not_status => box.get('not_status') ?? "";

  static String get not_users => box.get('not_users') ?? "";

  static bool get fisrtTime => box.get('fisrtTime') ?? true;

  static List<Map<String, dynamic>> get devices {
    String? jsonString = box.get('devices');
    if (jsonString == null || jsonString.isEmpty) return [];
    try {
      var decoded = jsonDecode(jsonString);
      if (decoded is List) return List<Map<String, dynamic>>.from(decoded);
    } catch (e) {
      print("خطا در تجزیه devices: $e");
    }
    return [];
  }

  // درخواست‌های در انتظارِ بازگشت (برای نمایش به لیدر و تأیید/رد)
  static List<Map<String, dynamic>> get deviceRequests {
    String? jsonString = box.get('deviceRequests');
    if (jsonString == null || jsonString.isEmpty) return [];
    try {
      var decoded = jsonDecode(jsonString);
      if (decoded is List) return List<Map<String, dynamic>>.from(decoded);
    } catch (e) {
      print("خطا در تجزیه deviceRequests: $e");
    }
    return [];
  }

  static set deviceRequests(List<Map<String, dynamic>> v) =>
      box.put('deviceRequests', jsonEncode(v));

  // --- Setters ---
  static set app_name(String v) => box.put('app_name', v);

  static set count(String v) => box.put('count', v);

  static set app_logo(String v) => box.put('app_logo', v);

  static set app_text(String v) => box.put('app_text', v);

  static set urlPost(String v) => box.put('urlPost', v);

  static set urlServer(String v) => box.put('urlServer', v);

  static set urlUser(String v) => box.put('urlUser', v);

  static set isFull(bool v) => box.put('isFull', v);

  static set isDays(bool v) => box.put('isDays', v);

  static set isDarkMode(bool v) => box.put('isDarkMode', v);

  static set isLogin(bool v) => box.put('isLogin', v);

  static set isGB(bool v) => box.put('isGB', v);

  static set isDay(bool v) => box.put('isDay', v);

  static set isActive(bool v) => box.put('isActive', v);

  static set isConnect(bool v) => box.put('isConnect', v);

  static set isConnectWin(bool v) => box.put('isConnectWin', v);

  static set firebaseInitialized(bool v) => box.put('firebaseInitialized', v);

  static set deviceInitialized(bool v) => box.put('deviceInitialized', v);

  static set device(String v) => box.put('device', v);

  static set model(String v) => box.put('model', v);

  static set token(String v) => box.put('token', v);

  static set sure(String v) => box.put('sure', v);

  static set username(String v) => box.put('username', v);

  static set password(String v) => box.put('password', v);

  static set subscription(String v) => box.put('subscription', v);

  static set status(String v) => box.put('status', v);

  static set result(String v) => box.put('result', v);

  static set ads(String v) => box.put('ads', v);

  static set adsFull(String v) => box.put('adsFull', v);

  static set devicesStatus(String v) => box.put('devicesStatus', v);

  static set pro(String v) => box.put('pro', v);

  static set date(String v) => box.put('date', v);

  static set adate(String v) => box.put('adate', v);

  static set days(String v) => box.put('days', v);

  static set support(String v) => box.put('support', v);

  static set amozesh(String v) => box.put('amozesh', v);

  static set buy(String v) => box.put('buy', v);

  static set renew(String v) => box.put('renew', v);

  static set version(String v) => box.put('version', v);

  static set versionWin(String v) => box.put('versionWin', v);

  static set link(String v) => box.put('link', v);

  static set linkWin(String v) => box.put('linkWin', v);

  static set full(String v) => box.put('full', v);

  static set size(String v) => box.put('size', v);

  static set total(String v) => box.put('total', v);

  static set multi(String v) => box.put('multi', v);

  static set not_name(String v) => box.put('not_name', v);

  static set not_text(String v) => box.put('not_text', v);

  static set not_link(String v) => box.put('not_link', v);

  static set not_status(String v) => box.put('not_status', v);

  static set not_users(String v) => box.put('not_users', v);

  static Future setFisrtTime(bool v) => box.put('fisrtTime', v);

  static set devices(List<Map<String, dynamic>> v) {
    try {
      if (v.isEmpty) {
        box.delete('devices');
      } else {
        box.put('devices', jsonEncode(v));
      }
    } catch (e) {
      print("خطا در ذخیره devices: $e");
    }
  }
}
