<?php

namespace App\Entity;

use App\Repository\NurseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NurseRepository::class)]
#[UniqueEntity('internalId')]
#[UniqueEntity('licenseNumber')]
#[UniqueEntity('username')]
#[UniqueEntity('email')]
class Nurse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 20)]
    private ?string $internalId = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 100)]
    #[Assert\Regex(pattern: '/^[\p{L} ]+$/u', message: 'El nombre solo puede contener letras y espacios')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 100)]
    #[Assert\Regex(pattern: '/^[\p{L} ]+$/u', message: 'El apellido solo puede contener letras y espacios')]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 20)]
    private ?string $licenseNumber = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 50)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['morning', 'afternoon', 'night'])]
    private ?string $shift = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInternalId(): ?string
    {
        return $this->internalId;
    }

    public function setInternalId(string $internalId): static
    {
        $this->internalId = $internalId;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(string $licenseNumber): static
    {
        $this->licenseNumber = $licenseNumber;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getShift(): ?string
    {
        return $this->shift;
    }

    public function setShift(string $shift): static
    {
        $this->shift = $shift;

        return $this;
    }
}