FROM nginx:stable-alpine

ENV TZ="Asia/Jakarta"
ENV PS1="\u@\h:\w\\$ "

RUN apk add --no-cache bash tzdata && \
    ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && \
    echo $TZ > /etc/timezone