<?php

namespace EfTech\BookLibrary\ConsoleCommand;

use EfTech\BookLibrary\Service\SearchAuthorsService\SearchAuthorsCriteria;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HashStr extends Command
{
    protected function configure(): void
    {
        $this->setName('bookLibrary:hash-str');
        $this->setDescription('hash string');
        $this->setHelp('hash by string');
        $this->addOption('data','d', InputOption::VALUE_REQUIRED,'hash by string');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $params = $input->getOptions();
        $msg = password_hash($params['data'], PASSWORD_DEFAULT);
        $output->write(
            $msg
        );
        return self::SUCCESS;
    }
}