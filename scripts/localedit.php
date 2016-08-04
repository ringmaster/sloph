<?
session_start();
require_once('../vendor/init.php');

if(isset($_GET['reset'])){ unset($_SESSION[$_GET['reset']]); }

function remove_empty($haystack){
  foreach ($haystack as $property => $values) {
    if (is_array($values)) {
      foreach($values as $k => $item)
        if(empty($item["value"])){
          unset($haystack[$property][$k]);
        }
    }

    if (empty($haystack[$property])) {
        unset($haystack[$property]);
    }
  }
  return $haystack;
}

function plustype($i=0){
  $types = array(
      "http://www.w3.org/ns/activitystreams#" => array("Actor", "Person", "Note", "Article", "Profile", "Organization", "Event", "Arrive", "Activity", "Object", "Like", "Announce", "Add", "Travel", "Accept", "Place", "Collection"),
      "http://vocab.amy.so/blog#" => array("Consumption", "Acquisition")
    );
  $out = '<p><label><strong>+ type</strong>: </label>';
  $out .= '  <select name="data[http://www.w3.org/1999/02/22-rdf-syntax-ns#type]['.$i.'][value]">';
  $out .= '    <option value="">none</option>';
  foreach($types as $base => $frags){
    foreach($frags as $type){
      $out .= '    <option value="'.$base.$type.'">'.$type.'</option>';
    }
  }
  $out .= '  </select>';
  $out .= '<input type="hidden" name="data[http://www.w3.org/1999/02/22-rdf-syntax-ns#type]['.$i.'][type]" value="uri" /></p>';
  return $out;
}

function delete($ep, $uri){
  $q = query_delete($uri);
  //$r = execute_query($ep, $q);
  return $q;
}

function insert($ep, $turtle){
  $q = query_insert($turtle);
  //$r = execute_query($ep, $q);
  return $q;
}

if(count($_POST['data']) > 0){
  $_POST = $_POST['data'];
  $newgraph = new EasyRdf_Graph($_POST['uri']);
  $uri = $_POST['uri'];
  unset($_POST['uri']);
  $rdfphp[$uri] = remove_empty($_POST);
  $newgraph->parse($rdfphp, 'php');
  var_dump($_POST);
  $turtle = $newgraph->serialise('ntriples');
  var_dump($turtle);
  $result[$uri] = $turtle . "\n-------\n" . delete($ep, $uri) . "\n-------\n" . insert($ep, $turtle);
}

if(!isset($_SESSION['uris'])){
  $q = query_select_s();
  $r = execute_query($ep, $q);
  if($r){
    $_SESSION['uris'] = select_to_list($r, array("uri"));
  }
}

if(isset($_GET['offset']) && is_numeric($_GET['offset'])){
  $offset = $_GET['offset'];
}else{
  $offset = 0;
}
if(isset($_GET['length']) && is_numeric($_GET['length'])){
  $length = $_GET['length'];
}else{
  $length = 20;
}

$uris = array_slice($_SESSION['uris'], $offset, $length);
$posts = array();
$posts = construct_uris($ep, $uris);

?>
<!doctype html>
<html>
  <head>
    <title>Local edit</title>
    <style>
      label { width: 32em; display: inline-block; text-align: right; }
      pre { max-height: 32em; overflow: auto; float: left; border: 1px solid silver; }
      input, textarea { max-width: 100%; border: 1px solid silver; padding: 0.4em; }
      textarea { width: 72em; height: 16em; }
      .info { background-color: #abcdef; padding: 0.4em; font-family: sans-serif; }
      hr { border: 2px solid #abcdef; }
    </style>
  </head>
  <body>
    <div class="info">
      <p>Resources <?=$offset?> to <?=$offset+$length?> of <?=count($_SESSION['uris'])?> | <a href="?offset=<?=$offset-$length?>">prev</a> | <a href="?offset=<?=$offset+$length?>">next</a> | <a href="?reset=uris">reset</a></p>
    </div>
    <?foreach($posts as $uri => $post):?>
      <form id="<?=$uri?>" method="post" action="#<?=$uri?>">
        <p><a href="<?=$uri?>"><?=$uri?></a> <input type="submit" value="Save"/></p>
        <input type="hidden" value="<?=$uri?>" name="data[uri]" />
        <?if(isset($result[$uri])):?>
          <div style="overflow:hidden; width: 100%;">
            <pre>
              <?=htmlentities($result[$uri])?>
            </pre>
          </div>
        <?endif?>
        <?if(!isset($post['http://www.w3.org/1999/02/22-rdf-syntax-ns#type'])):?>
          <?=plustype()?>
        <?endif?>
        <?foreach($post as $k => $vs):?>
          <p><label><?=$k?>: </label>
           <?foreach($vs as $i => $v):?>
              <?if(strlen($v['value']) > 120):?>
                <textarea name="data[<?=$k?>][<?=$i?>][value]"><?=$v['value']?></textarea>
              <?else:?>
                <input name="data[<?=$k?>][<?=$i?>][value]" type="text" value="<?=$v['value']?>" style="width: <?=strlen($v['value']) * 8?>px; max-width: 100%" />
              <?endif?>
              <input name="data[<?=$k?>][<?=$i?>][type]" type="hidden" value="<?=$v["type"]?>" />
              <?if(isset($v["datatype"])):?>
                <input name="data[<?=$k?>][<?=$i?>][datatype]" type="text" value="<?=$v["datatype"]?>" />
              <?endif?>
          <?endforeach?>
          </p>
          <p>
            <?if($k == "http://www.w3.org/1999/02/22-rdf-syntax-ns#type"):?>
              <?=plustype($i+1);?>
            <?else:?>
              <label><strong>+</strong></label>
              <input type="text" name="data[<?=$k?>][<?=count($vs)?>][value]" style="width: 8em;" />
              <select name="data[<?=$k?>][<?=count($vs)?>][type]">
                <option value="literal">lit</option>
                <option value="uri">uri</option>
              </select>
              <select name="data[<?=$k?>][<?=count($vs)?>][datatype]">
                <option value="">none</option>
                <option value="http://www.w3.org/2001/XMLSchema#dateTime">dateTime</option>
              </select>
            <?endif?>
          </p>
        <?endforeach?>
      </form>
      <hr/>
    <?endforeach?>
    <div class="info">
      <p>Resources <?=$offset?> to <?=$offset+$length?> of <?=count($_SESSION['uris'])?> | <a href="?offset=<?=$offset-$length?>">prev</a> | <a href="?offset=<?=$offset+$length?>">next</a> | <a href="?reset=uris">reset</a></p>
    </div>
  </body>
</html>