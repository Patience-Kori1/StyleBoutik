<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/users', name: 'app_user')]
    public function user (UserRepository $repo): Response
    {
        $users= $repo->findAll();
        return $this->render('user/users.html.twig', [
            'users' => $users,
        ]);
    }
}
