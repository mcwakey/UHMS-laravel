# Nalo SMS POST JSON Fix Report

## Context

UHMS SMS messages were being queued correctly, but delivery through Nalo was failing. The selected target integration is Nalo's **POST - Send sms api with auth_key -Json** method from the Postman documentation:

`https://documenter.getpostman.com/view/7705958/Uyr7Hydn`

## What Was Implemented Before

The existing `NaloSmsProvider` adapter was implemented as a cautious Phase 1 adapter with an uncertain Nalo contract.

It used:

- Provider: `App\Services\Integrations\Providers\Sms\NaloSmsProvider`
- Config entry: `config/integrations.php`
- Default URL:
  - `https://sms.nalosolutions.com/smsbackend/clientapi/Resl_Nalo/send-message/`
- HTTP method:
  - `POST`
- Payload shape:
  - `key`
  - `username`
  - `password`
  - `msisdn`
  - `message`
  - `sender_id`
- Success detection:
  - Treated JSON statuses such as `success`, `accepted`, `ok`, `sent`, or `message_id` as accepted.

The old adapter mixed the older `clientapi` style with a JSON POST call. That mismatch can cause Nalo to reject the message even though UHMS successfully queues the SMS job.

## What The Nalo Documentation Requires

The Postman collection documents **Send sms api with auth_key -Json** as:

- Method: `POST`
- URL:
  - `https://sms.nalosolutions.com/smsbackend/Resl_Nalo/send-message/`
- Body type:
  - JSON
- Required JSON body:

```json
{
  "key": "AUTH_KEY_FROM_NALO_PORTAL",
  "msisdn": "233244071872",
  "message": "Here are two, of many",
  "sender_id": "NALO"
}
```

Important naming detail:

- In UHMS, the credential is now exposed as `auth_key`.
- In Nalo's JSON request body, the field must still be sent as `key`.

Successful JSON response example:

```json
{
  "status": "1701",
  "job_id": "api.0000011.20221222.0000003",
  "msisdn": "233244071872"
}
```

## What I Am Doing Now To Solve It

I am updating UHMS to use the documented Nalo POST JSON auth-key method.

Changes being made:

- Update `NaloSmsProvider` to prefer `auth_key`.
- Keep `api_key` as a backwards-compatible alias so existing saved credentials can still work.
- Send JSON body fields exactly as Nalo documents:
  - `key`
  - `msisdn`
  - `message`
  - `sender_id`
- Use the documented JSON endpoint:
  - `https://sms.nalosolutions.com/smsbackend/Resl_Nalo/send-message/`
- Normalize old configured URLs that still contain:
  - `/smsbackend/clientapi/Resl_Nalo/`
  into:
  - `/smsbackend/Resl_Nalo/`
- Treat Nalo status `1701` as success.
- Store `job_id` as the provider message ID.
- Add readable Nalo error messages for common error codes such as:
  - `1706` invalid destination
  - `1707` invalid sender
  - `1709` user validation failed
  - `1025` insufficient user credit
- Update the provider credential form to include `auth_key`.
- Add a feature test proving UHMS sends the documented POST JSON payload.

## Operational Note

If Laravel is using a real queue driver, queued SMS messages still require a queue worker:

```powershell
php artisan queue:work --stop-when-empty
```

After this fix, a failed SMS should represent an actual provider rejection, invalid sender/number/credit issue, or credential issue, not a UHMS payload mismatch.
