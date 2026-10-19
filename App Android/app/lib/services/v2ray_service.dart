import 'package:flutter/material.dart';
import 'package:flutter_v2ray_client/flutter_v2ray.dart';
import 'package:shared_preferences/shared_preferences.dart';

class V2rayService {
  static V2ray? flutterV2ray;
  static var v2rayStatus = ValueNotifier<V2RayStatus>(V2RayStatus());
  static V2RayURL? v2rayURL;

  static String status = 'DISCONNECTED';

  static Future<void> initV2ray(String config, String status) async {
    flutterV2ray = V2ray(onStatusChanged: (status) {
      status = status.state as V2RayStatus;
      v2rayStatus.value = status;
      setConnectionStatus(status.state);
    });

    await flutterV2ray!.initialize();

    try {
      v2rayURL = V2ray.parseFromURL(config);
    } catch (e) {
      return;
    }
  }

  static Future<void> getPing() async {
    if (flutterV2ray != null) {
      await flutterV2ray!.getConnectedServerDelay().then((value) {
        // ServerConfigsListWidget.ping = '${value}ms';
      });
    }
  }

  // connection status
  static setConnectionStatus(String status) async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    prefs.setString('connectionStatus', status);
  }
}
