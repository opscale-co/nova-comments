<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Opscale\NovaComments\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Workbench\App\Models\User;

class AttachmentUploadTest extends TestCase
{
    #[Test]
    public function it_stores_an_uploaded_attachment_and_returns_its_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/nova-vendor/nova-comments/attachments', [
                'attachment' => UploadedFile::fake()->image('diagram.png'),
            ]);

        $response->assertSuccessful();
        $url = $response->json('url');

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('nova-comments/attachments', (string) $url);

        // Exactly one file was persisted on the configured disk.
        $this->assertCount(1, Storage::disk('public')->allFiles('nova-comments/attachments'));
    }

    #[Test]
    public function it_rejects_a_disallowed_file_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/nova-vendor/nova-comments/attachments', [
                'attachment' => UploadedFile::fake()->create('malware.exe', 10),
            ]);

        $response->assertStatus(422);
        $this->assertCount(0, Storage::disk('public')->allFiles('nova-comments/attachments'));
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/nova-vendor/nova-comments/attachments', [
            'attachment' => UploadedFile::fake()->image('diagram.png'),
        ]);

        $response->assertForbidden();
        $this->assertCount(0, Storage::disk('public')->allFiles('nova-comments/attachments'));
    }

    #[Test]
    public function it_deletes_an_attachment_by_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $stored = $this->actingAs($user)
            ->postJson('/nova-vendor/nova-comments/attachments', [
                'attachment' => UploadedFile::fake()->image('diagram.png'),
            ])->json('url');

        $this->assertCount(1, Storage::disk('public')->allFiles('nova-comments/attachments'));

        $response = $this->actingAs($user)
            ->deleteJson('/nova-vendor/nova-comments/attachments', ['url' => $stored]);

        $response->assertSuccessful();
        $this->assertCount(0, Storage::disk('public')->allFiles('nova-comments/attachments'));
    }
}
