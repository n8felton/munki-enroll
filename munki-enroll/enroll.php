<?php
namespace CFPropertyList;

require_once( 'cfpropertylist-2.0.1/CFPropertyList.php' );

$logFile = "enroll.log";

// Get the munki repo directory
$plist      = new CFPropertyList( '/Library/Preferences/com.github.munki.plist' );
$arrPref    = $plist->toArray();
$munki_repo = $arrPref['MUNKI_REPO'];

// Get the varibles passed by the enroll script
if ( isset( $_GET['manifest'] ) )
{
	if ( $_GET['manifest'] == '' )
	{
		logToFile("Manifest variable is blank. Exiting...");
		exit(1);
	}
	else
	{
		$manifest = $_GET['manifest'];
	}
}
if ( isset( $_GET['parent'] ) )
{
	if ( $_GET['parent'] == '' )
	{
		logToFile("Identifer is blank, using 'site_default'");
		$parent = 'site_default';
	}
	else
	{
		$parent = $_GET['parent'];
		logToFile("BUKEY:".getMRBU($parent));
	}
}

// Set the path to the manifests
$parent_manifest_path    = $munki_repo . '/manifests/' . $parent;
$machine_manifest_path   = $munki_repo . '/manifests/' . $manifest;

// Check if the parent/nested manifest exists
if ( file_exists( $parent_manifest_path ) )
{
    logToFile("Parent manifest ($parent) already exists.");
}
else
{
    logToFile("Parent manifest ($parent) does not exist.");
	generateManifest($parent_manifest_path, 'default', '');
}

// Check if manifest already exists for this machine
if ( file_exists( $machine_manifest_path ) )
{
    logToFile("Computer manifest ($manifest) already exists.");
}
else
{
    logToFile("Computer manifest ($manifest) does not exist.");
	generateManifest($machine_manifest_path, $parent, 'production');
}

function logToFile($message)
{
    global $logFile;
	$date = date_create();
	$timestamp = date_format($date, 'Y-m-d H:i:s');
	$remote_ip = $_SERVER['REMOTE_ADDR'];
	$remote_hostname = gethostbyaddr($remote_ip);
	$message = "[$timestamp] [$remote_hostname] [$remote_ip] $message".PHP_EOL;
    echo $message."<br/>";
    
	file_put_contents($logFile, $message, FILE_APPEND|LOCK_EX);
}

function getMRBU($bu)
{
	$bu = strtoupper($bu);
	$mrbu = shell_exec('scripts/munkireport_bu_query.sh');
	$arrMRBU = json_decode($mrbu, true);
	return $arrMRBU[array_search($bu, array_column($arrMRBU, 'name'))]['keys'][0];
}

function generateManifest($manifest_path, $parent, $catalog)
{
    $plist = new CFPropertyList();
    $plist->add( $dict = new CFDictionary() );

    if ( $catalog != '' )
	{
		// Add manifest to production catalog by default
		$dict->add( 'catalogs', $array = new CFArray() );
		$array->add( new CFString( $catalog ) );
	}

    // Add parent manifest to included_manifests to achieve waterfall effect
    $dict->add( 'included_manifests', $array = new CFArray() );
    $array->add( new CFString( $parent ) );
	
	$tmp = explode('/',$manifest_path);
	$manifest = end($tmp);
	logToFile("Generating manifest '$manifest'...");
	
    // Save the newly created plist
    $plist->saveXML( $manifest_path );
}

?>
