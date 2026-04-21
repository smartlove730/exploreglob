<?php

namespace Tests\Unit\Services;

use App\Models\FacebookAccount;
use App\Models\FacebookPage;
use App\Services\FacebookGraphService;
use App\Services\GoogleService;
use App\Services\InstagramService;
use App\Services\MetaPostService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MetaPostServiceDeleteInstagramTest extends TestCase
{
    public function test_it_retries_instagram_delete_with_user_token_on_permission_error(): void
    {
        Http::fake([
            'https://graph.facebook.com/*/1789*' => Http::sequence()
                ->push([
                    'error' => [
                        'message' => '(#10) Insufficient permissions to access this data',
                        'code' => 10,
                    ],
                ], 403)
                ->push(['success' => true], 200),
        ]);

        $service = new MetaPostService(
            $this->createMock(FacebookGraphService::class),
            $this->createMock(InstagramService::class),
            $this->createMock(GoogleService::class),
        );

        $account = new FacebookAccount(['long_lived_user_token' => 'user-token']);
        $page = new FacebookPage(['page_access_token' => 'page-token']);
        $page->setRelation('facebookAccount', $account);

        $service->deleteInstagramMedia($page, '1789');

        Http::assertSentCount(2);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/1789')
                && $request['access_token'] === 'page-token';
        });
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/1789')
                && $request['access_token'] === 'user-token';
        });
    }

    public function test_it_throws_when_instagram_delete_fails_without_fallback(): void
    {
        Http::fake([
            'https://graph.facebook.com/*/1789*' => Http::response([
                'error' => [
                    'message' => '(#10) Insufficient permissions to access this data',
                    'code' => 10,
                ],
            ], 403),
        ]);

        $service = new MetaPostService(
            $this->createMock(FacebookGraphService::class),
            $this->createMock(InstagramService::class),
            $this->createMock(GoogleService::class),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to delete Instagram media');

        $service->deleteInstagramMedia(new FacebookPage(['page_access_token' => 'page-token']), '1789');
    }
}
