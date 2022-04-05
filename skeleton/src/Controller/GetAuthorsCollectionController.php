<?php

namespace EfTech\BookLibrary\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use EfTech\BookLibrary\Service\SearchAuthorsService;
use EfTech\BookLibrary\Service\SearchAuthorsService\AuthorDto;
use EfTech\BookLibrary\Service\SearchAuthorsService\SearchAuthorsCriteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;


/** Контроллер поиска авторов
 *
 */
class GetAuthorsCollectionController extends AbstractController
{
    /**
     * Сервис валидации
     *
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;
    /** Логгер
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     *
     *
     * @var SearchAuthorsService
     */
    private SearchAuthorsService $searchAuthorsService;

    /**
     * @param LoggerInterface $logger
     * @param SearchAuthorsService $searchAuthorsService
     * @param ValidatorInterface $validator
     */
    public function __construct(
        LoggerInterface $logger,
        SearchAuthorsService $searchAuthorsService,
        ValidatorInterface $validator
    ) {
        $this->logger = $logger;
        $this->searchAuthorsService = $searchAuthorsService;
        $this->validator = $validator;
    }

    /** Валидирует параметры запроса
     * @param Request $serverRequest - объект серверного http запроса
     * @return string|null - строка с ошибкой или нулл если ошибки нет
     * @throws Exception
     */
    private function validateQueryParams(Request $serverRequest): ?string
    {

        $constraint = [
            new Assert\Collection(
                [
                    'allowExtraFields' => true,
                    'allowMissingFields' => true,
                    'fields' => [
                        'surname' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect author surname']),
                            ]
                        ),
                        'id' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect author id']),
                            ]
                        ),
                        'name' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect author name']),
                            ]
                        ),
                        'birthday' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect author birthday']),
                            ]
                        ),
                        'country' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect author country']),
                            ]
                        ),
                    ]
                ]
            ),
        ];

        $params = array_merge($serverRequest->query->all(), $serverRequest->attributes->all());
        $errors = $this->validator->validate($params, $constraint);
        $errStrCollection =  array_map(static function($v) {
            return $v->getMessage();
        },$errors->getIterator()->getArrayCopy());

        return count($errStrCollection) > 0 ? implode(',', $errStrCollection) : null;
    }

    /**
     *
     *
     *
     * @param Request $request - серверный объект запроса
     * @return Response - объект http ответа
     * @throws Exception
     */
    public function __invoke(Request $request): Response
    {
        $this->logger->info("Ветка authors");

        $resultOfParamValidation = $this->validateQueryParams($request);

        if (null === $resultOfParamValidation) {
            $params = array_merge($request->query->all(), $request->attributes->all());
            $foundAuthors = $this->searchAuthorsService->search(
                (new SearchAuthorsCriteria())
                    ->setId(isset($params['id']) ? (int)$params['id'] : null)
                    ->setName($params['name'] ?? null)
                    ->setBirthday($params['birthday'] ?? null)
                    ->setCountry($params['country'] ?? null)
                    ->setSurname($params['surname'] ?? null)
            );

            $httpCode = $this->buildHttpCode($foundAuthors);
            $result = $this->buildResult($foundAuthors);
        } else {
            $httpCode = 500;
            $result = [
                'status' => 'fail',
                'message' => $resultOfParamValidation
            ];
        }
        return $this->json($result, $httpCode);
    }

    /** Определяет http code
     * @param array $foundAuthors
     * @return int
     */
    protected function buildHttpCode(array $foundAuthors): int
    {
        return 200;
    }

    /** Подготавливает данные для ответа
     * @param array $foundAuthors
     * @return array
     */
    protected function buildResult(array $foundAuthors)
    {
        $result = [];
        foreach ($foundAuthors as $foundAuthor) {
            $result[] = $this->serializeAuthor($foundAuthor);
        }
        return $result;
    }

    /**
     * @param AuthorDto $authorDto
     * @return array
     */
    final protected function serializeAuthor(AuthorDto $authorDto): array
    {
        return [
            'id' => $authorDto->getId(),
            'name' => $authorDto->getName(),
            'surname' => $authorDto->getSurname(),
            'birthday' => $authorDto->getBirthday()->format('d.m.Y'),
            'country' => $authorDto->getCountry(),
        ];
    }
}
