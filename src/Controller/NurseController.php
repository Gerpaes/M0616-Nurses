<?php

namespace App\Controller;

use App\Repository\NurseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class NurseController extends AbstractController
{
    #[Route('/nurse/index', name: 'nurse_index', methods: ['GET'])]
    public function index(NurseRepository $nurseRepository): JsonResponse
    {
        $nurses = $nurseRepository->findAll();

        $data = [];

        foreach ($nurses as $nurse) {
            $data[] = [
                'id' => $nurse->getId(),
                'internalId' => $nurse->getInternalId(),
                'firstName' => $nurse->getFirstName(),
                'lastName' => $nurse->getLastName(),
                'licenseNumber' => $nurse->getLicenseNumber(),
                'username' => $nurse->getUsername(),
                'email' => $nurse->getEmail(),
                'shift' => $nurse->getShift(),
            ];
        }

        return $this->json($data);
    }


    #[Route('/nurse/login', name: 'nurse_login', methods: ['POST'])]
    public function loginNurses(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || empty($data['user']) || empty($data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'El usuario y la contraseña son obligatorios',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        foreach ($this->loadNurses() as $nurse) {
            if ($nurse['username'] === $data['user'] && $nurse['password'] === $data['password']) {
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

    #[Route('/nurses', name: 'nurse_get_nurses', methods: ['GET'])]
    public function getNurses(): JsonResponse
    {
        return $this->json($this->loadNurses());
    }

    private function loadNurses(): array
    {
        $path = $this->getParameter('kernel.project_dir') . '/data/nurses.json';
        $json = file_get_contents($path);

        return json_decode($json, true);
    }

    #[Route('/find-by-name/{name}', name: 'app_nurse_find_by_name', methods: ['GET'])]
    public function findByName(string $name, NurseRepository $nurseRepository): JsonResponse
    {
        $nurse = $nurseRepository->findOneBy(['firstName' => $name]);

        if (!$nurse) {
            return $this->json(['error' => 'Nurse not found'], 404);
        }

        return $this->json([
            'id' => $nurse->getId(),
            'internal_id' => $nurse->getInternalId(),
            'first_name' => $nurse->getFirstName(),
            'last_name' => $nurse->getLastName(),
            'license_number' => $nurse->getLicenseNumber(),
            'username' => $nurse->getUsername(),
            'email' => $nurse->getEmail(),
            'shift' => $nurse->getShift(),
        ]);
    }
}
