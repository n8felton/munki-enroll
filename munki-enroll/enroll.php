<?php
namespace CFPropertyList;

require_once( 'cfpropertylist-2.0.1/CFPropertyList.php' );

$logFile = "enroll.log";

// Get the munki repo directory
$plist              = new CFPropertyList( '/Library/Preferences/com.github.munki.plist' );
$arrPref            = $plist->toArray();
$munki_repo         = $arrPref['MUNKI_REPO'];

// Get the varibles passed by the enroll script
$identifier         = $_GET["identifier"];
$hostname           = $_GET["hostname"];

// Set the path to the manifests
$parent_manifest    = $munki_repo . '/manifests/' . $identifier;
$machine_manifest   = $munki_repo . '/manifests/' . $hostname;

function logToFile($message)
{
    global $logFile;
    $message = $message.PHP_EOL;
    echo $message."<br/>";
    
    // Code below from: http://stackoverflow.com/questions/3332262/how-do-i-prepend-file-to-beginning
    $handle = fopen($logFile, "r+");
    $len = strlen($message);
    $final_len = filesize($logFile) + $len;
    $cache_old = fread($handle, $len);
    rewind($handle);
    $i = 1;
    while (ftell($handle) < $final_len) {
      fwrite($handle, $message);
      $message = $cache_old;
      $cache_old = fread($handle, $len);
      fseek($handle, $i * $len);
      $i++;
    }
}

// Check if the parent/nested manifest exists
if ( file_exists( $parent_manifest ) )
{
    logToFile("Parent manifest ($identifier) already exists.");
}
else
{
    logToFile("Parent manifest ($identifier) does not exist.");

    // Create the new manifest plist
    $plist = new CFPropertyList();
    $plist->add( $dict = new CFDictionary() );

    // Add manifest to production catalog by default
    $dict->add( 'catalogs', $array = new CFArray() );
    $array->add( new CFString( 'production' ) );

    // Add parent manifest to included_manifests to achieve waterfall effect
    $dict->add( 'included_manifests', $array = new CFArray() );
    $array->add( new CFString( 'site_default' ) );

    // Save the newly created plist
    $plist->saveXML( $parent_manifest );
}

// Check if manifest already exists for this machine
if ( file_exists( $machine_manifest ) )
{
    logToFile("Computer manifest ($hostname) already exists.");
}
else
{
    logToFile("Computer manifest ($hostname) does not exist.");

    // Create the new manifest plist
    $plist = new CFPropertyList();
    $plist->add( $dict = new CFDictionary() );

    // Add manifest to production catalog by default
    $dict->add( 'catalogs', $array = new CFArray() );
    $array->add( new CFString( 'production' ) );

    // Add parent manifest to included_manifests to achieve waterfall effect
    $dict->add( 'included_manifests', $array = new CFArray() );
    $array->add( new CFString( $identifier ) );

    // Save the newly created plist
    $plist->saveXML( $machine_manifest );
}
?>