<?php 

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Cors implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Whitelist specific origins instead of allowing all
        $allowed_origins = [
            'https://192.168.2.6:8443',
            'https://trusted-frontend-domain.com',
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowed_origins)) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-Id");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 3600");

        if ($request->getMethod() === 'options') {
            die();
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
