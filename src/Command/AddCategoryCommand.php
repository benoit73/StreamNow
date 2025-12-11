<?php

namespace App\Command;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:category:add',
    description: 'Ajouter une nouvelle catégorie',
)]
class AddCategoryCommand extends AbstractCategoryCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Nom de la catégorie')
            ->addArgument('logo', InputArgument::REQUIRED, self::getLogoHelpDescription())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');
        $logo = $input->getArgument('logo');

        if (!$this->validateAndReportLogoUrl($io, $logo)) {
            return Command::FAILURE;
        }

        $category = new Category();
        $category->setName($name);
        $category->setLogo($logo);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $io->success(sprintf('Catégorie "%s" créée avec succès ! (ID: %d)', $name, $category->getId()));
        $io->writeln(sprintf('Logo: %s', $logo));

        return Command::SUCCESS;
    }
}
