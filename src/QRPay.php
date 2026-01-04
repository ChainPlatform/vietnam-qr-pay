<?php

namespace ChainPlatform\Pay;

use ChainPlatform\Pay\Constants\FieldID;
use ChainPlatform\Pay\Constants\QRProviderGUID;
use ChainPlatform\Pay\Constants\VietQRService;

class QRPay
{
    public $isValid = true;
    public $version = '01';
    public $initMethod = '11';
    public $provider;
    public $consumer;
    public $amount;
    public $additionalData;
    public $EVMCo = [];
    public $unreserved = [];

    public function __construct(string $content = '')
    {
        $this->provider = new \stdClass();
        $this->consumer = new \stdClass();
        $this->additionalData = new \stdClass();
        if (!empty($content)) {
            $this->parse($content);
        }
    }

    public function parse(string $content): void
    {
        if (strlen($content) < 4 || !$this->verifyCRC($content)) {
            $this->isValid = false;
            return;
        }
        $this->parseRootContent($content);
    }

    public function build(): string
    {
        $content = self::genFieldData(FieldID::VERSION, $this->version);
        $content .= self::genFieldData(FieldID::INIT_METHOD, $this->initMethod);

        $innerData = self::genFieldData('00', $this->consumer->bankBin) . self::genFieldData('01', $this->consumer->bankNumber);
        $providerData = self::genFieldData('00', $this->provider->guid) . self::genFieldData('01', $innerData) . self::genFieldData('02', $this->provider->service);

        $content .= self::genFieldData($this->provider->fieldId, $providerData);
        $content .= self::genFieldData(FieldID::AMOUNT, $this->amount);
        $content .= self::genFieldData(FieldID::ADDITIONAL_DATA, self::genFieldData('08', $this->additionalData->purpose));

        ksort($this->EVMCo);
        foreach ($this->EVMCo as $id => $val) {
            $content .= self::genFieldData($id, $val);
        }
        ksort($this->unreserved);
        foreach ($this->unreserved as $id => $val) {
            $content .= self::genFieldData($id, $val);
        }

        $content .= FieldID::CRC . "04";
        return $content . strtoupper(str_pad(dechex(CRC16::crc16ccitt($content)), 4, '0', STR_PAD_LEFT));
    }

    public static function initVietQR(array $options): self
    {
        $qr = new self();
        $qr->provider->fieldId = FieldID::VIETQR;
        $qr->provider->guid = QRProviderGUID::VIETQR;
        $qr->provider->service = $options['service'] ?? VietQRService::BY_ACCOUNT_NUMBER;
        $qr->consumer->bankBin = $options['bankBin'];
        $qr->consumer->bankNumber = $options['bankNumber'];
        $qr->amount = $options['amount'] ?? null;
        $qr->additionalData->purpose = $options['purpose'] ?? null;
        return $qr;
    }

    public function setEVMCoField(string $id, string $value): void
    {
        if ((int)$id >= 65 && (int)$id <= 79) {
            $this->EVMCo[$id] = $value;
        }
    }
    public function setUnreservedField(string $id, string $value): void
    {
        if ((int)$id >= 80 && (int)$id <= 99) {
            $this->unreserved[$id] = $value;
        }
    }
    private function verifyCRC($c): bool
    {
        return strtoupper(substr($c, -4)) === strtoupper(str_pad(dechex(CRC16::crc16ccitt(substr($c, 0, -4))), 4, '0', STR_PAD_LEFT));
    }
    private function sliceContent($c): array
    {
        return ['id' => substr($c, 0, 2), 'value' => substr($c, 4, (int)substr($c, 2, 2)), 'nextValue' => substr($c, 4 + (int)substr($c, 2, 2))];
    }
    private static function genFieldData($i, $v): string
    {
        return (empty($i) || empty($v)) ? '' : $i . str_pad(strlen($v), 2, '0', STR_PAD_LEFT) . $v;
    }
    private function parseRootContent($c): void
    {
        $slice = $this->sliceContent($c);
        if ($slice['id'] === FieldID::VERSION) {
            $this->version = $slice['value'];
        }
        if (strlen($slice['nextValue']) > 4) {
            $this->parseRootContent($slice['nextValue']);
        }
    }
}
