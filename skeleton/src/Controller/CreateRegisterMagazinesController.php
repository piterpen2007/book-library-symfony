<?php

namespace EfTech\BookLibrary\Controller;

use Doctrine\ORM\EntityManagerInterface;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService\NewMagazineDto;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService\ResultRegisteringTextDocumentDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 *  Контроллер реализующий логику обработки запроса добавления жрунала
 */
class CreateRegisterMagazinesController extends AbstractController
{
    private ArrivalNewTextDocumentService $arrivalNewTextDocumentService;
    private EntityManagerInterface $em;

    /**
     * @param ArrivalNewTextDocumentService $arrivalNewTextDocumentService
     * @param EntityManagerInterface $em
     */
    public function __construct(
        ArrivalNewTextDocumentService $arrivalNewTextDocumentService,
        EntityManagerInterface $em
    )
    {
        $this->arrivalNewTextDocumentService = $arrivalNewTextDocumentService;
        $this->em = $em;
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
        } catch (\Throwable $e) {
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
     */
    private function validateData($requestData): array
    {
        $err = [];
        if (false === is_array($requestData)) {
            $err[] = 'Данные о новом журнале не являются массивом';
        } else {
            if (false === array_key_exists('title', $requestData)) {
                $err[] = 'Отсутствует информация о заголовке журнала';
            } elseif (false === is_string($requestData['title'])) {
                $err[] = 'Заголовок журнала должен быть строкой';
            } elseif ('' === trim($requestData['title'])) {
                $err[] = 'Заголовок журнала не может быть пустой строкой';
            }

            if (false === array_key_exists('year', $requestData)) {
                $err[] = 'Отсутствует информация о годе издания журнала';
            } elseif (false === is_int($requestData['year'])) {
                $err[] = 'Год издания журнала должен быть целым числом';
            } elseif ($requestData['year'] <= 0) {
                $err[] = 'Год издания журнала не может быть меньше или равен нуля';
            }

            if (false === is_array($requestData['author_id_list'])) {
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

            if (false === array_key_exists('number', $requestData)) {
                $err[] = 'Отсутствует информация о номере журнала';
            } elseif (false === is_int($requestData['number'])) {
                $err[] = 'Номер журнала должен быть целым числом';
            } elseif ($requestData['number'] <= 0) {
                $err[] = 'Номер журнала не может быть меньше или равен нуля';
            }
        }

        return $err;
    }
}
