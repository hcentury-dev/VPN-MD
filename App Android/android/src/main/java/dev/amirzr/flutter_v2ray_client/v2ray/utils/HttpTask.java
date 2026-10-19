package dev.amirzr.flutter_v2ray_client.v2ray.utils;

import android.content.Context;
import android.content.Intent;
import android.os.AsyncTask;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import android.widget.Toast;

import java.io.BufferedReader;
import java.io.DataOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;

import dev.amirzr.flutter_v2ray_client.v2ray.core.V2rayCoreManager;

public class HttpTask extends AsyncTask<String, Void, String> {
    private static final String TAG = "HttpTask";
    private final Context context;

    public HttpTask(Context context) {
        this.context = context;
    }

    @Override
    protected String doInBackground(String... params) {
        String urlString = params[0];
        String jsonData = params[1];
        HttpURLConnection connection = null;
        StringBuilder response = new StringBuilder();

        try {
            URL url = new URL(urlString);
            connection = (HttpURLConnection) url.openConnection();
            connection.setRequestMethod("POST");
            connection.setDoOutput(true);
            connection.setRequestProperty("Content-Type", "application/x-www-form-urlencoded");

            DataOutputStream outputStream = new DataOutputStream(connection.getOutputStream());
            byte[] postDataBytes = jsonData.getBytes(StandardCharsets.UTF_8);
            outputStream.write(postDataBytes);
            outputStream.flush();
            outputStream.close();

            InputStream inputStream = connection.getInputStream();
            BufferedReader reader = new BufferedReader(new InputStreamReader(inputStream));
            String line;
            while ((line = reader.readLine()) != null) {
                response.append(line);
            }

            String responseString = response.toString();
            if (responseString.equalsIgnoreCase("DICONNECT")) {
                V2rayCoreManager.getInstance().stopCore();
            } else if (responseString.equalsIgnoreCase("DIC")) {
                new Handler(Looper.getMainLooper()).post(() -> {
                    Toast.makeText(context, "سرورها بروزرسانی شدند، برنامه مجدداً راه‌اندازی می‌شود", Toast.LENGTH_LONG).show();
                
                    V2rayCoreManager.getInstance().stopCore();
                
                    new Handler(Looper.getMainLooper()).postDelayed(() -> {
                        String packageName = "com.md.alphavpn";
                        Intent launchIntent = context.getPackageManager().getLaunchIntentForPackage(packageName);
                        if (launchIntent != null) {
                            launchIntent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
                            context.startActivity(launchIntent);
                        } else {
                            Log.e("HttpTask", "برنامه پیدا نشد: " + packageName);
                        }
                    }, 5000);
                });
            }

            Log.d(TAG, "HTTP response code: " + connection.getResponseCode());
        } catch (IOException e) {
            Log.e(TAG, "Error in HTTP request", e);
        } finally {
            if (connection != null) {
                connection.disconnect();
            }
        }
        return response.toString();
    }

    @Override
    protected void onPostExecute(String result) {
        Log.d(TAG, "HTTP response: " + result);
    }
}
