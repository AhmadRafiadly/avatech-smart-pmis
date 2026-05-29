# Draf Artefak Pengujian BAB IV — Avatech Smart-PMIS

Dokumen ini berisi rancangan pengujian ringkas berdasarkan fitur yang
**benar-benar terimplementasi**. Tidak ada fitur yang diuji di luar yang tersedia
pada build feature-freeze. Keluaran AI selalu diperlakukan sebagai draf yang
memerlukan validasi manusia (Human-in-the-Loop).

---

## 1. Rencana Black-Box Testing

Pengujian fungsional berdasarkan masukan/keluaran tanpa melihat kode internal.

| ID | Fitur | Skenario Uji | Input | Hasil yang Diharapkan | Status |
|----|-------|--------------|-------|------------------------|--------|
| BB-01 | Login | Login dengan kredensial valid | Email + password benar | Diarahkan ke dashboard sesuai peran | — |
| BB-02 | Login | Login dengan kredensial salah | Email/password salah | Pesan error, tetap di halaman login | — |
| BB-03 | Hak Akses Peran | Operasional membuka halaman CEO/PM | Akses `/executive` sebagai sa_qa | Redirect ke `/dashboard` | — |
| BB-04 | Hak Akses Proyek | Operasional membuka proyek tak ditugaskan | Akses `/projects/{id}` | Redirect ke `/projects` + pesan | — |
| BB-05 | Project Master | Tambah/ubah/arsip proyek | Form proyek valid | Data tersimpan + tercatat di Audit Trail | — |
| BB-06 | Client Directory | Tambah/ubah klien | Form klien valid | Data tersimpan | — |
| BB-07 | Team Assignment | Menugaskan anggota ke proyek | Form assignment | Penugasan tersimpan (PENUGASAN BARU di audit) | — |
| BB-08 | MoM | Menambah MoM | Tanggal + catatan | MoM tersimpan status draft | — |
| BB-09 | AI MoM Fixer | Generate ringkasan MoM | MoM ada + AI terkonfigurasi | Draf ringkasan tampil untuk ditinjau | — |
| BB-10 | AI WBS Generator | Generate WBS dari MoM | MoM ada + AI terkonfigurasi | Draf modul/task tampil, disimpan setelah disetujui | — |
| BB-11 | Gate AI | AI belum dikonfigurasi | Tanpa provider key | Tampil "AI belum dikonfigurasi", tidak crash | — |
| BB-12 | WBS/Task | Tambah/ubah/hapus task | Form task | Data tersimpan, status dapat diubah | — |
| BB-13 | Kanban | Ubah status task | Pindah status | Status diperbarui | — |
| BB-14 | QC/Test Case | Tambah test case + Expected Result | Form QC | Tersimpan, status pending | — |
| BB-15 | QC Status | Ubah status (Lulus/Gagal/Retest) | Aksi status | Status & tested_at diperbarui | — |
| BB-16 | AI Test Case | Generate test case | Modul ada + AI terkonfigurasi | Draf test case tampil untuk divalidasi | — |
| BB-17 | Export PDF | Export WBS & Test Case | Klik export | File PDF terunduh, tercatat di audit | — |
| BB-18 | Audit Trail | Filter & export CSV/Laporan | Pilih filter | Data sesuai, CSV/PDF laporan terunduh | — |
| BB-19 | AI Monitor | Lihat metadata AI | Buka `/ai-monitor` | Metadata tampil tanpa isi prompt | — |
| BB-20 | System Health | Lihat status kesiapan | Buka `/system-health` | Status DB/cache/storage/PDF/AI tampil | — |
| BB-21 | Draft Client | Generate draf WhatsApp/Email | Klik draf | Teks draf tampil, tidak terkirim otomatis | — |
| BB-22 | Logout | Keluar sesi | Klik logout | Sesi berakhir, back-button tidak menampilkan halaman terproteksi | — |

> Kolom "Status" diisi saat eksekusi pengujian (Lulus/Gagal/Catatan).

---

## 2. Rencana User Acceptance Testing (UAT)

Pengujian penerimaan oleh perwakilan pengguna sesuai peran.

| ID | Peran Penguji | Aktivitas | Kriteria Diterima |
|----|---------------|-----------|-------------------|
| UAT-01 | CEO/PM | Memantau portofolio via Executive Monitor | Informasi proyek/klien jelas & relevan |
| UAT-02 | CEO/PM | Mengelola klien & meninjau draf komunikasi AI | Draf membantu, mudah diedit, tidak terkirim otomatis |
| UAT-03 | SA/QA | Membuat MoM → AI WBS → meninjau hasil | Alur logis, draf AI dapat disunting sebelum simpan |
| UAT-04 | SA/QA | Mengelola QC/Test Case + AI Test Case | Test case relevan, validasi mudah |
| UAT-05 | Fullstack Dev | Mengelola task & Kanban | Status task mudah diperbarui |
| UAT-06 | UI/UX Designer | Mengakses task sesuai assignment | Hanya melihat lingkup yang relevan |
| UAT-07 | Semua peran | Navigasi & kejelasan antarmuka | Menu sesuai peran, istilah konsisten |

Skala penilaian disarankan: Likert 1–5 (Sangat Tidak Setuju → Sangat Setuju)
untuk pernyataan kegunaan, kemudahan, dan kejelasan.

---

## 3. Rencana Validasi Output LLM (HITL)

Pengujian kualitas keluaran AI yang menekankan peran validasi manusia.

| ID | Fitur AI | Aspek Dinilai | Metode | Kriteria |
|----|----------|---------------|--------|----------|
| LLM-01 | AI WBS Generator | Relevansi modul/task terhadap MoM | Tinjauan ahli (SA/QA) | Mayoritas item relevan & dapat dipakai setelah edit |
| LLM-02 | AI WBS Generator | Kepatuhan format (jumlah modul/task, status valid) | Cek otomatis parser + manual | Sesuai batas (maks modul/task) & enum valid |
| LLM-03 | AI Test Case Generator | Cakupan skenario uji | Tinjauan ahli | Skenario logis, Expected Result jelas |
| LLM-04 | AI MoM Fixer | Akurasi & keringkasan ringkasan | Tinjauan ahli | Ringkasan setia pada catatan asli |
| LLM-05 | Draft Client | Kesopanan & kejelasan bahasa | Tinjauan CEO/PM | Layak kirim setelah penyuntingan |
| LLM-06 | Semua fitur AI | Penanganan kegagalan provider | Uji fallback/gagal total | Pesan ramah, status gagal tercatat (metadata aman) |
| LLM-07 | Semua fitur AI | Prinsip HITL | Observasi alur | Tidak ada keluaran AI yang final tanpa persetujuan |

Metrik pendukung (dari `ai_request_logs`): jumlah permintaan, tingkat
keberhasilan, latensi rata-rata, dan kejadian fallback per provider.

---

## 4. TestSprite sebagai Dukungan Pengujian Eksternal

TestSprite digunakan sebagai alat bantu pengujian otomatis/eksternal untuk
melengkapi pengujian manual.

| Aspek | Keterangan |
|-------|------------|
| Peran | Dukungan pengujian otomatis (mis. alur UI/fungsional end-to-end) |
| Cakupan saran | Alur login, navigasi peran, alur Project Detail, alur AI (HITL), export PDF |
| Output | Laporan hasil uji yang dapat dilampirkan sebagai bukti pengujian |
| Posisi dalam skripsi | Pelengkap Black-Box & UAT, bukan pengganti validasi manusia |
| Catatan | Sesuaikan langkah dengan kapabilitas TestSprite yang Anda gunakan; cantumkan ringkasan hasil pada BAB IV |

> Catatan: detail teknis TestSprite (konfigurasi, jumlah test, hasil) **perlu
> diisi sesuai pelaksanaan nyata** Anda. Dokumen ini hanya menyediakan kerangka.

---

## Catatan Validasi Pengujian
- Tabel di atas adalah **draf rencana**; isi kolom status/hasil saat eksekusi.
- Pengujian fitur AI (BB-09, BB-10, BB-16, LLM-*) memerlukan provider AI yang
  terkonfigurasi. Bila tidak, dokumentasikan skenario gate (BB-11) sebagai bukti
  penanganan ketiadaan konfigurasi.
- Tidak ada fitur dark theme atau multi-bahasa yang diuji karena tidak menjadi
  bagian sistem.
