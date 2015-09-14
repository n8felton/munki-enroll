<?php
namespace CFPropertyList;

require_once( 'cfpropertylist-2.0.1/CFPropertyList.php' );

// Get the munki repo directory
$plist          = new CFPropertyList( '/Library/Preferences/com.github.munki.plist' );
$arrPref        = $plist->toArray();
$munki_repo     = $arrPref['MUNKI_REPO'];

// Get the varibles passed by the enroll script
$identifier     = $_GET["identifier"];
$hostname       = $_GET["hostname"];

// Check if manifest already exists for this machine
if ( file_exists( $munki_repo . '/manifests/' . $hostname ) )
{
    echo "Computer manifest already exists.";
}
else
{
    echo "Computer manifest does not exist.";

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
    $plist->saveXML( $munki_repo . '/manifests/' . $hostname );
}
?>