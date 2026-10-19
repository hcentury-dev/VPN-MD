import 'package:flutter/services.dart';

class V2RayService {
  static const platform = MethodChannel('flutter_v2ray');
  static const eventChannel = EventChannel('flutter_v2ray/status');

  // Method to start V2Ray
  Future<void> startV2Ray(String config) async {
    try {
      await platform.invokeMethod('startV2Ray', {"config": config});
    } on PlatformException catch (e) {
      print("Failed to start V2Ray: '${e.message}'.");
    }
  }

  // Method to stop V2Ray
  Future<void> stopV2Ray() async {
    try {
      await platform.invokeMethod('stopV2Ray');
    } on PlatformException catch (e) {
      print("Failed to stop V2Ray: '${e.message}'.");
    }
  }

  // Listen to the status updates
  void listenToV2RayStatus(Function(List<String>) onUpdate) {
    eventChannel.receiveBroadcastStream().listen((event) {
      onUpdate(event.cast<String>());
    });
  }
}
