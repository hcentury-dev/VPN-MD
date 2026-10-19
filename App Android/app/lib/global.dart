import 'package:flutter/cupertino.dart';
import 'package:flutter_v2ray_client/flutter_v2ray.dart';

class V2rayManager {
  V2rayManager._();

  static final V2rayManager i = V2rayManager._();

  ValueNotifier<V2RayStatus> v2rayStatus = ValueNotifier(V2RayStatus());
  late final V2ray flutterV2ray = V2ray(
    onStatusChanged: (status) => v2rayStatus.value = status,
  );
}

