# SMTP Settings

## GET /api/v1/smtp-settings
Retrieve the current SMTP configuration for the workspace.

### Response
```json
{
  "data": {
    "id": 1,
    "host": "smtp.example.com",
    "port": 587,
    "username": "user",
    "password": "********",
    "encryption": "tls",
    "from_name": "Example",
    "from_email": "no-reply@example.com",
    "reply_to": null,
    "is_active": true
  }
}
```

## POST /api/v1/smtp-settings
Create or update SMTP credentials.

### Request
```json
{
  "host": "smtp.example.com",
  "port": 587,
  "username": "user",
  "password": "secret",
  "encryption": "tls",
  "from_name": "Example",
  "from_email": "no-reply@example.com",
  "reply_to": "support@example.com"
}
```

### Response
```json
{
  "data": {
    "id": 1,
    "host": "smtp.example.com",
    "port": 587,
    "username": "user",
    "password": "********",
    "encryption": "tls",
    "from_name": "Example",
    "from_email": "no-reply@example.com",
    "reply_to": "support@example.com",
    "is_active": true
  }
}
```

## POST /api/v1/smtp-settings/test
Send a test email using the stored SMTP settings.

### Request
```json
{
  "to": "user@example.com"
}
```

### Response
```json
{
  "data": {
    "sent": true
  }
}
```
