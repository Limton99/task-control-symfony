<?php

namespace App\Controller;

use App\Model\Request\TaskRequest;
use App\Model\Request\UpdateTaskRequest;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/task')]
class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page  = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        return $this->json($this->taskService->list($page, $limit));
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get($id): JsonResponse
    {
        return $this->json($this->taskService->get($id));
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(#[MapRequestPayload] TaskRequest $request): JsonResponse
    {
        return $this->json($this->taskService->create($request));
    }

    #[Route('/{id}/update', name: 'update', methods: ['POST'])]
    public function update($id, #[MapRequestPayload] UpdateTaskRequest $taskRequest): JsonResponse
    {
        return $this->json($this->taskService->update($id, $taskRequest));
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        return $this->json($this->taskService->delete($id));
    }
}
