<?php // $Id$

$handlers = array (
    'user_deleted' => array (
         'handlerfile'      => '/portfolio/type/googledocs/lib.php',
         'handlerfunction'  => 'portfolio_googledocs_user_deleted', 
         'schedule'         => 'cron'
     ),
);

?>
