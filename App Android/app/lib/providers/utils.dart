import 'dart:math';

const sample =
    "vless://d672ec4f-afba-4f61-af09-eec872f97e99@23.227.60.7:443?path=%2FPath%2FTelegram%40proxy_mtm%2F%3Fed%3D2048&security=tls&encryption=none&host=test43.pages.dev&type=ws&sni=test43.pages.dev#DailyV2ray";

String formatByte(String bytesString, [int decimals = 1]) {
  int bytes = int.tryParse(bytesString) ?? 0;
  if (bytes <= 0) return "0 B";
  const suffixes = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
  var i = (log(bytes) / log(1024)).floor();
  return '${(bytes / pow(1024, i)).toStringAsFixed(decimals)} ${suffixes[i]}';
}

String formatBytes(int bytes, [int decimals = 1]) {
  if (bytes <= 0) return "0 B";
  const suffixes = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
  var i = (log(bytes) / log(1024)).floor();
  if (suffixes[i].contains("MB")) {
    return '${(bytes / pow(1024, i)).toStringAsFixed(decimals)} ${suffixes[i]}';
  } else {
    return "0 B";
  }
}

int formatBytesBytes(int bytes, [int decimals = 1]) {
  if (bytes <= 0) return 0;
  const suffixes = ["B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
  var i = (log(bytes) / log(1024)).floor();
  if (suffixes[i].contains("MB")) {
    return (bytes / pow(1024, i)).floor();
  } else {
    return 0;
  }
}

String formatDuration(Duration duration) {
  String negativeSign = duration.isNegative ? '-' : '';
  String twoDigits(int n) => n.toString().padLeft(2, "0");
  String twoDigitMinutes = twoDigits(duration.inMinutes.remainder(60).abs());
  String twoDigitSeconds = twoDigits(duration.inSeconds.remainder(60).abs());
  return "$negativeSign${twoDigits(duration.inHours)}:$twoDigitMinutes:$twoDigitSeconds";
}
