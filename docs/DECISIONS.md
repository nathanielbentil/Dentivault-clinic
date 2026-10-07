# Architectural Decision Records (ADR / DECISIONS.md)

This log records every major technical and architectural decision, alternatives considered, and the security justification. Use this document when preparing for project defense or oral examination.

---

## ADR-01: Framework & Language Selection
* **Decision:** PHP 8.3+ with Laravel 11.x.
* **Alternatives Considered:** Plain PHP from scratch, Node.js/Express, Python/FastAPI.
* **Justification:**
  - PHP 8.3 includes native, optimized support for **Argon2id** (`PASSWORD_ARGON2ID`).
  - Laravel provides battle-tested security components (CSRF protection, secure session manager, parameterized PDO query builder, password validation rules) vetted by thousands of security researchers.
  - Plain PHP increases the risk of subtle cryptographic or session handling bugs.

---

## ADR-02: Authentication Architecture (Custom Backend vs. Managed Supabase Auth)
* **Decision:** Self-hosted authentication backend using Laravel Fortify + custom Peppered Hasher; Supabase used strictly as managed PostgreSQL and Storage.
* **Alternatives Considered:** Supabase Auth (GoTrue).
* **Justification:**
  - Supabase Auth does not natively support server-side HMAC password peppers, Argon2id parameter tuning, or hashed offline recovery codes in custom database tables.
  - Building our authentication layer on Laravel Fortify allows us to fulfill all advanced university security specifications while utilizing Supabase for reliable cloud database hosting.

---

## ADR-03: Password Hashing Algorithm (Argon2id)
* **Decision:** Argon2id with unique salt (handled by PHP) + server-side HMAC-SHA256 Pepper.
* **Alternatives Considered:** bcrypt, PBKDF2, SHA-256/SHA-512, standard Argon2i/Argon2d.
* **Justification:**
  - Argon2id won the Password Hashing Competition (PHC).
  - It combines resistance to GPU/ASIC brute-force cracking (memory-hardness from Argon2d) with resistance to side-channel timing attacks (from Argon2i).
  - The server-side Pepper adds defense-in-depth: even if a full SQL dump is stolen, passwords cannot be cracked offline without the secret key residing only in the server environment.

---

## ADR-04: Multi-Factor Authentication (TOTP vs SMS/Email)
* **Decision:** Time-based One-Time Password (TOTP, RFC 6238) with hashed recovery codes.
* **Alternatives Considered:** SMS OTP, Email OTP.
* **Justification:**
  - SMS is vulnerable to SIM-swapping, SS7 attacks, and interception.
  - Email OTP is vulnerable if the user's email account is compromised.
  - TOTP works offline on the user's authenticator app (Google/Microsoft Authenticator) without third-party telecommunication vulnerabilities.
  - Recovery codes are stored as **one-way SHA-256 hashes** rather than plaintext or two-way encrypted strings.

---

## ADR-05: Backup Encryption (Asymmetric age vs. Symmetric AES-256)
* **Decision:** Asymmetric public-key encryption using **`age` (X25519)**.
* **Alternatives Considered:** GPG, Symmetric AES-256-CBC/GCM with OpenSSL.
* **Justification:**
  - Symmetric encryption requires the decryption key to sit on the server running the backup script. If the server is hacked, all backups are immediately decryptable.
  - Asymmetric encryption allows the server to hold only the **Public Key (Recipient)**. The server can only *lock* backups. The **Private Key** is kept offline on a hardware device, making stolen backups completely useless to an attacker.
  - `age` is a modern, audited, simple tool without the legacy complexity and security pitfalls of GPG.

---

## ADR-06: Local Environment (Laravel Herd + PostgreSQL 17 vs XAMPP)
* **Decision:** Laravel Herd + PostgreSQL 17 + Mailpit.
* **Alternatives Considered:** XAMPP, Docker Desktop.
* **Justification:**
  - XAMPP bundles MySQL (incompatible with Supabase PostgreSQL) and runs unnecessary insecure services (FTP, Mercury Mail) by default.
  - Herd provides clean PHP 8.3, Composer, and native local SSL (`https://`) without bloat or service overhead.
  - Mailpit provides a safe, zero-configuration local SMTP catch-all server.
