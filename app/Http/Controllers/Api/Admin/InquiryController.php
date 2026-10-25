<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingInquiry;
use App\Models\Page;
use App\Services\BookingNotifier;
use App\Services\HostingerMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index()
    {
        return BookingInquiry::query()->with('emailDeliveries')->latest()->paginate(30);
    }

    public function update(Request $request, BookingInquiry $inquiry): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,contacted,confirmed,completed,cancelled'],
        ]);
        $inquiry->update($validated);

        return response()->json(['inquiry' => $inquiry]);
    }

    public function destroy(BookingInquiry $inquiry): JsonResponse
    {
        $inquiry->delete();

        return response()->json(['message' => 'Inquiry deleted.']);
    }

    public function notify(
        BookingInquiry $inquiry,
        BookingNotifier $notifier,
        HostingerMailService $mailer
    ): JsonResponse {
        if (! $mailer->isConfigured()) {
            return response()->json([
                'message' => 'Hostinger Mail API is not configured or is currently disabled.',
            ], 422);
        }

        $notifier->notify($inquiry);

        return response()->json([
            'message' => 'Notification attempts completed. Delivery results are shown below.',
            'inquiry' => $inquiry->fresh('emailDeliveries'),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'pages' => Page::query()->count(),
            'published' => Page::query()->where('is_published', true)->count(),
            'new_inquiries' => BookingInquiry::query()->where('status', 'new')->count(),
            'total_inquiries' => BookingInquiry::query()->count(),
        ]);
    }
}
