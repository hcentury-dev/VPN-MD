import 'dart:convert';
import 'dart:developer';
import 'package:shared_preferences/shared_preferences.dart';

import '../models/news_models.dart';
import '../models/question_model.dart';
import 'package:http/http.dart' as http;
import '../helpers/api_auth.dart';
import '../helpers/pref.dart';

class ApiRepostiory1 {
  ApiRepostiory1._();

  static Future<List<NewsModel>?> getNews() async {
    try {
      final Map<String, String> requestBody = {
        ...ApiAuth.sign('news'),
        'method': 'news',
        'username': Pref.username,
      };

      final response = await http.post(
        Uri.parse(urlPost),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: requestBody,
        encoding: Encoding.getByName("utf-8"),
      );

      if (response.statusCode == 200) {
        final res = jsonDecode(utf8.decode(response.bodyBytes));

        if (res['result'] == 'success') {
          final List<dynamic> newsList = res['news'] as List;
          final news = newsList.map((item) => NewsModel.fromMap(item)).toList();
          return news;
        }
      }
    } catch (e) {
      log('$e');
    }
    return null;
  }

  static Future<List<QuestionsModel>?> getQuestions() async {
    try {
      final Map<String, String> requestBody = {
        ...ApiAuth.sign('questions'),
        'method': 'questions',
        'username': Pref.username,
      };

      final response = await http.post(
        Uri.parse(urlPost),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: requestBody,
        encoding: Encoding.getByName("utf-8"),
      );

      if (response.statusCode == 200) {
        final res = jsonDecode(utf8.decode(response.bodyBytes));

        if (res['result'] == 'success') {
          final List<dynamic> newsList = res['news'] as List;
          final questions =
              newsList.map((item) => QuestionsModel.fromMap(item)).toList();
          return questions;
        }
      }
    } catch (e) {
      log('$e');
    }
    return null;
  }

  static Future<List<Map<String, dynamic>>> getAlwaysNotifications(
      String url) async {
    try {
      final Map<String, String> requestBody = {
        'method': 'details',
        'username': Pref.username,
        'password': Pref.password,
        'deviceIMEI': Pref.device,
      };

      final response = await http.post(
        Uri.parse(url),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: requestBody,
        encoding: Encoding.getByName("utf-8"),
      );

      if (response.statusCode == 200) {
        final jsonData = jsonDecode(response.body);
        List<Map<String, dynamic>> notifications = [];

        if (jsonData != null) {
          if (jsonData.containsKey('massage')) {
            notifications.add(Map<String, dynamic>.from(jsonData['massage']));
          }
          if (jsonData.containsKey('users')) {
            notifications.add(Map<String, dynamic>.from(jsonData['users']));
          }
          if (jsonData.containsKey('app')) {
            notifications.add(Map<String, dynamic>.from(jsonData['app']));
          }
          if (jsonData.containsKey('app1')) {
            notifications.add(Map<String, dynamic>.from(jsonData['app1']));
          }
          if (jsonData.containsKey('app2')) {
            notifications.add(Map<String, dynamic>.from(jsonData['app2']));
          }

          // Save 'dayTime' in SharedPreferences
          if (jsonData['app']?.containsKey('dayTime') == true) {
            String dayTime = jsonData['app']['dayTime'];
            SharedPreferences prefs = await SharedPreferences.getInstance();
            await prefs.setString('dayTime', dayTime);
            print('Saved dayTime: $dayTime'); // Add print for debugging
          }

          // Save 'full' in SharedPreferences
          if (jsonData['users']?.containsKey('full') == true) {
            String fullString = jsonData['users']['full'].toString();
            SharedPreferences prefs = await SharedPreferences.getInstance();
            await prefs.setString('full', fullString);
            print('Saved full: $fullString'); // Add print for debugging
          }

          return notifications;
        }
      }
    } catch (e) {
      print('Error fetching notifications: $e');
    }
    return [];
  }
}
