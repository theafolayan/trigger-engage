# Admin Stats

## GET /api/v1/admin/stats
Retrieve aggregated statistics for the workspace. Requires admin privileges.

### Response
```json
{
  "data": {
    "contacts": 10,
    "events": 25,
    "templates": 3
  }
}
```
