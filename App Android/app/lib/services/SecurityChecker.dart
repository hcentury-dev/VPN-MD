import 'package:flutter/services.dart';

class SecurityChecker {
  static const MethodChannel _channel =
      MethodChannel('com.example.security/check');

  static Future<bool> isManifestTampered() async {
    try {
      final bool isTampered = await _channel.invokeMethod('isManifestTampered');
      return isTampered;
    } catch (e) {
      print('Error checking manifest tampering: $e');
      return true;
    }
  }

  static Future<bool> hasUnauthorizedPermissions() async {
    try {
      final bool hasPermissions =
          await _channel.invokeMethod('hasUnauthorizedPermissions');
      return hasPermissions;
    } catch (e) {
      print('Error checking unauthorized permissions: $e');
      return true;
    }
  }
}
