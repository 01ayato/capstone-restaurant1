@extends('layouts.app')

@section('title', 'Menu Management')

@section('page-header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Catalog</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Menu Management</h1>
            <p class="mt-1 text-sm text-gray-500">Add, edit, and manage food items</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                class="admin-btn-secondary shrink-0"
                onclick="window.dispatchEvent(new CustomEvent('menu-items-open-categories'))"
            >
                Manage Categories
            </button>
            <button
                type="button"
                class="admin-btn-accent shrink-0"
                onclick="window.dispatchEvent(new CustomEvent('menu-items-open-add-category'))"
            >
                Add Category
            </button>
            <button
                type="button"
                class="admin-btn-accent shrink-0"
                onclick="window.dispatchEvent(new CustomEvent('menu-items-open-add-item'))"
            >
                Add Food Item
            </button>
        </div>
    </div>
@endsection

@section('content')
    <script type="application/json" id="menu-page-initial">@json($menuPageInitial)</script>

    <div
        x-data="menuItemsPage()"
        x-on:menu-items-open-categories.window="openManage()"
        x-on:menu-items-open-add-category.window="openAdd()"
        x-on:menu-items-open-add-item.window="openAddItem()"
        class="space-y-6"
    >
        <template x-teleport="body">
            <div
                x-show="addItemOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[210] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="add-item-title"
                x-on:keydown.escape.window="closeAddItem()"
                x-on:click.self="closeAddItem()"
            >
                <div class="flex max-h-[90vh] min-h-0 w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl" x-on:click.stop="">
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                        <div>
                            <h2 id="add-item-title" class="text-lg font-bold text-gray-900">Add Food Item</h2>
                            <p class="mt-1 text-sm text-gray-500">Create a new menu item and assign it to a category.</p>
                        </div>
                        <button type="button" class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700" x-on:click="closeAddItem()">
                            <span class="sr-only">Close</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </header>

                    <form
                        action="{{ route('admin.menu-items.store') }}"
                        method="post"
                        enctype="multipart/form-data"
                        class="flex min-h-0 flex-1 flex-col"
                    >
                        @csrf
                        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Category</label>
                                    <div class="flex gap-2">
                                        <select name="category_id" required x-model="addCategoryId" class="min-w-0 flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                                            @forelse ($categories as $cat)
                                                <option value="{{ $cat->id }}" data-slug="{{ $cat->slug }}">{{ $cat->name }}</option>
                                            @empty
                                                <option value="" disabled selected>No categories yet</option>
                                            @endforelse
                                        </select>
                                        <button
                                            type="button"
                                            class="shrink-0 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                                            title="Add category"
                                            x-on:click="openAdd()"
                                        >
                                            +
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Name</label>
                                    <input name="name" type="text" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Price (₱)</label>
                                    <input name="price" type="number" step="0.01" min="0" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Image</label>
                                    <input name="image" type="file" accept="image/*" class="w-full text-sm">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-gray-500">Description</label>
                                    <textarea name="description" rows="2" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"></textarea>
                                </div>
                                <div class="flex items-end">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="is_available" value="1" checked> Available
                                    </label>
                                </div>
                                <template x-if="isDrinksCategory(addCategoryId)">
                                    <div class="sm:col-span-2 grid gap-4 sm:grid-cols-2 rounded-lg border border-amber-200 bg-amber-50/60 p-4">
                                        <p class="sm:col-span-2 text-xs font-medium text-amber-800">Drinks inventory — stock is deducted when sold in POS or reserved as a preorder.</p>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-zinc-600">Stock Level</label>
                                            <input name="stock_level" type="number" min="0" value="{{ old('stock_level', 0) }}" x-bind:required="isDrinksCategory(addCategoryId)" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-zinc-600">Reorder Level</label>
                                            <input name="reorder_level" type="number" min="0" value="{{ old('reorder_level', 5) }}" x-bind:required="isDrinksCategory(addCategoryId)" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                                            <p class="mt-1 text-[11px] text-zinc-500">Alert when stock falls to this level or below.</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <footer class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-gray-200 bg-slate-50 px-6 py-4">
                            <button type="button" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50" x-on:click="closeAddItem()">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D]">
                                Add Item
                            </button>
                        </footer>
                    </form>
                </div>
            </div>
        </template>

        <div class="flex flex-wrap items-center gap-3">
            <label for="menu-category-filter" class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500">Filter by Category</label>
            <select
                id="menu-category-filter"
                x-model="selectedCategoryId"
                class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm transition focus:border-zinc-400 focus:outline-none"
            >
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="admin-table-shell overflow-x-auto lg:overflow-visible">
            <table class="min-w-[760px] divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-600">Item</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-600">Category</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-600">Price</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-600">Stock</th>
                        <th class="px-4 py-3 text-left font-semibold text-zinc-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-zinc-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($menuItems as $item)
                        <tr x-show="selectedCategoryId === '' || selectedCategoryId === '{{ $item->category_id }}'">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $item->imageUrl() }}" alt="" class="size-10 rounded-lg object-cover">
                                    <span class="font-medium">{{ $item->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $item->category->name }}</td>
                            <td class="px-4 py-3 font-medium">{{ $item->formattedPrice() }}</td>
                            <td class="px-4 py-3">
                                @if ($item->tracksInventory())
                                    <span class="font-medium {{ $item->isLowStock() ? 'text-amber-700' : 'text-zinc-800' }}">{{ $item->stock_level }}</span>
                                    @if ($item->reorder_level !== null)
                                        <span class="block text-[11px] text-zinc-500">Reorder ≤ {{ $item->reorder_level }}</span>
                                    @endif
                                    @if ($item->isLowStock())
                                        <span class="mt-0.5 inline-block rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">Low stock</span>
                                    @endif
                                @else
                                    <span class="text-xs text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $item->is_available ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $item->is_available ? 'Available' : 'Unavailable' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                    data-item="{{ json_encode([
                                        'id' => $item->id,
                                        'category_id' => $item->category_id,
                                        'name' => $item->name,
                                        'price' => (float) $item->price,
                                        'description' => $item->description,
                                        'is_available' => $item->is_available,
                                        'stock_level' => $item->stock_level,
                                        'reorder_level' => $item->reorder_level,
                                        'image_url' => $item->imageUrl(),
                                    ]) }}"
                                    x-on:click="openEditItem(JSON.parse($el.dataset.item))"
                                >
                                    Edit
                                </button>
                                <form
                                    action="{{ route('admin.menu-items.destroy', $item) }}"
                                    method="post"
                                    class="ml-2 inline"
                                    data-confirm-title="Delete this item?"
                                    data-confirm-text="This action cannot be undone."
                                    data-confirm-yes="Delete"
                                    data-confirm-no="Cancel"
                                    x-on:submit.prevent="(el => confirmDialog({ title: el.dataset.confirmTitle, text: el.dataset.confirmText, confirmButtonText: el.dataset.confirmYes, cancelButtonText: el.dataset.confirmNo, icon: 'warning' }).then(ok => ok && el.submit()))($el)"
                                >
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-600">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <template x-teleport="body">
            <div
                x-show="manageOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="manage-categories-title"
                x-on:keydown.escape.window="closeManage()"
                x-on:click.self="closeManage()"
            >
                <div class="flex max-h-[90vh] min-h-0 w-full max-w-3xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl" x-on:click.stop="">
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                        <div>
                            <h2 id="manage-categories-title" class="text-lg font-bold text-gray-900">Manage Categories</h2>
                            <p class="mt-1 text-sm text-gray-500">Categories appear in the menu item dropdown.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg bg-[#0D2232] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#16364D]"
                                x-on:click="openAdd()"
                            >
                                Add Category
                            </button>
                            <button type="button" class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700" x-on:click="closeManage()">
                                <span class="sr-only">Close</span>
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </header>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Name</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Items</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Order</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-if="categories.length === 0">
                                    <tr>
                                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">No categories yet. Add one to get started.</td>
                                    </tr>
                                </template>
                                <template x-for="category in categories" :key="category.id">
                                    <tr>
                                        <td class="px-4 py-3 font-medium" x-text="category.name"></td>
                                        <td class="px-4 py-3 text-gray-600" x-text="category.menu_items_count"></td>
                                        <td class="px-4 py-3 text-gray-600" x-text="category.sort_order"></td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                x-bind:class="category.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-gray-600'"
                                                x-text="category.is_active ? 'Active' : 'Hidden'"
                                            ></span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="inline-flex rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50"
                                                    x-on:click="openEdit(category)"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    class="inline-flex rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 shadow-sm transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                    x-bind:disabled="category.menu_items_count > 0"
                                                    x-on:click="confirmDelete(category)"
                                                >
                                                    Delete
                                                </button>
                                                <form
                                                    x-bind:id="'delete-category-' + category.id"
                                                    method="post"
                                                    x-bind:action="destroyFormAction(category.id)"
                                                    class="hidden"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div
                x-show="addOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[210] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="add-category-title"
                x-on:keydown.escape.window="closeAdd()"
                x-on:click.self="closeAdd()"
            >
                <div class="flex max-h-[90vh] min-h-0 w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl" x-on:click.stop="">
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                        <h2 id="add-category-title" class="text-lg font-bold text-gray-900">Add Category</h2>
                        <button type="button" class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700" x-on:click="closeAdd()">
                            <span class="sr-only">Close</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </header>

                    <form method="post" action="{{ route('admin.categories.store') }}" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-4">
                            <div>
                                <label for="add-category-name" class="mb-1 block text-xs font-medium text-gray-500">Name</label>
                                <input id="add-category-name" name="name" type="text" required placeholder="e.g. Appetizers" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label for="add-category-order" class="mb-1 block text-xs font-medium text-gray-500">Sort Order</label>
                                <input id="add-category-order" name="sort_order" type="number" min="0" placeholder="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label for="add-category-description" class="mb-1 block text-xs font-medium text-gray-500">Description</label>
                                <input id="add-category-description" name="description" type="text" placeholder="Optional" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_active" value="1" checked> Active
                            </label>
                        </div>
                        <footer class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-gray-200 bg-slate-50 px-6 py-4">
                            <button type="button" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50" x-on:click="closeAdd()">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D]">
                                Save Category
                            </button>
                        </footer>
                    </form>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div
                x-show="editOpen && editCategory"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[210] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-category-title"
                x-on:keydown.escape.window="closeEdit()"
                x-on:click.self="closeEdit()"
            >
                <div class="flex max-h-[90vh] min-h-0 w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl" x-on:click.stop="">
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                        <h2 id="edit-category-title" class="text-lg font-bold text-gray-900">Edit Category</h2>
                        <button type="button" class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700" x-on:click="closeEdit()">
                            <span class="sr-only">Close</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </header>

                    <form method="post" x-bind:action="editFormAction()" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        @method('PUT')
                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-4">
                            <div>
                                <label for="edit-category-name" class="mb-1 block text-xs font-medium text-gray-500">Name</label>
                                <input id="edit-category-name" name="name" type="text" required x-model="editCategory.name" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label for="edit-category-order" class="mb-1 block text-xs font-medium text-gray-500">Sort Order</label>
                                <input id="edit-category-order" name="sort_order" type="number" min="0" x-model="editCategory.sort_order" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label for="edit-category-description" class="mb-1 block text-xs font-medium text-gray-500">Description</label>
                                <input id="edit-category-description" name="description" type="text" x-model="editCategory.description" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_active" value="1" x-model="editCategory.is_active"> Active
                            </label>
                        </div>
                        <footer class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-gray-200 bg-slate-50 px-6 py-4">
                            <button type="button" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-slate-50" x-on:click="closeEdit()">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16364D]">
                                Save Changes
                            </button>
                        </footer>
                    </form>
                </div>
            </div>
        </template>

        <template x-teleport="body">
            <div
                x-show="editItemOpen && editItem"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[210] flex items-center justify-center bg-zinc-900/50 p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="edit-item-title"
                x-on:keydown.escape.window="closeEditItem()"
                x-on:click.self="closeEditItem()"
            >
                <div class="flex max-h-[90vh] min-h-0 w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl" x-on:click.stop="">
                    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-zinc-200 px-6 py-4">
                        <div>
                            <h2 id="edit-item-title" class="text-lg font-bold text-zinc-900">Edit Menu Item</h2>
                            <p class="mt-1 text-sm text-zinc-500" x-text="editItem?.name"></p>
                        </div>
                        <button type="button" class="rounded-lg p-1 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800" x-on:click="closeEditItem()">
                            <span class="sr-only">Close</span>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </header>

                    <form method="post" x-bind:action="editItemFormAction()" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        @method('PUT')
                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-4">
                            <div>
                                <label for="edit-item-category" class="mb-1 block text-xs font-medium text-zinc-500">Category</label>
                                <select id="edit-item-category" name="category_id" required x-model="editItem.category_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                                    <template x-for="category in categories" :key="category.id">
                                        <option x-bind:value="String(category.id)" x-text="category.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label for="edit-item-name" class="mb-1 block text-xs font-medium text-zinc-500">Name</label>
                                <input id="edit-item-name" name="name" type="text" required x-model="editItem.name" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label for="edit-item-price" class="mb-1 block text-xs font-medium text-zinc-500">Price (₱)</label>
                                <input id="edit-item-price" name="price" type="number" step="0.01" min="0" required x-model="editItem.price" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label for="edit-item-description" class="mb-1 block text-xs font-medium text-zinc-500">Description</label>
                                <textarea id="edit-item-description" name="description" rows="3" x-model="editItem.description" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm"></textarea>
                            </div>
                            <div>
                                <label for="edit-item-image" class="mb-1 block text-xs font-medium text-zinc-500">Image</label>
                                <div class="mb-2" x-show="editItem.image_url">
                                    <img x-bind:src="editItem.image_url" alt="" class="size-16 rounded-lg object-cover">
                                </div>
                                <input id="edit-item-image" name="image" type="file" accept="image/*" class="w-full text-sm">
                            </div>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="hidden" name="is_available" x-bind:value="editItem.is_available ? 1 : 0">
                                <input type="checkbox" value="1" x-model="editItem.is_available"> Available
                            </label>
                            <div
                                x-show="isDrinksCategory(editItem.category_id)"
                                x-cloak
                                class="space-y-3 rounded-lg border border-amber-200 bg-amber-50/60 p-4"
                            >
                                <p class="text-xs font-medium text-amber-800">Drinks inventory — stock is deducted in POS and reservation preorders.</p>
                                <div>
                                    <label for="edit-item-stock" class="mb-1 block text-xs font-medium text-zinc-600">Stock Level</label>
                                    <input
                                        id="edit-item-stock"
                                        name="stock_level"
                                        type="number"
                                        min="0"
                                        x-model="editItem.stock_level"
                                        x-bind:required="isDrinksCategory(editItem.category_id)"
                                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm"
                                    >
                                </div>
                                <div>
                                    <label for="edit-item-reorder" class="mb-1 block text-xs font-medium text-zinc-600">Reorder Level</label>
                                    <input
                                        id="edit-item-reorder"
                                        name="reorder_level"
                                        type="number"
                                        min="0"
                                        x-model="editItem.reorder_level"
                                        x-bind:required="isDrinksCategory(editItem.category_id)"
                                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm"
                                    >
                                    <p class="mt-1 text-[11px] text-zinc-500">Alert when stock falls to this level or below.</p>
                                </div>
                            </div>
                        </div>
                        <footer class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-zinc-200 bg-zinc-50 px-6 py-4">
                            <button type="button" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-800 shadow-sm transition hover:bg-zinc-50" x-on:click="closeEditItem()">
                                Cancel
                            </button>
                            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800">
                                Save Changes
                            </button>
                        </footer>
                    </form>
                </div>
            </div>
        </template>
    </div>
@endsection
