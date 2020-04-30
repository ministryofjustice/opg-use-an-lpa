#!/bin/sh

sudo /usr/bin/Xvfb :99 -screen 0 1920x1080x24 &

/usr/bin/google-chrome-stable \
  --disable-gpu \
  --disable-extensions \
  --headless \
  --remote-debugging-address=0.0.0.0 \
  --remote-debugging-port=9222 \
  --disable-setuid-sandbox \
  --no-sandbox \
  --window-size="1920,1080" \
  --disable-dev-shm-usage \
  --no-startup-window \
  --no-first-run \
  --no-pings

  #&

#exec "$@"