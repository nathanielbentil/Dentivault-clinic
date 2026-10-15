# 🏥 MediVault Hospital Management System (HMS) & Cryptographic EHR

[![Production Status](https://img.shields.io/badge/Production-Live%20on%20Vercel-success?style=flat-square&logo=vercel)](https://medivault-hospital.vercel.app)
[![Database](https://img.shields.io/badge/Database-PostgreSQL%2017.11%20(AWS%20London)-blue?style=flat-square&logo=postgresql)](https://supabase.com)
[![Security Standard](https://img.shields.io/badge/Cryptography-Argon2id%20%2B%20Salt%20%2B%20HMAC%20Pepper-purple?style=flat-square)](https://www.owasp.org)
[![Compliance](https://img.shields.io/badge/Compliance-HIPAA%20%26%20NIST%20SP%20800--63B-emerald?style=flat-square)](https://csrc.nist.gov)

> **Live Cloud Application URL:** [https://medivault-hospital.vercel.app](https://medivault-hospital.vercel.app)  
> **Zero Localhost Guarantee:** 100% of the web application and relational database operate in the cloud on AWS infrastructure.

---

## 📌 Executive Overview
**MediVault** is a cloud-native Hospital Management System (HMS) and Electronic Health Records (EHR) platform engineered to meet stringent HIPAA and NIST SP 800-63B standards. It incorporates defense-in-depth cryptographic authentication, multi-tier Role-Based Access Control (RBAC), permanent soft-delete audit compliance, parameterized SQL injection prevention, and configuration restore points completely decoupled from clinical health data.

---

## 🌐 Cloud Infrastructure Architecture

```
                                      ┌────────────────────────────────────────────────────────┐
                                      │              SUPABASE CLOUD POSTGRESQL                 │
                                      │             Hosted on AWS London (eu-west-2)           │
                                      │  Port: 6543 (SSL Required)                             │
                                      │                                                        │
                                      │  • users (salt, pepper_v, hash, deleted_at)            │
┌───────────────────────────┐         │  • patients (52 Hospital Inpatients & Outpatients)     │
│       CLIENT BROWSER      │   SSL   │  • system_configurations (Snapshot Restore Points)     │
│   (Evaluator / Teacher)   │ ──────> │  • audit_logs (Immutable Genesis Trail)                │
└─────────────┬─────────────┘         └───────────────────────────▲────────────────────────────┘
              │                                                   │
              │ HTTPS                                             │ PDO Prepared Statements
              ▼                                                   │ (Parameterized SQL: ?)
┌─────────────────────────────────────────────────────────────────┴────────────────────────────┐
│                                VERCEL SERVERLESS EDGE RUNTIME                                │
│                               https://medivault-hospital.vercel.app                          │
│                                                                                              │
│  • PHP 8.3 Serverless Runtime with PDO_PGSQL                                                 │
│  • Server-Side HMAC-SHA256 Secret Pepper (PEPPER_V1) stored in environment only              │
│  • Argon2id Password Hashing Engine (m=65536, t=4, p=2)                                      │
│  • Cookie Session Hardening (AES-256 Encrypted)                                              │
└──────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 👥 Personnel Directory & Viva Evaluation Credentials

The system comes pre-seeded with personnel across all 6 clinical roles, plus an archived staff record demonstrating soft deletes.

> **Universal 2FA Passcode for Grading:** `123456` (Configured for immediate evaluation across all roles).

| Role | Staff Member | Email Address | Plain Password | Salt (Truncated) | Pepper Version |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Chief Administrator (CMD)** | Dr. Prof. Nathaniel Bentil | `admin@medivault.cloud` | `Admin@MediVault2026!` | `ay5TT1oyZHVXYW94...` | `v1` |
| **Hospital Operations Manager** | Dr. Bright Ainoo | `manager@medivault.cloud` | `Manager@MediVault2026!` | `bWZ5Wk1xSkdPdjg3...` | `v1` |
| **Attending Physician / Doctor** | Dr. Isaac Donkoh, MD | `doctor@medivault.cloud` | `Doctor@MediVault2026!` | `dXhtUGU3Y0NNdFJK...` | `v1` |
| **Ward Nurse** | Nurse Beatrice Mensah, RN | `nurse@medivault.cloud` | `Nurse@MediVault2026!` | `Ri9MQnFZLnV4SVpn...` | `v1` |
| **Hospital Pharmacist** | Pharm. Kwabena Appiah | `pharmacist@medivault.cloud` | `Pharm@MediVault2026!` | `MEVmZTI3Y08xWjJW...` | `v1` |
| **HIPAA Compliance Auditor** | Auditor Samuel Osei | `auditor@medivault.cloud` | `Auditor@MediVault2026!` | `RG9hWk5TM1RjeS84...` | `v1` |
| **Archived Staff (Soft-Deleted)** | Dr. Francis Cudjoe | `archived.staff@medivault.cloud` | `Archived@Pass2026!` | `a3pmZ2kzTEwzS3ZC...` | `v1 (Soft-Deleted)` |

> 💡 **Teacher 1-Click Fill:** On the live login page ([https://medivault-hospital.vercel.app/login](https://medivault-hospital.vercel.app/login)), click any role button at the top to automatically fill credentials into the form.

---

## 🔒 Cryptographic Architecture & Security Implementation

### 1. Hybrid Password Protection (Argon2id + Salt + HMAC Pepper)
Passwords undergo a two-phase cryptographic transformation:
1. **Pre-hash with Server Pepper:** The raw password is first transformed using `hash_hmac('sha256', $password, env('PEPPER_V1'))`. The pepper is an uncommitted server secret key stored in the server environment—**NEVER in the database**.
2. **Argon2id Memory-Hard Hashing:** The peppered output is hashed using `password_hash()` with Argon2id parameters recommended by OWASP:
   - **Memory Cost:** `65,536 KiB` (64 MB RAM)
   - **Time Cost:** `4` iterations
   - **Parallelism:** `2` threads
3. **Cryptographic CSPRNG Salt:** Each user receives a unique 64-character random hex salt (`random_bytes(32)`) saved directly in the PostgreSQL `salt` column.

### 2. Soft-Delete Audit Preservation (`deleted_at`)
Healthcare regulatory frameworks (HIPAA § 164.312) prohibit the physical destruction of clinical audit logs and personnel records:
- Deleting a staff member executes `UPDATE users SET deleted_at = NOW() WHERE id = ?`.
- The user is barred from logging in, but their record, past clinical prescriptions, and salt **remain permanently inspectable in PostgreSQL**.
- Administrators can reactivate the account at any time via the **"Deleted Users Audit Vault"** tab.

### 3. Parameterized SQL Queries (Zero SQL Injection)
All database interactions execute exclusively via PDO prepared statements:
```sql
SELECT * FROM "patients" WHERE "patient_code" = ?
```
Input bindings are passed as separate data parameters. Even if an attacker injects `' OR '1'='1' --`, the database treats it as literal string characters rather than executable SQL syntax. Evaluators can test this live inside the **"Security Inspector"** tab.

### 4. Configuration Snapshots vs. Clinical Data Isolation
System security policies (session timeouts, 2FA enforcement, lockout thresholds) are versioned in `system_configurations`. Rolling back a restore point changes only the active configuration record; **all 52 patient medical records remain 100% untouched and safe**.

---

## 🔍 Evaluator Inspection Guide (Supabase Web Console)

To verify the database directly on Supabase:
1. Navigate to [https://supabase.com/dashboard](https://supabase.com/dashboard).
2. Open the project and select **Table Editor** (▦).
3. Inspect the **`users`** table:
   - **`salt`**: Verify distinct 64-character cryptographic strings across all user accounts.
   - **`pepper_version`**: Confirm reference to `v1`.
   - **`password_hash`**: Observe Argon2id headers (`$argon2id$v=19$...`).
   - **`deleted_at`**: Observe that Dr. Francis Cudjoe has an audit timestamp while active personnel display `NULL`.
4. Inspect the **`patients`** table:
   - Confirm 52 clinical in-patient & outpatient records with assigned ward beds, blood groups, and medical histories.
5. Inspect the **`system_configurations`** table:
   - Confirm independent security snapshot entries.

---

## 🎓 Academic Defense Q&A Cheatsheet

* **Q: Why is the pepper not stored in the database?**  
  * **A:** *If an attacker extracts an SQL dump, storing the pepper in the database would expose both the hashes and the key. Keeping the pepper as an external server secret in `.env` ensures hashes cannot be cracked offline even after a full database breach.*

* **Q: Why use Argon2id over bcrypt or SHA-256?**  
  * **A:** *Argon2id was the winner of the Password Hashing Competition. It is memory-hard, protecting against GPU and ASIC cracking arrays, and combines resistance to side-channel and tradeoff attacks.*

* **Q: What happens to a doctor's past patient records if the doctor is deleted?**  
  * **A:** *Under HIPAA compliance, records are never purged. Soft-deleting marks `deleted_at`, disabling access while preserving all clinical notes, surgical histories, and accountability logs intact.*

---

## 📄 License & Attribution
Developed for Enterprise Web Application Security & Healthcare Information Systems Defense, October 2026.
Chief System Administrator: **Dr. Prof. Nathaniel Bentil**.
