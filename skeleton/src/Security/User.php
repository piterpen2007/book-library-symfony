<?php

namespace EfTech\BookLibrary\Security;
use EfTech\BookLibrary\Entity\User as UserFromDomain;

/**
 * Обертка над юзером
 *
 */
class User implements
    \Symfony\Component\Security\Core\User\UserInterface, \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface
{

    /**
     * Пользователь из приложения
     *
     * @var UserFromDomain
     */
    private UserFromDomain $user;

    /**
     * @param UserFromDomain $user
     */
    public function __construct(UserFromDomain $user)
    {
        $this->user = $user;
    }

    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        return [
            UserRoleInterface::ROLE_AUTH_USER
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): ?string
    {
        return $this->user->getLogin();
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement @method string getUserIdentifier()
    }

    public function getPassword(): ?string
    {
        return $this->user->getPassword();
    }

    public function getUserIdentifier():string
    {
        return $this->user->getLogin();
    }
}