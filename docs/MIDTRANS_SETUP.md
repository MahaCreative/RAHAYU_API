# Midtrans Setup (Laravel 12)

This document explains how to configure Midtrans for this Laravel project and how to test Virtual Account payments.

## 1. Get Midtrans credentials

-   Login to Midtrans Dashboard (https://dashboard.midtrans.com)
-   For sandbox, go to Settings -> Access Keys and copy `Server Key` and `Client Key` for sandbox.
-   For production, use the production keys.
-   Also note your **Merchant ID** (format usually starts with 'G' for sandbox/production), you'll need it for some integrations and reporting.

## 2. Set .env

Edit `.env` (or copy `.env.example`) and set:

MIDTRANS_ENV=sandbox
MIDTRANS_SERVER_KEY=SB-Midtrans-Server-Key-Here
MIDTRANS_CLIENT_KEY=SB-Midtrans-Client-Key-Here
MIDTRANS_MERCHANT_ID=G123456789

## 3. Config file

A config file `config/midtrans.php` is included and will choose the correct base API URL depending on `MIDTRANS_ENV`.

## 4. Migrations

Make sure migration for `pembayarans` exists (it was added). Run migrations:

```bash
php artisan migrate
```

## 5. Webhook / Callback

Set the Midtrans Notification URL (for sandbox) to:

`https://<your-server>/api/midtrans/callback`

Midtrans will POST notifications here. The controller verifies `signature_key` using the server key.

## 6. Create VA flow (how it works)

-   Client calls `POST /api/payment/create-va` with `pemesanan_id` and `bank`.
-   Server calls Midtrans `POST /v2/charge` with `payment_type=bank_transfer` and transaction details.
-   Server stores the `va_number`, `order_id`, `expiry` in table `pembayarans` and returns JSON with VA details.

### Keys / IDs: what they are and when to use

-   **Merchant ID**: identifier of your merchant account (starts with `G...`). Not required for server-side charge calls, but useful for reporting, some callbacks, and dashboard mapping. Store it in `.env` as `MIDTRANS_MERCHANT_ID`.
-   **Server Key**: secret key used in server-to-server calls (e.g., charge / create VA). Must stay on the backend only.
-   **Client Key**: public key used for client-side integrations (Snap JS or mobile SDK). It is safe to expose in the mobile app when using Snap or client-side flows (not for server calls).

Use-case summary:

-   Creating VA (bank_transfer): server uses **Server Key** to call Midtrans `/v2/charge`. Merchant ID is optional for the API call but keep it in config for bookkeeping.
-   Snap / Web-based checkout: include **Client Key** on client side.
-   Webhook verification & server-side updates: verify `signature_key` using **Server Key**.

## 7. Testing locally

-   If developing locally, expose your server using `ngrok` and register the ngrok URL in Midtrans dashboard for callbacks.
-   Example ngrok start:

```powershell
ngrok http 8000
```

-   Use the returned URL `https://xxxxxx.ngrok.io` and set `APP_URL` and Midtrans callback accordingly.

## 8. Verify signature manually (example)

Midtrans `signature_key` is `sha512(order_id + status_code + gross_amount + server_key)`.
You can recompute that on your server to verify the notification.

## 9. Notes & Security

-   Keep `MIDTRANS_SERVER_KEY` secret on server-side only.
-   For production, make sure to use HTTPS and validate notifications.
-   For e-wallets (Gopay, Shopeepay), you may need to use Snap or other Midtrans flows.

If you want, I can:

-   Switch the `PaymentController` to use sandbox base URL automatically (already done).
-   Add a test Artisan command to simulate Midtrans notifications.
-   Add unit tests for payment controller flows.

\*\*\* End Patch
