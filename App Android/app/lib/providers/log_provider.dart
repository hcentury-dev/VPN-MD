import 'package:flutter_riverpod/flutter_riverpod.dart';

final logProvider = StateProvider<List<String>>((ref) {
  return [];
});
