# Gitty Developer Documentation

## Quick Start

Gitty is a macOS-native git client built with NativePHP (Laravel + Electron wrapper), Livewire 4 for reactive components, and Flux UI Free for the component library. The application uses a service-oriented architecture with DTOs for data transfer, event-driven communication between components, and Tailwind CSS v4 with the Catppuccin Latte color palette for styling.

## Table of Contents

- [Architecture & Patterns](#architecture--patterns)
- [API Reference](#api-reference)
- [Frontend](#frontend)
- [Guides](#guides)
- [Related Resources](#related-resources)
- [Tech Stack Summary](#tech-stack-summary)

---

## Architecture & Patterns

### [Architecture Overview](architecture.md)
System layers, boot process, dependency injection, service-oriented architecture, and core design patterns used throughout gitty.

---

## API Reference

### [Services](services.md)
All 20+ services with public method signatures, parameters, return types, and usage examples. Covers GitService, DiffService, RepositoryService, and more.

### [DTOs](dtos.md)
All 15 data transfer objects with complete property tables, types, descriptions, and usage patterns. Includes FileStatus, DiffHunk, CommitInfo, and others.

### [Components](components.md)
All 18 Livewire components with properties, methods, events, and integration examples. Covers staging panel, diff viewer, commit panel, and all UI components.

### [Event System](events.md)
Complete event map with flow diagrams, event payloads, listeners, and cross-component communication patterns.

---

## Frontend

### [Frontend Architecture](frontend.md)
CSS architecture (Tailwind v4, Catppuccin Latte), Alpine.js patterns, Flux UI component usage, NativePHP integration, and styling conventions.

---

## Guides

### [Features](features.md)
All features from a developer perspective: staging, committing, branching, diffing, repository management, and keyboard shortcuts.

### [Testing](testing.md)
Test infrastructure, patterns, how to write tests, running tests, and coverage for unit tests, feature tests, and Livewire component tests.

### [Common Tasks](common-tasks.md)
Cookbook for common development tasks: adding features, debugging, extending services, creating components, and troubleshooting.

---

## Related Resources

- **[AGENTS.md](../AGENTS.md)** — Design system guidelines, color palette, Flux UI conventions, icon usage, and UI/UX patterns
- **[NativePHP Documentation](https://nativephp.com)** — Official NativePHP docs for Electron integration
- **[Flux UI Documentation](https://fluxui.dev)** — Official Flux UI component library docs
- **[Livewire Documentation](https://livewire.laravel.com)** — Official Livewire 4 docs
- **[Catppuccin Palette](https://catppuccin.com/palette/)** — Color palette reference

---

## Tech Stack Summary

| Technology | Version | Purpose |
|------------|---------|---------|
| **NativePHP** | Latest | Electron wrapper for macOS desktop app |
| **Laravel** | 12 | Backend framework, routing, services |
| **Livewire** | 4 | Reactive PHP components, frontend logic |
| **Flux UI Free** | v2 | Component library (buttons, dropdowns, modals) |
| **Tailwind CSS** | v4 | Utility-first CSS framework with JIT |
| **Pest** | 4 | Testing framework (unit + feature tests) |
| **PHP** | 8.4 | Language runtime |
| **SQLite** | — | Database for repository metadata |
| **Catppuccin Latte** | — | Color palette (light theme) |
| **Phosphor Icons** | — | Icon set (light variant for headers) |

---

**Navigation:** [Architecture](architecture.md) · [Services](services.md) · [DTOs](dtos.md) · [Components](components.md) · [Events](events.md) · [Frontend](frontend.md) · [Features](features.md) · [Testing](testing.md) · [Common Tasks](common-tasks.md)
