@extends('layouts.app')

@section('title', 'Feedback')

@section('page-header')
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Guest services</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Feedback</h1>
        <p class="mt-1 text-sm text-gray-500">Review guest ratings and follow up when needed.</p>
    </div>
@endsection

@section('content')
    <div x-data="{ contactOpen: false, contactFeedback: null }">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('admin.feedback.index') }}" class="rounded-[10px] border px-3 py-1.5 text-xs font-semibold {{ $activeStatus ? 'border-gray-200 text-gray-600' : 'border-orange-300 bg-orange-50 text-orange-700' }}">All</a>
            @foreach ($statuses as $value => $label)
                <a href="{{ route('admin.feedback.index', ['status' => $value]) }}" class="rounded-[10px] border px-3 py-1.5 text-xs font-semibold {{ $activeStatus === $value ? 'border-orange-300 bg-orange-50 text-orange-700' : 'border-gray-200 text-gray-600' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="admin-table-shell overflow-x-auto">
            <table class="min-w-[900px] divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Customer</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Rating</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Comment</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($feedbacks as $feedback)
                        @php
                            $feedbackStatusClass = match ($feedback->status) {
                                'approved' => 'admin-badge-completed',
                                'rejected' => 'admin-badge-cancelled',
                                default => 'admin-badge-pending',
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium">{{ $feedback->user?->name }}</p>
                                <p class="text-xs text-gray-500">{{ $feedback->user?->email }}</p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-amber-500">
                                {{ str_repeat('★', $feedback->rating) }}<span class="text-gray-300">{{ str_repeat('★', 5 - $feedback->rating) }}</span>
                            </td>
                            <td class="px-4 py-3 max-w-xs truncate text-gray-600" title="{{ $feedback->comment }}">{{ $feedback->comment ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $feedback->created_at->format('M d, Y h:i A') }}</td>
                            <td class="px-4 py-3"><span class="{{ $feedbackStatusClass }}">{{ $feedback->statusLabel() }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @if ($feedback->status === 'pending')
                                        <form action="{{ route('admin.feedback.status', $feedback) }}" method="post" class="inline">
                                            @csrf
                                            @method('patch')
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="inline-flex size-8 items-center justify-center rounded-lg border border-emerald-200 text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-50" title="Approve">
                                                <i class="fa-solid fa-check text-xs" aria-hidden="true"></i>
                                                <span class="sr-only">Approve</span>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.feedback.status', $feedback) }}" method="post" class="inline">
                                            @csrf
                                            @method('patch')
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" class="inline-flex size-8 items-center justify-center rounded-lg border border-red-200 text-red-700 transition hover:border-red-300 hover:bg-red-50" title="Reject">
                                                <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
                                                <span class="sr-only">Reject</span>
                                            </button>
                                        </form>
                                    @endif
                                    @if ($feedback->user?->email)
                                        <button
                                            type="button"
                                            class="inline-flex size-8 items-center justify-center rounded-lg border border-sky-200 text-sky-700 transition hover:border-sky-300 hover:bg-sky-50"
                                            title="Email {{ $feedback->user->email }}"
                                            x-on:click="contactOpen = true; contactFeedback = {{ json_encode([
                                                'id' => $feedback->id,
                                                'name' => $feedback->user->name,
                                                'email' => $feedback->user->email,
                                                'action' => route('admin.feedback.contact', $feedback),
                                            ]) }}"
                                        >
                                            <i class="fa-solid fa-envelope text-xs" aria-hidden="true"></i>
                                            <span class="sr-only">Email customer</span>
                                        </button>
                                    @endif
                                    <form action="{{ route('admin.feedback.destroy', $feedback) }}" method="post" class="inline" onsubmit="return confirm('Delete this feedback?');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="inline-flex size-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:border-red-300 hover:bg-red-50 hover:text-red-700" title="Delete">
                                            <i class="fa-solid fa-trash text-xs" aria-hidden="true"></i>
                                            <span class="sr-only">Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">No feedback submitted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $feedbacks->links() }}</div>

        {{-- Contact customer dialog --}}
        <template x-teleport="body">
            <div
                x-show="contactOpen"
                x-cloak
                x-transition.opacity
                class="fixed inset-0 z-[200] flex items-center justify-center bg-[#0D2232]/50 p-4"
                role="dialog"
                aria-modal="true"
                x-on:keydown.escape.window="contactOpen = false"
                x-on:click.self="contactOpen = false"
            >
                <div class="w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl" x-on:click.stop>
                    <header class="flex items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Email customer</h2>
                            <p class="mt-1 text-sm text-gray-500" x-text="contactFeedback ? contactFeedback.name + ' · ' + contactFeedback.email : ''"></p>
                        </div>
                        <button type="button" class="rounded-lg p-1 text-gray-500 transition hover:bg-slate-100 hover:text-gray-700" x-on:click="contactOpen = false">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </header>
                    <form method="post" x-bind:action="contactFeedback ? contactFeedback.action : ''" class="space-y-4 px-6 py-5">
                        @csrf
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-400">Subject</label>
                            <input name="subject" required maxlength="150" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="About your recent review">
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wider text-gray-400">Message</label>
                            <textarea name="message" rows="5" required maxlength="2000" class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Write your message to the customer..."></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="rounded-[10px] border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600" x-on:click="contactOpen = false">Cancel</button>
                            <button type="submit" class="admin-btn-primary">Send email</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
@endsection
