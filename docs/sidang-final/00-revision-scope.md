# Sidang Final Hardening - Revision Scope

## Goal
Revisi pasca-semhas difokuskan untuk menjawab feedback penguji dengan bukti yang bisa ditunjukkan pada sidang akhir.

## Final Strategy
Base tetap menggunakan branch clean agar alur demo skripsi tidak rusak. Fitur lanjutan dari full branch boleh diambil kembali hanya jika langsung menjawab feedback penguji.

## MUST HAVE
1. Requirement Intake / Upload Dokumen
2. AI menggunakan konteks Requirement Intake
3. Testing Evidence Page
4. Formula Reference
5. AI Fallback Evidence
6. Demo Project AIS Universitas end-to-end
7. Penjelasan Data Flow MoM dan AI Draft

## SHOULD HAVE
1. Laravel Feature Test
2. Task Dependency
3. Gantt-lite
4. Quick Assign Recommendation
5. MoSCoW Priority pada Requirement Intake

## COULD HAVE
1. C4 Architecture Diagram sebagai pelengkap
2. JTBD format sebagai pendekatan bantu requirement
3. Export report tambahan untuk evidence

## WON'T DO FOR NOW
1. Full Google Drive API integration
2. Microsoft Project-level scheduler
3. AI auto-assign tanpa approval
4. Full automatic PDF parsing/OCR
5. Rombak total UML/ERD

## Development Rule
Kerjakan kecil, aman, dan bisa dites. Jangan menambah fitur besar sebelum MUST HAVE selesai.