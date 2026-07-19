<?php

namespace App\Http\Controllers;

use App\Models\TestingEvidence;
use App\Services\AuditLogger;
use App\Support\AppTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TestingEvidenceController extends Controller
{
    public const CATEGORIES = [
        'Black-Box Testing' => 'Black-Box Testing',
        'UAT Terbatas' => 'UAT Terbatas',
        'Validasi Keluaran LLM' => 'Validasi Keluaran LLM',
        'TestSprite' => 'TestSprite',
    ];

    private const ACCESS_ROLES = ['ceo_pm', 'admin', 'super_admin', 'developer', 'sa_qa'];
    private const MANAGE_ROLES = ['ceo_pm', 'admin', 'super_admin', 'developer', 'sa_qa'];

    public function index(Request $request)
    {
        $this->authorizeAccess($request);
        $evidences = TestingEvidence::orderByDesc('tested_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('category')
            ->map(fn ($group) => $group->first())
            ->sortBy(fn ($ev) => array_search($ev->category, array_keys(self::CATEGORIES), true))
            ->values()
            ->map(fn ($ev) => [
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
                'evidence_file_url' => $ev->evidence_file_path ? route('testing-evidence.preview', $ev) : null,
                'evidence_download_url' => $ev->evidence_file_path ? route('testing-evidence.download', $ev) : null,
            ]);

        $statusLabels = [
            'Black-Box Testing' => 'Lulus',
            'UAT Terbatas' => 'Diterima',
            'Validasi Keluaran LLM' => 'Valid',
            'TestSprite' => 'Lulus',
        ];
        $groupedEvidence = TestingEvidence::orderByDesc('tested_at')->orderByDesc('id')->get()->groupBy('category');
        $summaries = collect(self::CATEGORIES)->keys()->map(function ($category) use ($groupedEvidence, $statusLabels) {
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
            'canManage' => $request->user()->hasAnyRole(self::MANAGE_ROLES),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage($request);
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
        ]);
        if ((int) $validated['passed_scenarios'] + (int) $validated['failed_scenarios'] !== (int) $validated['total_scenarios']) {
            throw ValidationException::withMessages(['total_scenarios' => 'Total skenario harus sama dengan jumlah lulus dan gagal.']);
        }

        $filePath = $request->hasFile('file') ? $request->file('file')->store('testing-evidence', 'local') : null;
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
        AuditLogger::log('testing_evidence_created', 'Testing Evidence', 'Menambah Testing Evidence: <strong>'.e($evidence->title).'</strong> ('.e($evidence->category).')', $evidence, null, [
            'id' => $evidence->id,
            'category' => $evidence->category,
            'passed' => $evidence->passed_scenarios,
            'failed' => $evidence->failed_scenarios,
            'total' => $evidence->total_scenarios,
            'has_file' => filled($filePath),
        ]);

        return redirect()->route('testing-evidence.index')->with('status', 'Testing Evidence "'.$evidence->title.'" berhasil ditambahkan.');
    }

    public function preview(Request $request, TestingEvidence $evidence): BinaryFileResponse
    {
        $this->authorizeAccess($request);

        return $this->fileResponse($evidence, 'inline');
    }

    public function download(Request $request, TestingEvidence $evidence): BinaryFileResponse
    {
        $this->authorizeAccess($request);

        return $this->fileResponse($evidence, 'attachment');
    }

    public function destroy(Request $request, TestingEvidence $evidence)
    {
        $this->authorizeManage($request);
        $old = ['id' => $evidence->id, 'title' => $evidence->title, 'category' => $evidence->category, 'has_file' => filled($evidence->evidence_file_path)];
        $evidence->delete();
        AuditLogger::log('testing_evidence_deleted', 'Testing Evidence', 'Menghapus Testing Evidence: <strong>'.e($old['title']).'</strong> ('.e($old['category']).')', null, $old, null);

        return redirect()->route('testing-evidence.index')->with('status', 'Testing Evidence "'.$old['title'].'" berhasil dihapus.');
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ! $user->archived_at && $user->hasAnyRole(self::ACCESS_ROLES), 403);
    }

    private function authorizeManage(Request $request): void
    {
        $this->authorizeAccess($request);
        abort_unless($request->user()->hasAnyRole(self::MANAGE_ROLES), 403);
    }

    private function fileResponse(TestingEvidence $evidence, string $disposition): BinaryFileResponse
    {
        [$disk, $path] = $this->storedFile($evidence->evidence_file_path, 'testing-evidence/');
        $name = basename($path);

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Disposition' => $disposition.'; filename="'.str_replace('"', '', $name).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function storedFile(?string $path, string $prefix): array
    {
        $path = str_replace('\\', '/', ltrim((string) $path, '/'));
        abort_unless(filled($path) && str_starts_with($path, $prefix), 404);
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return [$disk, $path];
            }
        }
        abort(404);
    }
}
