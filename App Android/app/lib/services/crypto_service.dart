import 'dart:convert';
import 'dart:typed_data';
import 'package:encrypt/encrypt.dart' as encrypt;

import '../helpers/pref.dart';

class CryptoService {
  final String keyString = key;

  String? decryptConfig(String encryptedConfigData) {
    try {
      final key = encrypt.Key.fromUtf8(keyString.substring(0, 32));
      final encrypter =
          encrypt.Encrypter(encrypt.AES(key, mode: encrypt.AESMode.cbc));

      final encryptedBytes = base64.decode(encryptedConfigData);
      final iv = encrypt.IV(encryptedBytes.sublist(0, 16));
      final encryptedData =
          encrypt.Encrypted(Uint8List.fromList(encryptedBytes.sublist(16)));

      return encrypter.decrypt(encryptedData, iv: iv);
    } catch (e) {
      print("Decryption error: $e");
      return null;
    }
  }
}
