<?php

/**
 * Triggered when the plugin is uninstalled.
 *
 * Follow this control flow when implementing:
 *
 * - Ensure this method is static.
 * - Verify if the $_REQUEST content corresponds to the plugin name.
 * - Perform an admin referrer check to ensure it passes authentication.
 * - Confirm that the $_GET parameters are valid and make sense.
 * - Validate for other user roles directly via links/query string parameters.
 * - For multisite, execute once for a single site in the network and once sitewide.
 *
 * This file might be updated in future versions of the Boilerplate, but this provides the general
 * structure and outline for its functionality.
 */

defined( "ABSPATH" ) || exit( "Direct Access Not Allowed" );