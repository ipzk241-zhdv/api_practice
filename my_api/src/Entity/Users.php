<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: "users")] // Ensure the table name is something other than 'user'
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", unique: true)]
    private string $login;

    #[ORM\Column(type: "string")]
    private string $password;

    public function getUserIdentifier(): string
    {
        return $this->login; // змінив на login
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
        // можна додати код для очищення чутливих даних, якщо потрібно
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    // Реалізація методу з PasswordAuthenticatedUserInterface
    public function getUserPassword(): string
    {
        return $this->password;
    }
}
