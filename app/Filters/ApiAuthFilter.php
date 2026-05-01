<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\UserModel;

class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Pengecualian untuk preflight CORS dan endpoint public
        if ($request->getMethod() === 'options') {
            return;
        }

        $path = $request->getUri()->getPath();
        if (strpos($path, '/api-android/auth/login') !== false || 
            strpos($path, '/api-android/profile-photo') !== false || 
            strpos($path, '/api-android/task-photo') !== false ||
            strpos($path, '/api-android/integration/heartbeat') !== false ||
            strpos($path, '/api-android/integration/push') !== false) {
            return;
        }


        $userId = $request->getHeaderLine('X-User-Id') ?: $request->getVar('user_id');
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$userId || !$authHeader) {
            return \Config\Services::response()->setJSON([
                'status' => 401,
                'message' => 'Unauthorized: Missing User ID or Token'
            ])->setStatusCode(401);
        }

        // Token format: Bearer base64(userId:secretKey)
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = base64_decode($token);
        
        if (!$decoded || strpos($decoded, ':') === false) {
            return \Config\Services::response()->setJSON([
                'status' => 401,
                'message' => 'Unauthorized: Invalid Token Format'
            ])->setStatusCode(401);
        }

        list($tokenId, $secretKey) = explode(':', $decoded, 2);

        if ($tokenId != $userId) {
            return \Config\Services::response()->setJSON([
                'status' => 401,
                'message' => 'Unauthorized: Token mismatch'
            ])->setStatusCode(401);
        }

        $model = new UserModel();
        $user = $model->find($userId);

        if (!$user || $user['secret_key'] !== $secretKey) {
            return \Config\Services::response()->setJSON([
                'status' => 401,
                'message' => 'Unauthorized: Invalid Token'
            ])->setStatusCode(401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
