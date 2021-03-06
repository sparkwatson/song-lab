<?php
    class User
    {
        private $id;
        private $first_name;
        private $last_name;
        private $email;
        private $username;
        private $bio;
        private $photo;
        private $password;

        function __construct($id = null, $first_name, $last_name, $email, $username, $bio, $photo, $password)
        {
            $this->id = $id;
            $this->first_name = $first_name;
            $this->last_name = $last_name;
            $this->email = $email;
            $this->username = $username;
            $this->bio = $bio;
            $this->photo = $photo;
            $this->password = $password;
        }

        // Setters

        function setFirstName($new_first_name)
        {
            $this->first_name = (string) $new_first_name;
        }

        function setLastName($new_last_name)
        {
            $this->last_name = (string) $new_last_name;
        }

        function setEmail($new_email)
        {
            $this->email = (string) $new_email;
        }

        function setUsername($new_username)
        {
            $this->username = (string) $new_username;
        }

        function setBio($new_bio)
        {
            $this->bio = (string) $new_bio;
        }

        function setPhoto($new_photo)
        {
            $this->photo = $new_photo;
        }

        function setPassword($new_password)
        {
            $this->password = $new_password;
        }

        // Getters

        function getId()
        {
            return $this->id;
        }

        function getFirstName()
        {
            return $this->first_name;
        }

        function getLastName()
        {
            return $this->last_name;
        }

        function getEmail()
        {
            return $this->email;
        }

        function getUsername()
        {
            return $this->username;
        }

        function getBio()
        {
            return $this->bio;
        }

        function getPhoto()
        {
            return $this->photo;
        }

        function getPassword()
        {
            return $this->password;
        }

        function save()
        {
            $GLOBALS['DB']->exec("INSERT INTO users (first_name, last_name, email, username, bio, photo, password) VALUES (
                '{$this->getFirstName()}', '{$this->getLastName()}', '{$this->getEmail()}', '{$this->getUsername()}', '{$this->getBio()}', '{$this->getPhoto()}', '{$this->getPassword()}');");
            $this->id = $GLOBALS['DB']->lastInsertId();
        }

        static function getAll()
        {
            $returned_users = $GLOBALS['DB']->query("SELECT * FROM users;");

            $users = array();
            foreach($returned_users as $user) {
                $id = $user['id'];
                $first_name = $user['first_name'];
                $last_name = $user['last_name'];
                $email= $user['email'];
                $username= $user['username'];
                $bio= $user['bio'];
                $photo= $user['photo'];
                $password = $user['password'];
                $new_user = new User($id, $first_name, $last_name, $email, $username, $bio, $photo, $password);
                array_push($users, $new_user);
            }
            return $users;

        }

        static function verifyLogin($username, $password)
        {
            $query = $GLOBALS['DB']->query("SELECT * FROM users WHERE username = '{$username}' AND password = '{$password}'");
            $login_match = $query->fetchAll(PDO::FETCH_ASSOC);
            $found_match = null;

            foreach($login_match as $match){
                $id = $match['id'];
                $first_name = $match['first_name'];
                $last_name = $match['last_name'];
                $email= $match['email'];
                $username= $match['username'];
                $bio= $match['bio'];
                $photo= $match['photo'];
                $password = $match['password'];
                $found_match = User::find($id);
            }
            return $found_match;
        }

        static function deleteAll()
        {
            $GLOBALS['DB']->exec("DELETE FROM users;");
        }

        function delete()
        {
            $GLOBALS['DB']->exec("DELETE FROM users WHERE id = {$this->getId()};");
            $GLOBALS['DB']->exec("DELETE FROM collaborations WHERE user_id = {$this->getId()};");
        }

        function update($new_first_name, $new_last_name, $new_email, $new_username, $new_bio, $new_photo, $new_password)
        {
            $GLOBALS['DB']->exec("UPDATE users SET first_name = '{$new_first_name}', last_name = '{$new_last_name}', email = '{$new_email}', username = '{$new_username}', bio = '{$new_bio}', photo = '{$new_photo}', password = '{$new_password}' WHERE id = {$this->getId()};");
            $this->setFirstName($new_first_name);
            $this->setLastName($new_last_name);
            $this->setEmail($new_email);
            $this->setUsername($new_username);
            $this->setBio($new_bio);
            $this->setPhoto($new_photo);
            $this->setPassword($new_password);
        }

        static function find($search_id)
        {
            $found_user = null;
            $users = User::getAll();

            foreach($users as $user)
            {
                $user_id = $user->getId();
                if ($user_id == $search_id)
                {
                  $found_user = $user;
                }
            }
            return $found_user;
        }

        static function findUsername($search_username)
        {
            $found_user = null;
            $users = User::getAll();

            foreach($users as $user)
            {
                if ($search_username == $user->getUsername())
                {
                  $found_user = $user;
                }
            }
            return $found_user;
        }

        function addProject($project)
       {
            $GLOBALS['DB']->exec("INSERT INTO collaborations (project_id, user_id) VALUES ({$project->getId()}, {$this->getId()});");
       }

       function getProjects()
       {
            $returned_projects = $GLOBALS['DB']->query("SELECT projects.* FROM users JOIN collaborations ON (collaborations.user_id = users.id) JOIN projects ON (projects.id = collaborations.project_id) WHERE users.id = {$this->getId()};");

            $projects = array();
            foreach($returned_projects as $project)
            {
                $id = $project['id'];
                $title = $project['title'];
                $description = $project['description'];
                $genre = $project['genre'];
                $resources = $project['resources'];
                $lyrics = $project['lyrics'];
                $type = $project['type'];
                $user_id = $project['user_id'];
                $new_project = new Project($id, $title, $description, $genre, $resources, $lyrics, $type, $user_id);
                array_push($projects, $new_project);
            }
            return $projects;
        }

        function getOwnerProjects()
        {
            $query = $GLOBALS['DB']->query("SELECT * FROM projects WHERE user_id = {$this->getId()} ORDER BY id DESC;");
		    $projects = array();

            foreach ($query as $project)
            {
                $id = $project['id'];
                $title = $project['title'];
                $description = $project['description'];
                $genre = $project['genre'];
                $resources = $project['resources'];
                $lyrics = $project['lyrics'];
                $type = $project['type'];
                $user_id = $project['user_id'];
                $new_project = new Project($id, $title, $description, $genre, $resources,        $lyrics, $type, $user_id);
                array_push($projects, $new_project);
            }
            return $projects;
        }

        function addMessage($message)
        {
           $GLOBALS['DB']->exec("INSERT INTO messages_user (message_id, user_id) VALUES ({$message->getId()}, {$this->getId()});");
        }

        function getOwnerMessages()
        {
            $returned_messages = $GLOBALS['DB']->query("SELECT messages.* FROM users
                JOIN messages_user ON (messages_user.user_id = users.id)
                JOIN messages ON (messages.id = messages_user.message_id)
                WHERE users.id = {$this->getId()};");

            $messages = array();
            foreach($returned_messages as $message)
            {
                $id = $message['id'];
                $user_message = $message['message'];
                $sender = $message['sender'];
                $project_id = $message['project_id'];
                $new_message = new Message($id, $user_message, $sender, $project_id);
                array_push($messages, $new_message);
            }
           return $messages;
        }

    }
?>
