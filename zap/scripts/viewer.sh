#!/bin/sh
zap-cli --verbose quick-scan --self-contained http://host.docker.internal:9001 --spider -o '-config api.disablekey=true' -l Medium