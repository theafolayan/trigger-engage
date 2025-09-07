# Contacts

All endpoints require an `Authorization: Bearer {token}` header and an
`X-Workspace` header identifying the current workspace.

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

## GET /api/v1/contacts
List contacts in the current workspace.

### Response
```json
{
  "data": [
    {
      "id": 1,
      "email": "user@example.com",
      "first_name": "Jane",
      "last_name": "Doe",
      "attributes": {
        "plan": "pro"
      }
    }
  ]
}
```

## GET /api/v1/contacts/{id}
Retrieve a contact by ID.

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
Returns `404` if the contact does not exist.

## PUT /api/v1/contacts/{id}
Update a contact.

### Request
```json
{
  "first_name": "Janet",
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
    "first_name": "Janet",
    "last_name": "Doe",
    "attributes": {
      "plan": "pro"
    }
  }
}
```
Returns `404` if the contact does not exist.

## DELETE /api/v1/contacts/{id}
Delete a contact.

### Response
Responds with `204 No Content` on success.
Returns `404` if the contact does not exist.

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
To remove a device token, see [DELETE /api/v1/contacts/{id}/device-tokens/{token}](#delete-apiv1contactsiddevice-tokenstoken).

## DELETE /api/v1/contacts/{id}/device-tokens/{token}
Remove a device token from a contact.

### Request
```bash
curl -X DELETE http://localhost/api/v1/contacts/1/device-tokens/ExponentPushToken[xxxxxxxx] \
  -H "Authorization: Bearer <token>" \
  -H "X-Workspace: demo"
```

### Response
Responds with `204 No Content` on success.
Returns `401` if the bearer token is invalid.
Returns `404` if the token is not found.
