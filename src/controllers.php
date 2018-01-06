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
        $sql = "SELECT * FROM todos WHERE id = '$id'";
        $todo = $app['db']->fetchAssoc($sql);

        return $app['twig']->render('todo.html', [
            'todo' => $todo,
        ]);
    } else {
        $sql = "SELECT * FROM todos WHERE user_id = '${user['id']}'";
        $todos = $app['db']->fetchAll($sql);

        return $app['twig']->render('todos.html', [
            'todos' => $todos,
        ]);
    }
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