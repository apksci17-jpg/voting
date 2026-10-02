// frontend/js/components.js - Shell UI Component Generator with SVG Icons

const ICONS = {
    dashboard: `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"></rect><rect x="14" y="3" width="7" height="5" rx="1"></rect><rect x="14" y="12" width="7" height="9" rx="1"></rect><rect x="3" y="16" width="7" height="5" rx="1"></rect></svg>`,
    votingControl: `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>`,
    candidates: `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>`,
    results: `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.45 1-1 1H7v2h10v-2h-2c-.55 0-1-.45-1-1v-2.34"></path><path d="M18 4H6v7a6 6 0 0 0 12 0V4z"></path></svg>`,
    ballot: `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>`,
    profile: `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>`,
    logout: `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>`,
    menu: `<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>`,
    close: `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>`,
    voters: `<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>`,
    voteBox: `<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>`
};

function renderSidebar(role, activePage) {
    const isVoter = role === 'voter';
    let navItems = [];

    if (isVoter) {
        navItems = [
            { id: 'dashboard', label: 'Dashboard', icon: ICONS.dashboard, href: 'dashboard.html' },
            { id: 'candidates', label: 'Candidates', icon: ICONS.candidates, href: 'candidates.html' },
            { id: 'ballot', label: 'Cast Vote', icon: ICONS.ballot, href: 'ballot.html' },
            { id: 'profile', label: 'Profile', icon: ICONS.profile, href: 'profile.html' }
        ];
    } else {
        navItems = [
            { id: 'dashboard', label: 'Dashboard', icon: ICONS.dashboard, href: 'dashboard.html' },
            { id: 'voting_control', label: 'Voting Control', icon: ICONS.votingControl, href: 'voting_control.html' },
            { id: 'candidates', label: 'Manage Candidates', icon: ICONS.candidates, href: 'candidates.html' },
            { id: 'voters', label: 'Manage Voters', icon: ICONS.voters, href: 'voters.html' },
            { id: 'results', label: 'Results', icon: ICONS.results, href: 'results.html' }
        ];
    }

    const navLinksHtml = navItems.map(item => {
        const isActive = activePage === item.id;
        const activeBg = isActive ? 'background: rgba(255, 255, 255, 0.18); font-weight: 800; border-left: 4px solid #60a5fa;' : 'background: transparent; font-weight: 600; opacity: 0.88;';
        return `
            <li class="nav-item ${isActive ? 'active' : ''}">
                <a href="${item.href}" style="display: flex; align-items: center; gap: 12px; padding: 12px 18px; border-radius: 10px; color: #ffffff; text-decoration: none; font-size: 14px; transition: all 0.2s ease; ${activeBg}">
                    <span class="nav-icon" style="display: flex; align-items: center; justify-content: center; flex-shrink: 0;">${item.icon}</span>
                    <span class="nav-text" style="letter-spacing: 0.2px;">${item.label}</span>
                </a>
            </li>
        `;
    }).join('');

    return `
        <!-- Mobile Sidebar Backdrop -->
        <div id="sidebarBackdrop" onclick="toggleSidebar(false)" style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.45); z-index: 998; display: none; opacity: 0; transition: opacity 0.3s ease;"></div>

        <!-- Sidebar Navigation (Fixed Position & Isolated Scroll) -->
        <aside id="appSidebar" class="sidebar" style="position: fixed; top: 0; left: 0; bottom: 0; width: 260px; height: 100vh; background: linear-gradient(180deg, #1259d6 0%, #0d44a8 100%); color: white; display: flex; flex-direction: column; padding: 24px 0; flex-shrink: 0; z-index: 999; box-shadow: 4px 0 20px rgba(0,0,0,0.08); overflow-y: auto; overflow-x: hidden; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.2) transparent; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
            <!-- Sidebar Header / Logo -->
            <div class="sidebar-header" style="text-align: center; padding: 0 20px 20px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.15); margin-bottom: 20px; position: relative; flex-shrink: 0;">
                <button type="button" class="mobile-close-btn" onclick="toggleSidebar(false)" style="position: absolute; right: 10px; top: 0; background: none; border: none; color: white; cursor: pointer; display: none;">
                    ${ICONS.close}
                </button>
                <div class="sidebar-logo" style="width: 52px; height: 52px; margin: 0 auto 10px auto; background: rgba(255, 255, 255, 0.15); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.12);">
                    <img src="../assets/images/logo.png" alt="Tomorrow Vote" onerror="this.outerHTML=ICONS.voteBox" style="width: 36px; height: 36px; object-fit: contain;">
                </div>
                <div class="sidebar-app-name" style="font-size: 17px; font-weight: 900; letter-spacing: 0.5px; color: #ffffff;">Tomorrow Vote</div>
                <div class="sidebar-app-sub" style="font-size: 10px; font-weight: 700; letter-spacing: 1.2px; color: rgba(255, 255, 255, 0.75); margin-top: 3px;">${isVoter ? 'VOTER PORTAL' : 'ADMIN PORTAL'}</div>
            </div>

            <!-- Navigation Links -->
            <ul class="sidebar-nav" style="list-style: none; padding: 0 14px; margin: 0; flex-grow: 1; display: flex; flex-direction: column; gap: 6px;">
                ${navLinksHtml}
            </ul>

            <!-- Logout Button -->
            <div style="padding: 16px 14px 0 14px; border-top: 1px solid rgba(255, 255, 255, 0.15); flex-shrink: 0;">
                <a href="#" onclick="logoutUser()" style="display: flex; align-items: center; gap: 12px; padding: 12px 18px; border-radius: 10px; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 600; background: rgba(239, 68, 68, 0.18); border: 1px solid rgba(239, 68, 68, 0.3); transition: all 0.2s ease;">
                    <span class="nav-icon" style="display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fca5a5;">${ICONS.logout}</span>
                    <span class="nav-text" style="letter-spacing: 0.2px;">Sign Out</span>
                </a>
            </div>
        </aside>
    `;
}

function renderBottomNav(role, activePage) {
    const isVoter = role === 'voter';
    const items = isVoter ? [
        { id: 'dashboard', label: 'Dashboard', icon: ICONS.dashboard, href: 'dashboard.html' },
        { id: 'candidates', label: 'Candidates', icon: ICONS.candidates, href: 'candidates.html' },
        { id: 'ballot', label: 'Ballot', icon: ICONS.ballot, href: 'ballot.html' },
        { id: 'profile', label: 'Profile', icon: ICONS.profile, href: 'profile.html' }
    ] : [
        { id: 'dashboard', label: 'Overview', icon: ICONS.dashboard, href: 'dashboard.html' },
        { id: 'voting_control', label: 'Control', icon: ICONS.votingControl, href: 'voting_control.html' },
        { id: 'candidates', label: 'Candidates', icon: ICONS.candidates, href: 'candidates.html' },
        { id: 'voters', label: 'Voters', icon: ICONS.voters, href: 'voters.html' },
        { id: 'results', label: 'Results', icon: ICONS.results, href: 'results.html' }
    ];

    const tabsHtml = items.map(item => {
        const isActive = activePage === item.id;
        const color = isActive ? '#1059d6' : '#64748b';
        const fw = isActive ? '800' : '600';
        return `
            <a href="${item.href}" class="mobile-tab-btn ${isActive ? 'active' : ''}" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; text-decoration: none; color: ${color}; font-weight: ${fw}; font-size: 11px; height: 100%; transition: all 0.15s ease;">
                <span class="tab-icon" style="display: flex; align-items: center; justify-content: center;">${item.icon}</span>
                <span class="tab-label" style="letter-spacing: 0.2px;">${item.label}</span>
                ${isActive ? '<span style="width: 16px; height: 3px; background: #1059d6; border-radius: 99px; margin-top: -1px;"></span>' : ''}
            </a>
        `;
    }).join('');

    return `
        <nav class="mobile-bottom-nav" aria-label="Mobile Bottom Navigation">
            ${tabsHtml}
        </nav>
    `;
}

function renderHeader(user) {
    return `
        <header class="top-header" style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
            <div style="display: flex; align-items: center; gap: 14px;">
                <button type="button" class="mobile-menu-btn" onclick="toggleSidebar(true)" style="background: none; border: none; color: #334155; cursor: pointer; padding: 6px; border-radius: 6px; display: none; align-items: center; justify-content: center;">
                    ${ICONS.menu}
                </button>
                <div>
                    <span style="font-size: 11px; font-weight: 800; color: #2563eb; text-transform: uppercase; letter-spacing: 0.8px;">${user.role === 'admin' ? 'Administration' : 'Student Voter'}</span>
                    <div style="font-weight: 900; color: #0f172a; font-size: 16px;">${user.role === 'admin' ? 'Admin Portal' : 'Tomorrow Vote'}</div>
                </div>
            </div>

            <div class="header-user" style="display: flex; align-items: center; gap: 12px;">
                <div style="text-align: right;">
                    <div style="font-weight: 800; color: #0f172a; font-size: 14px;">${user.full_name}</div>
                    <div style="font-size: 11px; font-weight: 600; color: #64748b;">${user.student_id || user.username || ''}</div>
                </div>
                <div class="header-avatar" style="width: 38px; height: 38px; border-radius: 50%; background: #eff6ff; border: 1.5px solid #bfdbfe; display: flex; align-items: center; justify-content: center; color: #2563eb; flex-shrink: 0;">
                    ${ICONS.profile}
                </div>
            </div>
        </header>
    `;
}

async function initPage(role, activePage) {
    if (!api.getToken()) {
        window.location.href = '../login.html';
        return null;
    }

    try {
        const res = await api.auth.me();
        if (res.user.role !== role) {
            window.location.href = '../login.html';
            return null;
        }

        const appContainer = document.getElementById('app');
        appContainer.style.display = 'flex';
        appContainer.style.width = '100%';
        appContainer.style.minHeight = '100vh';
        appContainer.style.backgroundColor = '#f8fafc';

        appContainer.innerHTML = `
            ${renderSidebar(role, activePage)}
            <div class="main-wrapper" style="margin-left: 260px; width: calc(100% - 260px); min-height: 100vh; display: flex; flex-direction: column; flex-grow: 1; background-color: #f8fafc;">
                ${renderHeader(res.user)}
                <main class="content-body" id="pageContent" style="background-color: #f8fafc; flex-grow: 1; max-width: 1280px; width: 100%; margin: 0 auto; box-sizing: border-box;"></main>
                ${renderBottomNav(role, activePage)}
            </div>
        `;

        if (typeof api !== 'undefined' && api.showSkeleton) {
            api.showSkeleton('pageContent', 'cards');
        }

        setupMobileResponsiveStyles();
        return res.user;
    } catch (err) {
        window.location.href = '../login.html';
    }
}

function setupMobileResponsiveStyles() {
    if (document.getElementById('components-responsive-css')) return;
    const style = document.createElement('style');
    style.id = 'components-responsive-css';
    style.innerHTML = `
        .top-header {
            padding-top: calc(14px + env(safe-area-inset-top, 0px)) !important;
            padding-bottom: 14px !important;
            padding-left: calc(28px + env(safe-area-inset-left, 0px)) !important;
            padding-right: calc(28px + env(safe-area-inset-right, 0px)) !important;
            box-sizing: border-box !important;
        }
        .content-body {
            padding: 28px;
            box-sizing: border-box;
        }
        @media (max-width: 900px) {
            #appSidebar {
                position: fixed !important;
                left: 0;
                top: 0;
                bottom: 0;
                transform: translateX(-100%);
                z-index: 1000 !important;
                padding-top: calc(20px + env(safe-area-inset-top, 0px)) !important;
                padding-bottom: calc(24px + env(safe-area-inset-bottom, 0px)) !important;
            }
            #appSidebar.open {
                transform: translateX(0) !important;
            }
            .mobile-menu-btn {
                display: flex !important;
            }
            .mobile-close-btn {
                display: block !important;
            }
            .main-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .top-header {
                padding-top: calc(14px + env(safe-area-inset-top, 0px)) !important;
                padding-bottom: 14px !important;
                padding-left: calc(16px + env(safe-area-inset-left, 0px)) !important;
                padding-right: calc(16px + env(safe-area-inset-right, 0px)) !important;
                box-sizing: border-box !important;
            }
            .content-body {
                padding-top: 18px !important;
                padding-left: calc(16px + env(safe-area-inset-left, 0px)) !important;
                padding-right: calc(16px + env(safe-area-inset-right, 0px)) !important;
                padding-bottom: calc(120px + env(safe-area-inset-bottom, 0px)) !important;
                box-sizing: border-box !important;
            }
            .mobile-bottom-nav {
                height: calc(64px + env(safe-area-inset-bottom, 0px)) !important;
                min-height: calc(64px + env(safe-area-inset-bottom, 0px)) !important;
                padding-bottom: env(safe-area-inset-bottom, 0px) !important;
                box-sizing: border-box !important;
            }
        }
    `;
    document.head.appendChild(style);
}

function toggleSidebar(open) {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar || !backdrop) return;

    if (open) {
        sidebar.classList.add('open');
        backdrop.style.display = 'block';
        setTimeout(() => { backdrop.style.opacity = '1'; }, 10);
    } else {
        sidebar.classList.remove('open');
        backdrop.style.opacity = '0';
        setTimeout(() => { backdrop.style.display = 'none'; }, 300);
    }
}

async function logoutUser() {
    try {
        await api.auth.logout();
    } catch (e) {
        // proceed with local cleanup regardless
    }
    api.clearToken();
    window.location.href = '../login.html';
}

/* ==========================================================================
   Universal Candidate Detail Modal & Native Mobile Bottom Sheet
   ========================================================================== */
let currentModalEscListener = null;

function showCandidateModal(cand, options = {}) {
    if (!cand) return;

    let backdrop = document.getElementById('candidateModalBackdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'candidateModalBackdrop';
        backdrop.className = 'candidate-modal-backdrop';
        backdrop.onclick = (e) => {
            if (e.target === backdrop) closeCandidateModal();
        };
        document.body.appendChild(backdrop);
    }

    const platformContent = cand.platform 
        ? cand.platform.replace(/\n\n/g, '</p><p style="margin-top: 10px;">').replace(/\n/g, '<br>')
        : '<em>No detailed campaign platform or manifesto was submitted for this candidate.</em>';

    const isBallot = options.isBallot || false;
    const canSelect = options.canSelect || false;
    const isVoter = options.role === 'voter' || (!options.role && window.location.pathname.includes('/voter/'));

    backdrop.innerHTML = `
        <div class="candidate-modal-sheet" role="dialog" aria-modal="true" aria-labelledby="modalCandName">
            <div class="sheet-drag-handle"></div>
            <button type="button" class="modal-close-btn" onclick="closeCandidateModal()" aria-label="Close dialog">
                ${ICONS.close}
            </button>

            <!-- Candidate Hero Header -->
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="position: relative; width: 100px; height: 100px; margin: 0 auto 14px auto;">
                    <img src="${cand.profile_image ? '../assets/uploads/' + cand.profile_image : '../assets/uploads/default.svg'}" onerror="this.onerror=null; this.src='../assets/uploads/default.svg'" alt="${cand.candidate_name}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3.5px solid #2563eb; box-shadow: 0 8px 24px rgba(37, 99, 235, 0.25);">
                    <div style="position: absolute; bottom: 2px; right: 2px; width: 28px; height: 28px; border-radius: 50%; background: #16a34a; color: white; display: flex; align-items: center; justify-content: center; border: 2.5px solid #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.15);" title="Official Certified Nominee">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                </div>

                <div style="display: inline-flex; align-items: center; gap: 6px; background: #eff6ff; color: #1d4ed8; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px;">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    ${cand.position_name || 'Candidate'}
                </div>

                <h2 id="modalCandName" style="font-size: 24px; font-weight: 900; color: #0f172a; margin: 4px 0 2px 0; line-height: 1.25;">
                    ${cand.candidate_name}
                </h2>
                <div style="font-size: 13px; font-weight: 600; color: #64748b;">
                    ${cand.year_level || 'Student Nominee'} &bull; Official Student Council Nominee
                </div>
            </div>

            <!-- Platform / Manifesto Section -->
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 18px; padding: 20px 22px; margin-bottom: 24px; position: relative;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                    <div style="font-size: 12px; font-weight: 800; color: #1e40af; text-transform: uppercase; letter-spacing: 0.6px; display: inline-flex; align-items: center; gap: 6px;">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        Platform & Advocacy Manifesto
                    </div>
                    <span style="font-size: 11px; font-weight: 700; color: #15803d; background: #dcfce7; padding: 2px 8px; border-radius: 10px;">Verified Record</span>
                </div>
                <div style="font-size: 14px; color: #334155; line-height: 1.7; font-weight: 500;">
                    ${platformContent}
                </div>
            </div>

            <!-- Key Pillars / Badges -->
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 26px;">
                <div style="display: inline-flex; align-items: center; gap: 6px; background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 10px; font-size: 12px; font-weight: 700; color: #334155;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Student Leadership
                </div>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 10px; font-size: 12px; font-weight: 700; color: #334155;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Campus Welfare
                </div>
                <div style="display: inline-flex; align-items: center; gap: 6px; background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 10px; font-size: 12px; font-weight: 700; color: #334155;">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Transparency
                </div>
            </div>

            <!-- Actions Footer -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                ${isBallot && canSelect ? `
                    <button type="button" id="modalSelectBtn" style="flex: 1; background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); color: #ffffff; padding: 13px 22px; border-radius: 12px; font-size: 14px; font-weight: 800; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.35); transition: all 0.2s ease;">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        <span>Select on Ballot</span>
                    </button>
                ` : (isVoter && !window.location.pathname.includes('ballot.html') ? `
                    <a href="ballot.html" style="flex: 1; background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); color: #ffffff; padding: 13px 22px; border-radius: 12px; font-size: 14px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.35); transition: all 0.2s ease;">
                        <span>Proceed to Ballot</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </a>
                ` : '')}

                <button type="button" onclick="closeCandidateModal()" style="background: #f1f5f9; color: #334155; border: 1.5px solid #cbd5e1; padding: 12px 20px; border-radius: 12px; font-size: 14px; font-weight: 800; cursor: pointer; transition: all 0.2s ease;">
                    Close
                </button>
            </div>
        </div>
    `;

    if (isBallot && canSelect && options.onSelect) {
        const selectBtn = document.getElementById('modalSelectBtn');
        if (selectBtn) {
            selectBtn.onclick = () => {
                options.onSelect(cand);
                closeCandidateModal();
            };
        }
    }

    // Lock background scrolling
    document.body.style.overflow = 'hidden';

    // Show with animation
    backdrop.style.display = 'flex';
    requestAnimationFrame(() => {
        backdrop.classList.add('active');
    });

    if (currentModalEscListener) {
        document.removeEventListener('keydown', currentModalEscListener);
    }
    currentModalEscListener = (e) => {
        if (e.key === 'Escape') closeCandidateModal();
    };
    document.addEventListener('keydown', currentModalEscListener);
}

function closeCandidateModal() {
    const backdrop = document.getElementById('candidateModalBackdrop');
    if (!backdrop) return;

    backdrop.classList.remove('active');
    document.body.style.overflow = '';

    if (currentModalEscListener) {
        document.removeEventListener('keydown', currentModalEscListener);
        currentModalEscListener = null;
    }

    setTimeout(() => {
        backdrop.style.display = 'none';
    }, 320);
}
