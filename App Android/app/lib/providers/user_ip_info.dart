// ignore_for_file: public_member_api_docs, sort_constructors_first
class UserIpInfo {
  final String? ip;
  final String? continentCode;
  final String? country;
  final String? countryCode;
  final String? countryCode3;
  final String? region;
  final String? regionCode;
  final String? city;
  final num? latitude;
  final num? longitude;
  final String? timezone;
  final num? offset;
  final num? asn;
  final String? organization;

  UserIpInfo({
    this.ip,
    this.continentCode,
    this.country,
    this.countryCode,
    this.countryCode3,
    this.region,
    this.regionCode,
    this.city,
    this.latitude,
    this.longitude,
    this.timezone,
    this.offset,
    this.asn,
    this.organization,
  });

  UserIpInfo.fromJson(Map<String, dynamic> json)
      : ip = json['ip'],
        continentCode = json['continent_code'],
        country = json['country'],
        countryCode = json['country_code'],
        countryCode3 = json['country_code3'],
        region = json['region'],
        regionCode = json['region_code'],
        city = json['city'],
        latitude = json['latitude'],
        longitude = json['longitude'],
        timezone = json['timezone'],
        offset = json['offset'],
        asn = json['asn'],
        organization = json['organization'];

  Map<String, dynamic> toJson() {
    Map<String, dynamic> json = <String, dynamic>{};
    json['ip'] = ip;
    json['continent_code'] = continentCode;
    json['country'] = country;
    json['country_code'] = countryCode;
    json['country_code3'] = countryCode3;
    json['region'] = region;
    json['region_code'] = regionCode;
    json['city'] = city;
    json['latitude'] = latitude;
    json['longitude'] = longitude;
    json['timezone'] = timezone;
    json['offset'] = offset;
    json['asn'] = asn;
    json['organization'] = organization;

    return json;
  }

  @override
  String toString() {
    return 'ip: $ip, continentCode: $continentCode, country: $country, countryCode: $countryCode, countryCode3: $countryCode3, region: $region, regionCode: $regionCode, city: $city, latitude: $latitude, longitude: $longitude, timezone: $timezone, offset: $offset, asn: $asn, organization: $organization';
  }

  @override
  bool operator ==(covariant UserIpInfo other) {
    if (identical(this, other)) return true;

    return other.ip == ip &&
        other.continentCode == continentCode &&
        other.country == country &&
        other.countryCode == countryCode &&
        other.countryCode3 == countryCode3 &&
        other.region == region &&
        other.regionCode == regionCode &&
        other.city == city &&
        other.latitude == latitude &&
        other.longitude == longitude &&
        other.timezone == timezone &&
        other.offset == offset &&
        other.asn == asn &&
        other.organization == organization;
  }

  @override
  int get hashCode {
    return ip.hashCode ^
        continentCode.hashCode ^
        country.hashCode ^
        countryCode.hashCode ^
        countryCode3.hashCode ^
        region.hashCode ^
        regionCode.hashCode ^
        city.hashCode ^
        latitude.hashCode ^
        longitude.hashCode ^
        timezone.hashCode ^
        offset.hashCode ^
        asn.hashCode ^
        organization.hashCode;
  }
}
