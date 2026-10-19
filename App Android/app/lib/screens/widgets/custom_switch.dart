import 'package:flutter/material.dart';

class VerticalSwitch extends StatelessWidget {
  final bool value;
  final ValueChanged<bool> onChanged;

  const VerticalSwitch({
    super.key,
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => onChanged(!value),
      child: AnimatedContainer(
        duration: Duration(milliseconds: 300),
        width: 40,
        height: 70,
        padding: EdgeInsets.symmetric(vertical: 6),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(20),
          color: value ? Colors.green : Colors.grey.shade500,
        ),
        child: Stack(
          children: [

            AnimatedAlign(
              duration: Duration(milliseconds: 300),
              alignment: value ? Alignment.bottomCenter : Alignment.topCenter,
              child: Container(
                width: 30,
                height: 30,
                decoration: BoxDecoration(
                  color: Colors.white,
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black26,
                      blurRadius: 4,
                      offset: Offset(0, 2),
                    ),
                  ],
                ),
                child: Icon(
                  value ? Icons.keyboard_arrow_down : Icons.keyboard_arrow_up,
                  color: value ? Colors.green : Colors.red,
                  size: 20,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
