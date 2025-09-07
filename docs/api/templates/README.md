# Templates

## GET /api/v1/templates
List all templates in the workspace.

### Response
```json
{
  "data": [
    {
      "id": 1,
      "name": "Welcome",
      "subject": "Welcome!",
      "html": "<p>Hello {{contact.first_name}}</p>",
      "text": "Hello",
      "editor_meta": {}
    }
  ]
}
```

## POST /api/v1/templates
Create a new template.

### Request
```json
{
  "name": "Welcome",
  "subject": "Welcome!",
  "html": "<p>Hello {{contact.first_name}}</p>",
  "text": "Hello",
  "editor_meta": {}
}
```

### Response
```json
{
  "data": {
    "id": 1,
    "name": "Welcome",
    "subject": "Welcome!",
    "html": "<p>Hello {{contact.first_name}}</p>",
    "text": "Hello",
    "editor_meta": {}
  }
}
```

## GET /api/v1/templates/{id}
Retrieve a single template.

## PUT /api/v1/templates/{id}
Update template attributes (same fields as create).

## DELETE /api/v1/templates/{id}
Remove a template. Responds with `204 No Content` on success.

## POST /api/v1/templates/{id}/preview
Render a template with optional contact or event context.

### Request
```json
{
  "contact_id": 1,
  "event_id": 2
}
```

### Response
```json
{
  "data": {
    "subject": "Welcome!",
    "html": "<p>Hello Jane</p>",
    "text": "Hello Jane"
  }
}
```

## POST /api/v1/templates/{id}/test
Queue a test email using the template.

### Request
```json
{
  "to": "user@example.com",
  "contact_id": 1,
  "event_id": 2
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
