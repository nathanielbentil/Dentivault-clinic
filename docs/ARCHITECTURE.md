# System Architecture & Technical Design Document

## 1. Executive Summary
This document defines the high-level architecture, layer separation, data flow, and trust boundaries for the **Secure Web-Based Authentication, Authorization, Backup & Recovery System**.

---

## 2. High-Level Layered Architecture

```
                       [ 🌐 Client Web Browser ]
                                   │
                                   │ HTTPS / Strict-Transport-Security (HSTS)
                                   ▼
                       [ 🛡️ Local Web Server (nginx / Herd) ]
                       - TLS Termination
                       - Security Headers (CSP, X-Frame-Options, etc.)
                                   │
                                   ▼
                       [ 🧱 Application Firewall / Middleware Layer ]
                       - HTTPS Enforcer
                       - Rate Limiting & Throttling
                       - CSRF Token Validation
                       - Strict Content-Security-Policy
                       - Session State & Fixation Guard
                                   │
                                   ▼
                       [ 🔐 Authentication & Identity Layer ]
                       - Laravel Fortify
                       - Argon2id + Server-side HMAC Pepper Hasher
                       - TOTP 2FA Engine (RFC 6238)
                       - Single-use Hashed Recovery Codes
                       - Account Status Guard (Active / Pending / Disabled)
                                   │
                                   ▼
                       [ 👥 Authorization & RBAC Layer ]
                       - Laravel Gates & Policies
                       - Role Definitions (`USER`, `ADMIN`)
                       - Resource Ownership Verification
                                   │
                                   ▼
                       [ 🗄️ Database & Audit Layer ]
                       - Parameterized Query Builder / Eloquent
                       - Append-Only Audit Logging
                       - Database Migration System
                                   │
                 ┌─────────────────┴─────────────────┐
                 ▼                                   ▼
   [ ☁️ Supabase Cloud PostgreSQL ]     [ 💾 Local PostgreSQL 17 ]
   - Production Primary Database        - Development Sandbox
   - TLS Required                       - Automated Test Database
   - Row-Level-Security (RLS)           - Restore Drill Sandbox
                 │
                 ▼ (Nightly Dump + age X25519 Encryption)
   [ 📦 3-2-1 Backup & Recovery System ]
   - Copy 1: Live Cloud Database
   - Copy 2: Encrypted Local Archive (`backups/local/`)
   - Copy 3: Encrypted Cloud S3 Bucket (Supabase Storage)
```

---

## 3. Trust Boundaries & Security Enclaves

### Trust Boundary 1: Client vs. Server
* **Rule:** The frontend browser is an untrusted environment.
* **Defense:** All authorization checks, input validation, role checks, and rate-limiting take place strictly on the backend. No sensitive tokens or secrets are stored in client-accessible storage (`localStorage` or `sessionStorage`).

### Trust Boundary 2: Application vs. Database
* **Rule:** The database must never execute unescaped or concatenated input.
* **Defense:** 100% of database interactions use parameterized SQL statements. Tables on Supabase have Row Level Security enabled to prevent unauthorized REST/Data API leakage.

### Trust Boundary 3: Server vs. Backups
* **Rule:** Compromising the server must not expose past backups.
* **Defense:** Backups are encrypted using **asymmetric encryption (`age`)**. The server only holds the **public key** (which can only encrypt). The **private key** is kept offline on a secure external device.

---

## 4. Key Architectural Decisions (Summary)
1. **Framework:** Laravel 11.x on PHP 8.3 (Modern, secure, native Argon2id, built-in CSRF/Session guards).
2. **Auth Engine:** Fortify with custom Peppered Hasher (HMAC-SHA256 + Argon2id).
3. **Database:** Dual PostgreSQL setup (Supabase in cloud, PostgreSQL 17 local for drills and testing).
4. **Backups:** Asymmetric encryption with `age` + 3-2-1 multi-destination redundancy.
