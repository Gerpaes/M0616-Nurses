<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class NurseController extends AbstractController
{
    #[Route('/nurse/index', name: 'nurse_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->getNurses());
    }

    private function getNurses(): array
    {
        $path = $this->getParameter('kernel.project_dir') . '/data/nurses.json';
        $json = file_get_contents($path);

        return json_decode($json, true);

    }

    #[Route('/find-by-name/{name}', name: 'app_nurse_find_by_name', methods: ['GET'])]
    public function findByname(string $name): JsonResponse
    {
        $nurses = json_decode(file_get_contents(__DIR__ . '/../../data/nurses.json'), true);

        foreach ($nurses as $nurse) {
            if (strcasecmp($nurse['first_name'], $name) === 0) {
                return $this->json($nurse);
            }
        }

        return $this->json(['error' => 'Nurse not found'], 404);
    }
}
