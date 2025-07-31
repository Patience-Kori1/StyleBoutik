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

    #[Route('/admin/user/{id}/remove/editor/role ', name: 'app_user_remove_editor_role')]
    public function removeRoleeditor(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles([]);
        $entityManager->flush();

        $this->addFlash('danger', "Le rôle éditeur à bien été retiré à l'utilisateur");
        
        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove/', name: 'app_user_remove')]
    public function ruserRemove(EntityManagerInterface $entityManager,$id,  UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('danger', "L'utilisateur à bien été supprimé.");
        
        return $this->redirectToRoute('app_user');
    }
    
}
