# BACKEND CONTRACT — Volpa Mail API v1

Source backend: `/home/samuel/projects/mail.volpa.com.br`. Generated from source. The client SDK in `/home/samuel/projects/volpa-mail-laravel` must match this byte-for-byte.

## Auth
Header `X-API-Key: <key>`. Key format `mf_live_*` / `mf_test_*`.

## Error envelopes (IMPORTANT — two shapes)
- **Business errors**: `{"error": {"code": "<string>", "message": "<optional>"}}`
- **Laravel validation (422)**: `{"message": "...", "errors": {"field": ["msg"]}}`
- **Throttle (429)**: `{"message": "Too Many Requests."}` + headers `Retry-After`, `X-RateLimit-Limit`, `X-RateLimit-Remaining`.

Error codes: missing_api_key(401), invalid_api_key(401), ip_not_allowed(403), not_found(404), idempotency_conflict(409), sender_not_found(422), invalid_sender(422), broadcast_finalized(422), cannot_cancel(422).

---

## 1. Webhook signature (custom scheme — NOT Svix)
Delivery headers: `X-VolpaMail-Signature`, `X-VolpaMail-Event` (e.g. `email.delivered`), `Content-Type: application/json`, `User-Agent: VolpaMail-Webhooks/1.0`.

Signature header format: `t=<unix_ts>,v1=<hex_hmac>`.
Signed string: `<unix_ts> . "." . <raw JSON body>` (single period separator).
Algorithm: HMAC-SHA256, **hex** (lowercase, 64 chars), compared with `hash_equals`.
Tolerance: **300 seconds**. Secret: plain string, no prefix.

Backend verify (mirror exactly):
```php
public function verify(string $payload, string $secret, string $signatureHeader, int $tolerance = 300): bool
{
    $parts = [];
    foreach (explode(',', $signatureHeader) as $segment) {
        $segment = trim($segment);
        if ($segment === '' || !str_contains($segment, '=')) { continue; }
        [$key, $value] = explode('=', $segment, 2);
        $parts[$key] = $value;
    }
    if (!isset($parts['t'], $parts['v1']) || !ctype_digit($parts['t'])) { return false; }
    $ts = (int) $parts['t'];
    if (abs(time() - $ts) > $tolerance) { return false; }
    $expected = hash_hmac('sha256', $ts . '.' . $payload, $secret);
    return hash_equals($expected, $parts['v1']);
}
```

Webhook body: `{"type": "<event>", "created": "<ISO8601>", "data": { ... }}`.

Webhook MANAGEMENT endpoints (client can register/list endpoints):
- `GET /webhooks` → `{data:[{id,url,description,events,is_active,consecutive_failures,last_success_at}]}`
- `POST /webhooks` body `{url(req,max500), description?, events(req array min1)}` → 201 `{id,url,events,secret,created_at}`. Allowed events: email.sent, email.delivered, email.opened, email.clicked, email.bounced, email.complained, email.failed, email.unsubscribed, email.inbound, domain.verified, domain.failed, broadcast.started, broadcast.completed, `*`.
- `GET /webhooks/{id}` → details incl. total_deliveries,last_failure_at.
- `DELETE /webhooks/{id}` → 204.
- `POST /webhooks/{id}/test` → 202 `{queued:true}`.

---

## 2. Suppressions
SuppressionReason enum: `hard_bounce, soft_bounce_repeated, complaint, unsubscribe, manual, invalid_address`.

- `GET /suppressions` query `reason?, source?, limit?(1-100,def50)` → `{data:[{id,email,reason,source,created_at}]}`
- `POST /suppressions` body `{email(req,rfc), reason(req,enum)}` → 201 `{id,email,reason,created_at}` (no source)
- `GET /suppressions/{email}` (route email regex `.+@.+`) → 200 `{id,email,reason,source,created_at}` | 404 `{"error":{"code":"not_found"}}`
- `DELETE /suppressions/{email}` → 204 (body `[]`) | 404
- `POST /suppressions/import` body `{emails(req array 1-1000 rfc), reason?(enum minus soft_bounce_repeated; def manual)}` → 202 `{imported:int, reason:string}`

---

## 3. Contacts
ContactStatus enum: `active, unsubscribed, bounced, complained`.

- `GET /contacts` query `status?, limit?(1-100,def50)` → `{data:[{id,email,first_name,last_name,status,subscribed_at}]}`
- `POST /contacts` body `{email(req,rfc), first_name?(max255), last_name?(max255), tags?(array), attributes?(array kv), list_ids?(array uuid)}` → 201 `{id,email,first_name,last_name,status,tags,attributes,subscribed_at,created_at}`
- `GET /contacts/{id}` → 200 (full shape above) | 404

## 4. Contact lists
- `GET /contact-lists` → `{data:[{id,name,slug,total_contacts,total_subscribed}]}`
- `POST /contact-lists` body `{name(req,max255), slug?(max100,auto), description?}` → 201 `{id,name,slug,total_contacts,created_at}`
- `GET /contact-lists/{id}` → 200 `{id,name,slug,description,total_contacts,total_subscribed,created_at}` | 404
- `POST /contact-lists/{id}/import` body (JSON) `{contacts(req array, *.email rfc)}` OR multipart `csv` file → 202 `{list_id, imported:int}` | 404

---

## 5. Broadcasts
BroadcastStatus enum: `draft, scheduled, sending, sent, canceled, failed`. Final = sent|canceled|failed.

Full serialize shape: `{id,name,subject,status,scheduled_at,started_at,completed_at,total_recipients,total_sent,total_delivered,total_failed}`.

- `GET /broadcasts` → `{data:[{id,name,subject,status,total_recipients,total_sent,started_at,completed_at}]}` (partial)
- `POST /broadcasts` body `{name(req,max255), sender_id(req,uuid), subject(req,max998), template_id?, message_stream_id?, html_body?, text_body?, reply_to?(rfc), contact_list_ids?(array), excluded_list_ids?(array), segment_filters?(array), scheduled_at?(date after now → status scheduled)}` → 201 full shape | 422 `{"error":{"code":"invalid_sender"}}`
- `GET /broadcasts/{id}` → 200 full | 404
- `POST /broadcasts/{id}/send` (no body) → 202 `{id,status,total_queued:int}` | 404 | 422 `{"error":{"code":"broadcast_finalized"}}`
- `POST /broadcasts/{id}/cancel` (no body) → 200 full | 404 | 422 `{"error":{"code":"cannot_cancel"}}`

---

## 6. Domains
**No `/domains` endpoints exist.** Skip DomainResource entirely.

---

## 7. Idempotency
Header `Idempotency-Key` on `POST /emails` ONLY. TTL 24h default. Replay (same body) → cached response. Conflict (different body) → 409 `{"error":{"code":"idempotency_conflict","message":"Idempotency-Key reused with a different request body."}}`.

## 8. Rate limiting
HTTP 429 via Laravel default throttle. Body `{"message":"Too Many Requests."}`. Read `Retry-After` header (seconds). Client should surface status 429 + retryAfter.

---

## 9. POST /emails (single send)
Success: **HTTP 202**. Response:
```json
{"id":"eml_<uuid-no-hyphens>","status":"queued","from":"sender@x.com","to":["r@x.com"],"subject":"Hello","message_stream":"outbound","created_at":"2024-01-01T00:00:00+00:00"}
```
`from` is a STRING (email only). `to` is string[]. `message_stream` string|null. Attachment fields: filename, content_type, content(base64), disposition?(attachment|inline), content_id?. Errors: 422 sender_not_found, 409 idempotency_conflict, 422 Laravel validation.

## 10. POST /emails/batch
Body: `{default_from?{email,name?}, default_template?, default_tags?(array), message_stream?, emails(req array 1-500): [{to(req 1-50 [{email,name?}]), from?{email,name?}, subject?, html?, text?, template_id?, variables?, tags?}]}` → 202 `{batch_id:"bat_<uuid>", total_queued:int, status:"queued", created_at}`.

---

## 11. EmailStatus (backend) — full set
`queued, scheduled, processing, sent, delivered, opened, clicked, bounced, soft_bounced, complained, rejected, failed, canceled`.
Backend never emits `pending` or `deferred`.

---

## ⚠️ SDK DISCREPANCIES TO FIX
1. **Error parser**: `VolpaMailException::fromResponse` reads `body.message`/`body.errors` only — must ALSO read `body.error.code`/`body.error.message`.
2. **EmailStatus enum**: SDK missing scheduled, processing, opened, clicked, soft_bounced, rejected, canceled. Keep `pending` as unknown-fallback; `deferred` harmless to keep.
3. **SentEmail DTO**: only captures id/status/created_at — add from(string), to(string[]), subject, messageStream (all nullable, additive).
4. **`GET /emails/{id}`**: route does NOT exist on backend. Keep method but flag to user.
5. **429**: add retryAfter to exception, surface status 429.
