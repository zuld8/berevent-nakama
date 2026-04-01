<?php

namespace App\Http\Controllers;

use App\Services\WaService;
use Illuminate\Http\Request;

class WaController extends Controller
{
    public function validateNumber(Request $request)
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:30'],
        ]);

        $svc = new WaService();
        $cfg = $svc->getConfig();

        // If disabled or not configured, treat as valid.
        if (! (bool)($cfg['validate_enabled'] ?? false) || empty($cfg['validate_client_id'])) {
            return response()->json([
                'ok' => true,
                'isRegistered' => true,
                'message' => 'Validation disabled',
            ]);
        }

        $res = $svc->validateNumber($data['number']);
        $isReg = (bool)($res['isRegistered'] ?? false);

        return response()->json([
            'ok' => true,
            'isRegistered' => $isReg,
            'data' => $res,
        ]);
    }
}

