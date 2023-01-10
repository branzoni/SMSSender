<?php

include("./Modem.php");
include("./Sender.php");

$message = "Ночь. Улица. Фонарь. Аптека. Я покупаю вазелин. За мной стоят два человека: армян и сумрачный грузин. Вот скрипнула в подъезд пружина и повторилось все как встарь: пустая банка вазелина, аптека, улица, фонарь.";
$sender = new Sender(new Modem("COM5"), "79190716167", $message);
echo $sender->getDA();
//$sender->sendAsPlainText();

