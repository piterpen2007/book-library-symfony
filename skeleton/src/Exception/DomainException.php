<?php

namespace EfTech\BookLibrary\Exception;


/**
 * Выбрасывает исключение, если значеине ге соответствует определенной допустимой области данных
 */
class DomainException extends \DomainException implements ExceptionInterface
{
}
