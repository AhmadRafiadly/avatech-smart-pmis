# Avatech Smart-PMIS - Sidang Final Hardening Context

## Goal
This project is in post-semhas revision phase. The goal is not to rebuild the system, but to answer examiner feedback with demonstrable evidence for final thesis defense.

## Current Base
Base branch: bimbingan-clean
Working branch: sidang-final-hardening

The clean thesis scope must remain stable. Advanced features from the full branch may be reused only if they directly answer examiner feedback and do not break the current demo flow.

## Main Priorities
1. Requirement Intake / document upload for PRD, process documents, MoM notes, and external links.
2. AI context from Requirement Intake while preserving Human-in-the-Loop.
3. Testing Evidence page for Black-Box, UAT, LLM Validation, and TestSprite.
4. Formula Reference for dashboard/card calculations.
5. AI Fallback Evidence through AI Monitor/diagnostics.
6. Mature AIS University demo project data.

## Hard Rules
- Do not rebuild the system from scratch.
- Do not introduce large scope creep.
- Do not implement full Google Drive API.
- Do not make AI auto-apply final decisions.
- All AI outputs must remain draft/review/apply/cancel.
- Keep metadata-only AI logging.
- Keep role-based access and project assignment guard.
- Prefer small, testable changes.
- Avoid breaking the current clean live demo flow.
- Do not run destructive database commands.

## Commands to Avoid
- php artisan migrate:fresh
- php artisan migrate:refresh
- php artisan migrate:rollback
- php artisan db:wipe

## Examiner Feedback Focus
- Explain why the system is more than Google Drive.
- Support requirement documents such as PRD/process documents.
- Clarify where MoM and AI draft data are stored.
- Prove testing results inside the system.
- Explain all dashboard/card formulas.
- Prove AI fallback behavior.
- Prepare one mature end-to-end demo project scenario.