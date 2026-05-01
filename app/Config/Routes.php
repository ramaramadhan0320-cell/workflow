<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ✅ PUBLIC ROUTES (TANPA LOGIN)
$routes->get('/', 'Auth::login');
$routes->get('/login', 'Auth::login'); // 🔥 PINDAH KE SINI
$routes->post('login/process', 'Auth::process');
// PDF cache serving (no auth required - secured by MD5 filename validation)
$routes->get('/payment/serve-cached-pdf', 'Payment::serveCachedPdf');


// 🔒 PROTECTED ROUTES
$routes->group('', ['filter' => 'auth'], function($routes) {

    $routes->get('/logout', 'Auth::logout');
    $routes->get('/dashboard', 'Dashboard::index'); 
    
    $routes->get('/absensi', 'Absensi::index'); 
    $routes->post('/absensi/absen', 'Absensi::absen'); 
    $routes->post('/absen/ajax', 'Absensi::absenHariIni');

    $routes->get('/detail', 'Task::detailList'); 
    $routes->get('/task/(:num)', 'Task::taskDetail/$1');
    $routes->post('/task/update', 'Task::updateTask');
    $routes->post('/task/update-status', 'Task::updateStatus');
    $routes->delete('/task/delete/(:num)', 'Task::delete/$1');
    $routes->get('image/(:any)', 'Task::getImage/$1');
    $routes->post('/task/store', 'Task::store');
    
    // Task Payment
    $routes->post('/payment/store', 'TaskPayment::store');
    $routes->get('/payment/(:num)', 'TaskPayment::getPayments/$1');
    $routes->delete('/payment/delete/(:num)', 'TaskPayment::delete/$1');
    
    // Payment Dashboard
    $routes->get('/payment', 'Payment::index');
    $routes->get('/payment/rincian', 'Payment::rincian');
    $routes->get('/payment/slip', 'Payment::slip');
    $routes->post('/payment/send-slip', 'Payment::sendSlip');
    $routes->get('/payment/get-slip-data', 'Payment::getSlipData');
    $routes->get('/payment/get-slip-pdf-base64', 'Payment::getSlipPdfBase64');
    $routes->get('/payment/get-slip-pdf', 'Payment::getSlipPdf');
    $routes->get('/payment/download-slip', 'Payment::downloadSlip');
    $routes->get('/payment/preview-slip', 'Payment::previewSlip');
    $routes->get('/payment/check-acc-status', 'Payment::checkAccStatus');
    $routes->post('/payment-dashboard/store', 'Payment::store');
    $routes->post('/payment-dashboard/update', 'Payment::update');
    $routes->delete('/payment-dashboard/delete/(:num)', 'Payment::delete/$1');
    
    // Profile
    $routes->get('/profile', 'Profile::index');
    $routes->post('/profile/update', 'Profile::update');
    $routes->post('/profile/reset-password', 'Profile::resetPassword');
    $routes->get('/profile/image/(:any)', 'Profile::getProfileImage/$1');

    // Report Transaksi
    $routes->get('/report', 'Report::index');
    $routes->post('/report/add-transaction', 'Report::addTransaction');
    $routes->post('/report/delete-transaction/(:any)', 'Report::deleteTransaction/$1');
    $routes->post('/report/update-modal-awal', 'Report::updateModalAwal');
    $routes->get('/report/export', 'Report::exportReport');
    $routes->post('/report/store-cashbon', 'Report::storeCashbon');

    // Roles Management
    $routes->get('/roles', 'Roles::index');
    $routes->post('/roles/store', 'Roles::store');
    $routes->post('/roles/update', 'Roles::update');
    $routes->delete('/roles/delete/(:num)', 'Roles::delete/$1');

    // Announcement
    $routes->get('/announcement', 'Announcement::index');
    $routes->post('/announcement/store', 'Announcement::store');
    $routes->get('/announcement/approve/(:num)', 'Announcement::approve/$1');
    $routes->get('/announcement/reject/(:num)', 'Announcement::reject/$1');
    $routes->get('/announcement/delete/(:num)', 'Announcement::delete/$1');
    $routes->get('/announcement/permissions', 'Announcement::permissions');
    $routes->get('/announcement/togglePermission/(:num)', 'Announcement::togglePermission/$1');

    // Bank File
    $routes->get('/bank-file', 'BankFile::index');
    $routes->post('/bank-file/upload', 'BankFile::upload');
    $routes->get('/bank-file/download/(:any)', 'BankFile::download/$1');
    $routes->post('/bank-file/delete/(:any)', 'BankFile::delete/$1');

    // Integration IoT
    $routes->get('/integration', 'Integration::index');
    $routes->post('/integration/process-absensi', 'Integration::processAbsensi');
    $routes->get('/integration/get-devices', 'Integration::getDevices');
    $routes->post('/integration/add-device', 'Integration::addDevice');
    $routes->post('/integration/check-device-status', 'Integration::checkDeviceStatus');
    $routes->post('/integration/refresh-all-status', 'Integration::refreshAllDeviceStatus');
    $routes->post('/integration/remove-device', 'Integration::removeDevice');
    $routes->get('/integration/get-summary', 'Integration::getSummary');
    $routes->get('/integration/get-recent-activities', 'Integration::getRecentActivities');
    $routes->get('/integration/proxy', 'Integration::proxy');
    $routes->get('/integration/stream', 'Integration::stream');

    // IoT API (Bypass CSRF untuk device IoT)
    $routes->group('api/iot', ['namespace' => 'App\Controllers'], function($routes) {
        $routes->get('receive-data', 'IotController::receiveData');
        $routes->get('generate-qr/(:num)', 'IotController::generateQr/$1');
        $routes->get('generate-string', 'IotController::generateString');
    });



    // Payment Management (Admin Only)
    $routes->get('/payment-management', 'PaymentManagement::index');
    $routes->get('/payment-management/test-slip', 'PaymentManagement::testSlip');
    $routes->get('/payment-management/test-create-acc-folder', 'PaymentManagement::testCreateAccFolder');
    $routes->get('/payment-management/get-slip-data', 'PaymentManagement::getSlipData');
    $routes->get('/payment-management/get-slip-pdf-base64', 'PaymentManagement::getSlipPdfBase64');
    $routes->get('/payment-management/download-slip', 'PaymentManagement::downloadSlip');
    $routes->get('/payment-management/move-slip-to-acc', 'PaymentManagement::moveSlipToAcc');
    $routes->get('/payment-management/move-slip-from-acc', 'PaymentManagement::moveSlipFromAcc');
    $routes->get('/payment-management/check-acc-status', 'PaymentManagement::checkAccStatus');
    $routes->get('/payment-management/flush-slip-data', 'PaymentManagement::flushSlipData');
    // Experimental: Withdrawal Bypass (Admin Only)
    $routes->get('/payment-management/experimental-bypass-withdrawal', 'PaymentManagement::experimentalBypassWithdrawal');
    $routes->get('/payment-management/experimental-reset-bypass', 'PaymentManagement::experimentalResetBypass');


    // Credit Page
    $routes->get('/credit', function() {
        return view('credit');
    });

});


// API
$routes->group('api', function($routes) {
    $routes->post('login', 'Api\Auth::login');
    $routes->get('tasks', 'Api\Task::index');
    $routes->get('task/(:num)', 'Api\Task::detail/$1'); 
});
    // Attendance API
    $routes->group('api/absensi', ['namespace' => 'App\Controllers\Api'], function($routes) {
        $routes->options('submit', 'Absensi::submit');
        $routes->post('submit', 'Absensi::submit');
    });

// API ANDROID
$routes->group('api-android', ['namespace' => 'App\Controllers\ApiAndroid'], function($routes) {
    // Auth
    $routes->post('auth/login', 'Auth::login');
    $routes->get('auth/profile', 'Auth::profile');
    $routes->post('auth/profile/update', 'Auth::updateProfile');
    $routes->post('auth/profile/reset-password', 'Auth::resetPassword');
    $routes->get('profile-photo/(:any)', 'Auth::getProfileImage/$1');
    
    // Dashboard
    $routes->get('dashboard/summary', 'Dashboard::summary');
    
    // Task
    $routes->get('task-photo/(:any)', 'Task::getImage/$1');
    $routes->get('task/list', 'Task::list');
    $routes->get('task/detail/(:num)', 'Task::detail/$1');
    $routes->post('task/store', 'Task::store');
    $routes->post('task/update', 'Task::update');
    $routes->post('task/update/(:num)', 'Task::update/$1');
    $routes->delete('task/delete/(:num)', 'Task::delete/$1');
    $routes->post('task/update-status', 'Task::updateStatus');
    
    // Payment
    $routes->get('payment/history', 'Payment::history');
    $routes->post('payment/store', 'Payment::store');
    $routes->get('payment/detail/(:num)', 'Payment::detail/$1');
    $routes->get('payment/slip', 'Payment::getSlip');
    $routes->post('payment/withdraw', 'Payment::withdraw');
    $routes->post('payment/send-slip', 'Payment::sendSlip');

    // Payment Management (Admin Only)
    $routes->get('payment-management/list', 'PaymentManagement::list');
    $routes->post('payment-management/move-to-acc', 'PaymentManagement::moveToAcc');
    $routes->post('payment-management/move-from-acc', 'PaymentManagement::moveFromAcc');
    $routes->post('payment-management/flush', 'PaymentManagement::flushSlipData');
    $routes->post('payment-management/bypass', 'PaymentManagement::bypassWithdrawal');
    // IoT Integration (Admin Only)
    $routes->get('integration/summary', 'Integration::getSummary');
    $routes->get('integration/devices', 'Integration::getDevices');
    $routes->post('integration/add-device', 'Integration::addDevice');
    $routes->post('integration/remove-device', 'Integration::removeDevice');
    $routes->post('integration/refresh', 'Integration::refreshDevices');
    $routes->get('integration/activities', 'Integration::getRecentActivities');
    $routes->post('integration/heartbeat', 'Integration::heartbeat');
    $routes->post('integration/push', 'Integration::push');
    
    // Roles (Admin Only)
    $routes->get('roles/list', 'Roles::list');
    $routes->post('roles/store', 'Roles::store');
    $routes->post('roles/update/(:num)', 'Roles::update/$1');
    $routes->delete('roles/delete/(:num)', 'Roles::delete/$1');
    
    // Absensi
    $routes->get('absensi/history', 'Absensi::history');
    $routes->post('absensi/submit', 'Absensi::submit');

    // Report
    $routes->get('report/daily', 'Report::getDaily');
    $routes->post('report/transaction/add', 'Report::addTransaction');
    $routes->delete('report/transaction/delete/(:any)', 'Report::deleteTransaction/$1');
    $routes->post('report/modal-awal', 'Report::updateModalAwal');
    $routes->get('report/export', 'Report::exportDaily');

    // Announcement
    $routes->get('announcement/list', 'Announcement::list');
    $routes->post('announcement/store', 'Announcement::store');
    $routes->post('announcement/approve/(:num)', 'Announcement::approve/$1');
    $routes->post('announcement/reject/(:num)', 'Announcement::reject/$1');
    $routes->delete('announcement/delete/(:num)', 'Announcement::delete/$1');
    $routes->post('announcement/togglePermission/(:num)', 'Announcement::togglePermission/$1');

    // Bank File
    $routes->get('bank-file/list', 'BankFile::list');
    $routes->post('bank-file/upload', 'BankFile::upload');
    $routes->get('bank-file/download/(:any)', 'BankFile::download/$1');
    $routes->delete('bank-file/delete/(:any)', 'BankFile::delete/$1');
});