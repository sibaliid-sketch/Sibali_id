# TODO: Implement TOTP 2FA Replacement

## A. DEPLOY PREP
- [ ] Create branch feature/totp-2fa
- [ ] Backup DB to infrastructure/firewall-backup/db_backup.sql
- [ ] Export firewall rules to infrastructure/firewall-backup/firewall_rules.json

## B. DEPENDENCIES
- [ ] composer require pragmarx/google2fa bacon/bacon-qr-code

## C. DATABASE
- [ ] Create migration add_two_factor_fields_to_users_table.php with two_factor_secret, two_factor_recovery_codes, two_factor_enabled_at (encrypted)
- [ ] Run php artisan migrate

## D. MODEL (User)
- [ ] Add setTwoFactorSecret/getTwoFactorSecret accessors using Crypt
- [ ] Add setTwoFactorRecoveryCodes/getTwoFactorRecoveryCodes accessors using Crypt

## E. CONTROLLER (TwoFactorController)
- [ ] Create TwoFactorController with enable() method: generate secret, recovery codes, QR base64
- [ ] Add verify() method: check TOTP code or recovery code, set session or JWT
- [ ] Implement generateQrBase64() using bacon-qr-code

## F. MIDDLEWARE (EnsureTwoFactorPassed)
- [ ] Create middleware to check session('2fa_passed') or token claim
- [ ] Register in Kernel.php
- [ ] Apply to sensitive routes (admin, finance, etc.)

## G. AUTH FLOW
- [ ] Update EnhancedLoginController: after password valid, if 2FA enabled, save temp user id, redirect to 2FA verify
- [ ] After verify success, set session('2fa_passed'), complete login
- [ ] If not setup, provide link to enable

## H. UI/UX
- [ ] Update auth/two-factor/setup.blade.php: show TOTP QR, secret, steps for Google Authenticator
- [ ] Create auth/two-factor/verify.blade.php: input 6 digit, verify button, recovery option
- [ ] Show recovery codes once on enable

## I. DISABLE OLD SYSTEM
- [ ] Comment/disable old TwoFactorAuthController routes
- [ ] Comment/disable TwoFactorAuthService
- [ ] Disable Layer10_TwoFactorAuth in FirewallManager
- [ ] Remove SMS/email OTP configs from .env (set to empty)
- [ ] Comment/disable any OTP jobs/cron/workers (if found)

## J. RATE LIMIT & LOCKOUT
- [ ] Add throttle middleware to verify route: throttle:5,1
- [ ] Implement lockout: track failures, alert admin after X failures

## K. AUDIT & LOGGING
- [ ] Log events: 2fa.enabled, 2fa.verified.success, 2fa.verified.fail, 2fa.recovery.used, 2fa.secret.regenerated
- [ ] Use activity_log or create two_factor_audit_logs table

## L. TESTS
- [ ] Unit tests: generate secret, verify valid/invalid TOTP, recovery codes consumption
- [ ] Integration tests: login -> 2FA prompt -> verify -> complete

## M. ROLLOUT PLAN
- [ ] Deploy to staging
- [ ] Run migration on staging
- [ ] QA & security review
- [ ] Deploy to production, run migration
- [ ] Monitor logs & lockouts 24-72 hours

## N. ROLLBACK PLAN
- [ ] Revert branch & code if issues
- [ ] Reapply firewall rules from backup
- [ ] Repopulate .env keys from secure vault
- [ ] Rollback migration if destructive

## O. CHECKLIST DISABLE OLD
- [ ] Delete/comment SMS OTP queue handlers
- [ ] Delete/comment email OTP templates/mailers
- [ ] Delete/comment cron/worker triggering OTP
- [ ] Remove API keys from .env
- [ ] Disable firewall rules related to OTP
- [ ] Update docs & SOP (archive legacy)
