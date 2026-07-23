<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Rules\NotReservedSubdomain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TenantSubdomainAvailabilityController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $value = strtolower((string) $request->query('value', ''));

        $validator = Validator::make(['subdomain' => $value], [
            'subdomain' => [
                'required', 'string', 'min:3', 'max:63',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                new NotReservedSubdomain,
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['available' => false]);
        }

        return response()->json([
            'available' => ! Tenant::where('subdomain', $value)->exists(),
        ]);
    }
}
