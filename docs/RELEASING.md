# Release Process

This document explains how automated releases work in gitty and how to create new releases.

## Overview

When you push a version tag (e.g., `v1.2.3`) to GitHub:

1. GitHub Actions automatically triggers
2. Builds macOS DMG files for both Intel (x64) and Apple Silicon (arm64)
3. Creates a GitHub Release with the DMG files attached
4. Users can download and install the new version

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
2. **Setup PHP 8.4** and install Composer dependencies
3. **Setup Node.js** and install NPM dependencies
4. **Build frontend assets** with Vite
5. **Update version** in config from the tag
6. **Build macOS app** for both x64 and arm64 architectures
7. **Create GitHub Release** with release notes
8. **Upload DMG files** and blockmap files (for auto-updates)

The workflow runs on `macos-latest` runners to ensure proper macOS code signing.

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
