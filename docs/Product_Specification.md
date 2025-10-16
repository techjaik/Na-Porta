# Na Porta - Product Specification

Version: 1.0
Owner: techjaik
Last updated: 2025-10-16

## 1. Overview
Na Porta is a Brazilian last‑mile delivery storefront for household essentials. Users browse products, add to cart, manage multiple delivery addresses, and place orders (cash as primary payment). An admin panel manages products, categories, banners, and orders.

## 2. Goals & Success Metrics
- G1: Simple, reliable order flow on web (mobile‑first)
- G2: Fast page loads (<= 2s for user pages on typical hosting)
- G3: Easy catalog and order management for admins
- KPIs: Conversion rate, cart completion %, average TTFB, p95 page load, admin task time

## 3. Scope (MVP)
- Public site: browse products (by category, search), cart, checkout (cash), order confirmation
- Account: profile, saved addresses (multiple, default selection)
- Admin: products, categories, banners, orders list, basic user management
- Media: upload from computer or via URL (products/categories)
- Deployment: GitHub Actions to InfinityFree hosting

Out of scope (MVP): online payments, delivery fee calculation, coupons, notifications, multi‑tenant.

## 4. Personas
- Shopper (Ana): mobile web user, wants quick buy and easy address reuse
- Store Manager (Carlos): desktop admin, wants quick updates to products and orders

## 5. User Stories (Selected)
- As a shopper, I can browse products by category and search by name/description.
- As a shopper, I can add/remove items in the cart and update quantities.
- As a shopper, I can save multiple addresses, choose a default, and pick one during checkout.
- As a shopper, I can place an order with cash payment and see a confirmation with order ID.
- As a manager, I can create/edit/deactivate products and categories; upload images from file or URL.
- As a manager, I can view orders, their items, and status.

## 6. Functional Requirements

### 6.1 Catalog
- List products with pagination (12 per page)
- Filter by category; full‑text search on name/description
- Product card: image, name, short description, price, add‑to‑cart

### 6.2 Cart
- Add to cart from product list
- Update quantity, remove item, clear cart (AJAX, no full reload)
- Support anonymous carts (session) and user carts (user_id)

### 6.3 Account & Addresses
- View profile
- Manage addresses: add, edit, delete, mark default
- Default address auto‑selected at checkout

### 6.4 Checkout (Cash only)
- Choose from saved addresses or input a one‑off address
- Confirm order summary (items, quantities, total)
- Place order -> create order + order_items; clear cart
- Show success with order ID

### 6.5 Admin
- Products: CRUD, activation toggle, category assignment, price, image upload (file/URL)
- Categories: CRUD, activation, sort order, image upload (file/URL)
- Banners: CRUD, activation
- Orders: list with pagination, view details and status

## 7. Non‑Functional Requirements (NFRs)
- Performance: user pages p95 < 2.5s; admin p95 < 3s
- Pagination everywhere high‑volume (products, admin lists)
- DB Indexing on frequent filters and joins (user_id, product_id, category_id, created_at)
- Availability: 99%+ (best‑effort on shared hosting)
- Security: prepared statements, no secrets in repo, role‑based admin access
- Localization: pt‑BR copy, currency (BRL), formats
- Accessibility: semantic HTML, keyboard navigation, alt texts

## 8. Information Architecture
- Public: Home, Categories, Products, Cart, Checkout, Account
- Admin: Dashboard, Products, Categories, Banners, Orders, Users

## 9. Data Model (logical)
- users (id, name, email, password_hash, phone, created_at)
- user_addresses (id, user_id, label, street, number, complement, neighborhood, city, state, zip, is_default)
- categories (id, name, slug, is_active, sort_order, image_url)
- products (id, name, slug, category_id, description, price, image_url, is_active, is_featured, created_at)
- promotional_banners (id, title, image_url, link_url, is_active, created_at)
- cart_items (id, user_id, session_id, product_id, quantity, created_at)
- orders (id, user_id, total_amount, delivery_address, payment_method, status, created_at, updated_at)
- order_items (id, order_id, product_id, quantity, price, created_at)

Relationships:
- users 1—N user_addresses
- categories 1—N products
- users 1—N orders; orders 1—N order_items
- users 1—N cart_items OR session_id —N cart_items

Indexes (examples):
- products: (is_active), (category_id), (is_featured), (created_at), (name)
- categories: (is_active), (sort_order)
- cart_items: (user_id), (session_id), (product_id)
- orders: (user_id), (status), (created_at)
- order_items: (order_id), (product_id)
- users: (email), (created_at)
- user_addresses: (user_id), (is_default)

## 10. API & Endpoints (current)
- api/cart.php (JSON) actions: add, update, remove, clear
  - POST { action, product_id?, item_id?, quantity? }
  - Responses: { success, message?, data? }
- simple_address_api.php (JSON) actions: list, add, update, delete, set_default
  - Auth via session; user_id derived from session
- Page POST handlers: checkout.php processes order placement

Future (optional REST design):
- /api/addresses [GET, POST, PUT, DELETE]
- /api/orders [GET, POST]

## 11. Key Flows

### Add to Cart
1) User clicks Add -> POST api/cart.php {action:'add', product_id}
2) On success, update cart counter toast

### Checkout
1) User chooses address (saved/default) and confirms cash
2) Server validates cart, creates order + items, clears cart
3) Show success with order ID

### Address Management
1) List addresses; add/edit/delete via JSON endpoints
2) Only one default per user; setting default unsets others

## 12. Validation & Error Handling
- All DB access via prepared statements
- User‑friendly messages on failures (pt‑BR)
- Server‑side validation: required fields, numeric, formats (CEP)
- Graceful empty states (empty cart, no products)

## 13. Performance Plan
- Pagination added to products (12/page)
- AJAX updates in cart (no full reload)
- DB optimization script adds indexes
- Avoid per‑request schema creation in pages
- Image sizes optimized (where possible)

## 14. Deployment & Environments
- Dev: XAMPP (localhost, DB: na_porta_db, user root, no pwd)
- Prod: InfinityFree (domain *.free.nf, MySQL and FTP configured)
- CI/CD: GitHub Actions -> FTP Deploy (SamKirkland/FTP-Deploy-Action)
- Secrets stored in GitHub/hosting env, not in repo

## 15. Admin Policies
- Only authenticated admins can access /admin
- Product and category slugs unique; auto‑dedupe with suffixes
- Deleting entities that have dependencies should be restricted or soft‑deleted

## 16. Analytics & Monitoring (optional)
- Page views, add‑to‑cart, checkout start, checkout success
- Error logs review cadence

## 17. Testing Strategy
- Unit tests for utilities (where feasible)
- Integration smoke tests for cart and checkout
- Optional: Integrate TestSprite MCP Server to run end‑to‑end flows in CI

Acceptance criteria (examples):
- Add to cart updates counter within 250ms (AJAX)
- Products page p95 < 2.5s on shared hosting with 500+ SKUs
- Checkout creates order + items and clears cart reliably; user sees order ID
- Admin can upload images via file/URL and see them on the listing

## 18. Risks & Mitigations
- Shared hosting performance: mitigate with pagination, indexes, caching
- Upload security: validate files and mime types; store paths safely
- Session reliability: ensure consistent session handling for carts

## 19. Roadmap (Next)
- Online payments integration (Pix/credit)
- Delivery fees and address‑based pricing
- Order statuses and notifications (email/WhatsApp)
- Coupons and promotions
- Admin role/permission levels

## 20. Glossary
- CEP: Brazilian postal code
- PIX: Instant payment in Brazil

-- End of Document --

