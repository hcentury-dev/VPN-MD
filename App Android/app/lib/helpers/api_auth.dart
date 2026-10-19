import 'dart:convert';

import 'package:crypto/crypto.dart';

/// امضای چرخشیِ درخواست‌ها (مدل TOTP / Google Authenticator).
///
/// کلید خام هیچ‌وقت روی شبکه فرستاده نمی‌شود؛ به‌جای آن اپ و سرور هر دو
/// `HMAC_SHA256(seed, "ts|method")` را حساب می‌کنند و فقط این امضا + زمان
/// ارسال می‌شود. امضا هر ثانیه عوض می‌شود و سرور فقط زمان‌های نزدیک (±۵ دقیقه)
/// را می‌پذیرد، پس شنودِ شبکه و replay بی‌اثر می‌شود.
///
/// نکته: seed داخل باینری اپ است و با مهندسی معکوس قابل استخراج است (محدودیت
/// ذاتی هر اپ کلاینت). برای سخت‌ترکردن استخراج، اپ را با
/// `flutter build apk --obfuscate --split-debug-info=build/symbols` بساز.
class ApiAuth {
  // seed به‌صورت تکه‌تکه و درهم ذخیره شده تا یک رشته‌ی قابل‌grep نباشد.
  // ترتیب واقعی: [1] + [3] + [0] + [2]
  static const List<String> _p = [
    'e6a9b2c8d4f7013a',
    '9f2c7a1e5b8d34f6',
    '5e8c9b0d2f4a6178',
    'a0c9e28b7d1450f3',
  ];

  static String get _seed => _p[1] + _p[3] + _p[0] + _p[2];

  /// فیلدهای احراز هویت را برای متد داده‌شده می‌سازد؛ در bodyFields ادغام کن:
  ///   ..bodyFields = {...ApiAuth.sign('login'), 'username': ...}
  static Map<String, String> sign(String method) {
    final ts = (DateTime.now().millisecondsSinceEpoch ~/ 1000).toString();
    final mac = Hmac(sha256, utf8.encode(_seed));
    final sig = mac.convert(utf8.encode('$ts|$method')).toString();
    return {'ts': ts, 'sig': sig};
  }
}
