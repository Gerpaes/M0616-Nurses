<?php

namespace App\Controller;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;


final class NurseController extends AbstractController
{
    #[Route('/nurse', name: 'app_nurse_index', methods: ['GET'])]
    public function index(NurseRepository $nurseRepository): JsonResponse
    {
        return $this->json(array_map($this->toArray(...), $nurseRepository->findAll()));
    }

    #[Route('/nurse/new', name: 'app_nurse_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['errors' => ['body' => 'Invalid JSON']], JsonResponse::HTTP_BAD_REQUEST);
        }

        $nurse = new Nurse();
        $this->fill($nurse, $data);

        $errors = $this->validationErrors($nurse, $validator);
        if ($errors) {
            return $this->json(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($nurse);
        $entityManager->flush();

        return $this->json($this->toArray($nurse), JsonResponse::HTTP_CREATED);
    }

    #[Route('/nurse/{id<\d+>}', name: 'app_nurse_show', methods: ['GET'])]
    public function show(Nurse $nurse): JsonResponse
    {
        return $this->json($this->toArray($nurse));
    }

    #[Route('/nurse/{id<\d+>}', name: 'nurse_update', methods: ['PUT'])]
    public function edit(Request $request, ?Nurse $nurse, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        if (!$nurse) {
            return $this->json(['error' => 'Enfermero no encontrado'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !$data) {
            return $this->json(['error' => 'Datos no válidos'], 400);
        }

        if (array_key_exists('internalId', $data)) {
            return $this->json(['error' => 'El ID interno no se puede modificar'], 400);
        }

        $allowedFields = ['firstName', 'lastName', 'licenseNumber', 'username', 'password', 'email', 'shift'];
        foreach ($data as $field => $value) {
            if (!in_array($field, $allowedFields, true)) {
                return $this->json(['error' => "El campo '$field' no existe o no se puede modificar"], 400);
            }
            if (!is_string($value)) {
                return $this->json(['error' => "El campo '$field' debe ser un texto"], 400);
            }
        }

        $this->fill($nurse, $data);

        $errors = $this->validationErrors($nurse, $validator);
        if ($errors) {
            return $this->json(['errors' => $errors], 400);
        }

        $entityManager->flush();

        return $this->json($this->toArray($nurse), 200);
    }

    #[Route('/nurse/{id<\d+>}', name: 'app_nurse_delete', methods: ['DELETE'])]
    public function delete(Nurse $nurse, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($nurse);
        $entityManager->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/nurse/index', name: 'nurse_index', methods: ['GET'])]
    public function apiIndex(NurseRepository $nurseRepository): JsonResponse
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
    public function loginNurses(Request $request, NurseRepository $nurseRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || empty($data['user']) || empty($data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'El usuario y la contraseña son obligatorios',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        
        $nurse = $nurseRepository->findOneBy(['username' => $data['user']]);


        if ($nurse === null || $nurse->getPassword() !== $data['password']) {
            return $this->json([
                'success' => false,
                'message' => 'Usuario o contraseña incorrectos',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

         return $this->json([
            'success' => true,
            'message' => 'Login correcto',
        ]);
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

    private function toArray(Nurse $nurse): array
    {
        return [
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

    private function fill(Nurse $nurse, array $data): void
    {
        $setters = [
            'internalId' => $nurse->setInternalId(...),
            'firstName' => $nurse->setFirstName(...),
            'lastName' => $nurse->setLastName(...),
            'licenseNumber' => $nurse->setLicenseNumber(...),
            'username' => $nurse->setUsername(...),
            'password' => $nurse->setPassword(...),
            'email' => $nurse->setEmail(...),
            'shift' => $nurse->setShift(...),
        ];

        foreach ($setters as $field => $setter) {
            if (array_key_exists($field, $data)) {
                $setter((string) $data[$field]);
            }
        }
    }

    private function validationErrors(Nurse $nurse, ValidatorInterface $validator): array
    {
        $errors = [];
        foreach ($validator->validate($nurse) as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }

        return $errors;
    }


}
