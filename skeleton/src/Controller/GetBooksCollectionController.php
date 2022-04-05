<?php

namespace EfTech\BookLibrary\Controller;

use Exception;
use Psr\Log\LoggerInterface;
use EfTech\BookLibrary\Service\SearchTextDocumentService;
use EfTech\BookLibrary\Service\SearchTextDocumentService\SearchTextDocumentServiceCriteria;
use EfTech\BookLibrary\Service\SearchTextDocumentService\TextDocumentDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Контроллер поиска книг
 *
 */
class GetBooksCollectionController extends AbstractController
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
     * @var SearchTextDocumentService
     */
    private SearchTextDocumentService $searchTextDocumentService;

    /**
     * @param LoggerInterface $logger
     * @param SearchTextDocumentService $searchTextDocumentService
     * @param ValidatorInterface $validator
     */
    public function __construct(
        LoggerInterface $logger,
        SearchTextDocumentService $searchTextDocumentService,
        ValidatorInterface $validator
    ) {
        $this->logger = $logger;
        $this->searchTextDocumentService = $searchTextDocumentService;
        $this->validator = $validator;
    }


    /**  Валдирует параматры запроса
     * @param Request $request
     * @return string|null
     * @throws Exception
     */
    private function validateQueryParams(Request $request): ?string
    {
        $constraint = [
            new Assert\Collection(
                [
                    'allowExtraFields' => true,
                    'allowMissingFields' => true,
                    'fields' => [
                        'title' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect book title']),
                            ]
                        ),
                        'id' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect book id']),
                            ]
                        ),
                        'year' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect book year']),
                            ]
                        ),
                        'type' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect book type']),
                            ]
                        ),
                        'author_country' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect author country']),
                            ]
                        ),
                        'author_id' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect author id']),
                            ]
                        ),
                        'author_name' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect author name']),
                            ]
                        ),
                        'author_surname' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect author surname']),
                            ]
                        ),
                        'author_birthday' => new Assert\Optional(
                            [
                                new Assert\Type(['type' => 'string','message' => 'incorrect author birthday']),
                            ]
                        ),
                    ]
                ]
            ),
        ];

        $params = array_merge($request->query->all(), $request->attributes->all());
        $errors = $this->validator->validate($params, $constraint);
        $errStrCollection =  array_map(static function($v) {
            return $v->getMessage();
        },$errors->getIterator()->getArrayCopy());

        return count($errStrCollection) > 0 ? implode(',', $errStrCollection) : null;
    }


    /** Реализация поиска книг по критериям
     *
     *
     * @param Request $request - серверный объект запроса
     * @return Response - объект http ответа
     * @throws Exception
     */
    public function __invoke(Request $request): Response
    {
        $this->logger->info("Ветка books");
        $resultOfParamValidation = $this->validateQueryParams($request);
        if (null === $resultOfParamValidation) {
            $params = array_merge($request->query->all(), $request->attributes->all());
            $foundTextDocuments = $this->searchTextDocumentService
                ->search(
                    (new SearchTextDocumentServiceCriteria())
                        ->setAuthorSurname($params['author_surname'] ?? null)
                        ->setId($params['id'] ?? null)
                        ->setTitle($params['title'] ?? null)
                        ->setAuthorId(isset($params['author_id']) ? (int)$params['author_id'] : null)
                        ->setAuthorName($params['author_name'] ?? null)
                        ->setAuthorBirthday($params['author_birthday'] ?? null)
                        ->setAuthorCountry($params['author_country'] ?? null)
                        ->setYear(isset($params['year']) ? (int)$params['year'] : null)
                        ->setStatus($params['status'] ?? null)
                        ->setType($params['type'] ?? null)
                );

            $result = $this->buildResult($foundTextDocuments);
            $httpCode = $this->buildHttpCode($foundTextDocuments);
        } else {
            $httpCode = 500;
            $result = [
                'status' => 'fail',
                'message' => $resultOfParamValidation
            ];
        }
        return $this->json($result, $httpCode);
    }

    /** Подготавливает данные для ответа
     * @param array $foundTextDocuments
     * @return array
     */
    protected function buildResult(array $foundTextDocuments)
    {
        $result = [];
        foreach ($foundTextDocuments as $foundTextDocument) {
            $result[] = $this->serializeTextDocument($foundTextDocument);
        }
        return $result;
    }

    /**
     *
     * @param TextDocumentDto $textDocument
     *
     * @return array
     */
    final protected function serializeTextDocument(TextDocumentDto $textDocument): array
    {
        $jsonData = [
            'id' => $textDocument->getId(),
            'title' => $textDocument->getTitle(),
            'year' => $textDocument->getYear(),
            'title_for_printing' => $textDocument->getTitleForPrinting()
        ];
        if (TextDocumentDto::TYPE_MAGAZINE === $textDocument->getType()) {
            $jsonData['number'] = $textDocument->getNumber();
        }
        $jsonData['authors'] = array_values(
            array_map(static function (SearchTextDocumentService\AuthorDto $dto) {
                return [
                    'id' => $dto->getId(),
                    'name' => $dto->getName(),
                    'surname' => $dto->getSurname(),
                    'birthday' => $dto->getBirthday()->format('d.m.Y'),
                    'country' => $dto->getCountry(),
                ];
            }, $textDocument->getAuthors())
        );
        return $jsonData;
    }


    /** Подготавливает http code
     * @param array $foundTextDocument
     * @return int
     */
    protected function buildHttpCode(array $foundTextDocument): int
    {
        return 200;
    }
}
