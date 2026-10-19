// import 'package:awesome_notifications/awesome_notifications.dart';
// import 'package:flutter/material.dart';
//
// class NotificationService {
//   static Future<void> initializeNotification() async {
//     await AwesomeNotifications().initialize(
//       null,
//       [
//         NotificationChannel(
//           channelGroupKey: 'high_importance_channel',
//           channelKey: 'high_importance_channel',
//           channelName: 'Basic notifications',
//           channelDescription: 'Notification channel for basic tests',
//           defaultColor: Colors.deepPurple,
//           ledColor: Colors.white,
//           importance: NotificationImportance.Max,
//           channelShowBadge: true,
//           onlyAlertOnce: true,
//           playSound: true,
//         ),
//       ],
//       channelGroups: [
//         NotificationChannelGroup(
//           channelGroupKey: 'high_importance_channel',
//           channelGroupName: 'Group 1',
//         ),
//       ],
//       debug: true,
//     );
//
//     await AwesomeNotifications().isNotificationAllowed().then(
//           (isAllowed) async {
//         if (!isAllowed) {
//           await AwesomeNotifications().requestPermissionToSendNotifications();
//         }
//       },
//     );
//   }
//
//   Future<void> scheduleNotification({
//     required String title,
//     required DateTime scheduledDate,
//     Map<String, String>? payload,
//   }) async {
//     DateTime currentTime = DateTime.now();
//
//     if (scheduledDate.isBefore(currentTime)) {
//       await AwesomeNotifications().createNotification(
//         content: NotificationContent(
//           id: DateTime.now().millisecondsSinceEpoch.remainder(100000),
//           channelKey: 'high_importance_channel',
//           title: title,
//           body: 'Your scheduled notification.',
//           payload: payload,
//         ),
//       );
//     } else {
//       await AwesomeNotifications().createNotification(
//         content: NotificationContent(
//           id: DateTime.now().millisecondsSinceEpoch.remainder(100000),
//           channelKey: 'high_importance_channel',
//           title: title,
//           body: 'Your scheduled notification.',
//           payload: payload,
//         ),
//         schedule: NotificationCalendar.fromDate(date: scheduledDate),
//       );
//     }
//   }
// }
