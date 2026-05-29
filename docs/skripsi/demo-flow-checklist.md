# Checklist Alur Demo Sidang — Avatech Smart-PMIS

Alur demonstrasi ringkas untuk sidang skripsi. Tujuannya menunjukkan nilai inti
sistem: manajemen proyek terpusat, asistensi AI dengan prinsip Human-in-the-Loop,
serta auditabilitas dan kesiapan sistem. Setiap langkah dilengkapi poin
pembicaraan (talking points).

**Persiapan sebelum demo:**
- Pastikan server berjalan dan data seed/demo tersedia.
- Login menggunakan akun demo (mis. CEO/PM `joshua.raphael@avatech.test`,
  operasional `ahmad.rafiadly@avatech.test`).
- Bila ingin mendemokan fitur AI secara live, pastikan minimal satu provider
  (Gemini/Groq/OpenRouter) terkonfigurasi; bila tidak, tunjukkan gate
  "AI belum dikonfigurasi" sebagai perilaku aman.
- Jangan menampilkan API key/.env di layar.

---

| # | Langkah | Aksi | Poin Pembicaraan |
|---|---------|------|------------------|
| 1 | Login sebagai CEO/PM | Buka `/login`, masuk sebagai CEO/PM | "Login normal terpisah dari panel admin; akses diatur per peran." |
| 2 | Executive Monitor | Tampilkan `/executive` | "Ringkasan eksekutif lintas proyek & klien untuk pengambilan keputusan cepat." |
| 3 | Project Master | Buka `/projects` | "Seluruh proyek dikelola terpusat: status, fase, progres, tim." |
| 4 | Client Directory + AI Draft | Buka `/clients`, buka modal draf WhatsApp/Email | "AI membantu menyusun draf komunikasi; tidak ada pengiriman otomatis — keputusan tetap di pengguna." |
| 5 | AI Monitor & System Health | Buka `/ai-monitor` lalu `/system-health` | "Transparansi pemakaian AI (metadata aman) dan pengecekan kesiapan sistem untuk produksi." |
| 6 | Beralih ke peran operasional | Logout, login sebagai SA/QA | "Peran operasional hanya melihat dasbor & proyek yang ditugaskan." |
| 7 | Project Detail | Buka salah satu proyek yang ditugaskan | "Satu halaman kerja: Overview, AI Planning, Workspace, QC." |
| 8 | MoM | Tambah/tampilkan MoM | "Notulen rapat tercatat terpusat sebagai dasar perencanaan." |
| 9 | AI MoM → AI WBS → Kanban | Jalankan AI MoM Fixer, lalu AI WBS Generator, tinjau & simpan, lihat Kanban | "AI menghasilkan draf; pengguna meninjau/menyunting sebelum disimpan (Human-in-the-Loop). WBS jadi task di Kanban." |
| 10 | AI Test Case → QC | Jalankan AI Test Case Generator, validasi, kelola status QC | "AI mempercepat penyusunan test case; validasi & status (Lulus/Gagal/Retest) tetap manual." |
| 11 | Export PDF | Export WBS dan Test Case ke PDF | "Dokumentasi siap dibagikan; aktivitas export ikut tercatat." |
| 12 | Audit Trail | Buka `/audit` (sebagai CEO/PM) | "Semua aktivitas penting terekam untuk akuntabilitas; baris dapat ditelusuri ke proyek terkait." |

---

## Pesan Penutup Demo (saran)
- **Terpusat:** seluruh siklus proyek (MoM → WBS → task → QC → dokumentasi) dalam satu sistem.
- **Berbantuan AI, bukan otomatis penuh:** setiap keluaran AI adalah draf yang divalidasi manusia.
- **Aman & akuntabel:** akses berbasis peran, API key tidak ditampilkan, metadata AI dan jejak audit tercatat, serta kesiapan sistem dapat diperiksa.
- **Tahan gangguan:** fallback LLM multi-provider menjaga ketersediaan fitur AI.

## Rencana Cadangan (jika AI tidak tersedia saat demo)
- Tunjukkan gate "AI belum dikonfigurasi" sebagai bukti penanganan aman.
- Gunakan data hasil generate yang sudah tersimpan sebelumnya untuk menjelaskan alur.
- Tekankan bahwa arsitektur AI bersifat opsional/terpisah sehingga fungsi inti PMIS tetap berjalan.
