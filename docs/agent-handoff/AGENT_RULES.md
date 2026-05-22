# Agent Rules

These rules apply to every coding agent working on this project.

## Core workflow
1. Read these files before editing:
   - docs/agent-handoff/PROJECT_STATE.md
   - docs/agent-handoff/AGENT_RULES.md
   - docs/agent-handoff/ROLE_MATRIX.md
   - docs/agent-handoff/UI_STATUS.md
   - docs/agent-handoff/NEXT_TASK.md
   - docs/agent-handoff/AI_PLAN.md
2. Inspect relevant files first.
3. Show plan, affected files, and risks.
4. Wait for approval before editing.
5. After editing, summarize changes and run minimal verification.
6. Do not commit unless explicitly asked.

## Hard rules
- Do not redesign approved UI unless requested.
- Do not change auth/RBAC unless requested.
- Do not change DB/migrations/seeders unless requested.
- Do not initialize Git, push to GitHub, or change remote settings.
- Do not touch .env.
- Keep CEO/PM UI stable.
- Keep AI setup pending until explicitly requested.

## Multi-agent rules
- Only one agent may edit files at a time.
- Before switching agent, create a Git checkpoint commit.
- Other agents may review/audit, but should not edit at the same time.
