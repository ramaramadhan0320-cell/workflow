<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // cek session login
        if (!session()->get('isLoggedIn')) {
            // For AJAX requests, return JSON error instead of redirect
            if ($request->isAJAX()) {
                $response = service('response');
                $response->setStatusCode(401);
                $response->setJSON(['success' => false, 'message' => 'Not logged in']);
                return $response;
            }
            return redirect()->to('/');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // tidak perlu isi apa-apa
    }
}