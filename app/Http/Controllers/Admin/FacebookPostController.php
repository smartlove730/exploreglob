<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishPostJob;
use App\Models\FacebookApp;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FacebookPostController extends Controller
{
    public function index()
    {
        $posts = FacebookPost::query()->ownedBy(Auth::user())
            ->with(['page.facebookAccount.app'])
            ->latest()
            ->paginate(20);

        return view('admin.facebook.posts', compact('posts'));
    }

    public function create(Request $request)
    {
        $apps = FacebookApp::query()->ownedBy(Auth::user())->where('is_active', true)->orderBy('name')->get();
        $selectedAppId = (int) $request->integer('app_id');

        if ($selectedAppId === 0 && $apps->isNotEmpty()) {
            $selectedAppId = (int) $apps->first()->id;
        }

        $pages = FacebookPage::query()
            ->where('is_active', true)
            ->whereHas('facebookAccount', function ($query) use ($selectedAppId) {
                $query->where('user_id', Auth::id())
                    ->when($selectedAppId > 0, fn ($inner) => $inner->where('facebook_app_id', $selectedAppId));
            })
            ->orderBy('page_name')
            ->get();

        return view('admin.facebook.create-post', [
            'apps' => $apps,
            'selectedAppId' => $selectedAppId,
            'pages' => $pages,
            'selectedPageId' => (int) old('page_id', $request->integer('page_id')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'app_id' => 'required|integer|exists:facebook_apps,id',
            'page_id' => 'required|integer|exists:facebook_pages,id',
            'message' => 'required|string|max:60000',
            'image_url' => 'nullable|url|max:2048',
            'platforms' => 'required|array|min:1',
            'platforms.*' => 'required|string|in:facebook,instagram',
        ]);

        if (!empty($data['image_url'])) {
            $this->assertPublicHttpsImageUrl($data['image_url']);
        }

        $page = FacebookPage::query()
            ->whereKey($data['page_id'])
            ->where('facebook_app_id', $data['app_id'])
            ->where('is_active', true)
            ->whereHas('facebookAccount', fn ($query) => $query->where('user_id', Auth::id()))
            ->first();

        if (!$page) {
            return back()->withInput()->with('error', 'Selected page is not valid for this app/user.');
        }

        $platforms = collect($data['platforms'])->unique()->values()->all();

        if (in_array('instagram', $platforms, true) && mb_strlen($data['message']) > 2200) {
            throw ValidationException::withMessages([
                'message' => 'Instagram caption must be 2,200 characters or fewer.',
            ]);
        }

        if (in_array('instagram', $platforms, true) && empty($data['image_url'])) {
            throw ValidationException::withMessages([
                'image_url' => 'An HTTPS public image URL is required for Instagram posting.',
            ]);
        }

        $post = FacebookPost::create([
            'user_id' => $page->user_id,
            'page_id' => $page->id,
            'message' => $data['message'],
            'image_url' => $data['image_url'] ?? null,
            'platforms' => $platforms,
            'scheduled_at' => null,
            'status' => FacebookPost::STATUS_PENDING,
        ]);

        PublishPostJob::dispatch($post->id);

        return redirect()->route('admin.facebook.posts')->with('success', 'Post queued for publishing.');
    }

    public function retry(FacebookPost $post): RedirectResponse
    {
        if (!Auth::user()?->isAdmin() && $post->user_id !== Auth::id()) {
            abort(403);
        }

        $post->update([
            'status' => FacebookPost::STATUS_PENDING,
            'last_error' => null,
            'response_json' => null,
        ]);

        PublishPostJob::dispatch($post->id);

        return back()->with('success', 'Post retry queued.');
    }

    public function generateCaption(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $apiKey = config('gemini.api_key') ?? env('AIAPIKEY');

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gemini API key is not configured.',
                ], 500);
            }

            $model = config('gemini.model', 'gemma-3-27b');
            $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $aiPrompt = "Write a social media caption for the following prompt:\n\n{$request->string('prompt')->trim()}\n\nRequirements:\n- Caption should be engaging and human sounding.\n- Add relevant trending hashtags at the end.\n- Keep total length under 2000 characters.\n\nReturn only JSON in this exact schema:\n{\n  \"caption\": \"\"\n}";

            $response = Http::timeout(120)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($apiUrl, [
                    'contents' => [['parts' => [['text' => $aiPrompt]]]],
                ]);

            if (!$response->successful()) {
                Log::error('Caption generation request failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate caption from AI API.',
                ], 500);
            }

            $caption = $this->extractCaptionFromAiResponse($response->json());

            if ($caption === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'AI returned an invalid response format.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Caption generated successfully.',
                'data' => [
                    'caption' => $caption,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Caption generation error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unexpected error while generating caption.',
            ], 500);
        }
    }

    private function extractCaptionFromAiResponse(array $aiResponse): string
    {
        $rawText = $aiResponse['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if ($rawText === '') {
            return '';
        }

        $rawText = trim($rawText);
        $rawText = preg_replace('/^```json\s*/', '', $rawText) ?? $rawText;
        $rawText = preg_replace('/\s*```$/', '', $rawText) ?? $rawText;

        $decoded = json_decode($rawText, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return trim((string) ($decoded['caption'] ?? ''));
        }

        return '';
    }

    private function assertPublicHttpsImageUrl(string $imageUrl): void
    {
        $host = parse_url($imageUrl, PHP_URL_HOST);
        $scheme = parse_url($imageUrl, PHP_URL_SCHEME);

        if ($scheme !== 'https' || !$host) {
            throw ValidationException::withMessages([
                'image_url' => 'Image URL must be publicly accessible and use HTTPS.',
            ]);
        }

        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            throw ValidationException::withMessages([
                'image_url' => 'Image URL must not point to localhost.',
            ]);
        }

        if (filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw ValidationException::withMessages([
                'image_url' => 'Image URL must be publicly reachable.',
            ]);
        }
    }
}
