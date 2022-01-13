<?php
include_once './config/database.php';
require __DIR__.'/classes/JwtHandler.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");



$jwt = null;
$databaseService = new DatabaseService();
$conn = $databaseService->getConnection();

$data = json_decode(file_get_contents("php://input"));


$authHeader = $_SERVER['HTTP_AUTHORIZATION'];

$arr = explode(" ", $authHeader);

$jwt = $arr[1];

if($jwt){

    try {
        $jwtObj = new JwtHandler();
        $data = $jwtObj->jwtDecodeData($jwt);
        // Access is granted. Add code of the operation here 

        if(!empty($data['data']))
        {
            echo json_encode(array(
                "message" => "Access granted:",
                "decodedData" => $data
            ));
        }
        else
        {
            throw new Exception($data['message']);
        }

    }catch (Exception $e){

    http_response_code(401);

    echo json_encode(array(
        "message" => "Access denied.",
        "error" => $e->getMessage()
    ));
}

}
?>