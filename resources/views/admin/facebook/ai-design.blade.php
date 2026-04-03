@extends('admin.layout')

@section('title', 'AI Instagram Design')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">AI Instagram Design Generator</h1>
    <a class="btn btn-outline-secondary" href="{{ route('admin.posts.create') }}">Back to Create Post</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <p class="text-muted mb-3">Write your prompt, generate an Instagram square design, preview it, then post it as PNG to selected platforms.</p>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Facebook App</label>
                <select id="ai_design_app_id" class="form-select">
                    @foreach($apps as $app)
                        <option value="{{ $app->id }}" {{ $selectedAppId === $app->id ? 'selected' : '' }}>{{ $app->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Pages</label>
                <select id="ai_design_page_ids" class="form-select" multiple>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}" {{ in_array($page->id, $selectedPageIds, true) ? 'selected' : '' }}>{{ $page->page_name }} ({{ $page->page_id }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Use Ctrl/Cmd to select multiple pages.</small>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <label class="form-label">Design Prompt</label>
                <textarea id="ai_design_prompt" class="form-control" rows="5" placeholder="Example: Bold fintech launch post with dark gradient background, neon highlights, KPI cards, and CTA."></textarea>
                <small id="ai_design_generate_status" class="d-block mt-2 text-muted"></small>

                <label class="form-label mt-3">Caption (optional)</label>
                <textarea id="ai_design_caption" class="form-control" rows="4" placeholder="If empty, prompt text will be used as caption."></textarea>

                <div class="mt-3">
                    <label class="form-label d-block">Platforms</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input ai-design-platform" type="checkbox" value="facebook" id="ai_design_platform_facebook" checked>
                        <label class="form-check-label" for="ai_design_platform_facebook">Facebook</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input ai-design-platform" type="checkbox" value="instagram" id="ai_design_platform_instagram">
                        <label class="form-check-label" for="ai_design_platform_instagram">Instagram</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input ai-design-platform" type="checkbox" value="google_business" id="ai_design_platform_google_business">
                        <label class="form-check-label" for="ai_design_platform_google_business">Google Business</label>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <button type="button" id="ai_design_generate_btn" class="btn btn-outline-primary" data-generate-url="{{ route('admin.ai-design.generate') }}">Generate Design</button>
                    <button type="button" id="ai_design_post_btn" class="btn btn-success" data-publish-url="{{ route('admin.ai-design.publish') }}" disabled>Post Generated Content</button>
                </div>
                <small id="ai_design_publish_status" class="d-block mt-2 text-muted"></small>
            </div>
            <div class="col-lg-6">
                <label class="form-label">Generated Preview</label>
                <div class="border rounded bg-light p-3 d-flex justify-content-center align-items-center" style="min-height: 420px;">
                    <div id="ai_design_preview_container" class="w-100 d-flex justify-content-center align-items-center">
                        <div class="text-muted">Generated design preview will appear here.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const appIdInput = document.getElementById('ai_design_app_id');
    const pageIdsInput = document.getElementById('ai_design_page_ids');
    const aiDesignPrompt = document.getElementById('ai_design_prompt');
    const aiDesignCaption = document.getElementById('ai_design_caption');
    const aiDesignGenerateBtn = document.getElementById('ai_design_generate_btn');
    const aiDesignPostBtn = document.getElementById('ai_design_post_btn');
    const aiDesignGenerateStatus = document.getElementById('ai_design_generate_status');
    const aiDesignPublishStatus = document.getElementById('ai_design_publish_status');
    const aiDesignPreview = document.getElementById('ai_design_preview_container');
    const aiDesignShadowRoot = aiDesignPreview?.attachShadow ? aiDesignPreview.attachShadow({ mode: 'open' }) : null;

    const aiDesignState = {
        html: '',
        imageData: '',
    };

    const getSelectedPageIds = () => [...(pageIdsInput?.selectedOptions || [])]
        .map((option) => option.value)
        .filter(Boolean);

    appIdInput?.addEventListener('change', () => {
        const appId = appIdInput.value;
        const url = new URL(window.location.href);
        url.searchParams.set('app_id', appId);
        window.location.href = url.toString();
    });

    const renderAiDesignPreview = (html) => {
        if (!aiDesignPreview) return;

        if (!aiDesignShadowRoot) {
            aiDesignPreview.innerHTML = html || '<div class="text-muted">Generated design preview will appear here.</div>';
            return;
        }

        if (!html) {
            aiDesignShadowRoot.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;min-height:360px;color:#6c757d;">Generated design preview will appear here.</div>';
            return;
        }

        aiDesignShadowRoot.innerHTML = `
            <style>
                :host { display:block; width:100%; }
                .ai-design-surface {
                    width: 100%;
                    max-width: 420px;
                    aspect-ratio: 1 / 1;
                    margin: 0 auto;
                    overflow: auto;
                    background: #fff;
                    border-radius: 10px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                }
            </style>
            <div class="ai-design-surface">${html}</div>
        `;
    };

    aiDesignGenerateBtn?.addEventListener('click', async (event) => {
        event.preventDefault();

        const prompt = aiDesignPrompt?.value.trim() || '';
        if (!prompt) {
            aiDesignGenerateStatus.textContent = 'Please write a design prompt first.';
            aiDesignGenerateStatus.className = 'd-block mt-2 text-danger';
            return;
        }

        aiDesignGenerateBtn.disabled = true;
        aiDesignGenerateStatus.textContent = 'Generating design...';
        aiDesignGenerateStatus.className = 'd-block mt-2 text-muted';
        aiDesignPublishStatus.textContent = '';

        try {
            const response = await fetch(aiDesignGenerateBtn.dataset.generateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ prompt }),
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to generate AI design.');
            }

            aiDesignState.html = result.data.html || '';
            renderAiDesignPreview(aiDesignState.html);
            aiDesignGenerateStatus.textContent = 'Design generated successfully.';
            aiDesignGenerateStatus.className = 'd-block mt-2 text-success';
            aiDesignPostBtn.disabled = false;
        } catch (error) {
            aiDesignState.html = '';
            aiDesignPostBtn.disabled = true;
            renderAiDesignPreview('');
            aiDesignGenerateStatus.textContent = error.message || 'Unexpected error while generating design.';
            aiDesignGenerateStatus.className = 'd-block mt-2 text-danger';
        } finally {
            aiDesignGenerateBtn.disabled = false;
        }
    });

    aiDesignPostBtn?.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (!aiDesignState.html) {
            aiDesignPublishStatus.textContent = 'Please generate a design first.';
            aiDesignPublishStatus.className = 'd-block mt-2 text-danger';
            return;
        }

        const appId = appIdInput?.value;
        const pageIds = getSelectedPageIds();
        const platforms = [...document.querySelectorAll('.ai-design-platform:checked')].map((node) => node.value);
        const prompt = aiDesignPrompt?.value.trim() || '';
        const caption = aiDesignCaption?.value.trim() || '';

        if (!appId || !pageIds.length) {
            aiDesignPublishStatus.textContent = 'Select an app and at least one page first.';
            aiDesignPublishStatus.className = 'd-block mt-2 text-danger';
            return;
        }

        if (!platforms.length) {
            aiDesignPublishStatus.textContent = 'Select at least one platform.';
            aiDesignPublishStatus.className = 'd-block mt-2 text-danger';
            return;
        }

        aiDesignPostBtn.disabled = true;
        aiDesignPublishStatus.textContent = 'Converting design to PNG and posting...';
        aiDesignPublishStatus.className = 'd-block mt-2 text-muted';

        try {
            const previewNode = aiDesignShadowRoot?.querySelector('.ai-design-surface') || aiDesignPreview;
            const canvas = await window.html2canvas(previewNode, {
                backgroundColor: '#ffffff',
                scale: 2,
                useCORS: true,
            });
            aiDesignState.imageData = canvas.toDataURL('image/png');

            const response = await fetch(aiDesignPostBtn.dataset.publishUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    app_id: appId,
                    page_ids: pageIds,
                    platforms,
                    prompt,
                    caption,
                    image_data: aiDesignState.imageData,
                }),
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to publish generated design.');
            }

            aiDesignPublishStatus.textContent = result.message || 'Generated design post queued successfully.';
            aiDesignPublishStatus.className = 'd-block mt-2 text-success';
        } catch (error) {
            aiDesignPublishStatus.textContent = error.message || 'Unexpected error while posting generated design.';
            aiDesignPublishStatus.className = 'd-block mt-2 text-danger';
        } finally {
            aiDesignPostBtn.disabled = false;
        }
    });

    renderAiDesignPreview('');
});
</script>
@endpush
