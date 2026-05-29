# Diagram Perancangan Avatech Smart-PMIS untuk BAB III

Dokumen ini berisi draf diagram siap-skripsi untuk **BAB III** sistem
**Avatech Smart-PMIS** (Project Management Information System berbasis web milik
PT Ava Teknologi Nusantara). Seluruh diagram disusun berdasarkan struktur
implementasi nyata pada repositori (routes, controller, model, dan migrasi),
bukan asumsi. Diagram ditulis dengan sintaks **Mermaid** sehingga dapat dirender
ulang dan diekspor menjadi gambar.

Prinsip yang dipegang di seluruh dokumen:

- Keluaran AI bersifat **draf/rekomendasi** dan **wajib divalidasi pengguna**
  (*Human-in-the-Loop / HITL*). Tidak ada keputusan yang dieksekusi otomatis
  oleh AI.
- Draft komunikasi client (WhatsApp/Email) **tidak dikirim otomatis**; sistem
  hanya menyiapkan teks untuk disalin/diedit pengguna.
- AI Monitor hanya menyimpan **metadata aman** (provider, model, status, token,
  latensi, fallback path), bukan isi prompt/respons penuh, dan API key tidak
  pernah ditampilkan.

---

## Ringkasan Diagram

| No | Nama Diagram | Tujuan | Bagian BAB III |
|----|--------------|--------|----------------|
| 1 | Arsitektur Sistem | Memetakan lapisan pengguna, web, aplikasi Laravel, basis data, dan layanan pendukung (AI, PDF, audit, monitor) | 3.5.1 |
| 2 | Use Case Diagram | Menggambarkan aktor (peran) dan fungsi yang dapat diakses | 3.5.4 |
| 3 | Activity Diagram — Sistem Berjalan | Alur kerja manual/eksisting sebelum sistem usulan | 3.5.5 |
| 4 | Activity Diagram — Sistem Usulan | Alur kerja Smart-PMIS dengan asistensi AI + HITL | 3.5.5 |
| 5 | Sequence Diagram — AI MoM → WBS → Test Case | Interaksi komponen pada alur asistensi AI inti | 3.5.6 |
| 6 | Sequence Diagram — Draft Komunikasi Client | Interaksi pembuatan draf WhatsApp/Email tanpa auto-send | 3.5.6 |
| 7 | Class Diagram | Struktur kelas/model dan relasinya | 3.5.7 |
| 8 | Entity Relationship Diagram (ERD) | Struktur tabel basis data dan relasinya | 3.5.8 |
| 9 | Alur Integrasi API LLM Multi-Provider | Mekanisme fallback Gemini → Groq → OpenRouter | 3.5.10 |
| 10 | Monitoring, Auditabilitas, dan Kesiapan Sistem | Komponen pendukung produksi: audit, monitor AI, system health, smart insights | 3.5.12 |
| (—) | Role dan Hak Akses | Pemetaan peran ke kelompok navigasi & gerbang akses | 3.5.2 |
| (—) | Human-in-the-Loop | Titik validasi manusia pada keluaran AI | 3.5.3 |

> Catatan: subbab 3.5.2 (Role & Hak Akses) dan 3.5.3 (HITL) tidak memerlukan
> diagram terpisah yang baru — keduanya terwakili oleh Use Case (No. 2),
> Activity Diagram Usulan (No. 4), dan Sequence Diagram AI (No. 5). Tabel
> pemetaan peran disertakan pada bagian 3.5.2 di bawah.

---

## 1. Arsitektur Sistem

**Gambar 3.x Arsitektur Sistem Avatech Smart-PMIS**

Avatech Smart-PMIS dirancang dengan pola arsitektur berlapis berbasis kerangka
kerja Laravel. Lapisan pengguna terdiri atas peran CEO/Project Manager dan peran
operasional (System Analyst/QA, Fullstack Developer, UI/UX Designer) yang
mengakses sistem melalui antarmuka web. Permintaan diteruskan ke lapisan aplikasi
Laravel (routing, middleware otorisasi peran, controller, dan service) yang
berinteraksi dengan lapisan basis data relasional. Layanan pendukung mencakup
lapisan AI multi-provider, ekspor PDF, pencatatan jejak audit, pemantauan
metadata AI, pengecekan kesiapan sistem, dan mesin rekomendasi berbasis aturan.

```mermaid
flowchart TD
    subgraph U["Lapisan Pengguna (Peran)"]
        A1["CEO / Project Manager"]
        A2["System Analyst / QA"]
        A3["Fullstack Developer"]
        A4["UI/UX Designer"]
    end

    subgraph W["Antarmuka Web (Blade + Tailwind)"]
        W1["Halaman Eksekutif & Operasional"]
    end

    subgraph L["Lapisan Aplikasi Laravel"]
        R["Routing + Middleware Auth & Peran (ceo.pm)"]
        C["Controllers"]
        S["Services: AiPlanner, AuditLogger, SmartInsightService"]
    end

    subgraph D["Lapisan Basis Data (MySQL)"]
        DB["Tabel: users, clients, projects, team_assignments,\nproject_modules, project_tasks, project_moms,\nproject_qc_tests, audit_logs, ai_request_logs"]
    end

    subgraph EXT["Layanan Pendukung"]
        AI["AI Service Layer"]
        PDF["PDF Export (dompdf)"]
        AUD["Audit Trail"]
        MON["AI Monitor"]
        HEALTH["System Health"]
        INS["Smart Insights / Reminders"]
    end

    subgraph LLM["Provider LLM (Eksternal)"]
        G["Gemini"]
        GR["Groq"]
        OR["OpenRouter"]
    end

    U --> W --> R --> C --> S
    C --> DB
    S --> DB
    S --> AI
    AI --> G
    AI -. fallback .-> GR
    AI -. fallback .-> OR
    C --> PDF
    S --> AUD --> DB
    AI --> MON --> DB
    C --> HEALTH
    S --> INS
    INS --> DB
```

**Dasar penyusunan:**
`routes/web.php` (grup `auth` + middleware `ceo.pm`), `bootstrap/app.php`
(alias `ceo.pm` → `EnsureCeoPmAccess`, `PreventBackHistory`), controller
`ExecutiveController`, `DashboardController`, `ProjectController`,
`ClientController`, `TeamController`, `AuditController`, `AiMonitorController`,
`SystemHealthController`, `SettingsController`; service `App\Services\AiPlanner`,
`AuditLogger`, `SmartInsightService`; `config/ai.php` (urutan provider);
migrasi pada `database/migrations`.

**Keterangan untuk skripsi:**
Pada subbab Arsitektur Sistem, jelaskan bahwa sistem menerapkan pola berlapis
(presentation–application–data) khas Laravel. Tekankan bahwa otorisasi peran
diterapkan di lapisan middleware sehingga kontrol akses bersifat terpusat, dan
bahwa layanan AI diposisikan sebagai *layer* terpisah yang dipanggil controller
melalui service, bukan ditanam langsung di antarmuka. Sebutkan bahwa seluruh
keluaran AI dicatat metadata-nya untuk auditabilitas.

---

## 3.5.2 Role dan Hak Akses

**Tabel 3.x Pemetaan Peran ke Kelompok Navigasi dan Hak Akses**

Sistem menggunakan kontrol akses berbasis peran (RBAC) melalui paket Spatie
Permission. Peran dikelompokkan menjadi kelompok *executive* (CEO/PM dan
tier admin) dan kelompok *operational*.

| Peran (role) | Kelompok | Akses Utama |
|--------------|----------|-------------|
| `ceo_pm` | executive (ceo) | Executive Monitor, Project Master, Client Directory, Team Management, AI Monitor, Audit Trail, Settings |
| `admin`, `super_admin`, `developer` | executive (ceo) | Sama seperti CEO/PM + panel admin Filament (`/admin`) bila peran mengizinkan |
| `sa_qa` | operational | Dashboard, Projects (ter-assign), MoM, AI MoM Fixer, AI WBS Generator, QC/Test Case, AI Test Case Generator, Activity Log |
| `fullstack_dev` | operational | Dashboard, Projects (ter-assign), WBS/Task, Kanban Workspace, update status task |
| `uiux_designer` | operational | Dashboard, Projects (ter-assign), Workspace task sesuai assignment |

```mermaid
flowchart LR
    R1["ceo_pm / admin / super_admin / developer"] --> G1["Grup CEO\n(Executive)"]
    R2["sa_qa / fullstack_dev / uiux_designer"] --> G2["Grup Operational"]
    G1 --> M1["Middleware ceo.pm: lolos"]
    G2 --> M2["Middleware ceo.pm: redirect ke /dashboard"]
    M1 --> P1["/executive, /clients, /team, /ai-monitor,\n/settings, /audit"]
    M2 --> P2["/dashboard, /projects (hanya yang ditugaskan)"]
```

**Dasar penyusunan:**
`config/navigation.php` (`role_to_group`, definisi grup `ceo` dan `operational`),
`app/Http/Middleware/EnsureCeoPmAccess.php` (`ALLOWED_ROLES`),
`ProjectController::ensureCanEditProjectDetail()` dan
`ensureOperationalCanAccessProject()`, `AuditController::buildQuery()` (operasional
hanya melihat log miliknya sendiri).

**Keterangan untuk skripsi:**
Jelaskan bahwa hak akses ditegakkan di tiga lapisan: navigasi (apa yang terlihat),
middleware (apa yang boleh dibuka), dan controller (apa yang boleh diubah). Peran
CEO/PM bersifat *read-only* pada halaman Project Detail, sedangkan peran
operasional hanya dapat mengakses proyek yang ditugaskan kepadanya.

---

## 3.5.3 Human-in-the-Loop (HITL)

**Gambar 3.x Titik Validasi Human-in-the-Loop pada Keluaran AI**

Seluruh fitur AI pada Smart-PMIS menerapkan prinsip *Human-in-the-Loop*: AI hanya
menghasilkan draf, dan pengguna harus meninjau, menyunting, lalu menyetujui
sebelum data disimpan atau digunakan.

```mermaid
flowchart LR
    IN["Input pengguna\n(MoM / konteks proyek / konteks client)"] --> AI["AiPlanner memanggil LLM"]
    AI --> DRAFT["Keluaran berstatus DRAFT"]
    DRAFT --> REVIEW{"Pengguna meninjau & menyunting?"}
    REVIEW -- "Setuju" --> SAVE["Simpan/terapkan oleh pengguna"]
    REVIEW -- "Revisi" --> EDIT["Pengguna mengedit manual"] --> SAVE
    REVIEW -- "Tolak" --> DROP["Draf diabaikan"]
    SAVE --> LOG["Metadata dicatat ke ai_request_logs & audit_logs"]
```

**Dasar penyusunan:**
`AiPlanner::generateWbsDraft()`, `generateTestCaseDraft()`, `generateMomSummary()`,
`generateClientWhatsappDraft()`, `generateClientEmailDraft()` (semua mengembalikan
draf, bukan aksi final); status awal MoM `draft` pada migrasi `project_moms`;
gerbang `AiPlanner::isConfigured()`.

**Keterangan untuk skripsi:**
Posisikan HITL sebagai mitigasi risiko halusinasi LLM dan sebagai jaminan
akuntabilitas: keputusan akhir selalu berada pada manusia, sementara AI berperan
sebagai akselerator penyusunan dokumen.

---

## 2. Use Case Diagram

**Gambar 3.x Use Case Diagram Avatech Smart-PMIS**

Diagram use case berikut menggambarkan empat aktor utama beserta fungsi yang dapat
mereka akses. (Mermaid tidak memiliki notasi use-case UML baku; representasi
disusun sebagai graf aktor–use case yang setara secara makna.)

```mermaid
flowchart LR
    CEO(("CEO / PM"))
    SAQA(("System Analyst / QA"))
    DEV(("Fullstack Developer"))
    UIUX(("UI/UX Designer"))

    UC1["Login"]
    UC2["Kelola Project"]
    UC3["Kelola Client"]
    UC4["Kelola Team Assignment"]
    UC5["Pantau Executive Monitor"]
    UC6["Kelola MoM"]
    UC7["Generate AI MoM (Fixer)"]
    UC8["Generate AI WBS"]
    UC9["Kelola WBS / Task"]
    UC10["Kelola Kanban Workspace"]
    UC11["Kelola QC / Test Case"]
    UC12["Generate AI Test Case"]
    UC13["Export PDF (WBS / Test Case)"]
    UC14["Draft WhatsApp / Email Client"]
    UC15["Lihat Audit Trail / Activity Log"]
    UC16["Lihat AI Monitor"]
    UC17["Lihat System Health"]
    UC18["Kelola Settings"]

    CEO --- UC1 & UC2 & UC3 & UC4 & UC5 & UC13 & UC14 & UC15 & UC16 & UC17 & UC18
    SAQA --- UC1 & UC6 & UC7 & UC8 & UC9 & UC11 & UC12 & UC13 & UC15
    DEV --- UC1 & UC9 & UC10 & UC13 & UC15
    UIUX --- UC1 & UC10 & UC15
```

**Dasar penyusunan:**
`routes/web.php` (named routes per fitur), `config/navigation.php`,
`SystemHealthController::roleMatrix()` (matriks peran→akses), controller terkait
tiap use case.

**Keterangan untuk skripsi:**
Tekankan bahwa use case AI (Generate AI MoM/WBS/Test Case, Draft Client) adalah
use case *berbantuan*, bukan otomatisasi penuh — relasinya selalu disertai
peninjauan pengguna. Aktor admin-tier (admin/super_admin/developer) dapat
dianggap perluasan dari CEO/PM dan opsional tidak digambarkan agar diagram
ringkas (sebutkan di narasi).

---

## 3. Activity Diagram — Sistem Berjalan

**Gambar 3.x Activity Diagram Sistem Berjalan**

Diagram berikut menggambarkan alur kerja proyek yang berjalan saat ini (eksisting)
sebelum penerapan Smart-PMIS, yang umumnya dilakukan secara manual dan tersebar di
berbagai berkas.

```mermaid
flowchart TD
    ST([Mulai]) --> RG["Requirement Gathering\n(notulen rapat manual)"]
    RG --> PL["Planning & WBS\n(penyusunan manual)"]
    PL --> DS["Design"]
    DS --> DV["Development"]
    DV --> QA["QA / Testing"]
    QA --> REV{"Ada revisi?"}
    REV -- "Ya" --> DV
    REV -- "Tidak" --> FN["Final / Handover"]
    FN --> EN([Selesai])
```

**Dasar penyusunan:**
Selaras dengan narasi proses bisnis pada BAB III (tahapan
Requirement → Planning → Design → Development → QA → Revisi → Handover).
*Diagram ini bersifat deskriptif terhadap proses bisnis, bukan terhadap kode.*

**Keterangan untuk skripsi:**
Gunakan diagram ini untuk menonjolkan kelemahan sistem berjalan: dokumentasi
tidak terpusat, penelusuran riwayat sulit, serta penyusunan WBS dan test case
memakan waktu — yang kemudian menjadi dasar usulan Smart-PMIS.

---

## 4. Activity Diagram — Sistem Usulan Avatech Smart-PMIS

**Gambar 3.x Activity Diagram Sistem Usulan Avatech Smart-PMIS**

Diagram berikut menggambarkan alur kerja pada Smart-PMIS dengan asistensi AI yang
selalu disertai validasi pengguna (HITL).

```mermaid
flowchart TD
    ST([Mulai]) --> LOGIN["Login"]
    LOGIN --> ROLE{"Peran pengguna"}
    ROLE -- "CEO/PM" --> EXE["Executive Monitor"]
    ROLE -- "Operasional" --> DASH["Operational Dashboard"]
    EXE --> PRJ["Buat / Kelola Project"]
    DASH --> PRJ
    PRJ --> MOM["Tambah MoM"]
    MOM --> FIX["AI MoM Fixer (draf ringkasan)"]
    FIX --> RV1{"Tinjau & setujui MoM?"}
    RV1 -- "Revisi" --> MOM
    RV1 -- "Setuju" --> WBS["AI WBS Generator (draf modul & task)"]
    WBS --> RV2{"Tinjau / edit WBS?"}
    RV2 -- "Revisi" --> WBS
    RV2 -- "Setuju" --> TASK["Assignment Task / Kanban Workspace"]
    TASK --> QC["Kelola QC / Test Case"]
    QC --> ATC["AI Test Case Generator (draf)"]
    ATC --> RV3{"Validasi test case?"}
    RV3 -- "Revisi" --> QC
    RV3 -- "Setuju" --> PDF["Export PDF (WBS / Test Case)"]
    PDF --> LOG["Audit Trail & AI Monitor mencatat aktivitas"]
    LOG --> EN([Selesai])
```

**Dasar penyusunan:**
`routes/web.php` (`projects.moms.store`, `projects.ai-mom.fix`,
`projects.ai-wbs.generate`, `projects.tasks.*`, `projects.qc.*`,
`projects.ai-test-cases.generate`, `projects.export.wbs`,
`projects.export.test-cases`), `ProjectController`, `resources/views/projects/show.blade.php`.

**Keterangan untuk skripsi:**
Bandingkan langsung dengan Activity Diagram Sistem Berjalan (No. 3) untuk
menunjukkan nilai tambah: dasbor sesuai peran, dokumentasi terpusat, asistensi AI
pada titik-titik yang paling memakan waktu (MoM, WBS, test case), serta ekspor dan
pencatatan otomatis. Tekankan kembali bahwa setiap keluaran AI melewati gerbang
validasi.

---

## 5. Sequence Diagram — Alur Asistensi AI: MoM → WBS → Test Case

**Gambar 3.x Sequence Diagram Alur Asistensi AI MoM, WBS, dan Test Case**

Diagram urutan berikut memperlihatkan interaksi antar-komponen pada alur asistensi
AI inti, lengkap dengan titik validasi Human-in-the-Loop.

```mermaid
sequenceDiagram
    actor SAQA as SA/QA
    participant UI as Project Detail UI
    participant PC as ProjectController
    participant AI as AiPlanner
    participant LLM as LLM Provider
    participant DB as Database
    participant MON as AI Monitor / Audit Log

    SAQA->>UI: Input MoM / minta generate WBS
    UI->>PC: POST ai-wbs/generate
    PC->>AI: generateWbsDraft(context)
    AI->>AI: isConfigured()? + susun prompt
    AI->>LLM: kirim prompt (urutan provider)
    LLM-->>AI: respons JSON
    AI->>AI: parse & validasi format
    AI-->>PC: draf WBS (modul + task)
    PC-->>UI: tampilkan draf untuk ditinjau
    SAQA->>UI: tinjau / edit / setujui
    UI->>PC: simpan WBS yang disetujui
    PC->>DB: simpan project_modules & project_tasks
    AI->>MON: catat metadata (ai_request_logs)
    PC->>MON: catat aktivitas (audit_logs)
    Note over SAQA,UI: Alur serupa berlaku untuk AI Test Case Generator (HITL)
```

**Dasar penyusunan:**
`ProjectController::generateWbsFromMom()`, `generateTestCases()`, `fixLatestMom()`;
`AiPlanner::generateWbsDraft()`, `callConfiguredProviders()`, `logAiRequest()`;
`AuditLogger::log()`; migrasi `project_modules`, `project_tasks`, `ai_request_logs`,
`audit_logs`.

**Keterangan untuk skripsi:**
Soroti tiga hal: (1) AI dipanggil melalui service `AiPlanner`, bukan langsung dari
controller ke LLM; (2) ada validasi format respons sebelum draf ditampilkan;
(3) penyimpanan ke basis data hanya terjadi setelah persetujuan pengguna,
sedangkan pencatatan metadata terjadi terlepas dari hasil (sukses/gagal).

---

## 6. Sequence Diagram — Draft Komunikasi Client (WhatsApp/Email)

**Gambar 3.x Sequence Diagram Draft Komunikasi Client Berbantuan AI**

Diagram berikut memperlihatkan pembuatan draf pesan WhatsApp/Email untuk client.
Sistem **tidak mengirim** pesan secara otomatis; keluaran hanya berupa teks draf
yang dapat disalin atau diedit pengguna.

```mermaid
sequenceDiagram
    actor CEO as CEO / PM
    participant UI as Client Directory UI
    participant CC as ClientController
    participant AI as AiPlanner
    participant LLM as LLM Provider
    participant DB as Database
    participant MON as AI Monitor

    CEO->>UI: Minta draf WhatsApp/Email untuk client
    UI->>CC: POST clients/{client}/draft/whatsapp (atau /email)
    CC->>AI: generateClientWhatsappDraft(context)
    AI->>LLM: kirim prompt (urutan provider)
    LLM-->>AI: respons teks draf
    AI-->>CC: teks draf
    AI->>MON: catat metadata (ai_request_logs)
    CC-->>UI: tampilkan draf (salin / edit manual)
    CEO->>CEO: kirim sendiri via kanal eksternal
    Note over UI,CEO: Tidak ada auto-send. Sistem berhenti pada draf.
```

**Dasar penyusunan:**
`routes/web.php` (`clients.draft.whatsapp`, `clients.draft.email`),
`ClientController::draftWhatsapp()`, `draftEmail()`,
`AiPlanner::generateClientWhatsappDraft()`, `generateClientEmailDraft()`,
`logAiRequest()` (feature `client_whatsapp_draft` / `client_email_draft`).

**Keterangan untuk skripsi:**
Tekankan aspek keamanan dan etika: sistem sengaja **tidak** memiliki kapabilitas
pengiriman otomatis untuk mencegah komunikasi yang tidak diinginkan; AI hanya
membantu menyusun bahasa, keputusan dan pengiriman tetap di tangan manusia.

---

## 7. Class Diagram

**Gambar 3.x Class Diagram Avatech Smart-PMIS**

Diagram kelas berikut disusun dari model Eloquent dan migrasi yang ada. Hanya
atribut kunci yang ditampilkan agar ringkas.

```mermaid
classDiagram
    class User {
        +id
        +name
        +email
        +phone
        +position
        +department
        +level
        +skills
        +avatar_color
        +archived_at
    }
    class Client {
        +id
        +name
        +code
        +tier
        +industry
        +pic_name
        +email
        +relationship_health
        +archived_at
    }
    class Project {
        +id
        +code
        +name
        +client_id
        +lead_user_id
        +phase
        +progress
        +status
        +ai_wbs_generated
        +archived_at
    }
    class TeamAssignment {
        +id
        +user_id
        +project_id
        +title
        +type
        +status
        +estimated_hours
        +due_date
    }
    class ProjectModule {
        +id
        +project_id
        +title
        +status
        +estimate_hours
        +sort_order
    }
    class ProjectTask {
        +id
        +project_id
        +project_module_id
        +assigned_to
        +title
        +status
        +priority
        +estimate_hours
    }
    class ProjectMom {
        +id
        +project_id
        +created_by
        +meeting_date
        +notes
        +summary
        +status
    }
    class ProjectQcTest {
        +id
        +project_id
        +project_module_id
        +project_task_id
        +created_by
        +title
        +scenario
        +expected_result
        +actual_result
        +status
        +priority
    }
    class AuditLog {
        +id
        +user_id
        +action
        +module
        +auditable_type
        +auditable_id
        +old_values
        +new_values
    }
    class AiRequestLog {
        +id
        +user_id
        +project_id
        +client_id
        +feature
        +provider
        +model
        +status
        +fallback_path
        +latency_ms
    }

    Client "1" --> "*" Project : memiliki
    User "1" --> "*" Project : memimpin (lead_user_id)
    Project "1" --> "*" TeamAssignment : penugasan
    User "1" --> "*" TeamAssignment : ditugaskan
    Project "1" --> "*" ProjectModule : modul WBS
    Project "1" --> "*" ProjectTask : task
    ProjectModule "1" --> "*" ProjectTask : berisi
    User "1" --> "*" ProjectTask : assignee (assigned_to)
    Project "1" --> "*" ProjectMom : notulen
    User "1" --> "*" ProjectMom : pembuat
    Project "1" --> "*" ProjectQcTest : uji QC
    ProjectModule "1" --> "*" ProjectQcTest : terkait
    ProjectTask "1" --> "*" ProjectQcTest : terkait
    User "1" --> "*" AuditLog : pelaku
    User "1" --> "*" AiRequestLog : pemohon
    Project "1" --> "*" AiRequestLog : konteks
    Client "1" --> "*" AiRequestLog : konteks
```

**Dasar penyusunan:**
Model pada `app/Models/` (`User`, `Client`, `Project`, `TeamAssignment`,
`ProjectModule`, `ProjectTask`, `ProjectMom`, `ProjectQcTest`, `AuditLog`,
`AiRequestLog`) dan migrasi terkait di `database/migrations`. `AuditLog`
menggunakan relasi polimorfik (`auditable`).

**Keterangan untuk skripsi:**
Jelaskan bahwa `Project` adalah entitas pusat yang menautkan client, tim, WBS
(modul + task), MoM, dan QC. `AuditLog` bersifat polimorfik sehingga dapat
mencatat perubahan pada beragam entitas. `AiRequestLog` terpisah dari log audit
karena menyimpan metadata pemrosesan AI, bukan perubahan data domain.

---

## 8. Entity Relationship Diagram (ERD)

**Gambar 3.x Entity Relationship Diagram Avatech Smart-PMIS**

ERD berikut menggambarkan tabel inti dan relasinya berdasarkan migrasi. Tabel
teknis Laravel (cache, jobs, sessions, tabel izin Spatie) tidak ditampilkan pada
ERD utama agar fokus pada domain bisnis.

```mermaid
erDiagram
    USERS ||--o{ PROJECTS : "memimpin"
    CLIENTS ||--o{ PROJECTS : "memiliki"
    USERS ||--o{ TEAM_ASSIGNMENTS : "ditugaskan"
    PROJECTS ||--o{ TEAM_ASSIGNMENTS : "penugasan"
    PROJECTS ||--o{ PROJECT_MODULES : "modul"
    PROJECTS ||--o{ PROJECT_TASKS : "task"
    PROJECT_MODULES ||--o{ PROJECT_TASKS : "berisi"
    USERS ||--o{ PROJECT_TASKS : "assignee"
    PROJECTS ||--o{ PROJECT_MOMS : "notulen"
    USERS ||--o{ PROJECT_MOMS : "pembuat"
    PROJECTS ||--o{ PROJECT_QC_TESTS : "uji QC"
    PROJECT_MODULES ||--o{ PROJECT_QC_TESTS : "terkait"
    PROJECT_TASKS ||--o{ PROJECT_QC_TESTS : "terkait"
    USERS ||--o{ AUDIT_LOGS : "pelaku"
    USERS ||--o{ AI_REQUEST_LOGS : "pemohon"
    PROJECTS ||--o{ AI_REQUEST_LOGS : "konteks"
    CLIENTS ||--o{ AI_REQUEST_LOGS : "konteks"

    USERS {
        bigint id PK
        string name
        string email
        string position
        string department
        timestamp archived_at
    }
    CLIENTS {
        bigint id PK
        string name
        string code
        enum tier
        string industry
        tinyint relationship_health
        timestamp archived_at
    }
    PROJECTS {
        bigint id PK
        string code
        string name
        bigint client_id FK
        bigint lead_user_id FK
        string phase
        tinyint progress
        enum status
        boolean ai_wbs_generated
    }
    TEAM_ASSIGNMENTS {
        bigint id PK
        bigint user_id FK
        bigint project_id FK
        string title
        string status
        smallint estimated_hours
        date due_date
    }
    PROJECT_MODULES {
        bigint id PK
        bigint project_id FK
        string title
        string status
        smallint estimate_hours
        int sort_order
    }
    PROJECT_TASKS {
        bigint id PK
        bigint project_id FK
        bigint project_module_id FK
        bigint assigned_to FK
        string title
        string status
        string priority
        smallint estimate_hours
    }
    PROJECT_MOMS {
        bigint id PK
        bigint project_id FK
        bigint created_by FK
        date meeting_date
        text notes
        text summary
        string status
    }
    PROJECT_QC_TESTS {
        bigint id PK
        bigint project_id FK
        bigint project_module_id FK
        bigint project_task_id FK
        bigint created_by FK
        string title
        text scenario
        text expected_result
        text actual_result
        string status
        string priority
    }
    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK
        string action
        string module
        string auditable_type
        bigint auditable_id
        json old_values
        json new_values
    }
    AI_REQUEST_LOGS {
        bigint id PK
        bigint user_id FK
        bigint project_id FK
        bigint client_id FK
        string feature
        string provider
        string model
        string status
        json fallback_path
        int latency_ms
    }
```

**Dasar penyusunan:**
Migrasi: `create_users_table` + `add_team_profile_fields_to_users_table`,
`create_clients_table` + `add_profile_fields_to_clients_table`,
`create_projects_table` (+ description/archived_at), `create_team_assignments_table`
(+ estimated_hours), `create_project_modules_table`, `create_project_tasks_table`,
`create_project_moms_table`, `create_project_qc_tests_table`,
`create_audit_logs_table`, `create_ai_request_logs_table`.

**Keterangan untuk skripsi:**
Sebutkan bahwa kardinalitas dominan adalah satu-ke-banyak dengan `projects`
sebagai poros. Relasi `audit_logs` ke entitas lain bersifat polimorfik
(`auditable_type`/`auditable_id`), sehingga pada ERD digambarkan sebagai kolom
generik, bukan foreign key kaku. Tabel pendukung preferensi pengguna
(`workspace_settings`, `user_notification_preferences`, dll.) dapat disebut dalam
narasi sebagai tabel konfigurasi tanpa dimasukkan ke ERD inti.

---

## 9. Alur Integrasi API LLM Multi-Provider

**Gambar 3.x Alur Integrasi API LLM Multi-Provider**

Smart-PMIS menggunakan strategi *fallback* berurutan untuk meningkatkan
ketersediaan layanan AI. Permintaan dicoba ke provider sesuai urutan konfigurasi;
bila gagal, sistem berpindah ke provider berikutnya. Hanya metadata aman yang
dicatat.

```mermaid
flowchart TD
    REQ["Permintaan fitur AI\n(WBS / Test Case / MoM / Draft Client)"] --> CFG{"isConfigured()?\nada provider terkonfigurasi"}
    CFG -- "Tidak" --> ERRC["Tampilkan: AI belum dikonfigurasi"]
    CFG -- "Ya" --> ORD["Baca urutan provider\n(config/ai.php)"]
    ORD --> G{"Coba Gemini"}
    G -- "Sukses" --> PARSE["Parse & validasi respons"]
    G -- "Gagal" --> GR{"Coba Groq"}
    GR -- "Sukses" --> PARSE
    GR -- "Gagal" --> OR{"Coba OpenRouter"}
    OR -- "Sukses" --> PARSE
    OR -- "Gagal" --> FAIL["Semua provider gagal"]
    PARSE -- "Valid" --> SAVE["Kembalikan draf + simpan metadata\n(provider, model, token, latensi, fallback_path)"]
    PARSE -- "Tidak valid" --> FAIL
    FAIL --> ERRF["Tampilkan pesan ramah +\ncatat status gagal (metadata aman)"]
    SAVE --> DONE([Selesai])
    ERRF --> DONE
    ERRC --> DONE
```

**Dasar penyusunan:**
`config/ai.php` (`provider_order` default `gemini,groq,openrouter`, key dari env),
`AiPlanner::isConfigured()`, `configuredProviders()`, `callConfiguredProviders()`,
`callGemini()` / `callGroq()` / `callOpenRouter()`, `parseResponse()`,
`logAiRequest()`, `sanitizeAiError()` (meredaksi pola API key pada pesan error).

**Keterangan untuk skripsi:**
Tekankan dua kontribusi desain: (1) **ketahanan** melalui fallback multi-provider
sehingga kegagalan satu penyedia tidak menghentikan fitur; (2) **keamanan &
privasi** karena API key hanya dibaca dari environment, tidak pernah ditampilkan,
dan log hanya menyimpan metadata (bukan isi prompt/respons). Sebutkan bahwa
kegagalan total tetap ditangani dengan pesan ramah, bukan *error* mentah.

---

## 10. Monitoring, Auditabilitas, dan Kesiapan Sistem

**Gambar 3.x Perancangan Monitoring, Auditabilitas, dan Kesiapan Sistem**

Untuk mendukung kesiapan operasional (production support), Smart-PMIS menyediakan
empat komponen pendukung yang saling melengkapi.

```mermaid
flowchart TD
    SYS["Aktivitas Sistem & Pengguna"] --> AUD["Audit Trail\n(audit_logs)"]
    AIP["Pemrosesan AI"] --> MON["AI Monitor\n(ai_request_logs: metadata aman)"]
    INFRA["Infrastruktur (DB, cache, storage,\nPDF, provider AI, environment)"] --> HEALTH["System Health\n(pengecekan kesiapan)"]
    DATA["Data sistem (assignment, task, jadwal)"] --> INS["Smart Insights / Reminders\n(berbasis aturan)"]

    AUD --> SET["Settings\n(titik konfigurasi & dukungan)"]
    MON --> SET
    HEALTH --> SET
    INS --> DASH["Dashboard / Topbar Notifikasi"]

    subgraph DESC["Peran tiap komponen"]
        D1["Audit Trail = jejak aktivitas pengguna/sistem"]
        D2["AI Monitor = metadata pemrosesan AI"]
        D3["System Health = pengecekan kesiapan"]
        D4["Smart Insights = pengingat berbasis aturan dari data sistem"]
    end
```

**Dasar penyusunan:**
`AuditController` + `AuditLogger` + `audit_logs`; `AiMonitorController` +
`AiRequestLog` + `ai_request_logs`; `SystemHealthController` (cek database,
storage, cache, PDF, AI provider, environment, migrasi, last activity);
`SmartInsightService` (rekomendasi berbasis aturan dari `team_assignments`,
`project_tasks`, `audit_logs`); `SettingsController`.

**Keterangan untuk skripsi:**
Jelaskan pemisahan tanggung jawab: Audit Trail untuk akuntabilitas perubahan data,
AI Monitor untuk transparansi pemakaian AI, System Health untuk verifikasi
kesiapan sebelum demo/deploy, dan Smart Insights sebagai bantuan pengingat
berbasis aturan (bukan prediksi AI). Tegaskan bahwa Smart Insights bersifat
*rule-based* agar tidak terjadi overclaim terhadap kapabilitas AI.

---

## Rekomendasi Penempatan di BAB III

| Subbab BAB III | Diagram/Tabel yang Digunakan |
|----------------|------------------------------|
| 3.5.1 Arsitektur Sistem | Diagram No. 1 (Arsitektur Sistem) |
| 3.5.2 Role dan Hak Akses | Tabel + diagram pada bagian 3.5.2 |
| 3.5.3 Human-in-the-Loop | Diagram HITL pada bagian 3.5.3 (didukung No. 5) |
| 3.5.4 Use Case Diagram | Diagram No. 2 (Use Case) |
| 3.5.5 Activity Diagram | Diagram No. 3 (Berjalan) & No. 4 (Usulan) |
| 3.5.6 Sequence Diagram | Diagram No. 5 (AI MoM→WBS→Test Case) & No. 6 (Draft Client) |
| 3.5.7 Class Diagram | Diagram No. 7 (Class) |
| 3.5.8 ERD | Diagram No. 8 (ERD) |
| 3.5.10 Integrasi API LLM Multi-Provider | Diagram No. 9 (Fallback Multi-Provider) |
| 3.5.12 Monitoring, Auditabilitas, dan Kesiapan Sistem | Diagram No. 10 (Monitoring) |

> Subbab 3.5.9 dan 3.5.11 sengaja tidak dipetakan ke diagram pada dokumen ini
> (kemungkinan diisi narasi lain, mis. rancangan antarmuka atau pengujian).
> Sesuaikan penomoran dengan kerangka BAB III final Anda.

---

## Catatan Validasi

Hal-hal berikut perlu Anda verifikasi/sesuaikan secara manual sebelum dimasukkan
ke naskah:

1. **Penamaan tabel penugasan.** Brief menyebut `project_assignments` /
   `ProjectAssignment`, tetapi implementasi nyata menggunakan **`team_assignments`**
   (model `TeamAssignment`). Diagram memakai nama nyata. Pastikan narasi skripsi
   konsisten dengan nama ini.
2. **Titik akses System Health.** Brief menyatakan System Health diakses dari
   *Settings > Umum*. Pada kode, terdapat route `/system-health` (`system-health.index`)
   yang dilindungi middleware `ceo.pm`, namun `config/navigation.php` **tidak**
   menampilkannya di sidebar. **Perlu verifikasi** lokasi tautan masuk yang
   sebenarnya (mis. di halaman Settings atau menu sekunder).
3. **AI MoM Fixer & AI Test Case Generator.** Route dan method tersedia
   (`projects.ai-mom.fix`, `projects.ai-test-cases.generate`). Status pengaktifan
   tombol di UI Project Detail dapat bergantung pada `AiPlanner::isConfigured()`
   (gerbang konfigurasi). **Perlu verifikasi** apakah pada build demo Anda kedua
   fitur ini sudah aktif penuh atau masih berstatus "segera tersedia".
4. **Aktor admin-tier.** Peran `admin`/`super_admin`/`developer` memiliki akses
   setara CEO/PM plus panel Filament `/admin`. Pada Use Case Diagram, aktor ini
   tidak digambar terpisah agar ringkas — sesuaikan bila pembimbing meminta aktor
   admin eksplisit.
5. **Tabel preferensi/keamanan.** Terdapat tabel pendukung (`workspace_settings`,
   `user_notification_preferences`, `user_integration_states`,
   `user_recovery_codes`, `user_security_preferences`,
   `account_deletion_requests`, `team_workloads`) yang **tidak** dimasukkan ke ERD
   inti. Putuskan apakah perlu ERD pendukung terpisah.
6. **Diagram Sistem Berjalan (No. 3)** bersifat deskriptif terhadap proses bisnis
   organisasi, bukan terhadap kode. Sesuaikan tahapannya dengan kondisi nyata di
   PT Ava Teknologi Nusantara bila berbeda.
7. **Penomoran subbab (3.5.x)** mengikuti permintaan brief; selaraskan dengan
   daftar isi BAB III final Anda.
8. **Atribut pada Class Diagram & ERD** sengaja diringkas (atribut kunci saja).
   Bila pembimbing meminta seluruh kolom, lengkapi dari berkas migrasi terkait.
