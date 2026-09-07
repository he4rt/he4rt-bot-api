# syntax=docker/dockerfile:1.15.0

FROM bluenviron/mediamtx:1.20.1-ffmpeg

COPY docker/mediamtx/mediamtx.yml /mediamtx.yml
