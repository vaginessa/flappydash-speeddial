<?php

/*

This example is copied from:

https://www.johnromanodorazio.com/ntptest.php#source-code-container

*****

Github repo of this project, made by John R. D'Orazio (@JohnRDOrazio):

https://github.com/JohnRDOrazio/jQuery-Clock-Plugin

*****

This notice is NOT part of the example source code.

No further changes have been made.

*/

/* Query an NTP time server on port 123 (SNTP protocol) : */
  $bit_max = 4294967296;
  $epoch_convert = 2208988800;
  $vn = 3;

  $servers = array('0.uk.pool.ntp.org','1.uk.pool.ntp.org','2.uk.pool.ntp.org','3.uk.pool.ntp.org');
  $server_count = count($servers);

  //see rfc5905, page 20
  //first byte
  //LI (leap indicator), a 2-bit integer. 00 for 'no warning'
  $header = '00';
  //VN (version number), a 3-bit integer.  011 for version 3
  $header .= sprintf('%03d',decbin($vn));
  //Mode (association mode), a 3-bit integer. 011 for 'client'
  $header .= '011';

  //echo bindec($header);    

  //construct the packet header, byte 1
  $request_packet = chr(bindec($header));

  //we'll use a for loop to try additional servers should one fail to respond
  $i = 0;
  for($i; $i < $server_count; $i++) {
    $socket = @fsockopen('udp://'.$servers[$i], 123, $err_no, $err_str,1);
    if ($socket) {

      //add nulls to position 11 (the transmit timestamp, later to be returned as originate)
      //10 lots of 32 bits
      for ($j=1; $j<40; $j++) {
        $request_packet .= chr(0x0);
      }

      //the time our packet is sent from our server (returns a string in the form 'msec sec')
      $local_sent_explode = explode(' ',microtime());
      $local_sent = $local_sent_explode[1] + $local_sent_explode[0];

      //add 70 years to convert unix to ntp epoch
      $originate_seconds = $local_sent_explode[1] + $epoch_convert;

      //convert the float given by microtime to a fraction of 32 bits
      $originate_fractional = round($local_sent_explode[0] * $bit_max);

      //pad fractional seconds to 32-bit length
      $originate_fractional = sprintf('%010d',$originate_fractional);

      //pack to big endian binary string
      $packed_seconds = pack('N', $originate_seconds);
      $packed_fractional = pack("N", $originate_fractional);

      //add the packed transmit timestamp
      $request_packet .= $packed_seconds;
      $request_packet .= $packed_fractional;

      if (fwrite($socket, $request_packet)) {
        $data = NULL;
        stream_set_timeout($socket, 1);

        $response = fread($socket, 48);

        //the time the response was received
        $local_received = microtime(true);

        //echo 'response was: '.strlen($response).$response;
      }
      fclose($socket);

      if (strlen($response) == 48) {
        //the response was of the right length, assume it's valid and break out of the loop
        break;
      }
      else {
        if ($i == $server_count-1) {
          //this was the last server on the list, so give up
          die('unable to establish a connection');
        }
      }
    }
    else {
      if ($i == $server_count-1) {
        //this was the last server on the list, so give up
        die('unable to establish a connection');
      }
    }
  }

  //unpack the response to unsiged lonng for calculations
  $unpack0 = unpack("N12", $response);
  //print_r($unpack0);

  //present as a decimal number
  $remote_originate_seconds = sprintf('%u', $unpack0[7])-$epoch_convert;
  $remote_received_seconds = sprintf('%u', $unpack0[9])-$epoch_convert;
  $remote_transmitted_seconds = sprintf('%u', $unpack0[11])-$epoch_convert;

  $remote_originate_fraction = sprintf('%u', $unpack0[8]) / $bit_max;
  $remote_received_fraction = sprintf('%u', $unpack0[10]) / $bit_max;
  $remote_transmitted_fraction = sprintf('%u', $unpack0[12]) / $bit_max;

  $remote_originate = $remote_originate_seconds + $remote_originate_fraction;
  $remote_received = $remote_received_seconds + $remote_received_fraction;
  $remote_transmitted = $remote_transmitted_seconds + $remote_transmitted_fraction;

  //unpack to ascii characters for the header response
  $unpack1 = unpack("C12", $response);
  //print_r($unpack1);

  //echo 'byte 1: ' . $unpack1[1] . ' | ';

  //the header response in binary (base 2)
  $header_response =  base_convert($unpack1[1], 10, 2);

  //pad with zeros to 1 byte (8 bits)
  $header_response = sprintf('%08d',$header_response);

  //Mode (the last 3 bits of the first byte), converting to decimal for humans;
  $mode_response = bindec(substr($header_response, -3));

  //VN
  $vn_response = bindec(substr($header_response, -6, 3));

  //the header stratum response in binary (base 2)
  $stratum_response =  base_convert($unpack1[2], 10, 2);
  $stratum_response = bindec($stratum_response);
  //echo 'stratum: ' . bindec($stratum_response);

  //calculations assume a symmetrical delay, fixed point would give more accuracy
  $delay = (($local_received - $local_sent) / 2)  - ($remote_transmitted - $remote_received);
  $delay_ms = round($delay * 1000) . ' ms';
  //echo 'delay: ' . $delay * 1000 . 'ms';

  $server = $servers[$i];

  $ntp_time =  $remote_transmitted - $delay;
  $ntp_time_explode = explode('.',$ntp_time);

  $ntp_time_formatted = date('Y-m-d H:i:s A e ', $ntp_time_explode[0]).'.'.$ntp_time_explode[1];

  //compare with the current server time
  $server_time =  microtime();
  $server_time_explode = explode(' ', $server_time);
  $server_time_micro = round($server_time_explode[0],4);
  $server_time_formatted = date('Y-m-d H:i:s A e ', $server_time_explode[1]) .'.'. substr($server_time_micro,2);

  //$server_time = time();
  //$server_time_formatted = date('Y-m-d H:i:s', $server_time);
  
  date_default_timezone_set('Europe/Rome');
  $time =  $ntp_time_explode[0];
  $time_formatted = date('Y-m-d H:i:s A e', $time);
  $time0 = $ntp_time_explode[0] - date('Z');
  $time0_formatted = date('Y-m-d H:i:s A e', $time0);
  //$time = microtime();
  //$time_explode = explode(' ', $time);
  //$time_micro = round($time_explode[0],4);
  //$time_formatted = date('Y-m-d H:i:s A e', $time_explode[1]) .'.'. substr($time_micro,2);
  /*
  $time0 = time() - date('Z');
  $time0_formatted = date('Y-m-d H:i:s A e', $time0); // 12:50:29
  
  $time3 = date('h:i:s A e', gmdate('U')); // 14:50:29
  $time4 = gmstrftime("%I:%M:%S %p %Z",time());
  $timezone = "";
  if (date_default_timezone_get()) {
    $timezone = date_default_timezone_get();
  }
  elseif(ini_get('date.timezone')){
    $timezone = ini_get('date.timezone');
  }
  */


?>

<html>  
    <head>  
        <title>jQuery Clock NTP timestamp example</title>
        <meta charset="UTF-8">
        <style type="text/css">
            html, body {height: 100%; margin: 0; padding: 0; background-color: Gray; }
            #clocks-container { border: 1px groove White; border-radius: 15px; padding: 10px; width: 40%; float: left; margin: 30px; background-color: LightGray; text-align: center; }
            #table-container {  border: 1px solid Green;  border-radius: 15px; padding: 10px; width: 40%; float: left; margin: 30px; background-color: White; text-align: center; }
            #ntp-results-table {  }
            td{
                width: 160px; height: 20px;
                padding: 4px;
                border: 1px solid #000;
                font-size: 12px;
            }
            .ntp_response{
                width: 240px;
            }  
            
            /* SAMPLE CSS STYLES FOR JQUERY CLOCK PLUGIN */
            .jqclock { text-align:center; border: 2px #369 ridge; background-color: #69B; padding: 10px; margin:20px auto; width: 40%; box-shadow: 5px 5px 15px #005; }
            .clockdate { color: DarkRed; font-weight: bold; background-color: #8BD; margin-bottom: 10px; font-size: 18px; display: block; padding: 5px 0; text-shadow: 1px 1px 3px #FCC; outline: 1px solid White; }
            .clocktime { border: 2px inset DarkBlue; outline: 3px ridge LightBlue; background-color: #444; padding: 5px 0; font-size: 14px; font-family: "Courier"; color: LightGreen; margin: 2px; display: block; font-weight:bold; text-shadow: 1px 1px 1px Black; }
            
            .servertimetest { border: 1px solid Blue; margin: 2px; }
        </style>
        <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
        <script type="text/javascript" src="//rawgit.com/Lwangaman/jQuery-Clock-Plugin/master/jqClock.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
              customtimestamp = parseInt($("#jqclock").data("time"));
              console.log("current local timestamp is " + new Date().getTime());
              console.log("current ntp timestamp is " + customtimestamp);
              $("#jqclock").clock({"langSet":"en","timestamp":customtimestamp});
              $("#jqclock-local").clock({"langSet":"en"});
            });    
        </script>
    </head>
    <body>
        <h1>jQuery Clock Server Example</h1>
        <p><i>Source Code of jQuery plugin at <a href="https://github.com/Lwangaman/jQuery-Clock-Plugin">https://github.com/Lwangaman/jQuery-Clock-Plugin</a></i></p>
        <div>
        <div id="table-container">
          <h2>NTP Server Request</h2>
          <table id="ntp-results-table" border="0" align="center">
            <tr>
              <td>Server:
              <td class="ntp_response"><?php echo $server;?></td>
            </tr>
            <tr>
              <td>VN (version number):</td>
              <td class="ntp_response"><?php echo $vn_response;?></td>
            </tr>
            <tr>
              <td>Mode:</td>
              <td class="ntp_response"><?php echo $mode_response;?></td>
            </tr>
            <tr>
              <td>Stratum:</td>
              <td class="ntp_response"><?php echo $stratum_response;?></td>
            </tr>
            <tr>
              <td>Origin time:</td>
              <td class="ntp_response"><?php echo $remote_originate;?></td>
            </tr>
            <tr>
              <td>Received:</td>
              <td class="ntp_response"><?php echo $remote_received;?></td>
            </tr>
            <tr>
              <td>Transmitted:</td>
              <td class="ntp_response"><?php echo $remote_transmitted;?></td>
            </tr>
            <tr>
              <td>Delay:</td>
              <td class="ntp_response"><?php echo $delay_ms;?></td>
            </tr>
            <tr>
              <td>NTP time:</td>
              <td class="ntp_response"><?php echo $ntp_time_explode[0];?></td>
            </tr>
            <tr>
              <td>NTP time formatted:</td>
              <td class="ntp_response"><?php echo $ntp_time_formatted;?></td>
            </tr>
            <tr>
              <td>NTP time adjusted to Europe/London timezone:</td>
              <td class="ntp_response"><?php echo $time;?></td>
            </tr>
            <tr>
              <td>NTP time adjusted to Europe/Rome timezone formatted:</td>
              <td class="ntp_response"><?php echo $time_formatted; ?></td>
            </tr>
            <tr>
              <td>NTP time adjusted to Europe/London timezone then adjusted to account for timezone offset:</td>
              <td class="ntp_response"><?php echo $time0;?></td>
            </tr>
            <tr>
              <td>NTP time adjusted to Europe/Rome timezone then adjusted to account for timezone offset, formatted:</td>
              <td class="ntp_response"><?php echo $time0_formatted; ?></td>
            </tr>
            <!--
            <tr>
              <td>Server time:</td>
              <td class="ntp_response"><?php /* echo $server_time; */?></td>
            </tr>
            <tr>
              <td>Server time formatted:</td>
              <td class="ntp_response"><?php /*echo $server_time_formatted;*/?></td>
            </tr>
            <tr>
              <td>Server time as London timezone:</td>
              <td class="ntp_response"><?php /*echo $time;*/?></td>
            </tr>
            <tr>
              <td>Server time as London timezone formatted:</td>
              <td class="ntp_response"><?php /*echo $time_formatted;*/?></td>
            </tr>
            <tr>
              <td>Server time as London timezone, stripped of timezone offset:</td>
              <td class="ntp_response"><?php /*echo $time0;*/?></td>
            </tr>
            <tr>
              <td>Server time as London timezone formatted, stripped of timezone offset:</td>
              <td class="ntp_response"><?php /*echo $time0_formatted;*/?></td>
            </tr>
            -->
          </table>
        </div>
        
        <div id="clocks-container">
          <h2>Current NTP Time:</h2>
          <div id="jqclock" data-time="<?php echo $time; ?>"></div>
          <div id="jqclock-reference-date"><?php echo strftime("%A, %B %e, %Y",$time); ?></div>          
          <div class="servertimetest" id="jqclock-reference-time"><i>Time being fed into the jquery clock:</i> <br><?php echo $time_formatted; /* strftime("%I:%M:%S %p %Z",$time); */ ?></div>
          
          <h2>Compared to Current Local Time:</h2>
          <div id="jqclock-local"></div>
        </div>
        </div>
    </body>
</html>          