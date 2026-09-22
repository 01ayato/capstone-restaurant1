<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\FeedbackReplyMail;
use App\Models\Feedback;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $feedbacks = Feedback::query()
            ->with(['user', 'order', 'reservation'])
            ->when($status && in_array($status, Feedback::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.feedback.index', [
            'feedbacks' => $feedbacks,
            'statuses' => Feedback::STATUS_LABELS,
            'activeStatus' => $status,
        ]);
    }

    public function updateStatus(Request $request, Feedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([Feedback::STATUS_APPROVED, Feedback::STATUS_REJECTED])],
        ]);

        $feedback->update([
            'status' => $validated['status'],
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Feedback '.$feedback->statusLabel().'.');
    }

    public function contact(Request $request, Feedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $feedback->loadMissing('user');
        $to = $feedback->user?->email;

        if (! $to) {
            return back()->withErrors(['message' => 'This customer has no email on file.']);
        }

        try {
            Mail::to($to)->send(new FeedbackReplyMail($feedback, $validated['subject'], $validated['message']));
        } catch (Throwable $e) {
            Log::warning('Feedback reply email failed', [
                'feedback_id' => $feedback->id,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['message' => 'Could not send the email. Please try again.']);
        }

        $feedback->update([
            'contacted_at' => now(),
            'contacted_by' => Auth::id(),
        ]);

        return back()->with('success', 'Email sent to '.$to.'.');
    }

    public function destroy(Feedback $feedback): RedirectResponse
    {
        $feedback->delete();

        return back()->with('success', 'Feedback deleted.');
    }
}
