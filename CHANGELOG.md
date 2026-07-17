# Changelog

## 0.1.1

- Added `verifyDocument`/`verifyLiveness`/`verifyFaceMatch` (multipart
  file uploads via `CURLFile`) — previously missing entirely.
- Added `$dateOfBirth`/`$phoneNumber` to `verifyBvn` — `$phoneNumber` is
  required on live keys, the upstream provider rejects BVN match
  requests without it.

## 0.1.0

- Initial release: identity verification (BVN/NIN/FRSC/credit-score/CAC),
  prepaid billing, realtime call/chat token issuance, streaming moderation,
  and the gifting economy.
