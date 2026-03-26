# CATMIN V1 Reference

## Purpose

This document explains the current CATMIN V1 architecture in simple terms.
It is meant to help a developer understand:

- what already exists
- what is still legacy PHP
- what has already been moved into Laravel
- how the admin works today
- how the module and settings systems are being prepared
- what to do next without breaking the project

## Project State

CATMIN is currently a hybrid application.

Two worlds coexist:

1. Legacy PHP pages and dashboard assets
2. A Laravel application gradually taking over routing, auth, admin views, settings, and modular structure

The migration strategy is progressive on purpose.
The project avoids a destructive rewrite.

## Main Principles

### 1. Do not break the existing dashboard

The legacy dashboard still exists and remains useful during the migration.
It provides:

- working assets
- working page structures
- stable visual references
- source material for Laravel Blade integration

### 2. Move logic before moving design

CATMIN does not aim to redesign everything first.
The current strategy is:

- centralize configuration
- introduce services and helpers
- add Laravel routing and auth
- move views into Blade while keeping the existing look

### 3. Keep the code understandable

New pieces are added in focused layers:

- config
- services
- helpers
- models
- seeders
- Blade views

This reduces the risk of another hard-to-maintain monolith.

## Current High-Level Architecture

```text
CATMIN
├── app/
│   ├── Helpers/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Services/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── dashboard/
│   ├── assets/
│   ├── components/
│   ├── content/
│   ├── index.php
│   ├── login.html
│   └── page_403.html / page_404.html / page_500.html
├── frontend/
├── modules/
├── resources/views/
│   ├── admin/
│   └── frontend/
└── routes/
```

## Admin Logic

### Current admin entry

The Laravel admin path is centralized in configuration.

Default path:

`/admin`

This path is controlled by:

- `config/catmin.php`
- `.env` via `CATMIN_ADMIN_PATH`
- `AdminPathService`
- `AdminPathHelper`

That means the admin prefix can be changed later without rewriting every route.

### Admin routes

Admin routes currently cover:

- login
- logout
- admin access redirect
- legacy page preview
- bridge page
- error pages

The routes are defined in [routes/web.php](/var/www/catmin.local/catmin/routes/web.php).

### Authentication

The project currently contains two admin-related user concepts:

1. `admins` table for the initial CATMIN admin auth flow
2. `users` + `roles` + `user_roles` for the future RBAC foundation

This is acceptable for the current phase because the system is being migrated progressively.

### Admin views

The admin now uses Blade views in:

- `resources/views/admin/layouts`
- `resources/views/admin/partials`
- `resources/views/admin/pages`

Important pieces already migrated:

- login page
- error pages 403, 404, 500
- sidebar
- head/footer structure

## Legacy Dashboard Logic

### Role of `dashboard/index.php`

The legacy `index.php` is still an important bridge.

Its job is simple:

1. accept a `page` parameter
2. validate it against a whitelist
3. include the header component
4. include the aside component
5. include the topnav component
6. include the corresponding content HTML file
7. include the footer component

This means `index.php` is a loader.
It is not a full application framework.

### Role of `dashboard/content/`

The `content/` directory stores individual page bodies.
These files are mostly static HTML blocks.

Examples:

- dashboard
- charts
- tables
- widgets
- forms
- media gallery

These files are useful because they can be migrated into Blade gradually without rewriting their content first.

### Role of `dashboard/components/`

The `components/` directory contains shared layout parts.

Important files:

- header.php
- aside.php
- topnav.php
- footer.php

These files currently define:

- CSS and JS loading
- shared navigation structure
- layout shell
- shared UI behavior

They are the main source for Blade migration.

## Module Logic

### Goal

CATMIN is being prepared as a modular system.

The idea is to allow features to live in isolated modules such as:

- blog
- media
- users
- settings
- seo
- pages

### Current implementation

The project now contains a first module layer through:

- `ModuleManager`
- `ModuleLoader`
- `module:list` Artisan command
- `modules` database table

### How modules work right now

A module is primarily discovered from files.

Current source of truth for module discovery:

- `modules/*/module.json`

The database table `modules` exists to prepare future state tracking.
This hybrid approach is intentional.

Why this choice works:

- file discovery is simple and stable during development
- database state will be useful later for activation, config and admin tooling

### Navigation and modules

The admin navigation is now configuration-driven and can already show module-backed sections.
That means the UI is no longer fully hardcoded.

## Settings Logic

A first settings system now exists.

Core parts:

- `settings` database table
- `Setting` model
- `SettingService`
- `SettingSeeder`
- `setting()` helper

Current goals of this system:

- centralize global options
- avoid repeated database queries
- provide defaults from config
- prepare frontend and modules to consume shared values

Examples of settings already supported:

- site name
- site URL
- admin theme
- admin path
- frontend enabled flag

## Helper Logic

A first helper toolbox exists now.

Helpers include:

- `admin_path()`
- `admin_route()`
- `admin_url()`
- `setting()`
- `module_enabled()`
- `module_info()`
- `catmin_navigation()`
- `catmin_theme()`

These helpers matter because they keep Blade templates and controllers readable.

## Database Foundations

The current database now includes the main foundation tables for the migration:

- `users`
- `admins`
- `roles`
- `user_roles`
- `settings`
- `modules`
- Laravel system tables like cache, jobs, sessions, migrations

This is enough to support:

- early auth
- RBAC preparation
- settings storage
- module state preparation

It is not yet the final CMS schema.

## Current Git Conventions

The work done so far follows a prompt-by-prompt workflow.

For each prompt:

1. implement the requested change
2. validate the change
3. document the result
4. move the prompt file to `prompts/effectue/`
5. create a clean git commit
6. push to GitHub

Commit messages are explicit and tied to the prompt number.
This is useful for traceability.

## What Has Been Completed in This Phase

Prompts completed in this sequence:

- 015 integration plan
- 016 database setup
- 017 admin routing and path centralization
- 018 Blade admin layout
- 019 authentication base and system pages foundation
- 020 module loader base
- 021 include migration plan
- 022 admin path documentation and consolidation
- 023 database foundations
- 024 seed admin and base roles
- 025 dynamic admin navigation
- 026 global settings system
- 027 first CATMIN helpers
- 028 frontend Laravel foundation
- 029 Laravel integration of login and error pages

## Known Constraints

These constraints are normal for the current stage:

- the application is hybrid, not fully Laravel-native yet
- some legacy HTML is still the visual reference
- RBAC exists as a foundation, not a full authorization system yet
- modules are discovered from files first, database state second
- public frontend content models are not built yet

These are not failures.
They are part of the progressive migration strategy.

## Safe Next Steps

The next reasonable steps are:

1. move more legacy includes into Blade progressively
2. introduce page/content models for the public side
3. connect module activation more tightly to the database table
4. add admin interfaces for settings and modules
5. add real permission middleware on top of the RBAC foundation
6. progressively document module contracts

## What a New Developer Should Understand First

If you are new to CATMIN, start with these facts:

1. The project is not meant to be rewritten in one shot.
2. The legacy dashboard is still part of the migration strategy.
3. Laravel is becoming the new application shell.
4. Services and helpers are the safest entry points for new work.
5. Configuration is being centralized to avoid hardcoded paths and logic.
6. If you need to change behavior, prefer the new Laravel services over editing legacy files directly.

## Summary

CATMIN V1 is a progressive migration project.

It already has:

- centralized admin routing
- Laravel Blade admin views
- initial authentication foundation
- a first module system
- a first settings system
- helper functions for clean reuse
- a public Laravel frontend foundation

It still keeps the legacy dashboard alive because that is the safest path to continuity.

This is the correct model to use when maintaining the project today:

- preserve what works
- centralize new logic
- migrate in layers
- document every step
