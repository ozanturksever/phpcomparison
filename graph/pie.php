<?php

	require_once "../library/mysql.class.php";
	require_once "../library/functions.php";

	$db = new mysql;
	$db->connect() or die("db connection error");
	
	$test = get('test')=='loop'?'loop':'assign';
	$type = get('type')=='memory'?'memory':'execution_time';
	$summary = $db->get_list( "SELECT template_engine AS name, avg(execution_time) AS execution_time, avg(memory) AS memory FROM template_benchmark WHERE test='$test' GROUP BY template_engine ORDER BY template_engine" );

	$template_tested = $db->get_list( "SELECT template_engine FROM template_benchmark WHERE test='$test' GROUP BY template_engine ORDER BY template_engine", "template_engine", "template_engine" );

	if( $template_selected = get( 'template' ) ){
		$where = "WHERE test='$test' AND (";
		$i=0;
		foreach( $template_selected as $tpl => $on ){
			$where .= $i==0?" template_engine='$tpl'":" OR template_engine='$tpl'";
			$i=1;
		}
		$where .= ")";
	}
	else
		$where = "WHERE test='$test'";

	$rows = $db->get_list( "SELECT template_engine, n, avg(execution_time) AS execution_time, avg(memory) AS memory FROM template_benchmark $where GROUP BY template_engine, n ORDER BY n, template_engine" );
	$template_show = $db->get_list( "SELECT template_engine, avg(execution_time) AS execution_time FROM template_benchmark $where GROUP BY template_engine ORDER BY n, template_engine", "template_engine", "template_engine" );
	$nrows = $db->get_list( "SELECT n FROM template_benchmark $where GROUP BY n" );

	$color = array('#3366cc','#dc3912','#ff9900','#109618','#990099','#0099c6','#dd4477' );
	$color_sel = "colors:[";
	$nc = sizeof($color);
	$i=0;$cs=false;
	foreach( $template_tested as $template ){
		if( isset($template_show[ $template ] )){
			$color_sel .= $cs ? ",'".$color[$i%$nc] . "'" : "'".$color[$i%$nc] . "'" ;
			$cs=true;
		}
		$i++;
	}
	$color_sel .= "],";


	$tot = 0;
	 foreach( $summary as $name => $res )
	 	$tot += round($res[$type]);
	 
	 $pie = array();
	 foreach( $summary as $name => $res ){
	 	if( isset( $template_show[$res['name']] ) ){
	 		
	 		if( $type=='memory')
	 			$pie[$res['name']] = round( $res[$type] / 1024 );
	 		else
	 			$pie[$res['name']] = round( $res[$type] );
	 	}
	 }
     
    $html = "data.addColumn('number', 'speed');";
	$html .= "data.addRows(".(count($summary)).");\n";

     $i=0;
    foreach( $pie as $name => $ex ){
    	$html .= "data.setValue($i, 0,'$name');";
        $html .= "data.setValue($i, 1, $ex);";
        $i++;
    }
		
  	

?>


<html>
	<head>
		<link href="../graph/style.css" type="text/css" rel="stylesheet"/>
		    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
		    <script type="text/javascript">
		    	google.load("visualization", "1", {packages:["corechart"]});
				google.setOnLoadCallback(drawChart);
				function drawChart() {
					// Create and populate the data table.
					var data = new google.visualization.DataTable();
					data.addColumn('string', '<?php echo $type=='memory'?'Memory (KB)':'Execution Time (�s)'; ?>' );

					<?php echo $html; ?>

        			var chart = new google.visualization.PieChart(document.getElementById('pie_chart'));
        			chart.draw(data, {width: 430, height: 350, <?echo $color_sel; ?> is3D:true, title: '<?php echo $type; ?>'});
				}
			</script>
		</head>
	<body>
		<div id="pie_chart"></div>
	</body>
</html>