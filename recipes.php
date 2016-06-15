<?php
/**
 * Created by IntelliJ IDEA.
 * User: bulent
 * Date: 12.04.2016
 * Time: 10:59
 */

class recipes extends recipeApp{

    public function __construct()
    {
        parent::__construct();
    }

    /*
     * URL: /ws/general.recipes.json/getCategories
     *
     * //
     * RESPONSE
     * [
          {
            "category_id": "1",
            "category_name": "Çorbalar",
            "recipe_count": "605"
          },
     *
     * */
    //Categories
    public function getCategories(){
        // $sql = "SELECT *, (SELECT COUNT(rc.recipe_id) FROM recipe_categories AS rc WHERE c.category_id = rc.category_id) AS recipe_count FROM categories AS c ORDER BY c.category_name";
        // $sql = "SELECT * FROM categories AS c, recipe_categories AS rc WHERE c.category_id = rc.category_id ORDER BY c.category_name";
        $sql = "SELECT c.category_id, c.category_name, COUNT(rc.recipe_id) AS recipe_count FROM categories AS c LEFT JOIN recipe_categories AS rc ON c.category_id = rc.category_id GROUP BY rc.category_id";
        $result = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
        echo $this->printMessage($result);
        return;
    }

    /*
     * URL: /ws/general.recipes.json/getCuisines
     *
     * //
     * RESPONSE
     * [
          {
            "cuisine_id": "1",
            "cuisine_name": "Türk",
            "recipe_count": "4321"
          },
     *
     * */
    //Cuisines
    public function getCuisines(){
        $sql = "SELECT c.cuisine_id, c.cuisine_name, COUNT(rc.recipe_id) AS recipe_count FROM cuisines AS c LEFT JOIN recipe_cuisines AS rc ON c.cuisine_id = rc.cuisine_id GROUP BY rc.cuisine_id";
        $result = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
        echo $this->printMessage($result);
        return;
    }


    /*
     * URL: /ws/general.recipes.json/getIngredients
     * {"order":"normal"} // {"order": "type"} // default order is "normal"
     * normal is just list of ingredients
     * type is igredients list of block with ingredient types.
     * //
     * RESPONSE
     * [
          {
            "ingredient_id": "3",
            "ingredient_type_id": "4",
            "ingredient_name": "Kuru Fasulye",
            "is_searchable": "1"
          },
     *
     * ....OR....
     *
     * [
          {
            "ingredient_type_id": "1",
            "ingredient_type": "Kırmızı Et",
            "item": [
              {
                "ingredient_id": "44",
                "ingredient_type_id": "1",
                "ingredient_name": "Koyun Eti",
                "is_searchable": "1"
              },
     * */
    //Ingredients for search
    public function getIngredients()
    {
        $order = isset($this->post_data->order) ? $this->post_data->order : 'normal';
        $sql = "SELECT * FROM ingredients WHERE is_searchable = '1' ORDER BY ingredient_type_id";

        if ($order == 'type') {

            $sql_type = "SELECT ingredient_type_id, ingredient_type FROM ingredient_type ORDER BY ordered";
            $result_type = $this->con->query($sql_type)->fetchAll(PDO::FETCH_ASSOC);
            $result = array();
            foreach($result_type AS $key=>$type){
                $sql = "SELECT * FROM ingredients WHERE is_searchable = '1' AND ingredient_type_id='$type[ingredient_type_id]' ORDER BY ingredient_id";
                $ingredients = $this->con->query($sql);
                $check          = $ingredients->rowCount();
                if($check > 0) {
                    $result[] = array_merge($type, array('item'=>$ingredients->fetchAll(PDO::FETCH_OBJ)));
                }
            }
        }else{
            $result = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
        }

        echo $this->printMessage($result);
        return;
    }


    /*
     * URL: /ws/general.recipes.json/getIngredientTypes
     *
     * //
     * RESPONSE
     * [
          {
            "ingredient_type_id": "1",
            "ingredient_type": "Kırmızı Et",
            "ordered": "1"
          }, ...
     *
     * */
    //Ingredients Types
    public function getIngredientTypes(){
        $sql = "SELECT * FROM ingredient_type ORDER BY ordered";
        $result = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
        echo $this->printMessage($result);
        return;
    }

    /*
     * URL: /ws/general.recipes.json/searchRecipeTitle
     * REQUEST
     * {"recipe_title":"Yulaf"}
     * {"recipe_title":"Yulaf"}
     * {"recipe_title":"Yulaf", "start":0, "end":20}  //DEFAULT start: 0, end:10
     *
     * RESPONSE
     * [
          {
            "recipe_id": "3346",
            "recipe_title": "Yulaf Ezmeli Kurabiye",
            "category_id": "18",
            "cuisine_id": "8"
          },
     * */
    public function searchRecipeTitle(){
        $title = $this->post_data->recipe_title;
        //return $title;
        $start = isset($this->post_data->start) ? $this->post_data->start : 0;
        $end = isset($this->post_data->end) ? $this->post_data->end : 10;

        $sql = "SELECT r.recipe_id, r.recipe_title, rc.category_id, rcu.cuisine_id FROM recipe AS r LEFT JOIN (recipe_categories AS rc, recipe_cuisines AS rcu) ON (r.recipe_id = rc.recipe_id AND r.recipe_id = rcu.recipe_id) WHERE r.status = '1' AND r.recipe_title LIKE '$title%' ORDER BY r.recipe_title LIMIT $start, $end";
        $result = $this->con->query($sql);

        $check          = $result->rowCount();
        if($check > 0){
            $recipes =  $result->fetchAll(PDO::FETCH_OBJ);
            echo $this->printMessage($recipes);
            return;
        }
        echo $this->printMessage(array());
        return;
    }

    /*
     * URL: /ws/general.recipes.json/getRecipes
     *
     * recipe_id, category_id, cuisine_id, recipe_title
     * ingredients
     *
     * REQUESTS
     * {"ingredients":[16,196]}
     * {"category_id":2}
     * {"recipe_id":9301}
     * {"cuisine_id":1}
     * {"recipe_title":"batlıcan"}
     * { ........         "start": 0, "end":2}   //DEFAULT start: 0, end:50
     *
     * RESPONSE
     * [
     *     {
     *       "recipe_id": "4684",
     *       "recipe_title": "Elmalı Yulaflı Cooke",
     *       "category_id": "18",
     *       "cuisine_id": "8"
     *     }, ...... ]
     * */
    public function getRecipes(){
        $obj = $this->post_data;

       // return $obj->ingredients;
        $start = isset($obj->start) ? $obj->start : 0;
        $end = isset($obj->end) ? $obj->end : 50;

        if(isset($obj->ingredients)){
            $krtr=implode(',',$obj->ingredients);
            $count=count($krtr);
            $sorgu = '';
            $sorgu3 = '';
            if($count>0){
                $sorgu3='WHERE ingredient_id IN('.$krtr.') ';
                if($count<=3){
                    $tp=$count-1;
                }else{
                    $tp=$count-(floor($count/2));
                }// DISTINCT m.ingredient_id,
                $srg="SELECT
                      m.recipe_id,
                      t.recipe_title,
                      rc.category_id, rcu.cuisine_id,
                      count(DISTINCT m.ingredient_id) AS ingredient_count
                      FROM recipe_ingredient AS m
                      LEFT JOIN (recipe AS t, recipe_categories AS rc, recipe_cuisines AS rcu)
                      ON (m.recipe_id=t.recipe_id AND t.recipe_id = rc.recipe_id AND t.recipe_id = rcu.recipe_id) $sorgu3 GROUP BY m.recipe_id
                      HAVING COUNT(DISTINCT m.ingredient_id)>=$tp ORDER BY ingredient_count DESC, t.recipe_title";
                // $sql1=$srg;
                $sql2=$srg." LIMIT $start,$end";
                $result = $this->con->query($sql2)->fetchAll(PDO::FETCH_OBJ);
                echo $this->printMessage($result);
                return;
            }
        }

        if(isset($obj->category_id)){
            $sql = "SELECT r.recipe_id, r.recipe_title, rc.category_id, rcu.cuisine_id FROM recipe AS r LEFT JOIN (recipe_categories AS rc, recipe_cuisines AS rcu) ON (r.recipe_id = rc.recipe_id AND r.recipe_id = rcu.recipe_id) WHERE r.status = '1' AND rc.category_id = '$obj->category_id' ORDER BY r.recipe_title LIMIT $start, $end";
            $recipes = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
            echo $this->printMessage($recipes);
            return;
        }

        if(isset($obj->cuisine_id)){
            $sql = "SELECT r.recipe_id, r.recipe_title, rc.category_id, rcu.cuisine_id FROM recipe AS r LEFT JOIN (recipe_categories AS rc, recipe_cuisines AS rcu) ON (r.recipe_id = rc.recipe_id AND r.recipe_id = rcu.recipe_id) WHERE r.status = '1' AND rcu.cuisine_id = '$obj->cuisine_id' ORDER BY r.recipe_title LIMIT $start, $end";
            $recipes = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
            echo $this->printMessage($recipes);
            return;
        }

        if(isset($obj->recipe_id)){
            $sql = "SELECT r.recipe_id, r.recipe_title, rc.category_id, rcu.cuisine_id FROM recipe AS r LEFT JOIN (recipe_categories AS rc, recipe_cuisines AS rcu) ON (r.recipe_id = rc.recipe_id AND r.recipe_id = rcu.recipe_id) WHERE r.status = '1' AND r.recipe_id = '$obj->recipe_id' ORDER BY r.recipe_title LIMIT $start, $end";
            $recipes = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
            echo $this->printMessage($recipes);
            return;
        }

        if(isset($obj->recipe_title)){
            $sql = "SELECT r.recipe_id, r.recipe_title, rc.category_id, rcu.cuisine_id FROM recipe AS r LEFT JOIN (recipe_categories AS rc, recipe_cuisines AS rcu) ON (r.recipe_id = rc.recipe_id AND r.recipe_id = rcu.recipe_id) WHERE r.status = '1' AND r.recipe_title LIKE '%$obj->recipe_title%' ORDER BY r.recipe_title LIMIT $start, $end";
            $result = $this->con->query($sql);

            $check          = $result->rowCount();
            if($check > 0){
                $recipes =  $result->fetchAll(PDO::FETCH_OBJ);
                echo $this->printMessage($recipes);
                return;
            }
            echo 'testtt';
            $sql = "SELECT r.recipe_id, r.recipe_title, rc.category_id, rcu.cuisine_id FROM recipe AS r LEFT JOIN (recipe_categories AS rc, recipe_cuisines AS rcu) ON (r.recipe_id = rc.recipe_id AND r.recipe_id = rcu.recipe_id) WHERE r.status = '1' AND (r.recipe_title LIKE '%$obj->recipe_title%' OR SOUNDEX(r.recipe_title)=SOUNDEX('$obj->recipe_title') OR r.recipe_title SOUNDS like '%$obj->recipe_title%') ORDER BY r.recipe_title LIMIT $start, $end";
            $result = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
            echo $this->printMessage($result);
            return;
        }


        echo $this->printMessage(array());
        return;
    }
    /*
     * URL: /ws/general.recipes.json/getRecipeDetail
     *
     * REQUEST
     * {"recipe_id":"777"}
     *
     * RESPONSE
     * [
          {
            "recipe_id": "777",
            "recipe_title": "Galeta Unlu Poğaça",
            "recipe_description": "Patatesleri haşlayıp kabuklarını soyun ve çatalla ezin.\r\nTuz, karabiber ve kırmızıbiber ekleyip iç malzemesini hazırlayın.\r\nMargarin, sıvıyağ, yoğurt, yumurta akı, kabartma tozu, un ve tuzu\r\nkarıştırıp kulak memesi yumuşaklığında hamur elde edin.\r\nYumurta büyüklüğünde parçalara ayırıp yuvarlayın.\r\nHer bir parçayı elinle açın ve içine patatesli karışımı yerleştirip kapatın.\r\nKenarlarına bir çatal yardımı ile şekil verin.\r\nÜzerlerine çırpılmış yumurta sarısı sürüp galeta ununa batırın ve\r\nyağlanmış fırın tepsisine dizin.\r\nOrta ısılı fırında pişirin.\r\nNOT : Poğaçaların sadece üzerine yumurta sarısı sürüp galeta ununa\r\nbulayın.\r\nAksi takdirde tepsiye yapışabilir.",
            "servings": "8",
            "preparation_time": "60 Dk",
            "status": "1",
            "category_id": "5",
            "cuisine_id": "1",
            "ingredients": [
              {
                "ingredient_id": "587",
                "prefix": "",
                "ingredient_name": "Hamuru İçin:",
                "suffix": ""
              },....]}]
     *
     * */
    public function getRecipeDetail(){
        $obj = $this->post_data;
        $recipe_id = $obj->recipe_id;
        $sql = "SELECT r.*, rc.category_id, rcu.cuisine_id FROM recipe AS r LEFT JOIN (recipe_categories AS rc, recipe_cuisines AS rcu) ON (r.recipe_id = rc.recipe_id AND r.recipe_id = rcu.recipe_id) WHERE r.status = '1' AND r.recipe_id = '$recipe_id' ORDER BY r.recipe_title LIMIT 0,1";
        $recipes = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);

        $result = array();
        $i = 0;
        foreach($recipes AS $recipe){
            $sql_ingredient = "SELECT ri.ingredient_id, ri.prefix, i.ingredient_name, ri.suffix FROM recipe_ingredient AS ri LEFT JOIN ingredients AS i ON ri.ingredient_id = i.ingredient_id WHERE ri.recipe_id = '$recipe->recipe_id' ";
            $ingredients = $this->con->query($sql_ingredient)->fetchAll(PDO::FETCH_OBJ);
            $recipe->ingredients = $ingredients;
            array_push($result, $recipe);
            //$result[] = $recipe;
            $i++;
        }
        echo $this->printMessage($result);
        return;
    }

    /*
     * URL: /ws/general.recipes.json/getDailySpecial
     *
     * //
     * RESPONSE
     * [
          {
            "field_title": "Ana Yemek",
            "special_date": "2016-04-12",
            "recipe_id": "346",
            "recipe_title": "Çeltik Kebabı",
            "category_id": "6",
            "cuisine_id": "1"
          },
          {
            "field_title": "Pilav",
            "special_date": "2016-04-12",
            "recipe_id": "719",
            "recipe_title": "Domatesli Pirinç Pilavı",
            "category_id": "7",
            "cuisine_id": "1"
          }, ...
     *
     * */
    public function getDailySpecial(){
        $date = date("Y-m-d");
        // $date = date('Y-m-d', strtotime(date("Y-m-d") . "+1 days") );
        $sql = "SELECT
        sf.title AS field_title, DATE_FORMAT(ds.special_date, '%Y-%m-%d') AS special_date, r.recipe_id, r.recipe_title, rc.category_id, rcu.cuisine_id
        FROM daily_special AS ds
        LEFT JOIN (recipe AS r, special_fields AS sf, recipe_categories AS rc, recipe_cuisines AS rcu)
        ON (ds.recipe_id=r.recipe_id AND ds.field_id = sf.field_id AND r.recipe_id = rc.recipe_id AND r.recipe_id = rcu.recipe_id)
        WHERE r.status = '1' AND DATE(ds.special_date) = DATE('$date')
        ORDER BY r.recipe_title LIMIT 0, 10";
        $recipes = $this->con->query($sql)->fetchAll(PDO::FETCH_OBJ);
        echo $this->printMessage($recipes);
        return;
    }

    /*
     * recipe_id = 5013
     * /ws/general.photo/5013
     * 120x90
     *
     * recipe_id = 5013
     * /ws/general.photo/5013_p
     * 400x300
     *
     * default 400x300
     *
    */
    public function getPhoto(){
        $obj = $this->post_data;
        $recipe_id = $obj->recipe_id;
        $path = '_tarif/';
        $file_name = $recipe_id.'.jpg';

        if(file_exists($path.$file_name)){
            $image = $path.$file_name;
        }else{
            $image = $path.'default.jpg';
        }

        $type = 'image/jpeg';

        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($image)) . ' GMT', true, 200);
        header('Content-Type:'.$type);
        header('Content-Length: ' . filesize($image));

        readfile($image);

        return;
    }

    /*
     * URL: /ws/general.recipes.json/searchImsakCity
     * REQUEST
     * {"city":"İsta"}
     * {"city":"Türki"}
     *
     * RESPONSE
     * [
          {
            "city_id": "68162",
            "state_code": "İstanbul",
            "city": "Akalan / Çatalca"
          },
          {
            "city_id": "32102",
            "state_code": "Tokat",
            "city": "Akçatarla"
          },
     * */
    public function searchImsakCity(){
        $city = $this->post_data->city;
        $start = isset($this->post_data->start) ? $this->post_data->start : 0;
        $end = isset($this->post_data->end) ? $this->post_data->end : 10;

        $sql = "SELECT c.city_id, c.state_code, c.city FROM cities AS c WHERE c.city LIKE '%$city%' OR c.state_code LIKE '%$city%' ORDER BY c.city, c.state_code LIMIT $start, $end";
        $result = $this->con->query($sql);

        $check          = $result->rowCount();
        if($check > 0){
            $cities =  $result->fetchAll(PDO::FETCH_OBJ);
            echo $this->printMessage($cities);
            return;
        }
        echo $this->printMessage(array());
        return;
    }


    /*
     * URL: /ws/general.recipes.json/searchImsakCity
     * REQUEST
     * // default city is İstanbul, city_id is 16741 and start_date is today, end_date is +1  for (server time)
     * {"city_id":68162} // default start_date is today, end_date +1 (server time)
     * {"city_id":68162, "start_date": "2016-04-13"} //Y-m-d //end_date default +1
     * {"city_id":68162, "start_date": "2016-04-13", "end_date": "2016-04-13"} //Y-m-d
     *
     * RESPONSE
     * {
          "city_id": "16741",
          "country": "Türkiye",
          "state_code": "İstanbul",
          "city": "İstanbul",
          "imsak": [
            {
              "imsak": "4:32",
              "gunes": "6:20",
              "ogle": "13:16",
              "ikindi": "16:59",
              "aksam": "19:50",
              "yatsi": "21:25",
              "thedate": "2016-04-13"
            },
            {
              "imsak": "4:30",
              "gunes": "6:18",
              "ogle": "13:16",
              "ikindi": "16:59",
              "aksam": "19:51",
              "yatsi": "21:27",
              "thedate": "2016-04-14"
            }
          ]
        }
     * */
    public function getImsak(){
        $city_id = isset($this->post_data->city_id) ? $this->post_data->city_id : '16741';
        $start_date = isset($this->post_data->start_date) ? $this->post_data->start_date : date("Y-m-d");

        if(!isset($this->post_data->end_date)){
            $date = new DateTime($start_date);
            $date->modify("+1 days");
            $end_date = $date->format("Y-m-d");
        }else{
            $end_date = $this->post_data->end_date;
        }

        $sql_city = "SELECT c.city_id, u.country, c.state_code, c.city FROM cities AS c LEFT JOIN countries AS u ON c.country_id = u.country_id WHERE c.city_id='$city_id' LIMIT 0, 1";
        $result_city = $this->con->query($sql_city)->fetch(PDO::FETCH_ASSOC);


        $sql = "SELECT i.imsak, i.gunes, i.ogle, i.ikindi, i.aksam, i.yatsi, i.thedate FROM imsak AS i WHERE i.city_id='$city_id' AND i.thedate BETWEEN '$start_date' AND '$end_date' ORDER BY i.dayofyear LIMIT 0, 365";
        $result = $this->con->query($sql);

        $check          = $result->rowCount();
        if($check > 0){
            $imsak =  array('imsak'=>$result->fetchAll(PDO::FETCH_OBJ));
            echo $this->printMessage(array_merge($result_city, $imsak));
            return;
        }
        echo $this->printMessage(array());
        return;
    }

}
