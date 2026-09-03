const DISCORDPLUS_GATE_VERSION = '2';
const DISCORD_SVG =
  '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M20.317 4.37a19.79 19.79 0 0 0-4.885-1.515.07.07 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.548-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>';

function discordplusWaitForApi() {
  return new Promise((resolve) => {
    if (window.FeatherPanel && window.FeatherPanel.api) {
      resolve(window.FeatherPanel.api);
      return;
    }
    const check = setInterval(() => {
      if (window.FeatherPanel && window.FeatherPanel.api) {
        clearInterval(check);
        resolve(window.FeatherPanel.api);
      }
    }, 100);
    setTimeout(() => {
      clearInterval(check);
      resolve(null);
    }, 8000);
  });
}

function discordplusIsExemptPath() {
  const path = window.location.pathname || '';
  return (
    path.startsWith('/admin') ||
    path.startsWith('/dashboard/account') ||
    path.startsWith('/account') ||
    path.startsWith('/auth') ||
    path.startsWith('/login') ||
    path.startsWith('/register')
  );
}

function discordplusEnsureOverlay() {
  let overlay = document.getElementById('discordplus-gate-overlay');
  if (overlay && overlay.getAttribute('data-version') !== DISCORDPLUS_GATE_VERSION) {
    overlay.remove();
    overlay = null;
  }
  if (overlay) {
    return overlay;
  }

  overlay = document.createElement('div');
  overlay.id = 'discordplus-gate-overlay';
  overlay.hidden = true;
  overlay.setAttribute('data-version', DISCORDPLUS_GATE_VERSION);
  overlay.setAttribute('role', 'presentation');
  overlay.innerHTML = `
    <div class="discordplus-gate-panel" role="dialog" aria-modal="true" aria-labelledby="discordplus-gate-title">
      <h2 id="discordplus-gate-title">${DISCORD_SVG}<span>Link Discord to continue</span></h2>
      <p id="discordplus-gate-message">Link your Discord account before using servers, webspaces, or VMs.</p>
      <div class="discordplus-gate-actions">
        <a class="discordplus-gate-primary" id="discordplus-gate-link" href="/api/user/auth/discord/link">
          ${DISCORD_SVG}
          <span>Link Discord</span>
        </a>
        <a class="discordplus-gate-secondary" id="discordplus-gate-account" href="/dashboard/account?tab=settings">
          Account settings
        </a>
      </div>
    </div>
  `;
  document.body.appendChild(overlay);
  return overlay;
}

async function discordplusFetchStatus() {
  try {
    const response = await fetch('/api/user/discordplus/status', {
      credentials: 'include',
      headers: { Accept: 'application/json' },
    });
    if (!response.ok) return null;
    const payload = await response.json();
    if (!payload || !payload.success || !payload.data) return null;
    return payload.data;
  } catch (error) {
    console.warn('DiscordPlus status failed', error);
    return null;
  }
}

function discordplusApplyGate(status) {
  const overlay = discordplusEnsureOverlay();
  if (!status || !status.blocked || discordplusIsExemptPath()) {
    overlay.hidden = true;
    document.documentElement.style.removeProperty('overflow');
    return;
  }

  const message = document.getElementById('discordplus-gate-message');
  const account = document.getElementById('discordplus-gate-account');
  const link = document.getElementById('discordplus-gate-link');

  if (message && status.message) message.textContent = status.message;
  if (account && status.account_url) account.setAttribute('href', status.account_url);
  if (link) link.setAttribute('href', status.link_url || '/api/user/auth/discord/link');

  overlay.hidden = false;
  document.documentElement.style.overflow = 'hidden';
}

class DiscordPlusPlugin {
  constructor() {
    this.timer = null;
  }

  async init() {
    await this.refresh();
    this.timer = window.setInterval(() => this.refresh(), 12000);
    window.addEventListener('popstate', () => this.refresh());

    const pushState = history.pushState;
    history.pushState = function () {
      const result = pushState.apply(this, arguments);
      window.setTimeout(() => window.dispatchEvent(new Event('discordplus:route')), 0);
      return result;
    };
    window.addEventListener('discordplus:route', () => this.refresh());
  }

  async refresh() {
    discordplusApplyGate(await discordplusFetchStatus());
  }
}

(async function bootDiscordPlus() {
  try {
    await discordplusWaitForApi();
  } catch (_) {}
  await new DiscordPlusPlugin().init();
})();
