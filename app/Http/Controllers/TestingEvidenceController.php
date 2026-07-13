<?php

namespace App\Http\Controllers;

use App\Models\TestingEvidence;
use App\Services\AuditLogger;
use App\Support\AppTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TestingEvidenceController extends Controller
{
    public const CATEGORIES = [
        'Black-Box Testing' => 'Black-Box Testing',
        'UAT Terbatas' => 'UAT Terbatas',
        'Validasi Keluaran LLM' => 'Validasi Keluaran LLM',
        'TestSprite' => 'TestSprite',
    ];

    public function index()
    {
        $evidences = TestingEvidence::orderByDesc('tested_at')
            ->orderByDesc('id')
            ->get()
            // Show only one canonical row per category (the latest) so stale
            // duplicate rows never surface in the list, even before a re-seed.
            ->groupBy('category')
            ->map(fn ($group) => $group->first())
            ->sortBy(fn ($ev) => array_search($ev->category, array_keys(self::CATEGORIES), true))
            ->values()
            ->map(function ($ev) {
                return [
                    'id' => $ev->id,
                    'category' => $ev->category,
                    'title' => $ev->title,
                    'total_scenarios' => $ev->total_scenarios,
                    'passed_scenarios' => $ev->passed_scenarios,
                    'failed_scenarios' => $ev->failed_scenarios,
                    'result_status' => $ev->result_status,
                    'tested_at' => $ev->tested_at?->format('Y-m-d'),
                    'tested_at_label' => $ev->tested_at ? AppTime::cast($ev->tested_at)?->format('d M Y') : '—',
                    'notes' => $ev->notes,
                    'evidence_url' => $ev->evidence_url,
                    'evidence_file_url' => $ev->evidence_file_path ? Storage::disk('public')->url($ev->evidence_file_path) : null,
                ];
            });

        $statusLabels = [
            'Black-Box Testing' => 'Lulus',
            'UAT Terbatas' => 'Diterima',
            'Validasi Keluaran LLM' => 'Valid',
            'TestSprite' => 'Lulus',
        ];

        $groupedEvidence = TestingEvidence::orderByDesc('tested_at')
            ->orderByDesc('id')
            ->get(['id', 'category', 'total_scenarios', 'passed_scenarios', 'failed_scenarios', 'tested_at'])
            ->groupBy('category');

        $summaries = collect(self::CATEGORIES)
            ->keys()
            ->map(function ($category) use ($groupedEvidence, $statusLabels) {
                // Use the latest/final canonical evidence per category instead of
                // summing every row — that keeps the summary correct (e.g. 12/12)
                // even if older or duplicate rows still linger in the table.
                $latest = $groupedEvidence->get($category, collect())->first();

                return [
                    'category' => $category,
                    'total' => (int) ($latest->total_scenarios ?? 0),
                    'passed' => (int) ($latest->passed_scenarios ?? 0),
                    'failed' => (int) ($latest->failed_scenarios ?? 0),
                    'status' => $statusLabels[$category] ?? 'Tervalidasi',
                ];
            });

        return view('testing-evidence.index', [
            'title' => 'QA Evidence',
            'evidences' => $evidences,
            'summaries' => $summaries,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'title' => ['required', 'string', 'max:255'],
            'total_scenarios' => ['required', 'integer', 'min:0'],
            'passed_scenarios' => ['required', 'integer', 'min:0'],
            'failed_scenarios' => ['required', 'integer', 'min:0'],
            'result_status' => ['required', 'string', 'max:50'],
            'tested_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'evidence_url' => ['nullable', 'url', 'max:1000'],
            'file' => ['nullable', 'file', 'max:10240'],
        ], [
            'title.required' => 'Judul wajib diisi.',
            'category.in' => 'Kategori tidak valid.',
            'evidence_url.url' => 'URL bukti harus valid.',
            'file.max' => 'File maksimal 10MB.',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('testing-evidence', 'public');
        }

        $evidence = TestingEvidence::create([
            'category' => $validated['category'],
            'title' => $validated['title'],
            'total_scenarios' => $validated['total_scenarios'],
            'passed_scenarios' => $validated['passed_scenarios'],
            'failed_scenarios' => $validated['failed_scenarios'],
            'result_status' => $validated['result_status'],
            'tested_at' => $validated['tested_at'],
            'notes' => $validated['notes'] ?? null,
            'evidence_url' => $validated['evidence_url'] ?? null,
            'evidence_file_path' => $filePath,
        ]);

        AuditLogger::log(
            'testing_evidence_created',
            'Testing Evidence',
            'Menambah Testing Evidence: <strong>' . e($evidence->title) . '</strong> (' . e($evidence->category) . ')',
            $evidence,
            null,
            [
                'id' => $evidence->id,
                'category' => $evidence->category,
                'passed' => $evidence->passed_scenarios,
                'total' => $evidence->total_scenarios,
            ]
        );

        return redirect()->route('testing-evidence.index')
            ->with('status', 'Testing Evidence "' . $evidence->title . '" berhasil ditambahkan.');
    }

    public function destroy(TestingEvidence $evidence)
    {
        $title = $evidence->title;
        $category = $evidence->category;

        if ($evidence->evidence_file_path) {
            Storage::disk('public')->delete($evidence->evidence_file_path);
        }

        AuditLogger::log(
            'testing_evidence_deleted',
            'Testing Evidence',
            'Menghapus Testing Evidence: <strong>' . e($title) . '</strong> (' . e($category) . ')',
            null,
            [
                'id' => $evidence->id,
                'title' => $title,
                'category' => $category,
            ],
            null
        );

        $evidence->delete();

        return redirect()->route('testing-evidence.index')
            ->with('status', 'Testing Evidence "' . $title . '" berhasil dihapus.');
    }
}