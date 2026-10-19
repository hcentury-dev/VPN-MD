import 'dart:developer';
import 'dart:io';

import 'package:http/http.dart' as http;

class ApiRepository {
  static Future<List<String>> getConfigs(String url) async {
    try {
      final response = await http.get(Uri.parse(url));
      if (response.statusCode == HttpStatus.ok) {
        final list = response.body
            .split('\n')
            .map(
              (e) => e.trim(),
        )
            .toList();
        return list;
      }
    } catch (e) {
      log(e.toString());
    }
    throw Exception('');
  }
}
