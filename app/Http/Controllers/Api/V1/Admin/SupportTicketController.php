<?php

namespace App\Http\Controllers\Api\V1\Admin;

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
     * Display a listing of all support tickets in the system (filtered for admin dashboard).
     */
    public function index(): JsonResponse
    {
        $tickets = $this->supportTicketService->getAllTickets();

        return $this->successResponse(
            SupportTicketResource::collection($tickets),
            'All support tickets retrieved successfully'
        );
    }

    /**
     * Update the status of the specified support ticket.
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:open,in_progress,resolved,closed',
        ]);

        $ticket = $this->supportTicketService->updateTicketStatus((int) $id, $request->status);

        return $this->successResponse(
            new SupportTicketResource($ticket),
            'Support ticket status updated successfully'
        );
    }
}
