<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user')]
    public function user (UserRepository $repo): Response
    {
        $users= $repo->findAll();
        return $this->render('user/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/user/{id}/to/editor ', name: 'app_user_to_editor')] //remettre la wildcard {role} pour selection du role en question
    public function changeRole(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles(['ROLE_EDITOR', 'ROLE_USER']);
        $entityManager->flush();

        $this->addFlash('success', "Le rôle éditeur à bien été ajouté à l'utilisateur");

        return $this->redirectToRoute('app_user');
    }
}
