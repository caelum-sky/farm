<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\KycVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KycApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $attributes = $request->validate([
            'document_type' => ['required', Rule::in(['national_id', 'farm_registration', 'business_registration', 'drivers_license'])],
            'document_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'document_path' => ['nullable', 'string', 'max:700', 'regex:/^[A-Za-z0-9_\/.\-]+$/', 'not_regex:/\.\./'],
            'notes' => ['nullable', 'string', 'max:1200'],
        ]);

        $documentPath = $attributes['document_path'] ?? null;

        if ($request->hasFile('document_file')) {
            $documentPath = $request->file('document_file')->store('kyc/'.$request->user()->id, 'private');
        }

        $kyc = KycVerification::create([
            'user_id' => $request->user()->id,
            'provider' => 'manual',
            'provider_reference' => 'KYC-'.$request->user()->id.'-'.now()->format('YmdHis'),
            'status' => 'pending',
            'document_type' => $attributes['document_type'],
            'checks' => ['identity' => 'pending', 'documents' => 'pending'],
            'review_notes' => $attributes['notes'] ?? null,
            'submitted_at' => now(),
        ]);

        if ($documentPath) {
            Document::create([
                'documentable_type' => $kyc->getMorphClass(),
                'documentable_id' => $kyc->id,
                'uploaded_by' => $request->user()->id,
                'type' => $attributes['document_type'],
                'disk' => 'private',
                'path' => $documentPath,
                'status' => 'pending',
            ]);
        }

        $request->user()->update(['kyc_status' => 'pending']);

        return response()->json(['data' => $kyc->fresh()], 201);
    }
}
