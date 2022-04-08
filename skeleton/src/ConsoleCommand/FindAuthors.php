<?php

namespace EfTech\BookLibrary\ConsoleCommand;

use EfTech\BookLibrary\Service\SearchAuthorsService;
use EfTech\BookLibrary\Service\SearchAuthorsService\AuthorDto;
use EfTech\BookLibrary\Service\SearchAuthorsService\SearchAuthorsCriteria;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FindAuthors extends \Symfony\Component\Console\Command\Command
{
    /**
     *
     *
     * @var SearchAuthorsService
     */
    private SearchAuthorsService $searchAuthorsService;

    /**
     * @param SearchAuthorsService $searchAuthorsService
     */
    public function __construct(SearchAuthorsService $searchAuthorsService)
    {
        parent::__construct();
        $this->searchAuthorsService = $searchAuthorsService;
    }

    protected function configure(): void
    {
        $this->setName('bookLibrary:find-authors');
        $this->setDescription('Found authors');
        $this->setHelp('Found authors by criteria');
        $this->addOption('surname','sn', InputOption::VALUE_REQUIRED,'Found authors surname');
        $this->addOption('id','i', InputOption::VALUE_REQUIRED,'Found authors id');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $params = $input->getOptions();
        $dtoCollection = $this->searchAuthorsService->search(
            (new SearchAuthorsCriteria())
                ->setSurname($params['surname'] ?? null)
                ->setId(isset($params['id']) ? (int)$params['id'] : null)
        );
        $jsonData = $this->buildJsonData($dtoCollection);
        $table = new Table($output);
        $table->setHeaders([
            'id',
            'name',
            'surname',
            'birthday',
            'country',
        ]);
        $table->setRows($jsonData);
        $table->render();
        return self::SUCCESS;
    }

    /**
     *
     *
     * @param AuthorDto[] $dtoCollection
     *
     * @return array
     */
    private function buildJsonData(array $dtoCollection): array
    {
        $result = [];
        /** @var AuthorDto $authorDto */
        foreach ($dtoCollection as $authorDto) {
            $result[] = [
                'id' => $authorDto->getId(),
                'name' => $authorDto->getName(),
                'surname' => $authorDto->getSurname(),
                'birthday' => $authorDto->getBirthday()->format('d.m.Y'),
                'country' => $authorDto->getCountry(),
            ];
        }
        return $result;
    }
}