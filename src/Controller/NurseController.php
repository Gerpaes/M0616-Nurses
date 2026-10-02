<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class NurseController extends AbstractController
{
    #[Route('/nurse', name: 'app_nurse')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/NurseController.php',
        ]);
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
