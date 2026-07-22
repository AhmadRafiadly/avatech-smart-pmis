<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectRequirementInboxItem;
use App\Models\TeamAssignment;
use App\Models\User;
use App\Services\RequirementDocumentTextExtractor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class RequirementIntakeUploadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_txt_upload_is_extracted_and_stored_privately_with_metadata(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user);
        $text = "Kebutuhan pengguna\nDashboard akademik";

        $this->actingAs($user)->post(route('projects.requirement-intake.store', $project), [
            'title' => 'PRD Akademik',
            'source_type' => 'prd',
            'priority' => 'must',
            'file' => UploadedFile::fake()->createWithContent('requirements.txt', $text),
        ])->assertSessionHasNoErrors();

        $item = ProjectRequirementInboxItem::where('project_id', $project->id)->firstOrFail();
        $this->assertSame('extracted', $item->extraction_status);
        $this->assertSame($text, $item->extracted_text);
        $this->assertSame(hash('sha256', $text), $item->file_sha256);
        $this->assertSame('text/plain', $item->mime_type);
        Storage::disk('local')->assertExists($item->file_path);
        $audit = \App\Models\AuditLog::where('action', 'requirement_intake_created')->latest('id')->firstOrFail();
        $this->assertSame('requirements.txt', $audit->new_values['original_filename']);
        $this->assertArrayNotHasKey('extracted_text', $audit->new_values);
        $this->assertArrayNotHasKey('file_path', $audit->new_values);
        $this->assertStringNotContainsString($text, json_encode($audit->new_values));
    }

    public function test_mocked_pdf_extraction_is_persisted_and_preview_uses_internal_html(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user);
        $pdf = "%PDF-1.4\nmock";
        $this->mock(RequirementDocumentTextExtractor::class)->shouldReceive('extract')->once()->andReturn(['status' => 'extracted', 'text' => 'Isi PDF']);

        $this->actingAs($user)->post(route('projects.requirement-intake.store', $project), $this->payload(['file' => UploadedFile::fake()->createWithContent('prd.pdf', $pdf)]))->assertSessionHasNoErrors();
        $item = ProjectRequirementInboxItem::where('project_id', $project->id)->firstOrFail();

        $this->assertSame('Isi PDF', $item->extracted_text);
        $preview = $this->actingAs($user)->get(route('projects.requirement-intake.preview', [$project, $item]));
        $csp = $preview->headers->get('Content-Security-Policy');
        preg_match("/script-src 'nonce-([^']+)'/", $csp, $nonce);
        $preview->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertSee(base64_encode($pdf), false)
            ->assertSee('nonce="'.$nonce[1].'"', false)
            ->assertSee('new Uint8Array(binary.length)', false)
            ->assertSee("new Blob([bytes], { type: 'application/pdf' })", false)
            ->assertSee('URL.createObjectURL', false)
            ->assertSee('URL.revokeObjectURL', false)
            ->assertSee('frame.src = objectUrl', false)
            ->assertSee('Memuat preview PDF')
            ->assertDontSee('data:application/pdf;base64,', false)
            ->assertDontSee('fetch(', false)
            ->assertDontSee('XMLHttpRequest', false)
            ->assertSee('Download Original')
            ->assertDontSee($item->file_path, false);
        $this->assertSame("default-src 'none'; script-src 'nonce-{$nonce[1]}'; style-src 'unsafe-inline'; frame-src blob:; base-uri 'none'; object-src 'none'; form-action 'none'; frame-ancestors 'self'", $csp);
        $download = $this->actingAs($user)->get(route('projects.requirement-intake.download', [$project, $item]));
        $download->assertDownload('prd.pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename=prd.pdf');
        $this->assertSame($pdf, file_get_contents($download->baseResponse->getFile()->getPathname()));
    }

    public function test_mismatched_extension_and_content_is_rejected(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user);

        $this->actingAs($user)->post(route('projects.requirement-intake.store', $project), [
            'title' => 'Dokumen palsu',
            'source_type' => 'prd',
            'priority' => 'must',
            'summary' => 'Ringkasan',
            'file' => UploadedFile::fake()->createWithContent('fake.pdf', 'plain text'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('project_requirement_inbox_items', ['project_id' => $project->id]);
    }

    public function test_pdf_extractor_failure_requires_summary_and_does_not_persist(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user);
        $extractor = $this->mock(RequirementDocumentTextExtractor::class);
        $extractor->shouldReceive('extract')->once()->andReturn(['status' => 'failed', 'text' => null]);

        $this->actingAs($user)->post(route('projects.requirement-intake.store', $project), [
            'title' => 'PDF tanpa teks',
            'source_type' => 'prd',
            'priority' => 'must',
            'file' => UploadedFile::fake()->createWithContent('empty.pdf', "%PDF-1.4\nmock"),
        ])->assertSessionHasErrors('summary');

        $this->assertDatabaseMissing('project_requirement_inbox_items', ['project_id' => $project->id]);
    }

    public function test_no_text_requires_summary_but_typed_summary_allows_failed_extraction(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user);
        $extractor = $this->mock(RequirementDocumentTextExtractor::class);
        $extractor->shouldReceive('extract')->twice()->andReturn(['status' => 'no_text', 'text' => null]);
        $file = fn () => UploadedFile::fake()->createWithContent('scan.pdf', "%PDF-1.4\nscan");

        $this->actingAs($user)->post(route('projects.requirement-intake.store', $project), $this->payload(['file' => $file()]))->assertSessionHasErrors('summary');
        $this->actingAs($user)->post(route('projects.requirement-intake.store', $project), $this->payload(['summary' => 'Ringkasan manual', 'file' => $file()]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('project_requirement_inbox_items', ['project_id' => $project->id, 'summary' => 'Ringkasan manual', 'extraction_status' => 'no_text', 'extracted_text' => null]);
    }

    public function test_unsupported_binary_and_oversize_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user);

        foreach ([
            UploadedFile::fake()->create('binary.txt', 2, 'application/octet-stream'),
            UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
        ] as $file) {
            $this->actingAs($user)->post(route('projects.requirement-intake.store', $project), $this->payload(['summary' => 'Manual', 'file' => $file]))->assertSessionHasErrors('file');
        }

        $this->assertDatabaseMissing('project_requirement_inbox_items', ['project_id' => $project->id]);
    }

    public function test_unassigned_and_archived_contributors_cannot_upload(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user, false);
        $payload = $this->payload(['summary' => 'Manual']);

        $this->actingAs($user)->post(route('projects.requirement-intake.store', $project), $payload)->assertRedirect(route('projects.index'));
        $user->update(['archived_at' => now()]);
        $this->actingAs($user)->post(route('projects.requirement-intake.store', $project), $payload)->assertRedirect(route('login'));
    }

    public function test_private_file_access_is_authorized_and_cross_project_route_is_rejected(): void
    {
        Storage::fake('local');
        $owner = $this->contributor();
        $outsider = $this->contributor();
        $project = $this->project($owner);
        $otherProject = $this->project($outsider);
        $item = $this->item($project, $owner, 'project-requirements/'.$project->id.'/doc.txt');
        Storage::disk('local')->put($item->file_path, 'safe');

        $preview = $this->actingAs($owner)->get(route('projects.requirement-intake.preview', [$project, $item]));
        $preview->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'; frame-ancestors 'self'")
            ->assertSee('Preview teks TXT')
            ->assertSee('safe')
            ->assertDontSee('Tampilan ini adalah teks hasil ekstraksi.')
            ->assertDontSee('<script', false);
        $this->actingAs($owner)->get(route('projects.requirement-intake.download', [$project, $item]))
            ->assertDownload('doc.txt')
            ->assertHeader('Content-Disposition', 'attachment; filename=doc.txt');
        $this->actingAs($outsider)->get(route('projects.requirement-intake.preview', [$project, $item]))->assertRedirect(route('projects.index'));
        $this->actingAs($outsider)->get(route('projects.requirement-intake.download', [$otherProject, $item]))->assertNotFound();
    }

    public function test_replacement_deletes_only_old_file_and_validation_failure_preserves_it(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user);
        $old = 'project-requirements/'.$project->id.'/old.txt';
        $unrelated = 'project-requirements/'.$project->id.'/keep.txt';
        Storage::disk('local')->put($old, 'old');
        Storage::disk('local')->put($unrelated, 'keep');
        $item = $this->item($project, $user, $old);

        $this->actingAs($user)->put(route('projects.requirement-intake.update', [$project, $item]), $this->payload(['summary' => 'Manual', 'file' => UploadedFile::fake()->createWithContent('bad.pdf', 'text')]))->assertSessionHasErrors('file');
        Storage::disk('local')->assertExists($old);
        $this->actingAs($user)->put(route('projects.requirement-intake.update', [$project, $item]), $this->payload(['file' => UploadedFile::fake()->createWithContent('new.txt', 'new text')]))->assertSessionHasNoErrors();

        $item->refresh();
        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists($unrelated);
        Storage::disk('local')->assertExists($item->file_path);
    }

    public function test_update_without_replacement_accepts_existing_extracted_nonblank_text(): void
    {
        $user = $this->contributor();
        $project = $this->project($user);
        $item = $this->item($project, $user, 'project-requirements/'.$project->id.'/existing.txt');

        $this->actingAs($user)->put(route('projects.requirement-intake.update', [$project, $item]), $this->payload())->assertSessionHasNoErrors();
    }

    public function test_update_without_replacement_rejects_existing_blank_extracted_text(): void
    {
        $user = $this->contributor();
        $project = $this->project($user);
        $item = $this->item($project, $user, 'project-requirements/'.$project->id.'/blank.txt', ['extracted_text' => " \n\t"]);

        $this->actingAs($user)->put(route('projects.requirement-intake.update', [$project, $item]), $this->payload())->assertSessionHasErrors('summary');
    }

    public function test_failed_database_delete_keeps_file_and_does_not_audit(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user);
        $path = 'project-requirements/'.$project->id.'/delete.txt';
        Storage::disk('local')->put($path, 'delete');
        $item = $this->item($project, $user, $path);
        $auditCount = \App\Models\AuditLog::where('action', 'requirement_intake_deleted')->count();
        $dispatcher = ProjectRequirementInboxItem::getEventDispatcher();
        ProjectRequirementInboxItem::deleting(fn () => false);

        try {
            $this->withoutExceptionHandling()->actingAs($user)->delete(route('projects.requirement-intake.destroy', [$project, $item]));
            $this->fail('RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Gagal menghapus Requirement Intake dari database.', $exception->getMessage());
        } finally {
            ProjectRequirementInboxItem::unsetEventDispatcher();
            ProjectRequirementInboxItem::setEventDispatcher($dispatcher);
        }

        Storage::disk('local')->assertExists($path);
        $this->assertDatabaseHas('project_requirement_inbox_items', ['id' => $item->id]);
        $this->assertSame($auditCount, \App\Models\AuditLog::where('action', 'requirement_intake_deleted')->count());
    }

    public function test_delete_removes_exact_file_and_keeps_unrelated_file(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user);
        $path = 'project-requirements/'.$project->id.'/delete.txt';
        $keep = 'project-requirements/'.$project->id.'/keep.txt';
        Storage::disk('local')->put($path, 'delete');
        Storage::disk('local')->put($keep, 'keep');
        $item = $this->item($project, $user, $path);

        $this->actingAs($user)->delete(route('projects.requirement-intake.destroy', [$project, $item]))->assertSessionHasNoErrors();

        Storage::disk('local')->assertMissing($path);
        Storage::disk('local')->assertExists($keep);
        $this->assertDatabaseMissing('project_requirement_inbox_items', ['id' => $item->id]);
        $audit = \App\Models\AuditLog::where('action', 'requirement_intake_deleted')->latest('id')->firstOrFail();
        $this->assertEqualsCanonicalizing([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'title' => 'Requirement',
            'original_filename' => 'delete.txt',
            'mime_type' => 'text/plain',
            'file_size' => 4,
            'extraction_status' => 'extracted',
        ], $audit->old_values);
        $this->assertArrayNotHasKey('file_path', $audit->old_values);
        $this->assertArrayNotHasKey('extracted_text', $audit->old_values);
    }

    public function test_ai_context_is_deterministic_bounded_and_excludes_paths(): void
    {
        $user = $this->contributor();
        $project = $this->project($user);
        $first = $this->item($project, $user, 'project-requirements/'.$project->id.'/secret.txt', ['title' => 'Reviewed', 'status' => 'reviewed', 'summary' => 'Ringkasan utama', 'extracted_text' => str_repeat('x', 5000)]);
        $second = $this->item($project, $user, null, ['title' => 'Used', 'status' => 'used', 'summary' => 'Prioritas utama']);
        $project->setRelation('requirementInboxItems', collect([$first, $second]));
        $method = new \ReflectionMethod(\App\Http\Controllers\ProjectController::class, 'projectRequirementAiContext');
        $context = $method->invoke(app(\App\Http\Controllers\ProjectController::class), $project);

        $this->assertLessThanOrEqual(16000, mb_strlen($context));
        $this->assertStringContainsString('Ringkasan: Ringkasan utama', $context);
        $this->assertStringContainsString('Ekstrak dokumen:', $context);
        $this->assertStringNotContainsString('project-requirements/', $context);
        $this->assertLessThan(strpos($context, 'Reviewed'), strpos($context, 'Used'));
    }

    public function test_extractor_normalizes_and_caps_txt_deterministically(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'requirement-');
        file_put_contents($path, "A\0\t  B\r\n" . str_repeat('x', RequirementDocumentTextExtractor::MAX_EXTRACTED_CHARACTERS + 10));

        $result = (new RequirementDocumentTextExtractor(new Parser()))->extract($path, 'txt');
        unlink($path);

        $this->assertSame('extracted', $result['status']);
        $this->assertStringStartsWith("A\t B\n", $result['text']);
        $this->assertSame(RequirementDocumentTextExtractor::MAX_EXTRACTED_CHARACTERS, mb_strlen($result['text']));
    }

    public function test_docx_structural_validation_extraction_preview_and_download(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user);
        $valid = $this->docx('valid.docx', '<w:p><w:r><w:t>Hello</w:t><w:tab/><w:t>World</w:t><w:br/><w:t>Next</w:t></w:r></w:p>');

        $this->actingAs($user)->post(route('projects.requirement-intake.store', $project), $this->payload(['file' => $valid]))->assertSessionHasNoErrors();
        $item = ProjectRequirementInboxItem::where('project_id', $project->id)->firstOrFail();
        $this->assertSame("Hello\tWorld\nNext", $item->extracted_text);
        $this->assertSame(RequirementDocumentTextExtractor::DOCX_MIME, $item->mime_type);
        $this->actingAs($user)->get(route('projects.requirement-intake.preview', [$project, $item]))
            ->assertOk()
            ->assertSeeText('Preview teks hasil ekstraksi')
            ->assertSeeText('Tampilan ini adalah teks hasil ekstraksi. Tata letak Word asli, tabel, dan gambar dapat berbeda atau tidak ditampilkan.')
            ->assertSeeText('Hello')
            ->assertSeeText('World')
            ->assertSeeText('Next')
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($user)->get(route('projects.requirement-intake.download', [$project, $item]))
            ->assertDownload('valid.docx')
            ->assertHeader('Content-Disposition', 'attachment; filename=valid.docx');
    }

    public function test_invalid_docx_is_rejected_even_with_summary(): void
    {
        Storage::fake('local');
        $user = $this->contributor();
        $project = $this->project($user);

        foreach ([
            UploadedFile::fake()->createWithContent('fake.docx', 'not a zip'),
            $this->docx('missing.docx', null),
            $this->docx('doctype.docx', '<!DOCTYPE foo><w:p><w:r><w:t>Bad</w:t></w:r></w:p>'),
            $this->docx('large.docx', '<w:p><w:r><w:t>'.str_repeat('x', RequirementDocumentTextExtractor::MAX_DOCX_DOCUMENT_XML_BYTES).'</w:t></w:r></w:p>'),
        ] as $file) {
            $this->actingAs($user)->post(route('projects.requirement-intake.store', $project), $this->payload(['summary' => 'Manual', 'file' => $file]))->assertSessionHasErrors('file');
        }
    }

    public function test_requirement_page_uses_internal_native_preview_without_external_viewer(): void
    {
        $user = $this->contributor();
        $project = $this->project($user);
        $item = $this->item($project, $user, 'project-requirements/'.$project->id.'/prd.pdf', [
            'original_filename' => 'prd.pdf',
            'mime_type' => 'application/pdf',
        ]);
        $previewUrl = route('projects.requirement-intake.preview', [$project, $item]);

        $response = $this->actingAs($user)->get(route('projects.show', $project));
        $source = file_get_contents(resource_path('views/projects/show.blade.php'));
        $previewScript = substr($source, strpos($source, "const requirementPreviewModal"), strpos($source, '/* ===== Requirement Intake edit modal ===== */') - strpos($source, "const requirementPreviewModal"));

        $response->assertOk()
            ->assertSee('data-requirement-preview-trigger', false)
            ->assertSee('data-preview-url=', false)
            ->assertSee('data-preview-title=', false)
            ->assertSee('data-preview-filename=', false)
            ->assertSee('data-preview-extension=', false)
            ->assertSee('data-preview-type=', false)
            ->assertSee('data-preview-kind="pdf"', false)
            ->assertSee('data-preview-size=', false)
            ->assertSee('data-preview-download-url=', false)
            ->assertSee('data-requirement-preview-frame', false)
            ->assertSee('fixed inset-0 z-50 hidden items-center justify-center p-4', false)
            ->assertSee('h-[calc(100dvh-2rem)] max-h-[calc(100dvh-2rem)] w-full max-w-6xl flex-col overflow-hidden', false)
            ->assertSee('flex shrink-0 items-center justify-between', false)
            ->assertSee('data-requirement-preview-frame class="block min-h-0 flex-1 w-full border-0"', false)
            ->assertDontSee('min-h-0 flex-1 overflow-y-auto pb-8', false)
            ->assertDontSee('min-h-[70dvh]', false)
            ->assertSee('window.open(btn.dataset.previewUrl, \'_blank\', \'noopener\')', false)
            ->assertSee('data-preview-url="'.$previewUrl.'"', false)
            ->assertSee('Preview teks TXT')
            ->assertSee('Preview teks hasil ekstraksi')
            ->assertSee('Tampilan ini adalah teks hasil ekstraksi. Tata letak Word asli, tabel, dan gambar dapat berbeda atau tidak ditampilkan.')
            ->assertDontSee('frame.src = btn.dataset.previewUrl', false)
            ->assertDontSee('newTab.location = btn.dataset.previewUrl', false)
            ->assertDontSee('x-data="{ previewOpen', false)
            ->assertDontSee('x-bind:src', false)
            ->assertDontSee('docs.google.com/gview', false)
            ->assertDontSee('view.officeapps.live.com', false);
        $this->assertStringContainsString('document.body.appendChild(requirementPreviewModal)', $previewScript);
        $this->assertStringNotContainsString('fetch(', $previewScript);
        $this->assertStringNotContainsString('response.blob()', $previewScript);
        $this->assertStringNotContainsString('URL.createObjectURL', $previewScript);
        $this->assertStringNotContainsString('URL.revokeObjectURL', $previewScript);
        $this->assertStringNotContainsString('blobUrl', $previewScript);
        $this->assertStringContainsString('frame.src = textPreviewUrl', $previewScript);
    }

    private function docx(string $name, ?string $document): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'docx-');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        if ($document !== null) {
            $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$document.'</w:body></w:document>');
        }
        $zip->close();

        return new UploadedFile($path, $name, 'application/octet-stream', null, true);
    }

    private function contributor(): User
    {
        $role = Role::findOrCreate('sa_qa', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function project(User $user, bool $assigned = true): Project
    {
        $client = Client::create(['name' => 'Intake Client', 'code' => strtoupper(substr(uniqid(), -4)), 'tier' => 'standard']);
        $project = Project::create(['code' => strtoupper(substr(uniqid(), -4)), 'name' => 'Intake Project', 'client_id' => $client->id, 'lead_user_id' => $user->id, 'phase' => 'Discovery', 'due_at' => now()->addWeek()->toDateString(), 'progress' => 0, 'status' => 'on-track']);
        if ($assigned) {
            TeamAssignment::create(['user_id' => $user->id, 'project_id' => $project->id, 'title' => 'QA', 'type' => 'project', 'status' => 'in_progress']);
        }

        return $project;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['title' => 'Requirement', 'source_type' => 'prd', 'priority' => 'must'], $overrides);
    }

    private function item(Project $project, User $user, ?string $path, array $overrides = []): ProjectRequirementInboxItem
    {
        return ProjectRequirementInboxItem::create(array_merge([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => 'Requirement',
            'source_type' => 'prd',
            'priority' => 'must',
            'status' => 'draft',
            'summary' => null,
            'file_path' => $path,
            'original_filename' => $path ? basename($path) : null,
            'mime_type' => $path ? 'text/plain' : null,
            'file_size' => $path ? 4 : null,
            'extraction_status' => $path ? 'extracted' : 'not_applicable',
            'extracted_text' => $path ? 'safe' : null,
            'extracted_at' => $path ? now() : null,
        ], $overrides));
    }
}
