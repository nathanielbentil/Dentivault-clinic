/**
 * Enterprise Security & DR System - Frontend API Client & State Manager
 */

// Configure Backend API Endpoint
const API_BASE_URL = window.location.origin.includes(':8000') 
  ? `${window.location.origin}/api` 
  : 'http://127.0.0.1:8000/api';

// Authentication State Utilities
const Auth = {
  getToken() {
    return localStorage.getItem('secureauth_token');
  },
  setToken(token) {
    localStorage.setItem('secureauth_token', token);
  },
  getUser() {
    try {
      return JSON.parse(localStorage.getItem('secureauth_user'));
    } catch (e) {
      return null;
    }
  },
  setUser(user) {
    localStorage.setItem('secureauth_user', JSON.stringify(user));
  },
  clear() {
    localStorage.removeItem('secureauth_token');
    localStorage.removeItem('secureauth_user');
    localStorage.removeItem('secureauth_2fa_token');
  },
  isAuthenticated() {
    return !!this.getToken();
  },
  isAdmin() {
    const user = this.getUser();
    return user && (user.is_admin || (user.roles && (user.roles.includes('Admin') || user.roles.includes('SuperAdmin'))));
  }
};

// Unified API Request Wrapper
async function apiRequest(endpoint, options = {}) {
  const url = `${API_BASE_URL}/${endpoint.replace(/^\//, '')}`;
  const headers = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    ...(options.headers || {})
  };

  const token = Auth.getToken();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  try {
    const response = await fetch(url, {
      ...options,
      headers
    });

    const data = await response.json().catch(() => ({}));

    if (response.status === 401 && !options.skipAuthRedirect) {
      Auth.clear();
      if (!window.location.pathname.endsWith('login.html') && !window.location.pathname.endsWith('register.html') && !window.location.pathname.endsWith('index.html')) {
        window.location.href = 'login.html';
      }
    }

    return {
      status: response.status,
      ok: response.ok,
      data
    };
  } catch (error) {
    console.error('API Error:', error);
    showToast('Network error: Unable to connect to backend server at ' + API_BASE_URL, 'error');
    return {
      status: 0,
      ok: false,
      data: { message: 'Network connection failed.' }
    };
  }
}

// Toast Notification Manager
function showToast(message, type = 'info') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.innerHTML = `
    <span style="font-size: 1.1rem;">${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span>
    <span style="flex: 1;">${message}</span>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

// Global UI Initialization (Navbar, User Session, Protected Routes)
document.addEventListener('DOMContentLoaded', () => {
  renderNavbar();
  enforcePageGuards();
});

// Enforce Route Protection
function enforcePageGuards() {
  const path = window.location.pathname;
  const isAuth = Auth.isAuthenticated();

  const protectedPages = ['dashboard.html', 'admin.html', 'backups.html'];
  const adminOnlyPages = ['admin.html', 'backups.html'];

  const currentPage = path.substring(path.lastIndexOf('/') + 1);

  if (protectedPages.includes(currentPage) && !isAuth) {
    window.location.href = 'login.html';
    return;
  }

  if (adminOnlyPages.includes(currentPage) && !Auth.isAdmin()) {
    showToast('Access Denied: Admin privileges required.', 'error');
    setTimeout(() => {
      window.location.href = 'dashboard.html';
    }, 1500);
  }
}

// Render Top Navigation Bar Dynamically
function renderNavbar() {
  const navContainer = document.querySelector('.navbar .nav-container');
  if (!navContainer) return;

  const isAuth = Auth.isAuthenticated();
  const user = Auth.getUser();
  const isAdmin = Auth.isAdmin();
  const currentPath = window.location.pathname;

  let linksHtml = `
    <li><a href="index.html" class="nav-link ${currentPath.endsWith('index.html') || currentPath.endsWith('/') ? 'active' : ''}">Overview</a></li>
  `;

  if (isAuth) {
    linksHtml += `
      <li><a href="dashboard.html" class="nav-link ${currentPath.endsWith('dashboard.html') ? 'active' : ''}">Dashboard</a></li>
    `;

    if (isAdmin) {
      linksHtml += `
        <li><a href="admin.html" class="nav-link ${currentPath.endsWith('admin.html') ? 'active' : ''}">Staff & Roles</a></li>
        <li><a href="backups.html" class="nav-link ${currentPath.endsWith('backups.html') ? 'active' : ''}">3-2-1 Backup Vault</a></li>
      `;
    }

    let roleBadgeClass = 'badge-blue';
    let roleName = 'Dentist';
    if (user && user.roles) {
      if (isAdmin || user.roles.includes('Admin')) {
        roleBadgeClass = 'badge-purple';
        roleName = 'Admin';
      } else if (user.roles.includes('Manager')) {
        roleBadgeClass = 'badge-amber';
        roleName = 'Manager';
      }
    }

    linksHtml += `
      <li style="display: flex; align-items: center; gap: 0.75rem; margin-left: 0.75rem;">
        <span class="badge ${roleBadgeClass}">${roleName}</span>
        <span style="font-size: 0.85rem; color: var(--text-secondary);">${user ? user.email : ''}</span>
        <button onclick="handleLogout()" class="btn btn-secondary btn-sm" style="padding: 0.35rem 0.65rem;">Sign Out</button>
      </li>
    `;
  } else {
    linksHtml += `
      <li><a href="login.html" class="nav-link ${currentPath.endsWith('login.html') ? 'active' : ''}">Staff Sign In</a></li>
      <li><a href="register.html" class="btn btn-primary btn-sm">Register</a></li>
    `;
  }

  navContainer.innerHTML = `
    <a href="index.html" class="brand">
      <div class="brand-icon" style="font-size: 1.25rem;">🦷</div>
      <span>DentiVault <span style="color: var(--accent-cyan); font-weight: 400; font-size: 0.85rem;">Clinical System</span></span>
    </a>
    <ul class="nav-links">
      ${linksHtml}
    </ul>
  `;
}

// Global Sign Out Handler
async function handleLogout() {
  if (Auth.isAuthenticated()) {
    await apiRequest('auth/logout', { method: 'POST', skipAuthRedirect: true });
  }
  Auth.clear();
  showToast('You have been signed out.', 'info');
  setTimeout(() => {
    window.location.href = 'login.html';
  }, 500);
}

// NIST SP 800-63B Password Complexity Meter Helper
function evaluatePasswordStrength(password) {
  const checks = {
    length: password.length >= 12,
    lowercase: /[a-z]/.test(password),
    uppercase: /[A-Z]/.test(password),
    number: /[0-9]/.test(password),
    symbol: /[^A-Za-z0-9]/.test(password),
  };

  let score = 0;
  if (checks.length) score += 2;
  if (checks.lowercase && checks.uppercase) score += 1;
  if (checks.number) score += 1;
  if (checks.symbol) score += 1;

  let label = 'Weak';
  let level = 'weak';

  if (score >= 5) {
    label = 'Strong (NIST SP 800-63B Compliant)';
    level = 'strong';
  } else if (score >= 4) {
    label = 'Good';
    level = 'good';
  } else if (score >= 2) {
    label = 'Fair';
    level = 'fair';
  }

  return { checks, score, label, level };
}
