# Agro Trade Backend (Laravel API)

## Setup

1. Copy `.env.example` to `.env` and configure your MySQL credentials:
   ```bash
   cp .env.example .env
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Generate app key:
   ```bash
   php artisan key:generate
   ```

4. Run migrations and seed:
   ```bash
   php artisan migrate --seed
   ```

5. Start server:
   ```bash
   php artisan serve
   ```
   API will be at `http://localhost:8000/api`.

## Seeded Test Users

| Role        | Email                     | Password |
|-------------|---------------------------|----------|
| Admin       | admin@agrotrade.local     | password |
| Farmer      | farmer@example.com        | password |
| Store Owner | store@example.com         | password |
| Client      | client@example.com        | password |

## Security
- Role middleware (`role:farmer,store_owner`) on seller endpoints.
- Admin middleware checks `AUTHORIZED_ADMIN_EMAIL` from env.
- Sanctum token auth; tokens stored as Bearer in mobile, or httpOnly cookies in SPA.
- Rate limiting via `throttle:api`.
- Security headers middleware.
- Input validation and `$fillable` on all models.

## API Endpoints

| Method | Endpoint                     | Auth  | Description               |
|--------|------------------------------|-------|---------------------------|
| POST   | /auth/register               | No    | Register user             |
| POST   | /auth/login                  | No    | Login, get token          |
| POST   | /auth/logout                 | Yes   | Logout                    |
| GET    | /products                    | No    | List approved products    |
| GET    | /products/{id}               | No    | Single product            |
| POST   | /products                    | Seller| Create product            |
| PUT    | /products/{id}               | Owner | Update product            |
| DELETE | /products/{id}               | Owner | Delete product            |
| GET    | /orders                      | Yes   | Client orders             |
| POST   | /orders                      | Yes   | Create order              |
| GET    | /orders/seller               | Seller| Seller orders             |
| PUT    | /orders/{id}/status          | Seller| Update order status       |
| GET    | /reviews                     | Yes   | List reviews              |
| POST   | /reviews                     | Yes   | Create review             |
| POST   | /payments/initiate           | Yes   | Chargily stub             |
| POST   | /payments/verify             | Yes   | Verify payment            |
| *Admin endpoints under /admin/* |      |                           |

## Folder Structure
```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   └── Models/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/api.php
└── .env.example
```
