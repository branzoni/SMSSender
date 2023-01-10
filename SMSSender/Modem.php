<?php

class Modem
{
    private $resource;
    protected $port;
    protected $speed = 115200;
    protected $lastError;

    public function __construct(string $port, $speed = 115200)
    {
        $this->port = $port;
        $this->speed = $speed;
    }

    public function openPort(): bool
    {
        if (!$this->setMode()) return false;
        $this->resource = $this->getHandle();       
        return boolval($this->resource);
    }

    public function closePort(): bool
    {
        $tmp = @fclose($this->resource);
        $tmp =  boolval($tmp);
        return $tmp;        
    }

    public function setCommand(string $cmd, int $timeout = 2): bool
    {
        if(!$this->resource) return false;
        $tmp = @fwrite($this->resource, $cmd);        
        $tmp =  boolval($tmp);
        sleep($timeout);
        return $tmp;
    }

    public function setCMGF($value): bool
    {
        return $this->setCommand("AT+CMGF=$value\r");
    }

    public function setCMGS(string $value): bool
    {
        return $this->setCommand("AT+CMGS=$value\r");
    }

    public function test(): bool
    {
        return $this->setCommand("AT\r");
    }

    private function setMode(): bool
    {
        $tmp = "mode $this->port BAUD=$this->speed PARITY=n DATA=8 STOP=1 xon=off octs=off rts=on";
        $tmp = @exec($tmp);
        return boolval($tmp);
    }

    private function getHandle()
    {
        $tmp = @fopen($this->port, "r+b");
        $tmp =  boolval($tmp);
        return $tmp;
    }
}
