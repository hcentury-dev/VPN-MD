import 'package:get/get.dart';

class ApplicationLanguage extends Translations {
  @override
  Map<String, Map<String, String>> get keys => {
    // 🌐 فارسی
    'fa_IR': {
      "server1": "سرورها",
      "search": "جستجو",
      "all": "همه",
      "testAllServers": "تست همه سرورها",
      "autoBestLocation": "انتخاب بهترین لوکیشن خودکار",
      "checkingTheFastestServer": "در حال بررسی سریع‌ترین سرور...",
      "back": "بازگشت",
      "connect": "اتصال",
      "disconnect": "قطع اتصال",
    },

    // 🌐 انگلیسی
    'en_US': {
      "server1": "Servers",
      "search": "Search",
      "all": "All",
      "testAllServers": "Test All Servers",
      "autoBestLocation": "Auto Select Best Location",
      "checkingTheFastestServer": "Checking the fastest server...",
      "back": "Back",
      "connect": "Connect",
      "disconnect": "Disconnect",
    },

    // 🌐 چینی ساده
    'zh_CN': {
      "server1": "服务器",
      "search": "搜索",
      "all": "全部",
      "testAllServers": "测试所有服务器",
      "autoBestLocation": "自动选择最佳位置",
      "checkingTheFastestServer": "正在检测最快的服务器...",
      "back": "返回",
      "connect": "连接",
      "disconnect": "断开连接",
    },

    // 🌐 روسی
    'ru_RU': {
      "server1": "Серверы",
      "search": "Поиск",
      "all": "Все",
      "testAllServers": "Тестировать все серверы",
      "autoBestLocation": "Автовыбор лучшего расположения",
      "checkingTheFastestServer": "Проверка самого быстрого сервера...",
      "back": "Назад",
      "connect": "Подключить",
      "disconnect": "Отключить",
    },
  };
}
