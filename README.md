# PHP ChainPlatform/Pay – Vietnam QR Pay (VietQR • MoMo • ZaloPay • VNPay)

Được chuyển PHP từ thư viện js của tác giả https://github.com/xuannghia/vietnam-qr-pay

Thư viện PHP hỗ trợ **encode & decode** mã QR thanh toán theo chuẩn EMVCo dành cho:
- **VietQR (NAPAS 247 – QR Ngân hàng)**
- **QR Đa năng MoMo**
- **QR Đa năng ZaloPay**
- **VNPayQR**
- Tạo payload EMV • Validate CRC16 • Sinh QR Image

Được viết dựa trên chuẩn VietQR và ví dụ từ các ví điện tử Việt Nam.

<p align="center">
  <a href="https://github.com/ChainPlatform/ChainSDK/blob/HEAD/LICENSE">
    <img src="https://img.shields.io/badge/license-MIT-blue.svg" />
  </a>
  <a href="https://www.npmjs.com/package/@chainplatform/sdk">
    <img src="https://img.shields.io/npm/v/@chainplatform/sdk?color=brightgreen&label=npm%20package" alt="Current npm package version." />
  </a>
  <a href="https://www.npmjs.com/package/@chainplatform/sdk">
    <img src="https://img.shields.io/npm/dt/@chainplatform/sdk.svg"></img>
  </a>
  <a href="https://www.npmjs.com/package/@chainplatform/sdk">
    <img src="https://img.shields.io/badge/platform-android%20%7C%20ios%20%7C%20web-blue"></img>
  </a>
  <a href="https://github.com/ChainPlatform/ChainSDK/pulls">
    <img src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg" alt="PRs welcome!" />
  </a>
  <a href="https://twitter.com/intent/follow?screen_name=doansan">
    <img src="https://img.shields.io/twitter/follow/doansan.svg?label=Follow%20@doansan" alt="Follow @doansan" />
  </a>
</p>

---

## ⚙️ Cài đặt

```bash
composer require chainplatform/pay
```

---

## 🧩 Encode – Tạo mã QR

## 1) VietQR TĨNH (không có số tiền)

```php
use ChainPlatform\Pay\QRPay;
use ChainPlatform\Pay\BanksObject;

$qrPay = QRPay::initVietQR([
    'bankBin' => BanksObject::acb()->bin,
    'bankNumber' => '257678859',
]);

$content = $qrPay->build();

echo $content;
// 00020101021138530010A0000007270123000697041601092576788590208QRIBFTTA53037045802VN6304AE9F
```

---

## 2) VietQR ĐỘNG (có số tiền + nội dung)

```php
$qrPay = QRPay::initVietQR([
    'bankBin' => BanksObject::mbbank()->bin,
    'bankNumber' => '88787627133',
    'amount' => '10000',
    'purpose' => 'Chuyen tien',
]);

$content = $qrPay->build();

echo $content;
// 00020101021238530010A0000007270123000697041601092576788590208QRIBFTTA53037045405100005802VN62150811Chuyen tien630453E6
```

---

## 3) QR Đa năng MoMo

MoMo sử dụng số tài khoản nội bộ tại BVBank để nhận tiền từ VietQR.

```php
use ChainPlatform\Pay\QRPay;
use ChainPlatform\Pay\BanksObject;

$accountNumber = '99MM24011M34875080';

$momoQR = QRPay::initVietQR([
    'bankBin' => BanksObject::banviet()->bin,
    'bankNumber' => $accountNumber,
]);

// Mã tham chiếu riêng của MoMo
$momoQR->additionalData->reference = 'MOMOW2W' . substr($accountNumber, 10);

// Trường ID 80 = 3 số cuối số điện thoại
$momoQR->setUnreservedField('80', '046');

echo $momoQR->build();
```

Ví dụ output:

```
00020101021138620010A00000072701320006970454011899MM24011M348750800208QRIBFTTA53037045802VN62190515MOMOW2W3487508080030466304EBC8
```

---

## 4) QR Đa năng ZaloPay

ZaloPay cũng dùng tài khoản BVBank để định tuyến.

```php
$accountNumber = '99ZP24009M07248267';

$zaloQR = QRPay::initVietQR([
    'bankBin' => BanksObject::banviet()->bin,
    'bankNumber' => $accountNumber,
]);

echo $zaloQR->build();
```

Ví dụ output:

```
00020101021138620010A00000072701320006970454011899ZP24009M072482670208QRIBFTTA53037045802VN6304073C
```

---

## 5) Tạo QR VNPay

```php
$qrPay = QRPay::initVNPayQR([
    'merchantId' => '0102154778',
    'merchantName' => 'TUGIACOMPANY',
    'store' => 'TU GIA COMPUTER',
    'terminal' => 'TUGIACO1',
]);

echo $qrPay->build();
```

Output mẫu:

```
00020101021126280010A0000007750110010531314453037045408210900005802VN5910CELLPHONES62600312CPSHN ONLINE0517021908061613127850705ONLHN0810CellphoneS63047685
```

---

# 🧭 Decode – Phân tích nội dung QR

## Decode VietQR

```php
$qrContent = '00020101021238530010A0000007270123000697041601092576788590208QRIBFTTA5303704540410005802VN62150811Chuyen tien6304BBB8';

$qrPay = new QRPay($qrContent);

$qrPay->isValid;                // true
$qrPay->provider->name;         // VIETQR
$qrPay->consumer->bankBin;      // 970416
$qrPay->consumer->bankNumber;   // 257678859
$qrPay->amount;                 // 1000
$qrPay->additionalData->purpose // Chuyen tien
```

---

## Decode VNPAY

```php
$qrContent = '00020101021126280010A0000007750110010531314453037045408210900005802VN5910CELLPHONES62600312CPSHN ONLINE0517021908061613127850705ONLHN0810CellphoneS63047685';

$qrPay = new QRPay($qrContent);

$qrPay->isValid;                   // true
$qrPay->provider->name;            // VNPAY
$qrPay->merchant->merchantId;      // 0105313144
$qrPay->amount;                    // 21090000
$qrPay->additionalData->store;     // CPSHN ONLINE
$qrPay->additionalData->terminal;  // ONLHN
$qrPay->additionalData->purpose;   // CellphoneS
$qrPay->additionalData->reference; // 02190806161312785
```

---

# 🧱 QRPay Class

```php
use ChainPlatform\Pay\QRPay;
```

| Thuộc tính | Ý nghĩa |
|-----------|---------|
| `isValid` | Kiểm tra CRC & chuẩn EMV |
| `initMethod` | 11 = tĩnh, 12 = động |
| `provider` | VietQR / VNPAY |
| `merchant` | Thông tin merchant |
| `consumer` | Thông tin người trả |
| `amount` | Số tiền |
| `currency` | 704 = VND |
| `nation` | VN |
| `additionalData` | Thông tin bổ sung |
| `crc` | Mã checksum |
| `build()` | Tạo lại QR |

---

### Provider

| Field | Mô tả |
|-------|-------|
| `guid` | GUID EMV |
| `name` | VietQR / VNPay |

### Merchant

| Field | Mô tả |
|-------|-------|
| `id` | Merchant ID |
| `name` | Merchant Name |

### Consumer

| Field | Mô tả |
|-------|-------|
| `bankBin` | Mã BIN |
| `bankNumber` | STK |

### Additional Data

| Field | Mô tả |
|-------|-------|
| `billNumber` | Số hóa đơn |
| `mobileNumber` | SĐT |
| `store` | Tên cửa hàng |
| `loyaltyNumber` | Mã khách hàng thân thiết |
| `reference` | Mã tham chiếu |
| `customerLabel` | Label |
| `terminal` | POS |
| `purpose` | Nội dung |

---

# 🔧 Build QR mới từ QR cũ

```php
$qrPay = new QRPay($originalContent);

// Sửa thông tin
$qrPay->amount = '10000';
$qrPay->additionalData->purpose = 'Cam on nhe - thu vien tao ma thanh toan QRPay tren PHP';

// Build lại
$newQR = $qrPay->build();

// Output
/*
00020101021238530010A0000007270123000697041601092576788590208QRIBFTTA530370454069999995802VN62140810Cam on nhe6304E786
*/
```
![alt text](qr.png)

---

## 🪪 License

MIT © 2026 [Chain Platform](https://chainplatform.net)

---

## 💖 Support & Donate

If you find this package helpful, consider supporting the development:

| Cryptocurrency | Address |
|----------------|----------|
| **Bitcoin (BTC)** | `17grbSNSEcEybS1nHh4TGYVodBwT16cWtc` |
![alt text](image-1.png)
| **Ethereum (ETH)** | `0xa2fd119a619908d53928e5848b49bf1cc15689d4` |
![alt text](image-2.png)
| **Tron (TRX)** | `TYL8p2PLCLDfq3CgGBp58WdUvvg9zsJ8pd` |
![alt text](image.png)
| **DOGE (DOGE)** | `DDfKN2ys4frNaUkvPKcAdfL6SiVss5Bm19` |
| **USDT (SOLANA)** | `cPUZsb7T9tMfiZFqXbWbRvrUktxgZQXQ2Ni1HiVXgFm` |

Your contribution helps maintain open-source development under the Chain Platform ecosystem 🚀
