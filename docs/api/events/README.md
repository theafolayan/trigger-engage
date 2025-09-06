# Events

## POST /api/v1/events
Ingest a custom event that can trigger automations.

### Request
```json
{
  "name": "user.signed_up",
  "contact_email": "user@example.com",
  "payload": {
    "plan": "pro"
  },
  "auto_create_contact": true
}
```

### Response
```json
{
  "data": {
    "id": 1
  }
}
```
