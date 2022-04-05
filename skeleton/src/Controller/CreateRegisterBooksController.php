<?php

namespace EfTech\BookLibrary\Controller;

use Doctrine\ORM\EntityManagerInterface;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService\NewBookDto;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService\ResultRegisteringTextDocumentDto;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *  Контроллер реализующий логику регистрации новых журналов
 */
class CreateRegisterBooksController extends AbstractController
{
    /**
     * Сервис валидации
     *
     * @var ValidatorInterface
     */
    private ValidatorInterface  $validator;
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
     * @param ValidatorInterface $validator
     */
    public function __construct(
        ArrivalNewTextDocumentService $arrivalNewTextDocumentService,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        $this->arrivalNewTextDocumentService = $arrivalNewTextDocumentService;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }


    /**
     * @Route("/books/register", name="book_register", methods={"POST"})
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
     * @throws Exception
     */
    private function validateData($requestData): array
    {
        $constraints = [
            new Assert\Type(['type' => 'array', 'message' => 'Данные о новой книге не являются массивом']),
            new Assert\Collection([
                'allowExtraFields'     => false,
                'allowMissingFields'   => false,
                'missingFieldsMessage' => 'Отсутствует обязательное поле: {{ field }}',
                'extraFieldsMessage'   => 'Есть лишние поля: {{ field }}',
                'fields'               => [
                    'title'          => [
                        new Assert\Type(['type' => 'string', 'message' => 'Заголовок книги должен быть строкой']),
                        new Assert\NotBlank([
                            'message'    => 'Заголовок книги не может быть пустой строкой',
                            'normalizer' => 'trim'
                        ]),
                        new Assert\Length([
                            'min'        => 1,
                            'max'        => 255,
                            'minMessage' => 'Некорректная длина заголовка книги. Необходимо {{ limit }} символов',
                            'maxMessage' => 'Некорректная длина заголовка книги. Максимальное количество {{ limit }} символов'
                        ])
                    ],
                    'year'           => [
                        new Assert\Type(['type' => 'int', 'message' => 'Год издания книги должен быть числом']),
                        new Assert\Positive(['message' => 'Год издания книги не может быть меньше или равным 0'])
                    ],
                    'author_id_list' => [
                        new Assert\Type(['type' => 'array', 'message' => 'Список авторов книги должен быть массивом']),
                        new Assert\Count(
                            ['min' => 1, 'minMessage' => 'Список авторов книги должен содержать хотя бы один элемент']
                        ),
                        new Assert\All([
                            new Assert\Type(
                                [
                                    'type'    => 'int',
                                    'message' => 'Список авторов книги содержит некорректные идентификаторы'
                                ]
                            )
                        ])
                    ]
                ]
            ]),
        ];

        $err = $this->validator->validate($requestData, $constraints);

        return array_map(static function ($v) {
            return $v->getMessage();
        }, $err->getIterator()->getArrayCopy());

    }
}
