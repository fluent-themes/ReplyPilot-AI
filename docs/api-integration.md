# ReplyPilot AI - API Integration Guide

## Table of Contents

1. [Overview](#overview)
2. [Public API Endpoints](#public-api-endpoints)
3. [Admin API Endpoints](#admin-api-endpoints)
4. [Authentication & Security](#authentication--security)
5. [AI Provider APIs](#ai-provider-apis)
6. [Webhook Integration](#webhook-integration)
7. [Rate Limiting](#rate-limiting)
8. [Error Handling](#error-handling)
9. [Code Examples](#code-examples)
10. [Testing & Debugging](#testing--debugging)

## Overview

ReplyPilot AI provides both public-facing and administrative API endpoints for integration with external systems. All endpoints support JSON responses and follow RESTful conventions where applicable.

For a complete endpoint reference, see [EndpointMap.md](../EndpointMap.md).

## Public API Endpoints

### Form Submission API

**Endpoint**: `/public/ajax-submit.php`  
**Method**: POST  
**Content-Type**: application/x-www-form-urlencoded or multipart/form-data

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| name | string | Yes | Customer name (3-100 characters) |
| email | string | Yes | Valid email address |
| message | string | Yes | Customer message (10-5000 characters) |
| tone | string | No | Response tone preference (friendly/professional/technical) |
| purchase_code | string | No | Envato purchase code for validation |
| product_name | string | No | Associated product name |

#### Example Request

```javascript
const formData = new FormData();
formData.append('name', 'John Doe');
formData.append('email', 'john@example.com');
formData.append('message', 'I need help with installation');
formData.append('tone', 'friendly');

fetch('https://yourdomain.com/public/ajax-submit.php', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Ticket ID:', data.ticket_id);
    } else {
        console.error('Error:', data.message);
    }
});
```

#### Response Format

**Success Response** (200 OK):
```json
{
    "success": true,
    "message": "Thank you for your submission!",
    "ticket_id": "TKT-20250826-ABC123",
    "redirect_url": "/?page=ticket&ref=abc123def456"
}
```

**Error Response** (400/429):
```json
{
    "success": false,
    "message": "Rate limit exceeded. Please wait 60 seconds.",
    "error_code": "RATE_LIMIT_EXCEEDED",
    "retry_after": 60
}
```

### Ticket Status API

**Endpoint**: `/?page=ticket&ref={ref}`  
**Method**: GET  
**Authentication**: Session-based (ticket owner only)

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| ref | string | Yes | Unique ticket reference (32 characters) |

#### Response

Returns HTML page with ticket details including:
- Submission date and status
- Customer message
- AI-generated response (if available)
- Admin replies history
- Category assignment

## Admin API Endpoints

### Test Provider Connection

**Endpoint**: `/admin/test_provider.php`  
**Method**: GET  
**Authentication**: Admin session required

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | Yes | Provider type (ai/license) |
| provider | string | Yes | Provider name (openai/claude/gemini/envato) |

#### Example Request

```javascript
fetch('/admin/test_provider.php?type=ai&provider=openai', {
    credentials: 'include'
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Provider test successful:', data.details);
    } else {
        console.error('Provider test failed:', data.error);
    }
});
```

#### Response Format

```json
{
    "success": true,
    "provider": "openai",
    "details": {
        "model": "gpt-3.5-turbo",
        "test_response": "Connection successful",
        "response_time": 1.23,
        "tokens_used": 15
    }
}
```

### Export Submissions

**Endpoint**: `/admin/export_csv.php`  
**Method**: GET  
**Authentication**: Admin session + CSRF token

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| csrf_token | string | Yes | Valid CSRF token from session |
| from_date | string | No | Start date (YYYY-MM-DD) |
| to_date | string | No | End date (YYYY-MM-DD) |
| category | string | No | Filter by category |
| status | string | No | Filter by status (pending/replied/closed) |

#### Response

Returns CSV file download with columns:
- ID, Date, Name, Email
- Message, Category, Status
- AI Reply, Admin Reply
- Ticket Reference

## Authentication & Security

### Session-Based Authentication

Admin endpoints require authenticated session:

```php
// Check in PHP
session_start();
if (!isset($_SESSION['rpai_admin_unlocked']) || 
    $_SESSION['rpai_admin_unlocked'] !== true) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}
```

### CSRF Protection

All POST requests require CSRF token:

```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validate token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid CSRF token']));
}
```

### API Key Authentication (Future)

Planned REST API with key authentication:

```
Authorization: Bearer YOUR_API_KEY
```

## AI Provider APIs

### OpenAI Integration

**Configuration**:
```php
$config = [
    'api_key' => getenv('OPENAI_API_KEY'),
    'model' => 'gpt-3.5-turbo',
    'temperature' => 0.7,
    'max_tokens' => 1000
];
```

**Request Example**:
```php
$client = new OpenAIClient($config);
$response = $client->generateReply([
    'system' => 'You are a helpful customer support agent.',
    'user' => $customerMessage,
    'context' => [
        'category' => $category,
        'tone' => $tone
    ]
]);
```

### Claude API Integration

**Configuration**:
```php
$config = [
    'api_key' => getenv('CLAUDE_API_KEY'),
    'model' => 'claude-3-opus-20240229',
    'max_tokens' => 2000
];
```

**Request Example**:
```php
$client = new ClaudeClient($config);
$response = $client->generateReply([
    'messages' => [
        ['role' => 'user', 'content' => $customerMessage]
    ],
    'system' => 'Professional customer support assistant'
]);
```

### Gemini API Integration

**Configuration**:
```php
$config = [
    'api_key' => getenv('GEMINI_API_KEY'),
    'model' => 'gemini-pro',
    'safety_settings' => 'BLOCK_MEDIUM_AND_ABOVE'
];
```

**Request Example**:
```php
$client = new GeminiClient($config);
$response = $client->generateReply([
    'prompt' => $customerMessage,
    'temperature' => 0.7,
    'candidate_count' => 1
]);
```

## Webhook Integration

### Incoming Webhooks (Planned)

Accept submissions from external services:

**Endpoint**: `/api/webhook/submit`  
**Method**: POST  
**Headers**: 
```
X-Webhook-Secret: YOUR_WEBHOOK_SECRET
Content-Type: application/json
```

**Payload**:
```json
{
    "source": "external_form",
    "timestamp": "2025-08-26T10:00:00Z",
    "data": {
        "name": "Customer Name",
        "email": "customer@example.com",
        "message": "Support request",
        "metadata": {
            "source_id": "12345",
            "priority": "high"
        }
    }
}
```

### Outgoing Webhooks

Notify external systems of events:

**Events**:
- `submission.created` - New submission received
- `submission.replied` - Reply sent to customer
- `submission.categorized` - Category assigned
- `submission.closed` - Ticket closed

**Payload Example**:
```json
{
    "event": "submission.replied",
    "timestamp": "2025-08-26T10:30:00Z",
    "data": {
        "ticket_id": "TKT-20250826-ABC123",
        "ref": "abc123def456",
        "reply_type": "ai_generated",
        "reply_sent_at": "2025-08-26T10:29:45Z"
    }
}
```

## Rate Limiting

### Default Limits

| Endpoint | Rate Limit | Window | Per |
|----------|------------|--------|-----|
| /public/ajax-submit.php | 6 requests | 60 seconds | Session/IP |
| /admin/send_email.php | 10 emails | 60 seconds | Session |
| /admin/test_provider.php | 5 tests | 60 seconds | Session |
| AI Provider APIs | Varies | Varies | API Key |

### Rate Limit Headers

Response includes rate limit information:

```
X-RateLimit-Limit: 6
X-RateLimit-Remaining: 4
X-RateLimit-Reset: 1693056000
```

### Handling Rate Limits

```javascript
async function submitWithRetry(data, maxRetries = 3) {
    for (let i = 0; i < maxRetries; i++) {
        const response = await fetch('/public/ajax-submit.php', {
            method: 'POST',
            body: data
        });
        
        if (response.status === 429) {
            const retryAfter = response.headers.get('Retry-After') || 60;
            await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
            continue;
        }
        
        return response.json();
    }
    throw new Error('Max retries exceeded');
}
```

## Error Handling

### Error Response Format

All API errors follow consistent format:

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid email address format",
        "field": "email",
        "details": {
            "provided": "invalid-email",
            "expected": "valid email format"
        }
    }
}
```

### Common Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| VALIDATION_ERROR | 400 | Input validation failed |
| AUTHENTICATION_REQUIRED | 401 | Missing or invalid authentication |
| PERMISSION_DENIED | 403 | Insufficient permissions |
| NOT_FOUND | 404 | Resource not found |
| RATE_LIMIT_EXCEEDED | 429 | Too many requests |
| PROVIDER_ERROR | 502 | AI provider API error |
| SERVER_ERROR | 500 | Internal server error |

### Error Handling Best Practices

```php
try {
    // API operation
    $result = processSubmission($data);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
} catch (ValidationException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'VALIDATION_ERROR',
            'message' => $e->getMessage(),
            'field' => $e->getField()
        ]
    ]);
    
} catch (Exception $e) {
    // Log full error
    error_log($e->getMessage());
    
    // Return sanitized error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'SERVER_ERROR',
            'message' => 'An error occurred processing your request'
        ]
    ]);
}
```

## Code Examples

### PHP Integration

```php
<?php
class ReplyPilotAPI {
    private $baseUrl;
    private $sessionCookie;
    
    public function __construct($baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function submitTicket($data) {
        $ch = curl_init($this->baseUrl . '/public/ajax-submit.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API request failed with status: $httpCode");
        }
        
        return json_decode($response, true);
    }
    
    public function getTicketStatus($ref) {
        $url = $this->baseUrl . '/?page=ticket&ref=' . urlencode($ref);
        $html = file_get_contents($url);
        
        // Parse HTML for ticket details
        // Return structured data
    }
}

// Usage
$api = new ReplyPilotAPI('https://support.example.com');
$result = $api->submitTicket([
    'name' => 'Customer Name',
    'email' => 'customer@example.com',
    'message' => 'I need help with my order'
]);
echo "Ticket created: " . $result['ticket_id'];
```

### JavaScript/Node.js Integration

```javascript
class ReplyPilotClient {
    constructor(baseUrl) {
        this.baseUrl = baseUrl.replace(/\/$/, '');
    }
    
    async submitTicket(data) {
        const formData = new URLSearchParams(data);
        
        const response = await fetch(`${this.baseUrl}/public/ajax-submit.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Request failed');
        }
        
        return response.json();
    }
    
    async testProvider(type, provider, adminSession) {
        const response = await fetch(
            `${this.baseUrl}/admin/test_provider.php?type=${type}&provider=${provider}`,
            {
                credentials: 'include',
                headers: {
                    'Cookie': `PHPSESSID=${adminSession}`
                }
            }
        );
        
        return response.json();
    }
}

// Usage
const client = new ReplyPilotClient('https://support.example.com');

client.submitTicket({
    name: 'John Doe',
    email: 'john@example.com',
    message: 'Technical support needed'
})
.then(result => {
    console.log('Success:', result.ticket_id);
})
.catch(error => {
    console.error('Error:', error.message);
});
```

### Python Integration

```python
import requests
import json

class ReplyPilotAPI:
    def __init__(self, base_url):
        self.base_url = base_url.rstrip('/')
        self.session = requests.Session()
    
    def submit_ticket(self, data):
        """Submit a new support ticket"""
        response = self.session.post(
            f"{self.base_url}/public/ajax-submit.php",
            data=data
        )
        
        if response.status_code == 429:
            retry_after = int(response.headers.get('Retry-After', 60))
            raise Exception(f"Rate limited. Retry after {retry_after} seconds")
        
        response.raise_for_status()
        return response.json()
    
    def get_ticket_status(self, ref):
        """Get ticket status by reference"""
        response = self.session.get(
            f"{self.base_url}/",
            params={'page': 'ticket', 'ref': ref}
        )
        response.raise_for_status()
        # Parse HTML response
        return self._parse_ticket_html(response.text)
    
    def test_ai_provider(self, provider, admin_cookie):
        """Test AI provider connection (admin only)"""
        self.session.cookies.set('PHPSESSID', admin_cookie)
        response = self.session.get(
            f"{self.base_url}/admin/test_provider.php",
            params={'type': 'ai', 'provider': provider}
        )
        return response.json()

# Usage
api = ReplyPilotAPI('https://support.example.com')

# Submit ticket
result = api.submit_ticket({
    'name': 'Customer Name',
    'email': 'customer@example.com',
    'message': 'I need assistance with my account'
})
print(f"Ticket created: {result['ticket_id']}")
```

## Testing & Debugging

### API Testing Tools

**Using cURL**:
```bash
# Test submission
curl -X POST https://yourdomain.com/public/ajax-submit.php \
  -d "name=Test User" \
  -d "email=test@example.com" \
  -d "message=This is a test submission"

# Test with rate limiting
for i in {1..10}; do
  curl -X POST https://yourdomain.com/public/ajax-submit.php \
    -d "name=Test$i" \
    -d "email=test$i@example.com" \
    -d "message=Test message $i" \
    -w "\nStatus: %{http_code}\n"
  sleep 1
done
```

**Using Postman**:

1. Create new collection "ReplyPilot API"
2. Add environment variables:
   - `base_url`: Your domain
   - `csrf_token`: From session
   - `session_id`: PHPSESSID cookie

3. Create requests for each endpoint
4. Add tests to validate responses

### Debug Headers

Enable debug mode to get additional headers:

```
X-Debug-Time: 0.123s
X-Debug-Memory: 2048KB
X-Debug-Queries: 5
X-Debug-Cache: HIT
```

### Common Integration Issues

**CORS Errors**:
```javascript
// Add to your server configuration
header('Access-Control-Allow-Origin: https://yourapp.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

**Session Issues**:
```php
// Ensure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // HTTPS only
ini_set('session.cookie_samesite', 'Lax');
```

**JSON Response Issues**:
```php
// Always set content type
header('Content-Type: application/json; charset=utf-8');

// Ensure clean output
ob_clean();
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
```

### Monitoring & Logging

**Request Logging**:
```php
// Log API requests
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'endpoint' => $_SERVER['REQUEST_URI'],
    'method' => $_SERVER['REQUEST_METHOD'],
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'response_code' => http_response_code()
];
error_log(json_encode($logData), 3, 'storage/logs/api.log');
```

**Performance Monitoring**:
```php
$startTime = microtime(true);

// API operation

$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000;

header('X-Response-Time: ' . round($executionTime, 2) . 'ms');
```

## API Roadmap

### Planned Features

1. **RESTful API v2**
   - Full CRUD operations
   - OAuth 2.0 authentication
   - GraphQL endpoint
   - WebSocket support

2. **Enhanced Webhooks**
   - Configurable webhook URLs
   - Retry mechanism
   - Webhook signatures
   - Event filtering

3. **Batch Operations**
   - Bulk ticket creation
   - Batch status updates
   - Mass categorization
   - Bulk exports

4. **Analytics API**
   - Real-time metrics
   - Custom report generation
   - Predictive analytics
   - Trend analysis

---

**Version**: 1.0.0  
**Last Updated**: August 2025  
**Support**: For API support, contact support@fluentthemes.com