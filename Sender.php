<?php

class Sender
{
    protected Modem $modem;
    protected String $number;
    protected String $text;

    public function __construct(modem $modem, string $number, string $text)
    {
        $this->modem = $modem;
        $this->number = $number;
        $this->text = $text;
    }

    public function sendAsPlainText(): bool
    {
        $this->modem->setCMGF(1);
        $this->modem->setCMGS("\"+$this->number\"");
        return $this->modem->setCommand($this->text . chr(26));
    }


    public function sendAsPacketDataUnit(): bool
    {
        // отправка составной смс в цифровом формате длинной до 67 знаков каждая  с поддержкой кириллицы
        $this->modem->setCMGF(0);
        $parts = $this->getTextParts();

        foreach ($parts as $index => $part) {
            $pdu = $this->getPacketDataUnit($part,  count($parts), $index + 1);
            $this->modem->setCMGS($pdu['len']);
            $this->modem->setCommand($pdu['data'] . chr(26));
        }

        return true;
    }

    private function getTextParts(): array
    {
        $max_length = 67;
        return mb_str_split($this->text, $max_length);
    }

    private function getPacketDataUnit(string $partText, int $partCount, int $partIndex): array
    {
        // формирует packet data unit
        $sca = $this->getSCA();
        $pduType = $this->getPDUType($partCount);
        $tpMR = $this->getMR();
        $tpDAL =  $this->getDAL();
        $tpDAT = $this->getDAT();
        $tpDA = $this->getDA(); // форматированный номер
        $tpPID =  $this->getPID();;
        $tpDCS = $this->getDCS();
        $tpVP = $this->getVP();
        $tpUDH = $this->getUDH($partCount, $partIndex); // заголовок (нужен для составных смс)
        $tpUD = $this->getUD($partText); // сообщение
        $tpUDL = $this->getUDL($tpUDH, $tpUD); // длина данных пользователя, включая заголовок

        $pdu = $sca . $pduType . $tpMR . $tpDAL . $tpDAT . $tpDA . $tpPID . $tpDCS . $tpVP . $tpUDL . $tpUDH . $tpUD;
        $pdu = strtoupper($pdu);

        $tmp = [];
        $tmp['lenFull'] = strlen($pdu);
        $tmp['len'] = (mb_strlen($pdu) - 2) / 2;
        $tmp['partCount'] = $partCount;
        $tmp['partIndex'] = $partIndex;
        $tmp['textLength'] = strlen($partText);
        $tmp['data'] = $pdu;
        return $tmp;
    }

    private function getSCA(): string
    {
        return "00";
    }

    private function getPDUType(int $partCount): string
    {
        return $partCount == 1 ? "01" : "41"; // тип сообщения (01 - одиночное, 41 - составное
    }

    private function getMR(): string
    {
        return "00";
    }

    private function getDAL(): string
    {
        return sprintf("%'.02s", dechex(strlen($this->number))); // длина номера       
    }

    private function getDAT(): string
    {
        return "91"; // тип номера: 91 - междунродный стандарты        
    }

    private function getPID(): string
    {
        return "00";
    }

    private function getDCS(): string
    {
        return "08"; // 00 -7-бит, 160 знаков без кириллицы; 08 - 70 знаков, с кириллицей; 10 - то же что 00, токак flash, 18 - то же, что 08, токак flash
    }

    private function getVP(): string
    {
        return "";
    }

    private function getUDH(int $partCount, int $partIndex): string
    {
        return ($partCount == 1) ? "" : "050003FF" . sprintf("%'.02s", dechex($partCount)) . sprintf("%'.02s", dechex($partIndex));
    }

    private function getUD(string $partText): string
    {
        return bin2hex(iconv("utf-8", "UCS-2", $partText));
    }

    private function getUDL(string $tpUDH, string $tpUD): string
    {
        return sprintf("%'.02s", dechex(strlen($tpUDH . $tpUD) / 2));
    }

    function getDA(): string
    {
        // перемешивание символов в номере        
        $number = $this->number;        
        $number .=  !(strlen($number)  & 1) ? "" : "F";
        $number = str_split($number, 2);
        
        $tmp = '';
        foreach($number as $value){            
            $tmp .=  strrev($value);
        }

        return $tmp;
    }
}