<?php
  if(isset($_GET['getStatus'])){
    $descArray = array(
                  '<th>Srv</th>'=>'<th title="Child Server number - generation">Srv</th>',
                  '<th>PID</th>'=>'<th title="OS process ID">PID</th>',
                  '<th>Acc</th>'=>'<th title="Number of accesses this connection / this child / this slot">Acc</th>',
                  '<th>M</th>'=>'<th title="Mode of operation">M</th>',
                  '<th>SS</th>'=>'<th title="Seconds since beginning of most recent request">SS</th>',
                  '<th>Req</th>'=>'<th title="Milliseconds required to process most recent request">Req</th>',
                  '<th>Dur</th>'=>'<th title="Sum of milliseconds required to process all requests">Dur</th>',
                  '<th>Conn</th>'=>'<th title="Kilobytes transferred this connection">Conn</th>',
                  '<th>Child</th>'=>'<th title="Megabytes transferred this child">Child</th>',
                  '<th>Slot</th>'=>'<th title="Total megabytes transferred this slot">Slot</th>'
                );
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0');
	curl_setopt($curl, CURLOPT_NOBODY, false);
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 30);
	curl_setopt($curl, CURLOPT_URL, ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].'/server-status');
	curl_setopt($curl, CURLOPT_HEADER, false);
	$response = curl_exec($curl);

  if(!empty($response)){
    include_once 'simple_html_dom.php';
    $html = str_get_html($response);
    $raw = $html;
    $html->find('table',1)->outertext = '';
    $html->find('dl',0)->outertext = '';
    $html->find('p',0)->outertext = '';
    $html->find('pre',0)->outertext = '';
    foreach($html->find('hr') AS $hr){
      $hr->outertext = '';
    }
    $retArray = array();
    $t = 0;
    foreach($html->find('table',0)->find('tr') AS $tr){
      foreach($tr->find('th') AS $th){
        $retArray['requestsTable']['headers'][] = $th->innertext;
      }
      foreach($tr->find('td') AS $td){
        $retArray['requestsTable']['data'][$t][] = strip_tags($td->innertext);
      }
      $t++;
    }
    $i = 1;
    $retArray['Header'] = $html->find('h1',0)->innertext;
	  $retArray['Footer'] = $html->find('address',0)->innertext;
      foreach($html->find('dl',1)->find('dt') AS $dt){
        if(!stristr($dt->outertext,'Parent Server')){
          if(strstr($dt->outertext,':')){
            $cells = explode(':',$dt->innertext);
            if(stristr($cells[0],'Total accesses')){
              $retArray['Stats']['Total Requests'] = number_format(intval(explode(' ',$cells[1])[1]));
              $retArray['Stats']['Total Traffic'] = str_replace(' - Total Duration','',$cells[2]);
              $retArray['Stats']['Total Duration'] = number_format(intval(explode(' ',$cells[3])[1]));
            } else if(stristr($cells[0],' time')){
              $t = $cells[0]; 
              unset($cells[0]);
              $retArray[ucwords($t)] = strtotime(str_replace(array('Eastern Standard Time','Eastern Daylight Time'),'',implode(':',$cells))) * 1000;
            } else {
              $retArray['Stats'][ucwords($cells[0])] = $cells[1];
            }
            $dt->outertext = '<dt class="dt-'.$i.'">'.$dt->innertext.'</dt>';
            $i++;
          } else {
            if(stristr($dt->innertext,'requests currently being processed')){
              $retArray['Stats']['Active Requests'] = number_format(intval(explode(' ',$dt->innertext)[0]));
              $retArray['Stats']['Requests Processing'] = $dt->innertext;
            } else {
              $retArray['Stats']['Requests Breakdown'] = $dt->innertext;
            }
          }
        } else {
          $dt->outertext = '';
        }
      }
      $retArray['currentEpoch'] = time() * 1000;
      echo json_encode($retArray);
    }
  } else { ?>
  <!DOCTYPE html>
  <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta http-equiv="X-UA-Compatible" content="ie=edge">
      <title>Server Status</title>
      <style type="text/css">
        html, body { margin: 0; padding: 15px 25px 0px 15px; }
        html { background: -webkit-radial-gradient(50% 50%, ellipse closest-corner, rgba(0,0,0,0.4) 30%, rgba(0,0,0,0.8) 100%);  }
        body { color: #ddd; }
        
        #chartContainer { box-shadow: 10px 8px 15px #2b2b2b; height: 250px; }
        h3 { text-align: right; margin: 0; }

        dl { padding: 0.5em; }
        dt { text-align: left;  font-weight: bold; }
        table { width: 100%; box-shadow: 10px 8px 15px #2b2b2b; }
        td { margin: 4px; padding:0.3em; background-color: #eee; color: #fff; text-align: center; }
        td:last-child { text-align: left; }
        th { color: #333; }
        tr:nth-child(odd) td { background: #333; }
        tr:nth-child(even) td { background: #666; }
        th { border: 1px solid #AAA; padding: 2px; background-color: #ccc; font-family: Helvetica; font-weight: bold; }
        font { font-family: Helvetica; font-weight: bold; }
        address { text-align: center; color: #eee; font-size: 1.2em; padding: 10px; }

        ul { list-style: none; white-space: nowrap; overflow: hidden; color: #333; }
        li { float: left; opacity: 0.8; background: #fff; border: 1px solid #333; padding: 5px; margin: 10px; border-radius: 8px; width: 210px; height: 150px; word-wrap: break-word; white-space: normal; position: relative; box-shadow: 5px 5px 5px 0px rgba(51,51,51,0.25); overflow: hidden; text-align: center; }
        li:hover { opacity: 1.0; }
        li h2 { font-size: 1.35em; }
        div.clr { clear: both; }
      </style>
      <script type="text/javascript" src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
      <script type="text/javascript" src="https://code.highcharts.com/highcharts.js"></script>
    </head>
    <body>
      <h1 id="pageTitle"></h1>
      <h3 id="currentTimeStamp"><?php echo date('l, F d, Y, g:i:s A'); ?></h3>
      <h3 id="serverStartTime"></h3>
      <div id="chartContainer"></div>
      <div id="responseContainer">
        <ul></ul>
        <div class="clr"></div>
        <table id="requestsTable">
          <thead><tr></tr></thead>
          <tbody></tbody>
        </table>
      </div>
	  <address id="pageFooter"></address>
    </body>
    <script>
      var graph;
      var serverStartInt = 0;
      df = { weekday: 'long', year: 'numeric', month: 'long', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' };
      descArray = {
                  Srv: 'Child Server number - generation',
                  PID: 'OS process ID',
                  Acc: 'Number of accesses this connection / this child / this slot',
                  M: 'Mode of operation',
                  SS: 'Seconds since beginning of most recent request',
                  Req: 'Milliseconds required to process most recent request',
                  Dur: 'Sum of milliseconds required to process all requests',
                  Conn: 'Kilobytes transferred this connection',
                  Child: 'Megabytes transferred this child',
                  Slot: 'Total megabytes transferred this slot'
                };
      setInterval(function(){
        $('#currentTimeStamp').html('<span title="'+ Math.floor(Date.now() / 1000).toString() +'">'+ new Date().toLocaleTimeString('en-us', df) +'</span>');
      },1000);

      function refreshData(initialRequest){
        $.ajax({ url: 'server-status.php?getStatus', type: 'GET', success: function(theResponse){
            json = JSON.parse(theResponse);

            if($('#serverStartTime').text() == '' || serverStartInt != json['Restart Time']){
              $('#serverStartTime').text('Server started '+ new Date(json['Restart Time']).toLocaleTimeString('en-us', df));
              serverStartInt = json['Restart Time'];
            }
            $('#responseContainer ul').empty();
            for (const key of Object.keys(json.Stats)) {
              if(key != 'Server Load'){
                $('#responseContainer ul').append('<li><h2>'+ key +'</h2>'+ json.Stats[key] +'</li>');
              }
            }
            if($('#requestsTable thead tr').html() == ''){
              for (const th of Object.keys(json.requestsTable.headers)) {
                var thTitle = (descArray.hasOwnProperty(json.requestsTable.headers[th])) ? ' title="'+ descArray[json.requestsTable.headers[th]] +'"' : '';
                $('#requestsTable thead tr').append('<th'+ thTitle +'>'+ json.requestsTable.headers[th] +'</th>');
              }
            }
            
            $('#requestsTable tbody').empty();
            for (const tr of Object.keys(json.requestsTable.data)){
              $('#requestsTable tbody').append('<tr> \
                <td>'+ json.requestsTable.data[tr][0] +'</td> \
                <td>'+ json.requestsTable.data[tr][1] +'</td> \
                <td>'+ json.requestsTable.data[tr][2] +'</td> \
                <td>'+ json.requestsTable.data[tr][3] +'</td> \
                <td>'+ json.requestsTable.data[tr][4] +'</td> \
                <td>'+ json.requestsTable.data[tr][5] +'</td> \
                <td>'+ json.requestsTable.data[tr][6] +'</td> \
                <td>'+ json.requestsTable.data[tr][7] +'</td> \
                <td>'+ json.requestsTable.data[tr][8] +'</td> \
                <td>'+ json.requestsTable.data[tr][9] +'</td> \
                <td>'+ json.requestsTable.data[tr][10] +'</td> \
                <td>'+ json.requestsTable.data[tr][11] +'</td> \
                <td>'+ json.requestsTable.data[tr][12] +'</td> \
                <td>'+ json.requestsTable.data[tr][13] +'</td> \
              </tr>');
            }
            
            if($('#pageTitle').text() == ''){
              $('#pageTitle').text(json.Header);
            }
			
			if($('#pageFooter').text() == ''){
              $('#pageFooter').text(json.Footer);
            }
            var series = graph.series[0];
            var shift = series.data.length > 20;
            graph.series[0].addPoint([json.currentEpoch, parseInt(json.Stats['Active Requests'].replace(/,/g,''))], true, shift);
            graph.series[1].addPoint([json.currentEpoch, parseInt(json.Stats['Total Requests'].replace(/,/g,''))], true, shift);
          }
        })
      }
      refreshData(true);
      Highcharts.setOptions({
        global: {
          timezoneOffset: (new Date()).getTimezoneOffset()
        }
      });
      graph = Highcharts.chart('chartContainer', {
        chart: {
          type: 'line',
          animation: Highcharts.svg,
          events: {
            load: function () {
              setInterval(function(){
                refreshData(false)
              }, 5000);
            }
          }
        },
        time: {
          useUTC: false
        },
        title: {
          text: 'Apache Server Requests'
        },
        xAxis: {
          type: 'datetime',
          tickPixelInterval: 150
        },
        yAxis: [{
          title: {
            text: 'Active Requests'
          },
          min: 0
        },
        {
          title: {
            text: 'Total Requests'
          },
          opposite: true
        }],
        tooltip: {
          pointFormat: '{point.x:%l:%M:%S %P}<br/>{point.y}'
        },
        legend: {
          enabled: false
        },
        credits: {
          enabled: false
        },
        exporting: {
          enabled: false
        },
        series: [{
          name: 'Active Requests',
          data: []
          },
          {
          yAxis: 1,
          name: 'Total Requests',
          data: []
        }]
    });
    </script>
  </html>
<?php } ?>