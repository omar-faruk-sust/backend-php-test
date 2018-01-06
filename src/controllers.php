<?php

use Omar\Models\Todo;
use Omar\Models\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addGlobal('user', $app['session']->get('user'));

    return $twig;
}));


$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html', [
        'readme' => file_get_contents('../README.md'),
    ]);
});


$app->match(/**
 * @param Request $request
 * @return \Symfony\Component\HttpFoundation\RedirectResponse
 */
    '/login', function (Request $request) use ($app) {
    $username = filter_var($request->get('username'),FILTER_SANITIZE_STRING);
    $password = filter_var($request->get('password'), FILTER_SANITIZE_STRING);

    if ($username) {
        $user = new User();
        $loginResult = $user->login($username, $password);

        if (!empty($loginResult)){
            $app['session']->set('user', $loginResult);
            return $app->redirect('/todo');
        } else {
            return $app->redirect('/login');
        }
    }

    return $app['twig']->render('login.html', array());
});


$app->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect('/');
});

$app->get('/todo', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $page = (int) $request->get('page');
    if($page == 0) {
        $page = 1;
    }
    $user = $app['session']->get('user');
    $userId = $user['id'];


    /*** pagination*/
    $limit = (int) ( isset( $_GET['limit'] ) ) ? $_GET['limit'] : 10;
    $page  = (int) ( isset( $_GET['page'] ) ) ? $_GET['page'] : 1;

    $todo  = new Todo();
    $result = $todo->getAll($userId, $limit, $page);
    /**paination**/

    return $app['twig']->render('todos.html', [
        'todos' => $result['todos']->data,
        'link' => $result['link']
    ]);
});


$app->get('/todo/{id}', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    if ($id){
        $todoObj = new Todo();
        $result = $todoObj->getTodoById(intval($id), $user['id']);

        if($result != null) {
            return $app['twig']->render('todo.html', [
                'todo' => $result,
            ]);
        }
    }

    return $app->redirect('/todo');


})->value('id', null);


$app->post('/todo/add', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $user_id = $user['id'];
    $description = trim(strip_tags($request->get('description')));

    if($description != "") {
        $todo = new Todo();
        $insertedId = $todo->add($user_id, $description);

        if($insertedId >0) {
            return $app->redirect('/todo');
        } else {
            return $app->redirect('/todo');
        }
    } else {
        return $app->redirect('/todo');
    }
});

$app->match('/todo/delete/{id}', function ($id) use ($app) {

    $todo = new Todo();
    $todo->delete($id);
    return $app->redirect('/todo');
});

$app->post('/todo/mark/{id}', function (Request $request, $id) use ($app){
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $todoObj = new Todo();
    $todoObj->update($id, $user['id']);

    return $app->redirect('/todo');
});

$app->get('/todo/{id}/json', function ($id) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    if ($id){
        $todoObj = new Todo();
        $result = $todoObj->getTodoById(intval($id), $user['id']);

        if($result != null) {
            return $app['twig']->render('view.html', [
                'todo' => json_encode($result),
            ]);
        }
    }

    return $app->redirect('/todo');

})->value('id', null);