<?php

namespace App\Model\Response;

use App\Entity\Task;

class TaskResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $status,
    ) {}

    public static function fromEntity(Task $task): self
    {
        return new self(
            id:    $task->getId(),
            title: $task->getTitle(),
            status:  $task->getStatus(),
        );
    }
}
