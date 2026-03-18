<?php
namespace App\Traits;

trait ApiResponse
{
    public function success($data = null, $message = 'Success', $code = 200) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public function error($message = 'Error', $errors = null, $code = 400) {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public function unauthorized($message = 'Unauthorized') {
        return $this->error($message, null, 401);
    }

    public function forbidden($message = 'Forbidden') {
        return $this->error($message, null, 403);
    }
}