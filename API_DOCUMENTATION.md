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
        "created_at": "2026-01-22T15:00:00.000000Z"
    },
    "access_token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "refresh_token": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "expires_in": 900,
    "refresh_expires_in": 604800
}
```

> ⏱️ **Token Expiration:**
> - `access_token`: Valid 15 menit (900 detik)
> - `refresh_token`: Valid 7 hari (604800 detik)
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
        "created_at": "2026-01-22T15:00:00.000000Z"
    },
    "access_token": "2|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "refresh_token": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer",
    "expires_in": 900,
    "refresh_expires_in": 604800
}
```

> ⏱️ **Token Expiration:**
> - `access_token`: Valid 15 menit (900 detik)
> - `refresh_token`: Valid 7 hari (604800 detik)
```

**Error Response (401 Unauthorized):**
```json
{
    "message": "Email atau password salah.",
    "attempts_remaining": 4
}
```

**Error Response (429 Account Locked):**
```json
{
    "message": "Akun dikunci sementara karena terlalu banyak percobaan gagal. Silakan coba lagi dalam 15 menit.",
    "retry_after": 900,
    "locked": true
}
```

---

### 3. Refresh Token

**Endpoint:** `POST /api/refresh`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "refresh_token": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Success Response (200 OK):**
```json
{
    "message": "Token refreshed successfully",
    "access_token": "3|yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy",
    "refresh_token": "yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy",
    "token_type": "Bearer",
    "expires_in": 900,
    "refresh_expires_in": 604800
}
```

> 🔄 **Token Rotation:** Setiap refresh, kedua token (access & refresh) akan diganti dengan yang baru untuk keamanan.

**Error Response (401 Unauthorized):**
```json
{
    "message": "Invalid or expired refresh token"
}
```

---

### 4. Logout User

**Endpoint:** `POST /api/logout`

**Headers:**
```
Accept: application/json
Authorization: Bearer {your_access_token}
```

**Success Response (200 OK):**
```json
{
    "message": "Logout successful"
}
```

> ℹ️ Logout akan revoke access token saat ini DAN refresh token terkait.

---

### 5. Logout from All Devices

**Endpoint:** `POST /api/logout-all`

**Headers:**
```
Accept: application/json
Authorization: Bearer {your_access_token}
```

**Success Response (200 OK):**
```json
{
    "message": "Logged out from all devices successfully"
}
```

> ⚠️ Ini akan revoke SEMUA token dari semua perangkat.

---

### 6. Get Authenticated User

**Endpoint:** `GET /api/user`

**Headers:**
```
Accept: application/json
Authorization: Bearer {your_access_token}
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

### 7. Get Active Sessions

**Endpoint:** `GET /api/sessions`

**Headers:**
```
Accept: application/json
Authorization: Bearer {your_access_token}
```

**Success Response (200 OK):**
```json
{
    "sessions": [
        {
            "id": 1,
            "ip_address": "127.0.0.1",
            "user_agent": "Mozilla/5.0...",
            "created_at": "2026-01-23T10:00:00.000000Z",
            "expires_at": "2026-01-30T10:00:00.000000Z",
            "is_current": true
        },
        {
            "id": 2,
            "ip_address": "192.168.1.100",
            "user_agent": "HTTPie/3.2.1",
            "created_at": "2026-01-22T08:00:00.000000Z",
            "expires_at": "2026-01-29T08:00:00.000000Z",
            "is_current": false
        }
    ]
}
```

---

### 8. Get Audit Logs

**Endpoint:** `GET /api/audit-logs`

**Headers:**
```
Accept: application/json
Authorization: Bearer {your_access_token}
```

**Success Response (200 OK):**
```json
{
    "audit_logs": [
        {
            "event": "login_success",
            "status": "success",
            "ip_address": "127.0.0.1",
            "user_agent": "Mozilla/5.0...",
            "created_at": "2026-01-23T10:00:00.000000Z"
        },
        {
            "event": "token_created",
            "status": "success",
            "ip_address": "127.0.0.1",
            "user_agent": "Mozilla/5.0...",
            "created_at": "2026-01-23T10:00:00.000000Z"
        }
    ]
}
```

**Event Types:**
- `login_success` - Login berhasil
- `login_failed` - Login gagal
- `logout` - User logout
- `token_created` - Token baru dibuat
- `token_refreshed` - Token di-refresh
- `token_revoked` - Token di-revoke
- `account_locked` - Akun dikunci karena too many failed attempts
- `register` - User baru mendaftar

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

### 3. Refresh Token

```bash
http POST http://127.0.0.1:8000/api/refresh \
    refresh_token="YOUR_REFRESH_TOKEN_HERE"
```

### 4. Get User Info (with Token)

```bash
http GET http://127.0.0.1:8000/api/user \
    "Authorization:Bearer YOUR_ACCESS_TOKEN"
```

### 5. Get Active Sessions

```bash
http GET http://127.0.0.1:8000/api/sessions \
    "Authorization:Bearer YOUR_ACCESS_TOKEN"
```

### 6. Get Audit Logs

```bash
http GET http://127.0.0.1:8000/api/audit-logs \
    "Authorization:Bearer YOUR_ACCESS_TOKEN"
```

### 7. Logout (Current Device)

```bash
http POST http://127.0.0.1:8000/api/logout \
    "Authorization:Bearer YOUR_ACCESS_TOKEN"
```

### 8. Logout from All Devices

```bash
http POST http://127.0.0.1:8000/api/logout-all \
    "Authorization:Bearer YOUR_ACCESS_TOKEN"
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
- **Access Token expiration:** 15 menit
- **Refresh Token expiration:** 7 hari

---

## 🔒 Security Features

| Feature | Status | Description |
|---------|--------|-------------|
| Password Hashing | ✅ | Bcrypt via `Hash::make()` |
| Strong Password | ✅ | Uppercase, lowercase, number, symbol required |
| Breached Password Check | ✅ | Checked against Have I Been Pwned database |
| Register Rate Limit | ✅ | **1 register per 3 jam per IP** |
| Login Rate Limit | ✅ | **1 login attempt per 1 menit per IP** |
| Account Lockout | ✅ | **Block 15 menit setelah 5 failed attempts per email** |
| Access Token Expiration | ✅ | **Token expired setelah 15 menit** |
| Refresh Token | ✅ | **Valid 7 hari untuk extend session** |
| Token Rotation | ✅ | **Refresh menghasilkan token baru (access + refresh)** |
| Generic Error Messages | ✅ | **Tidak mengungkapkan apakah email terdaftar** |
| Timing Attack Prevention | ✅ | **Hash check dilakukan meskipun user tidak ada** |
| JSON Validation | ✅ | Content-Type and format validation |
| Input Sanitization | ✅ | Null bytes removed, trimmed |
| Name Regex | ✅ | Only letters, spaces, hyphens, apostrophes |
| Email Normalization | ✅ | Lowercase + RFC + DNS validation |
| Token Revocation | ✅ | Logout deletes current token |
| Logout All Devices | ✅ | **Revoke semua token dari semua perangkat** |
| Session Management | ✅ | **Lihat semua active sessions** |
| Audit Logging | ✅ | **Log semua auth events (login, logout, token, etc)** |
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
- **Login:** Rate limit exceeded (lebih dari 1 attempt per menit)
- **Login Lockout:** 5 percobaan gagal = akun dikunci 15 menit
- **Register:** Rate limit exceeded (hanya 1 register per 3 jam per IP)
- Response akan menampilkan waktu tunggu dalam `retry_after`

**Contoh Response Login Rate Limit:**
```json
{
    "message": "Terlalu banyak percobaan login. Silakan tunggu 1 menit.",
    "retry_after": 60
}
```

**Contoh Response Account Lockout (5 failed attempts):**
```json
{
    "message": "Akun dikunci sementara karena terlalu banyak percobaan gagal. Silakan coba lagi dalam 15 menit.",
    "retry_after": 900,
    "locked": true
}
```

**Contoh Response Register Rate Limit:**
```json
{
    "message": "Anda hanya dapat mendaftar 1x setiap 3 jam. Silakan tunggu 2 jam 45 menit lagi.",
    "retry_after": 9900
}
```

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

- [Laravel Fortify Documentation](https://laravel.com/docs/12.x/fortify)
- [Laravel Sanctum Documentation](https://laravel.com/docs/12.x/sanctum)
- [HTTPie Documentation](https://httpie.io/docs/cli)

