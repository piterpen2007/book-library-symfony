<?php

namespace EfTech\BookLibrary\Repository\UserRepository;

use Doctrine\ORM\Mapping as ORM;
use EfTech\BookLibrary\Entity\User;

/**
 * Поставщик данных о логине/пароле пользователя
 *
 * @ORM\Entity(repositoryClass=\EfTech\BookLibrary\Repository\UserDoctrineRepository::class)
 * @ORM\Table(
 *     name="users",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="users_login_unq", columns={"login"})
 *     }
 * )
 */
class UserDataProvider extends User
{
}
