<?php

namespace App\Service\Impl;

use App\Entity\Task;
use App\Model\Request\TaskRequest;
use App\Model\Request\UpdateTaskRequest;
use App\Model\Response\HttpResponse;
use App\Model\Response\TaskResponse;
use App\Repository\TaskRepository;
use App\Service\TaskService;
use Doctrine\ORM\EntityManagerInterface;

class TaskServiceImpl implements TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly EntityManagerInterface  $em,
    ) {}

    public function list(int $page, int $limit = 20)
    {
        $offset = max(0, ($page - 1) * $limit);

        $qb = $this->taskRepository->createQueryBuilder('t')
            ->orderBy('t.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $items = $qb->getQuery()->getResult();
        $total = (int)$this->taskRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()->getSingleScalarResult();
        return new HttpResponse(
            200,
            [
                'list' => array_map(fn(Task $task) => TaskResponse::fromEntity($task), $items),
                'meta' => ['page' => $page, 'limit' => $limit, 'total' => $total]
            ]
        );
    }

    public function get($id)
    {
        return new HttpResponse(200, TaskResponse::fromEntity($this->taskRepository->find($id)));
    }

    public function create(TaskRequest $request)
    {
        $task = new Task();

        $task->setTitle($request->title);
        $task->setStatus($request->status);
        $this->em->persist($task);
        $this->em->flush();

        return new HttpResponse(201);
    }

    public function update($id, UpdateTaskRequest $taskRequest)
    {
        $task = $this->taskRepository->find($id);

        $task->setStatus($taskRequest->status);

        $this->em->flush();

        return new HttpResponse(204);
    }

    public function delete($id)
    {
        $task = $this->taskRepository->find($id);

        $this->em->remove($task);

        $this->em->flush();

        return new HttpResponse(204);
    }
}
