<!-- 
* Twitter assembly bot that assembles user code and gives the assembled code via
* Twitter.
*
* Copyright (C) 2014  Stephen Chavez and Taylor Hornby
* 
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
* 
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
* 
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>. 
* 
* You can contact the authors at <https://dicesoft.net/> or <https://defuse.ca/> 
* -->

<?php
require_once('libs/tmhOAuth/tmhOAuth.php');
require_once('libs/assemblyLib.php');

define('BOT_SCREEN_NAME', 'plzasm');
define('LOG_FILE', '/var/log/plzasm.log');


function logMessage($msg)
{
    file_put_contents(LOG_FILE, $msg, FILE_APPEND);
}

$CREDS = array(
    'consumer_key'     => '',
    'consumer_secret'  => '',
    'user_token'       => '',
    'user_secret'      => '',
);

$config = array_merge(
    $CREDS,
    array('is_streaming' => true)
);


$params = array(
    'with' => 'user',
);

while (true) {
    $mainTwitterStream = new tmhOAuth($config);

    $res = $mainTwitterStream->streaming_request(
        'GET',
        'https://userstream.twitter.com/1.1/user.json',
        $params,
        'callbackkk'
    );

    logMessage( "!!! DISCONNECTED !!! Retrying...\n" );
    sleep( 5 );
}

function sendReply($id, $message)
{
    global $CREDS;

     $reply = new tmhOAuth($CREDS);
     logMessage( "Sending reply [$message]..\n" );
     $tweetInfo = array(
         "status" => $message,
         "in_reply_to_status_id" => $id
     );
     $res = $reply->request("POST", "https://api.twitter.com/1.1/statuses/update.json", $tweetInfo, true);
     if ($res !== 200) {
         logMessage("ERROR: Reply POST failed!\n");
     }
}

function sendErrorReply($contentId, $errorMsg)
{
    sendReply(
             $contentId,
             $errorMsg
         );

}

function callbackkk($content, $length, $metrics)
{
    $a = new Assembler();
    logMessage( "IN CALLBACKKK:\n" );

    logMessage( "Content:\n" );
    $content = json_decode($content, true);
    logMessage( print_r($content, true) );

    // Ignore anything that isn't a tweet.
    if (!isset($content['text']))
        return;

    // Ignore everything the bot tweets.
    if ($content['user']['screen_name'] == BOT_SCREEN_NAME)
        return;

    // Ignore retweets.
    if (isset($content['retweeted_status'])) {
        return;
    }

    // ignore http or https
    if (stripos($content['text'], "http") !== FALSE) {
        return;
    }
 
    if (stripos($content['text'], "https") !== FALSE) {
        return;
     }

    // Pentium FOOF bug easter egg
    if (stripos($content['text'], "LOCK CMPXCHG8B EAX") !== FALSE) {
        sendReply( 
                  $content['id_str'],
                  '@' . $content['user']['screen_name'] .
                  " Help! I am frozen. Please reset me.");
        return;
    }

    $possibleUserCode = findPossibleCodeText($content['text']);
    $result = "";
    $newCodeText = "";

    try {
        if (isCodeX64($possibleUserCode) ) {
            $possibleUserCode = str_ireplace("#x64", "", $possibleUserCode);
            $a->setArch("x64"); // or "x64"
        }
        else
        {
            $a->setArch("x86"); // or "x64"
        }
        
        $result = $a->assemble($possibleUserCode);
        
        $emptyString = buildAssembledMessage(
                                             $content['user']['screen_name'],
                                             '',
                                             $result['hex']);


        $remainingCharsForCodeUse = getRemainingChars($emptyString);
        echo $remainingCharsForCodeUse . ' in good code';
        
        // get user printable code with no bad chars
        $userPrintableCode = getShortPrintableCode($possibleUserCode);
       
        if (strlen($userPrintableCode) > $remainingCharsForCodeUse) {
            $newCodeText = substr(
                                  $userPrintableCode, 
                                  0, 
                                  $remainingCharsForCodeUse - 3) . "...";
        }
        else
        {
            $newCodeText = $userPrintableCode;
        }
        
        if ($remainingCharsForCodeUse < 10) {
            throw new Exception;
            return;
        }
        
        // build string with real code
        $msg = buildAssembledMessage(
                                     $content['user']['screen_name'],
                                     $newCodeText, 
                                     $result['hex']
                                 );
        
        sendReply(
                  $content['id_str'],
                  $msg
            );
        echo $result['hex']; // or 'code', 'hex_zero_bold', 'string', 'array'
    } 
    catch (Exception $ex)
    {
        // get printable code with no bad chars
        $userPrintableCode = getShortPrintableCode($possibleUserCode);

        $emptyString = buildCodeNotBeAssembledMessage(
                                                  $content['user']['screen_name'],
                                                  '');
        $remainingCharsForCodeUse = getRemainingChars($emptyString);
        echo $remainingCharsForCodeUse . ' in bad code';


        if (strlen($userPrintableCode) > $remainingCharsForCodeUse) {
            $newCodeText = substr(
                                  $userPrintableCode, 
                                  0, 
                                  $remainingCharsForCodeUse - 3) . "...";
        }
        else
        {
            $newCodeText = $userPrintableCode;
        }
        
        // build string with real code
        $errorMsg = buildCodeNotBeAssembledMessage(
                                                   $content['user']['screen_name'], 
                                                   $newCodeText);
        
        sendErrorReply(
                        $content['id_str'], 
                        $errorMsg
                    );
    }
}

function buildCodeNotBeAssembledMessage($userHandle, $code)
{
    return '@' . $userHandle . ' Sorry, "' . trim($code) .
        '" could not be assembled.';
}

function buildAssembledMessage($userHandle, $userCode, $hexCode)
{
 
    return '@' . $userHandle . ' "' . trim($userCode) . '" assembles to ' . 
       '"' .  $hexCode . '"';
}

function getRemainingChars($someString)
{
    return 140 - strlen($someString);
}

function getShortPrintableCode($codeToCut)
{
    $badChars = array('@', '#', '$');
    $newCodeText = trim(str_replace($badChars, "", $codeToCut));
   
    return $newCodeText;
}

function isCodeX64($possibleUserCode)
{
    $tempPos = stripos($possibleUserCode, "#x64");
    if($tempPos === false)
    {
        return false;
    }
    return true;
}

function findPossibleCodeText($stringToSearch)
{
    // Check if the bot is mentioned then find some intel asm code,
      
    $findMe = '@' . BOT_SCREEN_NAME;
    return trim(str_ireplace($findMe, "", $stringToSearch));
}

?>
