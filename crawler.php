<?php
define('GOOGLE_EMAIL', 'xxx@gmail.com');
define('GOOGLE_PASSWD', 'xxx');
define('ANDROID_DEVICEID', '0000000000000000'); 
//Do Includes
include("src/MarketSession.php");

if(!isset($argv[1])) {
    echo "Bitte Appnamen mit angeben. Zum Beispiel: php crawler.php whatsapp";
    exit;
}

//Try to Login
//For Issues Please See Readme.md
try {
    $session = new MarketSession();
    $session->login(GOOGLE_EMAIL, GOOGLE_PASSWD);
    $session->setAndroidId(ANDROID_DEVICEID);
    $session->setOperatorSimyo();
    sleep(1);#Reduce Throttling
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "ERROR: cannot login as " . GOOGLE_EMAIL;
    exit(1);
}

//Build Request
$ar = new AppsRequest();
$ar->setQuery($argv[1]);
#$ar->setOrderType(AppsRequest_OrderType::NONE);
$ar->setStartIndex(0);
$ar->setEntriesCount(1);

$ar->setWithExtendedInfo(true);
#$ar->setViewType(AppsRequest_ViewType::PAID);
#$ar->setAppType(AppType::WALLPAPER);

$reqGroup = new Request_RequestGroup();
$reqGroup->setAppsRequest($ar);

//Fetch Request
try {
    $response = $session->execute($reqGroup);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}

//Loop And Display
$groups = $response->getResponsegroupArray();
foreach ($groups as $rg) {
    $appsResponse = $rg->getAppsResponse();
    $apps = $appsResponse->getAppArray();
    foreach ($apps as $app) {
        $file = fopen("data/" . $app->getTitle() . ".txt", "w");
        echo $app->getTitle() . " (" . $app->getId() . ")\n";
        $comments2 = array();
        for ($i = 0; $i <= 4500; $i = $i + 10) {
            sleep(1);
            //Get comments
            $cr = new CommentsRequest();
            $cr->setAppId($app->getId());
            $cr->setEntriesCount(10);
            $cr->setStartIndex($i);

            $reqGroup = new Request_RequestGroup();
            $reqGroup->setCommentsRequest($cr);
            try {
                $response = $session->execute($reqGroup);
                $groups = $response->getResponsegroupArray();

                foreach ($groups as $rg) {
                    $commentsResponse = $rg->getCommentsResponse();

                    if (!$comments = $commentsResponse->getCommentsArray()) {
                        fclose($file);
                    }

                    foreach ($comments as $key => $comment) {
                        $string = $key + $i . "; " . $comment->getCreationTime() . "; \"" . $comment->getText(
                            ) . "\"; " . $comment->getAuthorName() . "\n";
                        fwrite($file, $string);
                        $comments2[$i + $key] = $string;

                    }

                }
                echo $i . "\n";
            } catch (Exception $e) {
                echo $e->getMessage();
                if(strpos($e->getMessage(), '400')) exit(1);
                $i = $i - 10;
                Sleep(5);
            }

        }
        fclose($file);
    }
}