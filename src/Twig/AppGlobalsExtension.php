<?php

namespace App\Twig;

use App\Repository\CategorieRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Extension Twig qui injecte les catégories dans tous les templates.
 * Permet d'afficher le menu catégories dans la navbar sur toutes les pages.
 */
class AppGlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly CategorieRepository $categorieRepository
    ) {}

    /**
     * Rend la variable 'all_categories' disponible dans tous les templates Twig.
     * Utilisée dans layouts/navbar.html.twig pour le menu déroulant des catégories.
     */
    public function getGlobals(): array
    {
        return [
            'all_categories' => $this->categorieRepository->findAll(),
        ];
    }
}
