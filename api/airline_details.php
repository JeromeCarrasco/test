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

$requestData = json_decode(file_get_contents("php://input"));


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
            $result = redirect_function($requestData,$data);
            /*echo json_encode(array(
                "message" => "Access granted:",
                "decodedData" => $data
            ));*/
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
    exit;
    }

    echo $result;
}


function redirect_function($responseData,$decodedData)
{
    switch($responseData->action){
        case "showAllAirline":
            return get_airline_details($responseData,$decodedData);
        break;
        case "":

        break;

        default:

    }
   
}


function get_airline_details($responseData,$decodedData)
{
    $databaseService = new DatabaseService();
    $conn = $databaseService->getConnection();


    $additional_filters = "";
    
    if(property_exists($responseData, 'source') && !empty($responseData->source))
    {
        $additional_filters .= " AND atp.atp_source like :source ";
    }

    if(property_exists($responseData, 'destination') && !empty($responseData->destination))
    {
        $additional_filters .= " AND atp.atp_destination like :destination ";
    }

    if(property_exists($responseData, 'depart_date') && !empty($responseData->depart_date))
    {
        $additional_filters .= " AND atp.atp_departure_date = :depart_date ";
    }


    if(property_exists($responseData, 'depart_time') && !empty($responseData->depart_time))
    {
        $additional_filters .= " AND atp.atp_departure_time = :depart_time ";
    }

    if(property_exists($responseData, 'arrival_time') && !empty($responseData->arrival_time))
    {
        $additional_filters .= " AND atp.atp_arrival_time = :arrival_time ";
    }

    if(property_exists($responseData, 'head_count') && !empty($responseData->head_count))
    {
        $additional_filters .= " AND atp.ad_total_capacity > :head_count ";
    }


    $query = "  SELECT 	atp.atp_id as id,
                    atp.atp_source as source,
                    atp.atp_destination	as destination,
                    atp.atp_departure_time as depart_time,
                    atp.atp_departure_date as depart_date,
                    atp.atp_arrival_time as arrival_time,
                    atp.atp_arrival_date as arrival_date,
                    atp.atp_duration as flight_duration,
                    atp.atp_travel_price as price,
                    ad.ad_airline_name as airlin_name,
                    ad.ad_airline_code as airline_code,
                    ad.ad_airline_number as airline_number,
                    ad.ad_total_capacity as capacity
                FROM `fs_airline_travel_plan` as atp 
                JOIN fs_airline_details as ad 
                ON atp.ad_id = ad.ad_id
                WHERE atp.status = 1
                AND ad.ad_airline_status = 1 ";
    $query .= $additional_filters;
    $query .= " ; ";
    $stmt = $conn->prepare( $query );

    if(property_exists($responseData, 'source')  && !empty($responseData->source))
    {
        $stmt->bindParam(":source",$responseData->source);
    }

    if(property_exists($responseData, 'destination')  && !empty($responseData->destination))
    {
        $stmt->bindParam(":destination",$responseData->destination);
    }

    if(property_exists($responseData, 'depart_date')  && !empty($responseData->depart_date))
    {
        $stmt->bindParam(":depart_date",$responseData->depart_date);
    }

    if(property_exists($responseData, 'depart_time')  && !empty($responseData->depart_time))
    {
        $stmt->bindParam(":depart_time",$responseData->depart_time);
    }

    if(property_exists($responseData, 'arrival_time')  && !empty($responseData->arrival_time))
    {
        $stmt->bindParam(":arrival_time",$responseData->arrival_time);
    }

    if(property_exists($responseData, 'head_count')  && !empty($responseData->head_count))
    {
        $stmt->bindParam(":head_count",$responseData->head_count);
    }

   
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return json_encode($result);
}

?>