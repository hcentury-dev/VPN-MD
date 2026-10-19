-keep class androidx.work.** { *; }
-keep class androidx.core.app.NotificationManagerCompat { *; }
-keep class androidx.core.app.NotificationCompat { *; }
-keep class io.flutter.plugins.** { *; }
-keep class com.dexterous.flutterlocalnotifications.** { *; }
-keepclassmembers class com.md.hidaj.notifications.* {*;}

# حفاظت از کدهای مربوط به Flutter
-keep class io.flutter.** { *; }
-keep class com.google.firebase.** { *; }
-keep class io.flutter.embedding.** { *; }

# جلوگیری از حذف کدهای استفاده نشده
-keepclassmembers class * {
    @android.webkit.JavascriptInterface <methods>;
}
-dontwarn io.flutter.embedding.**
-dontwarn com.google.firebase.**
-keep class androidx.multidex.** { *; }
-keepclassmembers class * {
    public *;
    protected *;
    private *;
}
-dontwarn androidx.multidex.**
-keep class io.flutter.** { *; }
-keep class com.md.hidaj.** { *; }
