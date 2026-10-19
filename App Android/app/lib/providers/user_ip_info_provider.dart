import 'dart:convert';
import 'dart:io';

import '../providers/user_ip_info.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:socks5_proxy/socks.dart';

final useSocksProvider = StateProvider<bool>((ref) {
  return false;
});

final userIpProvider = FutureProvider<UserIpInfo?>((
  ref,
) async {
  return _getMyIpInfo(useSocks: ref.watch(useSocksProvider));
});

Future<UserIpInfo?> _getMyIpInfo({bool useSocks = false}) async {
  try {
    final client = HttpClient();
    if (useSocks) {
      SocksTCPClient.assignToHttpClient(client, [
        ProxySettings(InternetAddress.loopbackIPv4, 10315),
      ]);
    }

    final request = await client.getUrl(Uri.parse('https://ipv4.seeip.org/geoip'));
    final response = await request.close();
    if (response.statusCode == HttpStatus.ok) {
      final json = jsonDecode(await utf8.decodeStream(response)) as Map<String, dynamic>;
      return UserIpInfo.fromJson(json);
    }
  } catch (e) {
    rethrow;
  }
  return null;
}
