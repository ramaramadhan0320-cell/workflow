<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\TaskModel;

class Task extends BaseController
{
    public function index()
    {
        $model = new TaskModel();

        $tasks = $model->findAll();

        return $this->response->setJSON([
            'status' => 200,
            'data' => $tasks
        ]);
    }
}