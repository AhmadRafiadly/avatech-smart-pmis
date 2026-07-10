# Feature Spec - Testing Evidence

## Purpose
Testing Evidence dibuat untuk menjawab feedback penguji bahwa hasil pengujian harus bisa dibuktikan valid dan lulus di dalam sistem.

## Problem Answered
- Penguji dapat melihat bukti bahwa sistem sudah diuji.
- Hasil Black-Box, UAT, Validasi LLM, dan TestSprite tidak hanya muncul di dokumen skripsi, tetapi juga bisa ditampilkan sebagai evidence sistem.

## Minimum Feature
Add a Testing Evidence page or support section.

Evidence categories:
1. Black-Box Testing
2. UAT Terbatas
3. Validasi Keluaran LLM
4. TestSprite

Fields:
- category
- title
- total_scenarios
- passed_scenarios
- failed_scenarios
- result_status
- tested_at
- notes
- evidence_file_path nullable
- evidence_url nullable

## Demo Data
Seed these final results:
- Black-Box Testing: 12/12 lulus
- UAT Terbatas: 5/5 diterima
- Validasi Keluaran LLM: 6/6 valid
- TestSprite: 15/15 lulus

## Rules
- This feature is for evidence and thesis defense support.
- Do not replace the actual testing documents.
- Allow screenshot/PDF upload or external evidence URL.
- Keep the page simple and readable.
- Avoid large testing framework changes in this feature.

## Evidence for Sidang
Demo flow:
1. Open Testing Evidence page.
2. Show Black-Box 12/12.
3. Show UAT 5/5.
4. Show Validasi LLM 6/6.
5. Show TestSprite 15/15.
6. Open uploaded screenshot/PDF/link if needed.