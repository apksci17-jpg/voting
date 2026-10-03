# Online Voting System (OVS) — Architecture & Project Structure

## 1. Overview & System Intent

The **Online Voting System (OVS)** is an automated, secure digital voting platform built for institutional student council elections. It provides cryptographic vote integrity, automated ballot tabulation, audit reporting, and role-based access control.

The system is engineered as a **decoupled, headless architecture**:
- **Headless Backend**: A lightweight, stateless PHP RESTful JSON API server.
- **Static Decoupled Frontend**: Pure HTML5, CSS3, and modern Vanilla JavaScript, optimized for web deployment and native mobile compilation via **Capacitor** (iOS & Android).
- **Stateless Bearer Authentication**: Custom 64-character Bearer token stored in `localStorage` and sent via `Authorization: Bearer <token>` headers, completely eliminating cookie session limitations across cross-origin mobile webviews.
- **Biometric-Free Clean Cryptography**: Streamlined voting flow without external AI biometric dependencies. Ballots are encrypted and recorded using AES-256-CBC database payloads.

---

## 2. Directory Layout (Headless / Capacitor-Ready Architecture)

```
c:/xampp/htdocs/voting/
│
├── .agents/                        # Agent Rules & Skills
│   ├── rules/
│   │   └── ovs-rules.md            # Agent behavioral & architectural guidelines
│   └── skills/
│       └── ovs-implementation/
│           └── SKILL.md            # Implementation reference cheatsheet
│
├── android/                        # Native Android Studio Project (Capacitor Container)
│   ├── app/src/main/assets/public/ # Pure frontend static assets (isolated mirror)
│   └── build.gradle                # Android Gradle build configuration
│
├── backend/                        # Headless PHP REST API & Database Services
│   ├── api/                        # JSON API Endpoints
│   │   ├── .htaccess               # FastCGI Bearer authorization & CORS rules
│   │   ├── admin.php               # Admin endpoints (/dashboard, /control, /results, /candidates, /voters)
│   │   ├── auth.php                # Authentication (/login, /logout, /me)
│   │   ├── health.php              # Cloud health check & deployment diagnostic endpoint
│   │   └── voter.php               # Voter endpoints (/dashboard, /candidates, /cast, /profile)
│   │
│   ├── config/                     # Configuration & Infrastructure
│   │   ├── cors.php                # Cross-Origin Resource Sharing (CORS) headers & JSON error handling
│   │   └── database.php            # MySQL PDO connection singleton (Railway & XAMPP auto-detect)
│   │
│   ├── database/                   # Schema & Database Migrations
│   │   ├── schema.sql              # Clean database schema definition with auto-init support
│   │   └── voting_db_import.sql    # Complete SQL dump with positions, candidates & voters
│   │
│   ├── includes/                   # Core Server Utilities
│   │   ├── api_auth.php            # Bearer token validation middleware
│   │   ├── functions.php           # Business logic, schedule evaluators & MySQL 8 compliant tallies
│   │   ├── encryption.php          # AES-256-CBC ballot cryptographic functions
│   │   ├── pdf_export.php          # Official PDF report generator & backup archiver
│   │   └── fpdf.php                # Vector PDF rendering engine
│   │
│   └── backups/                    # Automated PDF Audit Archive Storage
│
├── frontend/                       # Static Decoupled Client (Mirrored to Capacitor WebDir)
│   ├── index.html / login.html     # Unified Sign-in screen with glassmorphism & password toggle
│   │
│   ├── js/                         # Client Logic & Services
│   │   ├── api.js                  # Dynamic API network client & token persistence
│   │   └── components.js           # Shared layout injector, responsive drawer & SVG icons
│   │
│   ├── assets/                     # Static Design Assets
│   │   ├── css/
│   │   │   └── style.css           # Global stylesheet & design tokens
│   │   ├── images/
│   │   │   ├── logo.png            # Official institutional emblem
│   │   │   └── login_bg.png        # Official ballot box voting graphic
│   │   └── uploads/                # Candidate profile photos & uploaded graphics
│   │
│   ├── admin/                      # Administrative Single Page / Multi-Page Views
│   │   ├── dashboard.html          # Admin analytics, metric cards & error resilience
│   │   ├── voting_control.html     # Live status switcher (OPEN/CLOSED) & archival controls
│   │   ├── candidates.html         # Candidate CRUD management & platform verification
│   │   ├── voters.html             # Voter account registration, credential editing & ballot reset
│   │   └── results.html            # Real-time tally, leading candidate badges & rankings
│   │
│   └── voter/                      # Student Voter Portal Views
│       ├── dashboard.html          # Voter greeting, election schedule & status badge
│       ├── candidates.html         # Candidate profiles, platforms & positions
│       ├── ballot.html             # Digital ballot form with selectable cards & checkmarks
│       ├── confirm_vote.html       # Ballot summary review & final cryptographic submission
│       └── profile.html            # Voter identity view & display name updater
│
├── capacitor.config.json           # Capacitor configuration (webDir: "frontend")
├── package.json                    # Project metadata & Capacitor 8 dependencies
├── package-lock.json               # Deterministic dependency tree lock
├── index.php                       # Root entry point (clean redirect to frontend/login.html)
├── Dockerfile                      # Production container recipe for Railway.app & cloud PaaS
├── entrypoint.sh                   # Startup container initializer (MPM fix, port binding, permissions)
├── railway.json                    # Railway deployment & builder configuration as code
├── .dockerignore                   # Docker build exclusions (node_modules, android, ios)
├── .gitignore                      # Git exclusions (node_modules, .capacitor)
├── .gitattributes                  # Git line-ending normalization (LF for shell scripts)
├── RULES.md                        # Architecture & Implementation Rules
├── SKILL.md                        # Skill Specification
├── context.md                      # Comprehensive Project History & Technical Context
└── structure.md                    # Project Architectural Blueprint (This Document)
```

---

## 3. Core Component Architecture

### 3.1 Authentication & Security Architecture
- **Stateless Bearer Token Flow**:
  1. Voter/Admin submits credentials (`student_id`/`username`/`email` + `password`) to `POST /backend/api/auth.php?action=login`.
  2. Server verifies hashed or plaintext passwords, generates a cryptographically secure 64-character token (`bin2hex(random_bytes(32))`), and stores it in `users.session_token`.
  3. Client saves token in `localStorage.setItem('voter_token', token)`.
  4. Subsequent API calls attach header `Authorization: Bearer <token>`.
- **CORS Handling**: `backend/config/cors.php` handles preflight `OPTIONS` requests and sets permissive headers, enabling Capacitor mobile apps (`capacitor://localhost`) or remote frontends to communicate seamlessly with the backend.

### 3.2 UI Design System, Typography & Animations
- **Typography**: Powered by Google Fonts **Plus Jakarta Sans** (headings, branding, action cards) and **Inter** (tabular data, metrics, body), offering modern aesthetics and crisp legibility.
- **Fixed Sidebar & Scroll Isolation**:
  - Desktop (>=900px): Fixed `260px` sidebar locked via `position: fixed !important; top: 0; left: 0; width: 260px; height: 100vh; overflow-y: auto;`. The main content is offset by `margin-left: 260px !important; width: calc(100% - 260px) !important;` so that main view scrolling operates in total isolation from the navigation.
  - Mobile (<900px): Transforms into a responsive off-canvas slide-out drawer with backdrop tap-to-dismiss overlay and mobile menu button.
- **Smooth Animation Suite & Fullscreen Circular Loading**:
  - Keyframe animations (`fadeInUp`, `scaleIn`, `pulseDot`, `spinnerRotate`, `spinnerDash`) applied to all view templates and cards.
  - Centered hardware-accelerated SVG circular indeterminate loader (`#app-fullscreen-loader`) with smooth cubic-bezier transitions on a clean neutral backdrop (`rgba(255, 255, 255, 0.94)`).
  - Login screen features the official ballot background graphic (`login_bg.png`) layered under a subtle depth gradient with an elevated frosted glassmorphism card (`backdrop-filter: blur(18px)`), while inner app portals utilize clean, high-performance neutral slate backgrounds (`#f8fafc`) for instantaneous First Contentful Paint.
  - Automatic shimmer skeleton placeholders (`api.showSkeleton()`) preventing blank or jumping layout shifts over slow connections.
- **Vector SVG Icons**: All legacy emoji characters replaced with clean, lightweight inline vector **SVG icons** (24x24 & 18x18 viewBoxes).

### 3.3 Administrative Management Subsystem
- **Candidate Administration (`frontend/admin/candidates.html`)**:
  - Full CRUD operations: Add new candidates with dynamic position dropdowns (`action=positions`), edit existing nominee profiles, and delete candidates.
  - Profile Photo Pipeline: Instant client-side file picker preview with base64 encoding (`profile_image_base64`) decoded and stored in `frontend/assets/uploads/`.
  - Position filter pills: Quick filtering by running position (President, Vice President, Secretary, etc.) with active candidate tallies.
  - Interactive cards featuring "View Info" (universal modal), "Edit" (form modal prefill), and "Delete" (confirmation dialog).
- **Voter Account Administration (`frontend/admin/voters.html`)**:
  - Real-time aggregate metric cards: Total Registered Voters, Ballots Cast, Pending Submissions, and Participation Turnout Rate.
  - Institutional Voter Registration: Add voters with unique Student ID, auto-suggested username/email, grade level, section, and hashed passwords via `password_hash(PASSWORD_DEFAULT)`.
  - Credential & Profile Editing: Update voter metadata or reset student passwords securely.
  - Individual Ballot Reset (`action=reset_voter_ballot`): Clears a specific voter's cast votes and resets `has_voted = 0` within an atomic transaction, allowing re-voting if authorized by election officials.
  - Safe Account Deletion (`action=delete_voter`): Cascade-deletes voter account and associated vote records inside an atomic transaction.
  - Search & Status Filtering: Instant client-side live search and status filter pills ("All Voters", "Voted", "Pending").
- **Voting Period Control**: Allows administrators to toggle polls between `OPEN` and `CLOSED`, with automated time synchronization based on configured schedules.
- **Certified PDF Archiving & On-Demand Streaming**:
  - Live election audit report generation via `FPDF_Extended`, capturing winner tallies, voter turnout percentages, and SHA-256 verification signatures.
  - Endpoints `action=download_pdf` and `action=download_backup` stream certified binary reports directly to client browsers and Capacitor webviews with admin token authorization and clean buffer flushing.
  - Client helper `api.admin.downloadPdf()` activates the fullscreen circular loader during download generation and triggers native device file downloads via Blob URLs.

### 3.4 Student Voter Subsystem
- **Digital Ballot**: Interactive candidate selection cards with active ring states and animated SVG checkmarks.
- **Ballot Persistence**: Uses client-side `sessionStorage` between `ballot.html` and `confirm_vote.html` before final server submission.
- **Tamper-Evident Encryption**: Votes are encrypted using AES-256-CBC and committed inside a MySQL transaction that flags the voter account as `has_voted = 1`, enforcing the strictly audited *One-Student, One-Vote* mandate.

### 3.5 Native Mobile Experience & Universal Candidate Modal
- **Universal Candidate Modal & Bottom Sheet**:
  - Activated by clicking/tapping any candidate card or dedicated info button across voter and admin pages.
  - Presents candidate portrait, certified nominee badge, position pill, formatted advocacy & platform manifesto, and key leadership pillar badges.
  - Dynamically features "Select on Ballot" direct-action button on `ballot.html` (programmatically selects radio input, smoothly centers card, and closes modal) or "Proceed to Ballot" link on `candidates.html`.
  - On mobile (<768px), presents as an iOS/Android native slide-up bottom sheet with top drag handle pill (`.sheet-drag-handle`) and safe-area insets.
- **Mobile Bottom Navigation Bar**:
  - On mobile (<900px), presents a fixed bottom tab bar with 5 destinations for admins (Overview, Control, Candidates, Voters, Results) and 4 for voters (Dashboard, Candidates, Ballot, Profile), active indicator pills, and backdrop blur.
- **Touch Tuning & Damping**:
  - Elimination of 300ms tap delay via `touch-action: manipulation`.
  - Suppression of blue tap highlight rectangles via `-webkit-tap-highlight-color: transparent`.
  - Accidental text selection prevention via `user-select: none`.
  - Physical haptic-like tap damping (`transform: scale(0.97)` on `:active`) and cubic-bezier elevation on hover (`translateY(-4px) scale(1.008)`).
- **Safe Area Insets & Bottom Navigation Clearance**:
  - `viewport-fit=cover` deployed across all HTML documents for Capacitor container edge-to-edge rendering.
  - Sticky `.top-header` applies `padding-top: calc(14px + env(safe-area-inset-top, 0px))` clearing the native status bar, notch, and dynamic island.
  - Fixed `.mobile-bottom-nav` applies `padding-bottom: env(safe-area-inset-bottom, 0px)` keeping tabs above the native home indicator.
  - Main view container (`.content-body`) on mobile viewports (<900px) enforces `padding-bottom: calc(120px + env(safe-area-inset-bottom, 0px)) !important;` (64px navigation bar + 56px whitespace margin) guaranteeing that bottom cards, forms, and submit buttons (e.g., Save Changes, Review Ballot) are never cut off or obstructed by the navigation bar.

### 3.6 Cloud Infrastructure, Docker & Database Provisioning
- **Production Containerization (`Dockerfile`, `entrypoint.sh`, `railway.json`)**:
  - Encapsulated in lightweight `php:8.2-apache` container with `pdo_mysql`.
  - Dedicated pre-start script (`entrypoint.sh`) disables conflicting `mpm_event` and `mpm_worker` Apache modules, forcing `mpm_prefork` to eradicate `AH00534` crashes.
  - Dynamically updates Apache's `ports.conf` and `<VirtualHost>` configuration to bind to Railway's assigned `$PORT` (defaulting to 8080).
  - Explicitly configured with `"builder": "DOCKERFILE"` in `railway.json` for deterministic cloud builds.
- **Zero-Config Database Discovery**:
  - `backend/config/database.php` auto-detects Railway's `MYSQL_URL` and individual connection parameters (`MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT`), with seamless fallback to local XAMPP.
  - Automatically provisions fresh database schemas and seed accounts from `backend/database/schema.sql` on virgin instances.
- **MySQL 8.0 `ONLY_FULL_GROUP_BY` Resilience**:
  - Timeline and position aggregations in `backend/includes/functions.php` utilize strict SQL subqueries with `MIN(created_at)` and explicit grouping, conforming strictly to modern MySQL 8.x standards.
  - Injected session-level SQL mode adjustment into PDO connections (`SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));`).
- **Clean JSON API Error Normalization**:
  - Disabled `display_errors` in API routes and registered a global exception handler in `backend/config/cors.php`, ensuring all errors output standard JSON (`{"success": false, "error": "..."}`) with HTTP 500.
  - Enhanced client-side `api.request()` in `frontend/js/api.js` to strip HTML error tags and throw readable exceptions if non-JSON output is ever returned.



