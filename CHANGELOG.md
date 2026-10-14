# Changelog

## 0.4.0

- Added taking payments: bank account numbers for your customers, card payment links, received payments, withdrawals, refunds, payout account, business profile and the payment event feed. Test keys use pretend money and include helpers to simulate a payment.

## 0.3.0

- Added address verification: start a check, read its result, submit the person's location and upload a utility bill or statement.
- Added the bills catalogue and pricing calls: networks, data plans (filter by daily, weekly, 14-days, monthly, 2-months, yearly), data durations, electricity companies, TV providers and bouquets, quote and running offers.
- Added webhook calls to list every delivery with its contents and to resend one.

## 0.2.0

- Added phone verification (OTP), SMS and bulk SMS, bill payments, webhook management,
  every file and image tool (PDF, OCR, background removal, app icons, store assets),
  verification lookup, document tamper check, account overview and a `call()` escape hatch.
- Removed `topup()` and `payments()`: those endpoints require a dashboard session and always
  returned 401 with an API key.

## 0.1.0

- Initial release: identity verification (BVN/NIN/FRSC/credit-score/CAC),
  prepaid billing, realtime call/chat token issuance, streaming moderation,
  and the gifting economy.
