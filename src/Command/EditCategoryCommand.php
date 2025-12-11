<?php

namespace App\Command;

use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:category:edit',
    description: 'Modifier une catégorie existante',
)]
class EditCategoryCommand extends AbstractCategoryCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'ID de la catégorie à modifier')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Nouveau nom de la catégorie')
            ->addOption('logo', null, InputOption::VALUE_OPTIONAL, self::getLogoHelpDescription())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id = $input->getArgument('id');
        $newName = $input->getOption('name');
        $newLogo = $input->getOption('logo');

        $category = $this->categoryRepository->find($id);

        if (!$category) {
            $io->error(sprintf('Catégorie avec l\'ID %d introuvable.', $id));
            return Command::FAILURE;
        }

        if (!$newName && !$newLogo) {
            $io->warning('Aucune modification spécifiée. Utilisez --name="Nouveau nom" et/ou --logo="https://example.com/image.png"');
            return Command::FAILURE;
        }

        $changes = [];

        if ($newName) {
            $oldName = $category->getName();
            $category->setName($newName);
            $changes[] = sprintf('Nom: "%s" → "%s"', $oldName, $newName);
        }

        if ($newLogo) {
            if (!$this->validateAndReportLogoUrl($io, $newLogo)) {
                return Command::FAILURE;
            }

            $oldLogo = $category->getLogo();
            $category->setLogo($newLogo);
            $changes[] = sprintf('Logo: %s → %s', $oldLogo, $newLogo);
        }

        $this->entityManager->flush();

        $io->success([
            sprintf('Catégorie #%d modifiée avec succès !', $id),
            ...$changes
        ]);

        return Command::SUCCESS;
    }
}
