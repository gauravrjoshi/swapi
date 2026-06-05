<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SupportTicketResource;
use App\Services\SupportTicketService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupportTicketController extends Controller
{
    use ApiResponse;

    protected $supportTicketService;

    public function __construct(SupportTicketService $supportTicketService)
    {
        $this->supportTicketService = $supportTicketService;
    }

    /**
     * Display a listing of the authenticated user's support tickets.
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = $this->supportTicketService->getUserTickets($request->user());

        return $this->successResponse(
            SupportTicketResource::collection($tickets),
            'Support tickets retrieved successfully'
        );
    }

    /**
     * Store a newly created support ticket in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|string|in:low,medium,high',
            'screenshot' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $ticket = $this->supportTicketService->createTicket(
            $request->user(),
            $request->only('title', 'description', 'priority'),
            $request->file('screenshot')
        );

        return $this->successResponse(
            new SupportTicketResource($ticket),
            'Support ticket submitted successfully',
            201
        );
    }

    /**
     * Display the specified support ticket details.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $ticket = $this->supportTicketService->getTicketDetails($request->user(), (int) $id);

        return $this->successResponse(
            new SupportTicketResource($ticket),
            'Support ticket details retrieved successfully'
        );
    }
}
