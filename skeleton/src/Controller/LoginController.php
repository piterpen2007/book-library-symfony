<?php

namespace EfTech\BookLibrary\Controller;

use EfTech\BookLibrary\Form\LoginForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{


    /** Обработка http запроса
     *
     *
     *
     * @param Request $request
     * @param AuthenticationUtils $utils
     * @return Response
     */
    public function __invoke(Request $request, AuthenticationUtils $utils): Response
    {
        $errs = $utils->getLastAuthenticationError();
        $formLogin = $this->createForm(LoginForm::class);
        $formLogin->setData([
            'login' => $utils->getLastUsername()
        ]);
        $contex = [
            'form_login' => $formLogin,
            'errs' => $errs
        ];
        return $this->renderForm('login.twig', $contex);
    }
}
