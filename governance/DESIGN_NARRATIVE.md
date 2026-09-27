# Shopnear System Architecture & Design Narrative

## 1. Overview
Shopnear is an ultra-fast, glassmorphic multi-vendor hyperlocal and B2C e-commerce marketplace platform. Built on zero-trust tenant isolation, atomic split-payment escrow ledgers, dynamic SEO structured data, multi-language localization (en, ml, hi), headless CMS landing pages, sponsored ad campaigns, and a demo payment gateway.

## 2. Architecture Specification
- **Core Engine (`/core/`)**: Immutable framework containing PDO database connection, SQLite migrations, REST router with semantic slug support, JWT security (`ApiAuth.php`), Google JSON-LD rich snippets (`SEO.php`), and localization driver (`Localization.php`).
- **Dynamic Modules (`/app/dynamic-modules/`)**: Sandbox for vendor extensions, checkout and atomic escrow settlement (`OrderController.php`), demo payment gateway (`DemoPaymentController.php`), CMS renderer (`LandingPageController.php`), sponsored placement ad engine (`AdController.php`), mobile REST API endpoints (`ApiController.php`), and glassmorphic bento-grid UI templates (`ViewController.php`).
- **Governance (`/governance/`)**: Immutable constitution (`domain-scope.json`) preventing cross-domain drift.
- **Control Plane (`/agent-workspace/`)**: Agent permissions manifest, circuit breaker resource quotas, and append-only evolution log.

## 3. System Evolution Changelog
- **2026-09-27 [v1.0.0]**: Initial system initialization of Shopnear multi-vendor e-commerce platform with core framework, atomic escrow ledger, glassmorphic UI, API gateway, ad placements, and demo payment simulator. Verified with test suite (`tests/run_tests.php`).
