import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import '../helpers/pref.dart';

class ChatScreen extends StatefulWidget {
  const ChatScreen({super.key});

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  static const Color _accent = Color(0xff416ff4);
  final List<Map<String, dynamic>> _messages = [];
  final TextEditingController _input = TextEditingController();
  final ScrollController _scroll = ScrollController();
  Timer? _timer;
  int _lastId = 0;
  bool _sending = false;

  String get _username => Pref.username;

  String _chatUrl() {
    final p = urlPost;
    if (p.isEmpty) return '';
    final i = p.lastIndexOf('/');
    return i >= 0 ? '${p.substring(0, i + 1)}chat.php' : 'chat.php';
  }

  @override
  void initState() {
    super.initState();
    _fetch();
    _timer = Timer.periodic(const Duration(seconds: 4), (_) => _fetch());
  }

  @override
  void dispose() {
    _timer?.cancel();
    _input.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _fetch() async {
    final url = _chatUrl();
    if (url.isEmpty || _username.isEmpty) return;
    try {
      final res = await http.post(Uri.parse(url), body: {
        'action': 'fetch',
        'username': _username,
        'after_id': _lastId.toString(),
      }).timeout(const Duration(seconds: 15));
      if (res.statusCode != 200) return;
      final data = jsonDecode(res.body);
      if (data is Map && data['ok'] == true && data['messages'] is List) {
        final msgs = (data['messages'] as List);
        if (msgs.isNotEmpty) {
          setState(() {
            for (final m in msgs) {
              _messages.add(Map<String, dynamic>.from(m as Map));
              final id = int.tryParse('${m['id']}') ?? 0;
              if (id > _lastId) _lastId = id;
            }
          });
          _scrollToBottom();
        }
      }
    } catch (_) {}
  }

  Future<void> _send() async {
    final text = _input.text.trim();
    if (text.isEmpty || _sending) return;
    final url = _chatUrl();
    if (url.isEmpty || _username.isEmpty) return;
    setState(() => _sending = true);
    _input.clear();
    try {
      await http.post(Uri.parse(url), body: {
        'action': 'send',
        'username': _username,
        'text': text,
      }).timeout(const Duration(seconds: 15));
    } catch (_) {}
    if (mounted) setState(() => _sending = false);
    await _fetch();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scroll.hasClients) {
        _scroll.animateTo(
          _scroll.position.maxScrollExtent,
          duration: const Duration(milliseconds: 250),
          curve: Curves.easeOut,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: const Color(0xff0e0e14),
        appBar: AppBar(
          backgroundColor: const Color(0xff14141c),
          elevation: 0,
          title: const Text('چت پشتیبانی', style: TextStyle(color: Colors.white)),
          iconTheme: const IconThemeData(color: Colors.white),
        ),
        body: Column(
          children: [
            Expanded(
              child: _messages.isEmpty
                  ? const Center(
                      child: Text('پیامی نیست. سوالت را بنویس.',
                          style: TextStyle(color: Colors.white54)),
                    )
                  : ListView.builder(
                      controller: _scroll,
                      padding: const EdgeInsets.all(12),
                      itemCount: _messages.length,
                      itemBuilder: (_, i) => _bubble(_messages[i]),
                    ),
            ),
            SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.all(8),
                child: Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _input,
                        style: const TextStyle(color: Colors.white),
                        minLines: 1,
                        maxLines: 4,
                        textInputAction: TextInputAction.send,
                        decoration: InputDecoration(
                          hintText: 'پیام...',
                          hintStyle: const TextStyle(color: Colors.white38),
                          filled: true,
                          fillColor: const Color(0xff1c1c26),
                          contentPadding:
                              const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(22),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        onSubmitted: (_) => _send(),
                      ),
                    ),
                    const SizedBox(width: 8),
                    GestureDetector(
                      onTap: _send,
                      child: Container(
                        width: 46,
                        height: 46,
                        decoration: const BoxDecoration(
                            color: _accent, shape: BoxShape.circle),
                        child: Icon(
                          _sending ? Icons.hourglass_empty : Icons.send_rounded,
                          color: Colors.white,
                          size: 20,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _bubble(Map<String, dynamic> m) {
    final isUser = (m['sender']?.toString() ?? 'user') == 'user';
    return Align(
      alignment: isUser ? Alignment.centerLeft : Alignment.centerRight,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 4),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
        constraints:
            BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.75),
        decoration: BoxDecoration(
          color: isUser ? _accent : const Color(0xff22222e),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Text(
          m['text']?.toString() ?? '',
          style: const TextStyle(color: Colors.white, fontSize: 14, height: 1.6),
        ),
      ),
    );
  }
}
