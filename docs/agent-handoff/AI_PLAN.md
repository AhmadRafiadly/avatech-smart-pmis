# AI Plan

AI setup is postponed.

Planned architecture:
- One Laravel AiService
- Provider config through .env
- Fallback order:
  1. Gemini
  2. Groq
  3. OpenRouter

Planned .env concept:
AI_PROVIDER=auto
AI_FALLBACK_ORDER=gemini,groq,openrouter

GEMINI_API_KEY=
GEMINI_MODEL=

GROQ_API_KEY=
GROQ_MODEL=

OPENROUTER_API_KEY=
OPENROUTER_MODEL=

Planned AI features:
- Generate WBS
- Fix / summarize MoM
- Generate Test Cases
- AI reminders / risk alerts later

Rules:
- Do not implement AI yet.
- Keep current AI buttons as placeholder/toast unless explicitly asked.
