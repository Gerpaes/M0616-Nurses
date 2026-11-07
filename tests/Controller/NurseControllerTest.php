<?php

namespace App\Tests\Controller;

use App\Entity\Nurse;
use App\Repository\NurseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NurseControllerTest extends WebTestCase
{
    private const PASSWORD = 'Secret123!';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Seguridad: los tests solo pueden ejecutarse contra SQLite (var/test.db),
        // nunca contra la base de datos de desarrollo. No abre conexión para comprobarlo.
        $params = $this->em()->getConnection()->getParams();
        if (($params['driver'] ?? null) !== 'pdo_sqlite') {
            self::fail('Los tests deben usar SQLite. Revisa DATABASE_URL en .env.test.');
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /** Texto único corto (12 caracteres hex). */
    private function token(): string
    {
        return bin2hex(random_bytes(6));
    }

    /** Letras aleatorias a-z, válidas para el Regex de nombre y apellido. */
    private function randomLetters(int $length = 8): string
    {
        $letters = '';
        foreach (str_split(random_bytes($length)) as $byte) {
            $letters .= chr(ord('a') + ord($byte) % 26);
        }

        return ucfirst($letters);
    }

    /** Payload válido para POST /nurse/new (claves en snake_case). */
    private function validPayload(array $overrides = []): array
    {
        $token = $this->token();

        return array_merge([
            'internal_id' => 'INT-'.$token,
            'first_name' => $this->randomLetters(),
            'last_name' => 'Garcia',
            'license_number' => 'LIC-'.$token,
            'username' => 'user_'.$token,
            'email' => $token.'@example.com',
            'shift' => 'morning',
            'password' => self::PASSWORD,
        ], $overrides);
    }

    /** Crea un enfermero directamente con Doctrine (contraseña en texto plano). */
    private function createNurseInDb(): Nurse
    {
        $token = $this->token();

        $nurse = (new Nurse())
            ->setInternalId('INT-'.$token)
            ->setFirstName($this->randomLetters())
            ->setLastName('Lopez')
            ->setLicenseNumber('LIC-'.$token)
            ->setUsername('user_'.$token)
            ->setPassword(self::PASSWORD)
            ->setEmail($token.'@example.com')
            ->setShift('afternoon');

        $em = $this->em();
        $em->persist($nurse);
        $em->flush();

        return $nurse;
    }

    /** Devuelve un id que seguro que no existe (crea un enfermero y lo borra). */
    private function nonExistentId(): int
    {
        $nurse = $this->createNurseInDb();
        $id = $nurse->getId();

        $em = $this->em();
        $em->remove($nurse);
        $em->flush();

        return $id;
    }

    private function findNurse(int $id): ?Nurse
    {
        return static::getContainer()->get(NurseRepository::class)->find($id);
    }

    private function jsonRequest(string $method, string $uri, array|string $body): void
    {
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            is_array($body) ? json_encode($body) : $body,
        );
    }

    private function responseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    // ------------------------------------------------------------------
    // index: GET /nurse
    // ------------------------------------------------------------------

    public function testIndexReturnsListOfNurses(): void
    {
        // Un enfermero creado debe aparecer en el listado, sin contraseña
        $nurse = $this->createNurseInDb();

        $this->client->request('GET', '/nurse');

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseData();
        self::assertContains($nurse->getId(), array_column($data, 'id'));
        self::assertArrayNotHasKey('password', $data[0]);
    }

    public function testIndexWithPostReturns405(): void
    {
        // /nurse solo acepta GET
        $this->client->request('POST', '/nurse');

        self::assertResponseStatusCodeSame(405);
    }

    // ------------------------------------------------------------------
    // new: POST /nurse/new
    // ------------------------------------------------------------------

    public function testNewCreatesNurseAndReturns201(): void
    {
        // Datos válidos: 201 con el enfermero en snake_case y sin contraseña
        $payload = $this->validPayload();

        $this->jsonRequest('POST', '/nurse/new', $payload);

        self::assertResponseStatusCodeSame(201);
        $data = $this->responseData();
        self::assertIsInt($data['id']);
        self::assertSame($payload['username'], $data['username']);
        self::assertSame($payload['first_name'], $data['first_name']);
        self::assertArrayNotHasKey('password', $data);
    }

    public function testNewStoresHashedPassword(): void
    {
        // La contraseña se guarda con hash, no en texto plano
        $this->jsonRequest('POST', '/nurse/new', $this->validPayload());
        self::assertResponseStatusCodeSame(201);

        $nurse = $this->findNurse($this->responseData()['id']);
        self::assertNotSame(self::PASSWORD, $nurse->getPassword());
        self::assertTrue(password_verify(self::PASSWORD, $nurse->getPassword()));
    }

    public function testNewWithBrokenJsonReturns400(): void
    {
        // JSON mal formado: 400
        $this->jsonRequest('POST', '/nurse/new', '{"username": ');

        self::assertResponseStatusCodeSame(400);
    }

    public function testNewWithMissingFieldsReturns422(): void
    {
        // JSON válido pero vacío: 422 con un error por cada campo obligatorio
        $this->jsonRequest('POST', '/nurse/new', []);

        self::assertResponseStatusCodeSame(422);
        $errors = $this->responseData()['errors'];
        foreach (['internal_id', 'first_name', 'last_name', 'license_number', 'username', 'password', 'email', 'shift'] as $field) {
            self::assertArrayHasKey($field, $errors);
        }
    }

    public function testNewWithInvalidShiftReturns422(): void
    {
        // Turno fuera de morning/afternoon/night: 422
        $this->jsonRequest('POST', '/nurse/new', $this->validPayload(['shift' => 'evening']));

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('shift', $this->responseData()['errors']);
    }

    public function testNewWithNumbersInNameReturns422(): void
    {
        // El nombre solo admite letras y espacios: 422
        $this->jsonRequest('POST', '/nurse/new', $this->validPayload(['first_name' => 'Ana123']));

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('first_name', $this->responseData()['errors']);
    }

    public function testNewWithDuplicateUsernameReturns422(): void
    {
        // Username ya existente (UniqueEntity): 422
        $existing = $this->createNurseInDb();

        $this->jsonRequest('POST', '/nurse/new', $this->validPayload(['username' => $existing->getUsername()]));

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('username', $this->responseData()['errors']);
    }

    // ------------------------------------------------------------------
    // show: GET /nurse/{id}
    // ------------------------------------------------------------------

    public function testShowReturnsNurse(): void
    {
        // Id existente: 200 con sus datos
        $nurse = $this->createNurseInDb();

        $this->client->request('GET', '/nurse/'.$nurse->getId());

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseData();
        self::assertSame($nurse->getId(), $data['id']);
        self::assertSame($nurse->getUsername(), $data['username']);
        self::assertArrayNotHasKey('password', $data);
    }

    public function testShowWithUnknownIdReturns404(): void
    {
        // Id inexistente: 404
        $this->client->request('GET', '/nurse/'.$this->nonExistentId());

        self::assertResponseStatusCodeSame(404);
    }

    // ------------------------------------------------------------------
    // edit: PUT /nurse/{id}
    // ------------------------------------------------------------------

    public function testEditUpdatesNurse(): void
    {
        // Cambio válido: 200 y el cambio queda guardado
        $nurse = $this->createNurseInDb();
        $newName = $this->randomLetters();

        $this->jsonRequest('PUT', '/nurse/'.$nurse->getId(), ['firstName' => $newName, 'shift' => 'night']);

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseData();
        self::assertSame($newName, $data['firstName']);
        self::assertSame('night', $data['shift']);
        self::assertSame($newName, $this->findNurse($nurse->getId())->getFirstName());
    }

    public function testEditWithUnknownIdReturns404(): void
    {
        // Id inexistente: 404
        $this->jsonRequest('PUT', '/nurse/'.$this->nonExistentId(), ['firstName' => 'Lucia']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testEditWithBrokenJsonReturns400(): void
    {
        // JSON mal formado: 400
        $nurse = $this->createNurseInDb();

        $this->jsonRequest('PUT', '/nurse/'.$nurse->getId(), '{"firstName": ');

        self::assertResponseStatusCodeSame(400);
    }

    public function testEditInternalIdReturns422(): void
    {
        // internalId no se puede modificar: 422 y no cambia
        $nurse = $this->createNurseInDb();
        $originalInternalId = $nurse->getInternalId();

        $this->jsonRequest('PUT', '/nurse/'.$nurse->getId(), ['internalId' => 'INT-'.$this->token()]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame($originalInternalId, $this->findNurse($nurse->getId())->getInternalId());
    }

    public function testEditUnknownFieldReturns422(): void
    {
        // Campo que no existe en Nurse: 422
        $nurse = $this->createNurseInDb();

        $this->jsonRequest('PUT', '/nurse/'.$nurse->getId(), ['age' => '30']);

        self::assertResponseStatusCodeSame(422);
    }

    public function testEditNonStringValueReturns422(): void
    {
        // Valor que no es texto: 422
        $nurse = $this->createNurseInDb();

        $this->jsonRequest('PUT', '/nurse/'.$nurse->getId(), ['firstName' => 123]);

        self::assertResponseStatusCodeSame(422);
    }

    public function testEditNameWithNumbersReturns422(): void
    {
        // Nombre con números (Regex de la entidad): 422
        $nurse = $this->createNurseInDb();

        $this->jsonRequest('PUT', '/nurse/'.$nurse->getId(), ['firstName' => 'Ana123']);

        self::assertResponseStatusCodeSame(422);
    }

    // ------------------------------------------------------------------
    // delete: DELETE /nurse/{id}
    // ------------------------------------------------------------------

    public function testDeleteRemovesNurse(): void
    {
        // Id existente: 200 y el enfermero ya no está en la base de datos
        $nurse = $this->createNurseInDb();
        $id = $nurse->getId();

        $this->client->request('DELETE', '/nurse/'.$id);

        self::assertResponseStatusCodeSame(200);
        self::assertSame('Nurse deleted successfully', $this->responseData()['message']);
        self::assertNull($this->findNurse($id));
    }

    public function testDeleteWithUnknownIdReturns404(): void
    {
        // Id inexistente: 404
        $this->client->request('DELETE', '/nurse/'.$this->nonExistentId());

        self::assertResponseStatusCodeSame(404);
    }

    // ------------------------------------------------------------------
    // getAll: GET /nurse/index
    // ------------------------------------------------------------------

    public function testGetAllReturnsListOfNurses(): void
    {
        // Un enfermero creado debe aparecer en el listado, sin contraseña
        $nurse = $this->createNurseInDb();

        $this->client->request('GET', '/nurse/index');

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseData();
        self::assertContains($nurse->getId(), array_column($data, 'id'));
        self::assertArrayNotHasKey('password', $data[0]);
    }

    public function testGetAllWithPostReturns405(): void
    {
        // /nurse/index solo acepta GET
        $this->client->request('POST', '/nurse/index');

        self::assertResponseStatusCodeSame(405);
    }

    // ------------------------------------------------------------------
    // login: POST /nurse/login
    // ------------------------------------------------------------------

    public function testLoginWithValidCredentialsReturns200(): void
    {
        // Enfermero creado con Doctrine (texto plano): login correcto
        $nurse = $this->createNurseInDb();

        $this->jsonRequest('POST', '/nurse/login', ['user' => $nurse->getUsername(), 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(200);
        self::assertTrue($this->responseData()['success']);
    }

    public function testLoginWithWrongPasswordReturns401(): void
    {
        // Contraseña incorrecta: 401
        $nurse = $this->createNurseInDb();

        $this->jsonRequest('POST', '/nurse/login', ['user' => $nurse->getUsername(), 'password' => 'wrong-password']);

        self::assertResponseStatusCodeSame(401);
        self::assertFalse($this->responseData()['success']);
    }

    public function testLoginWithUnknownUserReturns401(): void
    {
        // Usuario que no existe: 401
        $this->jsonRequest('POST', '/nurse/login', ['user' => 'nobody_'.$this->token(), 'password' => self::PASSWORD]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginWithBrokenJsonReturns400(): void
    {
        // JSON mal formado: 400
        $this->jsonRequest('POST', '/nurse/login', '{"user": ');

        self::assertResponseStatusCodeSame(400);
    }

    public function testLoginWithGetReturns405(): void
    {
        // /nurse/login solo acepta POST
        $this->client->request('GET', '/nurse/login');

        self::assertResponseStatusCodeSame(405);
    }

    // ------------------------------------------------------------------
    // findByName: GET /nurse/name/{name}
    // ------------------------------------------------------------------

    public function testFindByNameReturnsNurse(): void
    {
        // Nombre existente (aleatorio, único): 200 con sus datos en snake_case
        $nurse = $this->createNurseInDb();

        $this->client->request('GET', '/nurse/name/'.$nurse->getFirstName());

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseData();
        self::assertSame($nurse->getId(), $data['id']);
        self::assertSame($nurse->getFirstName(), $data['first_name']);
        self::assertArrayNotHasKey('password', $data);
    }

    public function testFindByNameWithUnknownNameReturns404(): void
    {
        // Nombre que no existe: 404
        $this->client->request('GET', '/nurse/name/'.$this->randomLetters(12));

        self::assertResponseStatusCodeSame(404);
    }
}