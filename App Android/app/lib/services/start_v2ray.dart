import 'package:flutter/services.dart';
import 'package:flutter_v2ray_client/flutter_v2ray.dart';
import 'package:get_storage/get_storage.dart';
import 'package:get/get.dart';
import '../class.dart';
import '../controller/language/language_controller.dart';
import '../global.dart';
import '../helpers/pref.dart';
import '../screens/server_location.dart';

class VpnService {
  static const platform =
      MethodChannel('com.github.blueboytm.flutter_v2ray/channel');
  late final VPNClass selected;
  late final VPNClass? best;
  static V2ray flutterV2ray = V2rayManager.i.flutterV2ray;

  static Future<void> executeVpnAction() async {
    try {
      await platform.invokeMethod('executeVpnAction');
    } catch (e) {
      print("Error executing VPN action: $e");
    }
  }

  static Future<void> connectedJava(VPNClass model, dynamic config) async {
    final LanguageController languageController =
        Get.find<LanguageController>();
    if (model.json == "true") {
      config.text = model.link;
    } else {
      if (model.fragment == 'true') {
        final parser = V2ray.parseFromURL(model.link);
        config.text = activeFragment(parser: parser);
      } else {
        final V2RayURL isV2rayURL = V2ray.parseFromURL(model.link);
        config.text = isV2rayURL.getFullConfiguration();
      }
    }
    List<String> filterApp =
        (GetStorage().read('appFilter') as List<dynamic>?)?.cast<String>() ??
            [];
    if (!filterApp.contains(packageName)) {
      filterApp.add(packageName);
    }

    const List<String> iranianAppPackages = [
      'com.rubika.android',
      'ir.rubx.bapp',
      'app.eitaa',
      'ir.bale',
      'ir.nasim',
      'ir.eitaa.messenger',
      'mobi.mmdt.ottplus',
      'ir.tgchannel.soroush',
      'ir.tgchannel.soroushPlus',
      'net.iGap',
      'app.igap',
      'com.shad',
      'app.hiddify.com',
      'com.iranicard.app',
      'com.v2ray.ang',
      'ir.mci.ecareapp',
      'com.myirancell',
      'ir.divar',
      'ir.bmi.bam.nativeweb',
      'cab.snapp.passenger',
      'com.ada.mbank.mehr',
      'ir.torob',
      'com.farsitel.bazaar',
      'ir.mservices.market',
      'ir.ayantech.ghabzino',
    ];

    for (var pkg in iranianAppPackages) {
      if (!filterApp.contains(pkg)) {
        filterApp.add(pkg);
      }
    }

    await GetStorage().write('appFilter', filterApp);

    // final sharedPref = await SharedPreferences.getInstance();
    // await sharedPref.setString("vpn_config_json", config.text);

    await flutterV2ray.startV2Ray(
        remark:
            "${languageController.isEN ? model.nameFa : model.nameEn} - ${languageController.isEN ? model.locationsFa : model.locationsEn}",
        config: config.text,
        proxyOnly: false,
        blockedApps: filterApp,
        address: urlPost,
        time: timeData,
        data: {
          "method": "usage",
          "username": Pref.username,
          "password": Pref.password,
          "idServer": model.serverId,
          "deviceIMEI": Pref.device,
          "deviceConfig": model.link,
          "usage": "{USAGE} MB",
        });
  }
}
