<?php

namespace ChainPlatform\Pay\Constants;

class AdditionalDataID
{
    public const BILL_NUMBER = '01'; // Số hóa đơn
    public const MOBILE_NUMBER = '02'; // Số ĐT
    public const STORE_LABEL = '03'; // Mã cửa hàng
    public const LOYALTY_NUMBER = '04'; // Mã khách hàng thân thiết
    public const REFERENCE_LABEL = '05'; // Mã tham chiếu
    public const CUSTOMER_LABEL = '06'; // Mã khách hàng
    public const TERMINAL_LABEL = '07'; // Mã số điểm bán
    public const PURPOSE_OF_TRANSACTION = '08'; // Mục đích giao dịch
    public const ADDITIONAL_CONSUMER_DATA_REQUEST = '09'; // Yêu cầu dữ liệu KH bổ sung
}
