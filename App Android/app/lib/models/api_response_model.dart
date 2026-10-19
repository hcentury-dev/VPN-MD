import 'dart:convert';

class ApiResponseModel {
  final String result;
  final Support support;
  final Settings settings;
  final Application application;
  final Notification notification;
  final Users users;
  final int credit;

  ApiResponseModel({
    required this.result,
    required this.support,
    required this.settings,
    required this.application,
    required this.notification,
    required this.users,
    required this.credit,
  });

  ApiResponseModel copyWith({
    String? result,
    Support? support,
    Settings? settings,
    Application? application,
    Notification? notification,
    Users? users,
    int? credit,
  }) =>
      ApiResponseModel(
        result: result ?? this.result,
        support: support ?? this.support,
        settings: settings ?? this.settings,
        application: application ?? this.application,
        notification: notification ?? this.notification,
        users: users ?? this.users,
        credit: credit ?? this.credit,
      );

  factory ApiResponseModel.fromJson(String str) =>
      ApiResponseModel.fromMap(json.decode(str));

  String toJson() => json.encode(toMap());

  factory ApiResponseModel.fromMap(Map<String, dynamic> json) =>
      ApiResponseModel(
        result: json["result"],
        support: Support.fromMap(json["support"]),
        settings: Settings.fromMap(json["settings"]),
        application: Application.fromMap(json["application"]),
        notification: Notification.fromMap(json["notification"]),
        users: Users.fromMap(json["users"]),
        credit: json["credit"],
      );

  Map<String, dynamic> toMap() => {
        "result": result,
        "support": support.toMap(),
        "settings": settings.toMap(),
        "application": application.toMap(),
        "notification": notification.toMap(),
        "users": users.toMap(),
        "credit": credit,
      };
}

class Application {
  final String id;
  final String version;
  final String link;

  Application({
    required this.id,
    required this.version,
    required this.link,
  });

  Application copyWith({
    String? id,
    String? version,
    String? link,
  }) =>
      Application(
        id: id ?? this.id,
        version: version ?? this.version,
        link: link ?? this.link,
      );

  factory Application.fromJson(String str) =>
      Application.fromMap(json.decode(str));

  String toJson() => json.encode(toMap());

  factory Application.fromMap(Map<String, dynamic> json) => Application(
        id: json["id"],
        version: json["version"],
        link: json["link"],
      );

  Map<String, dynamic> toMap() => {
        "id": id,
        "version": version,
        "link": link,
      };
}

class Notification {
  final String id;
  final String title;
  final String text;
  final String link;
  final String status;
  final String users;
  final DateTime date;

  Notification({
    required this.id,
    required this.title,
    required this.text,
    required this.link,
    required this.status,
    required this.users,
    required this.date,
  });

  Notification copyWith({
    String? id,
    String? title,
    String? text,
    String? link,
    String? status,
    String? users,
    DateTime? date,
  }) =>
      Notification(
        id: id ?? this.id,
        title: title ?? this.title,
        text: text ?? this.text,
        link: link ?? this.link,
        status: status ?? this.status,
        users: users ?? this.users,
        date: date ?? this.date,
      );

  factory Notification.fromJson(String str) =>
      Notification.fromMap(json.decode(str));

  String toJson() => json.encode(toMap());

  factory Notification.fromMap(Map<String, dynamic> json) => Notification(
        id: json["id"],
        title: json["title"],
        text: json["text"],
        link: json["link"],
        status: json["status"],
        users: json["users"],
        date: DateTime.parse(json["date"]),
      );

  Map<String, dynamic> toMap() => {
        "id": id,
        "title": title,
        "text": text,
        "link": link,
        "status": status,
        "users": users,
        "date":
            "${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}",
      };
}

class Settings {
  final String id;
  final String appName;
  final String appText;
  final String appLogo;
  final String appVersion;
  final String appDescription;
  final String appDevelopedBy;
  final String appGuestVolume;
  final String apiGuestDay;
  final String apiGuestOpt;
  final String apiGuestStatus;

  Settings({
    required this.id,
    required this.appName,
    required this.appText,
    required this.appLogo,
    required this.appVersion,
    required this.appDescription,
    required this.appDevelopedBy,
    required this.appGuestVolume,
    required this.apiGuestDay,
    required this.apiGuestOpt,
    required this.apiGuestStatus,
  });

  Settings copyWith({
    String? id,
    String? appName,
    String? appText,
    String? appLogo,
    String? appVersion,
    String? appDescription,
    String? appDevelopedBy,
    String? appGuestVolume,
    String? apiGuestDay,
    String? apiGuestOpt,
    String? apiGuestStatus,
  }) =>
      Settings(
        id: id ?? this.id,
        appName: appName ?? this.appName,
        appText: appText ?? this.appText,
        appLogo: appLogo ?? this.appLogo,
        appVersion: appVersion ?? this.appVersion,
        appDescription: appDescription ?? this.appDescription,
        appDevelopedBy: appDevelopedBy ?? this.appDevelopedBy,
        appGuestVolume: appGuestVolume ?? this.appGuestVolume,
        apiGuestDay: apiGuestDay ?? this.apiGuestDay,
        apiGuestOpt: apiGuestOpt ?? this.apiGuestOpt,
        apiGuestStatus: apiGuestStatus ?? this.apiGuestStatus,
      );

  factory Settings.fromJson(String str) => Settings.fromMap(json.decode(str));

  String toJson() => json.encode(toMap());

  factory Settings.fromMap(Map<String, dynamic> json) => Settings(
        id: json["id"],
        appName: json["app_name"],
        appText: json["app_text"],
        appLogo: json["app_logo"],
        appVersion: json["app_version"],
        appDescription: json["app_description"],
        appDevelopedBy: json["app_developed_by"],
        appGuestVolume: json["app_guest_volume"],
        apiGuestDay: json["api_guest_day"],
        apiGuestOpt: json["api_guest_opt"],
        apiGuestStatus: json["api_guest_status"],
      );

  Map<String, dynamic> toMap() => {
        "id": id,
        "app_name": appName,
        "app_text": appText,
        "app_logo": appLogo,
        "app_version": appVersion,
        "app_description": appDescription,
        "app_developed_by": appDevelopedBy,
        "app_guest_volume": appGuestVolume,
        "api_guest_day": apiGuestDay,
        "api_guest_opt": apiGuestOpt,
        "api_guest_status": apiGuestStatus,
      };
}

class Support {
  final String id;
  final String titleBuy;
  final String nameBuy;
  final String buy;
  final String titleChannel;
  final String nameChannel;
  final String channel;

  Support({
    required this.id,
    required this.titleBuy,
    required this.nameBuy,
    required this.buy,
    required this.titleChannel,
    required this.nameChannel,
    required this.channel,
  });

  Support copyWith({
    String? id,
    String? titleBuy,
    String? nameBuy,
    String? buy,
    String? titleChannel,
    String? nameChannel,
    String? channel,
  }) =>
      Support(
        id: id ?? this.id,
        titleBuy: titleBuy ?? this.titleBuy,
        nameBuy: nameBuy ?? this.nameBuy,
        buy: buy ?? this.buy,
        titleChannel: titleChannel ?? this.titleChannel,
        nameChannel: nameChannel ?? this.nameChannel,
        channel: channel ?? this.channel,
      );

  factory Support.fromJson(String str) => Support.fromMap(json.decode(str));

  String toJson() => json.encode(toMap());

  factory Support.fromMap(Map<String, dynamic> json) => Support(
        id: json["id"],
        titleBuy: json["title_buy"],
        nameBuy: json["name_buy"],
        buy: json["buy"],
        titleChannel: json["title_channel"],
        nameChannel: json["name_channel"],
        channel: json["channel"],
      );

  Map<String, dynamic> toMap() => {
        "id": id,
        "title_buy": titleBuy,
        "name_buy": nameBuy,
        "buy": buy,
        "title_channel": titleChannel,
        "name_channel": nameChannel,
        "channel": channel,
      };
}

class Users {
  final String id;
  final String username;
  final String password;
  final String size;
  final String total;
  final String full;
  final String subscription;
  final DateTime dateBuy;
  final DateTime date;
  final String adate;
  final String owner;
  final String agents;
  final String price;
  final String permium;
  final String multi;
  final String active;
  final String status;
  final String oprator;
  final String login;
  final String deviceImei;
  final String isLogin;
  final String fromId;

  Users({
    required this.id,
    required this.username,
    required this.password,
    required this.size,
    required this.total,
    required this.full,
    required this.subscription,
    required this.dateBuy,
    required this.date,
    required this.adate,
    required this.owner,
    required this.agents,
    required this.price,
    required this.permium,
    required this.multi,
    required this.active,
    required this.status,
    required this.oprator,
    required this.login,
    required this.deviceImei,
    required this.isLogin,
    required this.fromId,
  });

  Users copyWith({
    String? id,
    String? username,
    String? password,
    String? size,
    String? total,
    String? full,
    String? subscription,
    DateTime? dateBuy,
    DateTime? date,
    String? adate,
    String? owner,
    String? agents,
    String? price,
    String? permium,
    String? multi,
    String? active,
    String? status,
    String? oprator,
    String? login,
    String? deviceImei,
    String? isLogin,
    String? fromId,
  }) =>
      Users(
        id: id ?? this.id,
        username: username ?? this.username,
        password: password ?? this.password,
        size: size ?? this.size,
        total: total ?? this.total,
        full: full ?? this.full,
        subscription: subscription ?? this.subscription,
        dateBuy: dateBuy ?? this.dateBuy,
        date: date ?? this.date,
        adate: adate ?? this.adate,
        owner: owner ?? this.owner,
        agents: agents ?? this.agents,
        price: price ?? this.price,
        permium: permium ?? this.permium,
        multi: multi ?? this.multi,
        active: active ?? this.active,
        status: status ?? this.status,
        oprator: oprator ?? this.oprator,
        login: login ?? this.login,
        deviceImei: deviceImei ?? this.deviceImei,
        isLogin: isLogin ?? this.isLogin,
        fromId: fromId ?? this.fromId,
      );

  factory Users.fromJson(String str) => Users.fromMap(json.decode(str));

  String toJson() => json.encode(toMap());

  factory Users.fromMap(Map<String, dynamic> json) => Users(
        id: json["id"],
        username: json["username"],
        password: json["password"],
        size: json["size"],
        total: json["total"],
        full: json["full"],
        subscription: json["subscription"],
        dateBuy: DateTime.parse(json["date_buy"]),
        date: DateTime.parse(json["date"]),
        adate: json["adate"],
        owner: json["owner"],
    agents: json["agents"],
        price: json["price"],
        permium: json["permium"],
        multi: json["multi"],
        active: json["active"],
        status: json["status"],
        oprator: json["oprator"],
        login: json["login"],
        deviceImei: json["deviceIMEI"],
        isLogin: json["is_login"],
        fromId: json["from_id"],
      );

  Map<String, dynamic> toMap() => {
        "id": id,
        "username": username,
        "password": password,
        "size": size,
        "total": total,
        "full": full,
        "subscription": subscription,
        "date_buy":
            "${dateBuy.year.toString().padLeft(4, '0')}-${dateBuy.month.toString().padLeft(2, '0')}-${dateBuy.day.toString().padLeft(2, '0')}",
        "date":
            "${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}",
        "adate": adate,
    "owner": owner,
    "agents": agents,
        "price": price,
        "permium": permium,
        "multi": multi,
        "active": active,
        "status": status,
        "oprator": oprator,
        "login": login,
        "deviceIMEI": deviceImei,
        "is_login": isLogin,
        "from_id": fromId,
      };
}
