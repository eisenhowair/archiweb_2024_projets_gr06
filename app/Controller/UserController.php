<?php

namespace Manger\Controller;

use Manger\Model\UserModel;
use Manger\View\{
    UserView,
    AdminView,
    NutritionistView
};

/**
 * Controller for User-related things.
 * 
 * Handle actions such as registration, login, logout, and all modifications of attributes.
 */
class UserController
{

    /**
     * userModel
     *
     * @var UserModel
     */
    private $userModel;

    /**
     * Constructor
     *
     * Initializes the Users Controller with the User Model.
     */
    public function __construct()
    {

        $this->userModel = new UserModel();
    }


    /**
     * Display page from the View
     *
     * Renders and displays the specified page using the UserView.
     *
     * @param string $page The page to display.
     * @return void
     */
    public function GETPage($page)
    {
        if ($page == "dashboardAdmin") {
            $adminView = new AdminView();

            $html = $adminView->viewPage($page);

            echo $html;
            http_response_code(200);
        } else if ($page == "nutritionist-dashboard") {

            $nutritionistView = new nutritionistView();

            $html = $nutritionistView->viewPage($page);

            echo $html;
            http_response_code(200);
        } else {


            $userView = new UserView();

            $html = $userView->viewPage($page);

            echo $html;
            http_response_code(200);
        }
    }



    /**
     * Register
     *
     * Take the parameters from the _POST_ request, sanitize them and check in the database
     * if they correspond to a user.
     * If not, the password is hashed and all the data is sent to the Model to save it in the database.
     *
     * @return void
     */
    public function register()
    {
        // Sanitize email
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $fullname = trim($_POST['fullname'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Initialize data
        $data = [
            'fullname' => $fullname,
            'password' => $password,
            'email' => $email
        ];


        //User with the same email already exists
        if ($this->userModel->findUserByEmail($data['email'])) {
            header(APPJSON);
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            return;
        }

        //Passed validation checks so Hashing password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        //Register User
        header(APPJSON);
        if ($this->userModel->register($data)) {
            echo json_encode(['success' => true, 'redirect' => 'login.php']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Something went wrong']);
            exit;
        }
    }



    /**
     * Login
     *
     * Take the parameters from the _POST_ request, and check in the database if they correspond to a user.
     * If so, a session with the user's parameters is created.
     *
     * @return void
     */
    public function login()
    {
        // Sanitize email
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

        // Passwords should not be altered too much but trim whitespace
        $password = trim($_POST['password'] ?? '');

        // Initialize data
        $data = [
            'email' => $email,
            'password' => $password
        ];

        if ($this->userModel->findUserByEmail($data['email'])) {
            $loggerInUser = $this->userModel->login($data['email'], $data['password']);
            if ($loggerInUser) {
                $this->createUserSession($loggerInUser);
                echo json_encode(['success' => true]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Password Incorrect']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No user found']);
            exit;
        }
    }

    /**
     * Start the session.
     *
     * Take the parameters of *$user* and put them in the session,
     * attesting that the user is logged in.
     *
     * @param  mixed $user
     * @return void
     */
    public function createUserSession($user)
    {
        $_SESSION['id'] = $user->id;
        $_SESSION['fullname'] = $user->fullname;
        $_SESSION['email'] = $user->email;
        $_SESSION['height'] = $user->height;
        $_SESSION['age'] = $user->age;
        $_SESSION['weight'] = $user->weight;
        $_SESSION['goal'] = $user->goal;
        $_SESSION['role'] = $user->role;
        $_SESSION['img'] = $user->img;
        $_SESSION['total_clients'] = $user->total_clients;
        $_SESSION['etatPlan'] = "show";
    }

    /**
     * Remove data from the session, then destroy it.
     *
     * @return void
     */
    public function logout()
    {
        session_unset();
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Update User Details
     *
     * Updates user details after sainitizing them,
     * then create a new session for the user.
     *
     * @return void
     */
    public function updateUserDetails()
    {

        $id = filter_var(trim($_POST['user_id'] ?? ''), FILTER_SANITIZE_NUMBER_INT);
        $img_path = filter_var(trim($_POST['profile-image'] ?? ''), FILTER_SANITIZE_NUMBER_INT);
        $fullname = filter_var(trim($_POST['fullname'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $goal = filter_var(trim($_POST['goal'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $height = filter_var(trim($_POST['height'] ?? ''), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $weight = filter_var(trim($_POST['weight'] ?? ''), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $age = filter_var(trim($_POST['age'] ?? ''), FILTER_SANITIZE_NUMBER_INT);

        //Init data
        $data = [
            'id' => $id,
            'img_path' => $img_path,
            'fullname' => $fullname,
            'goal' => $goal,
            'height' => $height,
            'weight' => $weight,
            'age' => $age
        ];

        if ($this->userModel->updateUserDetails($data)) {
            $updatedUser = $this->userModel->getUserById($data['id']);
            $this->createUserSession($updatedUser);
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'No user found']);
            exit;
        }
    }


    /**
     * Update credentials.
     *
     * Update important credentials in the database after sanitizing them,
     * then create a new session for the user.
     *
     * @return void
     */
    public function updateUserCredentials()
    {


        //Init data
        $data = [
            'id' => $_SESSION['id'],
            'email' => trim($_POST['email'], FILTER_SANITIZE_EMAIL),
            'old_password' => trim($_POST['old_password']),
            'new_password' => trim($_POST['new_password']),
        ];

        if ($this->userModel->updateUserCredentials($data)) {
            $updatedUser = $this->userModel->getUserById($data['id']);
            $this->createUserSession($updatedUser);
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Old password incorrect']);
            exit;
        }
    }

    /**
     * Update User First Login
     *
     * Updates user details in the database via the Model function during the first login.
     *
     * @return void
     */
    public function updateUserFirstLogin()
    {
        // Sanitize each POST field individually
        $id = filter_var(trim($_POST['id'] ?? ''), FILTER_SANITIZE_NUMBER_INT);
        $fullname = filter_var(trim($_SESSION['fullname'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $goal = filter_var(trim($_POST['goal'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $height = filter_var(trim($_POST['height'] ?? ''), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $weight = filter_var(trim($_POST['weight'] ?? ''), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $age = filter_var(trim($_POST['age'] ?? ''), FILTER_SANITIZE_NUMBER_INT);
        $gender = filter_var(trim($_POST['gender'] ?? ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $dailyCalories = filter_var(trim($_POST['dailyCalories'] ?? ''), FILTER_SANITIZE_NUMBER_INT);

        // Initialize data
        $data = [
            'id' => $id,
            'fullname' => $fullname,
            'goal' => $goal,
            'height' => $height,
            'weight' => $weight,
            'age' => $age,
            'gender' => $gender,
            'dailyCalories' => $dailyCalories
        ];


        if ($this->userModel->updateUserFirstLogin($data)) {
            $updatedUser = $this->userModel->getUserById($data['id']);
            $this->createUserSession($updatedUser);
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Something went wrong']);
            exit;
        }
    }



    /**
     * getRecipesByName
     * 
     * Use the name from the GET request to look for correspondances  via the UserModel,
     * then display said correspondances
     * 
     * @return void
     */
    public function getRecipesByName()
    {
        header('APPJSON');
        $searchValue = isset($_GET['searchValue']) ? $_GET['searchValue'] : '';

        if (!empty($searchValue)) {
            $data = $this->userModel->getRecipesByName($searchValue);

            if ($data) {
                ob_start();
                include VIEWSDIR . DS . 'components' . DS . 'user' . DS . 'planning' . DS . 'searchResults.php';
                $output = ob_get_clean();

                echo json_encode(['success' => true, 'data' => $output]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No recipes found.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No search value provided.']);
        }
        exit;
    }

    /**
     * countNotification
     * 
     * Calls the Model to count the number of notifications the user has received
     *
     * @return void
     */
    public function countNotification()
    {
        header('APPJSON');

        $data = $this->userModel->getNotifsById($_SESSION['id']);

        if ($data >= 0) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Notification query failed.']);
        }

        exit;
    }
    /**
     * Retrieve a list of recipes
     * 
     * This function retrieves a list of recipes from the UserModel and returns 
     * the list as a JSON response. If no recipes are found, it returns a message 
     * indicating so.
     * 
     * @return void
     */
    function recipesList()
    {
        header('APPJSON');
        $recipes = $this->userModel->getRecipesList();
        // Start output buffering
        if ($recipes == false) {
            echo json_encode(['message' => '<h1 class="text-center text-secondary mt-5 display-3">No recipe found!!</h1>']);
            exit;
        } else {
            ob_start();
            // Include the view file, the $data variable will be used there
            require VIEWSDIR . DS . 'components' . DS . 'user' . DS . 'recipes' . DS . "recipes-table.php";
            // Store the buffer content into ¨$output variable
            $output = ob_get_clean();
            echo json_encode(['message' => $output]);
            exit;
        }
    }
    /**
     * Add a new recipe
     * 
     * Use the parameters from the POST request to create an object to send to RecipesModel,
     * then allow the user to choose an image from their directory to display with the recipe
     * , and put them all in the database
     * 
     * @return void
     */
    function addNewRecipe()
    {
        $name = $_POST['name'];
        $calories = $_POST['calories'];
        $visibility = $_POST['visibility'];
        $type = $_POST['type'];
        $File = $_FILES['file'];
        // Check if file was uploaded without errors
        if ($File['error'] == UPLOAD_ERR_OK) {
            // File is in $file['tmp_name']
            $tempFilePath = $File['tmp_name'];

            $destinationDirectory = 'public/images/recipesImages/';
            if (!file_exists($destinationDirectory)) {
                mkdir($destinationDirectory, 0777, true); // Création de dossier de destination s'il n'existe pas
            }
            $destination = $destinationDirectory . $_FILES['file']['name']; // Chemin de destination complet
            move_uploaded_file($tempFilePath, $destination);

            $data = [
                'name' => $name,
                'calories' => $calories,
                'image_url' => $_FILES['file']['name'], //nom de l'image
                'type' => $type,
                'visibility' => $visibility,

            ];
            if ($this->userModel->addRecipe($data)) {
                echo json_encode(['success' => true]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => "there is a probleme to add"]);
                exit;
            };
        }
    }

    /**
     * getUsersFromNotifications
     * 
     * Asks the Model for all notification senders' information,
     * in order to display them
     *
     * @return void
     */
    public function getUsersFromNotifications()
    {
        header('APPJSON');

        $data = $this->userModel->getUsersByNotifs();

        if ($data) {
            if ($data != "No notifications") {
                ob_start();
                include VIEWSDIR . DS . 'components' . DS . 'user' . DS . 'dashboard' . DS . 'senderNotifList.php';
                $output = ob_get_clean();
                echo json_encode(['success' => true, 'data' => $output]);
            } else {
                echo json_encode(['success' => true, 'data' => $data]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Users from notification query failed.']);
        }
    }

    /**
     * updateNotificationState
     * 
     * Asks the Model to update the notification, meaning either decline or accept it,
     * based on the content of the POST request
     *
     * @return void
     */
    public function updateNotificationState()
    {
        header('APPJSON');

        $data = $this->userModel->updateNotificationState();

        if ($data[0]) {
            echo json_encode(['success' => true, 'requestType' => $data[2], 'data' => $data[1]]);
        } else {
            echo json_encode(['success' => false, 'message' => $data[1]]);
        }
    }

    /**
     * addPlan
     * 
     * This function adds a new plan for the user based on the provided parameters. 
     * It invokes the addUserPlan method from the UserModel to handle the insertion 
     * of the plan into the database.
     * 
     * @param array $recipesData An array containing data about the recipes included in the plan
     * @param int $period The period of the plan in days
     * @param int $duration The total duration of the plan in days
     * @param string $planName The name of the plan provided by the user
     * @return void
     */

    public function addPlan($recipesData, $period, $duration, $planName)
    {
        if ($this->userModel->addUserPlan($recipesData, $period, $duration, $planName)) {
            echo json_encode(['success' => true, 'message' => "plan added"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'failed to insert plan']);
        }
    }
    /**
     * userHavePlan
     * 
     * This method checks if the user already has an existing plan by invoking 
     * the UserHavePlan method from the UserModel. It returns a JSON response 
     * indicating whether a plan is found or not. 
     * If a plan is found, it includes the plan's recipe details in data array.
     * 
     * @return void
     */
    public function userHavePlan()
    {
        if ($this->userModel->ifUserHavePlan()) {
            $result = $this->userModel->getPlanRecipesDetail();
            $data = $result['planRecipesDetails'];
            $planInfo = $result['planData'];
            echo json_encode(['success' => true, 'message' => 'PlanExist', 'data' => $data, 'planInfo' => $planInfo]);
        } else {
            echo json_encode(['success' => true, 'message' => 'noPlanExist']);
        }
    }

    /**
     * toggleRecipeConsumed
     * 
     * Toggles the consumed status of a specified recipe for a user.
     *
     * This method updates the status of a recipe to indicate whether it has been consumed or not by toggling
     * its current state in the database. It relies on a corresponding method in the userModel to perform the
     * actual database operation. The consumed status is a binary representation where '0' signifies that the
     * recipe has not been consumed, and '1' signifies that it has been consumed.
     *
     * Upon successfully toggling the consumed status, this method returns a JSON response indicating success
     * and a message stating that the recipe consumed status has been updated. If the operation fails, either
     * due to an error with the database update or because the specified recipe_id does not exist, it returns
     * a JSON response indicating failure along with an appropriate message.
     * 
     * This method expects that the $recipe_id provided is valid and exists within the database. It also
     * assumes that the userModel has a method `toggleRecipeConsumed` capable of executing the toggle operation
     * on the database level.
     *
     * @param int $recipe_id The unique identifier of the recipe whose consumed status is to be toggled.
     *
     * @return void Outputs a JSON-encoded response indicating the success or failure of the operation along
     *         with a descriptive message. The response format is:
     *         - On success: {'success': true, 'message': "Recipe consumed status updated!"}
     *         - On failure: {'success': false, 'message': 'Failed to toggle consumed'}
     */

    public function toggleRecipeConsumed($recipe_id)
    {
        if ($this->userModel->toggleRecipeConsumed($recipe_id)) {
            echo json_encode(['success' => true, 'message' => "Recipe consumed status updated!"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to toggle consumed']);
        }
    }
}
