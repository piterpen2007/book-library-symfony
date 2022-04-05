<?php

namespace EfTech\BookLibrary\Controller;

use Doctrine\ORM\EntityManagerInterface;
use EfTech\BookLibrary\Exception\RuntimeException;
use EfTech\BookLibrary\Form\CreateBookForm;
use EfTech\BookLibrary\Form\CreateMagazineForm;
use Psr\Log\LoggerInterface;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService\NewBookDto;
use EfTech\BookLibrary\Service\ArrivalNewTextDocumentService\NewMagazineDto;
use EfTech\BookLibrary\Service\SearchAuthorsService;
use EfTech\BookLibrary\Service\SearchTextDocumentService;
use EfTech\BookLibrary\Service\SearchTextDocumentService\SearchTextDocumentServiceCriteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TextDocumentAdministrationController extends AbstractController
{

    /** сервис добавления текстового документа
     * @var ArrivalNewTextDocumentService
     */
    private ArrivalNewTextDocumentService $arrivalNewTextDocumentService;

    /** Сервис поиска текстового документа
     * @var SearchTextDocumentService
     */
    private SearchTextDocumentService $searchTextDocumentService;

    private EntityManagerInterface $em;

    /**
     * @param SearchTextDocumentService $searchTextDocumentService Сервис поиска текстового документа
     * @param ArrivalNewTextDocumentService $arrivalNewTextDocumentService
     * @param EntityManagerInterface $em
     */
    public function __construct(
        SearchTextDocumentService $searchTextDocumentService,
        ArrivalNewTextDocumentService $arrivalNewTextDocumentService,
        EntityManagerInterface $em
    ) {
        $this->searchTextDocumentService = $searchTextDocumentService;
        $this->arrivalNewTextDocumentService = $arrivalNewTextDocumentService;
        $this->em = $em;
    }


    public function __invoke(Request $serverRequest): Response
    {
        $formBook = $this->createForm(CreateBookForm::class);
        $formMagazine = $this->createForm(CreateMagazineForm::class);
        $formBook->handleRequest($serverRequest);
        $formMagazine->handleRequest($serverRequest);


        if ($formBook->isSubmitted() && $formBook->isValid()) {
            $this->createBook($formBook->getData());
            $formBook = $this->createForm(CreateBookForm::class);
        } elseif ($formMagazine->isSubmitted() && $formMagazine->isValid()) {
            $this->createMagazine($formMagazine->getData());
            $formMagazine = $this->createForm(CreateMagazineForm::class);
        }

        $template = 'textDocument.administration.twig';
        $context = [
            'form_book' => $formBook,
            'form_magazine' => $formMagazine,
            'textDocuments' => $this->searchTextDocumentService->search(new SearchTextDocumentServiceCriteria())
        ];
        return $this->renderForm($template, $context);
    }



    /** Создаёт книгу
     * @param array $dataToCreate
     * @return void
     */
    private function createBook(array $dataToCreate): void
    {
        try {
            $this->em->beginTransaction();
            $this->arrivalNewTextDocumentService->registerBook(
                new NewBookDto(
                    $dataToCreate['title'],
                    $dataToCreate['year'],
                    $dataToCreate['author_id_list']
                )
            );
            $this->em->flush();
            $this->em->commit();
        } catch (Throwable $e) {
            $this->em->rollBack();
            throw new RuntimeException(
                'Ошибка при создании книги' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }





    /** Создаёт магазин
     * @param array $dataToCreate
     * @return void
     */
    private function createMagazine(array $dataToCreate): void
    {
        try {
            $this->em->beginTransaction();
            $this->arrivalNewTextDocumentService->registerMagazine(
                new NewMagazineDto(
                    $dataToCreate['title'],
                    $dataToCreate['year'],
                    $dataToCreate['author_id_list'],
                    $dataToCreate['number']
                )
            );
            $this->em->flush();
            $this->em->commit();
        } catch (Throwable $e) {
            $this->em->rollBack();
            throw new RuntimeException(
                'Ошибка при создании журнала' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

}
