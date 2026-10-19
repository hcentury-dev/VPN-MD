// import 'dart:developer';
//
// import 'package:firebase_core/firebase_core.dart';
// import 'package:firebase_messaging/firebase_messaging.dart';
// import 'package:flutter_local_notifications/flutter_local_notifications.dart';
//
// import '../firebase_options.dart';
//
// late FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin;
//
// Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
//   await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
//   log("Handling a background message: ${message.messageId}");
// }
//
// Future<void> _showLocalNotification(String title, String body) async {
//   const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
//     'your_channel_id',
//     'your_channel_name',
//     importance: Importance.high,
//     priority: Priority.high,
//   );
//
//   const NotificationDetails platformDetails = NotificationDetails(
//     android: androidDetails,
//   );
//
//   await flutterLocalNotificationsPlugin.show(
//     0,
//     title,
//     body,
//     platformDetails,
//     payload: 'item x',
//   );
// }
//
// void setupFirebaseMessaging() async {
//   try {
//     FirebaseMessaging messaging = FirebaseMessaging.instance;
//
//     NotificationSettings settings = await messaging.requestPermission(
//       alert: true,
//       badge: true,
//       sound: true,
//     );
//
//     if (settings.authorizationStatus == AuthorizationStatus.authorized) {
//       log('User granted permission');
//     } else {
//       log('User declined or has not accepted permission');
//     }
//
//     String? token = await messaging.getToken();
//     if (token == null) {
//       log("Failed to get Firebase Messaging Token");
//     } else {
//       log("Firebase Messaging Token: OK");
//     }
//
//     await messaging.subscribeToTopic('general');
//
//     FirebaseMessaging.onMessage.listen((RemoteMessage message) async {
//       log("Foreground message received: ${message.messageId}");
//       if (message.notification != null) {
//         log("Notification Title: ${message.notification!.title}");
//         log("Notification Body: ${message.notification!.body}");
//
//         await _showLocalNotification(
//           message.notification!.title ?? 'No Title',
//           message.notification!.body ?? 'No Body',
//         );
//       }
//     });
//
//     FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
//       log("Message clicked: ${message.data}");
//     });
//   } catch (e) {
//     log("Error setting up Firebase Messaging: $e");
//   }
// }
//
// class FirebaseService {
//   static Future<void> initialize() async {
//     await Firebase.initializeApp(
//         options: DefaultFirebaseOptions.currentPlatform);
//
//     flutterLocalNotificationsPlugin = FlutterLocalNotificationsPlugin();
//     const InitializationSettings initializationSettings =
//         InitializationSettings(
//       android: AndroidInitializationSettings('@mipmap/ic_launcher'),
//     );
//     await flutterLocalNotificationsPlugin.initialize(initializationSettings);
//
//     FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
//   }
// }
