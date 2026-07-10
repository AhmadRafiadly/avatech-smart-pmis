# Feature Spec - Requirement Intake

## Purpose
Requirement Intake dibuat untuk menjawab feedback penguji bahwa data kebutuhan proyek tidak cukup hanya tersebar di chat, dokumen, atau Google Drive. Sistem harus dapat menyimpan dokumen kebutuhan proyek dan menghubungkannya ke workflow Smart-PMIS.

## Problem Answered
- Client dapat memberikan PRD, dokumen proses berjalan, catatan meeting, atau link referensi.
- User tidak harus mengetik ulang semua kebutuhan ke MoM.
- Dokumen kebutuhan dapat menjadi konteks untuk AI MoM Fixer, AI WBS Generator, dan AI Test Case Generator.
- Smart-PMIS berbeda dari Google Drive karena dokumen terhubung ke project, MoM, WBS, task, QC, audit trail, dan AI Monitor.

## Minimum Feature
Add a Requirement Intake section inside Project Detail.

Fields:
- title
- source_type: prd, process_document, meeting_note, client_brief, google_drive_link, other
- priority: must, should, could, wont
- status: draft, reviewed, used
- summary
- file_path nullable
- external_url nullable
- created_by
- project_id

## Rules
- File upload is optional.
- External link is optional.
- Summary is required because AI context will use summary first.
- Do not implement full Google Drive API.
- Do not implement automatic PDF parsing for now.
- All changes must preserve existing Project Detail flow.
- Operational users may view assigned project requirements.
- CEO/PM and assigned operational users can add requirement notes depending on existing project access rules.

## AI Context
Requirement summary can be appended as additional context for:
- AI MoM Fixer
- AI WBS Generator
- AI Test Case Generator

AI output must remain draft-only and require review/apply/cancel.

## Evidence for Sidang
Demo flow:
1. Open AIS Universitas project.
2. Add/upload PRD or process document in Requirement Intake.
3. Add requirement summary.
4. Use requirement summary as AI context.
5. Generate WBS/Test Case draft.
6. Review and apply.
7. Show audit trail and AI monitor.

## Suggested Reuse
There is an old full-branch feature:
- commit 469f18b feat: add requirement inbox intake workflow

Do not cherry-pick blindly. Inspect and reuse only safe parts if needed.