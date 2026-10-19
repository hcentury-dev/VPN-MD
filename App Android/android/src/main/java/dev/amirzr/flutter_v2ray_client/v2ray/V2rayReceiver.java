package dev.amirzr.flutter_v2ray_client.v2ray;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;

import java.util.ArrayList;

import io.flutter.plugin.common.EventChannel;

public class V2rayReceiver extends BroadcastReceiver {
    public static EventChannel.EventSink vpnStatusSink;

    @Override
    public void onReceive(Context context, Intent intent) {
        try {
            if (intent == null) {
                Log.w("V2rayReceiver", "Received null intent");
                return;
            }

            Bundle extras = intent.getExtras();
            if (extras == null) {
                Log.w("V2rayReceiver", "Intent has no extras");
                return;
            }

            if (vpnStatusSink == null) {
                Log.w("V2rayReceiver", "vpnStatusSink is null, cannot send status");
                return;
            }

            ArrayList<String> list = new ArrayList<>();

            // DURATION
            String duration = extras.getString("DURATION");
            list.add(duration != null ? duration : "00:00:00");

            // Helper برای تبدیل هر نوع مقداری به String
            String uploadSpeed      = getSafeString(extras, "UPLOAD_SPEED", "0");
            String downloadSpeed    = getSafeString(extras, "DOWNLOAD_SPEED", "0");
            String uploadTraffic    = getSafeString(extras, "UPLOAD_TRAFFIC", "0");
            String downloadTraffic  = getSafeString(extras, "DOWNLOAD_TRAFFIC", "0");

            list.add(uploadSpeed);
            list.add(downloadSpeed);
            list.add(uploadTraffic);
            list.add(downloadTraffic);

            // STATE
            Object state = extras.getSerializable("STATE");
            if (state != null) {
                String stateStr = state.toString();
                list.add(stateStr.length() > 6 ? stateStr.substring(6) : stateStr);
            } else {
                list.add("DISCONNECTED");
            }

            vpnStatusSink.success(list);
        } catch (Exception e) {
            Log.e("V2rayReceiver", "onReceive failed", e);
        }
    }

    private String getSafeString(Bundle extras, String key, String defaultValue) {
        try {
            Object value = extras.get(key);
            if (value == null) return defaultValue;

            if (value instanceof String) {
                return (String) value;
            } else if (value instanceof Long || value instanceof Integer
                    || value instanceof Double || value instanceof Float) {
                return String.valueOf(value);
            } else {
                // هر نوع دیگه‌ای رو هم به String تبدیل می‌کنیم
                return value.toString();
            }
        } catch (Exception e) {
            Log.w("V2rayReceiver", "Failed to read key " + key, e);
            return defaultValue;
        }
    }
}
