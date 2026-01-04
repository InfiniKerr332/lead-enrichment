<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Services\LeadProcessorService;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    private $processor;

    public function __construct(LeadProcessorService $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Capture a new lead from external sources (forms, webhooks, etc.)
     */
    public function capture(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:leads,email',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'source' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create lead
            $lead = Lead::create([
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'company' => $request->company,
                'phone' => $request->phone,
                'source' => $request->source ?? 'api',
                'status' => 'new'
            ]);

            // Process lead in background (you can also queue this)
            $this->processor->processLead($lead);

            return response()->json([
                'success' => true,
                'message' => 'Lead captured and processing started',
                'lead_id' => $lead->id
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to capture lead',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track lead activity (page views, downloads, etc.)
     */
    public function trackActivity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'activity_type' => 'required|string',
            'activity_value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $lead = Lead::where('email', $request->email)->first();

            if (!$lead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lead not found'
                ], 404);
            }

            $scoringService = app(\App\Services\LeadScoringService::class);
            $scoringService->trackActivity(
                $lead,
                $request->activity_type,
                $request->activity_value
            );

            return response()->json([
                'success' => true,
                'message' => 'Activity tracked successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get lead details with score
     */
    public function show($id)
    {
        try {
            $lead = Lead::with(['enrichment', 'score', 'activities', 'assignment.salesRep'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'lead' => $lead
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }
    }

    /**
     * Get dashboard stats
     */
    public function stats()
    {
        try {
            $stats = [
                'total_leads' => Lead::count(),
                'new_leads' => Lead::where('status', 'new')->count(),
                'enriched_leads' => Lead::where('status', 'enriched')->count(),
                'assigned_leads' => Lead::where('status', 'assigned')->count(),
                'by_grade' => Lead::join('lead_scores', 'leads.id', '=', 'lead_scores.lead_id')
                    ->selectRaw('lead_scores.grade, COUNT(*) as count')
                    ->groupBy('lead_scores.grade')
                    ->pluck('count', 'grade'),
                'today_leads' => Lead::whereDate('created_at', today())->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}