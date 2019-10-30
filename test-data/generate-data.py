import requests
import os
import json
import sys

url = "https://frontend-integration.dev.sirius.opg.digital/api/public/v1/lpas"

# TODO only 7 of these actually come back. fix that
lpas = [
    700000000047,
    700000000138,
    700000000252,
    700000000344,
    700000000435,
    700000000526,
    700000000617,
    700000000708,
    700000000799,
    700000000914,
    700000001003,
    700000001094,
    700000001185,
    700000001276,
    700000001367,
    700000001458,
    700000001540,
    700000001631,
    700000001722,
    700000001813,
    700000001904
]

headers = {
    'User-Agent': "PostmanRuntime/7.15.2",
    'Accept': "*/*",
    'Cache-Control': "no-cache",
    'Host': "frontend-integration.dev.sirius.opg.digital",
    'Accept-Encoding': "gzip, deflate",
    'Connection': "keep-alive",
    'cache-control': "no-cache"
}

def getLpaData(cookie):
    cookieHeader = {
        'Cookie': "sirius=%s" % cookie,
    }
    headers.update(cookieHeader)

    if os.path.exists('gateway-data.json'):
        os.remove('gateway-data.json')

    lpaJson = []

    for lpa in lpas:
        querystring = {"uid":lpa}
        response = requests.request("GET", url, headers=headers, params=querystring)

        data = json.loads(response.text)

        if len(data) > 0:
            lpaJson.append(data[0])

    f = open('api-gateway.json', 'a')
    f.write(json.dumps(lpaJson))
    f.close()

def main(argv):
    if len(argv) != 1:
        print('generate-data.py <sirius session cookie value>')
        print('  - login to Sirius and get your cookie value using the inspector tools')
        sys.exit()

    getLpaData(argv[0])

if __name__ == "__main__":
    main(sys.argv[1:])