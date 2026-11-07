<?php

namespace App\Controller;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
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
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        // 1. Leer el JSON
        try {
            $data = $request->toArray();
        } catch (JsonException) {
            return $this->json(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // 2. Comprobar tipos: los setters de la entidad exigen string
        if (isset($data['license_number']) && is_int($data['license_number'])) {
            $data['license_number'] = (string) $data['license_number'];
        }

        $fields = ['internal_id', 'first_name', 'last_name', 'license_number', 'username', 'email', 'shift', 'password'];
        $errors = [];
        foreach ($fields as $field) {
            if (isset($data[$field]) && !is_string($data[$field])) {
                $errors[$field][] = 'Must be a string';
            }
        }
        if ($errors) {
            return $this->json(['errors' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 3. Validar la entidad con las restricciones definidas en Nurse.
        $nurse = new Nurse();
        $nurse->setInternalId($data['internal_id'] ?? '');
        $nurse->setFirstName($data['first_name'] ?? '');
        $nurse->setLastName($data['last_name'] ?? '');
        $nurse->setLicenseNumber($data['license_number'] ?? '');
        $nurse->setUsername($data['username'] ?? '');
        $nurse->setPassword($data['password'] ?? '');
        $nurse->setEmail($data['email'] ?? '');
        $nurse->setShift($data['shift'] ?? '');

        // 4. Validar las restricciones declaradas en la entidad.
        foreach ($validator->validate($nurse) as $violation) {
            $property = $this->toSnakeCase($violation->getPropertyPath());
            $errors[$property][] = $violation->getMessage();
        }

        if ($errors) {
            return $this->json(['errors' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $nurse->setPassword(password_hash($nurse->getPassword(), PASSWORD_BCRYPT));

        // 5. Guardar
        try {
            $entityManager->persist($nurse);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Dos peticiones simultáneas pueden pasar la validación a la vez
            return $this->json(['error' => 'Nurse already exists'], JsonResponse::HTTP_CONFLICT);
        }

        // 6. Responder sin el password
        return $this->json([
            'id' => $nurse->getId(),
            'internal_id' => $nurse->getInternalId(),
            'first_name' => $nurse->getFirstName(),
            'last_name' => $nurse->getLastName(),
            'license_number' => $nurse->getLicenseNumber(),
            'username' => $nurse->getUsername(),
            'email' => $nurse->getEmail(),
            'shift' => $nurse->getShift(),
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/nurse/{id<\d+>}', name: 'app_nurse_show', methods: ['GET'])]
    public function show(?Nurse $nurse): JsonResponse
    {
        if (!$nurse) {
            return $this->json(['error' => 'Nurse not found'], JsonResponse::HTTP_NOT_FOUND);
        }

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
    public function delete(int $id, NurseRepository $nurseRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $nurse = $nurseRepository->find($id);

        if (!$nurse) {
            return $this->json([
                'error' => 'Nurse not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($nurse);
        $entityManager->flush();

        return $this->json([
            'message' => 'Nurse deleted successfully'
        ], JsonResponse::HTTP_OK);
    }

    #[Route('/nurse/index', name: 'nurse_index', methods: ['GET'])]
    public function getAll(NurseRepository $nurseRepository): JsonResponse
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
    public function login(Request $request, NurseRepository $nurseRepository): JsonResponse
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

    #[Route('/nurse/name/{name}', name: 'app_nurse_find_by_name', methods: ['GET'])]
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

    private function toSnakeCase(string $value): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

}
