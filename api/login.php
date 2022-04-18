<?php
include_once './config/database.php';

require __DIR__.'/classes/JwtHandler.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $email = '';
    $password = '';
    $data = json_decode(file_get_contents("php://input"));
    $email = $data->email;
    $password = $data->password;
    if(!empty($email) && !empty($password))
    {
        $databaseService = new DatabaseService();
        $conn = $databaseService->getConnection();
    
        $table_name = 'fs_users';
    
        $query = "SELECT user_id, user_fname, user_lname, user_password FROM " . $table_name . " WHERE user_email = ? LIMIT 0,1";
    
        $stmt = $conn->prepare( $query );
        $stmt->bindParam(1, $email);
        $stmt->execute();
        $num = $stmt->rowCount();
    
        if($num > 0){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $id = $row['user_id'];
            $firstname = $row['user_fname'];
            $lastname = $row['user_lname'];
            $password_hash = $row['user_password'];
    
            if(password_verify($password, $password_hash))
            {

                http_response_code(200);

                $jwtObj = new JwtHandler();
                $token = $jwtObj->jwtEncodeData(
                    'http://localhost/flight_cms/api/',
                    array(
                        "id" => $id,
                        "firstname" => $firstname,
                        "lastname" => $lastname,
                        "email" => $email
                ));

                echo json_encode(
                    array(
                        "message" => "Successful login.",
                        "jwt" =>$token
                    ));
            }
            else{
    
                http_response_code(401);
                echo json_encode(array("message" => "Login failed.", "password" => $password));
            }
        }
        else{
    
            http_response_code(401);
            echo json_encode(array("message" => "Login failed.", "password" => $password));
        }
    }
    else
    {
        http_response_code(400);
        echo json_encode(array("message" => "Email or Password empty"));
    }
}
else
{
    http_response_code(400);
    echo json_encode(array("message" => "Request Not available"));
}
?>