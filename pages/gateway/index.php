<?php

/**
 * @defgroup pages_gateway
 */
 
/**
 * @file pages/gateway/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_gateway
 * @brief Handle gateway interaction requests. 
 *
 */

switch ($op) {
	case 'index':
	case 'lockss':
	case 'plugin':
		define('HANDLER_CLASS', 'GatewayHandler');
		import('pages.gateway.GatewayHandler');
		break;
}

?>
