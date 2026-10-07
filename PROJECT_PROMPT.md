# Secure Authentication, Authorization, Backup & Recovery System — Build Prompt (v2)

## 0. ROLE & CONTEXT

Act as a senior full-stack engineer, application-security engineer, database architect and **patient teacher**.

**About me:**
- I am a **beginner**. This is a **university IT/security project** that I must understand, explain, and defend.
- Machine: **Windows 11 Pro**. Nothing is installed except `winget`. No XAMPP (not needed).
- **Supabase project already created**: name `Secure-Auth`, region **West Europe (London, eu-west-2)**, free/nano compute, status Healthy, no migrations or backups yet. Credentials will be placed in `.env` by me when you tell me exactly which values to copy and from where in the Supabase dashboard.
- I have (or will create) a **GitHub** account. Do **not** use Supabase's GitHub integration; it isn't needed.

**Your job:** build a professional, security-first authentication system that runs locally, integrates with Supabase, and can later be extended with new features **without redesigning authentication**.

**Project location:** create everything from scratch in `C:\Users\NATINO\Desktop\SecureAuthProject`:
```
SecureAuthProject/
├── PROJECT_PROMPT.md   ← save this full prompt here first
├── PROGRESS.md         ← progress tracker (see rule 7)
├── setup/              ← install-tools.ps1, check-tools.ps1, SETUP_NOTES.md
├── backups/            ← local/ and restore-tests/ (OUTSIDE the app, never in Git)
└── app/                ← the Laravel application (Git repository)
    ├── app/Actions/Fortify/        registration, reset, password update
    ├── app/Console/Commands/       user:create-admin, backup:run|list|verify|restore|test-restore
    ├── app/Http/Controllers/(Admin/)  page controllers; admin-only under Admin/
    ├── app/Http/Middleware/        active-account, permission, security-headers guards
    ├── app/Http/Requests/          server-side validation (Form Requests)
    ├── app/Models/                 User, Role, Permission, AuditLog, BackupRun…
    ├── app/Policies/               RBAC + ownership rules
    ├── app/Services/Security/      PepperedHasher, RecoveryCodes
    ├── app/Services/Audit/         AuditLogger
    ├── app/Services/Backup/        dump, encrypt, upload, verify, restore
    ├── config/                     security.php, fortify.php, session.php, backup.php
    ├── database/migrations|seeders/
    ├── resources/views/auth|dashboard|admin|errors/
    ├── routes/web.php
    ├── tests/Unit|Feature|Security/
    ├── docs/                       SECURITY, BACKUP, API, DECISIONS, LEARNING, THREAT_MODEL
    ├── .github/workflows/          CI: tests, PHPStan, composer audit, gitleaks
    ├── public/                     the ONLY web-exposed folder
    ├── README.md, .env (gitignored), .env.example, .gitignore
```
The age backup **private key must never be stored in this folder**.

**Golden rules for working with me:**
1. Work **one phase at a time**. Never generate the whole project at once.
2. Run real commands and tests. **Never claim something works or is secure without evidence.**
3. Never silently ignore errors. Show them, explain them, fix them.
4. **Never ask me to paste secrets into the chat.** Tell me which file and line to put them in.
5. Explain everything in plain English (see Section 11 — Teaching).
6. Wait for my OK at the end of each phase before continuing.
7. **Maintain `PROGRESS.md`** and update it at the end of every phase (and whenever something important changes). It must contain:
   - current phase and status (not started / in progress / done)
   - checklist of all 19 phases with ✅ / 🔄 / ⬜
   - what was completed in each phase + test evidence summary
   - what's next (exact next step)
   - open issues, errors, and remaining risks
   - decisions made and setup values I must remember (**never secrets**)
   - what I (the user) still need to do (e.g. create Supabase project, paste keys into `.env`)

   If a new chat starts, first read `PROJECT_PROMPT.md` and `PROGRESS.md` and continue from where we stopped.
8. Make a **Git commit** at the end of each phase with a clear message (starting once Git is installed and the repo is initialized; never commit `.env`, backups, or keys).

---

## 1. DECIDED TECHNOLOGY STACK (do not change without explaining why)

| Layer | Choice |
|---|---|
| Language | PHP 8.3+ |
| Framework | **Laravel** (latest stable) |
| Auth backend | **Laravel Fortify** (headless) + custom **pepper** hasher |
| RBAC | Laravel **Gates/Policies** + own `roles` / `permissions` tables |
| Frontend | **Blade** templates (auto-escaping) + Tailwind CSS, minimal JS, no inline scripts |
| Database | **PostgreSQL** — Supabase (cloud/main) + local PostgreSQL 17 (dev, tests, restore drills) |
| Sessions | Laravel `database` session driver, server-side |
| Backups | `pg_dump` + **spatie/laravel-backup** (scheduling, retention, monitoring) |
| Backup encryption | **age** (X25519 public-key encryption; private key kept offline) |
| Cloud backup storage | **Supabase Storage** via S3-compatible API (private bucket) |
| Local environment | **Laravel Herd** (PHP, Composer, web server, local HTTPS) |
| Local email | **Mailpit** (captures emails locally) |
| Testing | **Pest/PHPUnit**, **PHPStan/Larastan**, `composer audit`, **gitleaks** |
| Version control | **Git** + private GitHub repo + **GitHub Actions** CI |
| Scheduling | Windows **Task Scheduler** → `php artisan schedule:run` every minute |

**Authentication decision (document in DECISIONS.md):** Supabase Auth is *not* used for login because the requirements (Argon2id, server-side pepper, hashed recovery codes) cannot be met with it. The app implements auth using **Fortify + established libraries**; Supabase provides PostgreSQL + Storage. State the risks of custom auth and how they are mitigated.

---

## 2. NON-NEGOTIABLE SECURITY RULES

Never:
- store plaintext passwords, or log passwords/hashes/secrets/tokens/TOTP secrets/recovery codes/keys
- hard-code secrets, API keys, DB credentials, the pepper, or encryption keys
- store the pepper in the database or alongside backups
- expose the Supabase **service-role key** or any server secret to the frontend
- put **session/auth tokens in URLs** (one-time email links are the only documented exception — see 5.3)
- store auth credentials in `localStorage`
- trust frontend authorization
- concatenate untrusted input into SQL (no raw SQL with interpolation, ever)
- commit `.env` (must be in `.gitignore` from the very first commit)
- invent custom cryptography
- disable a security control to make development easier
- silently generate a new pepper/key at startup (would break existing passwords/data)

The app must **fail safely** (refuse to start) if a required secret is missing or malformed.

---

## 3. USER IDENTITY & DATABASE

Tables (normalized, with PKs, FKs, unique constraints, indexes, timestamps):
`users`, `roles`, `permissions`, `role_user`, `permission_role`, `sessions`, `two_factor_recovery_codes`, `password_reset_tokens`, `email_verification` (or Laravel signed URLs), `audit_logs`, `login_attempts`/rate-limit store, `backup_runs`.

`users` must include: internal UUID primary key (email is **not** the PK), unique case-insensitive email, optional unique username, password hash, `pepper_version`, account status (`active` / `disabled` / `pending`), email-verified timestamp, 2FA secret (encrypted), 2FA confirmed timestamp, failed-login counters / lock-until, `created_at`, `updated_at`, `last_login_at`.

**Supabase database hardening:**
- Supabase auto-exposes the `public` schema via its Data API. Either put app tables in a **non-exposed schema** or **enable RLS on every table with no permissive policies** for `anon`/`authenticated` — and **test** that the anon key cannot read any table.
- Use **least-privilege DB roles**: a migration/owner role, an app runtime role (DML only, no DDL), and a backup role (read-only).
- Use migrations for every schema change. Use Laravel's query builder/Eloquent (parameterized) only.

---

## 4. PASSWORD SECURITY

Flow: `password → HMAC-SHA256(pepper) → Argon2id (unique random salt, handled by PHP's password_hash) → stored hash`

- Use Laravel's Argon2id hasher; wrap it with a **custom hasher** that applies the pepper via `hash_hmac('sha256', $password, $pepper)` before hashing. Do not hand-roll salt generation.
- Tune Argon2id memory/time cost; document the values and why.
- Store `pepper_version` per user. Peppers are loaded as `PASSWORD_PEPPER_V1`, `PASSWORD_PEPPER_V2`… via a **config abstraction** (never `env()` outside config files).
- **Pepper rotation:** explain the implications *before* implementing — old hashes can only be upgraded when the user next logs in (rehash with the new version); users who never log in keep the old version until forced reset; losing a pepper = those passwords are unverifiable.
- Strong password policy (length-first, e.g. min 12, check against breached-password list via `Password::uncompromised()`), password change (requires current password), password reset, rehash-on-login when parameters change.
- Document how the pepper is generated (CSPRNG, e.g. `php -r "echo base64_encode(random_bytes(32));"`), stored, protected, rotated.

---

## 5. AUTHENTICATION & ACCOUNT FEATURES

### 5.1 Login flow
validate input → rate-limit check → find user → verify password (constant-time; also hash on unknown user to equalize timing) → check status & email verification → if 2FA enabled: TOTP/recovery challenge → **regenerate session** → record audit event. Use **generic error messages** to prevent account enumeration (login, registration, password reset).

### 5.2 Features
Registration, email verification, login, logout (server-side session destruction), password change, forgot/reset password, account activation/deactivation (admin), view & revoke own active sessions, **re-authentication (password confirm)** for sensitive actions.

### 5.3 Tokens
Reset/verification tokens: CSPRNG, **stored hashed**, single-use, short expiry, invalidated after use and after password change. The link opens a page that submits the token via **POST**; set `Referrer-Policy: no-referrer`; exclude tokens from logs. Document this as the only token-in-URL exception.

### 5.4 Two-factor authentication (TOTP)
Enrollment with QR code (server-generated SVG) + manual secret, **confirmation code required before activation**, login challenge, **recovery codes**, regeneration, protected disable (password + TOTP), re-auth for all 2FA changes, rate limiting, audit logging.
- TOTP secret **encrypted at rest** (Laravel encryption with `APP_KEY`, or a dedicated key).
- **Recovery codes stored as hashes** (Fortify stores them encrypted by default — override this or explicitly document the deviation), single-use, shown once.
- Prevent TOTP code replay within its time window.

---

## 6. AUTHORIZATION (RBAC)

- Roles: `USER`, `ADMIN` minimum; permission-based design so roles can be added without code redesign.
- USER: dashboard, own profile, own settings, own 2FA, own sessions.
- ADMIN: user management, activate/deactivate, role management, audit log viewer, backup status.
- Every protected route verifies: **authenticated → account active → permission → resource ownership** via reusable middleware/policies.
- Prevent: privilege escalation, IDOR, mass assignment (`$fillable` allowlists; never accept `role`/`status` from user forms), admin removing the last admin.
- Frontend hiding is cosmetic only — test direct API/HTTP bypass attempts.

---

## 7. SESSIONS, CSRF, HEADERS, INPUT, ERRORS

- **Sessions:** HttpOnly, `Secure` (HTTPS), `SameSite=Lax` (or Strict), `__Host-` cookie prefix where possible, idle timeout, absolute lifetime, regeneration on login/privilege change, invalidate other sessions after password change/2FA change, session fixation protection.
- **CSRF:** Laravel CSRF tokens on all state-changing requests.
- **Security headers:** strict CSP (`default-src 'self'; script-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'`), HSTS (HTTPS only), `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cache-Control: no-store` on authenticated pages. Document every CSP exception.
- **Input validation:** server-side Form Requests with allowlists; protect against XSS, SQLi, CSRF, command injection, path traversal, parameter tampering, mass assignment, type confusion. Context-aware output escaping.
- **Errors:** `APP_DEBUG=false` outside local; no stack traces, SQL, paths, or env values shown to users; detailed logs server-side only. Separate local/testing/production configs.
- **HTTPS:** use Herd's local HTTPS. Explain HTTP-localhost vs. HTTPS-production; never claim localhost HTTP = production security.

---

## 8. RATE LIMITING & BRUTE FORCE

Limits per **IP**, per **account**, and per **IP+account** on: login, 2FA, password reset, registration, email verification resend, sensitive actions. Progressive delays + temporary throttling (exponential backoff). **No permanent lockouts** (DoS risk). Log and flag suspicious patterns (e.g. many accounts from one IP, credential stuffing).

---

## 9. AUDIT LOGGING

Log: registration, login success/failure, logout, password change/reset, 2FA enable/disable, recovery code used, role change, account enable/disable, rate-limit triggers, suspicious activity, backup success/failure, restore operations.
Fields: id, user_id (nullable), event_type, success, IP, user agent, timestamp, safe JSON metadata. **Never** log secrets/tokens/passwords/codes/keys. Audit logs are append-only for the app role (no UPDATE/DELETE grants). Admin UI with filtering (parameterized).

---

## 10. BACKUP & DISASTER RECOVERY

### 10.1 Nightly automatic backup (configurable schedule)
1. `pg_dump` the Supabase database (custom format, `pg_dump` version ≥ server version)
2. Encrypt with **age** using the **public key only** (private key never on the server)
3. SHA-256 checksum + metadata manifest
4. Save locally (outside web root) → copy #2
5. Upload to private **Supabase Storage** bucket (server-side S3 credentials) → copy #3
6. Verify: local file exists, size sane, checksum matches, remote object exists and checksum matches
7. Retention: e.g. 7 daily / 4 weekly / 6 monthly
8. Record in `backup_runs` + audit log; email/dashboard alert on failure or if overdue
9. Retry on network failure; keep local copy and retry upload later if only the upload fails

**3-2-1:** live DB + local encrypted copy + cloud encrypted copy.

### 10.2 Weekly automatic restore test
Download/locate latest backup → verify checksum → decrypt → restore into a **local scratch PostgreSQL database** → verify table counts, users, roles, audit logs, and that a known test user can authenticate → record result.

### 10.3 Keys
age private key stored **offline** (password manager + USB). Never in Git, never with backups, never on the server. Document clearly: **lost key = backups permanently unrecoverable.**

### 10.4 Restore runbook (`php artisan backup:restore` + BACKUP.md)
Obtain latest valid backup → verify → decrypt → restore → verify DB → reconnect app → verify login, 2FA, users/roles, audit logs, app functionality. **Perform a real restore test before declaring completion.**

### 10.5 Commands
`backup:run`, `backup:list`, `backup:verify`, `backup:restore`, `backup:test-restore`.

---

## 11. TEACHING REQUIREMENTS (very important)

After **every phase**, provide:
1. **What** was built — plain English, no unexplained jargon
2. **Why** it was built this way, and alternatives considered
3. **What attack it stops**, with a real-world example
4. **What would go wrong** if it was skipped
5. A simple **diagram** where helpful
6. A **live attack demo** where relevant (e.g. SQLi on login fails, USER blocked from admin, brute force throttled, backup unreadable without key)
7. A short **"try it yourself"** exercise for me
8. Test results as evidence

Maintain throughout:
- **DECISIONS.md** — every major choice: decision, alternatives, reasoning
- **LEARNING.md** — beginner tour of the whole system (password typed → logged in → backed up)

At the end, provide **presentation prep**: full system walkthrough, **likely lecturer questions with answers** (e.g. salt vs pepper, what if DB is stolen, why not Supabase Auth, why Argon2id), and a slide-ready summary.

If I say "explain simpler" or "why?", stop and explain before continuing.

---

## 12. ENVIRONMENT & SECRETS

- `.env` (real, gitignored) and `.env.example` (placeholders only).
- Only variables actually needed, e.g.: `APP_ENV`, `APP_KEY`, `APP_URL`, `APP_DEBUG`, `DB_*` (Supabase), `DB_TEST_*` / local DB, `PASSWORD_PEPPER_V1`, `PASSWORD_PEPPER_CURRENT_VERSION`, `SUPABASE_URL`, `SUPABASE_S3_ENDPOINT`, `SUPABASE_S3_ACCESS_KEY_ID`, `SUPABASE_S3_SECRET_ACCESS_KEY`, `SUPABASE_BACKUP_BUCKET`, `BACKUP_AGE_RECIPIENT` (public key), `MAIL_*`.
- Explain which are secret, which are public, and why the service-role key is not needed by the app (or is server-only if it is).
- Document production secret management (platform env vars / secret manager).

---

## 13. TESTING (automated, must pass)

- **Auth:** valid login, wrong password, unknown account, disabled account, unverified account, 2FA success/failure, recovery code (and reuse rejected), logout.
- **Authorization:** USER→USER resource, USER→ADMIN resource (403), ADMIN→ADMIN, guest→protected (redirect/401), IDOR on another user's resource.
- **Security:** SQL injection payloads on every input, XSS payloads stored/reflected, CSRF missing/invalid token, brute force & rate-limit bypass attempts (e.g. header spoofing), session fixation, session expiry, token reuse, expired tokens, reset abuse, privilege escalation, mass assignment/parameter tampering, security headers present, no secrets in responses, RLS/anon-key denial against Supabase.
- **Password:** hash is Argon2id, unique salts (same password → different hashes), pepper applied (hash fails to verify without pepper), pepper not in DB.
- **Backup:** creation, encryption (file not readable as SQL), upload, checksum verification, restore, restore verification.
- Static analysis (PHPStan), `composer audit`, and CI running all of it.

---

## 14. PHASES (each ends with: run tests → review → fix → explain → update PROGRESS.md → Git commit → my OK)

1. Architecture + threat model draft + DECISIONS.md
2. Environment setup (Herd, PostgreSQL, Git, age, gitleaks, Mailpit) — explain each tool
3. Project init (Laravel, Git, `.gitignore`, `.env.example`, config abstraction, fail-safe secret checks)
4. Database (migrations, roles, Supabase hardening, RLS test)
5. Authentication (Fortify: register, verify, login, logout, reset)
6. Password security (Argon2id + pepper + versioning)
7. 2FA (TOTP, QR, hashed recovery codes)
8. RBAC (roles, permissions, policies, middleware)
9. Session security, CSRF, headers, rate limiting
10. Audit logging
11. Local backups + encryption + verification
12. Cloud backups (Supabase Storage)
13. Restore + disaster recovery + real restore test
14. Frontend (all pages: register, login, 2FA challenge, forgot/reset, verify email, dashboard, profile, 2FA settings, recovery codes, sessions, admin users, audit log, backup status, access denied)
15. Security hardening
16. Automated tests
17. Security tests
18. Final independent security audit (OWASP Top 10 + ASVS-style auth checks; classify CRITICAL/HIGH/MEDIUM/LOW; fix all CRITICAL/HIGH) + secret-leak scan (code, Git history, built assets, docs, logs, backups)
19. Documentation + presentation prep

---

## 15. THREAT MODEL

For each threat: **Threat → Impact → Existing defense → Additional mitigation → Residual risk.**
Cover: password guessing, credential stuffing, stolen credentials, SQL injection, frontend authorization bypass, session theft, password-reset abuse, 2FA brute force, leaked backups, stolen `.env`, stolen cloud credentials, request tampering, privilege escalation, Supabase Data API exposure, insider/admin misuse.

---

## 16. DOCUMENTATION

`README.md` (overview, architecture, stack, install, env vars, Supabase setup, migrations, run locally, create first admin via **CLI only** — `php artisan user:create-admin`, auth, 2FA, RBAC, backups, restore, testing, security model, deployment, troubleshooting), `SECURITY.md`, `BACKUP.md`, `API.md`, `DECISIONS.md`, `LEARNING.md`, `.env.example`. Include dependency commands: install, update, audit, remove.

---

## 17. DEFINITION OF DONE

Not complete until **all** are proven by tests/evidence: registration, email verification, login, Argon2id, unique salts, pepper (not in DB, from env), 2FA, recovery codes, RBAC (backend-enforced), secure sessions, logout, password reset, rate limiting, parameterized SQL, SQLi tests passing, audit logging, Supabase integration (with RLS/anon denial verified), secrets protected (gitleaks clean incl. history), local + cloud backups, encryption, verification, **real restore performed**, all automated & security tests passing, no unresolved CRITICAL/HIGH findings, documentation complete.

---

## 18. FINAL REPORT

Sections: What was built · Architecture · Technology stack · Database design · Authentication flow · 2FA flow · RBAC model · Password security (Argon2id, salt, pepper) · Session security · SQL injection protection · Supabase integration · Local backups · Cloud backups · Backup encryption · Restore/DR · Security test results · Automated test results · Known limitations · Remaining risks · How to run locally · How to back up · How to restore · How to deploy safely · Presentation prep.

**Do not prioritize speed over security. Do not declare completion without evidence.**
