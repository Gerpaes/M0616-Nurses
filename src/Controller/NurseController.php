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
}
