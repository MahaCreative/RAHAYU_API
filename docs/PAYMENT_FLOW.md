# Payment Flow Documentation

## Overview

Midtrans payment integration dengan support untuk Bank Transfer (VA) dan E-Wallet (GoPay, ShopeePay, Dana).

## Payment Process Flow

```
1. Customer selects payment method (Bank Transfer atau E-Wallet)
   ↓
2. Frontend calls /payment/create-va atau /payment/create-ewallet
   ↓
3. Backend creates Pembayaran record & calls Midtrans API
   ↓
4. Midtrans returns QR code/deeplink for e-wallet, VA for bank transfer
   ↓
5. Frontend displays payment details & QR code
   ↓
6. Customer completes payment via e-wallet app or bank
   ↓
7. Midtrans webhook sends callback to /midtrans/callback
   ↓
8. Backend updates payment status
```

## API Endpoints

### 1. Create Virtual Account (Bank Transfer)

```
POST /payment/create-va
Body: {
  "pemesanan_id": 1,
  "bank": "bri"  // or bca, mandiri, bni
}

Response:
{
  "success": true,
  "data": {
    "pembayaran": {
      "id": 1,
      "pemesanan_id": 1,
      "va_number": "123456789",
      "bank": "bri",
      "total": 100000,
      "status": "pending",
      "order_id": "ORDER-1-1234567890"
    },
    "midtrans": { ... full Midtrans response ... }
  }
}
```

### 2. Create E-Wallet Payment

```
POST /payment/create-ewallet
Body: {
  "pemesanan_id": 1,
  "payment_type": "gopay"  // or dana, shopeepay
}

Response:
{
  "success": true,
  "data": {
    "pembayaran": {
      "id": 2,
      "pemesanan_id": 1,
      "va_number": "{ json payment_info }",  // Contains QR code URL
      "bank": "gopay",
      "total": 100000,
      "status": "pending",
      "order_id": "ORDER-1-1234567890"
    },
    "payment_info": {
      "qr_code": "https://api.midtrans.com/qr/...",  // GoPay QR URL
      "redirect_url": "gojek://...",  // Deeplink to GoPay app
      "expiry_time": "2025-11-27T08:00:00"
    },
    "midtrans": { ... full Midtrans response ... }
  }
}
```

### 3. Get Payment Detail

```
GET /payment/{pemesanan_id}

Response:
{
  "success": true,
  "data": {
    "pemesanan": { ... order details ... },
    "pembayaran": {
      "id": 1,
      "va_number": "{ json payment_info }",  // Stored JSON
      "bank": "gopay",
      "status": "pending"
    },
    "payment_info": {
      "qr_code": "https://api.midtrans.com/qr/...",
      "redirect_url": "gojek://...",
      "expiry_time": "2025-11-27T08:00:00"
    }
  }
}
```

### 4. Check Payment Status

```
GET /payment/status/{pemesanan_id}

Response returns transaction status from Midtrans
```

### 5. Cancel Payment

```
POST /payment/cancel/{orderId}

Updates payment status to "cancelled"
Calls Midtrans cancel API
```

### 6. Midtrans Callback

```
POST /midtrans/callback

Midtrans sends notification about payment status change.
Backend verifies signature and updates payment status.
```

## Data Structure

### Pembayaran Table

```php
// Columns
- id
- pemesanan_id
- va_number  // For bank transfer: VA number, For e-wallet: JSON payment_info
- bank       // bri, bca, mandiri, bni (bank_transfer) or gopay, dana, shopeepay (e-wallet)
- total
- status     // pending, settlement, expire, cancel, deny
- order_id   // Midtrans order ID
- expiry     // Expiry timestamp
```

### Payment Info Structure

```json
// Bank Transfer
{
  "bank": {
    "bank": "bri",
    "va_number": "123456789"
  },
  "expiry_time": "2025-11-27T08:00:00"
}

// E-Wallet (GoPay/Dana)
{
  "qr_code": "https://api.midtrans.com/qr/...",
  "redirect_url": "gojek://...",
  "expiry_time": "2025-11-27T08:00:00"
}

// E-Wallet (ShopeePay)
{
  "redirect_url": "shopee://...",
  "expiry_time": "2025-11-27T08:00:00"
}
```

## Frontend Integration

### PaymentMethodScreen

1. Fetch available payment methods
2. User selects bank_transfer or e-wallet
3. Calls `/payment/create-va` or `/payment/create-ewallet`
4. Navigate to PaymentDetailScreen with pemesanan_id

### PaymentDetailScreen

1. Fetch payment details via `GET /payment/{pemesanan_id}`
2. Extract `payment_info` from response
3. For e-wallet: Display QR code (if qr_code field exists)
4. For e-wallet: Display "Buka Aplikasi" button with redirect_url
5. For bank transfer: Display VA number
6. Handle auto-cancel when timer expires
7. Allow user to check payment status

## Testing with Midtrans Sandbox

### Test Credentials

-   Merchant ID: from env (MIDTRANS_MERCHANT_ID)
-   Server Key: from env (MIDTRANS_SERVER_KEY)
-   Client Key: from env (MIDTRANS_CLIENT_KEY)
-   Sandbox URL: https://api.sandbox.midtrans.com

### GoPay Test

Amount: 20000
QR will be displayed in response

### Bank Transfer Test

Amount: Any
VA will be generated

### Webhook Testing

```bash
# Test callback locally (replace with actual values)
curl -X POST http://localhost/api/midtrans/callback \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "ORDER-1-1234567890",
    "status_code": "200",
    "gross_amount": "100000.00",
    "signature_key": "...",
    "transaction_status": "settlement",
    "settlement_time": "2025-11-27T08:30:00"
  }'
```

## Status Mappings

| Midtrans Status | Meaning             | Next Action               |
| --------------- | ------------------- | ------------------------- |
| settlement      | Payment successful  | Order complete            |
| pending         | Waiting for payment | Show payment instructions |
| expire          | Payment expired     | Allow retry               |
| cancel          | Payment cancelled   | Allow retry               |
| deny            | Payment denied      | Show error                |

## Notes

-   Dana is mapped to GoPay endpoint (sama akses via GoPay infrastructure)
-   QR codes are valid for 15 minutes by default
-   Deeplinks may not work if e-wallet app not installed
-   Signature verification is critical for webhook security
-   Always verify payment via `/payment/status` endpoint before fulfilling order
