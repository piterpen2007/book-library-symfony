<?php

namespace EfTech\BookLibrary\Controller;

use Psr\Log\LoggerInterface;
use EfTech\BookLibrary\Service\SearchAuthorsService;
use EfTech\BookLibrary\Service\SearchAuthorsService\AuthorDto;
use EfTech\BookLibrary\Service\SearchAuthorsService\SearchAuthorsCriteria;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/** Контроллер поиска авторов
 *
 */
class GetAuthorsCollectionController extends AbstractController
{
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
     */
    public function __construct(
        LoggerInterface $logger,
        SearchAuthorsService $searchAuthorsService
    ) {
        $this->logger = $logger;
        $this->searchAuthorsService = $searchAuthorsService;
    }

//    /** Валидирует параметры запроса
//     * @param ServerRequestInterface $serverRequest - объект серверного http запроса
//     * @return string|null - строка с ошибкой или нулл если ошибки нет
//     */
//    private function validateQueryParams(ServerRequestInterface $serverRequest): ?string
//    {
//        $paramsValidation = [
//            'surname' => 'incorrect author surname',
//            'id' => 'incorrect author id',
//            'name' => 'incorrect author name',
//            'birthday' => 'incorrect author birthday',
//            'country' => 'incorrect author country'
//        ];
//        $params = array_merge($serverRequest->getQueryParams(), $serverRequest->getAttributes());
//
//        return Assert::arrayElementsIsString($paramsValidation, $params);
//    }

    /**
     *
     *
     *
     * @param Request $request - серверный объект запроса
     * @return Response - объект http ответа
     * @throws JsonException
     */
    public function __invoke(Request $request): Response
    {
        $this->logger->info("Ветка authors");

       // $resultOfParamValidation = $this->validateQueryParams($request);
        $resultOfParamValidation = null;
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
