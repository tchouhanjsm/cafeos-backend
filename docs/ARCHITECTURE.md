# CafeOS Core Architecture

CafeOS follows a layered architecture.


UI Layer
↓
API Layer
↓
Service Layer
↓
Models
↓
Database


---

# System Layers

## 1 UI Layer

User interfaces include:

- POS Interface
- Kitchen Display
- Admin Dashboard
- Reports

---

## 2 API Layer

Built with Laravel controllers.

Examples:

- AuthController
- OrderController
- BillingController
- MenuController
- SettingsController

---

## 3 Service Layer

Handles business logic.

Examples:

- Order Service
- Billing Engine
- Kitchen Workflow
- Payment Engine
- Shift Management

---

## 4 Model Layer

Database models:

- Orders
- OrderItems
- Staff
- Tables
- Payments
- Shifts
- Settings

---

## 5 Database Layer

MySQL database storing all restaurant data.

Tables include:

- orders
- order_items
- menu_items
- tables
- staff
- payments
- shifts
- settings

---

# Modular Design

CafeOS supports future modules:

- Kitchen Display
- Inventory
- CRM
- Loyalty
- Reservations

Modules plug into the API layer.