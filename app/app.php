<?php
    require_once __DIR__."/../vendor/autoload.php";
    require_once __DIR__."/../src/User.php";
    require_once __DIR__."/../src/Project.php";
    require_once __DIR__."/../src/Message.php";

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

    // Load site upon arrival
    $app->get("/", function() use ($app) {
        session_start();
        $_SESSION['user_id'] = null;
        $user_id = $_SESSION['user_id'];
        $users = User::getAll();
        $error = "";
        return $app['twig']->render('index.html.twig', array('user' => $users, 'error' => $error, 'user_id' => $user_id));
    });

    // Go to homepage from menu
    $app->get("/home", function() use ($app) {
        session_start();
        $user_id = $_SESSION['user_id'];
        $users = User::getAll();
        $error = "";
        return $app['twig']->render('index.html.twig', array('user' => $users, 'error' => $error, 'user_id' => $user_id));
    });

    // Get about page
    $app->get("/about", function() use ($app) {
        session_start();
        $user_id = $_SESSION['user_id'];
        $error = "";
        return $app['twig']->render('about.html.twig', array('user_id' => $user_id, 'error' => $error));
    });

    // Create user
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
        session_start();
        $_SESSION['user_id'] = $user->getId();
        $user_id = $_SESSION['user_id'];
        return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $user_projects, 'user_id' => $user_id));
    });

    // Get private user profile
    $app->get("/user/{id}/profile", function($id) use ($app) {
        session_start();
        $user = User::find($id);
        $user_projects = $user->getProjects();
        return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $user_projects, 'current_user' => $user, 'user_id' => $_SESSION['user_id']));
    });

    //delete project from user profile page
    $app->delete("/project/{id}/delete", function($id) use ($app) {
        session_start();
        $project = Project::find($id);
        $project->delete();
        $user = User::find($project->getUserId());

        $user_projects = $user->getOwnerProjects();
        return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $user_projects, 'user_id' => $_SESSION['user_id']));
    });

    // Get projects list
    $app->get("/projects", function() use ($app){
        session_start();
        $user = User::find($_SESSION['user_id']);
        $projects = Project::getAll();

        foreach ($projects as $project){
        $owner = $project->getProjectOwner();
        $owner_name = $owner->getUsername();
        $owner_photo = $owner->getPhoto();
        // array_push($owners, $owner_name);
        }
        return $app['twig']->render('projects.html.twig', array('projects' => $projects, 'owner' => $owner_name, 'owner_photo' => $owner_photo, 'current_user' => $user, 'user_id' => $_SESSION['user_id']));

    });

    // Search projects page
    $app->post("/search", function() use ($app) {
        session_start();
        $user_id = $_SESSION['user_id'];
        $keyword = $_POST['search_term'];
        $project_matches = Project::search($keyword);
        return $app['twig']->render('projects.html.twig', array('projects' => $project_matches, 'user_id' => $user_id));
    });


    // Send message feature - TBD initial routing
    $app->post("/project/{id}/send_message", function($id) use ($app){
        session_start();
        $user_id = $_SESSION['user_id'];
        $project_to_collaborate = Project::find($id);
        $project_owner = $project_to_collaborate->getProjectOwner();
        $id = null;
        $message = $_POST['message'];
        $sender = $_POST['sender'];
        $project_id = $project_to_collaborate->getId();
        $new_message = new Message($id, $message, $sender, $project_id);
        $new_message->save();
        $project_owner->addMessage($new_message);
        return $app['twig']->render('sent_message.html.twig', array('owner' => $project_owner, 'user_id' => $user_id));
    });

    // Get messages
    $app->get("/user/{id}/messages", function($id) use ($app){
        session_start();
        $user_id = $_SESSION['user_id'];
        $user = User::find($id);
        $messages = $user->getOwnerMessages();
        $message_num = count($messages);
        return $app['twig']->render('view_messages.html.twig', array('messages' => $messages, 'count' => $message_num, 'user_id' => $user_id));

    });

    $app->post("/message/{id}/approve", function($id) use ($app){
          session_start();
          //add user to project as collaborator
          $message_to_delete = Message::find($id);
          $user = $message_to_delete->getMessageUser();
          $project = Project::find($message_to_delete->getProjectId());
          $sender_name = $message_to_delete->getSender();
          $sender = User::findUsername($sender_name);
          $project->addCollaborator($sender);
          $message_to_delete->delete();



          $messages = $user->getOwnerMessages();

          $message_num = count($messages);
          return $app['twig']->render('view_messages.html.twig', array('messages' => $messages, 'count' => $message_num, 'user_id' => $_SESSION['user_id']));
        });

    // Create a user project on private profile
    $app->post("/user/{id}/create_project", function($id) use ($app){
        session_start();
        $user_id = $_SESSION['user_id'];
        $user = User::find($id);
        $id = null;
        $title = $_POST['title'];
        $description = $_POST['description'];
        $genre = $_POST['genre'];
        $resources = $_POST['resources'];
        $lyrics = null; //fix this
        $type = null;
        $user_id = $user->getId(); //delete????
        $new_project = new Project($id, $title, $description, $genre, $resources, $lyrics, $type, $user_id);
        $new_project->save();
        $projects = $user->getOwnerProjects();
        return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $projects, 'user_id' => $user_id));
    });

    // Initial routing for returning to profile
    $app->get("/user/{id}", function($id) use ($app) {
        session_start();
        $user_id = $_SESSION['user_id'];
        $user = User::find($id);
        $user_projects = $user->getOwnerProjects();
        return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $user_projects, 'user_id' => $user_id));
      });


    // Sign in from index
    $app->post("/sign_in", function() use ($app) {
        $inputted_username = $_POST['username'];
        $inputted_password = $_POST['password'];
        $user =  User::verifyLogin($inputted_username, $inputted_password);

            if($user != null && $user->getUsername() == $inputted_username && $user->getPassword() == $inputted_password)
            {
                $found_user = $user;
                $user_projects = $found_user->getOwnerProjects();
                session_start();
                $_SESSION['user_id'] = $user->getId();
                $user_id = $_SESSION['user_id'];
                $session_status = $_SESSION['user_id'];
                return $app['twig']->render('private_profile.html.twig', array('user' => $found_user, 'projects' => $user_projects, 'user_id' => $user_id));
            } else {
                session_start();
                $error = "The username and password do not match!";
                return $app['twig']->render('index.html.twig', array('error' => $error, 'user_id' => $user_id));
            }
    });

    // Edit a specific user and return their profile page
    $app->patch("/user/{id}/edit_profile", function($id) use ($app){
        session_start();
        $user_id = $_SESSION['user_id'];
        $user = User::find($id);
        $new_first_name = $_POST['new_first_name'];
        $new_last_name = $_POST['new_last_name'];
        $new_email = null;
        $new_username = $_POST['new_username'];
        $new_bio = $_POST['new_bio'];
        $new_photo = $_POST['new_photo'];
        $new_password = $_POST['new_password'];
        $user->update($new_first_name, $new_last_name, $new_email, $new_username, $new_bio, $new_photo, $new_password);
        return $app['twig']->render('private_profile.html.twig', array('user' => $user, 'projects' => $user->getOwnerProjects(), 'user_id' => $user_id));
    });

    // Get page where user can edit their project
    $app->get("/user/{id}/edit_project", function($id) use ($app){
        session_start();
        $user_id = $_SESSION['user_id'];
        $user = User::find($id);
        $project = $user->getProjects($user->getId());
        return $app['twig']->render('edit_project.html.twig', array('user' => $user, 'user_id' => $user_id));
    });

    // Edit project and returns user to their profile page
    $app->patch("/user/{id}/edit_project", function($id) use ($app){
        session_start();
        $user_id = $_SESSION['user_id'];
        $user = User::find($id);

        $project = $user->getProjects($user->getId());
        $new_title = $_POST['new_title'];
        $new_description = $_POST['new_description'];
        $new_genre = $_POST['new_genre'];
        $new_resources = $_POST['new_resources'];
        $new_lyrics = $_POST['new_lyrics'];
        $new_type = $_POST['new_type'];
        $project->update($new_title, $new_description, $new_genre, $new_resources, $new_lyrics, $new_type);
        return $app['twig']->render('profile.html.twig', array('user' => $user, 'projects' => $user_projects, 'user_id' => $user_id));
    });

    // Get page (from edit modal) to delete specific user
  	$app->get("/user/{id}/delete", function($id) use ($app) {
          session_start();
          $user_id = $_SESSION['user_id'];
  		$user = User::find($id);
  		return $app['twig']->render('delete_user.html.twig', array(
  			'user' => $user, 'user_id' => $user_id));
  	});

    // Delete specific user; homepage rendered
  	$app->delete("/user/{id}/delete", function($id) use ($app) {
          session_start();
          $_SESSION['user_id'] = null;
          $user_id = $_SESSION['user_id'];
          $user = User::find($id);
          $user->delete();
          $error = "";
          return $app['twig']->render('index.html.twig', array('users' => User::getAll(), 'error' => $error, 'session' => $user_id));
      });

    // User Logs out of their session; homepage rendered
    $app->get("/log_out", function() use ($app) {
        session_start();
        $_SESSION['user_id'] = null;
        print_r($_SESSION['user_id']);
        $user_id = $_SESSION['user_id'];
        $users = User::getAll();
        $error = "";
        return $app['twig']->render('index.html.twig', array('user' => $users, 'error' => $error, 'user_id' => $user_id));
    });

    return $app;
?>
