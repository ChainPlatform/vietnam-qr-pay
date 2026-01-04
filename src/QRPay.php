<?php

namespace ChainPlatform\Pay;

use ChainPlatform\Pay\Constants\FieldID;
use ChainPlatform\Pay\Constants\QRProviderGUID;
use ChainPlatform\Pay\Constants\VietQRService;
use ChainPlatform\Pay\Constants\ProviderFieldID;
use ChainPlatform\Pay\Constants\VietQRConsumerFieldID;
use ChainPlatform\Pay\Constants\AdditionalDataID;

class QRPay
{
    public $isValid = true;
    public $version;
    public $initMethod;
    public $provider;
    public $merchant;
    public $consumer;
    public $amount;
    public $currency = '704';
    public $nation = 'VN';
    public $additionalData;
    public $crc;
    public $EVMCo = [];
    public $unreserved = [];

    public function __construct(string $content = '')
    {
        $this->provider = new \stdClass();
        $this->merchant = new \stdClass();
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

    private function parseRootContent(string $content): void
    {
        if (strlen($content) < 4) {
            return;
        }

        $slice = $this->sliceContent($content);
        $id = $slice['id'];
        $value = $slice['value'];
        $nextValue = $slice['nextValue'];

        switch ($id) {
            case FieldID::VERSION: $this->version = $value;
                break;
            case FieldID::INIT_METHOD: $this->initMethod = $value;
                break;
            case FieldID::VIETQR:
            case FieldID::VNPAYQR:
                $this->provider->fieldId = $id;
                $this->parseProviderInfo($value);
                break;
            case FieldID::AMOUNT: $this->amount = $value;
                break;
            case FieldID::CURRENCY: $this->currency = $value;
                break;
            case FieldID::NATION: $this->nation = $value;
                break;
            case FieldID::MERCHANT_NAME: $this->merchant->name = $value;
                break;
            case FieldID::ADDITIONAL_DATA: $this->parseAdditionalData($value);
                break;
            case FieldID::CRC: $this->crc = $value;
                break;
            default:
                $idNum = (int)$id;
                if ($idNum >= 65 && $idNum <= 79) {
                    $this->EVMCo[$id] = $value;
                } elseif ($idNum >= 80 && $idNum <= 99) {
                    $this->unreserved[$id] = $value;
                }
                break;
        }
        $this->parseRootContent($nextValue);
    }

    private function parseProviderInfo(string $content): void
    {
        if (strlen($content) < 4) {
            return;
        }

        $slice = $this->sliceContent($content);
        $id = $slice['id'];
        $value = $slice['value'];

        switch ($id) {
            case ProviderFieldID::GUID:
                $this->provider->guid = $value;
                break;
            case ProviderFieldID::DATA:
                if ($this->provider->guid === QRProviderGUID::VIETQR) {
                    $this->parseVietQRConsumer($value);
                } elseif ($this->provider->guid === QRProviderGUID::VNPAY) {
                    $this->merchant->id = $value;
                }
                $this->provider->data = $value;
                break;
            case ProviderFieldID::SERVICE:
                $this->provider->service = $value;
                break;
        }
        $this->parseProviderInfo($slice['nextValue']);
    }

    private function parseVietQRConsumer(string $content): void
    {
        if (strlen($content) < 4) {
            return;
        }

        $slice = $this->sliceContent($content);
        if ($slice['id'] === VietQRConsumerFieldID::BANK_BIN) {
            $this->consumer->bankBin = $slice['value'];
        } elseif ($slice['id'] === VietQRConsumerFieldID::BANK_NUMBER) {
            $this->consumer->bankNumber = $slice['value'];
        }
        $this->parseVietQRConsumer($slice['nextValue']);
    }

    private function parseAdditionalData(string $content): void
    {
        if (strlen($content) < 4) {
            return;
        }

        $slice = $this->sliceContent($content);
        switch ($slice['id']) {
            case AdditionalDataID::PURPOSE_OF_TRANSACTION: $this->additionalData->purpose = $slice['value'];
                break;
            case AdditionalDataID::BILL_NUMBER: $this->additionalData->billNumber = $slice['value'];
                break;
            case AdditionalDataID::MOBILE_NUMBER: $this->additionalData->mobileNumber = $slice['value'];
                break;
            case AdditionalDataID::STORE_LABEL: $this->additionalData->store = $slice['value'];
                break;
            case AdditionalDataID::TERMINAL_LABEL: $this->additionalData->terminal = $slice['value'];
                break;
        }
        $this->parseAdditionalData($slice['nextValue']);
    }

    public function build(): string
    {
        $content = self::genFieldData(FieldID::VERSION, $this->version ?? '01');
        $content .= self::genFieldData(FieldID::INIT_METHOD, $this->initMethod ?? '11');

        $guid = self::genFieldData(ProviderFieldID::GUID, $this->provider->guid);
        $innerData = self::genFieldData(VietQRConsumerFieldID::BANK_BIN, $this->consumer->bankBin) .
                     self::genFieldData(VietQRConsumerFieldID::BANK_NUMBER, $this->consumer->bankNumber);

        $providerData = $guid . self::genFieldData(ProviderFieldID::DATA, $innerData) .
                        self::genFieldData(ProviderFieldID::SERVICE, $this->provider->service);

        $content .= self::genFieldData($this->provider->fieldId, $providerData);
        $content .= self::genFieldData(FieldID::CURRENCY, $this->currency ?? '704');
        $content .= self::genFieldData(FieldID::AMOUNT, $this->amount);
        $content .= self::genFieldData(FieldID::NATION, $this->nation ?? 'VN');

        $addContent = self::genFieldData(AdditionalDataID::PURPOSE_OF_TRANSACTION, $this->additionalData->purpose);
        $content .= self::genFieldData(FieldID::ADDITIONAL_DATA, $addContent);

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
        $qr->initMethod = isset($options['amount']) ? '12' : '11';
        $qr->provider->fieldId = FieldID::VIETQR;
        $qr->provider->guid = QRProviderGUID::VIETQR;
        $qr->provider->service = $options['service'] ?? VietQRService::BY_ACCOUNT_NUMBER;
        $qr->consumer->bankBin = $options['bankBin'];
        $qr->consumer->bankNumber = $options['bankNumber'];
        $qr->amount = $options['amount'] ?? null;
        $qr->additionalData->purpose = $options['purpose'] ?? null;
        return $qr;
    }

    private function verifyCRC($c): bool
    {
        $check = substr($c, 0, -4);
        $crcCode = strtoupper(substr($c, -4));
        return $crcCode === strtoupper(str_pad(dechex(CRC16::crc16ccitt($check)), 4, '0', STR_PAD_LEFT));
    }

    private function sliceContent($c): array
    {
        $id = substr($c, 0, 2);
        $lenString = substr($c, 2, 2);
        $length = (int)$lenString;
        $value = substr($c, 4, $length);
        $nextValue = substr($c, 4 + $length);
        return ['id' => $id, 'value' => $value, 'nextValue' => $nextValue];
    }

    private static function genFieldData($i, $v): string
    {
        if (empty($i) || $v === null || $v === '') {
            return '';
        }
        return $i . str_pad(strlen($v), 2, '0', STR_PAD_LEFT) . $v;
    }
}
