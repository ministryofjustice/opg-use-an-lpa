#!/usr/bin/env bash
set -euo pipefail

function load_csv(){
    LPATRIMMED=$(tr -d '\r' < $1 |                  # trim Windows line-feeds to make easier
        awk 'BEGIN{RS="\n"; ORS=","}{print $1}' |   # convert lines to csv
        sed -e 's/[^0-9,]*//g' |                    # remove anything that's not a number or a comma
        sed 's/,$//')                               # fix comma at end of file if present.
}

function parse_interactive(){
    LPATRIMMED=$(  echo "$@" | tr -d '[:space:]')   # strip spaces out
    LPATRIMMED=${LPATRIMMED//[!0-9,]/}              # remove all other non required characters.

}

function check_file_sanity(){
     [[ ! -f $1 ]] && { echo "intermediate file $1 not found."; exit 2;}
     [[ ! -s $1 ]] && { echo "intermediate file $1 empty."; exit 2;}
     return 0
}

function generate_actor_codes(){
    mkdir -p /tmp/${FILENAME}
    aws-vault exec identity -- python -u ./generate_actor_codes.py ${LPAENV} ${LPATRIMMED} |
        tee /tmp/${FILENAME}/${FILENAME}.log        # create log file, from output
}

function process_actor_codes(){
    echo "Sanity check the logs..."
    check_file_sanity /tmp/${FILENAME}/${FILENAME}.log

    echo 'extracting and formatting LPA codes...'
    awk ' $1=="timestamp:" {$1=""; $2="";$3=""; print} ' /tmp/${FILENAME}/${FILENAME}.log | # find extract json
        jq -f  transform-lpa-json.jq > /tmp/${FILENAME}/${FILENAME}.txt                     # fix up the json output

    echo 'Sanity check the final output...'
    check_file_sanity /tmp/${FILENAME}/${FILENAME}.txt

    echo "/tmp/${FILENAME}/${FILENAME}.txt generated."

    echo "Contents for checking:"
    cat /tmp/${FILENAME}/${FILENAME}.txt

    echo "removing intermediate file..."
    rm /tmp/${FILENAME}/${FILENAME}.log
}

function make_encrypted_image() {
    hdiutil create -srcfolder /tmp/${FILENAME}/ -fs HFS+ -encryption AES-256 -volname ${FILENAME}  ~/Documents/${FILENAME}.dmg
    rm -r /tmp/${FILENAME}
}

function usage(){
    echo "Usage: generate_actor_codes.sh -e <environment-name> [-i \"<csv-inline-list-surrounded-by-quotes>\" | -f filename] [-v]" 1>&2
    exit 1;
}

INLINE_CSV=
INPUT_FILE=
LPAENV=

while getopts "e:i:f:v" opt
do
  case ${opt} in
    e)  # get the environment
        LPAENV=$OPTARG
        ;;
    f)  # file csv mode
        INPUT_FILE=$OPTARG;
        ;;
    i)  # inline mode
        INLINE_CSV=$OPTARG;
        ;;
    v)  # debug
        set -x
        ;;
    \?)
       usage
       ;;
 esac
done

#sanity checks
[[ -z "${LPAENV}" ]] && usage;
[[ -z "${INLINE_CSV}"  && -z "${INPUT_FILE}"  ]] && usage;
[[ -n "${INLINE_CSV}"  && -n "${INPUT_FILE}"  ]] && usage;

#set up file name
FILENAME=${LPAENV}_activation_codes_$(date +%Y%m%d%H%M)

if [[ -n "${INPUT_FILE}" ]]
then
    load_csv ${INPUT_FILE};

elif [[ -n "${INLINE_CSV}" ]]
then
    parse_interactive ${INLINE_CSV};
else
    usage;
fi

echo "environment name=${LPAENV}"
echo "LPA Id's=${LPATRIMMED}"
echo "A new ${FILENAME}.txt file will be generated."
echo "This will be stored securely in disk image ${FILENAME}.dmg and copied to your Documents folder."

read -p "Are the above details correct? [y/n]: " -n 1 -r
echo

if [[ $REPLY =~ ^[Yy]$ ]]
then
    echo "generating actor codes..."
    generate_actor_codes

    echo "processing actor codes..."
    process_actor_codes

    echo "creating encrypted disk image...."
    make_encrypted_image
else
    echo "generate actor codes script aborted."
fi
