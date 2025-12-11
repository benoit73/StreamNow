<?php

namespace App\Command;

use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractCategoryCommand extends Command
{
    protected const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected CategoryRepository $categoryRepository
    ) {
        parent::__construct();
    }

    /**
     * Valide une URL de logo et retourne l'erreur ou null si valide.
     */
    protected function validateLogoUrl(string $url): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return 'L\'URL du logo n\'est pas valide.';
        }

        $path = parse_url($url, PHP_URL_PATH);
        $extension = $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return sprintf(
                'Format d\'image non supporté "%s". Formats acceptés: %s',
                $extension ?: '(aucun)',
                implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }

        return null;
    }

    /**
     * Valide l'URL et affiche l'erreur si invalide.
     * Retourne true si valide, false sinon.
     */
    protected function validateAndReportLogoUrl(SymfonyStyle $io, string $url): bool
    {
        $error = $this->validateLogoUrl($url);
        
        if ($error !== null) {
            $io->error($error);
            return false;
        }

        return true;
    }

    /**
     * Retourne la description des formats acceptés pour l'aide.
     */
    protected static function getLogoHelpDescription(): string
    {
        return sprintf(
            'URL du logo (formats: %s)',
            implode(', ', self::ALLOWED_EXTENSIONS)
        );
    }
}
