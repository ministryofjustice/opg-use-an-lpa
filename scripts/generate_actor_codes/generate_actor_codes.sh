#!/usr/bin/env bash
set -euo pipefail

red=`tput setaf 1`
green=`tput setaf 2`
bold=`tput bold`
reset=`tput sgr0`

function get_lpa_inputcount(){
    LPA_UNIQUE_COUNT=$( echo "${LPATRIMMED}" | awk  -v FS="," "{ print NF }" );
}

function load_csv(){
    export LC_ALL=C

    LPATRIMMED=$( tr -d '\r' < $1  |        # fix line endings to unix
    tr '\n' ',' |                           # make each row a record (this may have multiples)
    tr -cd '[:print:]' |                    # cleanse non printable characters
    tr -d '[:space:]' |                     # remove spaces
    tr -d '"' |                             # remove double quotes
    awk -v RS="," -v ORS="," '!_[$0]++' |   # remove duplicates
    sed 's/,$//' |                          # remove trailing comma after processing.
    tr -s ',' )                             # squash commas
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
    mkdir -p ${OUTPUT_FOLDER}/out
    aws-vault exec identity -- python -u ./generate_actor_codes.py ${LPAENV} ${LPATRIMMED} |
        tee ${OUTPUT_STAGING_FILE}        # create log file, from output
}

function process_actor_codes(){
    echo "Sanity check the logs..."
    check_file_sanity ${OUTPUT_STAGING_FILE}

    echo 'extracting and formatting LPA codes...'
    awk ' $1=="timestamp:" {$1=""; $2="";$3=""; print} ' ${OUTPUT_STAGING_FILE} | # find extract json
        jq -f  transform-lpa-json.jq > ${OUTPUT_TEXT_FILE}                     # fix up the json output

    echo 'Sanity check the final output...'
    check_file_sanity ${OUTPUT_TEXT_FILE}

    echo "${OUTPUT_TEXT_FILE} generated."

    echo "Contents for checking:"
    cat ${OUTPUT_TEXT_FILE}
}

function make_encrypted_image() {
    hdiutil create -srcfolder /tmp/${FILENAME}/out -fs HFS+ -encryption AES-256 -volname ${FILENAME}  ~/Documents/${FILENAME}.dmg
    echo -e "${green}disk image created!${reset}"

    if [[ -z "${NO_CLEANUP}" ]]
    then
        echo  "removing intermediate folder..."
        rm -r ${OUTPUT_FOLDER}
    else
        echo "${OUTPUT_FOLDER} is still on disk."
    fi
}

function usage(){
    echo "Usage: generate_actor_codes.sh -e <environment-name> [-i \"<csv-inline-list-surrounded-by-quotes>\" | -f filename] [-v] [-n]" 1>&2
    exit 1;
}

INLINE_CSV=
INPUT_FILE=
LPAENV=
NO_CLEANUP=
while getopts "e:i:f:vn" opt
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
    n)
        #no clean up
        NO_CLEANUP=true
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

get_lpa_inputcount

echo "environment name=${LPAENV}"
echo "LPA Id's=${bold}${LPATRIMMED}${reset}"
echo "Total unique LPA's: ${bold}${LPA_UNIQUE_COUNT}${reset}"
echo "A new ${bold}${FILENAME}.txt${reset} file will be generated."
echo "This will be stored securely in disk image ${bold}${FILENAME}.dmg${reset} and copied to your Documents folder."

read -p "Are the above details correct? [y/n]: " -n 1 -r
echo

OUTPUT_FOLDER=/tmp/${FILENAME}
OUTPUT_TEXT_FILE=${OUTPUT_FOLDER}/out/${FILENAME}.txt
OUTPUT_STAGING_FILE=${OUTPUT_FOLDER}/${FILENAME}.log

if [[ $REPLY =~ ^[Yy]$ ]]
then
    echo "generating actor codes..."
    generate_actor_codes

    echo "processing actor codes..."
    process_actor_codes

    echo "creating encrypted disk image...."
    make_encrypted_image
else
    echo "${red}generate actor codes script aborted.${reset}"
fi
