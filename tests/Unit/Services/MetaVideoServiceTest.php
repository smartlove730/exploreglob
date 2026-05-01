<?php

namespace Tests\Unit\Services;

use App\Models\FacebookPage;
use App\Services\InstagramService;
use App\Services\MetaVideoService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MetaVideoServiceTest extends TestCase
{
    public function test_it_accepts_non_mp4_extension_when_content_type_is_mp4(): void
    {
        Http::fake([
            'https://cdn.example.com/*' => Http::sequence()
                ->push('', 200, ['Content-Type' => 'video/mp4'])
                ->push(['id' => 'video_123'], 200),
            'https://graph.facebook.com/*' => Http::response(['id' => 'video_123'], 200),
        ]);

        $service = new MetaVideoService($this->createMock(InstagramService::class));
        $page = new FacebookPage([
            'page_id' => '12345',
            'page_access_token' => 'test-token',
        ]);

        $response = $service->postToFacebookVideo($page, 'https://cdn.example.com/media/file', 'Caption');

        $this->assertSame(['id' => 'video_123'], $response);
        Http::assertSentCount(2);
    }

    public function test_it_rejects_non_mp4_urls_when_content_type_is_not_mp4(): void
    {
        Http::fake([
            'https://cdn.example.com/*' => Http::response('', 200, ['Content-Type' => 'video/webm']),
        ]);

        $service = new MetaVideoService($this->createMock(InstagramService::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Video URL must point to an MP4 file.');

        $service->postToFacebookVideo(
            new FacebookPage(['page_id' => '12345', 'page_access_token' => 'test-token']),
            'https://cdn.example.com/media/file',
            'Caption'
        );
    }
}
