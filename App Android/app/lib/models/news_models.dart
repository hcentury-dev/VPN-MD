class NewsModel {
  final String id;
  final String title_fa;
  final String title_en;
  final String text_fa;
  final String text_en;
  final String show;
  final String status;
  final String date;
  final String users;

  NewsModel({
    required this.id,
    required this.title_fa,
    required this.title_en,
    required this.text_fa,
    required this.text_en,
    required this.show,
    required this.status,
    required this.date,
    required this.users,
  });

  factory NewsModel.fromMap(Map<String, dynamic> json) {
    return NewsModel(
      id: json['id'],
      title_fa: json['title_fa'],
      title_en: json['title_en'],
      text_fa: json['text_fa'],
      text_en: json['text_en'],
      show: json['main'],
      status: json['status'],
      date: json['date'],
      users: json['users'],
    );
  }
}
