# Online Voting System ("Tomorrow Vote") — Project Context & Changelog

This document maintains a comprehensive record of all changes, features, architectural decisions, and system constraints implemented in the Online Voting System codebase.

---

## 1. Project Overview & System Environment
- **Project Location**: `c:\xampp\htdocs\voting`
- **Architecture Model**: **Decoupled Headless System** (Stateless PHP REST API + Static Frontend Client)
- **Target Environments**: Standard Web Browsers & Native Mobile App compilation via **Capacitor** (iOS & Android)
- **Database Engine**: MySQL (`voting_db` via PDO)
- **Authentication**: Stateless Bearer Token (`Authorization: Bearer <token>`) persisted in `localStorage` (`voter_token`) + 2FA Email OTP for voters
- **Default Credentials**:
  - Admin: `admin` / `admin123`
  - Voters: `Moises240104785`, `Jose240114524`, etc. (FirstName + StudentNumber)
- **Global Timezone**: `Asia/Manila` (Philippine Standard Time, UTC+8) globally set in PHP runtime (`date_default_timezone_set('Asia/Manila')`) and MySQL session (`SET time_zone = '+08:00'`)
- **Iconography Standard**: 100% Inline Vector SVG Icons (all legacy emoji characters eradicated)
- **Last Updated**: 2026-10-03

---

## 2. Major Architectural Overhaul & Evolution

### A. Frontend / Backend Separation (Headless Architecture for Capacitor)
- **Stakeholder Directive**: Complete decoupling of frontend and backend to facilitate immediate conversion into a mobile app via **Capacitor**.
- **Decoupled Static Frontend (`frontend/`)**:
  - Rebuilt entirely in pure HTML5, CSS3, and modern Vanilla JavaScript without any server-side PHP view rendering.
  - Zero PHP dependencies in the presentation layer. Can be hosted statically or bundled directly into an iOS/Android Capacitor container.
  - Unified client-side network client (`frontend/js/api.js`) featuring dynamic base path calculation and automated `Authorization: Bearer <token>` injection for all requests.
  - Reusable layout component injector (`frontend/js/components.js`) handling navigation sidebars, top headers, responsive mobile drawers, and SVG icon sets.
- **Headless PHP REST API (`backend/`)**:
  - Clean JSON-only endpoints in `backend/api/`:
    - `auth.php`: Login, token generation, identity verification (`me`), and logout.
    - `voter.php`: Voter dashboard metrics, candidate lists, encrypted ballot submission, and profile management.
    - `admin.php`: Election analytics, voting status toggle (`OPEN`/`CLOSED`), candidate management, results tabulation, and PDF archival.
  - Stateless Bearer Token Authentication: Added `session_token` column to the `users` table. Tokens are generated using `bin2hex(random_bytes(32))` and validated via `backend/includes/api_auth.php`.
  - Comprehensive CORS support in `backend/config/cors.php` supporting preflight `OPTIONS` and cross-origin requests from `localhost` and `capacitor://` schemes.

### B. Complete Eradication of Face Biometrics & Liveness Verification
- **Stakeholder Directive**: Analyze and completely remove the face verification, Gemini Multimodal Vision AI, and blink liveness features from the entire system.
- **Database Clean-up**:
  - Executed database migrations dropping `face_photo`, `face_descriptor`, and `face_registered` columns from the `users` table.
- **Codebase Clean-up**:
  - Removed all Gemini proxy scripts (`config/gemini.php`, `includes/gemini_service.php`, `voter/api_gemini_verify.php`).
  - Deleted legacy biometric engine script (`assets/js/face_biometrics.js`) and onboarding screen (`register_face.php`).
  - Stripped facial KYC verification HUD, blink challenges, and camera loops from voter ballot confirmation and profile management.
  - Ballots are now submitted directly and securely via AES-256-CBC cryptography without camera or hardware permission obstacles.

### C. Design System & Inline Vector SVG Icon Overhaul
- **Universal Emoji Replacement**:
  - Replaced all legacy emoji icons across all views with sharp, modern, accessible inline vector SVG icons (Dashboard, Voting Control, Candidates, Results, Ballot, Profile, Logout, Password Toggles, and Badges).
- **Glassmorphism Sign-In Screen (`frontend/login.html`)**:
  - Centered glassmorphism card (`backdrop-filter: blur(14px)`) positioned over the official ballot-box voting graphic (`assets/images/login_bg.png`).
  - Floating official school emblem (`assets/images/logo.png`) perched on top of the card.
  - Interactive password visibility toggle (`eye` and `eye-off` SVGs).
  - High-contrast typography and clear voter guidance: *"For the voters, use your school account"*.
- **Responsive Mobile Navigation Drawer**:
  - Dynamic slide-out navigation drawer with tap-to-dismiss backdrop for screens narrower than 900px.
  - Hamburger menu toggle icon integrated into the top bar.

### D. Typography Standard (Plus Jakarta Sans & Inter)
- **Stakeholder Directive**: Select the best typography to make the system engaging, readable, modern, and trustworthy.
- **Font Stack**:
  - Primary Display & Headings: **Plus Jakarta Sans** (weights 300, 400, 500, 600, 700, 800, 900) via Google Fonts. Offers geometric clarity, modern character, and excellent mobile legibility.
  - Body & Form Elements: **Inter** (weights 400, 500, 600, 700) for dense tabular metrics, ballot options, and system data.
  - Preconnected Google Fonts `<link rel="preconnect">` embedded across all 10 HTML view documents.

### E. Fixed Position Sidebar Architecture & Scroll Isolation
- **Stakeholder Directive**: Ensure the sidebar is in a fixed position so main page scrolling never moves or affects the navigation.
- **Implementation**:
  - Desktop Viewports (>=900px):
    - `.sidebar` is locked via `position: fixed !important; top: 0 !important; left: 0 !important; bottom: 0 !important; width: 260px !important; height: 100vh !important; z-index: 999; overflow-y: auto;`.
    - `.main-wrapper` uses `margin-left: 260px !important; width: calc(100% - 260px) !important; min-height: 100vh;`.
    - Scrolling main content never causes the sidebar to scroll or bounce.
  - Mobile Viewports (<900px):
    - Responsive off-canvas slide drawer with `transform: translateX(-100%)` and smooth `0.3s cubic-bezier(0.4, 0, 0.2, 1)` transition.
    - Backdrop overlay (`#sidebarBackdrop`) dismisses on tap with `overflow: hidden` on body during active drawer state.

### F. Smooth Animations, Micro-Interactions & Slow Network Architecture
- **Stakeholder Directive**: Add smooth animations and transitions to all pages and elements to make the system engaging and responsive on slow networks.
- **Keyframe Animation Suite**:
  - `fadeInUp`: 0.45s cubic-bezier(0.16, 1, 0.3, 1) upward smooth reveal for page containers and cards.
  - `fadeIn`: 0.35s ease fade for modal and backdrop reveals.
  - `scaleIn`: 0.35s scale reveal from 0.97 to 1.0 for candidate and action cards.
  - `pulseDot`: 2.0s infinite subtle breathing glow on active election and ballot status indicators.
  - `shimmer`: 1.5s infinite shimmering gradient on loading skeleton placeholders.
- **Slow Network Optimization**:
  - **Global Top Network Progress Bar**: `#network-progress-bar` automatically animates at the top of the viewport on any outgoing `fetch` call and completes smoothly on response resolution.
  - **Shimmer Skeleton Placeholders**: `api.showSkeleton()` renders pulse shimmer cards into `#pageContent` before data finishes fetching over slow connections, eliminating blank screen delays or sudden layout shifts.
  - **Universal Micro-Interactions**: All buttons and actionable cards feature `transform: scale(0.98)` on `:active` with smooth cubic-bezier easing.

### G. Clickable Candidate Cards & Native Mobile Experience (Capacitor Polish)
- **Stakeholder Directive**: Make candidate cards clickable to allow user or voter to show all candidate info. Make all clicks and hovers smooth, and make the system behave like a real native mobile app.
- **Universal Candidate Modal & Bottom Sheet (`frontend/js/components.js`)**:
  - `showCandidateModal(cand, options)` and `closeCandidateModal()`:
    - Candidate Hero: Large rounded avatar with certified nominee check badge, position pill badge, and student level.
    - Platform & Advocacy Manifesto: Full formatted platform text with dedicated verified record indicator.
    - Key Leadership Pillars: Visual badges for Student Leadership, Campus Welfare, and Transparency.
    - Contextual Dynamic Actions: Direct "Select on Ballot" button on `ballot.html` that automatically checks the candidate radio input, centers the card smoothly, and closes the modal; or "Proceed to Ballot" link on `candidates.html`.
    - Modal Dismissal: Back-drop click, top close button, and `Escape` keyboard listener with body scroll lock.
  - **Native Bottom Sheet for Mobile Viewports (<768px)**:
    - Slides smoothly from bottom (`translateY(100%)` to `translateY(0)`).
    - Rounded top corners (`border-radius: 28px 28px 0 0`), drag handle pill (`.sheet-drag-handle`), and safe-area inset padding.
- **Native Touch & Mobile Damping Tuning (`frontend/assets/css/style.css`)**:
  - **300ms Delay & Blue Tap Elimination**: Configured `touch-action: manipulation;` and `-webkit-tap-highlight-color: transparent;` across all interactive elements.
  - **Text Selection Protection**: Added `user-select: none;` and `-webkit-touch-callout: none;` on all cards, buttons, tabs, and headers to eliminate accidental text selection during touch scrolling.
  - **Haptic Tap Damping**: Configured `:active` states on buttons, cards, and labels with instant `transform: scale(0.97)` (0.08s duration) and ultra-smooth cubic-bezier elevation on hover (`translateY(-4px) scale(1.008)`).
- **Native Mobile Bottom Navigation Bar (<900px)**:
  - Fixed bottom tab bar (`.mobile-bottom-nav`) with frosted glass backdrop blur (`backdrop-filter: blur(16px)`).
  - 4 quick access tabs for voters (Dashboard, Candidates, Cast Vote, Profile) and admins (Overview, Control, Candidates, Results) with active pill indicators and safe-area padding.
- **Accessibility & Media Resilience**:
  - Keyboard activation (`Enter` / `Space`) supported on all candidate cards with `role="button"` and `tabindex="0"`.
  - Created standalone SVG vector avatar fallback (`frontend/assets/uploads/default.svg`) and recursive-safe `onerror` handlers across all candidate images.

### H. Mobile App Conversion: Safe Area Top & Bottom Insets & Navigation Clearance
- **Stakeholder Directive**: Add safe top and bottom padding since the system will be converted into a mobile app soon. Resolve the bottom part being cut and blocked by the navigation bar by adding bottom padding to give space to the navigation bar.
- **Viewport-Fit Cover**: Added `viewport-fit=cover` across all 10 HTML view files (`<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">`) allowing webview containers on iOS/Android (Capacitor) to extend under hardware notches, camera punch-holes, and gesture bars while exposing `env(safe-area-inset-*)` values.
- **Top Safe Area Padding (`.top-header`)**:
  - Configured `padding-top: calc(14px + env(safe-area-inset-top, 0px)) !important;` on `.top-header` so sticky headers cleanly flow under native mobile status bars without overlapping app titles or avatars.
- **Bottom Navigation Clearance & Safe Area (`.content-body` & `.mobile-bottom-nav`)**:
  - `.mobile-bottom-nav` sized to `calc(64px + env(safe-area-inset-bottom, 0px))` with internal `padding-bottom: env(safe-area-inset-bottom, 0px)` to keep tabs clear of the native gesture home indicator.
  - `.content-body` on mobile viewports (<900px) configured with `padding-bottom: calc(120px + env(safe-area-inset-bottom, 0px)) !important;` (64px nav bar + 56px breathing room).
  - Resolved previous style collision where inline styles or generic padding collapsed bottom clearance, guaranteeing that submit buttons (e.g. "Save Changes" on `profile.html`, "Review Selected Ballot" on `ballot.html`, and "Cast Official Ballot" on `confirm_vote.html`) are 100% visible and unblocked when scrolled to the bottom.
- **Candidate Bottom Sheet & Login Screen Safe Area**:
  - `.candidate-modal-sheet` bottom padding adjusted to `calc(32px + env(safe-area-inset-bottom, 0px))` for safe button accessibility.
  - `.login-body` on mobile applies `calc(20px + env(safe-area-inset-top, 0px))` and `calc(24px + env(safe-area-inset-bottom, 0px))` ensuring centered balance on mobile screens.

### I. Fullscreen Smooth Circular Loading Animation & Performance Optimization
- **Stakeholder Directive**: Turn the loading indicator into a fullscreen smooth circular animation, also remove blue background to speedup this app.
- **Fullscreen Smooth Circular Loader (`frontend/js/api.js` & `frontend/assets/css/style.css`)**:
  - Replaced the previous top `#network-progress-bar` with `#app-fullscreen-loader` (`.fullscreen-loader-overlay`).
  - Features an ultra-smooth, hardware-accelerated 60/120fps SVG circular spinner (`.circular-spinner`, `.spinner-circle`) with indeterminate dynamic dash offset and continuous rotation keyframes.
  - Backed by a clean, lightweight neutral overlay (`rgba(255, 255, 255, 0.94); backdrop-filter: blur(8px);`) with cubic-bezier fade transitions (`0.22s`).
  - Pre-initialized on DOM ready, automatically managed by network lifecycle in `api.request()`, with `api.showLoading()` / `api.hideLoading()` exposed for explicit screen transitions.
- **Removal of Main Dashboard Blue Background Tint**:
  - Replaced `--bg-light: #e7effa;` with clean, fast neutral `#f8fafc;` across main app layouts to maximize speed and performance.

### J. Login Background Image (`login_bg.png`) Restoration & Glassmorphic Elevation
- **Stakeholder Directive**: Add bg image to the login page (`frontend/assets/images/login_bg.png`), as the login page became too plain without the background graphic.
- **Full Viewport Ballot Background (`frontend/assets/css/style.css`)**:
  - Applied `url('../images/login_bg.png') center center / cover no-repeat fixed;` to `.login-body` layered beneath a subtle dark linear gradient (`rgba(11, 34, 77, 0.28)` to `rgba(7, 23, 54, 0.38)`) to preserve visual depth and rich contrast.
  - On mobile screens (`max-width: 768px`), configured `background-attachment: scroll !important;` to eliminate mobile webview repainting lag and maintain a locked 60/120 FPS scroll performance.
- **Elevated Frosted Glass Card (`.login-card`)**:
  - Structured `.login-card` with frosted glassmorphism (`background: rgba(255, 255, 255, 0.94); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px);`).
  - Added 26px rounded corners, crisp translucent border (`border: 1px solid rgba(255, 255, 255, 0.85);`), and deep elevation shadow (`box-shadow: 0 24px 60px rgba(0, 0, 0, 0.38)`).
  - Floating circular school emblem badge perched on top with vibrant `#2563eb` accent ring and clean drop shadow.
### K. End-to-End PDF Report Generation & Download Engine
- **Stakeholder Directive**: Analyze the download PDF feature in this system and make it working.
- **Root Cause Analysis**:
  - `frontend/admin/results.html` contained an anchor button labeled "Download Audit PDF" that merely navigated to `voting_control.html` without triggering a file download.
  - `backend/api/admin.php` lacked endpoints for on-demand PDF generation or streaming archived backups (`action=download_pdf` and `action=download_backup` returned 400 "Invalid action").
  - `frontend/admin/voting_control.html` used relative file paths (`../../backend/backups/${b.filename}`) which bypassed authentication and failed in Capacitor cross-origin environments.
  - `backend/includes/api_auth.php` only inspected HTTP headers and lacked query parameter fallback support (`?token=...`) needed for direct browser navigation downloads.
  - Output buffering was unmanaged, risking header conflicts and corrupted binary PDF streams.
- **Backend Streaming Implementation (`backend/api/admin.php` & `backend/includes/pdf_export.php`)**:
  - Implemented `action=download_pdf`: Authenticates via `requireApiAdmin()`, generates a certified PDF using `generateElectionPDFReport(true)`, clears output buffers with `ob_end_clean()`, and streams with modern headers (`Content-Type: application/pdf`, `Content-Disposition: attachment; filename="..."`, `Content-Length`, and `Cache-Control`).
  - Implemented `action=download_backup`: Safely streams requested historical PDF reports from `backend/backups/` with strict path traversal validation (`realpath()`, regex filename whitelisting).
  - Added `sanitizePdfText()` helper in `pdf_export.php` to transliterate UTF-8 characters into Windows-1252/Latin-1 for reliable FPDF rendering without crashes or garbled symbols.
  - Enhanced `getBearerToken()` in `backend/includes/api_auth.php` with query parameter fallback (`$_GET['token']`) for direct browser download links.
  - Updated `backend/config/cors.php` to expose `Content-Disposition` and `Content-Length` headers for client fetch operations.
- **Frontend Download Integration (`frontend/js/api.js` & Admin Views)**:
  - Added `api.admin.downloadPdf(filename)`: Automatically triggers the fullscreen smooth circular loader, fetches binary PDF data with Bearer token authentication, extracts suggested filename from `Content-Disposition`, creates an ephemeral Blob URL, and triggers native browser/mobile download.
  - Added `api.admin.getDownloadUrl(filename)`: Generates authenticated direct download URLs with embedded tokens for fallback scenarios.
  - Upgraded `frontend/admin/results.html`: Replaced static navigation link with interactive "Download Audit PDF" button wired to `downloadAuditPdf()`.
  - Upgraded `frontend/admin/voting_control.html`: Added "Export Live Audit PDF" button to header and reset section, and wired all historical backup rows to `downloadArchivedPdf(filename)`.
  - Upgraded `frontend/admin/dashboard.html`: Added "Export Audit PDF" button to the quick action header banner.

### L. Comprehensive Candidate & Voter Account Management Engine
- **Stakeholder Directive**: Enable administrators to add and manage candidates and voter accounts.
- **Backend Administrative REST API Extensions (`backend/api/admin.php`)**:
  - **Dynamic Positions (`action=positions`)**: Returns all official council positions ordered by `display_order`.
  - **Add Candidate (`action=add_candidate`)**: Validates nominee name and position ID. Accepts base64 image data (`profile_image_base64`), decodes and saves uniquely into `frontend/assets/uploads/`, and persists candidate with year level and campaign platform into `candidates`.
  - **Edit Candidate (`action=edit_candidate`)**: Updates existing nominee records, handles position re-assignment, platform edits, and optional new profile photo replacement.
  - **Delete Candidate (`action=delete_candidate`)**: Permanently removes candidate from the election ballot.
  - **Voter Roster & Metrics (`action=voters`)**: Returns complete student voter roster with voting status, phone, grade level, and section, alongside live aggregate turnout metrics (`total`, `voted`, `pending`, `turnout_pct`).
  - **Add Voter (`action=add_voter`)**: Enforces uniqueness of Student ID, username, and email. Hashes passwords using `password_hash(PASSWORD_DEFAULT)`. Defaults credentials to Student ID if unspecified.
  - **Edit Voter (`action=edit_voter`)**: Updates voter metadata, student ID, contact details, grade level, and section with duplicate conflict checks and optional password re-hashing.
  - **Delete Voter (`action=delete_voter`)**: Safely removes voter accounts and cascade-deletes associated ballot records within an atomic database transaction.
  - **Reset Voter Ballot (`action=reset_voter_ballot`)**: Allows administrators to clear an individual voter's cast ballot (`DELETE FROM votes WHERE voter_id = ?; UPDATE users SET has_voted = 0`) within an atomic transaction so the student can recast their vote.
- **Frontend Candidate Management UI (`frontend/admin/candidates.html`)**:
  - Added "+ Add Candidate" primary header button.
  - Dynamic position filter pills ("All Positions", "President", "Vice President", "Secretary") displaying active counts.
  - Add / Edit Candidate modal dialog supporting live photo preview, dynamic position dropdown, and advocacy manifesto editor.
  - Upgraded candidate cards with "View Info" (universal modal), "Edit" (modal prefill), and "Delete" (with confirmation) actions.
- **Dedicated Voter Management Portal (`frontend/admin/voters.html`)**:
  - Summary metric cards displaying Total Registered Voters, Ballots Cast, Pending Ballots, and Live Turnout Rate.
  - Interactive toolbar featuring status filter pills ("All Voters", "Voted", "Pending") and real-time search input matching student IDs, names, usernames, and sections.
  - High-density responsive data table on desktop transforming cleanly into touch-friendly cards on mobile viewports (<768px).
  - Add / Edit Voter modal dialog with automatic username/email suggestions based on Student ID.
  - Action buttons for Edit, Reset Ballot (for voters who have submitted), and Delete.
- **Universal Navigation & Dashboard Integration**:
  - Added `ICONS.voters` SVG icon and integrated "Manage Voters" into admin sidebar and mobile bottom navigation in `frontend/js/components.js`.
  - Linked the "Registered Voters" stat card on `frontend/admin/dashboard.html` directly to `voters.html` and added a dedicated "Voter Management" quick portal card.

---

### M. Railway.app Cloud Hosting, Dockerization & MySQL 8.0 Engine Hardening
- **Stakeholder Directive**: Guide on publishing the backend for Capacitor mobile app integration, evaluate InfinityFree compatibility, and host the system on Railway.app.
- **InfinityFree Feasibility & Incompatibility Assessment**:
  - Rigorously evaluated InfinityFree free PHP hosting for mobile API readiness.
  - Discovered that InfinityFree enforces a mandatory client-side browser verification challenge (setting an obfuscated `__test` cookie via AES JavaScript execution before granting access).
  - Because native Capacitor mobile applications (`fetch()` / WebViews) cannot execute the external challenge cookie verification loop, all mobile API requests are rejected with **HTTP 403 Forbidden**. InfinityFree was ruled out and modern containerized PaaS (Railway.app) was selected.
- **Production Container Architecture (`Dockerfile`, `entrypoint.sh`, `railway.json`)**:
  - Packaged the application into a standalone Docker container using `php:8.2-apache`.
  - **Resolved Apache MPM Conflict (`AH00534`)**: Standard Debian Apache images on Railway encounter Multi-Processing Module collision (`AH00534: apache2: Configuration error: More than one MPM loaded`) caused by conflicting `mpm_event` and `mpm_worker` modules. Created a dedicated startup script (`entrypoint.sh`) that strictly unloads conflicting MPMs and enforces `mpm_prefork` before starting Apache.
  - **Dynamic Port Assignment**: Configured `entrypoint.sh` to dynamically adapt Apache's `ports.conf` and `<VirtualHost>` configuration to Railway's assigned container `$PORT` (defaulting to 8080).
  - **Config as Code (`railway.json`)**: Configured `"builder": "DOCKERFILE"` with automatic restart policies.
- **Zero-Config Cloud Database Provisioning (`backend/config/database.php` & `schema.sql`)**:
  - Configured `database.php` to auto-detect Railway's `MYSQL_URL` and discrete variables (`MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT`), with seamless fallback to local XAMPP.
  - Built-in automatic table provisioning from `backend/database/schema.sql` on virgin databases (auto-creating `users`, `positions`, `candidates`, `votes`, and `election_settings` with 31 seed voters and default admin).
  - Created cloud diagnostic health endpoint (`backend/api/health.php`) providing status, environment checks, and table existence verification.
- **MySQL 8.0 `ONLY_FULL_GROUP_BY` Compatibility Hardening**:
  - Railway's managed MySQL 8.0 enforces `sql_mode=only_full_group_by` by default.
  - Rewrote analytical queries in `backend/includes/functions.php`:
    - `getVotesTimelineData()`: Encapsulated ballot aggregations into a strict subquery (`SELECT voter_id, MIN(created_at) as ballot_time FROM votes GROUP BY voter_id`) to ensure full compliance across all SQL engines.
    - `getVotesByPositionData()`: Explicitly included all non-aggregated columns (`p.id, p.position_name, p.display_order`) in the `GROUP BY` clause.
  - Injected session-level SQL mode adjustment into PDO connection setup (`SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));`).
- **Zero HTML Leakage in API Responses & Client Resilience**:
  - Added `ini_set('display_errors', '0')` and registered a global `set_exception_handler` in `backend/config/cors.php` ensuring uncaught exceptions always output clean JSON (`{"success": false, "error": "..."}`) with HTTP 500 instead of raw HTML error banners (`<br /><b>Fatal error...`).
  - Upgraded `api.request()` in `frontend/js/api.js` to safely inspect response text and strip HTML tags upon parse failure, preventing uncaught promise syntax crashes.
  - Added try-catch fallback error card with an interactive "Retry" button on `frontend/admin/dashboard.html`.

---

### N. Capacitor Mobile Installation & Isolated Frontend Packaging (v8.5.2)
- **Stakeholder Directive**: Install Capacitor to the project ensuring that **only the frontend** is bundled in the mobile app, using the latest versions.
- **Node.js Environment Modernization**:
  - Capacitor 8 CLI requires Node.js `>=22.0.0`. Switched Node environment from `v20.20.2` to `v24.12.0` using Windows `nvm use 24.12.0`.
- **Latest Capacitor 8 Toolchain**:
  - Installed `@capacitor/core@8.5.2`, `@capacitor/android@8.5.2`, and `@capacitor/ios@8.5.2` as core dependencies.
  - Installed `@capacitor/cli@8.5.2` as development dependency.
- **Isolated Frontend WebDir Configuration (`capacitor.config.json`)**:
  - Configured `"webDir": "frontend"` so that Capacitor only extracts and packages the static presentation files (`frontend/index.html`, `frontend/login.html`, `frontend/admin/`, `frontend/voter/`, `frontend/assets/`, `frontend/js/`).
  - Completely excludes backend PHP scripts, database schemas, Docker files, and server scripts from the mobile application package.
  - Configured `androidScheme: "https"` and `cleartext: true` for seamless connectivity to the Railway remote API (`REMOTE_API_URL`).
- **Native Android Project Initialization (`android/`)**:
  - Initialized native Android container via `npx cap add android`.
  - Verified asset mirroring: `android/app/src/main/assets/public/` contains strictly pure frontend client files and zero server-side code.
- **Client Route Portability (`frontend/js/api.js`)**:
  - Implemented `api.redirectToLogin()` in `api.js` ensuring that 401 unauthenticated redirects cleanly navigate to `../login.html` inside native webviews without expecting a `/frontend/` root path.
- **Build Isolation Guardrails (`.dockerignore` & `.gitignore`)**:
  - Added `node_modules/`, `android/`, `ios/`, and `.capacitor/` to `.dockerignore` to prevent Docker image bloat and keep Railway PaaS container deployments fast.
  - Added `.gitignore` to prevent tracking of dependencies and build caches.

---

### O. Elastic Scroll Elimination & Dark System Bars Implementation
- **Stakeholder Directive**: Remove the elastic bounce effect on mobile and make the status bar and navigation bar dark so they are clearly visible framing the white background of the app.
- **Eradication of Mobile Elastic Overscroll**:
  - **CSS Layer (`frontend/assets/css/style.css`)**:
    - Replaced `overscroll-behavior-y: contain` with `overscroll-behavior: none !important; overscroll-behavior-y: none !important; overscroll-behavior-x: none !important;` on `html, body`.
    - Set `-webkit-overflow-scrolling: auto !important;` across all scrollable containers (`.main-wrapper`, `.content-body`, `.sidebar`, `.candidate-modal-sheet`, `.app-container`) completely disabling WebKit/iOS and Chromium rubber-band bouncing.
  - **Native Android WebView Layer (`MainActivity.java`)**:
    - Injected `getBridge().getWebView().setOverScrollMode(View.OVER_SCROLL_NEVER);` inside `onStart()`, disabling the Android 12+ (API 31+) system-wide stretch/elastic bounce animation on the WebView.
- **Dark Status Bar & Dark Navigation Bar Configuration**:
  - **Android Native Theme (`styles.xml`)**:
    - Configured `android:statusBarColor` to `#0f172a` (Tomorrow Vote deep navy slate).
    - Configured `android:navigationBarColor` to `#0f172a`.
    - Set `android:windowLightStatusBar` and `android:windowLightNavigationBar` to `false` ensuring system icons (clock, battery, Wi-Fi, back, home, recents) render in high-contrast white.
  - **Android Runtime Window Controller (`MainActivity.java`)**:
    - Enforced `FLAG_DRAWS_SYSTEM_BAR_BACKGROUNDS` with `setStatusBarColor("#0f172a")` and `setNavigationBarColor("#0f172a")`.
    - Used `WindowInsetsControllerCompat` to enforce `setAppearanceLightStatusBars(false)` and `setAppearanceLightNavigationBars(false)`.
  - **Capacitor Configuration & Plugin (`capacitor.config.json` & `@capacitor/status-bar@8.0.4`)**:
    - Installed official `@capacitor/status-bar@8.0.4`.
    - Configured `plugins.StatusBar` with `overlaysWebView: false`, `style: "DARK"`, and `backgroundColor: "#0f172a"`.
  - **Web Document Standards**:
    - Injected `<meta name="theme-color" content="#0f172a">` and `<meta name="apple-mobile-web-app-status-bar-style" content="black">` across all 11 HTML view documents in `frontend/`.

---

## 3. Current Directory & File Inventory

```
c:/xampp/htdocs/voting/
├── android/                            # Native Android Studio Project (Capacitor Container)
│   ├── app/src/main/assets/public/     # Pure frontend assets strictly mirrored
│   └── build.gradle                    # Android Gradle build scripts
├── backend/
│   ├── api/
│   │   ├── .htaccess               # FastCGI Bearer authorization & CORS rules
│   │   ├── admin.php               # Administrative management JSON API (positions, candidates, voters, control, results)
│   │   ├── auth.php                # Authentication & identity JSON API
│   │   ├── health.php              # Cloud health check & deployment diagnostic endpoint
│   │   └── voter.php               # Student voter operations JSON API
│   ├── config/
│   │   ├── cors.php                # Permissive CORS headers, preflight handler & JSON exception handling
│   │   ├── database.php            # MySQL PDO connection singleton (Railway MYSQL_URL + XAMPP auto-detect)
│   │   └── mail.php                # SMTP and HTTPS mail delivery configuration
│   ├── database/
│   │   ├── schema.sql              # Clean database schema definition with auto-init support
│   │   └── voting_db_import.sql    # Complete SQL seed with positions & 31 student accounts
│   ├── includes/
│   │   ├── api_auth.php            # Bearer token validation middleware
│   │   ├── ballot_receipt.php      # Bank statement style electronic ballot receipt generator (e-BS)
│   │   ├── encryption.php          # AES-256-CBC cryptographic ballot utilities
│   │   ├── fpdf.php                # FPDF 1.86 vector PDF engine
│   │   ├── fpdf_protection.php     # 128-bit/40-bit RC4 stream encryption with OpenSSL & pure-PHP fallback
│   │   ├── functions.php           # Election schedules, result tallies & MySQL 8 compliant queries
│   │   ├── mailer.php              # Multi-channel mailer (SMTP 465/587, HTTPS Webhook, Brevo) with attachments
│   │   ├── phpmailer/              # Bundled PHPMailer 7.1.1 library
│   │   ├── voter_masterlist.php    # Clean parsed dataset of 53 voters from class masterlist
│   │   └── pdf_export.php          # Certified election audit report generator
│   └── backups/                    # Storage directory for certified PDF archives
│
├── frontend/                       # Static Decoupled Client (Mirrored to Capacitor WebDir)
│   ├── admin/
│   │   ├── candidates.html         # Candidate CRUD management & platform verification
│   │   ├── dashboard.html          # Administrative analytics & quick actions
│   │   ├── results.html            # Real-time results, progress bars & rankings
│   │   ├── voters.html             # Voter account registration, credential editing & ballot reset
│   │   └── voting_control.html     # Election status switcher & archival triggers
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css           # Global stylesheet & design variables
│   │   ├── images/
│   │   │   ├── logo.png            # Tomorrow Vote circular emblem
│   │   │   └── login_bg.png        # Official ballot box voting graphic
│   │   └── uploads/                # Uploaded candidate profile photos
│   ├── js/
│   │   ├── api.js                  # Frontend API network wrapper & token persistence
│   │   └── components.js           # Layout injector, responsive drawer & SVG icons
│   ├── voter/
│   │   ├── ballot.html             # Digital ballot selection cards with SVG checkmarks
│   │   ├── candidates.html         # Candidate platforms & profiles overview
│   │   ├── confirm_vote.html       # Ballot verification, biometrics & cryptographic submission
│   │   ├── dashboard.html          # Voter greeting, status badge, e-Statement notice & quick vote CTA
│   │   └── profile.html            # Student profile & display name manager
│   ├── index.html                  # Frontend root redirector to login.html
│   └── login.html                  # Official glassmorphism sign-in screen with 2FA OTP step
│
├── Masterlist BSIT31008-IS (1).xlsx # Authoritative class roster & voter emails
├── capacitor.config.json           # Capacitor configuration (webDir: "frontend")
├── package.json                    # Project metadata & Capacitor 8 dependencies
├── package-lock.json               # Deterministic dependency tree lock
├── index.php                       # Root redirector to frontend/login.html
├── Dockerfile                      # Production container recipe for Railway.app & cloud PaaS
├── entrypoint.sh                   # Startup container initializer (MPM fix, port binding, masterlist sync)
├── railway.json                    # Railway deployment & builder configuration as code
├── .dockerignore                   # Docker build exclusions (node_modules, android, ios)
├── .gitignore                      # Git exclusions (node_modules, .capacitor)
├── .gitattributes                  # Git line-ending normalization (LF for shell scripts)
├── RULES.md                        # Architectural rules & operating principles
├── SKILL.md                        # Skill definition & implementation guide
├── structure.md                    # System architecture & component blueprint
└── context.md                      # Comprehensive project memory log (This Document)
```

### G. Two-Factor Email OTP Verification & Cloud Firewall Mitigation
- **Two-Factor Voter Verification**:
  - Voter accounts require a 6-digit numeric OTP (`random_int(100000, 999999)`) with a 10-minute expiry window on each login attempt.
  - Verification codes are persisted in `users.login_otp` and `users.login_otp_expires_at`, with automated schema self-healing on virgin databases.
  - Direct login for administrative users without OTP to ensure immediate administrative access.
- **Cloud Egress Firewall Outbound SMTP Mitigation (Error Code 110)**:
  - Standard PaaS providers (e.g. Railway Free/Hobby tiers) block outbound TCP ports 25, 465, and 587 to prevent spam abuse, causing raw SMTP handshakes to time out (`code: 110`).
  - **Graceful Fallback Mode**: The backend catches network timeouts and provides a fallback code (`dev_otp`) with a clear cloud hosting notice banner on the login UI, preventing voters and testers from being locked out of elections.
  - **HTTPS Port 443 Support**: Added native support in `backend/includes/mailer.php` for `MAIL_WEBHOOK_URL` (Google Apps Script Web App / HTTPS Webhooks) and `BREVO_API_KEY` (Brevo HTTPS REST API) which communicate over standard outbound HTTPS (Port 443), completely bypassing platform SMTP port restrictions.

### H. Pre-Vote Biometric Authentication & Password Fallback
- **Pre-Vote Native Biometrics via Capacitor**:
  - Integrated `@capgo/capacitor-native-biometric` (v8.7.0) to authenticate student voters before sealing their ballot on `confirm_vote.html`.
  - Prompts device-native biometric prompt (Fingerprint / Face ID) via Android BiometricPrompt API.
  - Automatically queries hardware availability via `api.biometrics.isAvailable()` and displays an emerald "Biometric Authentication Enabled" shield badge on compatible mobile devices.
- **In-App Password Verification Fallback**:
  - If biometric hardware is unavailable, not enrolled, cancelled by the user, or fails verification, the voter is presented with an elevated Password Verification Modal.
  - Voter inputs their password, which is verified against `POST /backend/api/voter.php?action=verify_password` and validated atomically during `cast_vote`.
  - Upon successful verification, the ballot is sealed, encrypted with AES-256-CBC, and committed.

### I. Android Edge-to-Edge Native Display Support
- **Full Viewport Immersion**:
  - Enabled edge-to-edge layout where app content draws seamlessly underneath transparent system status and navigation bars.
  - Configured with `viewport-fit=cover` and CSS safe-area padding (`env(safe-area-inset-top)` and `env(safe-area-inset-bottom)`) applied to `.top-header`, `.mobile-bottom-nav`, and bottom content clear zones.
  - Eliminates visual letterboxing, black cutouts, and bar overlap across all modern Android devices.

### J. Online Banking e-Statement Password-Protected Ballot Delivery & Download
- **Electronic Statement of Ballot (e-BS) PDF Generator (`backend/includes/ballot_receipt.php`)**:
  - Replicates authentic online banking statement layouts (BDO, BPI, UnionBank, GCash, Metrobank):
    - Two-column institutional letterhead with statement reference (`REF: BS-2026-[student_id]-[hash]`).
    - Account overview card: Account Holder Name, Student Account No, Academic Program & Section, Timestamp, and Polling Precinct.
    - Itemized Ballot Transaction Ledger with transaction tracking numbers (`TXN-01`, etc.), electoral contests, selected candidates, and `COUNTED` status stamps.
    - Total contests voted and ledger reconciliation summary row (`LEDGER BALANCE: RECONCILED`).
    - Bank-grade cryptographic audit box featuring 64-character SHA-256 verification hash, AES-256-CBC certification, and statutory disclaimers.
- **Dual-Key Document Password Protection (`backend/includes/fpdf_protection.php`)**:
  - Standard 128-bit/40-bit RC4 stream encryption with OpenSSL and pure-PHP fallback engine.
  - Dual unlock keys: **Primary password** is the voter's Student ID (`users.student_id`); **Alternate password** is their Date of Birth in `YYYYMMDD` format (`users.date_of_birth`).
- **Online Banking Notification Email (`backend/includes/mailer.php`)**:
  - Dispatched immediately upon vote commitment via PHPMailer with MIME `Content-Disposition: attachment; filename="eStatement_Ballot_[student_id].pdf"`.
  - Structured as an official electronic statement advice email:
    - Dedicated attachment preview card with `.PDF` icon.
    - Prominent amber password reminder box with large monospace password badge and 3-step opening instructions.
    - Electronic statement summary table and banking-grade anti-fraud advisory.
- **Non-Blocking Execution & Direct Download**:
  - Email dispatch failures (e.g. cloud host port blocks) are safely logged without rolling back the committed vote transaction.
  - Added `GET /backend/api/voter.php?action=download_receipt` endpoint enabling direct PDF download from the voter dashboard or app.
  - Post-voting dashboard banner provides immediate visual confirmation and one-click statement download.

### K. Excel Masterlist Ingestion & Automated Railway Database Synchronization
- **Masterlist Data Extraction (`Masterlist BSIT31008-IS (1).xlsx`)**:
  - Ingested the entire 53-student class roster for section BSIT 31008.
  - Extracted 36 verified student emails (Gmail) and authoritative Student Numbers (e.g. `240104785` Alvarez, `240114837` Rañola, `240114524` Antonio, `240108957` Albos, etc.).
  - Generated `backend/includes/voter_masterlist.php` containing structured PHP records for all 53 voters.
- **Automated Railway Cloud Database Sync (`backend/config/database.php`)**:
  - Implemented `autoSyncMasterlistVoters($pdo)` running automatically upon PDO connection on both local and Railway environments.
  - **Existing Account Email Patching**: Automatically detects existing voter accounts (previously having dummy `@student.bcp.edu.ph` addresses) and updates their `email` to their real Gmail addresses from the Excel sheet.
  - **New Voter Auto-Provisioning**: Inserts all missing students into `users` with hashed passwords, Student IDs, and default role `'voter'`.
  - **Execution Guard**: Uses `election_settings.masterlist_voters_synced_v1` flag ensuring synchronization executes exactly once per database lifecycle with zero recurring query overhead.
- **Container Pre-Boot Hook (`entrypoint.sh`)**:
  - Injected pre-startup CLI sync (`php -r "require_once '/var/www/html/backend/config/database.php'; getDBConnection();"`) into `entrypoint.sh` executing right before Apache starts.
- **Dual Password Authentication Fallback**:
  - Upgraded password verification in `auth.php` and `voter.php` (`action=verify_password` and `action=cast_vote`): accepts both the hashed initial password (`FirstName + StudentNumber`) and the raw `StudentNumber` as valid initial credentials, preventing student login friction.
- **Database Seed Modernization (`schema.sql` & `voting_db_import.sql`)**:
  - Updated default database dumps to seed all 53 voters with their real emails and Student IDs.
- **Diagnostic Metrics (`backend/api/health.php` & `backend/api/admin.php`)**:
  - Added `total_voters`, `total_admins`, `voters_with_real_email`, and `masterlist_synced` status indicators to `health.php`.
  - Added `action=sync_masterlist` administrative endpoint in `admin.php` for manual re-triggering.

---

## 4. Key Operating Principles & Constraints
1. **Headless Discipline**: PHP files reside strictly in `backend/` and output JSON or PDF documents. All HTML rendering is performed by the `frontend/` static client.
2. **Capacitor Mobile Ready**: All asset and API paths resolve dynamically. State is managed via `localStorage` and `sessionStorage`.
3. **Hardware Biometrics via Capacitor**: Uses modern OS-level BiometricPrompt (fingerprint/face) with password fallback; zero external AI/camera facial recognition dependencies.
4. **SVG Icon Standard**: No raw emojis as navigation or action icons; strictly utilize inline vector SVGs.
5. **One Student, One Vote**: Enforced atomically via MySQL transactions and `has_voted` flags.
6. **E-Statement Security & Confidentiality**: Ballot receipts sent via email must always be password-protected with the student's unique credentials. Voting commitments must never be aborted if an external mail service experiences latency or network timeouts.
7. **Masterlist Synchronization**: The system automatically aligns the voter database with the authoritative class masterlist on startup and cloud deployment, ensuring all voters have their verified email addresses for 2FA OTP delivery.



