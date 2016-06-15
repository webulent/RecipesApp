<?php
/**
 * Created by IntelliJ IDEA.
 * User: bulent
 * Date: 12.04.2016
 * Time: 11:21
 */

ob_start();

header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');

//print_r($_GET);
/*
 * Array
# (
#    [fn_class] => recipe
#    [data_type] => json
#    [fn_caller] => getRecipe
# )
 * */

if(isset($_GET['fn_class'])){
    require_once "pdoConnection.php";
    require_once ("recipeApp.php");
    require_once ("recipes.php");

    $class = trim($_GET['fn_class']);
    $app = new $class();
    $app->data_type = isset($_GET['data_type']) ? $_GET['data_type'] : 'json';
    $fn_caller = isset($_GET['fn_caller']) ? $_GET['fn_caller'] : 'welcome';

    if(file_get_contents('php://input') and isset($_POST) and count( $_POST ) == 0){
        $json = file_get_contents('php://input');
        $obj = json_decode($json, true);
        $post_data = (object)$obj;
    }else if(isset($_POST) && count( $_POST ) != 0){
        $obj = json_decode($_POST, true);
        $post_data = (object)$obj;
    }

    if(isset($_GET['recipe_id']) && $_GET['fn_caller'] == 'getPhoto'){
        $post_data = (object)array('recipe_id'=>$_GET['recipe_id']);
    }

    $app->post_data = $post_data;

    $app->$fn_caller();
}


ob_end_flush();