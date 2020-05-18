#!/usr/bin/env sh

function extract_parameters_and_vars(){
    LPAENV=$1
    shift
    while(($#))
    do
        LPATRIMMED+=$1
        shift
    done
    LPATRIMMED=${LPATRIMMED} | tr -d "[ \t]"
    FILENAME=activation_codes_$(date +%Y%m%d)
}

function check_file_sanity()
{
     [[ ! -f $1 ]] && { echo "intermediate file $1 not found."; exit 2;}
     [[ ! -s $1 ]] && { echo "intermediate file $1 empty."; exit 2;}
     return 0
}


function generate_actor_codes(){
    mkdir -p /tmp/${FILENAME}
    aws-vault exec identity -- python -u ./generate_actor_codes.py ${LPAENV} ${LPATRIMMED} |
    tee /tmp/${FILENAME}/${FILENAME}.log
}

function process_actor_codes(){
    echo "Sanity check the logs..."
    check_file_sanity /tmp/${FILENAME}/${FILENAME}.log

    echo 'extracting and formatting LPA codes...'
    awk ' $1=="timestamp:" {$1=""; $2="";$3=""; print} ' /tmp/${FILENAME}/${FILENAME}.log |
    jq -f  transform-lpa-json.jq > /tmp/${FILENAME}/${FILENAME}.txt

    echo 'Sanity check the final output...'
    check_file_sanity /tmp/${FILENAME}/${FILENAME}.txt

    echo "/tmp/${FILENAME}/${FILENAME}.txt generated."

    echo "Contents for checking:"
    cat /tmp/${FILENAME}/${FILENAME}.txt

    echo "removing intermediate file..."
    rm /tmp/${FILENAME}/${FILENAME}.log
}

function make_encrypted_image() {
    hdiutil create -srcfolder /tmp/$FILENAME/ -fs HFS+ -encryption AES-256 -volname $FILENAME  ~/Documents/$FILENAME.dmg
    rm -r /tmp/$FILENAME
}

set -euo pipefail

if [[ "$#" -lt 2 ]]
then
    echo 'requires arguments: generate_actor_codes.sh <environment-name> <comma-separated-lpa-ids>';
else

    extract_parameters_and_vars $@

    echo "environment name=${LPAENV}"
    echo "LPA Id's=${LPATRIMMED}"
    echo "A new ${FILENAME}.txt will be generated."
    echo "This will be stored securely in disk image ${FILENAME}.dmg and copied to your Documents folder."

    read -p "Are the above details correct? [y/n]: " -n 1 -r
    echo

    if [[ $REPLY =~ ^[Yy]$ ]]
    then
        #echo "generating actor codes..."
        #generate_actor_codes

        #echo "processing actor codes..."
        #process_actor_codes

        echo "creating encrypted disk image...."
        make_encrypted_image
    else
        echo "generate actor codes script aborted."
    fi
fi
