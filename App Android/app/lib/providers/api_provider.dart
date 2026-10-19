import '../providers/api_repository.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

final apiProvider =
FutureProvider.family<List<String>, String>((ref, url) async {
  return ApiRepository.getConfigs(url);
});
