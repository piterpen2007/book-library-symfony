<?php

namespace EfTech\BookLibrary\Controller;

use Doctrine\ORM\EntityManagerInterface;
use EfTech\BookLibrary\Exception;
use EfTech\BookLibrary\Service\ArchiveTextDocumentService\ArchivingResultDto;
use EfTech\BookLibrary\Service\ArchiveTextDocumentService\Exception\TextDocumentNotFoundException;
use EfTech\BookLibrary\Service\ArchivingTextDocumentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UpdateMoveToArchiveBooksController extends AbstractController
{
    /** Сервис архивации документов
     * @var ArchivingTextDocumentService
     */
    private ArchivingTextDocumentService $archivingTextDocumentService;
    private EntityManagerInterface $em;

    /**
     * @param ArchivingTextDocumentService $archivingTextDocumentService
     * @param EntityManagerInterface $em
     */
    public function __construct(
        ArchivingTextDocumentService $archivingTextDocumentService,
        EntityManagerInterface $em
    ) {
        $this->archivingTextDocumentService = $archivingTextDocumentService;
        $this->em = $em;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $this->em->beginTransaction();
            $attributes = $request->attributes->all();
            if (false === array_key_exists('id', $attributes)) {
                throw new Exception\RuntimeException('there is no information about the id of the text document');
            }
            $resultDto = $this->archivingTextDocumentService->archive((int)$attributes['id']);
            $httpCode = 200;
            $jsonData = $this->buildJsonData($resultDto);
            $this->em->flush();
            $this->em->commit();
        } catch (TextDocumentNotFoundException $e) {
            $this->em->rollBack();
            $httpCode = 404;
            $jsonData = ['status' => 'fail', 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            $this->em->rollBack();
            $httpCode = 500;
            $jsonData = ['status' => 'fail', 'message' => $e->getMessage()];
        }

        return $this->json($jsonData, $httpCode);
    }

    /** Подготавливает данные для успешного ответа на основе dto сервиса
     * @param ArchivingResultDto $resultDto
     * @return array
     */
    private function buildJsonData(ArchivingResultDto $resultDto): array
    {
        return [
            'id' => $resultDto->getId(),
            'status' => $resultDto->getStatus(),
            'title_for_printing' => $resultDto->getTitleForPrinting()
        ];
    }
}
