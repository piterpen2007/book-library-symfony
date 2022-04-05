<?php

namespace EfTech\BookLibrary\Controller;

use Doctrine\ORM\EntityManagerInterface;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService\NewMagazineDto;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService\ResultRegisteringTextDocumentDto;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

/**
 *  Контроллер реализующий логику обработки запроса добавления жрунала
 */
class CreateRegisterMagazinesController extends AbstractController
{
    /**
     * Сервис валидации
     *
     * @var ValidatorInterface
     */
    private ValidatorInterface $validator;
    private ArrivalNewTextDocumentService $arrivalNewTextDocumentService;
    private EntityManagerInterface $em;

    /**
     * @param ArrivalNewTextDocumentService $arrivalNewTextDocumentService
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     */
    public function __construct(
        ArrivalNewTextDocumentService $arrivalNewTextDocumentService,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    )
    {
        $this->arrivalNewTextDocumentService = $arrivalNewTextDocumentService;
        $this->em = $em;
        $this->validator = $validator;
    }


    /**
     * @Route("/magazines/register",name="magazine_register", methods={"POST"})
     *
     *
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        try {
            $this->em->beginTransaction();
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
            $this->em->flush();
            $this->em->commit();
        } catch (Throwable $e) {
            $this->em->rollBack();
            $httpCode = 500;
            $jsonData = ['status' => 'fail','message' => $e->getMessage()];
        }

        return $this->json($jsonData,$httpCode);
    }

    /** Запуск сервиса
     * @param array $requestData
     * @return ResultRegisteringTextDocumentDto
     */
    private function runService(array $requestData): ResultRegisteringTextDocumentDto
    {
        $requestDto = new NewMagazineDto(
            $requestData['title'],
            $requestData['year'],
            $requestData['author_id_list'],
            $requestData['number'],
        );

        return $this->arrivalNewTextDocumentService->registerMagazine($requestDto);
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
            new Assert\Type(['type' => 'array', 'message' => 'Данные о новом журнале не являются массивом']),
            new Assert\Collection([
                'allowExtraFields'     => false,
                'allowMissingFields'   => false,
                'missingFieldsMessage' => 'Отсутствует обязательное поле: {{ field }}',
                'extraFieldsMessage'   => 'Есть лишние поля: {{ field }}',
                'fields'               => [
                    'title'          => [
                        new Assert\Type(['type' => 'string', 'message' => 'Заголовок журнала должен быть строкой']),
                        new Assert\NotBlank([
                            'message'    => 'Заголовок журнала не может быть пустой строкой',
                            'normalizer' => 'trim'
                        ]),
                        new Assert\Length([
                            'min'        => 1,
                            'max'        => 255,
                            'minMessage' => 'Некорректная длина заголовка журнала. Необходимо {{ limit }} символов',
                            'maxMessage' => 'Некорректная длина заголовка журнала. Максимальное количество {{ limit }} символов'
                        ])
                    ],
                    'year'           => [
                        new Assert\Type(['type' => 'int', 'message' => 'Год издания журнала должен быть числом']),
                        new Assert\Positive(['message' => 'Год издания журнала не может быть меньше или равным 0'])
                    ],
                    'number'         => [
                        new Assert\Type(['type' => 'int', 'message' => 'Номер журнала должен быть числом']),
                        new Assert\Positive(['message' => 'Номер журнала не может быть меньше или равным 0'])
                    ],
                    'author_id_list' => [
                        new Assert\Type(['type' => 'array', 'message' => 'Список авторов журнала должен быть массивом']
                        ),
                        new Assert\All([
                            new Assert\Type(
                                [
                                    'type'    => 'int',
                                    'message' => 'Список авторов журнала содержит некорректные идентификаторы'
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
