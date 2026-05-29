# Diagram Draw.io (Editable) — Avatech Smart-PMIS

Folder ini berisi versi **editable** (draw.io / diagrams.net) dari diagram skripsi
yang sebelumnya dibuat dalam format Mermaid (`docs/skripsi/diagrams/*.mmd`) dan
dirangkum pada `docs/skripsi/bab-3-diagrams-smart-pmis.md`.

Tujuan: memudahkan penyuntingan tata letak, label, dan styling untuk naskah skripsi
tanpa perlu mengubah sintaks Mermaid. Setiap berkas adalah XML `<mxfile>` yang dapat
dibuka langsung di https://app.diagrams.net atau aplikasi draw.io desktop, dan dapat
disunting sebagai bentuk (shape), konektor, dan kotak teks.

## Cara membuka & ekspor
1. Buka https://app.diagrams.net (atau draw.io desktop).
2. **File → Open** lalu pilih berkas `.drawio` yang diinginkan.
3. Sunting bentuk/garis/teks sesuai kebutuhan.
4. **File → Export as → SVG** (disarankan untuk Word/PDF agar tajam saat di-zoom).
   - Jika Word bermasalah menampilkan SVG, ekspor ulang sebagai **PNG** (skala 2x untuk ketajaman).
5. Sisipkan ke naskah, lalu tambahkan baris sumber: `Sumber: Diolah Peneliti, 2026.`

## Daftar berkas & penempatan di skripsi

| Berkas | Judul Diagram | Bagian BAB III (saran) |
|--------|---------------|------------------------|
| `01-arsitektur-sistem.drawio` | Arsitektur Sistem | 3.5.1 Arsitektur Sistem |
| `02-role-hak-akses.drawio` | Pemetaan Peran ke Navigasi & Hak Akses | 3.5.2 Role dan Hak Akses |
| `03-human-in-the-loop.drawio` | Titik Validasi Human-in-the-Loop | 3.5.3 Human-in-the-Loop |
| `04-use-case.drawio` | Use Case Diagram | 3.5.4 Use Case Diagram |
| `05-activity-sistem-berjalan.drawio` | Activity Diagram Sistem Berjalan | 3.5.5 Activity Diagram |
| `06-activity-sistem-usulan.drawio` | Activity Diagram Sistem Usulan | 3.5.5 Activity Diagram |
| `07-sequence-ai-mom-wbs-testcase.drawio` | Sequence Diagram AI MoM/WBS/Test Case | 3.5.6 Sequence Diagram |
| `08-sequence-draft-client.drawio` | Sequence Diagram Draft Komunikasi Client | 3.5.6 Sequence Diagram |
| `09-class-diagram.drawio` | Class Diagram | 3.5.7 Class Diagram |
| `10-erd.drawio` | Entity Relationship Diagram (ERD inti) | 3.5.8 ERD |
| `11-fallback-llm.drawio` | Alur Integrasi API LLM Multi-Provider | 3.5.10 Integrasi API LLM |
| `12-monitoring-auditabilitas.drawio` | Monitoring, Auditabilitas, & Kesiapan Sistem | 3.5.12 Monitoring & Kesiapan |

## Format & konvensi
- Format ekspor disarankan: **SVG** untuk kualitas Word/PDF; gunakan **PNG (2x)** bila Word bermasalah dengan SVG.
- Baris sumber untuk semua diagram perancangan: `Sumber: Diolah Peneliti, 2026.`
- Caption gambar diletakkan **di bawah** gambar (sesuaikan pedoman kampus).
- Penomoran "Gambar 3.x" pada judul di kanvas bersifat sementara — selaraskan dengan daftar isi final.

## Catatan keselarasan dengan implementasi
- Nama tabel penugasan adalah **`team_assignments`** (bukan `project_assignments`).
- Urutan fallback provider AI: **Gemini → Groq → OpenRouter**.
- **AI Monitor** = pencatatan metadata aman ke `ai_request_logs` (bukan isi prompt/respons).
- **Smart Insights / Reminders** bersifat **berbasis aturan** dari data sistem (bukan prediksi AI).
- **Draft WhatsApp/Email Client** = tanpa auto-send; pengguna menyalin/mengedit dan mengirim manual.
- Seluruh keluaran AI berstatus **draf** dan wajib divalidasi pengguna (Human-in-the-Loop).
- ERD/Class diagram menampilkan **atribut kunci** saja; tabel pendukung/preferensi dikecualikan dari versi inti agar tetap terbaca.
