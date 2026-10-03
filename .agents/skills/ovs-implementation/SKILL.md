---
name: ovs-implementation
description: Defines how an Antigravity coding agent should understand, implement, modify, test, and document the Online Voting System across its decoupled headless PHP API and Capacitor-ready static frontend architecture.
---

# SKILL.md — Online Voting System Implementation

## Purpose

This skill defines how an Antigravity coding agent should understand, implement, modify, test, and document the Online Voting System (OVS).

The system is architected as a **headless, decoupled system**:
1. **Stateless PHP Backend API** (`backend/`): Provides secure JSON REST endpoints, Bearer token authentication, 2FA Email OTP, CORS configuration, AES-256-CBC ballot encryption, and password-protected PDF e-Statement receipts.
2. **Decoupled Client Frontend** (`frontend/`): Pure HTML5, CSS3, and modern Vanilla JavaScript, optimized for both desktop web browsers and native mobile app packaging via **Capacitor** (iOS & Android).
3. **2FA Email OTP Sign-in**: Mandatory 6-digit numeric verification code dispatched to registered voter email on login, backed by multi-port SMTP failover and HTTPS Webhook relays.
4. **Hardware Native Biometrics with In-App Password Fallback**: Pre-vote identity verification via `@capgo/capacitor-native-biometric` (fingerprint/face) on mobile devices, with seamless fallback to secure password confirmation.
5. **Online Banking e-Statement (e-BS) Ballot Delivery**: Automatically generates a bank-statement-style certified electronic ballot receipt encrypted with RC4 dual-key protection (Student ID + Date of Birth YYYYMMDD), attached directly to the voter's confirmation email and available for on-demand in-app download.
6. **Edge-to-Edge Android Immersive Display**: Clean viewport rendering under transparent system bars with zero letterboxing and disabled elastic overscroll bounce.
7. **SVG Visual Standard**: Replaced all legacy emojis with sharp, scalable, accessible inline vector SVG icons across all views.
8. **Authoritative Class Roster & Automated Database Synchronization**: Full integration of BSIT 31008 class masterlist (`backend/includes/voter_masterlist.php`) featuring 53 verified voters with genuine student emails, dual password credentials (hashed `FirstName + StudentNumber` or raw `StudentNumber`), and automatic one-time database migration on Railway cloud instances via `autoSyncMasterlistVoters($pdo)` and `entrypoint.sh`.

---

## 1. System Identity & Architecture

- **System:** Online Voting System (OVS)
- **Architecture Model:** Decoupled Headless Client-Server (REST API + Static SPA/MPA)
- **Target Platforms:** Web (Desktop & Mobile browsers) + Native Mobile App (via Capacitor)
- **Primary Roles:** Administrator (`admin`) and Voter/User (`voter`)
- **Authentication:** Stateless Bearer Token (`Authorization: Bearer <token>`) persisted in `localStorage` + 2FA Email OTP for voters
- **Frontend Core:** Pure HTML5, CSS3, Vanilla JS (`frontend/js/api.js`, `frontend/js/components.js`)
- **Backend Core:** PHP 8+ with MySQL PDO, CORS headers, AES-256-CBC ballot encryption, FPDF 1.86, and RC4 PDF protection
- **Vote Lifecycle:** Candidate review → Digital ballot → Selections stored in `sessionStorage` → Confirmation screen → Biometric / Password identity verification → Encrypted atomic submission → Password-protected e-Statement email attachment & on-demand download → Dashboard confirmation badge
- **Administrative Lifecycle:** Candidate management → Polling control (`OPEN`/`CLOSED`) → Live results aggregation → Certified PDF audit backups

---

## 2. API Specifications & Endpoints

### 2.1 Authentication (`backend/api/auth.php`)
- `POST ?action=login`: Validates credentials (`username`/`student_id`/`email` + `password`). Supports dual password authentication for voters (matches either bcrypt hash of initial default password `FirstName + StudentNumber` or raw `StudentNumber` fallback).
  - For **Admins**: Immediately returns `{ success: true, token: "...", user: { id, role, full_name, username } }`.
  - For **Voters**: Generates 6-digit OTP, records in `users.login_otp` with 10-minute expiry, sends email, and returns `{ success: true, requires_otp: true, otp_user_id: ..., masked_email: "..." }`.
- `POST ?action=verify_otp`: Validates 6-digit code against `users.login_otp` and expiration timestamp. Upon match, invalidates the code and returns `{ success: true, token: "...", user: { ... } }`.
- `POST ?action=resend_otp`: Re-generates and re-dispatches OTP email with 30-second rate-limiting cooldown.
- `GET ?action=me`: Validates Bearer token header. Returns authenticated user payload.
- `POST ?action=logout`: Clears the user's `session_token` in the database.

### 2.2 Voter Subsystem (`backend/api/voter.php`)
- `GET ?action=dashboard`: Returns voter status, full name, election status (`OPEN`/`CLOSED`), and `has_voted` flag.
- `GET ?action=candidates`: Returns all candidates grouped by positions in display order.
- `POST ?action=verify_password`: Securely validates voter's password prior to casting ballot when biometrics are unavailable or bypassed. Supports dual password authentication (bcrypt hash or raw student ID fallback).
- `POST ?action=cast`: Commits voter choices. Optionally validates password (with dual-credential fallback). Encrypts payload with AES-256-CBC, inserts into `votes`, and sets `has_voted = 1` inside an atomic transaction. Triggers `generateProtectedBallotReceiptPdf()` and dispatches password-protected e-Statement email via PHPMailer.
- `GET ?action=download_receipt`: Streams the voter's password-protected e-Statement PDF receipt (`eStatement_Ballot_[student_id].pdf`) directly to the client browser or Capacitor app.
- `GET ?action=profile`: Returns student identity info (student ID, email, full name, birthdate).
- `POST ?action=update_profile`: Updates voter's editable full name.

### 2.3 Administrator Subsystem (`backend/api/admin.php`)
- `GET ?action=dashboard`: Returns voter turnout statistics, registered voter totals, ballot counts, and system status.
- `POST ?action=set_status`: Sets election status to `OPEN` or `CLOSED` with manual override tracking.
- `GET ?action=results`: Aggregates live ballots by position, candidate vote counts, and winner rankings.
- `POST ?action=reset_records`: Automatically generates a certified historical PDF archive and clears vote records for a new election term.
- `GET ?action=backups`: Lists all generated PDF audit reports stored in `backend/backups/`.
- `GET ?action=download_pdf`: Generates and streams live election audit report in PDF format.
- `GET ?action=download_backup&file=...`: Authenticated streaming of requested historical PDF backup file.
- `GET ?action=positions`: Retrieves official positions in display order.
- `GET ?action=candidates`: Returns active candidates with positions and platforms.
- `POST ?action=add_candidate`: Creates new candidate, saves base64/uploaded photo to `frontend/assets/uploads/`.
- `POST ?action=edit_candidate`: Updates candidate details, position, year, platform, and optional photo.
- `POST ?action=delete_candidate`: Removes candidate from database.
- `GET ?action=voters`: Retrieves registered voter accounts with voting status and aggregate turnout metrics.
- `POST ?action=add_voter`: Enforces unique Student ID, username, and email; creates voter with hashed password.
- `POST ?action=edit_voter`: Updates voter profile, section, and credentials with optional password reset.
- `POST ?action=delete_voter`: Safely deletes voter and cascade-deletes votes in an atomic transaction.
- `POST ?action=reset_voter_ballot`: Wipes a specific voter's ballot and resets `has_voted = 0` in an atomic transaction.
- `POST ?action=sync_masterlist`: Synchronizes registered voter accounts against the authoritative 53-voter class roster in `backend/includes/voter_masterlist.php`. Idempotently updates verified Gmail addresses, inserts missing voters, and returns sync counts.

### 2.4 Cloud Health Diagnostic (`backend/api/health.php`)
- `GET`: Returns JSON report of deployment health, PHP runtime version, environment variable flags (`has_MYSQL_URL`, `has_MYSQLHOST`, `has_PORT`), database connection status, list of auto-provisioned tables, `total_voters`, `voters_with_real_email`, and `masterlist_synced` status flag. Does not require authentication.

---

## 3. UI/UX Design System, Typography & Animations

- **Modern Typography (Plus Jakarta Sans & Inter)**: Styled with Google Fonts Plus Jakarta Sans (display) and Inter (body) for high readability, modern appearance, and visual engagement.
- **Fixed Sidebar & Scroll Isolation**: Navigation sidebar is permanently fixed (`position: fixed !important; 100vh`) on desktop (>=900px) so main view scrolling does not displace navigation. On mobile (<900px), transforms into off-canvas drawer with backdrop.
- **Clickable Candidate Modal & Mobile Bottom Sheet**: Universal modal (`showCandidateModal`) displays nominee portrait, verified certified badge, running position, and full advocacy manifesto. On mobile (<768px), smoothly animates as a native bottom sheet with top drag handle pill (`.sheet-drag-handle`).
- **Native Mobile Bottom Navigation Bar**: Fixed bottom tab bar (`.mobile-bottom-nav`) on screens <900px featuring glassmorphism blur and 5 quick-switch tabs for admins (Overview, Control, Candidates, Voters, Results) and 4 for voters (Dashboard, Candidates, Ballot, Profile) with active indicators and safe-area insets.
- **Native Touch Dynamics & Haptic Damping**: All touch surfaces feature `touch-action: manipulation` (no 300ms delay), `-webkit-tap-highlight-color: transparent`, `user-select: none`, and instant tap damping (`scale(0.97)` on `:active`) with cubic-bezier hover elevation (`translateY(-4px) scale(1.008)`).
- **Fullscreen Smooth Circular Loading Animation**: Centered, hardware-accelerated 60/120fps SVG circular indeterminate progress spinner (`#app-fullscreen-loader`, `.circular-spinner`) with smooth keyframe dash and rotation transitions on a clean, light neutral backdrop (`rgba(255, 255, 255, 0.94)`). Pre-initialized on DOM ready and automatically wired into `api.request()` network cycles.
- **High-Performance Neutral Backgrounds**: Clean, instantaneous-loading neutral slate/white surfaces (`#f8fafc`) across all inner app portals for immediate First Contentful Paint.
- **Strict Emoji Prohibition**: All legacy emoji icons have been replaced with inline vector SVG icons using standard 24x24 or 18x18 viewBoxes and theme tokens.
- **Login Ballot Background & Frosted Glassmorphic Sign-In**: Login screen features the official ballot background image (`login_bg.png`) layered under a subtle depth gradient with an elevated frosted glassmorphism card (`backdrop-filter: blur(18px)`), 2FA OTP verification modal, and high-contrast typography.
- **Edge-to-Edge Viewport**: Zero letterboxing with transparent system bars, dark system icons, and `overscroll-behavior: none` eliminating elastic bounce.

---

## 4. Capacitor Mobile Readiness

The frontend is specifically structured for direct compilation into native iOS and Android apps using Capacitor:
- Zero server-side rendering or PHP file dependencies in the `frontend/` directory.
- Dynamic `API_BASE` resolution in `frontend/js/api.js` supporting both relative URLs and custom native app schemes (`capacitor://localhost`).
- No cookie dependencies; all authorization is maintained via `Authorization: Bearer <token>` headers from `localStorage`.
- Touch-friendly action buttons with minimal 44x44px touch targets.
- Native mobile gestures, bottom navigation tabs, bottom sheet dialogs, and elimination of web-view tap delays and selection halos.
- Viewport-fit cover (`<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">`) exposing hardware notch, camera punch-hole, and home gesture safe-area metrics.
- Top safe-area padding on `.top-header` (`padding-top: calc(14px + env(safe-area-inset-top, 0px))`).
- Bottom clearance on `.content-body` (`padding-bottom: calc(120px + env(safe-area-inset-bottom, 0px)) !important;`) ensuring content, forms, and submit buttons are completely visible and unblocked above the bottom navigation bar.
- Pre-vote native biometrics using `@capgo/capacitor-native-biometric` (v8.7.0) with biometric availability detection and emerald status shield.

---

## 5. Online Banking e-Statement (e-BS) PDF Generator & Encryption

- **Generator Utility (`backend/includes/ballot_receipt.php`)**:
  - `generateProtectedBallotReceiptPdf($pdo, $voterId, $userPassword)`:
    - Builds an authentic bank-statement layout: institutional letterhead, statement reference (`REF: BS-2026-[student_id]-[hash]`), student account details card, itemized ballot transaction ledger (`TXN-01`, etc.), candidate names, `COUNTED` verification stamps, ledger reconciliation summary, and bank-grade SHA-256 cryptographic audit box.
- **Dual-Key Document Password Protection (`backend/includes/fpdf_protection.php`)**:
  - Extends `FPDF` with standard 128-bit/40-bit RC4 stream encryption.
  - Implements dual unlock keys:
    - **Primary password**: Voter's Student ID (`users.student_id`).
    - **Alternate password**: Voter's Date of Birth in `YYYYMMDD` format (`users.date_of_birth`).
- **Delivery Mailer (`backend/includes/mailer.php`)**:
  - `sendVoterBallotReceipt($voterEmail, $voterName, $studentId, $pdfContent, $birthdate)`:
    - Attaches encrypted PDF with MIME type `application/pdf` and filename `eStatement_Ballot_[student_id].pdf`.
    - Dispatches rich HTML email with attachment card, large monospace password reminder badge, 3-step opening instructions, and banking-grade security advisory.
  - Execution is non-blocking: mail network latency or timeout never aborts the committed vote transaction.
- **On-Demand In-App Download (`backend/api/voter.php`)**:
  - Endpoint `GET ?action=download_receipt` streams the password-protected receipt binary directly with `Content-Type: application/pdf` and `Content-Disposition: attachment; filename="eStatement_Ballot_[student_id].pdf"`.

---

## 6. Security & Verification Rules

1. **Server-Side Validation**: Client-side state is never trusted. Every administrative endpoint checks `requireApiAdmin()` and every voter endpoint checks `requireApiVoter()`.
2. **2FA Email OTP Verification**: Voter accounts must verify a 6-digit numeric OTP before Bearer session tokens are issued.
3. **Atomic Vote Commit**: Double-voting is strictly prevented via MySQL transactions and `has_voted` account verification.
4. **AES-256-CBC Encryption**: Ballots are stored encrypted with initialization vectors; plaintext votes are never written to disk or logs.
5. **Biometrics with Password Fallback**: Native mobile biometrics verify voter identity before ballot casting, with automatic fallback to password confirmation if biometrics are unavailable or fail.
6. **Password-Protected e-Statements**: All ballot copies sent to voters via email or downloaded are encrypted with RC4 stream cipher using student credentials.
7. **Certified PDF Archival**: Every reset triggers generation of a permanent PDF audit report with SHA-256 integrity signatures.
8. **Masterlist Synchronization & Dual Password Credential**: Real student emails are locked to verified student numbers. Initial authentication supports both standard default password (`FirstName + StudentNumber`) and raw `StudentNumber` fallback to guarantee zero onboarding friction.

---

## 7. Cloud Deployment & Containerization Architecture (Railway.app)

- **Containerization Blueprint (`Dockerfile`, `entrypoint.sh`, `railway.json`)**:
  - Base Image: `php:8.2-apache` with `pdo_mysql`.
  - MPM Conflict Mitigation: `entrypoint.sh` executes `a2dismod mpm_event mpm_worker` and enforces `a2enmod mpm_prefork` before starting `apache2-foreground` to prevent Apache `AH00534` crashes.
  - Dynamic Port Configuration: Adapts Apache to Railway's assigned `$PORT` environment variable (defaults to 8080).
  - Builder Configuration: Enforced `"builder": "DOCKERFILE"` in `railway.json`.
- **Database Engine Compatibility (MySQL 8.0)**:
  - All analytical queries in `backend/includes/functions.php` utilize strict subqueries and aggregates to comply with `ONLY_FULL_GROUP_BY`.
  - Automatic database schema and account provisioning executes on virgin database instances via `backend/database/schema.sql`.
- **API Error Normalization**:
  - Suppressed HTML error output in `backend/config/cors.php` via `ini_set('display_errors', '0')` and registered global JSON exception handler, ensuring all errors output valid JSON payloads rather than HTML strings.

---

## 8. Authoritative Class Masterlist & Cloud Synchronization

- **Masterlist Roster (`backend/includes/voter_masterlist.php`)**:
  - Defines the complete class roster of 53 students for section BSIT 31008.
  - 36 students have verified student IDs and authentic personal Gmail addresses (e.g., `moiseskeralavarez@gmail.com`, `cassandramherranola@gmail.com`).
  - 17 students with unlisted emails are assigned systematic unique IDs (`240199001`–`240199017`) and school domain placeholders, editable by administrators.
- **Automated Railway Cloud Sync (`backend/config/database.php` & `entrypoint.sh`)**:
  - When deployed to Railway (where an existing database already has the `users` table), `autoSyncMasterlistVoters($pdo)` checks the `election_settings.masterlist_voters_synced_v1` flag.
  - If unapplied, it automatically iterates the 53 masterlist records: updating existing records to their verified Gmail addresses and inserting any missing voter rows.
  - Executed automatically at container boot in `entrypoint.sh` via PHP CLI (`php -r "require 'backend/config/database.php'; autoSyncMasterlistVoters(getDbConnection());"`) and lazily during the first database connection.
