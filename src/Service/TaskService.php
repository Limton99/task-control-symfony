<?php

namespace App\Service;

use App\Model\Request\TaskRequest;
use App\Model\Request\UpdateTaskRequest;

interface TaskService
{
    public function list(int $page, int $limit);
    public function get($id);
    public function create(TaskRequest $request);
    public function update($id, UpdateTaskRequest $taskRequest);
    public function delete($id);
}
