<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhatsappNotificationController extends Controller
{
    public function __construct(protected WhatsappService $whatsapp) {}

    public function test(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $sent = $this->whatsapp->send($request->phone, $request->message);

        return response()->json(['sent' => $sent]);
    }
}
