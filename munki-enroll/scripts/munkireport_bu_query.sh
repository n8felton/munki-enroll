#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
log() {
	/bin/echo "$1" | /usr/bin/tee >(logger -t "$(basename "$0")")
}

# Retrieve data from munkireport
#DEBUG=1
MR_BASE_URL='https://example.com/munkireport'
MR_DATA_QUERY='/unit/get_machine_groups'
MR_LOGIN=''
MR_PASSWORD=''

[[ $DEBUG ]] && log 'Authenticating to munkireport..'
COOKIE_JAR=$(curl -s --cookie-jar - --data "login=${MR_LOGIN}&password=${MR_PASSWORD}" ${MR_BASE_URL}/auth/login)
SESSION_COOKIE=$(echo $COOKIE_JAR | sed 's/.*PHPSESSID /PHPSESSID=/')

[[ $DEBUG ]] && log $COOKIE_JAR
[[ $DEBUG ]] && log $SESSION_COOKIE

MACHINE_GROUPS=$(curl -s --cookie "$SESSION_COOKIE" ${MR_BASE_URL}${MR_DATA_QUERY})
[[ $DEBUG ]] && log "curl -LI -s --cookie "${SESSION_COOKIE}" ${MR_BASE_URL}${MR_DATA_QUERY}"
[[ $DEBUG ]] && log "$(curl -LI -s --cookie "${SESSION_COOKIE}" ${MR_BASE_URL}${MR_DATA_QUERY})"
[[ $DEBUG ]] && log "${MACHINE_GROUPS}"
echo $MACHINE_GROUPS


