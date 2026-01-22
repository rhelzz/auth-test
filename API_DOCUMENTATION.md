# Laravel Fortify + Sanctum API Documentation

API authentication menggunakan Laravel Fortify (API-first) dan Laravel Sanctum untuk token-based authentication.

## 📋 Base URL

```
http://127.0.0.1:8000
```

## 🔐 Authentication Endpoints

### 1. Register User

**Endpoint:** `POST /api/register`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "SecureP@ss123!",
    "password_confirmation": "SecureP@ss123!"
}
```

**Password Requirements:**
- Minimum 8 characters
- At least 1 uppercase letter (A-Z)
- At least 1 lowercase letter (a-z)
- At least 1 number (0-9)
- At least 1 special character (!@#$%^&* etc)
- Not in breached password database

**Name Requirements:**
- Only letters, spaces, hyphens, apostrophes, and dots
- No consecutive spaces
- 2-100 characters

**Success Response (201 Created):**
```json
{
    "message": "Registration successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2026-01-22T15:00:00.000000Z",
        "updated_at": "2026-01-22T15:00:00.000000Z"
    },
    "access_token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
    "message": "The password field must contain at least one symbol.",
    "errors": {
        "password": [
            "The password field must contain at least one uppercase and one lowercase letter.",
            "The password field must contain at least one symbol.",
            "The password field must contain at least one number."
        ]
    }
}
```

---

### 2. Login User

**Endpoint:** `POST /api/login`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "SecureP@ss123!"
}
```

⚠️ **Note:** Login ONLY accepts `email` and `password` fields. Any extra fields will be ignored.

**Success Response (200 OK):**
```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2026-01-22T15:00:00.000000Z",
        "updated_at": "2026-01-22T15:00:00.000000Z"
    },
    "access_token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
}
```

**Error Response (401 Unauthorized):**
```json
{
    "message": "These credentials do not match our records."
}
```

---

### 3. Logout User

**Endpoint:** `POST /api/logout`

**Headers:**
```
Accept: application/json
Authorization: Bearer {your_token_here}
```

**Success Response (200 OK):**
```json
{
    "message": "Logout successful"
}
```

**Error Response (401 Unauthorized):**
```json
{
    "message": "Unauthenticated."
}
```

---

### 4. Get Authenticated User

**Endpoint:** `GET /api/user`

**Headers:**
```
Accept: application/json
Authorization: Bearer {your_token_here}
```

**Success Response (200 OK):**
```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2026-01-22T15:00:00.000000Z"
    }
}
```

**Error Response (401 Unauthorized):**
```json
{
    "message": "Unauthenticated."
}
```

---

## 🧪 Testing dengan HTTPie

### Install HTTPie

**Windows (Chocolatey):**
```bash
choco install httpie
```

**Windows (pip):**
```bash
pip install httpie
```

**macOS:**
```bash
brew install httpie
```

**Linux:**
```bash
sudo apt install httpie
```

### 1. Register New User

```bash
http POST http://127.0.0.1:8000/api/register \
    name="John Doe" \
    email="john@example.com" \
    password="SecureP@ss123!" \
    password_confirmation="SecureP@ss123!"
```

### 2. Login User

```bash
http POST http://127.0.0.1:8000/api/login \
    email="john@example.com" \
    password="SecureP@ss123!"
```

### 3. Get User Info (with Token)

```bash
http GET http://127.0.0.1:8000/api/user \
    "Authorization:Bearer YOUR_TOKEN_HERE"
```

### 4. Logout

```bash
http POST http://127.0.0.1:8000/api/logout \
    "Authorization:Bearer YOUR_TOKEN_HERE"
```

---

## 🧪 Testing dengan cURL

### 1. Register
```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "SecureP@ss123!",
    "password_confirmation": "SecureP@ss123!"
  }'
```

### 2. Login
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "SecureP@ss123!"
  }'
```

### 3. Get User
```bash
curl -X GET http://127.0.0.1:8000/api/user \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 4. Logout
```bash
curl -X POST http://127.0.0.1:8000/api/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## ⚙️ Configuration

### Fortify Settings
- **Guard:** `web` (for Fortify authentication, returns Sanctum tokens)
- **Views:** `false` (API-first mode)
- **Prefix:** `api` (all routes at /api/*)
- **Features:** Registration only

### Sanctum Settings
- **Guard:** `sanctum` (used for protected routes)
- **Token expiration:** No expiration by default

---

## 🔒 Security Features

| Feature | Status | Description |
|---------|--------|-------------|
| Password Hashing | ✅ | Bcrypt via `Hash::make()` |
| Strong Password | ✅ | Uppercase, lowercase, number, symbol required |
| Breached Password Check | ✅ | Checked against Have I Been Pwned database |
| Rate Limiting | ✅ | 5 login attempts per minute per email+IP |
| JSON Validation | ✅ | Content-Type and format validation |
| Input Sanitization | ✅ | Null bytes removed, trimmed |
| Name Regex | ✅ | Only letters, spaces, hyphens, apostrophes |
| Email Normalization | ✅ | Lowercase + RFC + DNS validation |
| Token Revocation | ✅ | Logout deletes current token |
| Strict Login Fields | ✅ | Only email & password accepted |

---

## 📝 Validation Rules

### Register
| Field | Rules |
|-------|-------|
| `name` | required, 2-100 chars, regex (letters/spaces/hyphens only) |
| `email` | required, valid email (RFC+DNS), unique |
| `password` | required, min 8, uppercase, lowercase, number, symbol, confirmed |

### Login
| Field | Rules |
|-------|-------|
| `email` | required, valid email format, max 255 |
| `password` | required, min 8, max 255 |

---

## 🐛 Common Errors

### 401 Unauthenticated
- Token tidak valid atau sudah revoked
- Header Authorization tidak dikirim
- Format token salah (harus: `Bearer {token}`)

### 415 Unsupported Media Type
- Content-Type header bukan `application/json`
- Request tidak menggunakan JSON format

### 422 Validation Error
- Password tidak memenuhi requirements (uppercase, symbol, etc)
- Email sudah terdaftar
- Name mengandung karakter tidak valid
- Field required tidak diisi

### 429 Too Many Requests
- Rate limit exceeded (lebih dari 5 login attempts per menit)
- Tunggu 1 menit lalu coba lagi

---

## 📖 Complete Workflow Example

```bash
# 1. Register user dengan password kuat
http POST http://127.0.0.1:8000/api/register \
    name="Alice Smith" \
    email="alice@test.com" \
    password="MySecure@Pass123" \
    password_confirmation="MySecure@Pass123"

# 2. Login dan simpan token
http POST http://127.0.0.1:8000/api/login \
    email="alice@test.com" \
    password="MySecure@Pass123"

# Copy access_token dari response, misal: 1|abc123...

# 3. Access protected route
http GET http://127.0.0.1:8000/api/user \
    "Authorization:Bearer 1|abc123..."

# 4. Logout
http POST http://127.0.0.1:8000/api/logout \
    "Authorization:Bearer 1|abc123..."

# 5. Try access again (will fail - 401)
http GET http://127.0.0.1:8000/api/user \
    "Authorization:Bearer 1|abc123..."
```

---

## 🛠️ Tech Stack

- **Laravel 11** - PHP Framework
- **Laravel Fortify** - Authentication backend (API-first)
- **Laravel Sanctum** - Token-based authentication
- **MySQL/SQLite** - Database

---

## 📚 Additional Resources

- [Laravel Fortify Documentation](https://laravel.com/docs/11.x/fortify)
- [Laravel Sanctum Documentation](https://laravel.com/docs/11.x/sanctum)
- [HTTPie Documentation](https://httpie.io/docs/cli)

