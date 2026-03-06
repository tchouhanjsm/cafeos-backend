Content:

# CafeOS API Reference

Base URL


http://localhost:8000/api


---

# Authentication

POST


/auth/login


Body:


{
"pin": "1234"
}


Response:


JWT Token


---

# Orders

Create Order

POST


/orders


---

Get Orders

GET


/orders


---

Get Order Details

GET


/orders/{id}


---

Add Item

POST


/orders/{id}/items


---

Send Order to Kitchen

POST


/orders/{id}/send


---

Request Bill

POST


/orders/{id}/request-bill


---

Billing Queue

GET


/billing-queue