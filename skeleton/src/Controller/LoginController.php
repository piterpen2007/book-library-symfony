<?php

namespace EfTech\BookLibrary\Controller;

use EfTech\BookLibrary\Exception\RuntimeException;
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
        $response = null;
        $contex = [];
        if ('POST' === $request->getMethod()) {
            $authData = $request->request->all();

            //$this->validateAuthData($authData);
            if ($this->isAuth($authData['login'], $authData['password'])) {
                $response = $request->query->has('redirect') ?
                    $this->redirect($request->query->get('redirect')) :
                    $this->redirect('/');
            } else {
                $contex['errMsg'] = 'Логин и пароль не подходят';
            }
        }
        if (null === $response) {
            $response = $this->render('login.twig', $contex);
        }
        return $response;
    }

//    /** Логика валидации данных формы аутификации
//     * @param array $authData
//     */
//    private function validateAuthData(array $authData): void
//    {
//        if (false === array_key_exists('login', $authData)) {
//            throw new RuntimeException('Отсутствует логин');
//        }
//        if (false === is_string($authData['login'])) {
//            throw new RuntimeException('Логин имеет неверный формат');
//        }
//
//        if (false === array_key_exists('password', $authData)) {
//            throw new RuntimeException('Отсутствует password');
//        }
//        if (false === is_string($authData['password'])) {
//            throw new RuntimeException('password имеет неверный формат');
//        }
//    }

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
