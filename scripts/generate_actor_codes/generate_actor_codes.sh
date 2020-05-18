#!/usr/bin/env sh
if [[ "$#" -lt 2 ]]
then
    echo 'requires arguments: generate_actor_codes.sh <environment-name> <comma-separated-lpa-ids>';
else
    LPAENV=$1
    shift

    echo "environment name=$LPAENV"

    while(($#))
    do
        LPATRIMMED+=$1
        shift
    done

    LPATRIMMED=${LPATRIMMED} | tr -d "[ \t]"
    echo "LPA Id's=${LPATRIMMED}"
    read -p "Are the above details correct? [y/n]: " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]
    then
        FILENAME=activation_codes_$(date +%Y%m%d)

        mkdir -p /tmp/${FILENAME}

        echo "generating actor codes..."
        aws-vault exec identity -- python -u ./generate_actor_codes.py ${LPAENV} ${LPATRIMMED} |
        tee /tmp/${FILENAME}/${FILENAME}.log

        echo "Sanity check the logs..."
        [[ ! -f /tmp/${FILENAME}/${FILENAME}.log ]] && { echo 'intermediate file $FILENAME.log not found.'; exit 2;}
        [[ ! -s /tmp/${FILENAME}/${FILENAME}.log ]] && { echo 'intermediate file $FILENAME.log empty.'; exit 2;}

        echo 'extracting and formatting LPA codes...'
        awk ' $1=="timestamp:" {$1=""; $2="";$3=""; print} ' /tmp/${FILENAME}/${FILENAME}.log |
        jq -f  transform-lpa-json.jq > /tmp/${FILENAME}/${FILENAME}.txt

        echo 'Sanity check the final output...'
        [[ ! -f /tmp/${FILENAME}/${FILENAME}.txt ]] && { echo 'parsed file $FILENAME.txt not found.'; exit 2;}
        [[ ! -s /tmp/${FILENAME}/${FILENAME}.txt ]] && { echo 'parsed file $FILENAME.txt empty.'; exit 2;}
        echo "/tmp/${FILENAME}/${FILENAME}.txt generated."
        echo "Contents for checking:"
        cat /tmp/${FILENAME}/${FILENAME}.txt
    else
         echo "generate actor codes script aborted."
    fi

fi
