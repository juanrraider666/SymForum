<?php

namespace App\Controller\Panel;

use App\Controller\BaseController;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/panel")
 */
class UserPanelController extends BaseController
{
    /**
     * @Route("/users", name="panel.users")
     * @param UserRepository $userRepository
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(UserRepository $userRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $pagination = $paginator->paginate(
            $userRepository->findAllMembersQb(),
            $request->query->getInt('page', 1),
            30
        );

        return $this->render('panel/user/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    /**
     * @Route("/users/{slug}", name="panel.user.details")
     * @param User $user
     * @return Response
     */
    public function details(User $user): Response
    {
        return $this->render('panel/user/details.html.twig', [
            'user' => $user
        ]);
    }

    /**
     * @Route("/users/{slug}/reset", name="panel.user.reset")
     * @param User $user
     * @param UserService $userService
     * @return Response
     */
    public function reset(User $user, UserService $userService): Response
    {
        $userService->resetUser($user);
        $this->addCustomFlash('success', 'Utilisateurs', sprintf("L'utilisateur %s a été remis à zéro !", $user->getPseudo()));

        return $this->redirectToRoute('panel.user.details', [
            'slug' => $user->getSlug()
        ]);
    }

    /**
     * @Route("/users/{slug}/delete", name="panel.user.delete")
     * @param User $user
     * @param UserService $userService
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function delete(User $user, UserService $userService, Request $request): Response
    {
        $submittedToken = $request->request->get('token');

        if ($this->isCsrfTokenValid('delete-user', $submittedToken)) {
            $request->request->get('deleteData') ? $userService->deleteUser($user, true) : $userService->deleteUser($user);

            $this->addCustomFlash('success', 'Utilisateurs', sprintf("L'utilisateur %s a été supprimé !", $user->getPseudo()));

            return $this->redirectToRoute('panel.users');
        } else {
            throw new Exception("Jeton CSRF invalide !");
        }
    }
}
