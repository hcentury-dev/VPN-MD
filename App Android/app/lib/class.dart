import 'package:hive/hive.dart';

part 'class.g.dart';

@HiveType(typeId: 0)
class VPNClass {
  @HiveField(0)
  String serverId;
  @HiveField(1)
  String nameFa;
  @HiveField(2)
  String nameEn;
  @HiveField(3)
  String src;
  @HiveField(4)
  String locationsFa;
  @HiveField(5)
  String locationsEn;
  @HiveField(6)
  String titleFa;
  @HiveField(7)
  String titleEn;
  @HiveField(8)
  String link;
  @HiveField(9)
  String permium;
  @HiveField(10)
  String fragment;
  @HiveField(11)
  String json;
  @HiveField(12)
  String operator;
  @HiveField(13)
  String status;
  @HiveField(14)
  String date;
  @HiveField(15)
  int? ping;

  VPNClass({
    this.serverId = '',
    this.nameFa = '',
    this.nameEn = '',
    this.src = '',
    this.locationsFa = '',
    this.locationsEn = '',
    this.titleFa = '',
    this.titleEn = '',
    this.link = '',
    this.permium = '',
    this.fragment = '',
    this.json = '',
    this.operator = '',
    this.status = '',
    this.date = '',
    this.ping,
  });

  VPNClass copyWith({
    String? serverId,
    String? nameFa,
    String? nameEn,
    String? src,
    String? locationsFa,
    String? locationsEn,
    String? titleFa,
    String? titleEn,
    String? link,
    String? permium,
    String? fragment,
    String? json,
    String? operator,
    String? status,
    String? date,
    int? ping,
  }) {
    return VPNClass(
      serverId: serverId ?? this.serverId,
      nameFa: nameFa ?? this.nameFa,
      nameEn: nameEn ?? this.nameEn,
      src: src ?? this.src,
      locationsFa: locationsFa ?? this.locationsFa,
      locationsEn: locationsEn ?? this.locationsEn,
      titleFa: titleFa ?? this.titleFa,
      titleEn: titleEn ?? this.titleEn,
      link: link ?? this.link,
      permium: permium ?? this.permium,
      fragment: fragment ?? this.fragment,
      json: json ?? this.json,
      operator: operator ?? this.operator,
      status: status ?? this.status,
      date: date ?? this.date,
      ping: ping ?? this.ping,
    );
  }

  factory VPNClass.fromJson(Map<String, dynamic> json) {
    return VPNClass(
      serverId: json['serverId']?.toString() ?? '',
      nameFa: json['nameFa'] ?? '',
      nameEn: json['nameEn'] ?? '',
      src: json['src'] ?? '',
      locationsFa: json['locationsFa'] ?? '',
      locationsEn: json['locationsEn'] ?? '',
      titleFa: json['titleFa'] ?? '',
      titleEn: json['titleEn'] ?? '',
      link: json['link'] ?? '',
      permium: json['permium'] ?? '',
      fragment: json['fragment'] ?? '',
      json: json['json'] ?? '',
      operator: json['operator'] ?? '',
      status: json['status'] ?? '',
      date: json['date'] ?? '',
      ping: json['ping'] != null ? int.tryParse(json['ping'].toString()) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'serverId': serverId,
      'nameFa': nameFa,
      'nameEn': nameEn,
      'src': src,
      'locationsFa': locationsFa,
      'locationsEn': locationsEn,
      'titleFa': titleFa,
      'titleEn': titleEn,
      'link': link,
      'permium': permium,
      'fragment': fragment,
      'json': json,
      'operator': operator,
      'status': status,
      'date': date,
      'ping': ping,
    };
  }
}
