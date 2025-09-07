# Authentication

## POST /api/v1/auth/token
Issue an access token for authenticated API requests.

### Request
```json
{
  "email": "user@example.com",
  "password": "secret"
}
```

### Response
```json
{
  "data": {
    "token": "string"
  }
}
```

Returns `401` with an error object when credentials are invalid.
