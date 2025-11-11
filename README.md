# Overview of FeedLoop

## Abstract
FeedLoop is a PHP-based feedback management platform designed to capture, classify, and triage responses from academic communities. The system combines a configurable public-facing form engine with an administrative command center that unifies legacy feedback tickets and modern custom-form submissions. This dossier documents the current implementation, data flow, and operational considerations to guide future research, maintenance, and extension activities.

## System Goals & Context
- Provide a unified channel for students, guests, and staff to submit structured and unstructured feedback.
- Equip administrators with a consolidated dashboard for triage, analytics, and respondent follow-up.
- Preserve respondent privacy while still surfacing identities when submissions are non-anonymous.
- Maintain extensibility via modular forms, configurable landing experiences, and pluggable notification services.

## Architecture Summary
| Layer | Responsibilities | Key Assets |
| --- | --- | --- |
| Presentation | Landing page variants, public form UI, admin console | `index.php`, `index.html`, `admin/dashboard_admin/admin_dashboard.php` |
| Application | Feedback ingestion, custom form orchestration, notification workflows | `public/form/index.php`, `admin/content/feedback_management/view_feedback_content.php`, `includes/EmailService.php` |
| Data | Relational persistence, analytics triggers, configuration | `db.php`, `feedloop_db.sql`, `config/landing_config.php` |

The entry point (`index.php`) performs role-aware routing, forwarding authenticated actors to their dashboards and anonymous visitors to the configured landing experience @index.php#29-98 @config/landing_config.php#9-72.

## Request & Data Flow
1. **Landing / Authentication**: Visitors interact with the selected landing variant; admins authenticate through the unified login, which seeds session metadata used throughout the admin console @admin/dashboard_admin/admin_dashboard.php#1-200.
2. **Submission**: Public respondents complete custom forms. The submission handler validates required questions, writes to `form_responses` and `form_answers`, updates analytics, and redirects to a thank-you view @public/form/index.php#84-218.
3. **Consolidation**: Admin "All Feedback" view merges legacy `feedback_submissions` with the most recent custom-form responses so identity data appears immediately when provided @admin/content/feedback_management/view_feedback_content.php#60-391.
4. **Review & Response**: Admins inspect details, respond via modal workflows, and trigger notifications or exports for further analysis @admin/dashboard_admin/admin_dashboard.php#162-305.

## Data Model Highlights
The `feedloop_db.sql` schema models classic feedback (`feedback_submissions`) alongside richer custom-form entities (`custom_forms`, `form_questions`, `form_responses`, `form_answers`). Triggers keep response counts and analytics synchronized after each submission @feedloop_db.sql#334-437.

## Module Deep Dives
### Landing & Routing
- Configurable landing strategy toggles between HTML, PHP, or maintenance modes, exposing endpoints for dynamic stats and announcements @config/landing_config.php#9-72.
- Smart router enforces session-aware redirects, preventing authenticated actors from re-visiting the public landing page @index.php#29-98.

### Authentication & Session Management
- Admin dashboard hardens PHP sessions (cookie flags, audit logs) and validates roles before rendering privileged views @admin/dashboard_admin/admin_dashboard.php#1-130.
- Session activity timestamps update on dashboard load to support the "Active Sessions" widget and multi-tab safeguards @admin/dashboard_admin/admin_dashboard.php#85-127.

### Administrative Console
- Sidebar-delivered SPA experience loads feature panes (Manage Admins, Custom Forms, Feedback, Settings) without full page reloads @admin/dashboard_admin/admin_dashboard.php#162-305.
- Dashboard metrics aggregate category-level feedback counts, admin totals, and recent activity snapshots to inform triage priorities @admin/dashboard_admin/admin_dashboard.php#53-124.

### Feedback Management Pipeline
- Unified table lists legacy submissions alongside recent custom-form responses, standardizing submitter display logic (name, email, respondent type, anonymous badges) @admin/content/feedback_management/view_feedback_content.php#60-391.
- AJAX modals fetch detailed payloads, enable responses, and update resolution status without navigating away from the dashboard context @admin/content/feedback_management/view_feedback_content.php#395-517.

### Custom Forms & Analytics
- Access control: only form creators or super admins can view responses; permissions are derived from `admins.position` join data @admin/content/custom_forms/view_responses.php#28-55.
- Response aggregation: submissions are grouped per respondent with serialized answers, trend charts, and export tooling for deeper analysis @admin/content/custom_forms/view_responses.php#56-342.

### Public Form Processing
- Validates required fields, infers respondent type from email domains, persists responses, and updates analytics counters in a single transaction @public/form/index.php#84-213.
- Custom trigger recalculates form completion counts and last response timestamps to keep admin dashboards in sync @feedloop_db.sql#392-437.

### Notifications & Email Service
- `EmailService` supports password resets, confirmations, and registration OTPs via Gmail SMTP or file-based fallbacks, with rate limiting and logging @includes/EmailService.php#1-490.
- Notification payloads are logged to the database for observability, preserving IP/user-agent context for audit trails @includes/EmailService.php#170-210.

## Security & Privacy Considerations
- Session cookies marked `HttpOnly`, `SameSite=Lax`, and conditionally `Secure` to mitigate hijacking vectors @admin/dashboard_admin/admin_dashboard.php#1-45.
- Respondent identities surfaced only when voluntarily provided; otherwise, UI defaults to "Anonymous" or explicit anonymous badges @admin/content/feedback_management/view_feedback_content.php#218-366.
- Email workflows throttle reset attempts and record activity, reducing abuse risk @includes/EmailService.php#170-210.

## Deployment & Environment
- **Runtime**: PHP 8.x+, MySQL/MariaDB, Apache (bundled via XAMPP) @db.php#1-22.
- **Database initialization**: Import `feedloop_db.sql`, ensuring triggers and analytics tables are applied @feedloop_db.sql#334-437.
- **Configuration**: Update `config/landing_config.php` for landing strategy; configure email credentials in `config/email_config.php` consumed by `EmailService` @config/landing_config.php#9-72 @includes/EmailService.php#18-210.
- **File permissions**: Logs directory must be writable for debug and email fallbacks (`logs/`, `logs/email_debug.log`).

## Testing & Observability
- Admin dashboard logs session snapshots and user agents to `logs/debug.log`, facilitating forensic investigations @admin/dashboard_admin/admin_dashboard.php#13-29.
- Email fallback writes plaintext transcripts to `logs/email_debug.log` when SMTP fails, enabling QA of notification templates @includes/EmailService.php#149-168.
- Manual form submissions recommended after schema or UI changes to confirm consolidated feedback rendering.

## Known Limitations & Future Work
- No automated test suite; rely on manual verification, especially for complex form analytics.
- Notifications only cover password/registration flows; extending to feedback response alerts would complete the loop.
- Landing page configuration is static; introducing runtime toggles or CMS integration could streamline marketing updates.

## Directory Reference (Selected)
- `admin/dashboard_admin/` – Admin shell & layout assets.
- `admin/content/feedback_management/` – Feedback list, filters, response modals.
- `admin/content/custom_forms/` – Form builder, response analytics, exports.
- `public/form/` – Public form rendering, submission controller.
- `includes/` – Shared services (email, activity logs, security helpers).
- `assets/` – Frontend scripts and styles for both public and admin experiences.
- `config/` – Environment-level toggles and service credentials.
- `database/` / `database_updates/` – SQL scripts for feature-specific migrations.

## Appendix: Operational Checklist
1. Configure `.env`/config files for database and email secrets.
2. Import SQL schema and verify triggers applied successfully.
3. Seed at least one super admin via `admins` + `users` tables.
4. Submit test feedback and custom form responses to validate consolidation pipeline.
5. Inspect admin dashboard metrics and active session widgets for consistency.
6. Test password reset flow to confirm SMTP or file logging fallback behavior.

This research dossier should serve as a living reference for maintainers and researchers iterating on FeedLoop’s architecture. Future contributions should append empirical findings, performance benchmarks, and user studies to extend this knowledge base.
