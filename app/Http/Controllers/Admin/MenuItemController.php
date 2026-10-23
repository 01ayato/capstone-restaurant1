<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuItemController extends Controller
{
    public function index(): View
    {
        $menuItems = MenuItem::query()->with('category')->orderBy('category_id')->orderBy('sort_order')->get();
        $categories = Category::query()->withCount('menuItems')->orderBy('sort_order')->get();
        $signatureCount = $menuItems->where('is_signature', true)->count();

        $menuPageInitial = [
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'sort_order' => $category->sort_order,
                'is_active' => $category->is_active,
                'menu_items_count' => $category->menu_items_count,
            ])->values()->all(),
            'items' => $menuItems->map(fn (MenuItem $item) => [
                'id' => $item->id,
                'category_id' => (string) $item->category_id,
                'name' => $item->name,
                'description' => $item->description ?? '',
            ])->values()->all(),
            'signatureCount' => $signatureCount,
            'maxSignatureItems' => MenuItem::MAX_SIGNATURE_ITEMS,
            'routes' => [
                'store' => route('admin.categories.store'),
                'updateBase' => url('admin/categories'),
                'destroyBase' => url('admin/categories'),
                'itemUpdateBase' => url('admin/menu-items'),
                'itemSignatureBase' => url('admin/menu-items'),
            ],
        ];

        return view('admin.menu-items.index', compact('menuItems', 'categories', 'menuPageInitial', 'signatureCount'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateItem($request);

        if ($validated['is_signature'] && ! $this->canFeature()) {
            return back()->withInput()->with('error', $this->signatureLimitMessage());
        }

        $validated['slug'] = Str::slug($validated['name']).'-'.Str::random(4);

        if ($request->hasFile('image')) {
            $validated['image'] = $this->storeImage($request->file('image'));
        }

        MenuItem::query()->create($validated);

        return back()->with('success', 'Menu item created.');
    }

    public function update(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $validated = $this->validateItem($request, $menuItem->id);

        if ($validated['is_signature'] && ! $menuItem->is_signature && ! $this->canFeature()) {
            return back()->withInput()->with('error', $this->signatureLimitMessage());
        }

        if ($request->hasFile('image')) {
            $this->deleteImage($menuItem->image);
            $validated['image'] = $this->storeImage($request->file('image'));
        }

        $menuItem->update($validated);

        return back()->with('success', 'Menu item updated.');
    }

    /**
     * Quick star-toggle for the "Featured" column (used on the Home page's Signature Dishes section).
     */
    public function toggleSignature(MenuItem $menuItem): RedirectResponse
    {
        if (! $menuItem->is_signature && ! $this->canFeature()) {
            return back()->with('error', $this->signatureLimitMessage());
        }

        $menuItem->update(['is_signature' => ! $menuItem->is_signature]);

        return back()->with('success', $menuItem->is_signature ? 'Added to Signature Dishes.' : 'Removed from Signature Dishes.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $this->deleteImage($menuItem->image);
        $menuItem->delete();

        return back()->with('success', 'Menu item deleted.');
    }

    private function canFeature(): bool
    {
        return MenuItem::query()->where('is_signature', true)->count() < MenuItem::MAX_SIGNATURE_ITEMS;
    }

    private function signatureLimitMessage(): string
    {
        return 'You can only select up to '.MenuItem::MAX_SIGNATURE_ITEMS.' signature dishes. Please unselect one first.';
    }

    private function storeImage(UploadedFile $file): string
    {
        $directory = public_path('images/menu');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'images/menu/'.$filename;
    }

    private function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (str_starts_with($path, 'images/')) {
            $fullPath = public_path($path);
            if (is_file($fullPath)) {
                unlink($fullPath);
            }

            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateItem(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'base_prep_time' => ['required', 'integer', 'min:0'],
            'is_available' => ['nullable', 'boolean'],
            'is_signature' => ['nullable', 'boolean'],
            'stock_level' => ['nullable', 'integer', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]) + [
            'is_available' => $request->boolean('is_available'),
            'is_signature' => $request->boolean('is_signature'),
            'sort_order' => $request->input('sort_order', 0),
        ];

        $category = Category::query()->find($validated['category_id']);
        $isDrinks = ($category?->slug ?? '') === 'drinks';

        if ($isDrinks) {
            $request->validate([
                'stock_level' => ['required', 'integer', 'min:0'],
                'reorder_level' => ['required', 'integer', 'min:0'],
            ]);
            $validated['stock_level'] = (int) $request->input('stock_level');
            $validated['reorder_level'] = (int) $request->input('reorder_level');
        } else {
            $validated['stock_level'] = null;
            $validated['reorder_level'] = null;
        }

        return $validated;
    }
}
