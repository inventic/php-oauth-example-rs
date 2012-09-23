<?php

require_once "lib/RemoteResourceServer.php";

$output = NULL;

try {

    $config = parse_ini_file("config/rs.ini");

    if(array_key_exists("protected", $_GET) && $_GET['protected'] === "1") {
        $rs = new RemoteResourceServer($config);

        $headers = apache_request_headers();
        $ah = array_key_exists('Authorization', $headers) ? $headers['Authorization'] : NULL;
        $rs->verifyAuthorizationHeader($ah);
        $data = array(
            "authorized" => TRUE, 
            "id" => $rs->getResourceOwnerId(), 
            "entitlement" => $rs->getEntitlement(),
            "resource_owner_scope" => $rs->getScope(),
        );
        $output = json_encode($data);  
    } else {
        $data = array(
            "authorized" => FALSE, 
            "message" => "Hello World!",
        );
        $output = json_encode($data);
    }
    echo $output;

    // FIXME: we should move this stuff to the class so App does not have to deal with this!
} catch (ResourceServerException $e) {
    header("HTTP/1.1 " . $e->getResponseCode());    // FIXME: status thingy, not just code! 
    if("no_token" === $e->getMessage()) {
        // no authorization header is a special case, the client did not know
        // authentication was required, so tell it now without giving error message
        $hdr = 'Bearer realm="Resource Server"'; 
    } else {
        $hdr = sprintf('Bearer realm="Resource Server",error="%s",error_description="%s"', $e->getMessage(), $e->getDescription());
    }
    header("WWW-Authenticate: $hdr");
    echo json_encode(array("error" => $e->getMessage(), "error_description" => $e->getDescription()));
}
