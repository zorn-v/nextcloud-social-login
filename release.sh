#!/bin/bash

GITHUB_REPO=zorn-v/nextcloud-social-login
APP_NAME=sociallogin

cd `dirname $0`

git diff --quiet --exit-code
[ $? != 0 ] && echo There is unstaged changes && exit 1
[ ! -f .credentials ] && echo No credentials file found && exit 1
. .credentials

VERSION=v`grep '<version>' appinfo/info.xml | sed 's/[^0-9.]//g'`
UPLOAD_URL=`curl -sH "Authorization: token $GITHUB_TOKEN" -d "{\"tag_name\":\"$VERSION\"}" https://api.github.com/repos/$GITHUB_REPO/releases | grep '"upload_url"' | sed 's/.*"\(https:.*\){.*/\1/'`
[ -z "$UPLOAD_URL" ] && echo Can not get upload url && exit 1

git checkout master
git checkout -b release
git pull origin master
git tag $VERSION
git log --format='%D- %s' | sed -e 's/HEAD -> release, //' -e 's/, origin\/master, origin\/HEAD, master//' -e 's/tag: v\([^-]*\)/\n## \1\n/' > CHANGELOG.md
git add CHANGELOG.md
sed -i '/<description><\/description>/ {
  a <description><![CDATA[
  r README.md
  a ]]></description>
  d
}' appinfo/info.xml
git commit -am 'Release'
git archive release --prefix=$APP_NAME/ -o release.tar.gz
git checkout master
git branch -D release

curl -sH "Authorization: token $GITHUB_TOKEN" -H 'Content-Type: application/octet-stream' --data-binary '@release.tar.gz' ${UPLOAD_URL}?name=release.tar.gz > /dev/null
DOWNLOAD_URL=https://github.com/$GITHUB_REPO/releases/download/$VERSION/release.tar.gz

SIG=`openssl dgst -sha512 -sign ~/.nextcloud/certificates/$APP_NAME.key release.tar.gz | openssl base64 -A`
curl -X POST -sH "Authorization: Token $NC_TOKEN" -H 'Content-Type: application/json' -d "{\"download\":\"$DOWNLOAD_URL\",\"signature\":\"$SIG\"}" https://apps.nextcloud.com/api/v1/apps/releases

rm -f release.tar.gz
