<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function index(Request $request)
    {
        $status = $request->query('status');
        $type = $request->query('type');

        $query = Feedback::with('customer')->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $feedback = $query->paginate(15)->withQueryString();

        $openCount = Feedback::where('status', 'Open')->count();
        $inProgressCount = Feedback::where('status', 'In Progress')->count();
        $resolvedCount = Feedback::where('status', 'Resolved')->count();
        $averageRating = round(Feedback::where('type', 'Feedback')->avg('rating') ?: 5, 1);

        return view('feedback.index', compact('feedback', 'status', 'type', 'openCount', 'inProgressCount', 'resolvedCount', 'averageRating'));
    }

    public function updateStatus(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'status' => 'required|in:Open,In Progress,Resolved',
            'admin_response' => 'nullable|string|max:2000',
        ]);

        $oldStatus = $feedback->status;
        $feedback->update($validated);

        $this->activityLogService->log(
            action: 'Feedback Status Changed',
            entityType: 'Feedback',
            entityId: $feedback->id,
            description: sprintf('Feedback #%d from %s status changed from %s to %s', $feedback->id, $feedback->customer_name, $oldStatus, $feedback->status),
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $feedback->status, 'admin_response' => $feedback->admin_response]
        );

        return back()->with('success', "Feedback status updated to {$feedback->status}!");
    }
}
