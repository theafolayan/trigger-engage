# Contacts

## POST /api/v1/contacts
Create or update a contact in the current workspace.

### Request
```json
{
  "email": "user@example.com",
  "first_name": "Jane",
  "last_name": "Doe",
  "attributes": {
    "plan": "pro"
  }
}
```

### Response
```json
{
  "data": {
    "id": 1,
    "email": "user@example.com",
    "first_name": "Jane",
    "last_name": "Doe",
    "attributes": {
      "plan": "pro"
    }
  }
}
```

## POST /api/v1/contacts/import
Bulk import contacts from JSON or CSV payloads.

### Request
```json
[
  {"email": "a@example.com"},
  {"email": "b@example.com", "first_name": "B"}
]
```

### Response
```json
{
  "data": {
    "created": 2,
    "updated": 0,
    "errors": []
  }
}
```

## POST /api/v1/contacts/{id}/device-tokens
Attach a device token to a contact for push notifications.

### Request
```json
{
  "token": "ExponentPushToken[xxxxxxxx]",
  "platform": "ios",
  "driver": "expo"
}
```

### Response
```json
{
  "data": {
    "token": "ExponentPushToken[xxxxxxxx]"
  }
}
```
