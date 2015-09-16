<?php
namespace CFPropertyList;

require_once( 'cfpropertylist-2.0.1/CFPropertyList.php' );

$logFile = "enroll.log";

// Get the munki repo directory
$plist              = new CFPropertyList( '/Library/Preferences/com.github.munki.plist' );
$arrPref            = $plist->toArray();
$munki_repo         = $arrPref['MUNKI_REPO'];

// Get the varibles passed by the enroll script
if ( isset( $_GET['hostname'] ) )
{
	if ( $_GET['hostname'] == '' )
	{
		logToFile("Hostname variable is blank. Exiting...");
		exit(1);
	}
	else
	{
		$hostname = $_GET['hostname'];
	}
}
if ( isset( $_GET['identifier'] ) )
{
	if ( $_GET['identifier'] == '' )
	{
		logToFile("Identifer is blank, using 'site_default'");
		$identifier = 'site_default';
	}
	else
	{
		$identifier = $_GET['identifier'];
	}
}

// Set the path to the manifests
$parent_manifest_path    = $munki_repo . '/manifests/' . $identifier;
$machine_manifest_path   = $munki_repo . '/manifests/' . $hostname;

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

function generateManifest($manifest_path, $identifier)
{
    $plist = new CFPropertyList();
    $plist->add( $dict = new CFDictionary() );

    // Add manifest to production catalog by default
    $dict->add( 'catalogs', $array = new CFArray() );
    $array->add( new CFString( 'production' ) );

    // Add parent manifest to included_manifests to achieve waterfall effect
    $dict->add( 'included_manifests', $array = new CFArray() );
    $array->add( new CFString( $identifier ) );
	
	$tmp = explode('/',$manifest_path);
	$manifest = end($tmp);
	logToFile("Generating manifest '$manifest'...");
	
    // Save the newly created plist
    $plist->saveXML( $manifest_path );
}

// Check if the parent/nested manifest exists
if ( file_exists( $parent_manifest_path ) )
{
    logToFile("Parent manifest ($identifier) already exists.");
}
else
{
    logToFile("Parent manifest ($identifier) does not exist.");
	generateManifest($parent_manifest_path, 'site_default');
}

// Check if manifest already exists for this machine
if ( file_exists( $machine_manifest_path ) )
{
    logToFile("Computer manifest ($hostname) already exists.");
}
else
{
    logToFile("Computer manifest ($hostname) does not exist.");
	generateManifest($machine_manifest_path, $identifier);
}
?>
