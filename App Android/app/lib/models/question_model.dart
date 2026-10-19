class QuestionsModel {
  final String title;
  final String text;
  final String status;

  QuestionsModel({
    required this.title,
    required this.text,
    required this.status,
  });

  Map<String, dynamic> toMap() {
    return <String, dynamic>{
      'title': title,
      'text': text,
      'status': status,
    };
  }

  factory QuestionsModel.fromMap(Map<String, dynamic> map) {
    return QuestionsModel(
      title: map['title'] as String,
      text: map['text'] as String,
      status: map['status'] as String,
    );
  }
}
