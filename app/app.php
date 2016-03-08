<?php
    require_once __DIR__."/../vendor/autoload.php";
    require_once __DIR__."/../src/User.php";
    require_once __DIR__."/../src/Project.php";

    $app = new Silex\Application();

    $server = 'mysql:host=localhost;dbname=songlab';
    $username = 'root';
    $password = 'root';
    $DB = new PDO($server, $username, $password);

    $app['debug']=true;

    use Symfony\Component\HttpFoundation\Request;
    Request::enableHttpMethodParameterOverride();

    $app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/../views'
    ));

    // Get homepage
    $app->get("/", function() use ($app) {
        $users = User::getAll();
        return $app['twig']->render('index.html.twig', array('user' => $users));
    });

    $app->post("/user", function() use ($app) {
        $id = null;
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = null; //null - editable in profile
        $username = $_POST['username'];
        $bio = $_POST['bio'];
        $photo = null; //null - upload on profile edit
        $password = $_POST['password1'];
        $user = new User($id, $first_name, $last_name, $email, $username, $bio, $photo, $password);
        $user->save();
        $user_projects = $user->getProjects();
        return $app['twig']->render('profile.html.twig', array('user' => $user, 'projects' => $user_projects));
    });

<<<<<<< HEAD
    $app->get("/projects", function() use ($app){
        return $app['twig']->render('projects.html.twig', array('projects' => Project::getAll()));
    });

    $app->post("/search", function() use ($app) {
        $keyword = $_POST['search_term'];
        $project_matches = Project::search($keyword);
        return $app['twig']->render('projects.html.twig', array('projects' => $project_matches));
    });

    $app->get("/user/{id}/create_project", function($id) use ($app){
        $user = User::find($id);
        return $app['twig']->render('create_project.html.twig', array('user' => $user));
    });

    $app->post("/user/{id}", function($id) use ($app) {
        $user = User::find($id);
        $user_projects = $user->getProjects();
        return $app['twig']->render('profile.html.twig', array('user' => $user, 'projects' => $user_projects));
=======
    $app->post("/user", function() use ($app) {
        $users = User::getAll();
        $inputted_username = $_POST['username'];
        $inputted_password = $_POST['password'];
        $error = null;

        foreach($users as $user)
        {
            $username = $user->getUsername();
            $password = $user->getPassword();
            $id = $user->getId();

            if($username == $inputted_username && $password == $inputted_password)
            {
                $found_user = User::findUsername($username);
                $user_projects = $found_user->getOwnerProjects();

                return $app['twig']->render('private_profile.html.twig', array('user' => $found_user, 'projects' => $user_projects));
            } else {
                echo '<script src="js/sign_in_verify.js"></script>';
                $error = "The username and password do not match!";

                // return $app['twig']->render('index.html.twig', array('user' => $users, 'error' => $error));
            }
        }

        // return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $user_projects, 'error' => $error));
>>>>>>> 5a3317b036fe7621022fa237adcb73157ac0f1b0
    });

    // Get page to edit a specific user
    $app->get("/user/{id}/edit_profile", function($id) use ($app){
        $user = User::find($id);
        return $app['twig']->render('edit_profile.html.twig', array('user' => $user));
    });

    // Edit a specific user and return their profile page
    $app->patch("/user/{id}/edit_profile", function($id) use ($app){
        $user = User::find($id);
        $new_first_name = $_POST['new_first_name'];
        $new_last_name = $_POST['new_last_name'];
        $new_email = $_POST['new_email'];
        $new_username = $_POST['new_username'];
        $new_bio = $_POST['new_bio'];
        $new_photo = $_POST['new_photo'];
        $new_password = $_POST['new_password'];
        $user->update($new_first_name, $new_last_name, $new_email, $new_username, $new_bio, $new_photo, $new_password);
        return $app['twig']->render('profile.html.twig', array('user' => $user, 'projects' => $user_projects));
    });


    // Gets page where user can edit their project
    $app->get("/user/{id}/edit_project", function($id) use ($app){
        $user = User::find($id);
        $project = $user->getProjects($user->getId());
        return $app['twig']->render('edit_project.html.twig', array('user' => $user));
    });

    // Edits project and returns user to their profile page
    $app->patch("/user/{id}/edit_project", function($id) use ($app){
        $user = User::find($id);
        $project = $user->getProjects($user->getId());
        $new_title = $_POST['new_title'];
        $new_description = $_POST['new_description'];
        $new_genre = $_POST['new_genre'];
        $new_resources = $_POST['new_resources'];
        $new_lyrics = $_POST['new_lyrics'];
        $new_type = $_POST['new_type'];
        $project->update($new_title, $new_description, $new_genre, $new_resources, $new_lyrics, $new_type);
        return $app['twig']->render('profile.html.twig', array('user' => $user, 'projects' => $user_projects));
    });

    // Get page to delete specific user
    // User is directed TO this page FROM edit page
	$app->get("/user/{id}/delete", function($id) use ($app) {
		$user = User::find($id);
		return $app['twig']->render('delete_user.html.twig', array(
			'user' => $user));
	});

    // Delete specific user
    // User is sent to homepage after deletion
	$app->delete("/user/{id}/delete", function($id) use ($app) {
        $user = User::find($id);
        $user->delete();
        return $app['twig']->render('index.html.twig', array('users' => User::getAll()));
    });

    return $app;
?>
