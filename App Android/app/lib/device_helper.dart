import 'package:flutter/services.dart';

class DeviceIdHelper {
  static const MethodChannel _channel = MethodChannel('device_id_channel');

  static Future<String?> getUniqueDeviceId() async {
    try {
      final String? deviceId = await _channel.invokeMethod('getUniqueDeviceId');
      return deviceId;
    } catch (e) {
      print('Error getting device ID: $e');
      return null;
    }
  }
}
