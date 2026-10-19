import 'dart:convert';
import 'dart:io';
import 'package:flutter_v2ray_client/url/shadowsocks.dart';
import 'package:flutter_v2ray_client/url/socks.dart';
import 'package:flutter_v2ray_client/url/trojan.dart';
import 'package:flutter_v2ray_client/url/url.dart';
import 'package:flutter_v2ray_client/url/vless.dart';
import 'package:flutter_v2ray_client/url/vmess.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'flutter_v2ray_platform_interface.dart';
import 'model/v2ray_status.dart';
export 'model/v2ray_status.dart';
export 'url/url.dart';

class V2ray {

  V2ray({required this.onStatusChanged});
  final void Function(V2RayStatus status) onStatusChanged;

  Future<bool> requestPermission() async {
    if (Platform.isAndroid) {
      return FlutterV2rayPlatform.instance.requestPermission();
    }
    return true;
  }

  Future<void> initialize({
    String notificationIconResourceType = 'mipmap',
    String notificationIconResourceName = 'ic_launcher',
  }) async {
    await FlutterV2rayPlatform.instance.initializeV2Ray(
      onStatusChanged: onStatusChanged,
      notificationIconResourceType: notificationIconResourceType,
      notificationIconResourceName: notificationIconResourceName,
    );
  }

  Future<void> startV2Ray({
    required String remark,
    required String config,
    List<String>? blockedApps,
    List<String>? bypassSubnets,
    bool proxyOnly = false,
    String notificationDisconnectButtonName = "DISCONNECT",
    required String address,
    required int time,
    required Map<String, dynamic> data,
  }) async {
    try {
      final sharedPref = await SharedPreferences.getInstance();
      sharedPref.setString("vpn_config_json", config);
      if (jsonDecode(config) == null) {
        throw ArgumentError('The provided string is not valid JSON');
      }
    } catch (_) {
      throw ArgumentError('The provided string is not valid JSON');
    }

    await FlutterV2rayPlatform.instance.startV2Ray(
      remark: remark,
      config: config,
      blockedApps: blockedApps,
      proxyOnly: proxyOnly,
      bypassSubnets: bypassSubnets,
      notificationDisconnectButtonName: notificationDisconnectButtonName,
      time: time,
      address: address,
      data: data,
    );
  }

  Future<void> stopV2Ray() async {
    await FlutterV2rayPlatform.instance.stopV2Ray();
  }

  Future<int> getServerDelay({
    required String config,
    String url = 'https://google.com/generate_204',
  }) async {
    try {
      if (jsonDecode(config) == null) {
        throw ArgumentError('The provided string is not valid JSON');
      }
    } catch (_) {
      throw ArgumentError('The provided string is not valid JSON');
    }
    return FlutterV2rayPlatform.instance
        .getServerDelay(config: config, url: url);
  }

  Future<int> getConnectedServerDelay({
    String url = 'https://google.com/generate_204',
  }) async {
    return FlutterV2rayPlatform.instance.getConnectedServerDelay(url);
  }


  Future<String> getCoreVersion() async {
    return FlutterV2rayPlatform.instance.getCoreVersion();
  }

  Future<List<String>> getLogs() async {
    if (Platform.isAndroid) {
      return FlutterV2rayPlatform.instance.getLogs();
    }
    return [];
  }

  Future<bool> clearLogs() async {
    if (Platform.isAndroid) {
      return FlutterV2rayPlatform.instance.clearLogs();
    }
    return true;
  }

  static V2RayURL parseFromURL(String url) {
    switch (url.split('://')[0].toLowerCase()) {
      case 'vmess':
        return VmessURL(url: url);
      case 'vless':
        return VlessURL(url: url);
      case 'trojan':
        return TrojanURL(url: url);
      case 'ss':
        return ShadowSocksURL(url: url);
      case 'socks':
        return SocksURL(url: url);
      default:
        throw ArgumentError('url is invalid');
    }
  }
}
