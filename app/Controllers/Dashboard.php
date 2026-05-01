<?php

namespace App\Controllers;

use App\Models\TaskModel;

class Dashboard extends BaseController
{
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        $model = new TaskModel();
        $data['tasks'] = $model->findAll();

        // Get current user untuk check role
        $userModel = new \App\Models\UserModel();
        $userId = session()->get('id');
        $data['user'] = $userModel->find($userId);

        return view('dashboard', $data);
    }
    public function detail($id)
{
    if (!session()->get('isLoggedIn')) {
        return redirect()->to('/');
    }

    $model = new \App\Models\TaskModel();
    $data['task'] = $model->find($id);

    return view('detail', $data);
}

    public function absensi()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        $userModel = new \App\Models\UserModel();
        $userId = session()->get('id');
        $data['user'] = $userModel->find($userId);
        $data['username'] = $data['user']['username'];

        $kehadiranModel = new \App\Models\KehadiranModel();
        $data['kehadiran'] = $kehadiranModel->where('user_id', $userId)->orderBy('tanggal', 'DESC')->findAll();

        return view('absensi', $data);
    }
}