<?

include 'views/geo.php';

function get_icon($resource){
  foreach($resource->types() as $type){
    $t = get_icon_from_type($type);
    if($t) { return $t; }
  }
}

function get_icon_from_type($type){
  $icons = array(
       "asext:Consume" => "&#127860;"
      ,"asext:Acquire" => "&#128176;"
      ,"asext:Sleep" => "&#128164;"
      ,"as:Article" => "&#128196;"
      ,"as:Note" => "&#128493;"
      ,"as:Like" => "&#10030;"
      ,"as:Add" => "&#43;"
      ,"as:Announce" => "&#128257;"
      ,"as:Arrive" => "&#11165;"
      ,"as:Follow" => "&#128483;"
      ,"as:Event" => "&#128467;"
      ,"as:Accept" => "&#128467;"
      ,"as:Invite" => "&#128467;"
      ,"as:Travel" => "&#10239;"
      ,"as:Object" => "&#133;"
    );
  if(isset($icons[$type])){
    return $icons[$type];
  }else{
    return false;
  }
}

function get_travel_icon($tag){
  $icons = array(
     "bus" => "&#128652;"
    ,"car" => "&#128664;"
    ,"plane" => "&#9992;"
    ,"boat" => "&#128741;"
    ,"walk" => "&#128694;"
    ,"train" => "&#128645;"
  );
  if(isset($icons[$tag])){
    return $icons[$tag];
  }else{
    return false;
  }
}

function get_travel_icon_from_tags($tags){
  foreach($tags as $tag){
    $icon = get_travel_icon($tag->getValue());
    if($icon){
      return $icon;
    }
  }
  return get_icon_from_type('as:Travel');
}

function get_locations($ep){
  $q = query_for_places();
  $r = execute_query($ep, $q);
  if($r){
    $g = new EasyRdf_Graph();
    $g->parse($r, 'php');
    return $g;
  }
  return null;
}

function score_predicates(){
  return array(
      'view:banality'
    , 'view:intimacy'
    , 'view:tastiness'
    , 'view:wanderlust'
    , 'view:informative'
  );
}

function set_views($ep, $resource){
  
  if(!$resource->get('view:css')){
    $resource->addLiteral('view:stylesheet', 'views/'.get_style($resource).".css");
  }

  // Background colour for places and checkins
  if($resource->get('view:color') && !$resource->get('view:css')){
    $resource->addLiteral('view:css', "body { background-color: ".$resource->get('view:color')."; }\n");
  }
  if($resource->isA('as:Arrive')){
    $loc = get($ep, $resource->get('as:location'));
    $loc = $loc['content'];
    $resource->addLiteral('view:css', "body { background-color: ".$loc->get($resource->get('as:location'), 'view:color')."; }\n");
  }


  return $resource;
}

function get_style($resource){

  $score = array();
  $scorepreds = score_predicates();
  
  foreach($scorepreds as $p){
    if($resource->hasProperty($p)){ 
      $score[$p] = $resource->get($p)->getValue();
    }
  }

// $food = array(5,3,5,0,0);
// $lyric = array(5,5,0,0,0);
// $wg = array(0,0,0,0,4);
// $phd = array(0,1,0,0,5);
// $trek = array(4,4,0,0,0);
// $checkin = array(5,3,0,4,0);
// $feels = array(0,5,0,0,1);

  // array ( name of stylesheet => minimum scores required to trigger )
  $styles = array(
       "base" => array()
      ,"banal" => array("view:banality" => 3)
      ,"intimate" => array("view:intimacy" => 3)
      ,"tasty" => array("view:tastiness" => 3)
      ,"wander" => array("view:wanderlust" => 3)
      ,"scholar" => array("view:informative" => 3)
      ,"checkin" => array("view:wanderlust" => 4, "view:banality" => 5, "view:intimacy" => 5)
      ,"travel" => array("view:wanderlust" => 5, "view:banality" => 3, "view:intimacy" => 5)
    );

  $s = array();
  foreach($styles as $name => $numbers){
    
    if($numbers == $score){
      return $name;
    }else{
      foreach($numbers as $pred => $val){
        if(isset($score[$pred])){
          if($score[$pred] >= $val){
            $s[$name] = $styles[$name];
          }else{
            unset($s[$name]);
            break;
          }
        }
      }

    }
  }
  arsort($s);
  return key($s);
}
?>