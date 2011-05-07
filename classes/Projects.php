<?php

 class Projects extends Application{

  function __construct($args){
   // Searching for a method name and calling either it or default method
   if(is_array($args) && count($args) && method_exists($this, strtolower($args[0]).'Action')){
	call_user_func_array(array($this, strtolower($args[0]).'Action'), array_slice($args, 1));
   }else{
	$this->defaultAction();
   }
  }
  
  function defaultAction(){
   global $prefix;
  
   a_header('Pages');

   echo '<table cellpadding=5 cellspacing=0 width=100% class=t1>
          <tr><td class=he2><b>Keyword</b></td>
              <td class=he2 width=150><b>Language</b></td>
              <td class=he2 width=150><b>Geo Code</b></td>
              <td align=center class=he2 width=190><b>Action</b></td>
          </tr>';
   $query = 'SELECT * FROM '.$prefix.'query';
   $res = mysql_query($query);
   while($res && $page = mysql_fetch_object($res))
    echo '<tr>
           <td align=center class=rw>'.$page->query_q.'</td>
           <td class=rw align=center>'.($page->query_lang ? $page->query_lang : '-').'</td>
           <td class=rw align=center>'.($page->query_geocode ? $page->query_geocode : '-').'</td>
           <td class=rw align=center>
            <a href="'.$this->getUrl('projects/results/'.$page->query_id).'">results</a> -
            <a href="'.$this->getUrl('projects/edit/'.$page->query_id).'">edit</a>
           </td>
          </tr>';
   echo '</table><br /><input type="button" class="bu" value="Add Query" onclick="location.href = \''.$this->getUrl('projects/edit').'\'" />';

   a_footer();  
  }
  
  function editAction($id = 0){
   global $prefix;
   
    a_header('');

    if($id){
     $query = 'SELECT * FROM '.$prefix.'query WHERE query_id = '.$id;
     $res = mysql_query($query);
     if($res && mysql_num_rows($res)){
      $page = mysql_fetch_object($res);
	 }
	}

    echo '<form method=post action="'.$this->getUrl('projects/save').'"><input type=hidden name=id value="'.(int)$id.'">
           <table>
            <tr><td>Keyword:</td><td><input type="text" value="'.$page->query_q.'" name="query_q" /></td></tr>
            <tr><td>Language:</td><td><input type="text" value="'.$page->query_lang.'" name="query_lang" /></td></tr>
            <tr><td>Geo Code:</td><td><input type="text" value="'.$page->query_geocode.'" name="query_geocode" /></td></tr>
           </table><br />
           <div align="center"><input type=submit value="&nbsp; Save &nbsp;" class=bu> <input type=button value="Cancel" class=bu onclick="location.href = \''.$this->getUrl('projects').'\'"></div>
          </form>';
          
    a_footer();  
  }
  
  function saveAction(){
   global $prefix;
   
   $query = 'SELECT * FROM '.$prefix.'query WHERE query_id = '.$_POST['id'];
   $res = mysql_query($query);
   if($res && mysql_num_rows($res)){
     $query = 'UPDATE '.$prefix.'query 
                  SET query_q = "'.$_POST['query_q'].'", 
                      query_lang = "'.$_POST['query_lang'].'",
                      query_geocode = "'.$_POST['query_geocode'].'" 
                WHERE query_id = "'.$_POST['id'].'"';
   }else{
     $query = 'INSERT INTO '.$prefix.'query 
                  SET query_q = "'.$_POST['query_q'].'", 
                      query_lang = "'.$_POST['query_lang'].'",
                      query_geocode = "'.$_POST['query_geocode'].'"';
   }
   $res = mysql_query($query);
   header('Location: '.$this->getUrl('projects'));
  }
  
  function resultsAction($id){
   global $prefix;
   
    a_header('');

    $query = 'SELECT * FROM '.$prefix.'query WHERE query_id = '.(int)$id;
    $res = mysql_query($query);
    $current_query = mysql_fetch_object($res);

?>

<script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>
<script type="text/javascript" src="js/highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>
<script type="text/javascript">
                
 var chart;
 $(document).ready(function() {
  var options = {
   chart: { renderTo: 'container' },
   title: { text: 'Daily Twitter vs. Facebook' },
   subtitle: { text: 'Keyword: <?php echo $current_query->query_q ?> Language: <?php echo $current_query->query_lang ?>' },
   xAxis: {
    type: 'datetime',
    tickInterval: 7 * 24 * 3600 * 1000, // one week
    tickWidth: 0,
    gridLineWidth: 1,
    labels: { align: 'left', x: 3, y: -3 }
   },
   yAxis: [{ // left y axis
    title: { text: null },
    labels: { align: 'left', x: 3, y: 16, formatter: function() { return Highcharts.numberFormat(this.value, 0); } },
    showFirstLabel: false
   }, { // right y axis
    linkedTo: 0,
    gridLineWidth: 0,
    opposite: true,
    title: { text: null },
    labels: { align: 'right', x: -3, y: 16, formatter: function() { return Highcharts.numberFormat(this.value, 0); } },
    showFirstLabel: false
   }],
   legend: { align: 'left', verticalAlign: 'top', y: 20, floating: true, borderWidth: 0 },                                        
   tooltip: { shared: true, crosshairs: true },
   plotOptions: { series: { marker: { lineWidth: 1 } } },
   series: [{name: 'Facebook'}, {name: 'Twitter', lineWidth: 4, marker: {radius: 5}}]
  }

  var twitter = [], facebook = [];

<?php

 $query = 'SELECT e1.search_entity_value `source`, e2.search_entity_value `date` 
             FROM '.$prefix.'search s
       INNER JOIN '.$prefix.'search_entity e1 ON s.search_id = e1.search_id
       INNER JOIN '.$prefix.'search_entity e2 ON s.search_id = e2.search_id
            WHERE s.query_id = '.(int)$id.'
              AND e1.search_entity_name = "source"
              AND e2.search_entity_name = "published"
         GROUP BY s.search_id';
 $res = mysql_query($query);
 $t_cnt = $f_cnt = array();
 while($obj = mysql_fetch_object($res)){
  $darr = getdate($obj->date);
  $date = mktime(0, 0, 0, $darr['mon'], $darr['mday'], $darr['year']);
  $obj->source == twitter ? $t_cnt[$date]++ : $f_cnt[$date]++;
  $min_date = isset($min_date) && $min_date < $date ? $min_date : $date;
  $max_date = isset($max_date) && $max_date > $date ? $max_date : $date;
 }
 for($date = $min_date; $date <= $max_date; $date += 86400){
  echo 'twitter.push(['.($date*1000).', '.(int)$t_cnt[$date].']);';
  echo 'facebook.push(['.($date*1000).', '.(int)$f_cnt[$date].']);'; 
 }

?>
  options.series[0].data = facebook;
  options.series[1].data = twitter;
  chart = new Highcharts.Chart(options);
                                
 });                                
</script>
                
<div id="container" style="width:650px; height:300px; margin:0 auto"></div>

<?php

    $query = 'SELECT s.search_id 
                FROM '.$prefix.'search s
          INNER JOIN '.$prefix.'search_entity se ON se.search_id = s.search_id
               WHERE s.query_id = '.(int)$id.'
                 AND se.search_entity_name = "published"
            ORDER BY se.search_entity_value DESC';
    $res = mysql_query($query);

    while($res && $search = mysql_fetch_object($res)){
     $query = 'SELECT * FROM '.$prefix.'search_entity WHERE search_id = '.(int)$search->search_id;
     $re2 = mysql_query($query);
     $entity = array();
     while($re2 && $obj = mysql_fetch_object($re2)){
      if($obj->search_entity_name == 'link'){
       if(!isset($entity['link'])){
        $entity['link'] = array();
       }
       $entity['link'][] = json_decode(stripslashes($obj->search_entity_value));
      }else{
       $entity[$obj->search_entity_name] = $obj->search_entity_value;
      }
     }
     echo '<div class="results-container'.($entity['source'] == 'facebook' ? ' facebook' : '').'"><div class="left">';
     if($entity['source'] == 'facebook'){
      echo '<img src="images/fb.jpg" alt="" />';
     }else{
      while(is_array($entity['link']) && list($key, $link) = each($entity['link'])){
       if($link->{'@attributes'}->type == 'image/png'){
        echo '<a href="'.$entity['author-uri'].'" target="_blank" title="'.$entity['author-name'].'" onclick="blur();"><img src="'.$link->{'@attributes'}->href.'" alt="'.$entity['author-name'].'" /></a>';
       }
      }
     }
     echo '</div><div class="left msg-text"><strong>'.$entity['author-name'].'</strong><div class="date">'.date('F jS, Y H:i', $entity['published']).'</div>'.$entity['content'].'</div><div class="clear"></div></div>';
    }

    a_footer();   
  }

 } 

?>