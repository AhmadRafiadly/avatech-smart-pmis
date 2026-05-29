# Checklist Screenshot BAB IV (Implementasi) — Avatech Smart-PMIS

Daftar tangkapan layar yang perlu diambil untuk bab implementasi. Untuk setiap
screenshot, gunakan baris sumber:

> Sumber: Hasil Implementasi Sistem, 2026.

**Pedoman umum penyamaran data sensitif (data masking):**
- Sembunyikan/blur email, nomor telepon, dan nama PIC client asli bila memakai
  data nyata; gunakan data dummy/seeder bila memungkinkan.
- Jangan pernah menampilkan API key, token, atau isi `.env` pada screenshot apa pun.
- Pada Settings/Sekretaris AI, pastikan hanya status provider yang tampil
  (mis. "Belum dikonfigurasi"), bukan key.
- Pada AI Monitor, tampilkan metadata (provider, model, status, latensi); pastikan
  tidak ada potongan prompt/respons sensitif.
- Login sebagai akun demo (mis. `joshua.raphael@avatech.test`,
  `ahmad.rafiadly@avatech.test`), bukan akun pribadi.

---

| No | Screenshot | Fitur Terkait | Tujuan | Saran Caption | Catatan Masking |
|----|-----------|---------------|--------|---------------|-----------------|
| 1 | Halaman Login | Autentikasi (`/login`) | Menunjukkan gerbang masuk normal Smart-PMIS (terpisah dari `/admin`) | Gambar 4.x Halaman Login Avatech Smart-PMIS | Jangan tampilkan password yang diketik |
| 2 | Dashboard Operasional | `/dashboard` (sa_qa/dev/uiux) | Menunjukkan dasbor ringkas berbasis penugasan untuk peran operasional | Gambar 4.x Dashboard Operasional | Nama anggota boleh tampil bila demo data |
| 3 | Executive Monitor | `/executive` (ceo_pm) | Menunjukkan ringkasan eksekutif lintas proyek/klien | Gambar 4.x Executive Monitor CEO/PM | — |
| 4 | Project Master | `/projects` (CEO/PM) | Menunjukkan daftar & manajemen seluruh proyek | Gambar 4.x Halaman Project Master | — |
| 5 | Client Directory | `/clients` | Menunjukkan direktori klien + tier & kesehatan relasi | Gambar 4.x Client Directory | Blur email/telepon klien asli |
| 6 | Modal AI Draft Client | Draft WhatsApp/Email (`clients.draft.*`) | Menunjukkan draf komunikasi berbantuan AI (tanpa auto-send) | Gambar 4.x Draf Komunikasi Client Berbantuan AI | Pastikan tidak ada nomor/email asli |
| 7 | Project Detail — Overview | `/projects/{id}` tab Overview | Menunjukkan ringkasan proyek + tim + progres | Gambar 4.x Project Detail (Overview) | — |
| 8 | MoM + AI MoM Fixer | Tab AI Planning (`projects.moms.*`, `ai-mom.fix`) | Menunjukkan pencatatan MoM dan draf ringkasan AI (HITL) | Gambar 4.x Manajemen MoM dan AI MoM Fixer | Tandai keluaran AI sebagai "Draft" |
| 9 | WBS + AI WBS Generator | Tab AI Planning (`ai-wbs.generate`) | Menunjukkan pembuatan draf modul/task dari MoM dengan validasi pengguna | Gambar 4.x AI WBS Generator (Draft, HITL) | Tandai status Draft |
| 10 | Kanban Workspace | Tab Workspace (`projects.tasks.*`) | Menunjukkan papan task & pembaruan status | Gambar 4.x Kanban Workspace | — |
| 11 | QC / Test Case + AI Test Case Generator | Tab QC (`projects.qc.*`, `ai-test-cases.generate`) | Menunjukkan manajemen test case + draf AI + Expected/Actual Result | Gambar 4.x QC/Test Case dan AI Test Case Generator | Tandai keluaran AI sebagai Draft |
| 12 | Hasil Export PDF | `projects.export.wbs` / `export.test-cases` | Menunjukkan keluaran PDF WBS dan Test Case | Gambar 4.x Hasil Export PDF WBS dan Test Case | — |
| 13 | Audit Trail | `/audit` (CEO/PM) | Menunjukkan jejak aktivitas pengguna/sistem | Gambar 4.x Audit Trail | Nama aktor boleh tampil bila demo data |
| 14 | AI Monitor | `/ai-monitor` | Menunjukkan metadata pemrosesan AI (provider, model, status, latensi, fallback) | Gambar 4.x AI Monitor | Tidak menampilkan isi prompt/respons |
| 15 | Settings | `/settings` | Menunjukkan preferensi, status Sekretaris AI, integrasi | Gambar 4.x Halaman Settings | Jangan tampilkan API key |
| 16 | System Health | `/system-health` | Menunjukkan pengecekan kesiapan (DB, cache, storage, PDF, AI, environment) | Gambar 4.x System Health | Sembunyikan detail environment sensitif bila ada |

---

## Saran Tambahan
- Ambil screenshot pada resolusi desktop yang konsisten (mis. lebar 1280–1440 px)
  agar seragam di Word/PDF.
- Untuk alur AI (No. 8, 9, 11), pertimbangkan dua tangkapan: (a) sebelum generate,
  (b) hasil draf yang masih bisa diedit — untuk menegaskan prinsip Human-in-the-Loop.
- Activity Log (peran operasional) dapat ditambahkan sebagai varian dari No. 13
  untuk menunjukkan pembatasan lingkup (hanya aktivitas milik pengguna).
