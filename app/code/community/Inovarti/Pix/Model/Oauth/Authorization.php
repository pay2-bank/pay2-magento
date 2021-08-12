<?php
/**
 *
 * @category   Inovarti
 * @package    Inovarti_Pix
 * @author     Suporte <suporte@inovarti.com.br>
 * @copyright  2021 Inovarti (http://inovarti.com.br/)
 */
abstract class Inovarti_Pix_Model_Oauth_Authorization
{

    abstract public function authorization($httpClient);
    
}
