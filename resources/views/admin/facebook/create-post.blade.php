@extends('admin.layout')

@section('title', 'Create Social Post')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Create Social Post</h1>
    <a class="btn btn-outline-secondary" href="{{ route('admin.posts.index') }}">View History</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5">Google Drive Folder Media</h2>
        <p class="text-muted mb-3">Paste a shared Google Drive folder URL, fetch images/videos, preview them, then post one or many with generated captions.</p>
        @if($driveApiKeys->isEmpty())
            <div class="alert alert-warning">
                No connected Google account found. <a href="{{ route('admin.google-drive.connect') }}">Connect via OAuth</a> (recommended) or <a href="{{ route('admin.facebook.google-drive-keys.create') }}">add an API key manually</a>.
            </div>
        @endif

        <form id="driveFilterForm" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Facebook App</label>
                <select name="app_id" id="drive_app_id" class="form-select" required>
                    <option value="">Select an app</option>
                    @foreach($apps as $app)
                        <option value="{{ $app->id }}" {{ $selectedAppId === $app->id ? 'selected' : '' }}>{{ $app->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Facebook Pages (Active)</label>
                <select name="page_ids[]" id="drive_page_ids" class="form-select" multiple required>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}" {{ in_array($page->id, $selectedPageIds, true) ? 'selected' : '' }}>{{ $page->page_name }} ({{ $page->page_id }})</option>
                    @endforeach
                </select>
                <small class="text-muted">Use Ctrl/Cmd to select multiple pages.</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Google Drive Account</label>
                <select name="drive_api_key_id" id="drive_api_key_id" class="form-select">
                    <option value="">Auto (use connected OAuth account)</option>
                    @foreach($driveApiKeys as $driveApiKey)
                        <option value="{{ $driveApiKey->id }}" {{ $selectedDriveApiKeyId === $driveApiKey->id ? 'selected' : '' }}>{{ $driveApiKey->name }}{{ $driveApiKey->email ? ' ('.$driveApiKey->email.')' : '' }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Optional. Leave blank to auto-use your latest active OAuth Drive connection.</small>
            </div>
            <div class="col-12">
                <label class="form-label">Select Folder</label>
                <div>
                    <select class="form-select" id="saved_drive_folder_id">
                        <option value="">Select folder</option>
                        @foreach($driveFolders as $driveFolder)
                            @if($driveFolder->is_active)
                                <option
                                    value="{{ $driveFolder->id }}"
                                    data-folder-url="{{ $driveFolder->folder_url }}"
                                    data-drive-key-id="{{ $driveFolder->drive_api_key_id }}"
                                    data-is-active="1"
                                >{{ $driveFolder->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <div class="d-none mt-3" id="post_selected_container">
            <button type="button" class="btn btn-success" id="post_selected_btn">Post Selected</button>
            <small class="text-muted ms-2" id="selected_count_text">0 selected</small>
        </div>

        <div id="drive_status" class="small mt-3"></div>
        <div id="drive_images_grid" class="row g-3 mt-1"></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5">Manual Post (existing flow)</h2>
        @if($pages->isEmpty())
            <div class="alert alert-warning mb-0">No active pages found for selected app. Connect/sync pages in Facebook Settings.</div>
        @else
            <form method="POST" action="{{ route('admin.posts.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="app_id" value="{{ $selectedAppId }}">
                <div class="mb-3">
                    <label class="form-label">Pages</label>
                    <select name="page_ids[]" class="form-select" multiple required>
                        @foreach($pages as $page)
                            <option value="{{ $page->id }}" {{ in_array($page->id, $selectedPageIds, true) ? 'selected' : '' }}>{{ $page->page_name }} ({{ $page->page_id }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Use Ctrl/Cmd to select multiple pages.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label d-block">Platforms</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="platforms[]" value="facebook" checked>
                        <label class="form-check-label">Facebook</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="platforms[]" value="instagram">
                        <label class="form-check-label">Instagram</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Prompt for AI Caption</label>
                    <div class="input-group">
                        <input type="text" name="prompt" id="ai_prompt" class="form-control" value="{{ old('prompt') }}">
                        <button type="button" class="btn btn-outline-primary generate-caption-btn" data-target-caption="#message" data-target-prompt="#ai_prompt" data-status="#generate_caption_status" data-generate-url="{{ route('admin.facebook.posts.generate-caption') }}">Generate Caption</button>
                    </div>
                    <small id="generate_caption_status" class="text-muted d-block mt-1"></small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message / Caption</label>
                    <textarea name="message" id="message" rows="6" class="form-control" required>{{ old('message') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label d-block">Media Type</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input js-media-type" type="radio" name="media_type" value="image" id="media_type_image" {{ old('media_type', 'image') === 'image' ? 'checked' : '' }}>
                        <label class="form-check-label" for="media_type_image">Image</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input js-media-type" type="radio" name="media_type" value="video" id="media_type_video" {{ old('media_type') === 'video' ? 'checked' : '' }}>
                        <label class="form-check-label" for="media_type_video">Video</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image URL (optional)</label>
                    <input type="url" name="image_url" class="form-control" value="{{ old('image_url') }}">
                </div>
                <div class="mb-3" id="image_upload_group">
                    <label class="form-label">Upload Images</label>
                    <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                </div>
                <div class="mb-3 d-none" id="video_upload_group">
                    <label class="form-label">Upload Video (MP4)</label>
                    <input type="file" name="video" class="form-control" accept="video/mp4">
                </div>
                <button class="btn btn-primary">Publish Post</button>
            </form>
        @endif
    </div>
</div>

<div class="modal fade" id="drivePostModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="drivePostModalTitle">Post Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="drive_modal_preview" class="row g-2 mb-3"></div>
                <div class="mb-3">
                    <label class="form-label">Prompt</label>
                    <div class="input-group">
                        <input type="text" id="drive_modal_prompt" class="form-control" placeholder="Write prompt for caption generation">
                        <button type="button" class="btn btn-outline-primary generate-caption-btn" data-target-caption="#drive_modal_caption" data-target-prompt="#drive_modal_prompt" data-status="#drive_modal_caption_status" data-generate-url="{{ route('admin.facebook.posts.generate-caption') }}">Generate Caption</button>
                    </div>
                    <small id="drive_modal_caption_status" class="text-muted d-block mt-1"></small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Caption</label>
                    <textarea id="drive_modal_caption" class="form-control" rows="4"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label d-block">Platforms</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input drive-platform" type="checkbox" value="facebook" id="drive_platform_facebook" checked>
                        <label class="form-check-label" for="drive_platform_facebook">Facebook</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input drive-platform" type="checkbox" value="instagram" id="drive_platform_instagram">
                        <label class="form-check-label" for="drive_platform_instagram">Instagram</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label d-block">Post Mode</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="drive_post_mode" id="drive_post_mode_separate" value="separate" checked>
                        <label class="form-check-label" for="drive_post_mode_separate">
                            Post each selected image separately
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="drive_post_mode" id="drive_post_mode_combined" value="combined">
                        <label class="form-check-label" for="drive_post_mode_combined">
                            Post all selected images in one post (Facebook album / Instagram carousel)
                        </label>
                    </div>
                </div>
                <div class="small" id="drive_modal_status"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="drive_modal_post_btn" data-publish-url="{{ route('admin.posts.drive.publish') }}">Post</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const mediaTypeInputs = Array.from(document.querySelectorAll('.js-media-type'));
    const imageUploadGroup = document.getElementById('image_upload_group');
    const videoUploadGroup = document.getElementById('video_upload_group');

    const toggleMediaInputs = () => {
        const mediaType = mediaTypeInputs.find((input) => input.checked)?.value || 'image';
        const isVideo = mediaType === 'video';

        imageUploadGroup?.classList.toggle('d-none', isVideo);
        videoUploadGroup?.classList.toggle('d-none', !isVideo);
    };

    mediaTypeInputs.forEach((input) => input.addEventListener('change', toggleMediaInputs));
    toggleMediaInputs();

    const state = {
        folderId: null,
        images: [],
        selectedIds: new Set(),
        modalImages: [],
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const statusNode = document.getElementById('drive_status');
    const gridNode = document.getElementById('drive_images_grid');
    const postSelectedContainer = document.getElementById('post_selected_container');
    const selectedCountText = document.getElementById('selected_count_text');
    const postSelectedBtn = document.getElementById('post_selected_btn');
    const modalEl = document.getElementById('drivePostModal');
    const modal = new bootstrap.Modal(modalEl);

    const appIdInput = document.getElementById('drive_app_id');
    const pageIdsInput = document.getElementById('drive_page_ids');
    const driveApiKeyInput = document.getElementById('drive_api_key_id');
    const savedFolderInput = document.getElementById('saved_drive_folder_id');
    const initialFolderOptions = [...savedFolderInput.options].slice(1).map((option) => ({
        value: option.value,
        text: option.textContent,
        folderUrl: option.dataset.folderUrl,
        driveKeyId: option.dataset.driveKeyId,
        isActive: option.dataset.isActive === '1',
    }));
    const getSelectedPageIds = () => [...(pageIdsInput?.selectedOptions || [])]
        .map((option) => option.value)
        .filter(Boolean);

    const modalTitle = document.getElementById('drivePostModalTitle');
    const modalPreview = document.getElementById('drive_modal_preview');
    const modalCaption = document.getElementById('drive_modal_caption');
    const modalPrompt = document.getElementById('drive_modal_prompt');
    const modalStatus = document.getElementById('drive_modal_status');
    const modalPostBtn = document.getElementById('drive_modal_post_btn');

    const setStatus = (message, type = 'muted') => {
        statusNode.className = `small mt-3 text-${type}`;
        statusNode.textContent = message;
    };

    const setModalStatus = (message, type = 'muted') => {
        modalStatus.className = `small text-${type}`;
        modalStatus.textContent = message;
    };

    const getImageById = (id) => state.images.find((img) => img.id === id);
    const renderPreviewMedia = (media, className = '', style = '') => {
        if ((media.type || 'image') === 'video') {
            return `<video class="${className}" style="${style}" controls preload="metadata"><source src="${media.download_url}" type="${media.mime_type || 'video/mp4'}"></video>`;
        }

        return `<img
            src="${media.preview_url}"
            data-fallback-url="${media.download_url}"
            class="${className} drive-preview-image"
            loading="lazy"
            referrerpolicy="no-referrer"
            style="${style}"
            alt="${media.name}"
        >`;
    };

    const renderGrid = () => {
        gridNode.innerHTML = '';

        if (!state.images.length) {
            return;
        }

        state.images.forEach((image) => {
            const isChecked = state.selectedIds.has(image.id);
            const postedBadge = image.is_posted
                ? `<span class="badge text-bg-success">Posted (${(image.posted_platforms || []).join(', ')})</span>`
                : '<span class="badge text-bg-secondary">Not posted</span>';

            const col = document.createElement('div');
            col.className = 'col-md-3';
            col.innerHTML = `
                <div class="card h-100">
                    ${renderPreviewMedia(image, 'card-img-top', 'height: 180px; object-fit: cover;')}
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="form-check">
                                <input class="form-check-input drive-image-select" type="checkbox" value="${image.id}" id="drive_img_${image.id}" ${isChecked ? 'checked' : ''}>
                                <label class="form-check-label small" for="drive_img_${image.id}">Select</label>
                            </div>
                            ${postedBadge}
                        </div>
                        <div class="small text-muted text-truncate mb-1" title="${image.name}">${image.name}</div>
                        <div class="small text-muted mb-2">${(image.type || 'image').toUpperCase()}</div>
                        <button type="button" class="btn btn-primary btn-sm w-100 drive-single-post-btn" data-id="${image.id}">Post</button>
                    </div>
                </div>
            `;
            gridNode.appendChild(col);
        });

        updateSelectedUi();
    };

    const updateSelectedUi = () => {
        const count = state.selectedIds.size;
        selectedCountText.textContent = `${count} selected`;
        postSelectedContainer.classList.toggle('d-none', count === 0);
    };

    const openPostModal = (images) => {
        state.modalImages = images;
        modalTitle.textContent = images.length > 1 ? `Post ${images.length} Selected Media` : 'Post Media';
        modalCaption.value = '';
        modalPrompt.value = '';
        setModalStatus('');

        modalPreview.innerHTML = images.map((img) => `
            <div class="col-md-3 col-6">
                <div class="border rounded p-1 h-100">
                    ${renderPreviewMedia(img, 'img-fluid rounded', 'width: 100%; height: 120px; object-fit: cover;')}
                    <div class="small text-muted text-truncate mt-1" title="${img.name}">${img.name}</div>
                </div>
            </div>
        `).join('');

        modal.show();
    };

    const fetchMedia = async () => {
        const appId = appIdInput.value;
        const pageIds = getSelectedPageIds();
        const driveApiKeyId = driveApiKeyInput.value;
        const selected = savedFolderInput.options[savedFolderInput.selectedIndex];
        const folderUrl = selected?.dataset?.folderUrl?.trim() || '';

        if (!appId || !pageIds.length || !folderUrl) {
            setStatus('App, at least one page, and folder are required.', 'danger');
            return;
        }

        setStatus('Fetching media from Google Drive...', 'muted');
        try {
            const response = await fetch('{{ route('admin.posts.drive.images') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ app_id: appId, page_ids: pageIds, drive_api_key_id: driveApiKeyId || null, folder_url: folderUrl }),
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch media.');
            }

            state.folderId = result.data.folder_id;
            state.images = result.data.media || result.data.images || [];
            state.selectedIds.clear();

            renderGrid();
            setStatus(`Loaded ${state.images.length} media file(s).`, 'success');
        } catch (error) {
            state.images = [];
            state.selectedIds.clear();
            renderGrid();
            setStatus(error.message || 'Unexpected error while fetching media.', 'danger');
        }
    };

    const rebuildFolderOptionsByAccount = () => {
        const selectedAccountId = driveApiKeyInput.value;
        const filtered = initialFolderOptions.filter((folder) => folder.isActive && (!selectedAccountId || String(folder.driveKeyId) === String(selectedAccountId)));

        savedFolderInput.innerHTML = '<option value="">Select folder</option>';
        filtered.forEach((folder) => {
            const option = document.createElement('option');
            option.value = folder.value;
            option.textContent = folder.text;
            option.dataset.folderUrl = folder.folderUrl;
            option.dataset.driveKeyId = folder.driveKeyId;
            option.dataset.isActive = '1';
            savedFolderInput.appendChild(option);
        });
    };

    driveApiKeyInput?.addEventListener('change', () => {
        rebuildFolderOptionsByAccount();
        state.images = [];
        state.selectedIds.clear();
        renderGrid();
        setStatus('', 'muted');
    });

    savedFolderInput?.addEventListener('change', async () => {
        const selected = savedFolderInput.options[savedFolderInput.selectedIndex];
        if (!selected || !selected.value) return;

        if (selected.dataset.driveKeyId) {
            driveApiKeyInput.value = selected.dataset.driveKeyId;
        }

        await fetchMedia();
    });

    rebuildFolderOptionsByAccount();

    gridNode?.addEventListener('change', (event) => {
        const checkbox = event.target.closest('.drive-image-select');
        if (!checkbox) return;

        if (checkbox.checked) {
            state.selectedIds.add(checkbox.value);
        } else {
            state.selectedIds.delete(checkbox.value);
        }

        updateSelectedUi();
    });

    gridNode?.addEventListener('click', (event) => {
        const postBtn = event.target.closest('.drive-single-post-btn');
        if (!postBtn) return;

        const image = getImageById(postBtn.dataset.id);
        if (!image) return;

        openPostModal([image]);
    });

    document.addEventListener('error', (event) => {
        const imageNode = event.target.closest?.('img.drive-preview-image');
        if (!imageNode) return;

        if (imageNode.dataset.fallbackApplied === '1') {
            return;
        }

        const fallbackUrl = imageNode.dataset.fallbackUrl;
        if (!fallbackUrl || imageNode.src === fallbackUrl) {
            return;
        }

        imageNode.dataset.fallbackApplied = '1';
        imageNode.src = fallbackUrl;
    }, true);

    postSelectedBtn?.addEventListener('click', () => {
        const selectedImages = [...state.selectedIds]
            .map((id) => getImageById(id))
            .filter(Boolean);

        if (!selectedImages.length) {
            setStatus('Select at least one media file first.', 'danger');
            return;
        }

        openPostModal(selectedImages);
    });

    modalPostBtn?.addEventListener('click', async () => {
        const appId = appIdInput.value;
        const pageIds = getSelectedPageIds();
        const caption = modalCaption.value.trim();
        const platforms = [...document.querySelectorAll('.drive-platform:checked')].map((node) => node.value);
        const postMode = document.querySelector('input[name="drive_post_mode"]:checked')?.value || 'separate';

        if (!caption) {
            setModalStatus('Caption is required.', 'danger');
            return;
        }

        if (!platforms.length) {
            setModalStatus('Select at least one platform.', 'danger');
            return;
        }

        if (!state.modalImages.length) {
            setModalStatus('No media selected.', 'danger');
            return;
        }

        if (!pageIds.length) {
            setModalStatus('Select at least one page.', 'danger');
            return;
        }

        const payload = {
            app_id: appId,
            page_ids: pageIds,
            folder_id: state.folderId,
            caption,
            drive_api_key_id: driveApiKeyInput.value || null,
            post_mode: postMode,
            platforms,
            images: state.modalImages.map((img) => ({ id: img.id, url: img.download_url, resource_key: img.resource_key || '', mime_type: img.mime_type || '' })),
        };

        modalPostBtn.disabled = true;
        setModalStatus('Posting media...', 'muted');

        try {
            const response = await fetch(modalPostBtn.dataset.publishUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to publish media.');
            }

            const successfulRows = (result.data.results || []).filter((row) => row.success);
            const skippedRows = (result.data.results || []).filter((row) => row.skipped);
            const postedIds = new Set(successfulRows.map((row) => row.file_id));
            const postedPlatformsById = new Map(successfulRows.map((row) => [row.file_id, row.platforms || platforms]));

            state.images = state.images.map((image) => {
                if (!postedIds.has(image.id)) {
                    return image;
                }

                const newlyPostedPlatforms = postedPlatformsById.get(image.id) || platforms;
                return {
                    ...image,
                    is_posted: true,
                    posted_platforms: [...new Set([...(image.posted_platforms || []), ...newlyPostedPlatforms])],
                };
            });

            state.selectedIds.clear();
            renderGrid();
            if (skippedRows.length > 0) {
                setStatus((result.message || 'Posting finished.') + ` ${skippedRows.length} media skipped because already published.`, 'warning');
            } else {
                setStatus(result.message || 'Media posted successfully.', 'success');
            }
            modal.hide();
        } catch (error) {
            setModalStatus(error.message || 'Unexpected error while posting images.', 'danger');
        } finally {
            modalPostBtn.disabled = false;
        }
    });

    document.querySelectorAll('.generate-caption-btn').forEach((button) => {
        button.addEventListener('click', async () => {
            const promptInput = document.querySelector(button.dataset.targetPrompt);
            const captionInput = document.querySelector(button.dataset.targetCaption);
            const localStatus = document.querySelector(button.dataset.status);

            if (!promptInput || !captionInput || !localStatus) return;

            const prompt = promptInput.value.trim();
            if (!prompt) {
                localStatus.textContent = 'Please enter a prompt first.';
                localStatus.className = 'text-danger d-block mt-1';
                return;
            }

            button.disabled = true;
            localStatus.textContent = 'Generating caption...';
            localStatus.className = 'text-muted d-block mt-1';

            try {
                const response = await fetch(button.dataset.generateUrl, {
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
                if (!response.ok || !result.success) throw new Error(result.message || 'Failed to generate caption.');

                captionInput.value = result.data.caption || '';
                localStatus.textContent = 'Caption generated successfully.';
                localStatus.className = 'text-success d-block mt-1';
            } catch (error) {
                localStatus.textContent = error.message || 'Unexpected error while generating caption.';
                localStatus.className = 'text-danger d-block mt-1';
            } finally {
                button.disabled = false;
            }
        });
    });

    document.querySelectorAll('.generate-caption-btn').forEach((button) => {
        button.addEventListener('click', async () => {
            const promptInput = document.querySelector(button.dataset.targetPrompt);
            const captionInput = document.querySelector(button.dataset.targetCaption);
            const localStatus = document.querySelector(button.dataset.status);

            if (!promptInput || !captionInput || !localStatus) return;

            const prompt = promptInput.value.trim();
            if (!prompt) {
                localStatus.textContent = 'Please enter a prompt first.';
                localStatus.className = 'text-danger d-block mt-1';
                return;
            }

            button.disabled = true;
            localStatus.textContent = 'Generating caption...';
            localStatus.className = 'text-muted d-block mt-1';

            try {
                const response = await fetch(button.dataset.generateUrl, {
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
                if (!response.ok || !result.success) throw new Error(result.message || 'Failed to generate caption.');

                captionInput.value = result.data.caption || '';
                localStatus.textContent = 'Caption generated successfully.';
                localStatus.className = 'text-success d-block mt-1';
            } catch (error) {
                localStatus.textContent = error.message || 'Unexpected error while generating caption.';
                localStatus.className = 'text-danger d-block mt-1';
            } finally {
                button.disabled = false;
            }
        });
    });
});
</script>
@endpush
