import 'dart:convert';
import 'dart:developer';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart';

import '../helpers/ip_details.dart';
import '../helpers/pref.dart';

class NetworkTestScreen extends StatefulWidget {
  const NetworkTestScreen({super.key});

  @override
  State<NetworkTestScreen> createState() => _NetworkTestScreenState();
}

class _NetworkTestScreenState extends State<NetworkTestScreen> {
  IPDetails ipData = IPDetails(country: "country", regionName: "regionName", city: "city", zip: "zip", timezone: "timezone", isp: "isp", query: "query");

  @override
  void initState() {
    // TODO: implement initState
    super.initState();
    _getisp();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: ColorBg,
      //refresh button
      appBar: AppBar(
        leading: IconButton(
          onPressed: () => Navigator.pop(context),
          icon: const Icon(
            Icons.arrow_back_ios,
            color: toolColorBg,
            size: 20,
          ),
        ),
        backgroundColor: Colors.transparent,
      ),
      floatingActionButton: loading
          ? FloatingActionButton(
              onPressed: () {},
              backgroundColor: updateColorBg,
              child: const CircularProgressIndicator(
                color: Colors.white,
              ),
            )
          : FloatingActionButton.extended(
              backgroundColor: updateColorBg,
              onPressed: () {
                _getisp();
              },
              label: const Text(
                'بروزرسانی',
                style: TextStyle(fontSize: 15, fontFamily: 'sans', color: updatetxtColor),
              ),
            ),
      body: ListView(
        physics: const BouncingScrollPhysics(),
        padding: EdgeInsets.only(
          top: 16 + MediaQuery.paddingOf(context).top,
          left: 16,
          right: 16,
          bottom: 16,
        ),
        children: [
          //ip
          NetworkCard(data: NetworkData(title: 'آدرس آی پی', subtitle: ipData.query, icon: const Icon(CupertinoIcons.location_solid, color: Colors.blue))),

          //isp
          NetworkCard(data: NetworkData(title: 'شرکت مخابراتی', subtitle: ipData.isp, icon: const Icon(Icons.business, color: Colors.orange))),

          //location
          NetworkCard(
              data: NetworkData(
                  title: 'موقعیت',
                  subtitle: ipData.country.isEmpty ? 'Fetching ...' : '${ipData.city}, ${ipData.regionName}, ${ipData.country}',
                  icon: const Icon(CupertinoIcons.location, color: Colors.pink))),

          //timezone
          NetworkCard(data: NetworkData(title: 'ساعت منطقه', subtitle: ipData.timezone, icon: const Icon(CupertinoIcons.time, color: Colors.green))),
        ],
      ),
    );
  }

  bool loading = true;
  _getisp() async {
    setState(() {
      loading = true;
    });
    try {
      final res = await get(Uri.parse('http://ip-api.com/json/'));
      final data = jsonDecode(res.body);

      ipData = IPDetails.fromJson(data);
    } catch (e) {
      log(e.toString());
    }
    setState(() {
      loading = false;
    });
  }
}

class NetworkData {
  String title, subtitle;
  Icon icon;

  NetworkData({required this.title, required this.subtitle, required this.icon});
}

class NetworkCard extends StatelessWidget {
  final NetworkData data;

  const NetworkCard({super.key, required this.data});

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 3,
      margin: EdgeInsets.symmetric(vertical: MediaQuery.of(context).size.height * .01),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: InkWell(
        onTap: () {},
        borderRadius: BorderRadius.circular(0),
        child: ListTile(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),

          //flag
          trailing: Icon(data.icon.icon, color: data.icon.color, size: data.icon.size ?? 28),

          //title
          title: Text(
            data.title,
            textDirection: TextDirection.rtl,
            style: const TextStyle(fontFamily: 'sans'),
          ),

          //subtitle
          subtitle: Text(
            data.subtitle,
            textDirection: TextDirection.rtl,
            style: const TextStyle(fontFamily: 'sans'),
          ),
        ),
      ),
    );
  }
}
