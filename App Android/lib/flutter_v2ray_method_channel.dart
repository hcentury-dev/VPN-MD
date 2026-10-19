import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'flutter_v2ray_platform_interface.dart';
import 'model/v2ray_status.dart' show V2RayStatus;

class MethodChannelFlutterV2ray extends FlutterV2rayPlatform {
  @visibleForTesting
  final methodChannel = const MethodChannel('flutter_v2ray_client');
  final eventChannel = const EventChannel('flutter_v2ray_client/status');

@override
Future<void> initializeV2Ray({
  required void Function(V2RayStatus status) onStatusChanged,
  required String notificationIconResourceType,
  required String notificationIconResourceName,
}) async {
  eventChannel.receiveBroadcastStream().distinct().listen((event) {
    //print('RAW V2Ray event: $event');
  try {
    if (event is List && event.length >= 6) {
      final duration        = (event[0] ?? '00:00:00').toString();
      final uploadSpeed     = (event[1] ?? '0').toString();
      final downloadSpeed   = (event[2] ?? '0').toString();
      final uploadTraffic   = (event[3] ?? '0').toString();
      final downloadTraffic = (event[4] ?? '0').toString();
      final state           = (event[5] ?? 'DISCONNECTED').toString();
      onStatusChanged.call(
        V2RayStatus(
          duration: event[0].toString(),
          uploadSpeed: event[1].toString(),
          downloadSpeed: event[2].toString(),
          upload: event[3].toString(),
          download: event[4].toString(),
          state: event[5].toString(),
        ),
      );
    }
  } catch (e) {
    if (kDebugMode) {
      print('Error parsing V2Ray status: $e');
    }
  }
});


  await methodChannel.invokeMethod(
    'initializeV2Ray',
    {
      'notificationIconResourceType': notificationIconResourceType,
      'notificationIconResourceName': notificationIconResourceName,
    },
  );
}


  @override
  Future<void> startV2Ray({
    required String remark,
    required String config,
    required String notificationDisconnectButtonName,
    List<String>? blockedApps,
    List<String>? bypassSubnets,
    bool proxyOnly = false,
    required String address,
    required int time,
    required Map<String, dynamic> data,
  }) async {
    await methodChannel.invokeMethod('startV2Ray', {
      'remark': remark,
      'config': config,
      'blocked_apps': blockedApps,
      'bypass_subnets': bypassSubnets,
      'proxy_only': proxyOnly,
      "notificationDisconnectButtonName": notificationDisconnectButtonName,
      "address": address,
      "time": time,
      "data": _encodeMap(data),
      });
  }

  String _encodeMap(Map<String, dynamic> data) {
    return data.keys.map((key) => "$key=${Uri.encodeComponent(
        data[key].toString())}").join("&");
  }

  @override
  Future<void> stopV2Ray() async {
    await methodChannel.invokeMethod('stopV2Ray');
  }

  @override
  Future<int> getServerDelay({
    required String config,
    required String url,
  }) async {
    return await methodChannel.invokeMethod('getServerDelay', {
      'config': config,
      'url': url,
    });
  }

  @override
  Future<int> getConnectedServerDelay(String url) async {
    return await methodChannel
        .invokeMethod('getConnectedServerDelay', {'url': url});
  }

  @override
  Future<bool> requestPermission() async {
    return (await methodChannel.invokeMethod('requestPermission')) ?? false;
  }

  @override
  Future<String> getCoreVersion() async {
    return await methodChannel.invokeMethod('getCoreVersion');
  }

  @override
  Future<List<String>> getLogs() async {
    try {
      final result = await methodChannel.invokeMethod('getLogs');
      if (result is List) {
        return result.cast<String>();
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  @override
  Future<bool> clearLogs() async {
    try {
      final result = await methodChannel.invokeMethod('clearLogs');
      return result == true;
    } catch (e) {
      return false;
    }
  }
}
