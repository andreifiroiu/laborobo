<?php

declare(strict_types=1);

use App\Models\Deliverable;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
    Storage::fake('local');
    $this->fileUploadService = new FileUploadService();
});

test('signed URL is generated with default 60-minute expiration', function () {
    $path = '1/projects/123/test-document.pdf';
    Storage::disk('s3')->put($path, 'test content');

    $signedUrl = $this->fileUploadService->getSignedUrl($path, 's3');

    expect($signedUrl)->toBeString()
        ->and($signedUrl)->toContain($path);
});

test('signed URL supports configurable expiration', function () {
    $path = '1/projects/123/test-document.pdf';
    Storage::disk('s3')->put($path, 'test content');

    $signedUrl = $this->fileUploadService->getSignedUrl($path, 's3', 120);

    expect($signedUrl)->toBeString()
        ->and($signedUrl)->toContain($path);
});

test('file uploads to S3 disk via storeDocument method', function () {
    $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

    $path = $this->fileUploadService->storeDocument(
        file: $file,
        teamId: 1,
        context: 'projects',
        entityId: 123,
        disk: 's3'
    );

    expect($path)->toContain('1/projects/123/')
        ->and($path)->toContain('document.pdf');

    Storage::disk('s3')->assertExists($path);
});

test('file path follows pattern: team_id/context/entity_id/filename', function () {
    $file = UploadedFile::fake()->create('report.xlsx', 512, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $path = $this->fileUploadService->storeDocument(
        file: $file,
        teamId: 42,
        context: 'team-files',
        entityId: 999,
        disk: 's3'
    );

    expect($path)->toStartWith('42/team-files/999/')
        ->and($path)->toContain('report.xlsx');
});

test('storeDeliverableVersion accepts configurable disk parameter', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->make(['id' => 789]);

    $file = UploadedFile::fake()->create('deliverable.pdf', 2048, 'application/pdf');

    $path = $this->fileUploadService->storeDeliverableVersion(
        file: $file,
        deliverable: $deliverable,
        versionNumber: 1,
        disk: 's3'
    );

    expect($path)->toBe('deliverables/789/v1_deliverable.pdf');
    Storage::disk('s3')->assertExists('deliverables/789/v1_deliverable.pdf');
});

test('storeDocument falls back to local disk when specified', function () {
    $file = UploadedFile::fake()->create('local-doc.pdf', 512, 'application/pdf');

    $path = $this->fileUploadService->storeDocument(
        file: $file,
        teamId: 5,
        context: 'projects',
        entityId: 50,
        disk: 'local'
    );

    expect($path)->toContain('5/projects/50/')
        ->and($path)->toContain('local-doc.pdf');

    Storage::disk('local')->assertExists($path);
});
