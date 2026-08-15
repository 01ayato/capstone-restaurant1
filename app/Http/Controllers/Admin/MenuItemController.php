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
            'routes' => [
                'store' => route('admin.categories.store'),
                'updateBase' => url('admin/categories'),
                'destroyBase' => url('admin/categories'),
                'itemUpdateBase' => url('admin/menu-items'),
            ],
        ];

        return view('admin.menu-items.index', compact('menuItems', 'categories', 'menuPageInitial'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateItem($request);
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

        if ($request->hasFile('image')) {
            $this->deleteImage($menuItem->image);
            $validated['image'] = $this->storeImage($request->file('image'));
        }

        $menuItem->update($validated);

        return back()->with('success', 'Menu item updated.');
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $this->deleteImage($menuItem->image);
        $menuItem->delete();

        return back()->with('success', 'Menu item deleted.');
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
            'is_available' => ['nullable', 'boolean'],
            'stock_level' => ['nullable', 'integer', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]) + [
            'is_available' => $request->boolean('is_available'),
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
