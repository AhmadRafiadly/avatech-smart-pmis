# Feature Spec - AI Fallback Evidence

## Purpose
AI Fallback Evidence dibuat untuk membuktikan bahwa mekanisme fallback multi-provider benar-benar berjalan dan dapat ditunjukkan pada sidang akhir.

## Problem Answered
Penguji menanyakan apakah fallback Gemini → Groq → OpenRouter dapat dibuktikan secara otomatis.

## Existing System
AI Monitor sudah mencatat metadata:
- provider
- model
- status
- latency
- fallback path
- error message yang sudah disanitasi

Data sensitif tidak boleh disimpan:
- API key
- prompt lengkap
- raw response provider
- secret

## Minimum Feature
Improve AI Monitor or add AI Diagnostics section.

Displayed evidence:
- Provider order: Gemini → Groq → OpenRouter
- Last AI request status
- Provider used
- Fallback path
- Latency
- Success/failed status
- Sanitized error message if failed

## Optional Diagnostic
Add a safe test button only if simple:
- Test Provider Config
- Simulate fallback using mock/error mode
- Show result without exposing secrets

## Rules
- Do not expose API keys.
- Do not store full prompt or raw response.
- Do not claim zero downtime.
- Use wording: fallback menjaga ketersediaan asistensi AI ketika provider utama gagal/error.
- All AI output remains draft-only and must pass Human-in-the-Loop.

## Evidence for Sidang
Demo flow:
1. Open AI Monitor / AI Diagnostics.
2. Show provider order.
3. Show request log with provider used.
4. Show fallback path if provider changed.
5. Explain that metadata is stored without prompt/raw response/API key.