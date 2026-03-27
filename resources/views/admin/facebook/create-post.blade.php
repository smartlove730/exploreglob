@extends('admin.layout')

@section('title', 'Create Social Post')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Create Social Post</h1>
    <a class="btn btn-outline-secondary" href="{{ route('admin.posts.index') }}">View History</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5">Google Drive Folder Images</h2>
        <p class="text-muted mb-3">Paste a shared Google Drive folder URL, fetch images, then post one or many with generated captions.</p>
        @if($driveApiKeys->isEmpty())
            <div class="alert alert-warning">
                No active Google Drive key found. <a href="{{ route('admin.facebook.google-drive-keys.create') }}">Add a Drive key</a> first.
            </div>
        @endif

        @if(!$googleAccount)
            <div class="alert alert-warning">
                Google Business is not connected. <a href="{{ route('admin.google.settings') }}">Connect Google account</a> to enable Google Business posting.
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
                <label class="form-label">Facebook Page (Active)</label>
                <select name="page_id" id="drive_page_id" class="form-select" required>
                    <option value="">Select a page</option>
                    @foreach($pages as $page)
                        <option value="{{ $page->id }}" {{ $selectedPageId === $page->id ? 'selected' : '' }}>{{ $page->page_name }} ({{ $page->page_id }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Google Drive Key</label>
                <select name="drive_api_key_id" id="drive_api_key_id" class="form-select" required>
                    <option value="">Select Drive key</option>
                    @foreach($driveApiKeys as $driveApiKey)
                        <option value="{{ $driveApiKey->id }}" {{ $selectedDriveApiKeyId === $driveApiKey->id ? 'selected' : '' }}>{{ $driveApiKey->name }}{{ $driveApiKey->email ? ' ('.$driveApiKey->email.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Google Drive Folder URL</label>
                <div class="input-group">
                    <input type="url" name="folder_url" id="drive_folder_url" class="form-control" placeholder="https://drive.google.com/drive/folders/..." required>
                    <button type="button" class="btn btn-primary" id="fetch_drive_images_btn" data-fetch-url="{{ route('admin.posts.drive.images') }}" {{ $driveApiKeys->isEmpty() ? 'disabled' : '' }}>Fetch Images</button>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Or select saved folder</label>
                <div class="input-group">
                    <select class="form-select" id="saved_drive_folder_id">
                        <option value="">Select saved folder</option>
                        @foreach($driveFolders as $driveFolder)
                            <option
                                value="{{ $driveFolder->id }}"
                                data-folder-url="{{ $driveFolder->folder_url }}"
                                data-drive-key-id="{{ $driveFolder->drive_api_key_id }}"
                            >{{ $driveFolder->name }}</option>
                        @endforeach
                    </select>
                    <a href="{{ route('admin.facebook.drive-folders.create') }}" class="btn btn-outline-secondary">Manage Folders</a>
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
                    <label class="form-label">Page</label>
                    <select name="page_id" class="form-select" required>
                        <option value="">Select page</option>
                        @foreach($pages as $page)
                            <option value="{{ $page->id }}" {{ (int) old('page_id', $selectedPageId) === $page->id ? 'selected' : '' }}>{{ $page->page_name }} ({{ $page->page_id }})</option>
                        @endforeach
                    </select>
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
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="platforms[]" value="google_business">
                        <label class="form-check-label">Google Business Profile</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Google Business Location</label>
                    <select name="google_location_id" class="form-select">
                        <option value="">Use default location</option>
                        @foreach($googleLocations as $googleLocation)
                            <option value="{{ $googleLocation->id }}" {{ (int) $defaultGoogleLocationId === $googleLocation->id ? 'selected' : '' }}>{{ $googleLocation->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Required when posting to Google Business Profile.</small>
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
                    <label class="form-label">Upload Video (MP4, max 50MB)</label>
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
                    <div class="form-check form-check-inline">
                        <input class="form-check-input drive-platform" type="checkbox" value="google_business" id="drive_platform_google">
                        <label class="form-check-label" for="drive_platform_google">Google Business</label>
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
    const fetchBtn = document.getElementById('fetch_drive_images_btn');
    const statusNode = document.getElementById('drive_status');
    const gridNode = document.getElementById('drive_images_grid');
    const postSelectedContainer = document.getElementById('post_selected_container');
    const selectedCountText = document.getElementById('selected_count_text');
    const postSelectedBtn = document.getElementById('post_selected_btn');
    const modalEl = document.getElementById('drivePostModal');
    const modal = new bootstrap.Modal(modalEl);

    const appIdInput = document.getElementById('drive_app_id');
    const pageIdInput = document.getElementById('drive_page_id');
    const driveApiKeyInput = document.getElementById('drive_api_key_id');
    const folderUrlInput = document.getElementById('drive_folder_url');
    const savedFolderInput = document.getElementById('saved_drive_folder_id');

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
                    <img src="${image.preview_url}" class="card-img-top" style="height: 180px; object-fit: cover;" alt="${image.name}">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="form-check">
                                <input class="form-check-input drive-image-select" type="checkbox" value="${image.id}" id="drive_img_${image.id}" ${isChecked ? 'checked' : ''}>
                                <label class="form-check-label small" for="drive_img_${image.id}">Select</label>
                            </div>
                            ${postedBadge}
                        </div>
                        <div class="small text-muted text-truncate mb-2" title="${image.name}">${image.name}</div>
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
        modalTitle.textContent = images.length > 1 ? `Post ${images.length} Selected Images` : 'Post Image';
        modalCaption.value = '';
        modalPrompt.value = '';
        setModalStatus('');

        modalPreview.innerHTML = images.map((img) => `
            <div class="col-md-3 col-6">
                <div class="border rounded p-1 h-100">
                    <img src="${img.preview_url}" class="img-fluid rounded" alt="${img.name}" style="width: 100%; height: 120px; object-fit: cover;">
                    <div class="small text-muted text-truncate mt-1" title="${img.name}">${img.name}</div>
                </div>
            </div>
        `).join('');

        modal.show();
    };

    fetchBtn?.addEventListener('click', async () => {
        const appId = appIdInput.value;
        const pageId = pageIdInput.value;
        const driveApiKeyId = driveApiKeyInput.value;
        const folderUrl = folderUrlInput.value.trim();

        if (!appId || !pageId || !driveApiKeyId || !folderUrl) {
            setStatus('App, page, Drive key, and folder URL are required.', 'danger');
            return;
        }

        setStatus('Fetching images from Google Drive...', 'muted');
        fetchBtn.disabled = true;

        try {
            const response = await fetch(fetchBtn.dataset.fetchUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ app_id: appId, page_id: pageId, drive_api_key_id: driveApiKeyId, folder_url: folderUrl }),
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch images.');
            }

            state.folderId = result.data.folder_id;
            state.images = result.data.images || [];
            state.selectedIds.clear();

            renderGrid();
            setStatus(`Loaded ${state.images.length} image(s).`, 'success');
        } catch (error) {
            state.images = [];
            state.selectedIds.clear();
            renderGrid();
            setStatus(error.message || 'Unexpected error while fetching images.', 'danger');
        } finally {
            fetchBtn.disabled = false;
        }
    });

    savedFolderInput?.addEventListener('change', () => {
        const selected = savedFolderInput.options[savedFolderInput.selectedIndex];
        if (!selected || !selected.value) return;

        if (selected.dataset.folderUrl) {
            folderUrlInput.value = selected.dataset.folderUrl;
        }

        if (selected.dataset.driveKeyId) {
            driveApiKeyInput.value = selected.dataset.driveKeyId;
        }
    });

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

    postSelectedBtn?.addEventListener('click', () => {
        const selectedImages = [...state.selectedIds]
            .map((id) => getImageById(id))
            .filter(Boolean);

        if (!selectedImages.length) {
            setStatus('Select at least one image first.', 'danger');
            return;
        }

        openPostModal(selectedImages);
    });

    modalPostBtn?.addEventListener('click', async () => {
        const appId = appIdInput.value;
        const pageId = pageIdInput.value;
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
            setModalStatus('No images selected.', 'danger');
            return;
        }

        const payload = {
            app_id: appId,
            page_id: pageId,
            folder_id: state.folderId,
            caption,
            drive_api_key_id: driveApiKeyInput.value,
            post_mode: postMode,
            platforms,
            images: state.modalImages.map((img) => ({ id: img.id, url: img.download_url, resource_key: img.resource_key || '' })),
        };

        modalPostBtn.disabled = true;
        setModalStatus('Posting images...', 'muted');

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
                throw new Error(result.message || 'Failed to publish image(s).');
            }

            const postedIds = new Set((result.data.results || []).filter((row) => row.success).map((row) => row.file_id));

            state.images = state.images.map((image) => {
                if (!postedIds.has(image.id)) {
                    return image;
                }

                return {
                    ...image,
                    is_posted: true,
                    posted_platforms: [...new Set([...(image.posted_platforms || []), ...platforms])],
                };
            });

            state.selectedIds.clear();
            renderGrid();
            setStatus(result.message || 'Images posted successfully.', 'success');
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
