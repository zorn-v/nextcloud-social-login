#!/bin/bash

GITHUB_REPO=zorn-v/nextcloud-social-login
APP_NAME=sociallogin
NC_KEY_FILE=~/.nextcloud/certificates/$APP_NAME.key
NC_CERT_FILE=~/.nextcloud/certificates/$APP_NAME.crt

cd `dirname $0`

git checkout master
git pull origin master

git diff --quiet --exit-code
[ $? != 0 ] && echo There is unstaged changes && exit 1
[ ! -f .credentials ] && echo No credentials file found && exit 1
. .credentials
set -e
[ -z "$GITHUB_TOKEN" ] && echo GITHUB_TOKEN var is missing. Go to https://github.com/settings/tokens get one and put it in .credentials && exit 1
[ -z "$NC_TOKEN" ] && echo NC_TOKEN var is missing. Go to https://apps.nextcloud.com/account/token get one and put it in .credentials && exit 1

VERSION=v`grep '<version>' appinfo/info.xml | sed 's/[^0-9.]//g'`
UPLOAD_URL=`curl -sH "Authorization: token $GITHUB_TOKEN" -d "{\"tag_name\":\"$VERSION\"}" https://api.github.com/repos/$GITHUB_REPO/releases | grep '"upload_url"' | sed 's/.*"\(https:.*\){.*/\1/'`
[ -z "$UPLOAD_URL" ] && echo Can not get upload url && exit 1

git push
git checkout -b release
git tag $VERSION
git log --format='%D- %s' | sed -e 's/HEAD -> release, //' -e 's/, origin\/master, origin\/HEAD, master//' \
  -e 's/tag: v\([^-]*\)/\n## \1\n/' | sed 's/^.*\?- /- /' | uniq -u > CHANGELOG.md
git add CHANGELOG.md
sed -i '/<description> <\/description>/ {
  a <description><![CDATA[
  r README.md
  a ]]></description>
  d
}' appinfo/info.xml
git commit -am 'Release'
git archive release --prefix=$APP_NAME/ -o release.tar.gz
git checkout master
git branch -D release

if [ -n "$OCC_CMD_PATH" ]
then
  TMP_DIR=`mktemp -d`
  mv release.tar.gz $TMP_DIR/
  cd $TMP_DIR
  tar xzf release.tar.gz
  rm -f release.tar.gz
  php $OCC_CMD_PATH integrity:sign-app --path=$TMP_DIR/$APP_NAME --privateKey="$NC_KEY_FILE" --certificate="$NC_CERT_FILE"
  tar czf release.tar.gz $APP_NAME
  rm -rf $APP_NAME
fi

curl -sH "Authorization: token $GITHUB_TOKEN" -H 'Content-Type: application/octet-stream' --data-binary '@release.tar.gz' ${UPLOAD_URL}?name=release.tar.gz > /dev/null
DOWNLOAD_URL=https://github.com/$GITHUB_REPO/releases/download/$VERSION/release.tar.gz

SIG=`openssl dgst -sha512 -sign $NC_KEY_FILE release.tar.gz | openssl base64 -A`
curl -X POST -sH "Authorization: Token $NC_TOKEN" -H 'Content-Type: application/json' -d "{\"download\":\"$DOWNLOAD_URL\",\"signature\":\"$SIG\"}" https://apps.nextcloud.com/api/v1/apps/releases

rm -f release.tar.gz
