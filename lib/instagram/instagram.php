<?php
include_once dirname(__FILE__).'/mustache.php';

/* Get Info Methods */
class InstagramUploader
{
    // Constants
    const ACCESS_TOKEN_URI  = 'https://api.instagram.com/oauth/access_token';
    const PHOTO_NUM         = 10; // Photos per page to return
    const TEMPLATE_PATH     = 'lib/';

    // Resolution options
    const LOW_RES   = 'low_resolution';
    const THUMB     = 'thumbnail';
    const STD_RES   = 'standard_resolution';

    // Variables
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $code;

    function __construct($client_id, $client_secret, $redirect_uri, $code)
    {
        $this->client_id        = $client_id;
        $this->client_secret    = $client_secret;
        $this->redirect_uri     = $redirect_uri;

        $this->code             = $code;
    }

    function init()
    {               
        $api_info   = $this->getApiInfo(self::ACCESS_TOKEN_URI, false);
        $data  = $this->getUserAcctInfo($api_info['user_id'], $api_info['access_token'], false);
        echo '
        <input type="hidden" id="pdc_user_id" value="'.$api_info['user_id'].'" />
        <input type="hidden" id="pdc_user_access_token" value="'.$api_info['access_token'].'" />';
        // Make sure a proper resolution was passed in
        /*if ($res_opt != self::LOW_RES || $res_opt != self::THUMB || $res_opt != self::STD_RES)
        {
            $res_opt = self::THUMB;
        }*/

        // Process the data and format for rendering
        /*$img_data = array();        
        foreach ($user_info['data'] as $data)
        {
            $img_full   = $data['images'][self::STD_RES]['url'];
            $img_thumb  = $data['images'][self::THUMB]['url'];
            
            $images = array('full' => $img_full, 'thumb' => $img_thumb);
            $img_data[] = $images;            
        }    */   

        // Render the page template
        $this->render($data);//array('img_data' => $img_data, 'next_url' => $user_info['pagination']['next_url']));   
    }    

    function getApiInfo($url, $is_print)
    {
        $vars   = $this->generateAccessTokenVars();
        $ch     = $this->createAccessTokenCurl($url, $vars);
        $result = $this->executeCurl($ch, $is_print);

        // Prep variables
        return array('access_token' => $result['access_token'],
                     'user_id'      => $result['user']['id']
                    );      
    }

    function generateAccessTokenVars()
    {
        $vars   =   'client_id=' . $this->client_id . 
                    '&client_secret=' . $this->client_secret . 
                    '&grant_type=authorization_code&redirect_uri=' . $this->redirect_uri . 
                    '&code=' . $this->code;

        return $vars;
    }

    function getUserAcctInfo($user_id, $access_token, $is_print)
    {
        $ch = $this->createUserPhotoCurl($user_id, $access_token);

        return $this->executeCurl($ch, $is_print );      
    }

    /* Create Curl Object Methods */
    function createAccessTokenCurl($url, $vars)
    {
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_HEADER, FALSE); // Set it to false so we can parse the json
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");

        return $ch;
    }

    function createUserPhotoCurl($user_id, $access_token)
    {
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, 'https://api.instagram.com/v1/users/' . 
                                                                        $user_id . '/media/recent/?access_token=' . 
                                                                        $access_token . '&count=' . self::PHOTO_NUM); 
        curl_setopt ($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, FALSE); // Set it to false so we can parse the json

        return $ch;
    }
    
    function createUserFollows($user_id, $access_token)
    {
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, 'https://api.instagram.com/v1/users/' . 
                                                                        $user_id . '/follows/?access_token=' . 
                                                                        $access_token ); 
        curl_setopt ($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, FALSE); // Set it to false so we can parse the json

        return $ch;
    }

    /**
     * 
     * https://api.instagram.com/v1/users/29214240/follows
    * Passes the img links to the template. Each
    * data element consists of an assoc array
    * that contains one element 'full' and one
    * 'thumb'. These refer to the full and 
    * thumbnail sized images, respectively.
    */
    function render($data)
    {
        //$chooser_template = file_get_contents(self::TEMPLATE_PATH . '/img_chooser.html');
        // $chooser_template = file_get_contents(self::TEMPLATE_PATH . 'results.php');
        $chooser_template = file_get_contents(dirname(__FILE__).'/results.php');
        $mustache   = new Mustache();        
        //$header     = file_get_contents(self::TEMPLATE_PATH . 'header.html');
        //$footer     = file_get_contents(self::TEMPLATE_PATH . 'footer.html');
        //$view       = $mustache->render($chooser_template, $data, array('header' => $header, 'footer' => $footer));
        $view       = $mustache->render($chooser_template, $data, '');
        echo $view;
    }

    /* Execute Curl Method */
    function executeCurl($curl_obj, $is_print)
    {
        $result = curl_exec($curl_obj); 

        $json = json_decode($result, true);

        if (array_key_exists('code', $json) && $json['code'] == '400')
        {
            echo '400 Status | Error Type: ' . $json['error_type'] . 
                    ' Message: "' . $json['error_message'] . '"';
            exit;
        } 
        else if (!$result)
        {
            echo 'Curl error: ' . curl_error($curl_obj); 
            exit;
        }
        else 
        {
            if ($is_print)
            {
                print_r($result);   
            }

            return json_decode($result, true);              
        }
    }

    public static function nextPage($url)
    {
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, FALSE); // Set it to false so we can parse the json

        $result     = curl_exec($ch); 
        $assoc_arr  = json_decode($result, true);
        if (is_array($assoc_arr) && array_key_exists('code', $assoc_arr) && $assoc_arr['code'] == '400')
        //if (array_key_exists('code', $assoc_arr) && $assoc_arr['code'] == '400')
        {
            echo '400 Status | Error Type: ' . $assoc_arr['error_type'] . 
                    ' Message: "' . $assoc_arr['error_message'] . '"';
            exit;
        } 
        else if (!$result)
        {
            echo 'Curl error: ' . curl_error($ch);            
            exit;
        }
        else 
        {
            // Will already be formatted in json
            echo $result;              
        }
    }
}