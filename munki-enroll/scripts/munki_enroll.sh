#!/bin/bash
if [[ $EUID -ne 0 ]]; then
	echo "This script must be run as root" 
	exit 1
fi

log() {
	/bin/echo "$@" | /usr/bin/tee >(logger -t "$(basename "$0")")
}

# MRBU - MunkiReport Business Unit. Set to 1 to use.
MRBU=1
BASE_URL="https://example.com"
REPO_URL="${BASE_URL}/repo"
MANIFEST_URL="${REPO_URL}/manifests"
ENROLL_URL="${BASE_URL}/munki-enroll/enroll.php"

# Convert hostname to lowercase
HOSTNAME=$( echo "${HOSTNAME}" | tr '[:upper:]' '[:lower:]' )

# Convert hostname to manifest (short) name; e.g. nrh-1065-01
MANIFEST=${HOSTNAME%%.*}

# Get identifier by splitting hostname on "-" character; e.g. nrh
PARENT=${HOSTNAME%-*}

if [[ "$HOSTNAME" == "$PARENT" ]]; then
    log "[38;5;9mFailed to determine parent manifest due to lack of a hyphen (-) in the hostname.[0m"
    exit 1
else
    log "Manifest:  $MANIFEST"
    log "Parent:    $PARENT"
fi

RESULT=$(/usr/bin/curl --max-time 5 --silent --get -d manifest="${MANIFEST}" -d parent="${PARENT}" "${ENROLL_URL}")
#log "/usr/bin/curl --max-time 5 --silent --get -d manifest=${MANIFEST} -d parent=${PARENT} ${ENROLL_URL}"

if [[ $MRBU ]]; then
	# Get the Business Unit Key for Munki Report
	BUKEY=$(echo "$RESULT" | sed -En 's/^.*BUKEY:(.*)$/\1/p')
	log "BUKEY:     $BUKEY"
	defaults write /Library/Preferences/MunkiReport Passphrase "$BUKEY"
fi

BASIC_AUTH_HEADER=$(defaults read /private/var/root/Library/Preferences/ManagedInstalls AdditionalHttpHeaders | sed -En 's/^.*(Auth.*)"/\1/p')

# Get response code from web server to check if our manifest exists
VERIFY=$(curl -H "${BASIC_AUTH_HEADER}" --write-out %{http_code} --silent "${MANIFEST_URL}/${MANIFEST}" --output /dev/null)

# Verify our manifest exists
if [ "${VERIFY}" == "200" ]; then
    log "Manifest has been successfully verified!"
else
    log "Error verifying manifest exists. Error code is: ${VERIFY}"
fi
