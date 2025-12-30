# Mr. Xie's Convenience Store - API Reference

## Base URL

- **Production / Cloud**: `https://mr-xie-s-convenience-store-main-d3awzd.laravel.cloud/api`
- **Local Development**: `http://localhost:8000/api`

> **Note**: For local Docker development, the frontend is configured to use `http://localhost:8000/api` via the `VUE_APP_API_URL` build argument in `docker-compose.yml`.

## Authentication

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `POST` | `/register` | Register a new user | No |
| `POST` | `/login` | Login and receive API token | No |
| `POST` | `/logout` | Logout and invalidate token | Yes |
| `POST` | `/email/verification-notification` | Resend email verification link | Yes |
| `POST` | `/email/verify-code` | Verify email with code | Yes |

## Public Data & Products

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/products` | List products with pagination & filtering | No |
| `GET` | `/products/{id}` | Get product details | No |
| `GET` | `/products/{id}/reviews` | Get reviews for a product | No |
| `GET` | `/categories` | List all categories | No |
| `GET` | `/stores` | List store locations | No |
| `GET` | `/stores/{id}` | Get store details | No |
| `GET` | `/settings` | Global settings (carousel, etc.) | No |

## User Profile & Wallet

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/user` | Get current user info | Yes |
| `PUT` | `/user/profile` | Update profile info (name, phone) | Yes |
| `PUT` | `/profile` | Update address/shipping info | Yes |
| `GET` | `/user/wallet` | Get wallet balance and transaction history | Yes |
| `POST` | `/user/wallet/deposit` | Deposit funds into wallet | Yes |
| `GET` | `/favorites` | List favorite products | Yes |
| `POST` | `/favorites` | Add product to favorites | Yes |
| `DELETE` | `/favorites/{productId}` | Remove product from favorites | Yes |

## Shopping Cart

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/cart` | Get current user's cart | Yes |
| `POST` | `/cart/items` | Add item to cart | Yes |
| `PUT` | `/cart/items/{itemId}` | Update cart item quantity | Yes |
| `DELETE` | `/cart/items/{itemId}` | Remove item from cart | Yes |

## Orders

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/orders` | List user's orders | Yes |
| `POST` | `/orders` | Create a new order | Yes |
| `GET` | `/orders/{id}` | Get order details | Yes |
| `POST` | `/orders/{order}/pay` | Pay for an order | Yes |
| `POST` | `/orders/{order}/refund` | Request a refund | Yes |

## Coupons

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/coupons` | List available coupons | Yes |
| `POST` | `/coupons/check` | Check coupon validity | Yes |

## Reviews

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `POST` | `/products/{id}/reviews` | Create a review | Yes |
| `GET` | `/products/{id}/reviews/can-review` | Check if user can review product | Yes |
| `DELETE` | `/reviews/{id}` | Delete a review | Yes |

## Admin - Dashboard & General

| Method | Endpoint | Description | Access |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/stats` | Dashboard statistics | Staff/Admin |
| `GET` | `/admin/inventory-report` | Low stock report | Staff/Admin |

## Admin - Product Management

| Method | Endpoint | Description | Access |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/products` | Admin product list | Staff/Admin |
| `POST` | `/admin/products` | Create product | Staff/Admin |
| `PUT` | `/admin/products/{id}` | Update product | Staff/Admin |
| `DELETE` | `/admin/products/{id}` | Delete product | Staff/Admin |

## Admin - Product Variants & Attributes

| Method | Endpoint | Description | Access |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/products/{id}/variants` | List variants for product | Staff/Admin |
| `POST` | `/admin/products/{id}/variants` | Create single variant | Staff/Admin |
| `PUT` | `/admin/products/{id}/variants/{variant}` | Update variant | Staff/Admin |
| `DELETE` | `/admin/products/{id}/variants/{variant}` | Delete variant | Staff/Admin |
| `POST` | `/admin/products/{id}/variants/bulk-generate` | Bulk generate variants | Staff/Admin |
| `PUT` | `/admin/products/{id}/variants/bulk-price` | Bulk update prices | Staff/Admin |
| `PUT` | `/admin/products/{id}/variants/bulk-stock` | Bulk update stock | Staff/Admin |
| `POST` | `/admin/products/{id}/attributes` | Add attribute (Color/Size) | Staff/Admin |
| `PUT` | `/admin/attributes/{attribute}` | Update attribute | Staff/Admin |
| `DELETE` | `/admin/attributes/{attribute}` | Delete attribute | Staff/Admin |
| `POST` | `/admin/attributes/{attribute}/values` | Add attribute value | Staff/Admin |

## Admin - Orders

| Method | Endpoint | Description | Access |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/orders` | List all orders | Staff/Admin |
| `GET` | `/admin/orders/{id}` | Get order details | Staff/Admin |
| `PUT` | `/admin/orders/{order}/status` | Update order status | Staff/Admin |
| `PUT` | `/admin/orders/{order}/logistics` | Update tracking number | Staff/Admin |
| `POST` | `/admin/orders/{order}/refund` | Process refund | Staff/Admin |

## Admin - User Management (Restricted)

| Method | Endpoint | Description | Access |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/users` | List users | Admin Only |
| `POST` | `/admin/users` | Create user | Admin Only |
| `GET` | `/admin/users/{id}` | Get user details | Admin Only |
| `PUT` | `/admin/users/{id}` | Update user info | Admin Only |
| `PUT` | `/admin/users/{id}/role` | Update user role (Member/Admin) | Admin Only |
| `POST` | `/admin/users/{id}/wallet/transaction` | Adjust wallet balance | Admin Only |
| `DELETE` | `/admin/users/{id}` | Soft delete user | Admin Only |
| `DELETE` | `/admin/users/{id}/force` | Permanently delete user | Admin Only |

## Admin - Categories & Stores (Restricted)

| Method | Endpoint | Description | Access |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/categories` | List categories | Admin Only |
| `POST` | `/admin/categories` | Create category | Admin Only |
| `PUT` | `/admin/categories/{id}` | Update category | Admin Only |
| `DELETE` | `/admin/categories/{id}` | Delete category | Admin Only |
| `POST` | `/admin/categories/{id}/reassign` | Delete category & reassign products | Admin Only |
| `POST` | `/admin/categories/fix-slugs` | Repair category slugs | Admin Only |
| `GET` | `/admin/coupons` | List all coupons | Admin Only |
| `POST` | `/admin/coupons` | Create coupon | Admin Only |
| `PUT` | `/admin/coupons/{id}` | Update coupon | Admin Only |
| `DELETE` | `/admin/coupons/{id}` | Delete coupon | Admin Only |
| `PUT` | `/admin/stores/{id}` | Update store info | Admin Only |

## How to Test

You can use tools like **Postman**, **Insomnia**, or `curl` to test these endpoints.

**Example Request (Get Products):**
```bash
curl -X GET http://localhost:8000/api/products
```

**Example Request (Login):**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "password"}'
```

**Authenticated Requests:**
Include the token returned from login in the `Authorization` header:
`Authorization: Bearer <your-token>`
