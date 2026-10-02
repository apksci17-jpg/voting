---
name: ovs-implementation
description: Defines how an Antigravity coding agent should understand, implement, modify, test, and document the Online Voting System across its decoupled headless PHP API and Capacitor-ready static frontend architecture.
---

# SKILL.md — Online Voting System Implementation

## Purpose

This skill defines how an Antigravity coding agent should understand, implement, modify, test, and document the Online Voting System (OVS).

The system is architected as a **headless, decoupled system**:
1. **Stateless PHP Backend API** (`backend/`): Provides secure JSON REST endpoints, Bearer token authentication, CORS configuration, and AES-256-CBC ballot encryption.
2. **Decoupled Client Frontend** (`frontend/`): Pure HTML5, CSS3, and modern Vanilla JavaScript, optimized for both desktop web browsers and native mobile app packaging via **Capacitor** (iOS & Android).
3. **Clean Cryptography & Direct Ballot Flow**: Fast, accessible voting flow without external AI biometric dependencies.
4. **SVG Visual Standard**: Replaced all emojis with sharp, scalable, accessible inline vector SVG icons across all views.

---

## 1. System Identity & Architecture

- **System:** Online Voting System (OVS)
- **Architecture Model:** Decoupled Headless Client-Server (REST API + Static SPA/MPA)
- **Target Platforms:** Web (Desktop & Mobile browsers) + Native Mobile App (via Capacitor)
- **Primary Roles:** Administrator (`admin`) and Voter/User (`voter`)
- **Authentication:** Stateless Bearer Token (`Authorization: Bearer <token>`) persisted in `localStorage`
- **Frontend Core:** Pure HTML5, CSS3, Vanilla JS (`frontend/js/api.js`, `frontend/js/components.js`)
- **Backend Core:** PHP 8+ with MySQL PDO, CORS headers, AES-256-CBC ballot encryption, and FPDF report generator
- **Vote Lifecycle:** Candidate review → Digital ballot → Selections stored in `sessionStorage` → Confirmation screen → Encrypted atomic submission → Dashboard confirmation badge
- **Administrative Lifecycle:** Candidate management → Polling control (`OPEN`/`CLOSED`) → Live results aggregation → Certified PDF audit backups

---

## 2. API Specifications & Endpoints

### 2.1 Authentication (`backend/api/auth.php`)
- `POST ?action=login`: Validates credentials (`username`/`student_id`/`email` + `password`). Returns `{ success: true, token: "...", user: { id, role, full_name, student_id } }`.
- `GET ?action=me`: Validates Bearer token header. Returns authenticated user payload.
- `POST ?action=logout`: Clears the user's `session_token` in the database.

### 2.2 Voter Subsystem (`backend/api/voter.php`)
- `GET ?action=dashboard`: Returns voter status, full name, election status (`OPEN`/`CLOSED`), and `has_voted` flag.
- `GET ?action=candidates`: Returns all candidates grouped by positions in display order.
- `POST ?action=cast`: Commits voter choices. Encrypts payload with AES-256-CBC, inserts into `votes`, and sets `has_voted = 1` inside an atomic transaction.
- `GET ?action=profile`: Returns student identity info (student ID, email, full name).
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

### 2.4 Cloud Health Diagnostic (`backend/api/health.php`)
- `GET`: Returns JSON report of deployment health, PHP runtime version, environment variable flags (`has_MYSQL_URL`, `has_MYSQLHOST`, `has_PORT`), database connection status, and list of auto-provisioned tables. Does not require authentication.

---

## 3. UI/UX Design System, Typography & Animations

- **Modern Typography (Plus Jakarta Sans & Inter)**: Styled with Google Fonts Plus Jakarta Sans (display) and Inter (body) for high readability, modern appearance, and visual engagement.
- **Fixed Sidebar & Scroll Isolation**: Navigation sidebar is permanently fixed (`position: fixed !important; 100vh`) on desktop (>=900px) so main view scrolling does not displace navigation. On mobile (<900px), transforms into off-canvas drawer with backdrop.
- **Clickable Candidate Modal & Mobile Bottom Sheet**: Universal modal (`showCandidateModal`) displays nominee portrait, verified certified badge, running position, and full advocacy manifesto. On mobile (<768px), smoothly animates as a native bottom sheet with top drag handle pill (`.sheet-drag-handle`).
- **Native Mobile Bottom Navigation Bar**: Fixed bottom tab bar (`.mobile-bottom-nav`) on screens <900px featuring glassmorphism blur and 5 quick-switch tabs for admins (Overview, Control, Candidates, Voters, Results) and 4 for voters (Dashboard, Candidates, Ballot, Profile) with active indicators and safe-area insets.
- **Native Touch Dynamics & Haptic Damping**: All touch surfaces feature `touch-action: manipulation` (no 300ms delay), `-webkit-tap-highlight-color: transparent`, `user-select: none`, and instant tap damping (`scale(0.97)` on `:active`) with cubic-bezier hover elevation (`translateY(-4px) scale(1.008)`).
- **Fullscreen Smooth Circular Loading Animation**: Centered, hardware-accelerated 60/120fps SVG circular indeterminate progress spinner (`#app-fullscreen-loader`, `.circular-spinner`) with smooth keyframe dash and rotation transitions on a clean, light neutral backdrop (`rgba(255, 255, 255, 0.94)`). Pre-initialized on DOM ready and automatically wired into `api.request()` network cycles.
- **High-Performance Neutral Backgrounds**: Replaced `--bg-light: #e7effa;` with instantaneous-loading neutral slate/white surfaces (`#f8fafc`) across all inner app portals for immediate First Contentful Paint.
- **Strict Emoji Prohibition**: All legacy emoji icons have been replaced with inline vector SVG icons using standard 24x24 or 18x18 viewBoxes and theme tokens.
- **Login Ballot Background & Frosted Glassmorphic Sign-In**: Login screen features the official ballot background image (`login_bg.png`) layered under a subtle depth gradient with an elevated frosted glassmorphism card (`backdrop-filter: blur(18px)`), floating official emblem, and high-contrast typography.
- **Password Visibility Control**: Modern show/hide toggle utilizing clean SVG eye and eye-off vectors.
- **Off-Canvas Navigation Drawer**: Responsive slide-out navigation sidebar with tap-to-dismiss backdrop for screens <900px.
- **High-Contrast Data Display**: Accessible typography, high contrast metric cards, position badges, and dynamic progress bars.

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

---

## 5. Security & Verification Rules

1. **Server-Side Validation**: Client-side state is never trusted. Every administrative endpoint checks `requireApiAdmin()` and every voter endpoint checks `requireApiVoter()`.
2. **Atomic Vote Commit**: Double-voting is strictly prevented via MySQL transactions and `has_voted` account verification.
3. **AES-256-CBC Encryption**: Ballots are stored encrypted with initialization vectors; plaintext votes are never written to disk or logs.
4. **Certified PDF Archival**: Every reset triggers generation of a permanent PDF audit report with SHA-256 integrity signatures.

---

## 6. Cloud Deployment & Containerization Architecture (Railway.app)

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

