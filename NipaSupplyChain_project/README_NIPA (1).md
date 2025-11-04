# 🌿 NIPA SUPPLY CHAIN SYSTEM

## 📘 Project Overview
The **NIPA Supply Chain System** is a web-based management platform designed to track nipa production, inventory, and sales.
It allows users to record nipa batches, monitor stock levels, and generate printable reports for improved operational visibility.

### 🧭 System Objectives
- Digitalize nipa supply and transaction records.
- Simplify product tracking and inventory management.
- Provide quick access to reports and analytics for decision-making.

---

## 🗂️ Features Overview
| Feature | Description |
|----------|-------------|
| **Login Page** | Secure access for authorized users. |
| **Dashboard** | Displays summary of batches, inventory, and transactions. |
| **Batch Form** | Add, edit, or update nipa production batches. |
| **Inventory Page** | Monitor available nipa stocks and product status. |
| **Transaction / Report Page** | Log sales and generate printable reports. |
| **Search Function** | Quickly find nipa records by name, date, or ID. |

---

## 💾 Database
Database Name: `nipa_db`

### Tables Overview
- `users` – Stores admin and staff login information.
- `batches` – Tracks nipa batches with processing details.
- `inventory` – Maintains current stock data.
- `transactions` – Logs sales and delivery records.

To restore the database:
1. Open **phpMyAdmin** → Import tab
2. Import `create_tables.sql` first (structure)
3. Import `seed.sql` second (data)

---

## 💻 Source Code Structure
```
nipa_project/
│
├── docs/
│   ├── project_plan.pdf
│   └── user_credentials.txt
│
├── database/
│   ├── create_tables.sql
│   └── seed.sql
│
├── src/
│   ├── index.php
│   ├── dashboard.php
│   └── ...
│
└── README.md
```

---

## 🎥 Demo Video
🎬 [Watch Demo Video](https://drive.google.com/yourlink)

---

## 🔐 User Credentials
- **Admin:** admin / 12345  
- **Staff:** staff / 12345

---

## 👥 Team Members
| Name | Role |
|------|------|
| Paulina De la Torre | Project Lead / Backend Developer |
| Chris Mhel Jorie Corpuz | Frontend Developer |
| Mary Claire Valle | Database Designer |

---

## 🏫 School Information
**Cagayan State University**  
Bachelor of Science in Information Technology (BSIT)

---

## 📅 Date
November 2025
