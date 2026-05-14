<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ThemeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search'));
        $status = trim((string) $request->get('status'));
        $level = trim((string) $request->get('min_plan_level'));

        $themes = Theme::query()
            ->withCount('sites')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->when($level !== '', function ($query) use ($level) {
                $query->where('min_plan_level', (int) $level);
            })
            ->orderBy('min_plan_level')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.themes.index', compact('themes', 'search', 'status', 'level'));
    }

    public function create(): View
    {
        $theme = new Theme([
            'min_plan_level' => 1,
            'is_active' => true,
        ]);

        return view('superadmin.themes.create', compact('theme'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTheme($request);

        if ($request->hasFile('zip_file')) {
            $validated['zip_path'] = $this->storeThemeZip($request);
        }

        if ($request->hasFile('preview_file')) {
            $validated['preview_image'] = $this->storePreviewImage($request);
        }

        Theme::create($validated);

        return redirect()
            ->route('superadmin.themes.index')
            ->with('success', 'Theme created successfully.');
    }

    public function show(Theme $theme): View
    {
        $theme->load(['sites.user', 'sites.plan']);

        return view('superadmin.themes.show', compact('theme'));
    }

    public function edit(Theme $theme): View
    {
        return view('superadmin.themes.edit', compact('theme'));
    }

    public function update(Request $request, Theme $theme): RedirectResponse
    {
        $validated = $this->validateTheme($request, $theme);

        if ($request->hasFile('zip_file')) {
            $this->deleteFromDisk($theme->zip_path);
            $validated['zip_path'] = $this->storeThemeZip($request);
        }

        if ($request->hasFile('preview_file')) {
            $this->deleteFromDisk($theme->preview_image);
            $validated['preview_image'] = $this->storePreviewImage($request);
        }

        $theme->update($validated);

        return redirect()
            ->route('superadmin.themes.show', $theme)
            ->with('success', 'Theme updated successfully.');
    }

    public function destroy(Theme $theme): RedirectResponse
    {
        if ($theme->sites()->exists()) {
            return back()->with('error', 'This theme is attached to existing sites. Disable it instead of deleting.');
        }

        $this->deleteFromDisk($theme->zip_path);
        $this->deleteFromDisk($theme->preview_image);

        $theme->delete();

        return redirect()
            ->route('superadmin.themes.index')
            ->with('success', 'Theme deleted successfully.');
    }

    protected function validateTheme(Request $request, ?Theme $theme = null): array
    {
        $themeId = $theme?->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', 'alpha_dash', 'unique:themes,slug,' . $themeId],
            'zip_path' => ['nullable', 'string', 'max:255'],
            'preview_image' => ['nullable', 'string', 'max:255'],
            'zip_file' => [$theme ? 'nullable' : 'nullable', 'file', 'mimes:zip', 'max:102400'],
            'preview_file' => ['nullable', 'image', 'max:5120'],
            'min_plan_level' => ['required', 'integer', 'min:1', 'max:3'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');

        unset($validated['zip_file'], $validated['preview_file']);

        return $validated;
    }

    protected function storeThemeZip(Request $request): string
    {
        $disk = config('wordpress.theme_storage_disk', 'themes');

        return $request->file('zip_file')->store('', $disk);
    }

    protected function storePreviewImage(Request $request): string
    {
        $disk = config('wordpress.theme_storage_disk', 'themes');

        return $request->file('preview_file')->store('previews', $disk);
    }

    protected function deleteFromDisk(?string $path): void
    {
        if (! $path) {
            return;
        }

        $disk = config('wordpress.theme_storage_disk', 'themes');

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}