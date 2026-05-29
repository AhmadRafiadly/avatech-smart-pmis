# Daftar Caption dan Sumber Gambar/Tabel BAB III — Avatech Smart-PMIS

Dokumen ini menyediakan daftar caption (judul gambar/tabel) beserta baris sumber
yang siap disalin ke naskah BAB III. Penomoran "3.x" bersifat sementara —
selaraskan dengan daftar isi BAB III final Anda.

Konvensi baris sumber yang digunakan:

- **Diagram perancangan sistem** → `Sumber: Diolah Peneliti, 2026.`
- **Screenshot implementasi** (dipakai di BAB IV) → `Sumber: Hasil Implementasi Sistem, 2026.`
- **Tabel berbasis observasi perusahaan** → `Sumber: Hasil Observasi Lapangan, 2026.`
- **Tabel kebutuhan/perancangan** → `Sumber: Hasil Observasi Lapangan dan Perancangan Sistem, 2026.`

---

## A. Gambar (Diagram Perancangan) — BAB III

| No Gambar (saran) | Judul Gambar | Berkas Sumber Diagram | Baris Sumber | Penempatan |
|---|---|---|---|---|
| Gambar 3.1 | Arsitektur Sistem Avatech Smart-PMIS | `diagrams/01-arsitektur-sistem.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.1 Arsitektur Sistem |
| Gambar 3.2 | Pemetaan Peran ke Kelompok Navigasi dan Hak Akses | `diagrams/02-role-hak-akses.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.2 Role dan Hak Akses |
| Gambar 3.3 | Titik Validasi Human-in-the-Loop pada Keluaran AI | `diagrams/03-human-in-the-loop.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.3 Human-in-the-Loop |
| Gambar 3.4 | Use Case Diagram Avatech Smart-PMIS | `diagrams/04-use-case.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.4 Use Case Diagram |
| Gambar 3.5 | Activity Diagram Sistem Berjalan | `diagrams/05-activity-sistem-berjalan.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.5 Activity Diagram |
| Gambar 3.6 | Activity Diagram Sistem Usulan Avatech Smart-PMIS | `diagrams/06-activity-sistem-usulan.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.5 Activity Diagram |
| Gambar 3.7 | Sequence Diagram Alur Asistensi AI MoM, WBS, dan Test Case | `diagrams/07-sequence-ai-mom-wbs-testcase.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.6 Sequence Diagram |
| Gambar 3.8 | Sequence Diagram Draft Komunikasi Client Berbantuan AI | `diagrams/08-sequence-draft-client.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.6 Sequence Diagram |
| Gambar 3.9 | Class Diagram Avatech Smart-PMIS | `diagrams/09-class-diagram.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.7 Class Diagram |
| Gambar 3.10 | Entity Relationship Diagram Avatech Smart-PMIS | `diagrams/10-erd.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.8 ERD |
| Gambar 3.11 | Alur Integrasi API LLM Multi-Provider | `diagrams/11-fallback-llm.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.10 Integrasi API LLM |
| Gambar 3.12 | Perancangan Monitoring, Auditabilitas, dan Kesiapan Sistem | `diagrams/12-monitoring-auditabilitas.mmd` | Sumber: Diolah Peneliti, 2026. | 3.5.12 Monitoring & Kesiapan |

**Format penulisan caption di Word (contoh):**

> Gambar 3.1 Arsitektur Sistem Avatech Smart-PMIS
> Sumber: Diolah Peneliti, 2026.

---

## B. Tabel — BAB III

| No Tabel (saran) | Judul Tabel | Isi | Baris Sumber | Penempatan |
|---|---|---|---|---|
| Tabel 3.1 | Kebutuhan Fungsional Sistem | Daftar fitur fungsional (auth, project, client, team, MoM, WBS, QC, AI, PDF, audit) | Sumber: Hasil Observasi Lapangan dan Perancangan Sistem, 2026. | 3.x Analisis Kebutuhan |
| Tabel 3.2 | Kebutuhan Non-Fungsional | Keamanan (RBAC, redaksi API key), ketahanan (fallback LLM), auditabilitas, kinerja | Sumber: Hasil Observasi Lapangan dan Perancangan Sistem, 2026. | 3.x Analisis Kebutuhan |
| Tabel 3.3 | Pemetaan Peran dan Hak Akses | ceo_pm/admin-tier vs sa_qa/fullstack_dev/uiux_designer + akses tiap peran | Sumber: Hasil Observasi Lapangan dan Perancangan Sistem, 2026. | 3.5.2 Role dan Hak Akses |
| Tabel 3.4 | Daftar Aktor dan Use Case | Aktor + use case yang dapat diakses | Sumber: Diolah Peneliti, 2026. | 3.5.4 Use Case Diagram |
| Tabel 3.5 | Struktur Tabel Basis Data | Daftar tabel + kolom kunci (rujuk ERD/migrasi) | Sumber: Diolah Peneliti, 2026. | 3.5.8 ERD |
| Tabel 3.6 | Daftar Fitur AI dan Status HITL | Fitur AI (WBS, Test Case, MoM Fixer, Draft Client) + titik validasi manusia | Sumber: Diolah Peneliti, 2026. | 3.5.3 / 3.5.10 |
| Tabel 3.7 | Proses Bisnis Sistem Berjalan | Tahapan kerja eksisting + kendala | Sumber: Hasil Observasi Lapangan, 2026. | 3.x Sistem Berjalan |

> Catatan: judul dan jumlah tabel di atas adalah **saran** berdasarkan fitur yang
> benar-benar terimplementasi. Sesuaikan dengan struktur BAB III Anda. Tabel 3.7
> berbasis observasi lapangan (proses organisasi), sedangkan Tabel 3.1–3.6
> berbasis perancangan sistem/data nyata.

---

## C. Catatan Penempatan Sumber

- Letakkan caption **gambar di bawah** gambar, dan caption **tabel di atas**
  tabel (mengikuti pedoman umum penulisan ilmiah; sesuaikan dengan pedoman
  kampus Anda).
- Untuk diagram BAB III gunakan `Sumber: Diolah Peneliti, 2026.` karena diagram
  disusun sendiri oleh peneliti dari hasil perancangan.
- Untuk screenshot di BAB IV, gunakan `Sumber: Hasil Implementasi Sistem, 2026.`
  (lihat `bab-4-screenshot-checklist.md`).
