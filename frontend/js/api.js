// Configure your published remote backend URL here (used by Capacitor mobile app or remote hosting)
const REMOTE_API_URL = 'https://voting-production-5823.up.railway.app/backend/api'; // e.g. 'https://your-domain.com/backend/api'

const frontendIndex = window.location.pathname.indexOf('/frontend/');
const basePath = frontendIndex !== -1 ? window.location.pathname.substring(0, frontendIndex) : '';

// Auto-detect whether running inside native mobile webview (Capacitor) or standard local web browser
const isCapacitor = window.location.protocol === 'capacitor:' || 
                    window.location.protocol === 'file:' ||
                    (window.location.hostname === 'localhost' && !window.location.pathname.includes('/voting/'));

const API_BASE = (REMOTE_API_URL && (isCapacitor || !basePath)) 
    ? REMOTE_API_URL.replace(/\/$/, '') 
    : `${basePath}/backend/api`;

let activeRequests = 0;
let loaderHideTimeout = null;

function getFullscreenLoader() {
    let loader = document.getElementById('app-fullscreen-loader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'app-fullscreen-loader';
        loader.className = 'fullscreen-loader-overlay';
        loader.setAttribute('role', 'status');
        loader.setAttribute('aria-live', 'polite');
        loader.setAttribute('aria-label', 'Loading content');
        loader.innerHTML = `
            <div class="loader-content">
                <div class="circular-spinner">
                    <svg viewBox="0 0 50 50" class="spinner-svg">
                        <circle class="spinner-track" cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle>
                        <circle class="spinner-circle" cx="25" cy="25" r="20" fill="none" stroke-width="4"></circle>
                    </svg>
                </div>
                <div class="loader-text">Loading...</div>
            </div>
        `;
        document.body.appendChild(loader);
    }
    return loader;
}

function updateFullscreenLoader(status) {
    const loader = getFullscreenLoader();
    if (status === 'start') {
        activeRequests++;
        if (loaderHideTimeout) {
            clearTimeout(loaderHideTimeout);
            loaderHideTimeout = null;
        }
        loader.classList.add('visible');
    } else if (status === 'finish') {
        activeRequests = Math.max(0, activeRequests - 1);
        if (activeRequests === 0) {
            loaderHideTimeout = setTimeout(() => {
                loader.classList.remove('visible');
            }, 180);
        }
    }
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', getFullscreenLoader);
    } else {
        getFullscreenLoader();
    }
}

const api = {
    getToken: () => localStorage.getItem('voter_token'),
    setToken: (token) => localStorage.setItem('voter_token', token),
    clearToken: () => localStorage.removeItem('voter_token'),
    showLoading: () => updateFullscreenLoader('start'),
    hideLoading: () => updateFullscreenLoader('finish'),

    request: async (endpoint, options = {}) => {
        updateFullscreenLoader('start');
        const token = api.getToken();
        const headers = {
            'Content-Type': 'application/json',
            ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
            ...options.headers
        };

        try {
            const response = await fetch(`${API_BASE}/${endpoint}`, {
                ...options,
                headers
            });

            if (response.status === 401) {
                api.clearToken();
                window.location.href = `${basePath}/frontend/login.html`;
                throw new Error('Unauthorized');
            }

            const rawText = await response.text();
            let parsedData;
            try {
                parsedData = JSON.parse(rawText);
            } catch (jsonErr) {
                console.error(`Non-JSON response from ${endpoint}:`, rawText);
                const stripped = rawText.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                throw new Error(stripped || `Server returned invalid response (HTTP ${response.status})`);
            }

            return parsedData;
        } finally {
            updateFullscreenLoader('finish');
        }
    },

    get: (endpoint) => api.request(endpoint, { method: 'GET' }),
    post: (endpoint, data) => api.request(endpoint, { method: 'POST', body: JSON.stringify(data) }),

    auth: {
        login: (username, password) => api.post('auth.php?action=login', { username, password }),
        logout: () => api.post('auth.php?action=logout'),
        me: () => api.get('auth.php?action=me')
    },
    
    voter: {
        dashboard: () => api.get('voter.php?action=dashboard'),
        candidates: () => api.get('voter.php?action=candidates'),
        castVote: (votes) => api.post('voter.php?action=cast_vote', { votes }),
        profile: () => api.get('voter.php?action=profile'),
        updateProfile: (full_name) => api.post('voter.php?action=update_profile', { full_name })
    },

    admin: {
        dashboard: () => api.get('admin.php?action=dashboard'),
        setStatus: (status) => api.post('admin.php?action=set_status', { status }),
        resetRecords: () => api.post('admin.php?action=reset_records'),
        results: () => api.get('admin.php?action=results'),
        positions: () => api.get('admin.php?action=positions'),
        candidates: () => api.get('admin.php?action=candidates'),
        addCandidate: (data) => api.post('admin.php?action=add_candidate', data),
        editCandidate: (data) => api.post('admin.php?action=edit_candidate', data),
        deleteCandidate: (id) => api.post('admin.php?action=delete_candidate', { id }),
        voters: () => api.get('admin.php?action=voters'),
        addVoter: (data) => api.post('admin.php?action=add_voter', data),
        editVoter: (data) => api.post('admin.php?action=edit_voter', data),
        deleteVoter: (id) => api.post('admin.php?action=delete_voter', { id }),
        resetVoterBallot: (id) => api.post('admin.php?action=reset_voter_ballot', { id }),
        backups: () => api.get('admin.php?action=backups'),
        downloadPdf: async (filename = '') => {
            api.showLoading();
            try {
                const token = api.getToken();
                const endpoint = filename 
                    ? `admin.php?action=download_backup&file=${encodeURIComponent(filename)}`
                    : `admin.php?action=download_pdf`;
                
                const response = await fetch(`${API_BASE}/${endpoint}`, {
                    headers: token ? { 'Authorization': `Bearer ${token}` } : {}
                });

                if (!response.ok) {
                    if (response.status === 401) {
                        api.clearToken();
                        window.location.href = `${basePath}/frontend/login.html`;
                        throw new Error('Unauthorized');
                    }
                    const errData = await response.json().catch(() => ({}));
                    throw new Error(errData.error || `Failed to download PDF (${response.status})`);
                }

                let downloadName = filename;
                if (!downloadName) {
                    const disposition = response.headers.get('Content-Disposition');
                    if (disposition && disposition.indexOf('filename=') !== -1) {
                        const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                        if (matches != null && matches[1]) {
                            downloadName = matches[1].replace(/['"]/g, '');
                        }
                    }
                }
                if (!downloadName) {
                    const stamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
                    downloadName = `election_official_report_${stamp}.pdf`;
                }

                const blob = await response.blob();
                const blobUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = blobUrl;
                a.download = downloadName;
                document.body.appendChild(a);
                a.click();
                setTimeout(() => {
                    if (document.body.contains(a)) {
                        document.body.removeChild(a);
                    }
                    window.URL.revokeObjectURL(blobUrl);
                }, 300);

                return { success: true, filename: downloadName };
            } finally {
                api.hideLoading();
            }
        },
        getDownloadUrl: (filename = '') => {
            const token = api.getToken() || '';
            const endpoint = filename 
                ? `admin.php?action=download_backup&file=${encodeURIComponent(filename)}&token=${encodeURIComponent(token)}`
                : `admin.php?action=download_pdf&token=${encodeURIComponent(token)}`;
            return `${API_BASE}/${endpoint}`;
        }
    },

    // Shimmer skeleton renderer for responsive feel on slow connections
    showSkeleton: (containerId, type = 'cards') => {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (type === 'cards') {
            container.innerHTML = `
                <div class="animate-fade-in" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 28px;">
                    <div class="skeleton-card">
                        <div class="skeleton skeleton-text" style="width: 40%;"></div>
                        <div class="skeleton" style="height: 36px; width: 60%; margin: 10px 0;"></div>
                        <div class="skeleton skeleton-text" style="width: 50%;"></div>
                    </div>
                    <div class="skeleton-card">
                        <div class="skeleton skeleton-text" style="width: 40%;"></div>
                        <div class="skeleton" style="height: 36px; width: 60%; margin: 10px 0;"></div>
                        <div class="skeleton skeleton-text" style="width: 50%;"></div>
                    </div>
                    <div class="skeleton-card">
                        <div class="skeleton skeleton-text" style="width: 40%;"></div>
                        <div class="skeleton" style="height: 36px; width: 60%; margin: 10px 0;"></div>
                        <div class="skeleton skeleton-text" style="width: 50%;"></div>
                    </div>
                </div>
            `;
        } else if (type === 'list') {
            container.innerHTML = `
                <div class="animate-fade-in">
                    <div class="skeleton-card" style="margin-bottom: 20px;">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-text" style="width: 90%;"></div>
                        <div class="skeleton skeleton-text" style="width: 75%;"></div>
                    </div>
                    <div class="skeleton-card" style="margin-bottom: 20px;">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-text" style="width: 90%;"></div>
                        <div class="skeleton skeleton-text" style="width: 75%;"></div>
                    </div>
                </div>
            `;
        }
    }
};
