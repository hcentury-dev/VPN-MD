package dev.amirzr.flutter_v2ray_client.v2ray.services;

import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Build;
import android.service.quicksettings.Tile;
import android.service.quicksettings.TileService;
import android.util.Log;

import dev.amirzr.flutter_v2ray_client.v2ray.V2rayController;
import dev.amirzr.flutter_v2ray_client.v2ray.utils.VpnHelper;

import io.flutter.embedding.engine.FlutterEngine;
import io.flutter.embedding.engine.dart.DartExecutor;
import io.flutter.plugin.common.MethodChannel;

import androidx.annotation.RequiresApi;

import java.util.Objects;

@RequiresApi(api = Build.VERSION_CODES.N)
public class QuickSettingTileService extends TileService {

    private static final String CHANNEL = "com.github.blueboytm.flutter_v2ray/channel";
    private MethodChannel methodChannel;
    private FlutterEngine flutterEngine;

    @Override
    public void onStartListening() {
        super.onStartListening();
        updateTile();
    }

    @Override
    public void onTileAdded() {
        super.onTileAdded();
        updateTile();
    }

    @Override
    public void onCreate() {
        super.onCreate();
        flutterEngine = new FlutterEngine(this);
        flutterEngine.getDartExecutor().executeDartEntrypoint(
            DartExecutor.DartEntrypoint.createDefault()
        );
        methodChannel = new MethodChannel(flutterEngine.getDartExecutor().getBinaryMessenger(), CHANNEL);
    }

    @Override
    public void onClick() {
        super.onClick();
        Tile tile = getQsTile();
        SharedPreferences prefs = getSharedPreferences("FlutterSharedPreferences", Context.MODE_PRIVATE);
        String config = prefs.getString("flutter.vpn_config_json", "");

        if (!Objects.equals(config, "")) {
            if (VpnHelper.isVpnConnected(this)) {
                tile.setState(Tile.STATE_INACTIVE);
                tile.setLabel("VPN");
                tile.updateTile();
                V2rayController.StopV2ray(getApplicationContext());
                methodChannel.invokeMethod("vpnDisconnected", null);
            } else {
                tile.setState(Tile.STATE_ACTIVE);
                tile.setLabel("VPN");
                tile.updateTile();
                methodChannel.invokeMethod("vpnConnect", null);
            }
        } else {
            openApp();
        }
    }

    private void openApp() {
        Intent launchIntent = getPackageManager().getLaunchIntentForPackage(getPackageName());
        if (launchIntent != null) {
            launchIntent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
            startActivity(launchIntent);
        }
    }

    private void updateTile() {
        Tile tile = getQsTile();
        if (tile != null) {
            if (VpnHelper.isVpnConnected(this)) {
                tile.setState(Tile.STATE_ACTIVE);
                tile.setLabel("VPN");
            } else {
                tile.setState(Tile.STATE_INACTIVE);
                tile.setLabel("VPN");
            }
            tile.updateTile();
        }
    }
}
