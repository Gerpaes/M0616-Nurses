<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class NurseController extends AbstractController
{
    #[Route('/nurse/index', name: 'nurse_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->getNurses());
    }

    #[Route('/nurse/login', name: 'nurse_login', methods: ['POST'])]
    public function loginNurses(Request $request): JsonResponse{
        $data = json_decode($request->getContent(), true);
        if(!is_array($data) || empty ($data ['user']) || empty ($data ['password'])){
            return $this->json([
                'success' => false,
                'message' => 'El usuario y la contraseña son obligatorios',
            ], JsonResponse ::HTTP_BAD_REQUEST);

        }

        foreach($this->getNurses() as $nurse){
            if ($nurse['username'] === $data['user'] && $nurse['password'] === $data['password']){
                return $this->json([
                    'success' => true,
                    'message' => 'Login correcto',
                ]);
            }
            
        }
        return $this->json([
            'success' => false,
            'message' => 'Usuario o contraseña incorrectos',
        ], JsonResponse::HTTP_UNAUTHORIZED);

    }

    private function getNurses(): array
    {
        $path = $this->getParameter('kernel.project_dir') . '/data/nurses.json';
        $json = file_get_contents($path);

        return json_decode($json, true);

    }
}
