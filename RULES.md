# RULES.md — Antigravity Rules for the Online Voting System

## 1. Rule Priority

These rules govern all implementation, modification, debugging, testing, and documentation work on the Online Voting System.

Priority order:
1. User's explicit request for the current task.
2. Existing working project/codebase behavior.
3. The project's system documentation.
4. These implementation rules.
5. General engineering conventions.

When sources conflict, do not silently rewrite the system. Explain the conflict and make the smallest safe change required by the user's request.

The system documentation defines the Online Voting System as a decoupled, headless web and mobile-ready platform that authenticates users via tokens and 2FA email OTP, routes them by role, manages candidates and voters, controls voting periods, verifies voter identity via native biometrics or password fallback, guides ballot casting, cryptographically encrypts/stores votes, generates password-protected e-Statement receipts, and provides real-time results analytics.

---

## 2. Core System Rules

### Rule 2.1 — Preserve the Two-Role Model
The documented roles are:
- Administrator (`admin`)
- User/Voter (`voter`)

Do not create additional roles unless explicitly requested.

### Rule 2.2 — Preserve Role Separation
- Administrators manage the election.
- Voters participate in the election.
- A voter must never gain access to administrative functions merely by manipulating the UI, URL, request parameters, or client-side state.
- Authorization must be enforced server-side via token authentication on all API endpoints.
- The documented system routes authenticated users to either the Admin Portal or Voter Portal according to account role.

### Rule 2.3 — Do Not Invent Election Rules
Never silently introduce undocumented business rules such as:
- new voting positions,
- candidate eligibility formulas,
- vote limits,
- tie-breaking algorithms,
- election schedules,
- duplicate-vote policies,
- result-publication rules,
- voter anonymity guarantees,
- blockchain voting,
- AI camera facial recognition.

If the existing code already implements a rule, preserve it unless the user explicitly asks for a change.

---

## 3. Architecture & Authentication Rules

### Rule 3.1 — Headless Decoupled Architecture
- The system must remain strictly separated into:
  - `backend/`: Stateless PHP RESTful JSON API server.
  - `frontend/`: Static HTML5, CSS3, and modern Vanilla JavaScript client, completely decoupled and ready for Capacitor mobile app packaging.
- The frontend must communicate with the backend exclusively via HTTP JSON requests using `fetch` or the unified `frontend/js/api.js` client wrapper.
- PHP files must NEVER render HTML views directly. All templating occurs on the client.

### Rule 3.2 — Stateless Bearer Token & 2FA Email OTP Authentication
- Authentication is governed by random 64-character Bearer tokens generated upon login and persisted in `users.session_token`.
- For **Voter accounts**, 2FA Email OTP verification is mandatory on every login:
  - The server generates a 6-digit numeric OTP (`random_int(100000, 999999)`), records it in `users.login_otp`, and sets a 10-minute expiration timestamp in `users.login_otp_expires_at`.
  - The OTP is dispatched to the voter's registered email via PHPMailer with multi-port SMTP failover (465 SSL, 587 TLS) and HTTPS relay options (Google Apps Script Webhook, Brevo API).
  - The Bearer session token is issued ONLY after successful verification via `POST /backend/api/auth.php?action=verify_otp`.
  - A resend endpoint (`action=resend_otp`) with a 30-second rate-limiting cooldown must be maintained.
- For **Admin accounts**, credential validation immediately returns the 64-character Bearer token.
- Clients store this token in `localStorage` (`voter_token`) and attach it via the `Authorization: Bearer <token>` HTTP header.
- Do not rely on PHP cookie sessions (`$_SESSION`) for authentication, ensuring seamless compatibility across Capacitor mobile webviews and cross-origin environments.

### Rule 3.3 — CORS Policy
- `backend/config/cors.php` must maintain permissive CORS headers (`Access-Control-Allow-Origin: *`, `Authorization` header support, preflight `OPTIONS` handling) to allow native mobile wrappers (`capacitor://`, `http://localhost`) to execute API requests without origin blocking.
- `Content-Disposition` and `Content-Length` headers must be exposed to allow binary PDF streaming.

### Rule 3.4 — Validate Credentials Securely
- Validate supplied `student_id`, `username`, or `email` against the `users` table.
- Plaintext passwords or secret keys must NEVER be exposed in API payloads, console logs, or client-side storage.

### Rule 3.5 — Role-Based Routing
After successful authentication:
- Administrator → `frontend/admin/dashboard.html`
- User/Voter → `frontend/voter/dashboard.html`

### Rule 3.6 — Pre-Vote Native Biometrics with In-App Password Fallback
- Before committing and sealing a ballot on `confirm_vote.html`, the system must authenticate the voter:
  1. On native mobile hardware (Android/iOS via Capacitor), initiate biometric prompt (Fingerprint / Face ID) using `@capgo/capacitor-native-biometric`.
  2. If biometric hardware is unavailable, not enrolled, cancelled by the voter, or fails, the application must automatically display an elevated in-app Password Verification Modal.
  3. The entered password must be verified against `POST /backend/api/voter.php?action=verify_password` and validated atomically during `action=cast_vote`.
- Web browser environments without native biometric plugins must seamlessly default to the password verification flow.

---

## 4. Authorization Rules

### Rule 4.1 — Admin-Only Operations
The following operations are administrator functions protected by `requireApiAdmin()`:
- Candidate management (view, add, edit, delete, photo uploads, position assignments)
- Voter account management (view roster, register new voter, edit voter credentials/profile, delete voter)
- Resetting individual voter ballots (`reset_voter_ballot`) to allow recasting if authorized
- Voting status control (`OPEN` / `CLOSED`)
- Resetting election records & generating certified audit PDFs
- Viewing official tally, audit logs, and declaring winners
- Viewing and downloading live and archived PDF reports

### Rule 4.2 — Voter Operations
The voter workflow is protected by `requireApiVoter()`:
- Viewing candidate profiles and platforms
- Viewing voter dashboard and election schedules
- Receiving the digital ballot
- Selecting candidates (1 candidate per position)
- Reviewing selections on the confirmation screen
- Completing biometric or password identity verification
- Submitting the final vote with AES-256-CBC ballot encryption
- Downloading password-protected electronic ballot statement receipts (`action=download_receipt`)

### Rule 4.3 — Never Trust Client-Side Role Data
Never authorize actions based on `localStorage` role flags, hidden form inputs, or disabled buttons. Every API endpoint must validate the Bearer token and check the database role.

### Rule 4.4 — Candidate & Voter Account Integrity
- Candidate records require a valid position ID, nominee name, and optional platform/photo. Uploaded photos (base64 or multipart) must be sanitized and saved safely into `frontend/assets/uploads/`.
- Voter accounts require a unique Student ID, Username, and Email. Account passwords must always be hashed with `password_hash(PASSWORD_DEFAULT)`.
- Deleting a voter account or resetting an individual voter's ballot must execute atomically within a MySQL transaction (`BEGIN`, `DELETE FROM votes`, `DELETE/UPDATE users`, `COMMIT`) to prevent dangling votes or inconsistent turnout metrics.

---

## 5. Election-State, Ballot Integrity & e-Statement Rules

### Rule 5.1 — Voting Period Controls Are Authoritative
- Administrators control voting status (`OPEN` or `CLOSED`).
- The backend API (`backend/api/voter.php`) must reject any vote submission when polls are closed with HTTP 403 / error response.

### Rule 5.2 — One-Student, One-Vote Mandate
- Each registered voter is permitted to cast exactly one ballot.
- The vote insertion and voter flag update (`UPDATE users SET has_voted = 1 WHERE id = ?`) must execute atomically within a database transaction.
- If `has_voted == 1`, subsequent ballot submissions must be immediately rejected.

### Rule 5.3 — Encrypt the Submitted Vote
- Ballots must be encrypted using AES-256-CBC with secure encryption keys before insertion into the `votes` table.
- Raw voter choices must never be stored in plaintext.

### Rule 5.4 — Modern Hardware Biometrics & Zero Camera AI
- External camera-based facial recognition, AI face matching, and video liveness checks are strictly prohibited.
- Biometric authentication must strictly utilize device-native hardware APIs (Android BiometricPrompt / iOS LocalAuthentication via `@capgo/capacitor-native-biometric`).
- If biometric hardware is not available, not enrolled, or bypassed, the system must provide a seamless, secure in-app password verification fallback.

### Rule 5.5 — Password-Protected e-Statement (e-BS) Delivery
- Immediately upon successful vote commitment, the system must generate a bank-statement-style electronic ballot receipt PDF via `generateProtectedBallotReceiptPdf()`.
- The PDF document must be encrypted using 128-bit/40-bit RC4 stream encryption (`FPDF_Protection`).
- The encryption password must be set to the student's unique credentials:
  - **Primary unlock password**: Voter's Student ID (`users.student_id`).
  - **Alternate unlock password**: Voter's Date of Birth in `YYYYMMDD` format (`users.date_of_birth`).
- The protected PDF must be attached directly to an official electronic statement confirmation email dispatched via PHPMailer.
- Email dispatch failures (e.g. cloud host port blocks) must NEVER abort or roll back the committed vote transaction.
- Voters must be able to download their password-protected receipt on demand via `action=download_receipt` at any time from their voter portal.

---

## 6. UI/UX Design System Rules

### Rule 6.1 — Inline Vector SVG Icons
- All legacy emoji icons across the entire application are strictly replaced with clean, sharp, inline vector **SVG icons**.
- Never use emojis as system navigation or button icons.
- SVGs must use `stroke="currentColor"` or theme colors, standard 24x24 or 18x18 viewBoxes, and clean line caps/joins.

### Rule 6.2 — Mobile-First & Capacitor Optimization
- The interface must remain fully responsive and touch-friendly.
- On screens narrower than 900px, the navigation sidebar automatically transforms into an off-canvas drawer with a tap-to-dismiss backdrop and hamburger menu toggle.
- Button tap targets must be at least 44x44px with clear visual active/hover states.

### Rule 6.3 — Modern Typography Standard
- The primary display and interface font is **Plus Jakarta Sans** (weights 300 to 900), paired with **Inter** (weights 400 to 700) for dense metric displays.
- Google Fonts preconnect links must be present in all HTML view files to guarantee rapid font asset acquisition without layout shift.

### Rule 6.4 — Fixed Sidebar & Scroll Isolation
- The navigation sidebar on desktop (>=900px) must remain permanently locked (`position: fixed !important; top: 0; left: 0; width: 260px; height: 100vh; overflow-y: auto;`).
- The main content container must apply an exact `margin-left: 260px !important; width: calc(100% - 260px) !important;` offset so that main body scrolling never scrolls, jerks, or displaces the sidebar navigation.

### Rule 6.5 — Fullscreen Circular Loader, Smooth Transitions & High-Performance Backgrounds
- All views must employ smooth `@keyframes` animations (`fadeInUp`, `scaleIn`, `pulseDot`, `spinnerRotate`, `spinnerDash`) with cubic-bezier easing to ensure high visual engagement.
- On slow or high-latency networks, the application must:
  1. Trigger the fullscreen smooth circular loader (`#app-fullscreen-loader`) with a clean neutral backdrop (`rgba(255, 255, 255, 0.94)`) and hardware-accelerated SVG circular spinner during any API request or view transition.
  2. Maintain the official ballot background graphic (`login_bg.png`) on the login screen with an elevated frosted glassmorphism card (`backdrop-filter: blur(18px)`), while inner app portals utilize clean, high-performance neutral slate backgrounds (`#f8fafc`) for maximum rendering speed.
  3. Mount animated shimmer skeleton card placeholders (`api.showSkeleton()`) into content containers before data arrives, preventing blank screens or layout popping.
  4. Provide smooth button micro-interactions (`transform: scale(0.98)` on `:active`).

### Rule 6.6 — Clickable Candidate Cards, Native Bottom Sheets & Native Mobile App Feel
- Candidate cards across voter and admin views must be interactive, clickable, and keyboard-accessible (`role="button"`, `tabindex="0"`, `Enter`/`Space` handlers).
- Clicking or tapping any candidate card must open the universal candidate modal (`showCandidateModal`) displaying full nominee details, verified badges, and complete platform & advocacy manifesto.
- On mobile screens (<768px), candidate detail modals must display as native mobile slide-up bottom sheets with a top drag handle, safe-area inset padding, and backdrop dismissal.
- All touch controls must eliminate tap delays via `touch-action: manipulation`, suppress highlight boxes via `-webkit-tap-highlight-color: transparent`, prevent text selection via `user-select: none`, and provide responsive physical damping via `:active` transform scaling (`scale(0.97)`).
- On mobile screens (<900px), a native bottom navigation bar (`.mobile-bottom-nav`) must provide one-thumb switching across core portals with glassmorphism backdrop blur.

### Rule 6.7 — Mobile Safe Area Insets & Navigation Bar Clearance
- All HTML views must specify `<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">` to enable safe area environment variables in Capacitor native mobile wrappers.
- The top navigation bar (`.top-header`) must apply `padding-top: calc(14px + env(safe-area-inset-top, 0px))` so sticky headers cleanly clear the native status bar and camera notch.
- The fixed bottom navigation bar (`.mobile-bottom-nav`) must apply `padding-bottom: env(safe-area-inset-bottom, 0px)` so tab items remain clear of the device home gesture indicator.
- The main scrollable view container (`.content-body`) on mobile screens (<900px) must enforce a minimum `padding-bottom: calc(120px + env(safe-area-inset-bottom, 0px)) !important;` to ensure all submit buttons, form controls, and cards have at least 50px of visible whitespace above the floating bottom navigation bar when scrolled to the end of the page.

### Rule 6.8 — Edge-to-Edge Native Display & Elastic Scroll Elimination
- The mobile application must draw seamlessly edge-to-edge under transparent system bars without visual letterboxing or colored border seams.
- Native Android `MainActivity.java` and `styles.xml` configure transparent system bars with dark icons matching the slate background.
- Rubber-band elastic overscroll bouncing must be strictly disabled across all webviews and scrollable viewports using `overscroll-behavior: none !important;` and `View.OVER_SCROLL_NEVER`.

---

## 7. Results & Reporting Rules

### Rule 7.1 — Results Derived from Stored Votes
- Result tallies must be calculated directly from authoritative ballot records.
- Never hard-code candidate rankings, percentages, or winners.
- Percentages must be formatted cleanly to 1 decimal place.

### Rule 7.2 — Certified PDF Backups
- Resetting election records must automatically generate a certified, tamper-evident PDF report stored in `backend/backups/`.
- Each PDF report contains official COMELEC sign-offs, voter turnout metrics, and SHA-256 integrity hashes.

### Rule 7.3 — On-Demand PDF Report & Backup Streaming
- Both live election results audits and historical backups must be downloadable on-demand via authenticated API endpoints (`action=download_pdf` and `action=download_backup`).
- All PDF downloads must be protected with admin token validation (supporting both HTTP Bearer headers and query parameters for direct download triggers).
- Downloads must stream with modern headers (`Content-Type: application/pdf`, `Content-Disposition: attachment; filename="..."`, `Content-Length`) and clean output buffers to prevent corrupted binary streams.
- The client-side download helper (`api.admin.downloadPdf`) must trigger the fullscreen circular loader during download generation and support native Blob URL creation.

---

## 8. Cloud Hosting, Containerization & SQL Compatibility Rules

### Rule 8.1 — Environment-Agnostic Database Connection
- Never hardcode cloud or local database credentials.
- `backend/config/database.php` must support standard PaaS database URLs (`MYSQL_URL`) as well as discrete environment variables (`MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT`), with seamless fallback to local XAMPP (`localhost`, `root`, blank password, `voting`).

### Rule 8.2 — Single Apache MPM Module Enforcement
- When building or running Debian-based Apache Docker containers (`php:8.2-apache`), strictly enforce `mpm_prefork` and purge conflicting MPM modules (`mpm_event`, `mpm_worker`) before Apache starts.
- Never allow multiple MPM modules to load simultaneously, which prevents fatal `AH00534` container startup crashes.

### Rule 8.3 — Dynamic Container Port Binding
- In containerized environments, the application must dynamically bind Apache's listening ports to the runtime `$PORT` environment variable (defaulting to 8080) rather than hardcoded port 80.

### Rule 8.4 — Modern MySQL 8.x Strict Group By Compliance
- All SQL queries that utilize `GROUP BY` must comply with `ONLY_FULL_GROUP_BY` (SQL-92 standard).
- Every non-aggregated column in the `SELECT` list must be included in the `GROUP BY` clause, or calculated using an aggregate function (e.g. `MIN()`, `MAX()`, `COUNT()`), or encapsulated within a structured subquery.
- Database connection initialization must apply `SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));` as defensive resilience against legacy analytical queries.

### Rule 8.5 — Zero HTML Leakage in API Responses
- All API endpoints must suppress raw HTML error output (`ini_set('display_errors', '0')`) and register JSON exception handlers.
- Endpoints must NEVER output HTML tags (`<br /><b>Fatal error...`) to client callers, as this corrupts JSON parsing in web and mobile applications.

---

## 9. Definition of Done

A task is complete only when:
1. The requested feature or fix operates correctly.
2. The headless separation is maintained (no server-side HTML rendering).
3. Stateless Bearer token authentication and 2FA email OTP flows remain enforced.
4. Election-state rules and transaction boundaries remain intact.
5. All icons use clean inline SVG vectors without emojis.
6. Mobile responsiveness, edge-to-edge layout, and Capacitor readiness are preserved.
7. Cloud containerization, dynamic port binding, and MySQL 8 compatibility are preserved.
8. Password-protected ballot e-Statement generation and email dispatch operate smoothly without blocking core voting flow.
9. Documentation (.md files) is updated to reflect all architectural changes.
