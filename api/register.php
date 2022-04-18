<?php
include_once './config/database.php';

header("Access-Control-Allow-Origin: * ");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$firstName = '';
$lastName = '';
$email = '';
$password = '';
$retypepassword = '';
$conn = null;

$databaseService = new DatabaseService();
$conn = $databaseService->getConnection();

$data = json_decode(file_get_contents("php://input"));
if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    // The request is using the POST method
    
    if(!empty($data))
    {
        $firstName = $data->first_name;
        $lastName = $data->last_name;
        $email = $data->email;

        if(!check_email_availability($email)) {
            http_response_code(400);
            echo json_encode(array("message" => "Email is registered"));
            exit;
        }

        $password = $data->password;
        $retype_password = $data->retype_password;

        $valdiation_message = password_validation($password,$retype_password);
        if(!empty($valdiation_message)) {
            http_response_code(400);
            echo json_encode(array("message" => $valdiation_message));
            exit;
        }
        
        $table_name = 'fs_users';

        $query = "INSERT INTO " . $table_name . "
                        SET user_fname = :firstname,
                            user_lname = :lastname,
                            user_email = :email,
                            user_password = :password";

        $stmt = $conn->prepare($query);

        $stmt->bindParam(':firstname', $firstName);
        $stmt->bindParam(':lastname', $lastName);
        $stmt->bindParam(':email', $email);

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt->bindParam(':password', $password_hash);


        if($stmt->execute())
        {
            http_response_code(200);
            echo json_encode(array("message" => "User was successfully registered."));
        }
        else
        {
            http_response_code(400);

            echo json_encode(array("message" => "Unable to register the user."));
        }
    }
    else
    {
        http_response_code(400);

        echo json_encode(array("message" => "Missing parameters"));
    }
}
else
{
    http_response_code(400);

    echo json_encode(array("message" => "Request Not available"));
}





function password_validation($password,$retype_password)
{
    $passwordErr = "";
    if((!empty($password) && !empty($retype_password)) && ($password == $retype_password)) {

        if (strlen($password) <= '8') {
            $passwordErr = "Your Password Must Contain At Least 8 Characters!";
        }
        elseif(!preg_match("#[0-9]+#",$password)) {
            $passwordErr = "Your Password Must Contain At Least 1 Number!";
        }
        elseif(!preg_match("#[A-Z]+#",$password)) {
            $passwordErr = "Your Password Must Contain At Least 1 Capital Letter!";
        }
        elseif(!preg_match("#[a-z]+#",$password)) {
            $passwordErr = "Your Password Must Contain At Least 1 Lowercase Letter!";
        }
    }
    elseif((!empty($password) && !empty($retype_password))) {
        $passwordErr = "Please Check Entered Or Confirmed Password!";
    } else {
        $passwordErr = "Please enter password   ";
    }

    return $passwordErr;
}

function check_email_availability($email)
{
    $databaseService = new DatabaseService();
    $conn = $databaseService->getConnection();

    $table_name = 'fs_users';

    $query = "SELECT user_id FROM " . $table_name . " WHERE user_email = ? LIMIT 0,1";
    
    $stmt = $conn->prepare( $query );
    $stmt->bindParam(1, $email);
    $stmt->execute();

    $num = $stmt->rowCount();
    
    if($num == 0)
    {
        return true;
    }

    return false;
}

?>