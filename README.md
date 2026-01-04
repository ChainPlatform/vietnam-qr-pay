# ChainPlatform Pay

Thư viện hỗ trợ encode/decode mã QR của VietQR (QR Ngân hàng, QR Đa năng Momo/ZaloPay) & VNPayQR

## Cài đặt
```bash
composer require chainplatform/pay


```bash
use ChainPlatform\Pay\QRPay;
use ChainPlatform\Pay\Banks;
use ChainPlatform\Pay\Constants\BankKey;

$bank = Banks::getBanksObject()[BankKey::ACB];
$qr = QRPay::initVietQR([
    'bankBin' => $bank['bin'],
    'bankNumber' => '123456789',
    'amount' => '10000',
    'purpose' => 'Gen ây ai thanh toan'
]);

echo $qr->build();