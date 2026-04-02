@php($editingPlan = $plan ?? null)
<div class="col-md-3">
    <input class="form-control form-control-sm" name="name" placeholder="Name" value="{{ old('name', $editingPlan->name ?? '') }}" required>
</div>
<div class="col-md-2">
    <input class="form-control form-control-sm" name="slug" placeholder="slug" value="{{ old('slug', $editingPlan->slug ?? '') }}" required>
</div>
<div class="col-md-2">
    <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="price" placeholder="Price" value="{{ old('price', $editingPlan->price ?? '') }}" required>
</div>
<div class="col-md-1">
    <input class="form-control form-control-sm" name="currency" maxlength="3" placeholder="INR" value="{{ old('currency', $editingPlan->currency ?? 'INR') }}" required>
</div>
<div class="col-md-2">
    <select class="form-select form-select-sm" name="interval" required>
        @foreach(['daily','weekly','monthly','yearly'] as $interval)
            <option value="{{ $interval }}" @selected(old('interval', $editingPlan->interval ?? 'monthly') === $interval)>{{ ucfirst($interval) }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-2">
    <input type="number" min="1" class="form-control form-control-sm" name="post_limit" placeholder="Period Posts" value="{{ old('post_limit', $editingPlan->post_limit ?? 100) }}" required>
</div>

<div class="col-md-2"><input type="number" min="1" class="form-control form-control-sm" name="posts_per_day_limit" placeholder="Posts/day" value="{{ old('posts_per_day_limit', $editingPlan->posts_per_day_limit ?? '') }}"></div>
<div class="col-md-2"><input type="number" min="1" class="form-control form-control-sm" name="posts_per_week_limit" placeholder="Posts/week" value="{{ old('posts_per_week_limit', $editingPlan->posts_per_week_limit ?? '') }}"></div>
<div class="col-md-2"><input type="number" min="1" class="form-control form-control-sm" name="posts_per_month_limit" placeholder="Posts/month" value="{{ old('posts_per_month_limit', $editingPlan->posts_per_month_limit ?? '') }}"></div>
<div class="col-md-2"><input type="number" min="1" class="form-control form-control-sm" name="automation_limit" placeholder="Automations" value="{{ old('automation_limit', $editingPlan->automation_limit ?? '') }}"></div>
<div class="col-md-2"><input type="number" min="1" class="form-control form-control-sm" name="connected_apps_limit" placeholder="Connected apps" value="{{ old('connected_apps_limit', $editingPlan->connected_apps_limit ?? '') }}"></div>
<div class="col-md-2"><input type="number" min="1" class="form-control form-control-sm" name="synced_pages_limit" placeholder="Synced pages" value="{{ old('synced_pages_limit', $editingPlan->synced_pages_limit ?? '') }}"></div>

<div class="col-md-2"><input type="number" min="0" class="form-control form-control-sm" name="sort_order" placeholder="Sort" value="{{ old('sort_order', $editingPlan->sort_order ?? 0) }}"></div>
<div class="col-md-10 d-flex gap-3 align-items-center small">
    <label><input type="checkbox" name="facebook_enabled" value="1" @checked(old('facebook_enabled', $editingPlan->facebook_enabled ?? true))> Facebook</label>
    <label><input type="checkbox" name="instagram_enabled" value="1" @checked(old('instagram_enabled', $editingPlan->instagram_enabled ?? true))> Instagram</label>
    <label><input type="checkbox" name="google_business_enabled" value="1" @checked(old('google_business_enabled', $editingPlan->google_business_enabled ?? true))> Google Biz</label>
    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingPlan->is_active ?? true))> Active</label>
</div>
