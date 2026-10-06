# Online Voting System (OVS) — Architecture & Project Structure

## 1. Overview & System Intent

The **Online Voting System (OVS)** is an automated, secure digital voting platform built for institutional student council elections. It provides cryptographic vote integrity, automated ballot tabulation, audit reporting, and role-based access control.

The system is engineered as a **decoupled, headless architecture**:
- **Headless Backend**: A lightweight, stateless PHP RESTful JSON API server.
- **Static Decoupled Frontend**: Pure HTML5, CSS3, and modern Vanilla JavaScript, optimized for web deployment and native mobile compilation via **Capacitor** (iOS & Android).
- **Stateless Bearer Authentication & 2FA OTP**: Custom 64-character Bearer token stored in `localStorage` and sent via `Authorization: Bearer <token>` headers, completely eliminating cookie session limitations across cross-origin mobile webviews. Protected by a 6-digit email OTP (One-Time Password) on voter sign-in.
- **Hardware Native Biometrics with In-App Password Fallback**: Pre-vote identity verification via `@capgo/capacitor-native-biometric` (fingerprint/face) on mobile devices, with seamless fallback to secure password confirmation.
- **Online Banking e-Statement (e-BS) Ballot Delivery**: Automatically generates a bank-statement-style certified electronic ballot receipt encrypted with RC4 dual-key protection (Student ID + Date of Birth YYYYMMDD), attached directly to the voter's confirmation email and available for on-demand in-app download.
- **Clean Cryptography**: Clean, direct voting flow without external AI facial recognition dependencies. Ballots are encrypted and recorded using AES-256-CBC database payloads.

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
│   ├── app/src/main/res/           # Native styles, transparent edge-to-edge & system bar themes
│   └── build.gradle                # Android Gradle build configuration
│
├── backend/                        # Headless PHP REST API & Database Services
│   ├── api/                        # JSON API Endpoints
│   │   ├── .htaccess               # FastCGI Bearer authorization & CORS rules
│   │   ├── admin.php               # Admin endpoints (/dashboard, /control, /results, /candidates, /voters)
│   │   ├── auth.php                # Authentication (/login, /verify_otp, /resend_otp, /logout, /me)
│   │   ├── health.php              # Cloud health check & deployment diagnostic endpoint
│   │   └── voter.php               # Voter endpoints (/dashboard, /candidates, /verify_password, /cast, /download_receipt, /profile)
│   │
│   ├── config/                     # Configuration & Infrastructure
│   │   ├── cors.php                # Cross-Origin Resource Sharing (CORS) headers & JSON error handling
│   │   ├── database.php            # MySQL PDO connection singleton (Railway & XAMPP auto-detect)
│   │   └── mail.php                # SMTP and HTTPS mail delivery configuration
│   │
│   ├── database/                   # Schema & Database Migrations
│   │   ├── schema.sql              # Clean database schema definition with auto-init support
│   │   └── voting_db_import.sql    # Complete SQL dump with positions, candidates & voters
│   │
│   ├── includes/                   # Core Server Utilities
│   │   ├── api_auth.php            # Bearer token validation middleware
│   │   ├── ballot_receipt.php      # Bank-statement style electronic ballot receipt generator (e-BS)
│   │   ├── encryption.php          # AES-256-CBC ballot cryptographic functions
│   │   ├── fpdf.php                # Vector PDF rendering engine
│   │   ├── fpdf_protection.php     # 128-bit/40-bit RC4 stream encryption with OpenSSL & pure-PHP fallback
│   │   ├── functions.php           # Business logic, schedule evaluators & MySQL 8 compliant tallies
│   │   ├── mailer.php              # Multi-channel mailer (SMTP 465/587, HTTPS Webhook, Brevo) with attachments
│   │   ├── pdf_export.php          # Official PDF report generator & backup archiver
│   │   ├── phpmailer/              # Bundled PHPMailer 7.1.1 library
│   │   └── voter_masterlist.php    # Clean parsed dataset of 53 voters from class masterlist
│   │
│   └── backups/                    # Automated PDF Audit Archive Storage
│
├── frontend/                       # Static Decoupled Client (Mirrored to Capacitor WebDir)
│   ├── index.html                  # Frontend root entry point (redirects to login.html)
│   ├── login.html                  # Unified sign-in screen with glassmorphism, password toggle & 2FA OTP modal
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
│       ├── dashboard.html          # Voter greeting, election schedule, e-Statement notice & status badge
│       ├── candidates.html         # Candidate profiles, platforms & positions
│       ├── ballot.html             # Digital ballot form with selectable cards & checkmarks
│       ├── confirm_vote.html       # Ballot summary review, native biometrics / password & submission
│       └── profile.html            # Voter identity view & display name updater
│
├── Masterlist BSIT31008-IS (1).xlsx # Authoritative class roster & voter emails
├── capacitor.config.json           # Capacitor configuration (webDir: "frontend")
├── package.json                    # Project metadata & Capacitor 8 dependencies
├── package-lock.json               # Deterministic dependency tree lock
├── index.php                       # Root entry point (clean redirect to frontend/login.html)
├── Dockerfile                      # Production container recipe for Railway.app & cloud PaaS
├── entrypoint.sh                   # Startup container initializer (MPM fix, port binding, masterlist sync)
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

### 3.1 Authentication, 2FA OTP & Biometrics Security Architecture
- **Stateless Bearer Token & Voter Email OTP Flow**:
  1. Voter/Admin submits credentials (`student_id`/`username`/`email` + `password`) to `POST /backend/api/auth.php?action=login`.
  2. For **Admins**: Server validates password and immediately generates a 64-character token (`bin2hex(random_bytes(32))`), saving it in `users.session_token`.
  3. For **Voters**: Server generates a 6-digit numeric verification code (`random_int(100000, 999999)`), sets a 10-minute expiration timestamp in `users.login_otp_expires_at`, and dispatches a certified HTML email via **PHPMailer** using Gmail SMTP or configured HTTPS relays. The API returns `{ requires_otp: true, otp_user_id, masked_email }`.
  4. The voter enters the 6-digit code on `login.html`, which calls `POST /backend/api/auth.php?action=verify_otp`. Upon successful validation, the OTP is invalidated and the 64-character session token is issued. A resend endpoint (`action=resend_otp`) with a 30-second cooldown is available.
  5. Client saves token in `localStorage.setItem('voter_token', token)`.
  6. Subsequent API calls attach header `Authorization: Bearer <token>`.
- **Cloud Egress Firewall Outbound SMTP Mitigation**:
  - When outbound TCP ports 25, 465, or 587 are blocked by cloud PaaS firewalls (e.g., Railway Free/Hobby tiers resulting in timeout error 110), the system implements multi-channel mail delivery in `backend/includes/mailer.php`:
    - Direct Gmail SMTP with multi-port failover (465 SSL, 587 TLS).
    - HTTPS Webhook relay (`MAIL_WEBHOOK_URL`) via Google Apps Script Web App over standard port 443.
    - Brevo REST API relay (`BREVO_API_KEY`) over port 443.
    - Non-blocking fallback mode returning `dev_otp` in development/cloud sandbox to prevent lockout.
- **Pre-Vote Biometric Authentication & Password Fallback**:
  - Prior to sealing and casting the ballot on `confirm_vote.html`, the application initiates biometric verification using `@capgo/capacitor-native-biometric`.
  - On supported native mobile hardware (Android/iOS), the system prompts for fingerprint or face authentication (`verifyIdentity`).
  - An emerald "Biometric Authentication Enabled" shield badge displays when biometric hardware is enrolled and active.
  - If biometric hardware is unavailable, not enrolled, cancelled, or fails, the voter is presented with a secure in-app Password Verification Modal.
  - The password is validated via `POST /backend/api/voter.php?action=verify_password` and verified again atomically during `action=cast_vote`.
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

### 3.4 Student Voter Subsystem & Ballot Submission
- **Digital Ballot**: Interactive candidate selection cards with active ring states and animated SVG checkmarks.
- **Ballot Persistence**: Uses client-side `sessionStorage` between `ballot.html` and `confirm_vote.html` before final server submission.
- **Tamper-Evident Encryption**: Votes are encrypted using AES-256-CBC and committed inside a MySQL transaction that flags the voter account as `has_voted = 1`, enforcing the strictly audited *One-Student, One-Vote* mandate.
- **Automated e-Statement Generation & Dispatch**:
  - Upon successful vote commitment, the server automatically generates a password-protected electronic statement PDF via `generateProtectedBallotReceiptPdf()`.
  - The encrypted PDF is attached to an official confirmation email dispatched to the student's registered address.
  - The voter can also download the receipt at any time via `GET /backend/api/voter.php?action=download_receipt` from their dashboard.

### 3.5 Native Mobile Experience, Edge-to-Edge & Universal Candidate Modal
- **Edge-to-Edge Layout & Full Viewport Immersion**:
  - Full edge-to-edge drawing under transparent Android status and navigation bars.
  - Enforced `<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">` across all HTML documents.
  - Native Android `MainActivity.java` and `styles.xml` configure transparent system bars with dark icons matching the slate background.
- **Safe Area Insets & Bottom Navigation Clearance**:
  - Sticky `.top-header` applies `padding-top: calc(14px + env(safe-area-inset-top, 0px))` clearing the native status bar, notch, and dynamic island.
  - Fixed `.mobile-bottom-nav` applies `padding-bottom: env(safe-area-inset-bottom, 0px)` keeping tabs above the native home indicator.
  - Main view container (`.content-body`) on mobile viewports (<900px) enforces `padding-bottom: calc(120px + env(safe-area-inset-bottom, 0px)) !important;` (64px navigation bar + 56px whitespace margin) guaranteeing that bottom cards, forms, and submit buttons (e.g., Save Changes, Review Ballot) are never cut off or obstructed by the navigation bar.
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
- **Overscroll Elastic Bounce Elimination**:
  - CSS rule `overscroll-behavior: none !important;` on `html, body`.
  - Android WebView configured with `View.OVER_SCROLL_NEVER` preventing rubber-band stretching.

### 3.6 Cloud Infrastructure, Docker & Database Provisioning
- **Production Containerization (`Dockerfile`, `entrypoint.sh`, `railway.json`)**:
  - Encapsulated in lightweight `php:8.2-apache` container with `pdo_mysql`.
  - Dedicated pre-start script (`entrypoint.sh`) disables conflicting `mpm_event` and `mpm_worker` Apache modules, forcing `mpm_prefork` to eradicate `AH00534` crashes.
  - Dynamically updates Apache's `ports.conf` and `<VirtualHost>` configuration to bind to Railway's assigned `$PORT` (defaulting to 8080).
  - Explicitly configured with `"builder": "DOCKERFILE"` in `railway.json` for deterministic cloud builds.
- **Zero-Config Database Discovery**:
  - `backend/config/database.php` auto-detects Railway's `MYSQL_URL` and individual connection parameters (`MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT`), with seamless fallback to local XAMPP.
  - Automatically provisions fresh database schemas and seed accounts from `backend/database/schema.sql` on virgin instances, including auto-migration for `login_otp` and `login_otp_expires_at` columns.
- **MySQL 8.0 `ONLY_FULL_GROUP_BY` Resilience**:
  - Timeline and position aggregations in `backend/includes/functions.php` utilize strict SQL subqueries with `MIN(created_at)` and explicit grouping, conforming strictly to modern MySQL 8.x standards.
  - Injected session-level SQL mode adjustment into PDO connections (`SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));`).
- **Clean JSON API Error Normalization**:
  - Disabled `display_errors` in API routes and registered a global exception handler in `backend/config/cors.php`, ensuring all errors output standard JSON (`{"success": false, "error": "..."}`) with HTTP 500.
  - Enhanced client-side `api.request()` in `frontend/js/api.js` to strip HTML error tags and throw readable exceptions if non-JSON output is ever returned.

### 3.7 Online Banking e-Statement (e-BS) Password-Protected Ballot Delivery Engine
- **Authentic Banking Statement Design (`backend/includes/ballot_receipt.php`)**:
  - Replicates institutional electronic bank statements (BDO, BPI, UnionBank, GCash, Metrobank):
    - Two-column institutional letterhead with official statement reference: `REF: BS-2026-[student_id]-[hash]`.
    - Account overview card: Account Holder Name, Student Account No, Academic Program & Section, Timestamp, and Polling Precinct.
    - Itemized Ballot Transaction Ledger with transaction tracking numbers (`TXN-01`, etc.), electoral contests, selected candidates, and `COUNTED` status stamps.
    - Total contests voted and ledger reconciliation summary row (`LEDGER BALANCE: RECONCILED`).
    - Bank-grade cryptographic audit box featuring 64-character SHA-256 verification hash, AES-256-CBC certification, and statutory disclaimers.
- **Dual-Key Document Password Protection (`backend/includes/fpdf_protection.php`)**:
  - Implements standard 128-bit/40-bit RC4 stream encryption with OpenSSL and pure-PHP fallback engine.
  - Dual unlock keys:
    - **Primary password**: Voter's Student ID (`users.student_id`).
    - **Alternate password**: Voter's Date of Birth in `YYYYMMDD` format (`users.date_of_birth`).
- **Official Delivery Email with PDF Attachment (`backend/includes/mailer.php`)**:
  - Dispatched immediately upon vote commitment via PHPMailer with MIME `Content-Disposition: attachment; filename="eStatement_Ballot_[student_id].pdf"`.
  - Structured as an official electronic statement advice email:
    - Dedicated attachment preview card with `.PDF` icon and file details.
    - Prominent amber password reminder box with large monospace password badge and 3-step opening instructions.
    - Electronic statement summary table and banking-grade anti-fraud advisory.
- **Non-Blocking Execution & In-App Download (`backend/api/voter.php`)**:
  - Email dispatch failures (e.g. cloud host port blocks) are safely logged without rolling back the committed vote transaction.
  - Endpoint `GET /backend/api/voter.php?action=download_receipt` enables direct PDF download at any time from the voter dashboard.
  - Post-voting dashboard banner provides immediate visual confirmation and one-click statement download.

### 3.8 Automated Class Masterlist Ingestion & Railway Database Synchronization
- **Masterlist Data Extraction (`Masterlist BSIT31008-IS (1).xlsx`)**:
  - Ingests the authoritative 53-student class roster for section BSIT 31008.
  - Extracted 36 verified student emails (Gmail) and authoritative Student Numbers (e.g. `240104785` Alvarez, `240114837` Rañola, `240114524` Antonio, `240108957` Albos, etc.).
  - Generates `backend/includes/voter_masterlist.php` containing structured PHP records for all 53 voters.
- **Automated Railway Cloud Database Sync (`backend/config/database.php`)**:
  - Automatically executes `autoSyncMasterlistVoters($pdo)` upon PDO connection on Railway and local environments.
  - **Live Account Email Patching**: Detects existing voter accounts (previously having dummy `@student.bcp.edu.ph` addresses) and updates their `email` to their real Gmail addresses from the Excel sheet.
  - **New Voter Auto-Provisioning**: Inserts all missing students into `users` with hashed passwords, Student IDs, and default role `'voter'`.
  - **Execution Guard**: Uses `election_settings.masterlist_voters_synced_v1` flag ensuring synchronization executes exactly once per database lifecycle with zero recurring query overhead.
- **Container Pre-Boot Hook (`entrypoint.sh`)**:
  - Injected pre-startup CLI sync (`php -r "require_once '/var/www/html/backend/config/database.php'; getDBConnection();"`) into `entrypoint.sh` executing right before Apache starts.
- **Dual Password Authentication Fallback**:
  - Upgraded password verification in `auth.php` and `voter.php` (`action=verify_password` and `action=cast_vote`): accepts both the hashed initial password (`FirstName + StudentNumber`) and the raw `StudentNumber` as valid initial credentials, preventing student login friction.
- **Diagnostic & Administrative Control**:
  - Admin endpoint `POST /backend/api/admin.php?action=sync_masterlist` allows election officials to manually re-trigger masterlist alignment.
  - Health check endpoint `GET /backend/api/health.php` outputs live metrics: `total_voters`, `voters_with_real_email`, and `masterlist_synced` status.

### 3.9 Native Android App Icon & Multi-Resolution Density Packaging
- **Authoritative Vector-Master Emblem Source**:
  - Derived from `frontend/assets/images/icon.jpg` (1018x1018 px, official circular Tomorrow Vote emblem).
- **Android Adaptive Icons (Android 8.0+ / API 26+)**:
  - Configured in `android/app/src/main/res/mipmap-anydpi-v26/ic_launcher.xml` and `ic_launcher_round.xml`.
  - Background: Configured with `@color/ic_launcher_background` set to `#EDF3FF` matching the emblem's outer background.
  - Foreground: Circular emblem scaled to 65% of the 108dp canvas, guaranteeing 100% adherence to Android's 72dp safe zone circle across all OEM launcher masks (Circle, Squircle, Rounded Square, Pebble, Teardrop).
  - Densities generated:
    - `mipmap-mdpi`: 108 x 108 px
    - `mipmap-hdpi`: 162 x 162 px
    - `mipmap-xhdpi`: 216 x 216 px
    - `mipmap-xxhdpi`: 324 x 324 px
    - `mipmap-xxxhdpi`: 432 x 432 px
- **Legacy Standard & Round Launcher Icons (Android 7.1 and below)**:
  - Standard launcher `ic_launcher.png` and round launcher `ic_launcher_round.png` across all densities:
    - `mipmap-mdpi`: 48 x 48 px
    - `mipmap-hdpi`: 72 x 72 px
    - `mipmap-xhdpi`: 96 x 96 px
    - `mipmap-xxhdpi`: 144 x 144 px
    - `mipmap-xxxhdpi`: 192 x 192 px
- **Store & Distribution Artwork**:
  - High-res 512x512 px PNG generated at `android/app/src/main/ic_launcher-playstore.png` and `frontend/assets/images/icon-512.png`.
- **Native Resource Hardening**:
  - Updated `android/app/src/main/res/values/ic_launcher_background.xml` color to `#EDF3FF`.
  - Replaced legacy teal grid vector in `android/app/src/main/res/drawable/ic_launcher_background.xml` with solid `#EDF3FF` vector.
  - Removed obsolete default Android robot vector in `android/app/src/main/res/drawable-v24/ic_launcher_foreground.xml`.
