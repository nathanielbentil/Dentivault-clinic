# Threat Model & Risk Analysis

## 1. Overview
This threat model applies the **STRIDE** methodology (Spoofing, Tampering, Repudiation, Information Disclosure, Denial of Service, Elevation of Privilege) to identify potential attack vectors and specify defensive controls.

---

## 2. Threat Analysis Matrix

| ID | Threat Vector | STRIDE Category | Potential Impact | Existing & Designed Defenses | Residual Risk & Mitigation |
|---|---|---|---|---|---|
| **T-01** | **Password Guessing / Brute Force** | Spoofing | Account Takeover | Rate limiting per IP, per Account, progressive delays (exponential backoff), Argon2id memory-hard hashing. | Slow distributed botnet attacks mitigated by mandatory 2FA enrollment for sensitive roles. |
| **T-02** | **Credential Stuffing (Leaked Passwords)** | Spoofing | Account Takeover | Check passwords against HaveIBeenPwned database (`Password::uncompromised()`), unique salts + HMAC pepper. | User reused password on other breached sites; mitigated by 2FA. |
| **T-03** | **Database Theft / SQL Dump Leak** | Information Disclosure | Mass Credential Exposure | Argon2id + individual salts + server-side Pepper (stored in `.env`, outside DB). Hashed 2FA recovery codes. | Attacker cannot crack hashes without the pepper key. |
| **T-04** | **SQL Injection (SQLi)** | Tampering / Info Disclosure | Complete DB Compromise | 100% Parameterized queries via PDO / Eloquent query builder. Zero string concatenation. | Automated security test suite runs raw SQL injection payloads across all endpoints to verify immunity. |
| **T-05** | **Session Hijacking / Fixation** | Spoofing | Impersonating User | `HttpOnly`, `Secure`, `SameSite=Lax` cookies, automatic session ID regeneration upon login, absolute session timeout. | Physical theft of unlocked device mitigated by idle timeout and password re-confirmation for sensitive operations. |
| **T-06** | **Cross-Site Request Forgery (CSRF)** | Tampering | Unauthorized Actions | Cryptographic CSRF tokens on all POST/PUT/DELETE requests, SameSite cookie attributes. | Zero third-party script inclusion prevents script-based token harvesting. |
| **T-07** | **Cross-Site Scripting (XSS)** | Tampering / Info Disclosure | Session / Data Theft | Blade template auto-escaping `{{ }}`, strict Content Security Policy (`default-src 'self'`), no inline scripts. | Third-party UI packages audited via `composer audit` and strict CSP. |
| **T-08** | **Privilege Escalation** | Elevation of Privilege | Regular user becomes Admin | Server-side Gates & Policies, Eloquent `$fillable` protection prevents mass-assignment of `role` fields. | Automated tests attempt direct API role tampering to ensure rejection. |
| **T-09** | **Insecure Direct Object Reference (IDOR)** | Info Disclosure / Tampering | Accessing other users' data | Policies verify resource ownership (`$user->id === $resource->user_id`) on every backend controller method. | Unit & Feature tests verify User A cannot read or edit User B's resources. |
| **T-10** | **2FA TOTP Brute Force** | Spoofing | 2FA Bypass | Strict rate limiting on TOTP verification (5 attempts then cooldown), single-use replay prevention window. | Cooldown period prevents automated 6-digit guessing. |
| **T-11** | **Stolen Cloud Backup** | Information Disclosure | Full System Data Leak | Asymmetric `age` encryption (X25519) before upload. Private key is never stored on the server or in the cloud. | Without the offline private key, ciphertext is mathematically unbreakable. |
| **T-12** | **Accidental Secret Commit to Git** | Information Disclosure | Leaked API/Pepper Keys | `.gitignore` includes `.env`, `.env.backup`, `*.dump`, `*.key`. Gitleaks scanner runs pre-commit and in CI. | Secrets are never hard-coded in source files or documentation templates. |
| **T-13** | **Supabase Data API Exposure** | Info Disclosure / Tampering | Unauthenticated DB Access | Supabase `anon` public key access blocked via restrictive Row Level Security (RLS) on all application tables. | Automated test queries Supabase REST endpoint directly to verify 401/403 rejection. |
| **T-14** | **Denial of Service (DoS) via Account Lockout** | Denial of Service | Legitimate User Locked Out | No permanent account lockouts; uses exponential progressive throttling instead. | Legitimate users can always authenticate after a short cooldown or complete email reset. |
