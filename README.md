# Smart Project Management System with Multi-Provider LLM Integration

A web-based project management system developed as my Informatics Engineering thesis at UIN Syarif Hidayatullah Jakarta.

The application combines day-to-day project management workflows with optional LLM-assisted features for meeting documentation, WBS/task breakdown, test-scenario generation, and client communication drafts. AI-generated output is treated as a draft and only persisted through explicit user actions.

## Key Features

- Project, client, and team management
- Project modules, tasks, assignments, dependencies, and blockers
- Meeting minutes (MoM) and project documentation workflows
- Change requests and requirement inbox workflows
- Client review, design deliverables, UAT, QC, sign-off, and handover workflows
- Role-based access control and separate operational/admin access paths
- Audit trail for tracked application activity
- PDF exports for selected project artifacts
- Multi-provider LLM integration with fallback support

## LLM-Assisted Workflows

The application supports multiple LLM providers through a configurable fallback order:

- Google Gemini
- Groq
- OpenRouter

Current assisted workflows include:

- Structuring and improving meeting notes
- Generating WBS/module/task drafts from project and MoM context
- Generating black-box test-case drafts
- Drafting client WhatsApp and email follow-ups

Generated results are validated and returned as drafts before they are applied to project data. Provider API keys are read from environment variables and are not stored in source code.

## Tech Stack

**Backend**
- Laravel 12
- PHP 8.2+
- MySQL
- Eloquent ORM

**Frontend / Admin**
- Blade
- Livewire
- Alpine.js
- Filament 3
- Tailwind CSS 4
- Vite

**Access & Delivery**
- Spatie Laravel Permission / RBAC
- Filament Shield
- DomPDF
- PHPUnit

## Project Structure

The project is a Laravel monolith with:

- standard web routes for operational project workflows;
- a Filament-based admin layer;
- Eloquent models for project, client, team, audit, review, UAT, QC, and handover data;
- dedicated services for LLM planning/generation and audit logging.

The LLM integration is isolated behind application services and provider configuration so the rest of the project can operate independently when no provider key is configured.

## Local Setup

### Requirements

- PHP 8.2 or newer
- Composer
- Node.js and npm
- MySQL

### Installation

```bash
git clone https://github.com/AhmadRafiadly/avatech-smart-pmis.git
cd avatech-smart-pmis

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configure the MySQL connection in `.env`, then run:

```bash
php artisan migrate
npm run build
php artisan serve
```

For development with Vite:

```bash
npm run dev
```

### Optional LLM Configuration

AI features are disabled when no provider API key is configured. To enable them, add at least one provider key to `.env`.

Example configuration:

```env
AI_PROVIDER_ORDER=gemini,groq,openrouter

GEMINI_API_KEY=
GEMINI_MODEL=

GROQ_API_KEY=
GROQ_MODEL=

OPENROUTER_API_KEY=
OPENROUTER_MODEL=

AI_TIMEOUT_SECONDS=30
```

Do not commit real API keys or environment credentials.

## Thesis Context

**Concise English title:**  
Smart Web-Based Project Management System Using RUP with Multi-Provider LLM API Integration

The project was developed and deployed as part of my Bachelor's degree in Informatics Engineering. It applies the Rational Unified Process (RUP) and explores how LLM APIs can support project documentation, task breakdown, and testing workflows while keeping human approval in the loop.

## Author

**Ahmad Rafiadly Arlisyah**  
Software Developer / Full-Stack Web Developer

[LinkedIn](https://www.linkedin.com/in/ahmad-rafiadly-arlisyah)
