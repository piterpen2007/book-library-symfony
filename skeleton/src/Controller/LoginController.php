<?php

namespace EfTech\BookLibrary\Controller;

use EfTech\BookLibrary\Form\LoginForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LoginController extends AbstractController
{


    /** Обработка http запроса
     *
     *
     *
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        try {
            $response = $this->doLogin($request);
        } catch (Throwable $e) {
            $response = $this->buildErrorResponse($e);
        }
        return $response;
    }

    /**
     * @param Throwable $e
     * @return Response
     */
    private function buildErrorResponse(Throwable $e): Response
    {
        $httpCode = 500;
        $contex = [
            'errors' => [
                $e->getMessage()
            ]
        ];
        return $this->render('errors.twig', $contex)->setStatusCode($httpCode);
    }

    private function doLogin(Request $request): Response
    {
        $formLogin = $this->createForm(LoginForm::class);
        $formLogin->handleRequest($request);
        $response = null;
        $contex = [
            'form_login' => $formLogin
        ];
        if ($formLogin->isSubmitted() && $formLogin->isValid()) {
            $authData = $formLogin->getData();
            if ($this->isAuth($authData['login'], $authData['password'])) {
                $response = $request->query->has('redirect') ?
                    $this->redirect($request->query->get('redirect')) :
                    $this->redirect('/');
            } else {
                $contex['errMsg'] = 'Логин и пароль не подходят';
            }
        }
        if (null === $response) {
            $response = $this->renderForm('login.twig', $contex);
        }
        return $response;
    }

    /** Проводит аутентификацию пользователя
     * @param string $login
     * @param string $password
     * @return bool
     */
    private function isAuth(string $login, string $password): bool
    {
        return true;
    }
}
