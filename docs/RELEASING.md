# Release Process

This document explains how automated releases work in gitty and how to create new releases.

## Overview

When you push a version tag (e.g., `v1.2.3`) to GitHub:

1. GitHub Actions automatically triggers
2. Builds macOS DMG files for both Intel (x64) and Apple Silicon (arm64)
3. Creates a GitHub Release with the DMG files attached
4. Users can download and install the new version

## Prerequisites (One-Time Setup)

Code signing and notarization are required for macOS distribution. Without signing, macOS Gatekeeper will block the app.

### 1. Apple Developer Account

Enroll in the [Apple Developer Program](https://developer.apple.com/programs/) ($99/year). This is required for code signing certificates and notarization.

### 2. Create a Developer ID Application Certificate

In the Apple Developer portal, create a **Developer ID Application** certificate. This is the correct certificate type for apps distributed outside the Mac App Store.

> **Important:** Do NOT use a "Mac App Store" certificate — gitty is distributed via GitHub Releases, not the App Store.

Steps:
1. Go to [Certificates, Identifiers & Profiles](https://developer.apple.com/account/resources/certificates/list)
2. Click "+" to create a new certificate
3. Select **Developer ID Application**
4. Follow the CSR (Certificate Signing Request) instructions
5. Download and install the certificate in Keychain Access

### 3. Export as .p12

1. Open **Keychain Access** on your Mac
2. Find the "Developer ID Application" certificate (under "My Certificates")
3. Right-click → **Export**
4. Choose `.p12` format and set a strong password
5. Save the file securely

### 4. Base64-Encode the Certificate

The `.p12` file must be base64-encoded for use as a GitHub Secret:

```bash
base64 -i certificate.p12 | pbcopy
```

This copies the encoded string to your clipboard, ready to paste into GitHub Secrets.

### 5. Generate an App-Specific Password

Apple notarization requires an app-specific password (not your Apple ID password):

1. Go to [https://appleid.apple.com/account/manage](https://appleid.apple.com/account/manage)
2. Sign in with your Apple ID
3. Under **Sign-In and Security**, select **App-Specific Passwords**
4. Click "Generate" and name it (e.g., "gitty-notarization")
5. Copy the generated password

### 6. Find Your Team ID

1. Go to [Apple Developer Membership](https://developer.apple.com/account#MembershipDetailsCard)
2. Your **Team ID** is a 10-character alphanumeric string
3. Copy it for use in GitHub Secrets

## GitHub Secrets

Configure these secrets in your GitHub repository under **Settings → Secrets and variables → Actions**:

| Secret | Description |
|--------|-------------|
| `MACOS_CERTIFICATE_P12` | Base64-encoded Developer ID Application `.p12` certificate |
| `MACOS_CERTIFICATE_PASSWORD` | Password used when exporting the `.p12` file |
| `APPLE_ID` | Apple ID email address used for notarization |
| `APPLE_APP_SPECIFIC_PASSWORD` | App-specific password generated at appleid.apple.com (NOT your account password) |
| `APPLE_TEAM_ID` | 10-character Team ID from the Apple Developer portal |

All five secrets are required for signed builds. If any are missing, the workflow will still build the app but it will be **unsigned** (macOS Gatekeeper will warn users).

## Creating a Release

### Using the Release Script (Recommended)

The easiest way to create a release:

```bash
./bin/release
```

This interactive script will:
1. Show your current version
2. Ask what type of release (patch/minor/major/custom)
3. Calculate the new version number
4. Update `config/nativephp.php`
5. Create a git commit
6. Create a git tag

After the script completes, push your changes:

```bash
git push origin main && git push origin v1.2.3
```

### Manual Release

If you prefer to do it manually:

1. **Update the version** in `config/nativephp.php`:
   ```php
   'version' => env('NATIVEPHP_APP_VERSION', '1.2.3'),
   ```

2. **Commit the version bump**:
   ```bash
   git add config/nativephp.php
   git commit -m "chore(release): bump version to 1.2.3"
   ```

3. **Create a git tag**:
   ```bash
   git tag -a v1.2.3 -m "Release v1.2.3"
   ```

4. **Push to GitHub**:
   ```bash
   git push origin main
   git push origin v1.2.3
   ```

## Semantic Versioning

We follow [Semantic Versioning](https://semver.org/):

- **Patch** (1.0.0 → 1.0.1): Bug fixes, no breaking changes
- **Minor** (1.0.0 → 1.1.0): New features, backward compatible
- **Major** (1.0.0 → 2.0.0): Breaking changes

## GitHub Actions Workflow

The release workflow (`.github/workflows/release.yml`) performs these steps:

1. **Checkout code** from the tagged commit
2. **Setup PHP 8.4** with Composer caching
3. **Setup Node.js 22** with NPM caching
4. **Install Composer dependencies** (production only, cached)
5. **Install NPM dependencies**
6. **Build frontend assets** with Vite
7. **Import code signing certificate** — decodes the base64 `.p12` from secrets and imports into a temporary keychain (conditional: only runs if `MACOS_CERTIFICATE_P12` secret is set)
8. **Extract version** from the git tag
9. **Create .env file** with version and GitHub updater settings
10. **Setup database** (SQLite) and run migrations
11. **Update version** in NativePHP config from the tag
12. **Build macOS app** for both x64 and arm64 — signing env vars (`CSC_LINK`, `CSC_KEY_PASSWORD`, `APPLE_ID`, `APPLE_APP_SPECIFIC_PASSWORD`, `APPLE_TEAM_ID`) are passed to the build. NativePHP's `notarize.js` afterSign hook handles notarization automatically.
13. **Create GitHub Release** with DMGs, blockmap files, and `latest-mac.yml` (required for auto-updates)
14. **Cleanup keychain** — removes the temporary keychain (always runs, even on failure)

The workflow runs on `macos-latest` runners to ensure proper macOS builds.

### Environment Variables Set During Build

The workflow automatically configures these environment variables:

- `NATIVEPHP_APP_VERSION`: Extracted from the git tag
- `NATIVEPHP_APP_ID`: `com.gitty.app`
- `NATIVEPHP_UPDATER_ENABLED`: `true`
- `NATIVEPHP_UPDATER_PROVIDER`: `github`
- `GITHUB_REPO`: Auto-detected from repository
- `GITHUB_OWNER`: Auto-detected from repository owner
- `GITHUB_V_PREFIXED_TAG_NAME`: `true`
- `GITHUB_RELEASE_TYPE`: `release`

No manual configuration needed!

## Auto-Updates

gitty includes NativePHP's auto-update mechanism:

- Configured in `config/nativephp.php` under `updater`
- Uses GitHub Releases as the update source
- Downloads and installs updates automatically when users launch the app
- Requires these environment variables (set in `.env`):
  - `GITHUB_REPO`: Your repository name
  - `GITHUB_OWNER`: Your GitHub username/org
  - `GITHUB_V_PREFIXED_TAG_NAME=true`: Tags use `v` prefix
  - `GITHUB_RELEASE_TYPE=release`: Only check stable releases (not drafts)

Users will be prompted to update when a new version is available.

> **Signing required:** Auto-updates **require a signed and notarized app**. Unsigned builds will fail update validation because macOS rejects code signature changes. The `latest-mac.yml` file (uploaded to each GitHub Release) contains the version metadata and download URLs that the updater checks.

> **First signed release:** If you are transitioning from unsigned to signed builds, existing users with unsigned installs must **manually download** the first signed release. Subsequent updates will work automatically via the auto-updater.

## Release Checklist

Before creating a release:

- [ ] All tests passing (`php artisan test`)
- [ ] Frontend builds without errors (`npm run build`)
- [ ] Changelog/release notes prepared
- [ ] Version number follows semver
- [ ] `.env.example` updated if new env vars added
- [ ] Documentation updated for new features

## Testing the Build Locally

To test the build process before pushing a tag:

```bash
php artisan native:build mac arm64
```

The DMG will be in `nativephp/electron/dist/`.

## Verifying Signed Builds

After downloading a release DMG, you can verify the code signature and notarization:

```bash
# Verify code signature
codesign --verify --deep --strict /path/to/Gitty.app

# Check Gatekeeper acceptance (includes notarization check)
spctl -a -t exec -vvv /path/to/Gitty.app
```

Expected output for a properly signed and notarized app:

```
/path/to/Gitty.app: valid on disk
/path/to/Gitty.app: satisfies its Designated Requirement
```

```
/path/to/Gitty.app: accepted
source=Notarized Developer ID
```

### Certificate Expiry

Developer ID Application certificates are valid for **5 years** from the date of creation. When a certificate expires:

1. Create a new Developer ID Application certificate in the Apple Developer portal
2. Export as `.p12` and base64-encode it
3. Update the `MACOS_CERTIFICATE_P12` and `MACOS_CERTIFICATE_PASSWORD` secrets in GitHub

Existing signed builds remain valid — only new builds require the renewed certificate.

## Troubleshooting

### Build fails in GitHub Actions

- Check the Actions tab on GitHub for error logs
- Verify all secrets are set correctly
- Ensure PHP and Node versions match local development

### DMG not appearing in Release

- Check that the tag name starts with `v` (e.g., `v1.2.3`)
- Verify the workflow completed successfully
- Check the DMG files were created in the build step

### Auto-updates not working

- Verify `GITHUB_REPO` and `GITHUB_OWNER` in `.env`
- Ensure releases are marked as "releases" not "drafts"
- Check blockmap files were uploaded to the release
- Verify `latest-mac.yml` was uploaded to the GitHub Release — this file is required for the updater to detect new versions
- Ensure the app is **signed** — unsigned apps cannot auto-update

### Notarization failed

- Verify `APPLE_ID`, `APPLE_APP_SPECIFIC_PASSWORD`, and `APPLE_TEAM_ID` secrets are correct
- Ensure the app-specific password is still valid (they can be revoked)
- Check that your Apple Developer account is in good standing
- Review the notarization log in the GitHub Actions output for specific error codes

### Certificate not found during build

- Verify `MACOS_CERTIFICATE_P12` contains the full base64-encoded certificate (no line breaks or truncation)
- Verify `MACOS_CERTIFICATE_PASSWORD` matches the password used during `.p12` export
- Ensure the certificate is a **Developer ID Application** type (not Mac App Store or development)
- Re-export and re-encode if needed: `base64 -i certificate.p12 | pbcopy`

### "App is damaged" or Gatekeeper warning

- The app was likely built without signing — rebuild with all 5 secrets configured
- Users can temporarily bypass with: `xattr -cr /path/to/Gitty.app` (not recommended for distribution)

### Signed builds work but unsigned builds broke

- The workflow is designed to be backwards-compatible: builds still succeed without signing secrets, they just produce unsigned DMGs
- Check if a required secret was accidentally deleted

## Files Modified During Release

- `config/nativephp.php`: Version number updated
- Git tags: New version tag created
- GitHub: Release and DMG files published

## Environment Variables

Production releases require these environment variables (configure in GitHub Secrets for private repos):

```env
GITHUB_REPO=gitty
GITHUB_OWNER=yourusername
GITHUB_TOKEN=ghp_your_token_here
```

For public repos, `GITHUB_TOKEN` is automatically provided by GitHub Actions.
