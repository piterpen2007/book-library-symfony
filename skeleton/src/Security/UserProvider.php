<?php

namespace EfTech\BookLibrary\Security;

use Doctrine\ORM\EntityManagerInterface;
use EfTech\BookLibrary\Entity\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 */
class UserProvider implements UserProviderInterface
{
    /**
     *
     *
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $em;
    /**
     * Сервис поиска пользователя
     * @var UserRepositoryInterface
     */
    private UserRepositoryInterface $userRepository;

    /**
     * @param UserRepositoryInterface $userRepository
     * @param EntityManagerInterface $em
     */
    public function __construct(UserRepositoryInterface $userRepository, \Doctrine\ORM\EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->em = $em;
    }


    public function refreshUser(UserInterface $user): UserInterface
    {
        $userFromDom = $this->userRepository->findUserByLogin($user->getUserIdentifier());
        $this->em->refresh($userFromDom);
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }

    public function loadUserByUsername(string $username)
    {
         return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $userFromDom = $this->userRepository->findUserByLogin($identifier);
        if (null === $userFromDom) {
            throw new UserNotFoundException("Пользователь не найден $identifier");
        }
        return new User($userFromDom);
    }
}