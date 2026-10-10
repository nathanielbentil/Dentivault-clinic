/**
 * DentiVault WebAuthn & FIDO2 Biometric Passkey Engine
 * Supports Synaptics / Windows Hello (Fingerprint / Face), Touch ID, Face ID & Hardware Tokens
 */

window.DentiVaultBiometrics = {
    /**
     * Ensure environment is WebAuthn compliant (redirect 127.0.0.1 -> localhost for WebAuthn standard)
     */
    ensureValidDomain() {
        if (window.location.hostname === '127.0.0.1') {
            const newUrl = window.location.href.replace('127.0.0.1', 'localhost');
            window.location.href = newUrl;
            return false;
        }
        return true;
    },

    /**
     * Safely Convert Base64/Base64URL/UTF-8 string to ArrayBuffer without throwing atob errors
     */
    base64urlToBuffer(str) {
        if (!str) return new ArrayBuffer(0);
        if (str instanceof ArrayBuffer) return str;
        if (str instanceof Uint8Array) return str.buffer;
        
        if (typeof str !== 'string') {
            try {
                str = String(str);
            } catch (e) {
                return new ArrayBuffer(0);
            }
        }

        try {
            let base64 = str.replace(/-/g, '+').replace(/_/g, '/').replace(/\s/g, '');
            while (base64.length % 4) {
                base64 += '=';
            }
            const binary = atob(base64);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            return bytes.buffer;
        } catch (e) {
            return new TextEncoder().encode(str).buffer;
        }
    },

    /**
     * Convert ArrayBuffer to Base64URL string
     */
    bufferToBase64url(buffer) {
        if (!buffer) return '';
        if (typeof buffer === 'string') return buffer;
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    },

    /**
     * Check if WebAuthn platform biometrics are supported
     */
    async isSupported() {
        if (window.PublicKeyCredential && 
            typeof window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable === 'function') {
            try {
                return await window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            } catch (e) {
                return false;
            }
        }
        return false;
    },

    /**
     * Inject or get the Interactive Biometric Scanner Modal UI
     */
    getScannerModal() {
        let modal = document.getElementById('dentivault-biometric-scanner-modal');
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'dentivault-biometric-scanner-modal';
        modal.style.position = 'fixed';
        modal.style.inset = '0';
        modal.style.zIndex = '999999';
        modal.style.display = 'none';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.backgroundColor = 'rgba(2, 6, 23, 0.85)';
        modal.style.backdropFilter = 'blur(12px)';
        modal.style.padding = '1rem';
        modal.style.fontFamily = 'ui-sans-serif, system-ui, -apple-system, sans-serif';

        modal.innerHTML = `
            <style>
                @keyframes dvLaserScan {
                    0% { top: 12%; opacity: 0; }
                    20% { opacity: 1; }
                    80% { opacity: 1; }
                    100% { top: 84%; opacity: 0; }
                }
                @keyframes dvRadarPulse {
                    0% { transform: scale(0.9); opacity: 0.8; }
                    50% { transform: scale(1.18); opacity: 0.2; }
                    100% { transform: scale(0.9); opacity: 0.8; }
                }
                @keyframes dvPopIn {
                    0% { transform: scale(0.85); opacity: 0; }
                    100% { transform: scale(1); opacity: 1; }
                }
                @keyframes dvShake {
                    0%, 100% { transform: translateX(0); }
                    20%, 60% { transform: translateX(-6px); }
                    40%, 80% { transform: translateX(6px); }
                }
                .dv-modal-card {
                    animation: dvPopIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                }
                .dv-scan-laser {
                    animation: dvLaserScan 1.8s ease-in-out infinite alternate;
                }
                .dv-radar {
                    animation: dvRadarPulse 2.2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                }
                .dv-shake-error {
                    animation: dvShake 0.4s ease-in-out;
                }
            </style>
            <div class="dv-modal-card" style="background: #0f172a; border: 1.5px solid #06b6d4; border-radius: 1.5rem; max-width: 440px; width: 100%; padding: 2rem; text-align: center; box-shadow: 0 25px 50px -12px rgba(6, 182, 212, 0.25); color: #f8fafc; position: relative;">
                
                <!-- Close Button -->
                <button id="dv-bio-close-btn" style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; color: #94a3b8; font-size: 1.35rem; cursor: pointer; line-height: 1; padding: 0.25rem 0.5rem; border-radius: 0.5rem;">&times;</button>

                <!-- Scanner Visual Container -->
                <div style="position: relative; width: 120px; height: 120px; margin: 0 auto 1.5rem auto; display: flex; align-items: center; justify-content: center;">
                    <!-- Outer Radar Rings -->
                    <div id="dv-bio-radar" class="dv-radar" style="position: absolute; inset: -12px; border-radius: 9999px; border: 2px dashed rgba(6, 182, 212, 0.4);"></div>
                    <div id="dv-bio-ring" style="position: absolute; inset: 0; border-radius: 9999px; border: 2.5px solid #06b6d4; background: rgba(6, 182, 212, 0.08); transition: all 0.3s ease;"></div>
                    
                    <!-- Fingerprint SVG Icon -->
                    <div id="dv-bio-icon-box" style="position: relative; z-index: 10; font-size: 3.5rem; transition: transform 0.3s ease;">
                        <svg id="dv-bio-svg-fingerprint" width="68" height="68" viewBox="0 0 24 24" fill="none" stroke="#22d3ee" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/>
                            <path d="M14 13.12c0 2.38 0 6.38-1 8.88"/>
                            <path d="M2 16h.01"/>
                            <path d="M21.8 16c.2-2 .131-5.354 0-6"/>
                            <path d="M5 19.5C5.5 18 6 15 6 12a6 6 0 0 1 .34-2"/>
                            <path d="M8.65 22c.21-.66.45-1.32.57-2"/>
                            <path d="M9 6.8a6 6 0 0 1 9 5.2v2"/>
                            <path d="M12 2a10 10 0 0 0-6.88 17.2"/>
                            <path d="M17.5 19.8A10.05 10.05 0 0 0 22 12.02"/>
                        </svg>
                        
                        <!-- Success Checkmark SVG (hidden initially) -->
                        <svg id="dv-bio-svg-success" style="display: none;" width="68" height="68" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>

                        <!-- Error X SVG (hidden initially) -->
                        <svg id="dv-bio-svg-error" style="display: none;" width="68" height="68" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </div>

                    <!-- Animated Cyan Laser Beam -->
                    <div id="dv-bio-laser" class="dv-scan-laser" style="position: absolute; left: 15%; right: 15%; height: 3px; background: #38bdf8; box-shadow: 0 0 12px 3px #38bdf8; border-radius: 9999px; z-index: 20;"></div>
                </div>

                <!-- Text Status -->
                <h3 id="dv-bio-title" style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem; letter-spacing: -0.02em;">
                    Touch Your Fingerprint Sensor
                </h3>
                <p id="dv-bio-desc" style="font-size: 0.85rem; color: #94a3b8; line-height: 1.5; margin-bottom: 1.5rem;">
                    Place your enrolled finger on your PC's Synaptics fingerprint sensor or verify with Windows Hello to continue.
                </p>

                <!-- Status Feedback Badge -->
                <div id="dv-bio-status-badge" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.45rem 1rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; background: rgba(6, 182, 212, 0.15); color: #22d3ee; border: 1px solid rgba(6, 182, 212, 0.35); margin-bottom: 1.25rem;">
                    <span id="dv-bio-status-dot" style="width: 8px; height: 8px; border-radius: 9999px; background: #22d3ee; box-shadow: 0 0 8px #22d3ee;"></span>
                    <span id="dv-bio-status-text">Scanning Synaptics Sensor...</span>
                </div>

                <!-- Action Buttons (Try Again / Password Fallback) -->
                <div id="dv-bio-actions" style="display: flex; gap: 0.75rem; justify-content: center; margin-top: 0.5rem;">
                    <button id="dv-bio-retry-btn" style="display: none; flex: 1; padding: 0.7rem 1.25rem; border-radius: 0.75rem; background: linear-gradient(135deg, #06b6d4, #0d9488); border: none; color: #020617; font-weight: 800; font-size: 0.85rem; cursor: pointer; transition: transform 0.15s ease;">
                        🔄 Try Scan Again
                    </button>
                    <button id="dv-bio-fallback-btn" style="flex: 1; padding: 0.7rem 1.25rem; border-radius: 0.75rem; background: #1e293b; border: 1px solid #334155; color: #cbd5e1; font-weight: 700; font-size: 0.85rem; cursor: pointer;">
                        🔑 Use Password
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        return modal;
    },

    /**
     * Show scanner modal in specific state ('scanning', 'success', 'error')
     */
    updateScannerState(state, message = '') {
        const modal = this.getScannerModal();
        const card = modal.querySelector('.dv-modal-card');
        const ring = document.getElementById('dv-bio-ring');
        const radar = document.getElementById('dv-bio-radar');
        const laser = document.getElementById('dv-bio-laser');
        const svgFingerprint = document.getElementById('dv-bio-svg-fingerprint');
        const svgSuccess = document.getElementById('dv-bio-svg-success');
        const svgError = document.getElementById('dv-bio-svg-error');
        const title = document.getElementById('dv-bio-title');
        const desc = document.getElementById('dv-bio-desc');
        const badge = document.getElementById('dv-bio-status-badge');
        const badgeDot = document.getElementById('dv-bio-status-dot');
        const badgeText = document.getElementById('dv-bio-status-text');
        const retryBtn = document.getElementById('dv-bio-retry-btn');
        const fallbackBtn = document.getElementById('dv-bio-fallback-btn');

        card.classList.remove('dv-shake-error');

        if (state === 'scanning') {
            modal.style.display = 'flex';
            card.style.borderColor = '#06b6d4';
            card.style.boxShadow = '0 25px 50px -12px rgba(6, 182, 212, 0.25)';
            ring.style.borderColor = '#06b6d4';
            ring.style.background = 'rgba(6, 182, 212, 0.08)';
            radar.style.display = 'block';
            laser.style.display = 'block';
            svgFingerprint.style.display = 'block';
            svgSuccess.style.display = 'none';
            svgError.style.display = 'none';

            title.innerText = 'Touch Your Fingerprint Sensor';
            title.style.color = '#ffffff';
            desc.innerText = 'Place your enrolled finger on your PC\'s Synaptics fingerprint sensor or verify with Windows Hello.';
            badge.style.background = 'rgba(6, 182, 212, 0.15)';
            badge.style.borderColor = 'rgba(6, 182, 212, 0.35)';
            badge.style.color = '#22d3ee';
            badgeDot.style.background = '#22d3ee';
            badgeDot.style.boxShadow = '0 0 8px #22d3ee';
            badgeText.innerText = 'Scanning hardware sensor...';
            retryBtn.style.display = 'none';
            fallbackBtn.innerText = '🔑 Use Password';
        } 
        else if (state === 'success') {
            modal.style.display = 'flex';
            card.style.borderColor = '#10b981';
            card.style.boxShadow = '0 25px 50px -12px rgba(16, 185, 129, 0.35)';
            ring.style.borderColor = '#10b981';
            ring.style.background = 'rgba(16, 185, 129, 0.15)';
            radar.style.display = 'none';
            laser.style.display = 'none';
            svgFingerprint.style.display = 'none';
            svgSuccess.style.display = 'block';
            svgError.style.display = 'none';

            title.innerText = '✅ Fingerprint Verified!';
            title.style.color = '#34d399';
            desc.innerText = message || 'Biometric authentication confirmed. Granting clinical access...';
            badge.style.background = 'rgba(16, 185, 129, 0.2)';
            badge.style.borderColor = 'rgba(16, 185, 129, 0.5)';
            badge.style.color = '#34d399';
            badgeDot.style.background = '#10b981';
            badgeDot.style.boxShadow = '0 0 8px #10b981';
            badgeText.innerText = '✓ Verified Successfully';
            retryBtn.style.display = 'none';
            fallbackBtn.style.display = 'none';
        } 
        else if (state === 'error') {
            modal.style.display = 'flex';
            card.classList.add('dv-shake-error');
            card.style.borderColor = '#ef4444';
            card.style.boxShadow = '0 25px 50px -12px rgba(239, 68, 68, 0.35)';
            ring.style.borderColor = '#ef4444';
            ring.style.background = 'rgba(239, 68, 68, 0.15)';
            radar.style.display = 'none';
            laser.style.display = 'none';
            svgFingerprint.style.display = 'none';
            svgSuccess.style.display = 'none';
            svgError.style.display = 'block';

            title.innerText = '❌ Scan Failed / Not Recognized';
            title.style.color = '#f87171';
            desc.innerText = message || 'Sensor touch was not recognized or scan was cancelled. Please try again.';
            badge.style.background = 'rgba(239, 68, 68, 0.2)';
            badge.style.borderColor = 'rgba(239, 68, 68, 0.5)';
            badge.style.color = '#f87171';
            badgeDot.style.background = '#ef4444';
            badgeDot.style.boxShadow = '0 0 8px #ef4444';
            badgeText.innerText = 'Verification Failed';
            retryBtn.style.display = 'block';
            fallbackBtn.style.display = 'block';
            fallbackBtn.innerText = 'Cancel / Use Password';
        }
    },

    /**
     * Close the Biometric Scanner Modal
     */
    closeScannerModal() {
        const modal = this.getScannerModal();
        modal.style.display = 'none';
    },

    /**
     * Register a new Biometric Fingerprint / Passkey with interactive modal
     */
    async register(deviceName = 'Windows Hello Touch Biometrics') {
        if (!this.ensureValidDomain()) return;

        this.updateScannerState('scanning');

        const modal = this.getScannerModal();
        const closeBtn = document.getElementById('dv-bio-close-btn');
        const fallbackBtn = document.getElementById('dv-bio-fallback-btn');
        const retryBtn = document.getElementById('dv-bio-retry-btn');

        closeBtn.onclick = () => this.closeScannerModal();
        fallbackBtn.onclick = () => this.closeScannerModal();
        retryBtn.onclick = () => this.register(deviceName);

        try {
            // 1. Request registration options from server
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/biometric/register-challenge', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                }
            });

            if (!res.ok) {
                const errData = await res.json().catch(() => ({}));
                throw new Error(errData.error || 'Failed to initialize biometric challenge from server.');
            }

            const options = await res.json();

            // 2. Prepare WebAuthn PublicKeyCreationOptions
            const rpId = (options.rp && options.rp.id && options.rp.id !== '127.0.0.1') ? options.rp.id : 'localhost';

            const publicKey = {
                challenge: this.base64urlToBuffer(options.challenge),
                rp: {
                    name: options.rp.name || 'DentiVault Dental Practice',
                    id: rpId,
                },
                user: {
                    ...options.user,
                    id: this.base64urlToBuffer(options.user.id),
                },
                pubKeyCredParams: options.pubKeyCredParams,
                authenticatorSelection: options.authenticatorSelection || {
                    authenticatorAttachment: 'platform',
                    userVerification: 'preferred',
                    residentKey: 'preferred',
                },
                timeout: options.timeout || 60000,
                attestation: options.attestation || 'none',
                excludeCredentials: (options.excludeCredentials || []).map(c => ({
                    type: c.type || 'public-key',
                    id: this.base64urlToBuffer(c.id),
                    transports: c.transports || ['internal'],
                })),
            };

            // 3. Prompt user's hardware biometric sensor (Windows Hello / Touch ID)
            let credential;
            try {
                credential = await navigator.credentials.create({ publicKey });
            } catch (err) {
                console.warn('Native WebAuthn prompt cancelled or unavailable:', err);
                if (err.name === 'NotAllowedError') {
                    throw new Error('Biometric scan was cancelled or timed out.');
                }
                if (err.name === 'NotSupportedError' || err.name === 'InvalidStateError' || !window.PublicKeyCredential) {
                    throw new Error('Biometric sensor is not configured or available on this device.');
                }
                throw err;
            }

            if (!credential) {
                throw new Error('No biometric credential received from sensor.');
            }

            // 4. Encode response payload
            const credentialData = {
                id: credential.id,
                rawId: this.bufferToBase64url(credential.rawId),
                type: credential.type,
                name: deviceName,
                response: {
                    clientDataJSON: this.bufferToBase64url(credential.response.clientDataJSON),
                    attestationObject: this.bufferToBase64url(credential.response.attestationObject),
                },
                transports: credential.response.getTransports ? credential.response.getTransports() : ['internal'],
            };

            // 5. Send to server for verification and storage
            const verifyRes = await fetch('/biometric/register-verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(credentialData),
            });

            const result = await verifyRes.json();
            if (!verifyRes.ok) {
                throw new Error(result.error || 'Server rejected biometric verification.');
            }

            // 6. Display Success State
            this.updateScannerState('success', 'Your Synaptics fingerprint sensor has been linked to your account!');
            setTimeout(() => {
                this.closeScannerModal();
                window.location.reload();
            }, 1200);

            return result;
        } catch (err) {
            console.error('Biometric Registration Error:', err);
            this.updateScannerState('error', err.message || 'Fingerprint registration failed. Click Try Again.');
            throw err;
        }
    },

    /**
     * Authenticate via 1-Touch Biometric Fingerprint / Passkey with interactive modal
     */
    async login(email = null) {
        if (!this.ensureValidDomain()) return;

        this.updateScannerState('scanning');

        const modal = this.getScannerModal();
        const closeBtn = document.getElementById('dv-bio-close-btn');
        const fallbackBtn = document.getElementById('dv-bio-fallback-btn');
        const retryBtn = document.getElementById('dv-bio-retry-btn');

        closeBtn.onclick = () => this.closeScannerModal();
        fallbackBtn.onclick = () => this.closeScannerModal();
        retryBtn.onclick = () => this.login(email);

        try {
            // 1. Fetch authentication challenge
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const url = email ? `/biometric/login-challenge?email=${encodeURIComponent(email)}` : '/biometric/login-challenge';
            
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!res.ok) {
                const errData = await res.json().catch(() => ({}));
                throw new Error(errData.error || 'Failed to fetch biometric login challenge.');
            }

            const options = await res.json();

            // Check if any credentials exist
            if (!options.allowCredentials || options.allowCredentials.length === 0) {
                throw new Error('No fingerprint passkey has been linked yet! Please sign in with your password first, then open Profile Settings to link your fingerprint sensor.');
            }

            // 2. Prepare WebAuthn PublicKeyRequestOptions
            const allowList = (options.allowCredentials || []).map(c => ({
                type: c.type || 'public-key',
                id: this.base64urlToBuffer(c.id),
                transports: c.transports || ['internal'],
            }));

            const rpId = (options.rpId && options.rpId !== '127.0.0.1') ? options.rpId : 'localhost';

            const publicKey = {
                challenge: this.base64urlToBuffer(options.challenge),
                rpId: rpId,
                timeout: options.timeout || 60000,
                userVerification: options.userVerification || 'preferred',
            };

            if (allowList.length > 0) {
                publicKey.allowCredentials = allowList;
            }

            // 3. Prompt user's biometric hardware scanner
            let assertion;
            try {
                assertion = await navigator.credentials.get({ publicKey });
            } catch (err) {
                console.warn('Biometric login prompt error:', err);
                if (err.name === 'NotAllowedError') {
                    throw new Error('Fingerprint scan was cancelled or timed out.');
                }
                if (err.name === 'NotSupportedError' || !window.PublicKeyCredential) {
                    throw new Error('Windows Hello / Biometric sensor is not supported on this browser.');
                }
                throw new Error(err.message || 'Biometric authentication was cancelled.');
            }

            if (!assertion) {
                throw new Error('Biometric authentication cancelled or failed.');
            }

            // 4. Encode assertion payload
            const payload = {
                id: assertion.id,
                rawId: this.bufferToBase64url(assertion.rawId),
                type: assertion.type,
                response: {
                    clientDataJSON: this.bufferToBase64url(assertion.response.clientDataJSON),
                    authenticatorData: this.bufferToBase64url(assertion.response.authenticatorData),
                    signature: this.bufferToBase64url(assertion.response.signature),
                    userHandle: assertion.response.userHandle ? this.bufferToBase64url(assertion.response.userHandle) : null,
                },
            };

            // 5. Send to server for login verification
            const verifyRes = await fetch('/biometric/login-verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const result = await verifyRes.json();
            if (!verifyRes.ok) {
                throw new Error(result.error || 'Biometric authentication verification failed.');
            }

            // 6. Display Success State
            this.updateScannerState('success', `Welcome back, ${result.user?.name || 'Staff'}! Redirecting to Clinical Portal...`);
            setTimeout(() => {
                this.closeScannerModal();
                window.location.href = result.redirect || '/dashboard';
            }, 1000);

            return result;
        } catch (err) {
            console.error('Biometric Login Error:', err);
            this.updateScannerState('error', err.message || 'Fingerprint scan failed. Click Try Again.');
            throw err;
        }
    }
};
