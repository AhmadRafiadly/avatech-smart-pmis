<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectChangeRequest;
use App\Models\ProjectClientReview;
use App\Models\ProjectTask;
use App\Models\ProjectTaskDesignDeliverable;
use App\Services\AuditLogger;
use App\Support\AppTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientReviewController extends Controller
{
    public function show(string $token)
    {
        $review = $this->findReview($token);
        if (! $review || ! $this->ensureReviewAvailable($review, false)) {
            return $this->unavailableView();
        }

        $shouldLogOpen = $review->last_opened_at === null
            || AppTime::cast($review->last_opened_at)?->lt(AppTime::now()->copy()->subHour());

        $review->increment('opened_count');
        $review->forceFill(['last_opened_at' => AppTime::now()])->save();

        if ($shouldLogOpen) {
            AuditLogger::log(
                'client_review_opened',
                'Client Review',
                'Client review dibuka untuk proyek <strong>' . e($review->project->name) . '</strong>',
                $review,
                null,
                ['project_id' => $review->project_id, 'status' => $review->status],
            );
        }

        return view('client-reviews.show', $this->reviewViewData($review));
    }

    public function approve(Request $request, string $token)
    {
        $review = $this->findReview($token);
        if (! $review || ! $this->ensureReviewAvailable($review, true)) {
            return $this->unavailableView();
        }

        $validated = $request->validate([
            'client_name' => ['nullable', 'string', 'max:160'],
            'client_email' => ['nullable', 'email', 'max:190'],
            'client_feedback' => ['nullable', 'string', 'max:4000'],
        ]);

        $review->update([
            'status' => 'approved',
            'approved_at' => AppTime::now(),
            'client_name' => $validated['client_name'] ?? $review->client_name,
            'client_email' => $validated['client_email'] ?? $review->client_email,
            'client_feedback' => $validated['client_feedback'] ?? null,
        ]);

        AuditLogger::log(
            'client_review_approved',
            'Client Review',
            'Client menyetujui review <strong>' . e($review->title) . '</strong> untuk proyek <strong>' . e($review->project->name) . '</strong>',
            $review,
            null,
            [
                'project_id' => $review->project_id,
                'client_name' => $review->client_name,
                'feedback_summary' => $this->feedbackSummary($review->client_feedback),
            ],
        );

        return redirect()
            ->route('client-reviews.show', $review->token)
            ->with('status', 'Terima kasih. Approval client berhasil dicatat.');
    }

    public function requestRevision(Request $request, string $token)
    {
        $review = $this->findReview($token);
        if (! $review || ! $this->ensureReviewAvailable($review, true)) {
            return $this->unavailableView();
        }

        $validated = $request->validate([
            'client_name' => ['nullable', 'string', 'max:160'],
            'client_email' => ['nullable', 'email', 'max:190'],
            'client_feedback' => ['required', 'string', 'max:4000'],
        ], [
            'client_feedback.required' => 'Mohon isi catatan revisi agar tim Avatech dapat menindaklanjuti dengan jelas.',
        ]);

        $review->update([
            'status' => 'revision_requested',
            'revision_requested_at' => AppTime::now(),
            'client_name' => $validated['client_name'] ?? $review->client_name,
            'client_email' => $validated['client_email'] ?? $review->client_email,
            'client_feedback' => $validated['client_feedback'],
        ]);

        AuditLogger::log(
            'client_review_revision_requested',
            'Client Review',
            'Client meminta revisi pada review <strong>' . e($review->title) . '</strong> untuk proyek <strong>' . e($review->project->name) . '</strong>',
            $review,
            null,
            [
                'project_id' => $review->project_id,
                'client_name' => $review->client_name,
                'feedback_summary' => $this->feedbackSummary($review->client_feedback),
            ],
        );

        return redirect()
            ->route('client-reviews.show', $review->token)
            ->with('status', 'Catatan revisi berhasil dikirim ke tim Avatech.');
    }

    public function previewDesignDeliverable(string $token, ProjectTaskDesignDeliverable $deliverable)
    {
        $review = $this->findReview($token);
        if (! $review || ! $this->ensureReviewAvailable($review, false) || ! $this->canAccessDeliverable($review, $deliverable)) {
            return $this->unavailableView();
        }

        $path = $this->deliverablePdfPath($deliverable);
        $dataUri = null;
        $message = null;
        if ((filesize($path) ?: 0) <= 10 * 1024 * 1024) {
            $dataUri = 'data:application/pdf;base64,' . base64_encode(file_get_contents($path));
        } else {
            $message = 'File PDF terlalu besar untuk preview internal. Gunakan Download PDF untuk membuka file secara manual.';
        }

        return view('client-reviews.pdf-preview', [
            'title' => 'Preview PDF Mockup',
            'review' => $review,
            'deliverable' => $deliverable,
            'filename' => $this->deliverableFilename($deliverable) . '.pdf',
            'pdfDataUri' => $dataUri,
            'previewMessage' => $message,
            'downloadUrl' => route('client-reviews.design-deliverables.download', [$review->token, $deliverable]),
        ]);
    }

    public function downloadDesignDeliverable(string $token, ProjectTaskDesignDeliverable $deliverable)
    {
        $review = $this->findReview($token);
        if (! $review || ! $this->ensureReviewAvailable($review, false) || ! $this->canAccessDeliverable($review, $deliverable)) {
            return $this->unavailableView();
        }

        return response()->download($this->deliverablePdfPath($deliverable), $this->deliverableFilename($deliverable) . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function findReview(string $token): ?ProjectClientReview
    {
        return ProjectClientReview::query()
            ->where('token', $token)
            ->with([
                'project.client',
                'project.moms' => fn ($query) => $query->orderByDesc('meeting_date')->orderByDesc('id'),
                'project.tasks.designDeliverables',
                'project.qcTests',
                'project.changeRequests',
            ])
            ->first();
    }

    private function ensureReviewAvailable(ProjectClientReview $review, bool $forResponse): bool
    {
        if ($review->isExpired() && $review->status !== 'expired') {
            $review->forceFill(['status' => 'expired'])->save();
        }

        return $forResponse
            ? $review->canReceiveClientResponse()
            : $review->isPubliclyViewable();
    }

    private function reviewViewData(ProjectClientReview $review): array
    {
        $project = $review->project;

        return [
            'title' => 'Project Review',
            'review' => $review,
            'project' => $project,
            'progress' => $this->projectProgress($project),
            'momSummary' => $review->include_mom ? $this->momSummary($project) : null,
            'deliverables' => $review->include_design_deliverables ? $this->deliverableRows($review) : [],
            'qcSummary' => $review->include_qc_summary ? $this->qcSummary($project) : null,
            'changeRequests' => $review->include_change_requests ? $this->changeRequestRows($project) : [],
        ];
    }

    private function momSummary(Project $project): ?array
    {
        $mom = $project->moms->first(fn ($item) => filled($item->summary)) ?: $project->moms->first();
        if (! $mom) {
            return null;
        }

        return [
            'title' => $mom->meeting_date ? AppTime::cast($mom->meeting_date)?->format('d M Y') : 'MoM',
            'summary' => $mom->summary ?: 'Ringkasan MoM belum tersedia.',
        ];
    }

    private function deliverableRows(ProjectClientReview $review): array
    {
        return $review->project->tasks
            ->flatMap(fn (ProjectTask $task) => $task->designDeliverables->map(function (ProjectTaskDesignDeliverable $deliverable) use ($review, $task) {
                return [
                    'id' => $deliverable->id,
                    'task_title' => $task->title,
                    'title' => $deliverable->title,
                    'figma_url' => $deliverable->figma_url,
                    'has_pdf' => filled($deliverable->pdf_file_path),
                    'preview_url' => filled($deliverable->pdf_file_path) ? route('client-reviews.design-deliverables.preview', [$review->token, $deliverable]) : null,
                    'download_url' => filled($deliverable->pdf_file_path) ? route('client-reviews.design-deliverables.download', [$review->token, $deliverable]) : null,
                ];
            }))
            ->values()
            ->all();
    }

    private function qcSummary(Project $project): array
    {
        $by = $project->qcTests->countBy('status');
        $total = $project->qcTests->count();
        $passed = (int) ($by['passed'] ?? 0);

        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => (int) ($by['failed'] ?? 0),
            'pending' => (int) ($by['pending'] ?? 0),
            'retest' => (int) ($by['retest'] ?? 0),
            'percent' => $total > 0 ? (int) round($passed / $total * 100) : 0,
        ];
    }

    private function changeRequestRows(Project $project): array
    {
        return $project->changeRequests
            ->map(fn (ProjectChangeRequest $cr) => [
                'title' => $cr->title,
                'status' => $cr->status,
                'type' => $cr->type,
                'priority' => $cr->priority,
                'timeline_impact_days' => $cr->timeline_impact_days,
            ])
            ->values()
            ->all();
    }

    private function projectProgress(Project $project): int
    {
        $tasks = $project->tasks
            ->reject(fn (ProjectTask $task) => in_array(mb_strtolower(trim((string) $task->status)), ['archived', 'archive', 'cancelled', 'canceled', 'dibatalkan'], true))
            ->values();

        if ($tasks->isEmpty()) {
            return 0;
        }

        $totalHours = (int) $tasks->sum(fn (ProjectTask $task) => max(0, (int) $task->estimate_hours));
        $done = fn (ProjectTask $task) => in_array(mb_strtolower(trim((string) $task->status)), ['done', 'completed', 'complete', 'selesai'], true);

        if ($totalHours > 0) {
            $doneHours = (int) $tasks->filter($done)->sum(fn (ProjectTask $task) => max(0, (int) $task->estimate_hours));

            return (int) round($doneHours / $totalHours * 100);
        }

        return (int) round($tasks->filter($done)->count() / $tasks->count() * 100);
    }

    private function canAccessDeliverable(ProjectClientReview $review, ProjectTaskDesignDeliverable $deliverable): bool
    {
        if (! $review->include_design_deliverables) {
            return false;
        }

        $deliverable->loadMissing('task');

        return $deliverable->task
            && (int) $deliverable->task->project_id === (int) $review->project_id
            && filled($deliverable->pdf_file_path);
    }

    private function deliverablePdfPath(ProjectTaskDesignDeliverable $deliverable): string
    {
        $storagePath = str_replace('\\', '/', ltrim((string) $deliverable->pdf_file_path, '/'));
        abort_unless(
            filled($storagePath)
            && str_starts_with($storagePath, 'project-design-deliverables/')
            && Storage::disk('public')->exists($storagePath),
            404,
        );

        return Storage::disk('public')->path($storagePath);
    }

    private function deliverableFilename(ProjectTaskDesignDeliverable $deliverable): string
    {
        $filename = (string) str($deliverable->title)->slug('-');

        return $filename !== '' ? $filename : 'design-deliverable';
    }

    private function feedbackSummary(?string $feedback): ?string
    {
        if (! filled($feedback)) {
            return null;
        }

        return str($feedback)->squish()->limit(160)->toString();
    }

    private function unavailableView()
    {
        return response()->view('client-reviews.unavailable', [
            'title' => 'Review Tidak Tersedia',
        ], 404);
    }
}
