<?php

namespace EfTech\BookLibrary\ConsoleCommand;

use EfTech\BookLibrary\Service\SearchTextDocumentService;
use EfTech\BookLibrary\Service\SearchTextDocumentService\SearchTextDocumentServiceCriteria;
use EfTech\BookLibrary\Service\SearchTextDocumentService\TextDocumentDto;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FindBooks extends \Symfony\Component\Console\Command\Command
{
    /**
     *
     *
     * @var SearchTextDocumentService
     */
    private SearchTextDocumentService $searchTextDocumentService;

    /**
     * @param SearchTextDocumentService $searchTextDocumentService
     */
    public function __construct(SearchTextDocumentService $searchTextDocumentService)
    {
        parent::__construct();
        $this->searchTextDocumentService = $searchTextDocumentService;
    }


    protected function configure(): void
    {
        $this->setName('bookLibrary:find-books');
        $this->setDescription('Found books');
        $this->setHelp('Found books by criteria');
        $this->addOption('author_surname','A', InputOption::VALUE_REQUIRED,'Found author surname');
        $this->addOption('id','i', InputOption::VALUE_REQUIRED,'Found id');
        $this->addOption('title','t', InputOption::VALUE_REQUIRED, 'Found title');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $params = $input->getOptions();
        $textDocumentsDto = $this->searchTextDocumentService->search((
        new SearchTextDocumentServiceCriteria())
            ->setAuthorSurname($params['author_surname'] ?? null)
            ->setId(isset($params['id']) ? (int)$params['id'] : null)
            ->setTitle($params['title'] ?? null));
        $jsonData = $this->buildJsonData($textDocumentsDto);
        $output->writeln(json_encode(
            $jsonData,
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE
        ));
        return self::SUCCESS;
    }

    /**
     * @param TextDocumentDto[]
     * @return array
     */
    private function buildJsonData(array $foundTextDocuments): array
    {
        $result = [];
        foreach ($foundTextDocuments as $foundTextDocument) {
            $result[] = $this->serializeTextDocument($foundTextDocument);
        }
        return $result;
    }

    /**
     * @param TextDocumentDto $textDocument
     * @return array
     */
    private function serializeTextDocument(TextDocumentDto $textDocument): array
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
        $jsonData['authors'] = array_values(array_map(static function (SearchTextDocumentService\AuthorDto $dto) {
            return  [
                'id' => $dto->getId(),
                'name' => $dto->getName(),
                'surname' => $dto->getSurname(),
                'birthday' => $dto->getBirthday()->format('d.m.Y'),
                'country' => $dto->getCountry(),
            ];
        }, $textDocument->getAuthors()));

        return $jsonData;
    }

}