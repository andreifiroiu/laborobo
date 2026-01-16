<?php

declare(strict_types=1);

use App\Models\Deliverable;
use App\Models\Party;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->service = new FileUploadService();

    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
    ]);
    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
    ]);
    $this->deliverable = Deliverable::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $this->workOrder->id,
        'project_id' => $this->project->id,
    ]);
});

test('validates file size accepts files up to 50MB', function () {
    $file = UploadedFile::fake()->create('document.pdf', 50 * 1024); // 50MB in KB

    $errors = $this->service->validateFile($file);

    expect($errors)->toBeEmpty();
});

test('validates file size rejects files larger than 50MB', function () {
    $file = UploadedFile::fake()->create('large-document.pdf', 51 * 1024); // 51MB in KB

    $errors = $this->service->validateFile($file);

    expect($errors)->not->toBeEmpty();
    expect($errors)->toContain('The file size exceeds the maximum allowed size of 50MB.');
});

test('validates blocked extensions rejects dangerous files', function () {
    $blockedExtensions = ['exe', 'bat', 'sh', 'cmd', 'ps1', 'jar'];

    foreach ($blockedExtensions as $ext) {
        $file = UploadedFile::fake()->create("malicious.{$ext}", 100);

        $errors = $this->service->validateFile($file);

        expect($errors)->not->toBeEmpty("Extension .{$ext} should be blocked");
        expect($errors[0])->toContain('not allowed');
    }
});

test('stores deliverable version file and returns correct path', function () {
    $file = UploadedFile::fake()->create('report.pdf', 1024);

    $fileUrl = $this->service->storeDeliverableVersion($file, $this->deliverable, 1);

    expect($fileUrl)->toContain("deliverables/{$this->deliverable->id}/v1_report.pdf");
    Storage::disk('public')->assertExists("deliverables/{$this->deliverable->id}/v1_report.pdf");
});

test('detects MIME type correctly for uploaded files', function () {
    $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
    $imageFile = UploadedFile::fake()->create('image.png', 100, 'image/png');

    expect($pdfFile->getMimeType())->toBe('application/pdf');
    expect($imageFile->getMimeType())->toBe('image/png');
});

test('formats file size correctly', function () {
    expect($this->service->formatFileSize(500))->toBe('500 B');
    expect($this->service->formatFileSize(1024))->toBe('1 KB');
    expect($this->service->formatFileSize(1536))->toBe('1.5 KB');
    expect($this->service->formatFileSize(1048576))->toBe('1 MB');
    expect($this->service->formatFileSize(52428800))->toBe('50 MB');
    expect($this->service->formatFileSize(1073741824))->toBe('1 GB');
});
