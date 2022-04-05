<?php

namespace EfTech\BookLibrary\Controller;

use Doctrine\ORM\EntityManagerInterface;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService\NewBookDto;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService\ResultRegisteringTextDocumentDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Symfony\Component\Routing\Annotation\Route;

/**
 *  Контроллер реализующий логику регистрации новых журналов
 */
class CreateRegisterBooksController extends AbstractController
{
    private ArrivalNewTextDocumentService $arrivalNewTextDocumentService;
    /**
     * Менеджер сущностей
     *
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @param ArrivalNewTextDocumentService $arrivalNewTextDocumentService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ArrivalNewTextDocumentService $arrivalNewTextDocumentService,
        EntityManagerInterface $entityManager
    ) {
        $this->arrivalNewTextDocumentService = $arrivalNewTextDocumentService;
        $this->entityManager = $entityManager;
    }


    /**
     * @Route("/books/register",name="book_register", methods={"POST"})
     *
     *
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        try {
            $this->entityManager->beginTransaction();
            $requestData = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $validationResult = $this->validateData($requestData);

            if (0 === count($validationResult)) {
                // Создаю dto с входными данными
                $responseDto = $this->runService($requestData);
                $httpCode = 201;
                $jsonData = $this->buildJsonData($responseDto);
            } else {
                $httpCode = 400;
                $jsonData = ['status' => 'fail','message' => implode('.', $validationResult)];
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $e) {
            $this->entityManager->rollBack();
            $httpCode = 500;
            $jsonData = ['status' => 'fail','message' => $e->getMessage()];
        }

        return $this->json($jsonData,$httpCode);
    }

    private function runService(array $requestData): ResultRegisteringTextDocumentDto
    {
        $requestDto = new NewBookDto(
            $requestData['title'],
            $requestData['year'],
            $requestData['author_id_list'],
        );

        return $this->arrivalNewTextDocumentService->registerBook($requestDto);
    }

    /** Формирует результаты для ответа на основе dto
     * @param ResultRegisteringTextDocumentDto $responseDto
     * @return array
     */
    private function buildJsonData(ResultRegisteringTextDocumentDto $responseDto): array
    {
        return [
          'id' => $responseDto->getId(),
          'title_for_printing' => $responseDto->getTitleForPrinting(),
          'status' => $responseDto->getStatus()
        ];
    }

    /** Валидирует входные данные
     * @param $requestData
     * @return array
     */
    private function validateData($requestData): array
    {
        $err = [];
        if (false === is_array($requestData)) {
            $err[] = 'Данные о новой книге не являются массивом';
        } else {
            if (false === array_key_exists('title', $requestData)) {
                $err[] = 'Отсутствует информация о заголовке книги';
            } elseif (false === is_string($requestData['title'])) {
                $err[] = 'Заголовок книги должен быть строкой';
            } elseif ('' === trim($requestData['title'])) {
                $err[] = 'Заголовок книги не может быть пустой строкой';
            }

            if (false === array_key_exists('year', $requestData)) {
                $err[] = 'Отсутствует информация о годе издания книги';
            } elseif (false === is_int($requestData['year'])) {
                $err[] = 'Год издания книги должен быть целым числом';
            } elseif ($requestData['year'] <= 0) {
                $err[] = 'Год издания книги не может быть меньше или равен нуля';
            }

            if (false === array_key_exists('author_id_list', $requestData)) {
                $err[] = 'Отсутствует информация о авторах книги';
            } elseif (false === is_array($requestData['author_id_list'])) {
                $err[] = 'список id авторов книги должен быть массивом';
            } elseif (0 === count($requestData['author_id_list'])) {
                $err[] = 'массив авторов не должен быть пустым';
            } else {
                foreach ($requestData['author_id_list'] as $authorId) {
                    if (false === is_int($authorId)) {
                        $err[] = 'список id авторов книги содержит некорректный id';
                        break;
                    }
                }
            }
        }

        return $err;
    }
}
