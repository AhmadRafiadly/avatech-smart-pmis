# Feature Spec - Formula Reference

## Purpose
Formula Reference dibuat untuk menjawab feedback penguji tentang dasar perhitungan angka/card/dashboard di sistem.

## Problem Answered
Setiap angka yang muncul pada dashboard, executive monitor, project detail, team load, QC, dan AI monitor harus memiliki rumus yang jelas.

## Minimum Feature
Add a Formula Reference page or support section.

Formula list:

1. Project Progress
Progress = (Total estimasi jam task selesai / Total estimasi jam semua task) × 100%
Fallback:
Progress = (Jumlah task selesai / Jumlah seluruh task) × 100%

2. QC Pass Rate
QC Pass Rate = (Jumlah test case passed / Total test case) × 100%

3. Team Load
Team Load = (Total estimasi jam task aktif user / Kapasitas jam user) × 100%

4. Overdue Task
Overdue task = task yang belum done dan due date < tanggal hari ini

5. AI Success Rate
AI Success Rate = (Jumlah request AI sukses / Total request AI) × 100%

6. Fallback Count
Fallback Count = jumlah request AI yang berpindah dari provider utama ke provider berikutnya

7. AI Latency
AI Latency = waktu selesai request - waktu mulai request

8. System Health
System Health dihitung dari status konfigurasi environment, koneksi database, storage link, cache, dan konfigurasi AI provider.

## Rules
- Formula must be descriptive and easy to understand.
- Do not overclaim predictive analytics.
- Smart Insights remain rule-based, not AI prediction.
- Formula page is mainly for transparency and thesis defense evidence.

## Evidence for Sidang
Demo flow:
1. Open Formula Reference page.
2. Explain project progress formula.
3. Explain QC pass rate.
4. Explain team load.
5. Explain AI success/fallback metric.
6. Show that dashboard numbers are not arbitrary.