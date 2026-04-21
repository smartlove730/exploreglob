<?php

namespace Tests\Unit\Services;

use App\Models\FacebookApp;
use App\Services\FacebookGraphService;
use App\Services\InstagramService;
use Tests\TestCase;

class FacebookGraphServiceTest extends TestCase
{
    public function test_oauth_redirect_url_contains_required_instagram_and_page_scopes(): void
    {
        $service = new FacebookGraphService($this->createMock(InstagramService::class));
        $app = new FacebookApp([
            'app_id' => '123456789',
            'redirect_uri' => 'https://example.com/auth/facebook/callback',
        ]);

        $url = $service->getOAuthRedirectUrl($app);

        $this->assertStringContainsString('https://www.facebook.com/v19.0/dialog/oauth?', $url);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame(
            'pages_show_list,pages_read_engagement,pages_manage_posts,instagram_basic,instagram_content_publish,instagram_manage_contents',
            $query['scope'] ?? null
        );
        $this->assertSame('rerequest', $query['auth_type'] ?? null);
    }
}
