<?php
/**
* Copyrights: Deux Huit Huit 2019
* LICENCE: 2019 MIT http://deuxhuithuit.mit-license.org;
*/

if(!defined('__IN_SYMPHONY__')) die('<h2>Error</h2><p>You cannot directly access this file</p>');

require_once(EXTENSIONS . '/cache_management/lib/class.cachemanagement.php');

class eventCache_purge extends Event
{
    public static function about()
    {
        return array(
            'name' => 'Cache purge',
            'author' => array(
                'name' => 'Deux Huit Huit',
                'website' => 'https://deuxhuithuit.com/',
                'email' => 'open-source@deuxhuithuit.com'
            ),
            'version' => '1.0.0',
            'release-date' => '2019-07-10',
            'trigger-condition' => ''
        );
    }

    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    public function isAuthValid() {
        $config = Symphony::Configuration()->get('cache_management');
        $headers = array();

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 4) === 'HTTP') {
                $headers[strtolower(substr($key, 5))] = $value;
            }
        }

        // Skip the type, only take the creds.
        $token = explode(' ', $headers['authentication'])[1];

        return $token === $config['webhook_key'];
    }

    public function load()
    {
        // The request must be a post
        if (!$this->isPost()) {
            Symphony::Log()->pushToLog("[cache_management][cache purge] Not a post", E_NOTICE, true);
            redirect('/');
            return;
        }

        // The request must have a valid auth header
        if (!$this->isAuthValid()) {
            Symphony::Log()->pushToLog("[cache_management][cache purge] Not a valid authentification header", E_NOTICE, true);
            redirect('/');
            return;
        }

        // Everything seems good, purge the entire cache
        return $this->__trigger();
    }

    protected function __trigger()
    {
        $count = 0;

        $count += CacheManagement::purgeFileCache(false, '/^cache_(.+)/');
        $count += CacheManagement::purgeFileCache();
        $count += CacheManagement::purgeDBCache();
        $count += CacheManagement::purgeFileCache(false, null, '/cacheabledatasource');
        $count += CacheManagement::deleteFileCache();
        $count += CacheManagement::deleteDBCache();

        $result = new XMLElement('cache-purge', null, array(
            'success' => 'true',
            'count' => $count
        ));

        return $result;
    }

}
