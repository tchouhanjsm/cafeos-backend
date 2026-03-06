# CafeOS Database Schema

---

# Orders Table

Stores restaurant orders.

Columns:

- id
- order_number
- table_id
- staff_id
- shift_id
- order_type
- status
- guest_count
- notes
- created_at
- updated_at

---

# Order Items

Stores items inside an order.

Columns:

- id
- order_id
- menu_item_id
- item_name
- unit_price
- quantity
- subtotal
- notes
- status
- created_at

---

# Staff

Restaurant employees.

Columns:

- id
- name
- email
- role
- pin_code
- is_active

---

# Tables

Restaurant tables.

Columns:

- id
- table_number
- section
- status

---

# Payments

Payment records.

Columns:

- id
- order_id
- payment_method
- amount
- payment_time

---

# Settings

System configuration.

Columns:

- id
- key_name
- value
- label
- updated_at