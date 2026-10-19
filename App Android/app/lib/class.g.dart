// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'class.dart';

// **************************************************************************
// TypeAdapterGenerator
// **************************************************************************

class VPNClassAdapter extends TypeAdapter<VPNClass> {
  @override
  final int typeId = 0;

  @override
  VPNClass read(BinaryReader reader) {
    final numOfFields = reader.readByte();
    final fields = <int, dynamic>{
      for (int i = 0; i < numOfFields; i++) reader.readByte(): reader.read(),
    };
    return VPNClass(
      serverId: fields[0] as String? ?? '',
      nameFa: fields[1] as String? ?? '',
      nameEn: fields[2] as String? ?? '',
      src: fields[3] as String? ?? '',
      locationsFa: fields[4] as String? ?? '',
      locationsEn: fields[5] as String? ?? '',
      titleFa: fields[6]?.toString() ?? '',
      titleEn: fields[7] as String? ?? '',
      link: fields[8] as String? ?? '',
      permium: fields[9] as String? ?? '',
      fragment: fields[10] as String? ?? '',
      json: fields[11] as String? ?? '',
      operator: fields[12] as String? ?? '',
      status: fields[13] as String? ?? '',
      date: fields[14] as String? ?? '',
      ping: fields[15] as int?,
    );
  }

  @override
  void write(BinaryWriter writer, VPNClass obj) {
    writer
      ..writeByte(16)
      ..writeByte(0)
      ..write(obj.serverId)
      ..writeByte(1)
      ..write(obj.nameFa)
      ..writeByte(2)
      ..write(obj.nameEn)
      ..writeByte(3)
      ..write(obj.src)
      ..writeByte(4)
      ..write(obj.locationsFa)
      ..writeByte(5)
      ..write(obj.locationsEn)
      ..writeByte(6)
      ..write(obj.titleFa)
      ..writeByte(7)
      ..write(obj.titleEn)
      ..writeByte(8)
      ..write(obj.link)
      ..writeByte(9)
      ..write(obj.permium)
      ..writeByte(10)
      ..write(obj.fragment)
      ..writeByte(11)
      ..write(obj.json)
      ..writeByte(12)
      ..write(obj.operator)
      ..writeByte(13)
      ..write(obj.status)
      ..writeByte(14)
      ..write(obj.date)
      ..writeByte(15)
      ..write(obj.ping);
  }

  @override
  int get hashCode => typeId.hashCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is VPNClassAdapter &&
          runtimeType == other.runtimeType &&
          typeId == other.typeId;
}
